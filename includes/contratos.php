<?php
/**
 * Helper unificado de ciclo de vida de Contratos Comerciais
 * Centraliza as ações executadas quando um contrato é assinado.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/asaas.php';
require_once __DIR__ . '/financeiro_custos.php';

function processarAssinaturaContrato(string $contratoId, array $opcoesFaturamento = []): array {
    $db = Database::get();
    garantirEstruturaFinanceira($db);

    $retorno = [
        'success' => true,
        'mensagem' => '',
        'asaas_cobranca' => false,
        'erros' => []
    ];

    try {
        // Limpa estado de erro do PostgreSQL antes de iniciar transação
        try {
            if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' && $db->inTransaction()) {
                $db->rollBack();
            }
        } catch (Exception $e) {}

        $db->beginTransaction();

        // 1. Carrega contrato e dados_json
        $stmtC = $db->prepare("SELECT * FROM contratos WHERE id = ?");
        $stmtC->execute([$contratoId]);
        $contrato = $stmtC->fetch();

        if (!$contrato) {
            throw new Exception("Contrato não encontrado.");
        }

        $dadosJson = json_decode($contrato['dados_json'], true) ?: [];
        if (!empty($opcoesFaturamento)) {
            $camposFinanceiros = [
                'asaas_billing_type',
                'asaas_total_parcelas',
                'asaas_first_due_date',
                'asaas_valor_sinal',
                'asaas_sinal_vencimento',
                'entrada_status',
                'entrada_forma_pagamento',
                'entrada_conta',
                'entrada_observacao',
                'gerar_apenas_saldo'
            ];

            foreach ($camposFinanceiros as $campo) {
                if (array_key_exists($campo, $opcoesFaturamento)) {
                    $dadosJson[$campo] = $opcoesFaturamento[$campo];
                }
            }
        }

        // 2. Se houver proposta vinculada, atualiza proposta e oportunidade no CRM
        if (!empty($contrato['proposta_id'])) {
            // Atualiza status da proposta para aceita
            $db->prepare("UPDATE propostas SET status = 'aceita' WHERE id = ?")
               ->execute([$contrato['proposta_id']]);

            // Atualiza status da oportunidade vinculada para 'ganha' no CRM
            $stmtProp = $db->prepare("SELECT oportunidade_id FROM propostas WHERE id = ?");
            $stmtProp->execute([$contrato['proposta_id']]);
            $prop = $stmtProp->fetch();
            
            if ($prop && !empty($prop['oportunidade_id'])) {
                $db->prepare("UPDATE oportunidades SET etapa = 'ganha', atualizado_em = CURRENT_TIMESTAMP WHERE id = ?")
                   ->execute([$prop['oportunidade_id']]);
            }
        }

        // 3. Atualizar dados do cliente cadastrado (CPF/CNPJ e Contato) caso estejam vazios
        $sig1 = $dadosJson['signatario_1'] ?? null;
        $sig2 = $dadosJson['signatario_2'] ?? null;
        $sigEscolhido = $sig1;
        if (!empty($opcoesFaturamento['sig_choice']) && $opcoesFaturamento['sig_choice'] === '2' && !empty($sig2['nome'])) {
            $sigEscolhido = $sig2;
        }
        $clienteFornecedorNome = $sigEscolhido['nome'] ?? $contrato['cliente_nome'];
        if ($sig1 && !empty($sig1['nome']) && !empty($contrato['cliente_id'])) {
            $stmtGetCli = $db->prepare("SELECT cpf_cnpj, contato FROM clientes WHERE id = ?");
            $stmtGetCli->execute([$contrato['cliente_id']]);
            $cliExistente = $stmtGetCli->fetch();
            
            if ($cliExistente) {
                $novoCpf = $cliExistente['cpf_cnpj'];
                if (empty($novoCpf) && !empty($sig1['cpf'])) {
                    $novoCpf = preg_replace('/\D/', '', $sig1['cpf']);
                }
                $novoContato = $cliExistente['contato'];
                if (empty($novoContato) && !empty($sig1['email'])) {
                    $novoContato = $sig1['email'];
                }
                
                $stmtUpCli = $db->prepare("UPDATE clientes SET cpf_cnpj = ?, contato = ? WHERE id = ?");
                $stmtUpCli->execute([$novoCpf, $novoContato, $contrato['cliente_id']]);
            }
        }

        $db->commit();
        $retorno['mensagem'] = "Contrato atualizado para ASSINADO e CRM atualizado.";

        // 4. Integração de Faturamento Automático (Asaas)
        // Rodamos fora da transação principal para que erros de API externa não anulem o status do contrato
        $asaas = new AsaasService();
        $ignorarFlagAsaas = !empty($opcoesFaturamento['ignorar_flag_asaas']);
        if ($asaas->estaConfigurado() && ((int)($contrato['asaas_cobranca_gerada'] ?? 0) === 0 || $ignorarFlagAsaas)) {
            try {
                // Prepara dados para gerar cobrança no Asaas
                $tipoProposta = '';
                if (!empty($contrato['proposta_id'])) {
                    $stmtTipo = $db->prepare("SELECT tipo FROM propostas WHERE id = ?");
                    $stmtTipo->execute([$contrato['proposta_id']]);
                    $tipoProposta = (string)($stmtTipo->fetchColumn() ?: '');
                }

                $billingType = $opcoesFaturamento['asaas_billing_type'] ?? $dadosJson['asaas_billing_type'] ?? 'UNDEFINED';
                $sinalVencimento = $dadosJson['asaas_sinal_vencimento'] ?? date('Y-m-d');
                if (!empty($opcoesFaturamento['asaas_sinal_vencimento'])) {
                    $sinalVencimento = $opcoesFaturamento['asaas_sinal_vencimento'];
                }
                $firstDueDate = adicionarMesesData($sinalVencimento, 1) ?: ($dadosJson['asaas_first_due_date'] ?? date('Y-m-d', strtotime('+30 days')));
                if (!empty($opcoesFaturamento['asaas_first_due_date'])) {
                    $firstDueDate = $opcoesFaturamento['asaas_first_due_date'];
                } elseif (!empty($dadosJson['asaas_first_due_date'])) {
                    $firstDueDate = $dadosJson['asaas_first_due_date'];
                }
                $valorSinal = 0.0;
                $totalParcelas = (int)($opcoesFaturamento['asaas_total_parcelas'] ?? $dadosJson['asaas_total_parcelas'] ?? 1);
                $entradaStatus = $opcoesFaturamento['entrada_status'] ?? $dadosJson['entrada_status'] ?? '';
                $entradaJaPaga = $entradaStatus === 'pago';
                $gerarApenasSaldo = !empty($opcoesFaturamento['gerar_apenas_saldo']) || !empty($dadosJson['gerar_apenas_saldo']) || $entradaJaPaga;
                $valorTotalCobranca = (float)$contrato['valor_total'];
                $valorSinal = isset($opcoesFaturamento['asaas_valor_sinal'])
                    ? (float)$opcoesFaturamento['asaas_valor_sinal']
                    : (float)($dadosJson['asaas_valor_sinal'] ?? 0);

                if ($entradaStatus === 'nao_aplica') {
                    $valorSinal = 0.0;
                    $gerarApenasSaldo = false;
                }

                if ($tipoProposta === 'casamento') {
                    $planoId = detectarPlanoCasamento($dadosJson);
                    if ($billingType === 'CREDIT_CARD') {
                        $totalParcelas = max(1, $totalParcelas);
                        $firstDueDate = $opcoesFaturamento['asaas_first_due_date'] ?? $dadosJson['asaas_first_due_date'] ?? $firstDueDate;
                    } else {
                        if ($valorSinal <= 0) {
                            $percentualSinal = $planoId === 'heritage' ? 0.25 : 0.20;
                            $valorSinal = round((float)$contrato['valor_total'] * $percentualSinal, 2);
                        }
                        $totalParcelas = calcularParcelasSaldoCasamento($dadosJson, $totalParcelas);
                    }
                }

                if ($gerarApenasSaldo && $valorSinal > 0) {
                    $valorTotalCobranca = max(0.0, (float)$contrato['valor_total'] - $valorSinal);
                    $valorSinalAsaas = 0.0;
                } else {
                    $valorSinalAsaas = $valorSinal;
                }

                $dadosCobranca = [
                    'cliente_id' => $contrato['cliente_id'],
                    'cliente_nome' => $sigEscolhido['nome'] ?? $contrato['cliente_nome'],
                    'cliente_cpf_cnpj' => preg_replace('/\D/', '', $sigEscolhido['cpf'] ?? ''),
                    'cliente_email' => $sigEscolhido['email'] ?? '',
                    'cliente_telefone' => preg_replace('/\D/', '', $sigEscolhido['telefone'] ?? ''),
                    'valor_total' => $valorTotalCobranca,
                    'vencimento' => $firstDueDate,
                    'billing_type' => $billingType,
                    'descricao' => "Contrato: " . $contrato['titulo'],
                    'external_reference' => $contrato['id'],
                    'total_parcelas' => $totalParcelas,
                    'valor_sinal' => $valorSinalAsaas,
                    'sinal_vencimento' => $sinalVencimento
                ];

                $cobrancaRes = $asaas->criarCobranca($dadosCobranca);

                // Gravar lançamentos locais na tabela `lancamentos`
                if (!empty($cobrancaRes)) {
                    $db->beginTransaction();

                    if ($entradaJaPaga && $valorSinal > 0) {
                        gravarLancamentoEntradaManual(
                            $db,
                            $contrato,
                            $valorSinal,
                            $sinalVencimento,
                            $opcoesFaturamento['entrada_forma_pagamento'] ?? $dadosJson['entrada_forma_pagamento'] ?? 'pix',
                            $opcoesFaturamento['entrada_conta'] ?? $dadosJson['entrada_conta'] ?? 'c6',
                            $clienteFornecedorNome,
                            $opcoesFaturamento['entrada_observacao'] ?? $dadosJson['entrada_observacao'] ?? 'Entrada paga fora do Asaas.'
                        );
                    }

                    if (!empty($cobrancaRes['multiplo'])) {
                        // Se retornou múltiplo (sinal + parcelamento do saldo)
                        $sinal = $cobrancaRes['sinal'];
                        $saldo = $cobrancaRes['saldo'];

                        // 1. Gravar lançamento do Sinal
                        gravarLancamentoAsaas($db, $contrato, $sinal, "[Sinal] " . $contrato['titulo'], $valorSinal, $sinalVencimento, null, $clienteFornecedorNome);

                        // 2. Gravar lançamentos do Saldo
                        $saldoRestante = (float)$contrato['valor_total'] - $valorSinal;
                        $parcelasSaldo = obterParcelasAsaas($asaas, $saldo);
                        if (!empty($parcelasSaldo)) {
                            // Saldo Parcelado
                            $totalParcelasSaldo = count($parcelasSaldo);
                            foreach ($parcelasSaldo as $idx => $inst) {
                                gravarLancamentoAsaas($db, $contrato, $inst, "[Parcela " . ($idx + 1) . " de {$totalParcelasSaldo}] " . $contrato['titulo'], (float)$inst['value'], $inst['dueDate'], $inst['id'], $clienteFornecedorNome);
                            }
                        } else {
                            // Saldo em parcela única
                            gravarLancamentoAsaas($db, $contrato, $saldo, "[Saldo] " . $contrato['titulo'], $saldoRestante, $firstDueDate, null, $clienteFornecedorNome);
                        }
                    } else {
                        // Cobrança simples (única ou parcelamento direto do total sem sinal)
                        $parcelasDiretas = obterParcelasAsaas($asaas, $cobrancaRes);
                        if (!empty($parcelasDiretas)) {
                            // Parcelado direto
                            $totalParcelasDiretas = count($parcelasDiretas);
                            foreach ($parcelasDiretas as $idx => $inst) {
                                gravarLancamentoAsaas($db, $contrato, $inst, "[Parcela " . ($idx + 1) . " de {$totalParcelasDiretas}] " . $contrato['titulo'], (float)$inst['value'], $inst['dueDate'], $inst['id'], $clienteFornecedorNome);
                            }
                        } else {
                            // Parcela única
                            gravarLancamentoAsaas($db, $contrato, $cobrancaRes, $contrato['titulo'], (float)$contrato['valor_total'], $firstDueDate, null, $clienteFornecedorNome);
                        }
                    }

                    // Atualiza flag no contrato
                    $dadosJson['asaas_billing_type'] = $billingType;
                    $dadosJson['asaas_total_parcelas'] = $totalParcelas;
                    $dadosJson['asaas_first_due_date'] = $firstDueDate;
                    $dadosJson['asaas_valor_sinal'] = $valorSinal;
                    $dadosJson['asaas_sinal_vencimento'] = $sinalVencimento;
                    $dadosJson['entrada_status'] = $entradaStatus ?: ($entradaJaPaga ? 'pago' : ($dadosJson['entrada_status'] ?? 'pendente'));
                    $dadosJson['gerar_apenas_saldo'] = $gerarApenasSaldo ? 1 : 0;

                    $stmtUpContrato = $db->prepare("UPDATE contratos SET asaas_cobranca_gerada = 1, dados_json = ? WHERE id = ?");
                    $stmtUpContrato->execute([json_encode($dadosJson, JSON_UNESCAPED_UNICODE), $contratoId]);

                    $db->commit();
                    $retorno['asaas_cobranca'] = true;
                    $retorno['mensagem'] .= " Cobranças geradas no Asaas e lançamentos financeiros criados.";
                }
            } catch (Exception $ex) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $retorno['erros'][] = "Erro ao gerar faturamento Asaas: " . $ex->getMessage();
            }
        }

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $retorno['success'] = false;
        $retorno['erro'] = $e->getMessage();
    }

    return $retorno;
}

/**
 * Auxiliar para persistir o lançamento financeiro local baseado na resposta do Asaas
 */
