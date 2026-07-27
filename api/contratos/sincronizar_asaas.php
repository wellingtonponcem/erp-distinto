<?php
/**
 * API: sincroniza cobrancas Asaas vinculadas a um contrato.
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
    responderJson(['success' => false, 'erro' => 'Metodo nao permitido'], 405);
}

$id = $_POST['id'] ?? '';
if (!$id) {
    responderJson(['success' => false, 'erro' => 'ID do contrato e obrigatorio.'], 422);
}

function isAsaasNotFound(Throwable $e): bool {
    $msg = strtolower($e->getMessage());
    return str_contains($msg, 'http 404')
        || str_contains($msg, 'not found')
        || str_contains($msg, 'nao encontrada')
        || str_contains($msg, 'nao encontrado');
}

try {
    $db = Database::get();
    garantirEstruturaFinanceira($db);

    $stmtC = $db->prepare("SELECT * FROM contratos WHERE id = ? LIMIT 1");
    $stmtC->execute([$id]);
    $contrato = $stmtC->fetch(PDO::FETCH_ASSOC);

    if (!$contrato) {
        responderJson(['success' => false, 'erro' => 'Contrato nao encontrado.'], 404);
    }

    $asaas = new AsaasService();
    if (!$asaas->estaConfigurado()) {
        responderJson(['success' => false, 'erro' => 'Asaas nao esta configurado.'], 400);
    }

    $stmtL = $db->prepare("
        SELECT *
        FROM lancamentos
        WHERE cliente_id = ?
          AND asaas_id IS NOT NULL
          AND asaas_id != ''
          AND (descricao LIKE ? OR observacao LIKE ?)
        ORDER BY vencimento ASC
    ");
    $stmtL->execute([
        $contrato['cliente_id'],
        '%' . $contrato['titulo'] . '%',
        '%Contrato: ' . $contrato['id'] . '%'
    ]);
    $lancamentos = $stmtL->fetchAll(PDO::FETCH_ASSOC);

    $pagamentosPorId = [];
    $erros = [];
    $removidos = 0;

    try {
        foreach ($asaas->listarCobrancasPorReferencia($contrato['id']) as $payment) {
            if (empty($payment['id'])) {
                continue;
            }
            $pagamentosPorId[$payment['id']] = $payment;

            foreach (obterParcelasAsaas($asaas, $payment) as $parcela) {
                if (!empty($parcela['id'])) {
                    $pagamentosPorId[$parcela['id']] = $parcela;
                }
            }
        }
    } catch (Throwable $e) {
        $erros[] = 'Busca por referencia no Asaas: ' . $e->getMessage();
    }

    foreach ($lancamentos as $lancamento) {
        try {
            $payment = $asaas->obterCobranca($lancamento['asaas_id']);
            if (!empty($payment['id'])) {
                $pagamentosPorId[$payment['id']] = $payment;
            }

            foreach (obterParcelasAsaas($asaas, $payment) as $parcela) {
                if (!empty($parcela['id'])) {
                    $pagamentosPorId[$parcela['id']] = $parcela;
                }
            }
        } catch (Throwable $e) {
            if (isAsaasNotFound($e)) {
                $db->prepare("DELETE FROM lancamentos WHERE id = ?")->execute([$lancamento['id']]);
                $removidos++;
            } else {
                $erros[] = $lancamento['descricao'] . ': ' . $e->getMessage();
            }
        }
    }

    if (empty($pagamentosPorId) && empty($lancamentos)) {
        responderJson(['success' => false, 'erro' => 'Nenhuma cobranca Asaas foi encontrada para este contrato.'], 404);
    }

    $pagamentosOrdenados = array_values($pagamentosPorId);
    usort($pagamentosOrdenados, function ($a, $b) {
        return strcmp((string)($a['dueDate'] ?? ''), (string)($b['dueDate'] ?? ''));
    });

    $stmtLocal = $db->prepare("SELECT id FROM lancamentos WHERE asaas_id = ? LIMIT 1");
    $totalParcelasSaldo = 0;
    foreach ($pagamentosOrdenados as $payment) {
        $descricaoAsaas = strtolower((string)($payment['description'] ?? ''));
        $ehEntrada = str_contains($descricaoAsaas, '[entrada') || str_contains($descricaoAsaas, '[sinal');
        if (!$ehEntrada) {
            $totalParcelasSaldo++;
        }
    }

    $criados = 0;
    $atualizados = 0;
    $pagos = 0;
    $indiceParcela = 0;

    foreach ($pagamentosOrdenados as $payment) {
        if (empty($payment['id'])) {
            continue;
        }

        $statusAsaas = strtolower((string)($payment['status'] ?? ''));
        $stmtLocal->execute([$payment['id']]);
        $localId = $stmtLocal->fetchColumn();

        if (in_array($statusAsaas, ['deleted', 'refunded', 'refund_requested'], true)) {
            if ($localId) {
                $db->prepare("DELETE FROM lancamentos WHERE id = ?")->execute([$localId]);
                $removidos++;
            }
            continue;
        }

        $descricaoAsaas = strtolower((string)($payment['description'] ?? ''));
        $ehEntrada = str_contains($descricaoAsaas, '[entrada') || str_contains($descricaoAsaas, '[sinal');
        if ($ehEntrada) {
            $descricao = '[Sinal] ' . $contrato['titulo'];
        } else {
            $indiceParcela++;
            $descricao = '[Parcela ' . $indiceParcela . ' de ' . max(1, $totalParcelasSaldo) . '] ' . $contrato['titulo'];
        }

        gravarLancamentoAsaas(
            $db,
            $contrato,
            $payment,
            $descricao,
            (float)($payment['value'] ?? 0),
            (string)($payment['dueDate'] ?? date('Y-m-d')),
            extrairParcelamentoAsaasId($payment) ?: null
        );

        if ($localId) {
            $atualizados++;
        } else {
            $criados++;
        }

        if (in_array($statusAsaas, ['received', 'confirmed'], true)) {
            $pagos++;
        }
    }

    responderJson([
        'success' => true,
        'mensagem' => "Sincronizacao concluida. {$criados} criado(s), {$atualizados} atualizado(s), {$removidos} removido(s), {$pagos} pago(s).",
        'criados' => $criados,
        'atualizados' => $atualizados,
        'removidos' => $removidos,
        'pagos' => $pagos,
        'erros' => $erros
    ]);
} catch (Throwable $e) {
    responderJson(['success' => false, 'erro' => 'Erro interno: ' . $e->getMessage()], 500);
}
