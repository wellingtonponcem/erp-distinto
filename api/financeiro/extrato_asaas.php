<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/asaas.php';
require_once __DIR__ . '/../../includes/financeiro_custos.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

try {
    $db = Database::get();
    try {
        garantirEstruturaFinanceira($db);
    } catch (Throwable $e) {}

    $dataInicio = $_POST['data_inicio'] ?? '';
    $dataFim = $_POST['data_fim'] ?? '';

    if (!$dataInicio || !$dataFim) {
        http_response_code(400);
        echo json_encode(['erro' => 'Data início e data fim são obrigatórias']);
        exit;
    }

    $asaas = new AsaasService();
    if (!$asaas->estaConfigurado()) {
        http_response_code(500);
        echo json_encode(['erro' => 'Asaas não configurado']);
        exit;
    }

    $extrato = $asaas->obterExtratoFinanceiro($dataInicio, $dataFim);

    $descricoesTipo = [
        'PAYMENT_RECEIVED' => 'Recebimento de cobrança',
        'PIX_TRANSACTION_CREDIT' => 'PIX recebido',
        'PIX_TRANSACTION_CREDIT_FEE' => 'Taxa de PIX recebido',
        'PIX_TRANSACTION_CREDIT_REFUND' => 'Estorno de PIX recebido',
        'PIX_TRANSACTION_CREDIT_REFUND_CANCELLATION' => 'Cancelamento estorno PIX',
        'BILL_PAYMENT' => 'Pagamento de conta',
        'BILL_PAYMENT_CANCELLED' => 'Cancelamento pagamento de conta',
        'BILL_PAYMENT_REFUNDED' => 'Estorno pagamento de conta',
        'BILL_PAYMENT_FEE' => 'Taxa de pagamento de conta',
        'TRANSFER' => 'Transferência bancária',
        'TRANSFER_FEE' => 'Taxa de transferência',
        'TRANSFER_REVERSAL' => 'Estorno de transferência',
        'PAYMENT_FEE' => 'Taxa de pagamento',
        'PAYMENT_FEE_REVERSAL' => 'Estorno de taxa de pagamento',
        'PAYMENT_REVERSAL' => 'Estorno de cobrança',
        'REVERSAL' => 'Estorno',
        'CHARGEBACK' => 'Chargeback',
        'CHARGEBACK_REVERSAL' => 'Cancelamento chargeback',
        'DEBIT' => 'Débito',
        'CREDIT' => 'Crédito',
        'ASAAS_CARD_TRANSACTION' => 'Transação cartão Asaas',
        'ASAAS_CARD_CASHBACK' => 'Cashback cartão',
        'CONTRACTED_CUSTOMER_PLAN_FEE' => 'Taxa de plano Asaas',
        'PAYMENT_CUSTODY_BLOCK' => 'Bloqueio de saldo por custódia',
        'PAYMENT_CUSTODY_BLOCK_REVERSAL' => 'Desbloqueio de saldo por custódia',
        'PARTIAL_PAYMENT' => 'Pagamento parcial',
    ];

    $transacoes = [];
    foreach ($extrato as $t) {
        $id = $t['id'] ?? uniqid();
        $tipoAsaas = $t['type'] ?? '';
        $valorRaw = (float)($t['value'] ?? 0);
        $data = substr($t['date'] ?? '', 0, 10);

        $tipo = $valorRaw >= 0 ? 'receber' : 'pagar';

        $descricao = $descricoesTipo[$tipoAsaas] ?? $tipoAsaas;
        if (!empty($t['description'])) {
            $descricao .= ' - ' . $t['description'];
        }

        $fitid = $id;
        $parsed = [
            'fitid' => $fitid,
            'tipo' => $tipo,
            'data' => $data,
            'valor' => abs($valorRaw),
            'descricao' => $descricao,
            'asaas_tipo' => $tipoAsaas,
            'duplicado' => false
        ];

        if (!$data || $parsed['valor'] <= 0) {
            continue;
        }

        $stmt = $db->prepare("SELECT id FROM lancamentos WHERE observacao LIKE ? LIMIT 1");
        $stmt->execute(["%[ASAAS:{$id}]%"]);
        if ($stmt->fetch()) {
            $parsed['duplicado'] = true;
        }

        $transacoes[] = $parsed;
    }

    echo json_encode(['ok' => true, 'transacoes' => $transacoes]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno: ' . $e->getMessage() . ' na linha ' . $e->getLine()]);
    exit;
}