function extrairParcelamentoAsaasId(array $asaasPayment): string {
    $installment = $asaasPayment['installment'] ?? $asaasPayment['installmentId'] ?? $asaasPayment['installment_id'] ?? '';

    if (is_array($installment)) {
        return (string)($installment['id'] ?? '');
    }

    return (string)$installment;
}

function obterParcelasAsaas(AsaasService $asaas, array $asaasPayment): array {
    if (!empty($asaasPayment['installments']) && is_array($asaasPayment['installments'])) {
        return $asaasPayment['installments'];
    }

    $installmentId = extrairParcelamentoAsaasId($asaasPayment);
    if ($installmentId === '') {
        return [];
    }

    $parcelas = $asaas->listarCobrancasPorParcelamento($installmentId);
    usort($parcelas, function ($a, $b) {
        return strcmp((string)($a['dueDate'] ?? ''), (string)($b['dueDate'] ?? ''));
    });

    return $parcelas;
}

function gravarLancamentoAsaas(PDO $db, array $contrato, array $asaasPayment, string $descricao, float $valor, string $vencimento, ?string $installmentId = null, ?string $clienteFornecedor = null): void {
    if (empty($asaasPayment['id'])) {
        return;
    }

    $stmtExiste = $db->prepare("SELECT id FROM lancamentos WHERE asaas_id = ? LIMIT 1");
    $stmtExiste->execute([$asaasPayment['id']]);
    $existente = $stmtExiste->fetchColumn();
    if ($existente) {
        atualizarLancamentoAsaas($db, (string)$existente, $asaasPayment, $descricao, $valor, $vencimento);
        return;
    }

    $id = gerarId();
    
    $colunas = [
        'id', 'tipo', 'descricao', 'valor', 'valor_pago', 'categoria', 
        'cliente_fornecedor', 'vencimento', 'status', 'modalidade', 
        'observacao', 'cliente_id', 'asaas_id', 'asaas_boleto_url', 'asaas_invoice_url',
        'conta_id', 'conciliado'
    ];
    $valores = ['?', "'receber'", '?', '?', '0', "'servicos'", '?', '?', '?', "'avista'", '?', '?', '?', '?', '?', "'asaas'", '1'];

    $statusAsaas = strtolower($asaasPayment['status'] ?? 'pending');
    $statusLocal = 'pendente';
    $valorPago = 0.0;
    $dataPagamento = null;

    if (in_array($statusAsaas, ['received', 'confirmed'])) {
        $statusLocal = 'pago';
        $valorPago = (float)($asaasPayment['value'] ?? $valor);
        $dataPagamento = $asaasPayment['paymentDate'] ?? date('Y-m-d');
    } elseif ($statusAsaas === 'overdue') {
        $statusLocal = 'atrasado';
    }

    if ($dataPagamento) {
        $colunas[] = 'data_pagamento';
        $valores[] = '?';
    }

    $params = [
        $id,
        $descricao,
        $valor,
        $clienteFornecedor ?? $contrato['cliente_nome'],
        $vencimento,
        $statusLocal,
        "Cobrança gerada via Asaas de forma automática.",
        $contrato['cliente_id'],
        $asaasPayment['id'],
        $asaasPayment['bankSlipUrl'] ?? null,
        $asaasPayment['invoiceUrl'] ?? null
    ];

    if ($dataPagamento) {
        $params[] = $dataPagamento;
    }

    $stmt = $db->prepare("INSERT INTO lancamentos (" . implode(', ', $colunas) . ") VALUES (" . implode(', ', $valores) . ")");
    $stmt->execute($params);
}

