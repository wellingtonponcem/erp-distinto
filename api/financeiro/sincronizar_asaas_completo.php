<?php
/**
 * API: Sincronização Completa do Asaas
 * Busca TODAS as cobranças do Asaas (paginação automática) e sincroniza
 * com os lançamentos financeiros locais. Também retorna o saldo real da conta.
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/asaas.php';
require_once __DIR__ . '/../../includes/financeiro_custos.php';
require_once __DIR__ . '/../../includes/contratos.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['success' => false, 'erro' => 'Método não permitido'], 405);
}

try {
    $db = Database::get();
    garantirEstruturaFinanceira($db);

    $asaas = new AsaasService();
    if (!$asaas->estaConfigurado()) {
        responderJson(['success' => false, 'erro' => 'Asaas não está configurado.'], 400);
    }

    $criados = 0;
    $atualizados = 0;
    $removidos = 0;
    $pagos = 0;
    $erros = [];

    // Buscar TODOS os pagamentos do Asaas com paginação
    $offset = 0;
    $limit = 100;
    $totalProcessados = 0;

    do {
        try {
            $res = $asaas->listarCobrancas($limit, $offset);
        } catch (Exception $e) {
            $erros[] = "Erro ao buscar pagamentos (offset {$offset}): " . $e->getMessage();
            break;
        }

        $payments = $res['data'] ?? [];

        if (empty($payments)) {
            break;
        }

        foreach ($payments as $payment) {
            if (empty($payment['id'])) {
                continue;
            }

            $asaasId = $payment['id'];
            $statusAsaas = strtolower((string)($payment['status'] ?? ''));
            $externalReference = $payment['externalReference'] ?? '';

            // Busca se já existe lançamento local com este asaas_id
            $stmtLocal = $db->prepare("SELECT id, status, valor_pago FROM lancamentos WHERE asaas_id = ? LIMIT 1");
            $stmtLocal->execute([$asaasId]);
            $local = $stmtLocal->fetch(PDO::FETCH_ASSOC);

            // Se foi deletado/estornado no Asaas, remove local
            if (in_array($statusAsaas, ['deleted', 'refunded', 'refund_requested'], true)) {
                if ($local) {
                    $db->prepare("DELETE FROM lancamentos WHERE id = ?")->execute([$local['id']]);
                    $removidos++;
                }
                continue;
            }

            if ($local) {
                // Já existe — atualiza
                $novoStatus = 'pendente';
                $valorPago = 0;

                if (in_array($statusAsaas, ['received', 'confirmed'], true)) {
                    $novoStatus = 'pago';
                    $valorPago = (float)($payment['value'] ?? $local['valor']);
                    $pagos++;
                } elseif ($statusAsaas === 'overdue') {
                    $novoStatus = 'atrasado';
                }

                $sets = ['status = ?', 'valor_pago = ?'];
                $params = [$novoStatus, $valorPago];

                if (tabelaTemColuna($db, 'lancamentos', 'conciliado')) {
                    $sets[] = 'conciliado = ?';
                    $params[] = $novoStatus === 'pago' ? 1 : 0;
                }

                if ($novoStatus === 'pago') {
                    $dataPagamento = $payment['paymentDate'] ?? $payment['clientPaymentDate'] ?? date('Y-m-d');
                    $sets[] = 'data_pagamento = ?';
                    $params[] = $dataPagamento;

                    $sets[] = 'asaas_boleto_url = ?';
                    $params[] = $payment['bankSlipUrl'] ?? null;

                    $sets[] = 'asaas_invoice_url = ?';
                    $params[] = $payment['invoiceUrl'] ?? null;
                }

                $params[] = $local['id'];
                $db->prepare("UPDATE lancamentos SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
                $atualizados++;
            } else {
                // Não existe local — cria novo lançamento
                // Tenta encontrar o cliente local pelo externalReference ou nome
                $clienteNome = $payment['customerName'] ?? 'Cliente Asaas';
                $clienteId = null;

                if (!empty($externalReference) && !str_starts_with((string)$externalReference, 'manual_')) {
                    // externalReference pode ser ID de contrato ou de cliente
                    $stmtCli = $db->prepare("SELECT id, nome FROM clientes WHERE id = ? OR asaas_customer_id = ? LIMIT 1");
                    $stmtCli->execute([$externalReference, $externalReference]);
                    $clienteData = $stmtCli->fetch(PDO::FETCH_ASSOC);
                    if ($clienteData) {
                        $clienteId = $clienteData['id'];
                        $clienteNome = $clienteData['nome'];
                    }
                }

                // Se não achou pelo externalReference, tenta pelo customer (asaas_customer_id)
                if (!$clienteId && !empty($payment['customer'])) {
                    $stmtCli = $db->prepare("SELECT id, nome FROM clientes WHERE asaas_customer_id = ? LIMIT 1");
                    $stmtCli->execute([$payment['customer']]);
                    $clienteData = $stmtCli->fetch(PDO::FETCH_ASSOC);
                    if ($clienteData) {
                        $clienteId = $clienteData['id'];
                        $clienteNome = $clienteData['nome'];
                    }
                }

                $descricaoAsaas = $payment['description'] ?? 'Cobrança Asaas';

                $contratoMock = [
                    'cliente_nome' => $clienteNome,
                    'cliente_id' => $clienteId,
                    'titulo' => $descricaoAsaas,
                    'id' => null
                ];

                // Se for parcelamento, processa cada parcela
                $parcelas = [];
                if (!empty($payment['installment'])) {
                    try {
                        $parcelas = $asaas->listarCobrancasPorParcelamento(
                            is_array($payment['installment'])
                                ? ($payment['installment']['id'] ?? '')
                                : $payment['installment']
                        );
                    } catch (Exception $e) {
                        // Se falhar, trata como pagamento único
                    }
                }

                if (!empty($parcelas)) {
                    $totalPars = count($parcelas);
                    foreach ($parcelas as $idx => $parcela) {
                        if (!empty($parcela['id'])) {
                            gravarLancamentoAsaas(
                                $db, $contratoMock, $parcela,
                                "[Parcela " . ($idx + 1) . " de {$totalPars}] " . $descricaoAsaas,
                                (float)($parcela['value'] ?? 0),
                                (string)($parcela['dueDate'] ?? date('Y-m-d')),
                                extrairParcelamentoAsaasId($parcela) ?: null
                            );
                            $criados++;
                        }
                    }
                } else {
                    gravarLancamentoAsaas(
                        $db, $contratoMock, $payment,
                        $descricaoAsaas,
                        (float)($payment['value'] ?? 0),
                        (string)($payment['dueDate'] ?? date('Y-m-d'))
                    );
                    $criados++;
                }

                if (in_array($statusAsaas, ['received', 'confirmed'], true)) {
                    $pagos++;
                }
            }

            $totalProcessados++;
        }

        $offset += $limit;
        $hasMore = !empty($res['hasMore']);
    } while ($hasMore && $offset < 5000); // Safety limit

    // Buscar saldo real do Asaas
    $saldoAsaas = 0;
    try {
        $dadosPainel = $asaas->obterSaldoEExtrato();
        $saldoAsaas = $dadosPainel['saldo'] ?? 0;
    } catch (Exception $e) {
        $erros[] = 'Erro ao buscar saldo: ' . $e->getMessage();
    }

    responderJson([
        'success' => true,
        'mensagem' => "Sincronização concluída. {$criados} criado(s), {$atualizados} atualizado(s), {$removidos} removido(s), {$pagos} pago(s). Saldo Asaas: R$ " . number_format($saldoAsaas, 2, ',', '.'),
        'criados' => $criados,
        'atualizados' => $atualizados,
        'removidos' => $removidos,
        'pagos' => $pagos,
        'saldo_asaas' => $saldoAsaas,
        'total_processados' => $totalProcessados,
        'erros' => $erros
    ]);

} catch (Throwable $e) {
    responderJson(['success' => false, 'erro' => 'Erro interno: ' . $e->getMessage()], 500);
}
