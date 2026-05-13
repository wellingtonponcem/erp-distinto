<?php
/**
 * Processa pagamento via Mercado Pago Transparent Checkout
 * POST /api/assinatura/processar_pagamento.php
 * Body JSON: formData do Payment Brick + campo "plano"
 */
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/assinatura.php';

header('Content-Type: application/json');

exigirAutenticacao();

$usuario = usuarioAtual();
$userId  = $usuario['id'];
$raw     = file_get_contents('php://input');
$body    = json_decode($raw, true);

if (!$body) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload inválido']);
    exit;
}

$plano = $body['plano'] ?? 'mensal';
if (!in_array($plano, ['mensal', 'anual'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Plano inválido']);
    exit;
}

$cfg   = getMercadoPagoConfig();
$preco = $plano === 'anual'
    ? (float)($cfg['plano_anual_preco']  ?? PLANO_ANUAL_PRECO)
    : (float)($cfg['plano_mensal_preco'] ?? PLANO_MENSAL_PRECO);

$accessToken = getMercadoPagoAccessToken();
if (!$accessToken) {
    http_response_code(500);
    echo json_encode(['error' => 'Gateway de pagamento não configurado.']);
    exit;
}

// Montar payload para /v1/payments
$payment = [
    'transaction_amount' => $preco,
    'description'        => 'Meus Roteiros — Plano ' . ($plano === 'anual' ? 'Anual' : 'Mensal'),
    'payment_method_id'  => $body['payment_method_id'] ?? null,
    'payer'              => $body['payer'] ?? [],
    'external_reference' => $userId . ':' . $plano,
    'notification_url'   => rtrim(APP_URL, '/') . '/api/assinatura/webhook_mercadopago.php',
];

// Campos exclusivos para cartão
if (!empty($body['token'])) {
    $payment['token']        = $body['token'];
    $payment['installments'] = (int)($body['installments'] ?? 1);
    if (!empty($body['issuer_id'])) {
        $payment['issuer_id'] = $body['issuer_id'];
    }
}

// Campos extras passados pelo Brick (3DS, etc.)
foreach (['three_d_secure_mode','processing_mode','merchant_account_id'] as $k) {
    if (!empty($body[$k])) $payment[$k] = $body[$k];
}

// Remover nulls
$payment = array_filter($payment, fn($v) => $v !== null && $v !== '');

error_log('[MP] processar_pagamento user=' . $userId . ' plano=' . $plano . ' method=' . ($body['payment_method_id'] ?? '-'));

// Chamar API do Mercado Pago
$ch = curl_init('https://api.mercadopago.com/v1/payments');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payment),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken,
        'X-Idempotency-Key: ' . $userId . '-' . $plano . '-' . uniqid(),
    ],
    CURLOPT_TIMEOUT        => 30,
]);
$mpResponse = curl_exec($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr    = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    error_log('[MP] cURL error: ' . $curlErr);
    http_response_code(500);
    echo json_encode(['error' => 'Erro de conexão com o gateway.']);
    exit;
}

$result = json_decode($mpResponse, true);

if (!$result) {
    error_log('[MP] Resposta inválida HTTP ' . $httpCode . ': ' . $mpResponse);
    http_response_code(500);
    echo json_encode(['error' => 'Resposta inválida do gateway.']);
    exit;
}

// Se a API retornou um erro estrutural (ex: 400 Bad Request, erro de CPF, etc)
if (isset($result['error']) && is_string($result['error']) || (isset($result['status']) && is_int($result['status']))) {
    $msg = $result['message'] ?? 'Erro na validação do pagamento.';
    error_log('[MP] Erro da API: ' . $mpResponse);
    http_response_code(400);
    echo json_encode(['error' => 'Erro recusado pelo banco: ' . $msg]);
    exit;
}

error_log('[MP] status=' . ($result['status'] ?? '-') . ' detail=' . ($result['status_detail'] ?? '-') . ' id=' . ($result['id'] ?? '-'));

// Ativar assinatura imediatamente para pagamentos aprovados
if (($result['status'] ?? '') === 'approved') {
    ativarAssinatura(
        $userId,
        'mercadopago',
        (string)($result['id'] ?? uniqid()),
        $plano,
        $preco,
        null,
        $mpResponse
    );
}

// Retornar resposta completa para o Brick tratar (QR code PIX, etc.)
echo json_encode([
    'status'               => $result['status']        ?? 'error',
    'status_detail'        => $result['status_detail'] ?? null,
    'id'                   => $result['id']            ?? null,
    'point_of_interaction' => $result['point_of_interaction'] ?? null,
]);