function atualizarLancamentoAsaas(PDO $db, string $lancamentoId, array $asaasPayment, string $descricao, float $valor, string $vencimento): void {
    $statusAsaas = strtolower($asaasPayment['status'] ?? 'pending');
    $statusLocal = 'pendente';
    $valorPago = 0.0;
    $dataPagamento = null;

    if (in_array($statusAsaas, ['received', 'confirmed'], true)) {
        $statusLocal = 'pago';
        $valorPago = (float)($asaasPayment['value'] ?? $valor);
        $dataPagamento = $asaasPayment['paymentDate'] ?? $asaasPayment['clientPaymentDate'] ?? date('Y-m-d');
    } elseif ($statusAsaas === 'overdue') {
        $statusLocal = 'atrasado';
    } elseif (in_array($statusAsaas, ['deleted', 'refunded', 'refund_requested'], true)) {
        $db->prepare("DELETE FROM lancamentos WHERE id = ?")->execute([$lancamentoId]);
        return;
    }

    $sets = [
        'descricao = ?',
        'valor = ?',
        'vencimento = ?',
        'status = ?',
        'valor_pago = ?',
        'asaas_boleto_url = ?',
        'asaas_invoice_url = ?'
    ];
    $params = [
        $descricao,
        $valor,
        $vencimento,
        $statusLocal,
        $valorPago,
        $asaasPayment['bankSlipUrl'] ?? null,
        $asaasPayment['invoiceUrl'] ?? null
    ];

    if ($dataPagamento && tabelaTemColuna($db, 'lancamentos', 'data_pagamento')) {
        $sets[] = 'data_pagamento = ?';
        $params[] = $dataPagamento;
    }

    if (tabelaTemColuna($db, 'lancamentos', 'conciliado')) {
        $sets[] = 'conciliado = ?';
        $params[] = $statusLocal === 'pago' ? 1 : 0;
    }

    $params[] = $lancamentoId;
    $stmt = $db->prepare('UPDATE lancamentos SET ' . implode(', ', $sets) . ' WHERE id = ?');
    $stmt->execute($params);
}

