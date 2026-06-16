<?php
/**
 * Helper unificado de ciclo de vida de Contratos Comerciais
 * Centraliza as ações executadas quando um contrato é assinado.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/asaas.php';
require_once __DIR__ . '/financeiro_custos.php';

function processarAssinaturaContrato(string $contratoId): array {
    $db = Database::get();
    garantirEstruturaFinanceira($db);

    $retorno = [
        'success' => true,
        'mensagem' => '',
        'asaas_cobranca' => false,
        'erros' => []
    ];

    try {
        $db->beginTransaction();

        // 1. Carrega contrato e dados_json
        $stmtC = $db->prepare("SELECT * FROM contratos WHERE id = ?");
        $stmtC->execute([$contratoId]);
        $contrato = $stmtC->fetch();

        if (!$contrato) {
            throw new Exception("Contrato não encontrado.");
        }

        $dadosJson = json_decode($contrato['dados_json'], true) ?: [];

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
        if ($asaas->estaConfigurado() && (int)($contrato['asaas_cobranca_gerada'] ?? 0) === 0) {
            try {
                // Prepara dados para gerar cobrança no Asaas
                $totalParcelas = (int)($dadosJson['asaas_total_parcelas'] ?? 1);
                $valorSinal = (float)($dadosJson['asaas_valor_sinal'] ?? 0);
                $sinalVencimento = $dadosJson['asaas_sinal_vencimento'] ?? date('Y-m-d');
                $firstDueDate = $dadosJson['asaas_first_due_date'] ?? date('Y-m-d', strtotime('+30 days'));
                $billingType = $dadosJson['asaas_billing_type'] ?? 'UNDEFINED';

                $dadosCobranca = [
                    'cliente_id' => $contrato['cliente_id'],
                    'cliente_nome' => $sig1['nome'] ?? $contrato['cliente_nome'],
                    'cliente_cpf_cnpj' => preg_replace('/\D/', '', $sig1['cpf'] ?? ''),
                    'cliente_email' => $sig1['email'] ?? '',
                    'cliente_telefone' => preg_replace('/\D/', '', $sig1['telefone'] ?? ''),
                    'valor_total' => (float)$contrato['valor_total'],
                    'vencimento' => $firstDueDate,
                    'billing_type' => $billingType,
                    'descricao' => "Contrato: " . $contrato['titulo'],
                    'external_reference' => $contrato['id'],
                    'total_parcelas' => $totalParcelas,
                    'valor_sinal' => $valorSinal,
                    'sinal_vencimento' => $sinalVencimento
                ];

                $cobrancaRes = $asaas->criarCobranca($dadosCobranca);

                // Gravar lançamentos locais na tabela `lancamentos`
                if (!empty($cobrancaRes)) {
                    $db->beginTransaction();

                    if (!empty($cobrancaRes['multiplo'])) {
                        // Se retornou múltiplo (sinal + parcelamento do saldo)
                        $sinal = $cobrancaRes['sinal'];
                        $saldo = $cobrancaRes['saldo'];

                        // 1. Gravar lançamento do Sinal
                        gravarLancamentoAsaas($db, $contrato, $sinal, "[Sinal] " . $contrato['titulo'], $valorSinal, $sinalVencimento);

                        // 2. Gravar lançamentos do Saldo
                        $saldoRestante = (float)$contrato['valor_total'] - $valorSinal;
                        if (!empty($saldo['installments'])) {
                            // Saldo Parcelado
                            foreach ($saldo['installments'] as $idx => $inst) {
                                gravarLancamentoAsaas($db, $contrato, $inst, "[Parcela " . ($idx + 1) . "] " . $contrato['titulo'], (float)$inst['value'], $inst['dueDate'], $inst['id']);
                            }
                        } else {
                            // Saldo em parcela única
                            gravarLancamentoAsaas($db, $contrato, $saldo, "[Saldo] " . $contrato['titulo'], $saldoRestante, $firstDueDate);
                        }
                    } else {
                        // Cobrança simples (única ou parcelamento direto do total sem sinal)
                        if (!empty($cobrancaRes['installments'])) {
                            // Parcelado direto
                            foreach ($cobrancaRes['installments'] as $idx => $inst) {
                                gravarLancamentoAsaas($db, $contrato, $inst, "[Parcela " . ($idx + 1) . "] " . $contrato['titulo'], (float)$inst['value'], $inst['dueDate'], $inst['id']);
                            }
                        } else {
                            // Parcela única
                            gravarLancamentoAsaas($db, $contrato, $cobrancaRes, $contrato['titulo'], (float)$contrato['valor_total'], $firstDueDate);
                        }
                    }

                    // Atualiza flag no contrato
                    $stmtUpContrato = $db->prepare("UPDATE contratos SET asaas_cobranca_gerada = 1 WHERE id = ?");
                    $stmtUpContrato->execute([$contratoId]);

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
function gravarLancamentoAsaas(PDO $db, array $contrato, array $asaasPayment, string $descricao, float $valor, string $vencimento, ?string $installmentId = null): void {
    $id = gerarId();
    
    $colunas = [
        'id', 'tipo', 'descricao', 'valor', 'valor_pago', 'categoria', 
        'cliente_fornecedor', 'vencimento', 'status', 'modalidade', 
        'observacao', 'cliente_id', 'asaas_id', 'asaas_boleto_url', 'asaas_invoice_url'
    ];
    $valores = ['?', "'receber'", '?', '?', '0', "'servicos'", '?', '?', '?', "'avista'", '?', '?', '?', '?', '?'];

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
        $contrato['cliente_nome'],
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