function gravarLancamentoEntradaManual(
    PDO $db,
    array $contrato,
    float $valor,
    string $dataPagamento,
    string $formaPagamento = 'pix',
    string $contaId = 'c6',
    ?string $clienteFornecedor = null,
    string $observacao = 'Entrada paga fora do Asaas, aguardando conciliação por OFX.'
): void {
    if ($valor <= 0) {
        return;
    }

    $id = gerarId();
    $dataPagamento = $dataPagamento ?: date('Y-m-d');
    $colunas = [
        'id', 'tipo', 'descricao', 'valor', 'valor_pago', 'categoria',
        'cliente_fornecedor', 'vencimento', 'status', 'modalidade',
        'observacao', 'cliente_id'
    ];
    $valores = ['?', "'receber'", '?', '?', '?', "'servicos'", '?', '?', "'pago'", "'avista'", '?', '?'];
    $params = [
        $id,
        "[Entrada paga] " . $contrato['titulo'],
        $valor,
        $valor,
        $clienteFornecedor ?? $contrato['cliente_nome'],
        $dataPagamento,
        trim($observacao . " Contrato: " . $contrato['id']),
        $contrato['cliente_id']
    ];

    if (tabelaTemColuna($db, 'lancamentos', 'forma_pagamento')) {
        $colunas[] = 'forma_pagamento';
        $valores[] = '?';
        $params[] = $formaPagamento;
    }
    if (tabelaTemColuna($db, 'lancamentos', 'conta_id')) {
        $colunas[] = 'conta_id';
        $valores[] = '?';
        $params[] = $contaId ?: null;
    }
    if (tabelaTemColuna($db, 'lancamentos', 'conciliado')) {
        $colunas[] = 'conciliado';
        $valores[] = '0';
    }
    if (tabelaTemColuna($db, 'lancamentos', 'data_pagamento')) {
        $colunas[] = 'data_pagamento';
        $valores[] = '?';
        $params[] = $dataPagamento;
    }

    $stmt = $db->prepare("INSERT INTO lancamentos (" . implode(', ', $colunas) . ") VALUES (" . implode(', ', $valores) . ")");
    $stmt->execute($params);
}
