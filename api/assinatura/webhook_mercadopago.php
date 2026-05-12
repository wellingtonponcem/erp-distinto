<?php
/**
 * Webhook — Mercado Pago
 * POST /api/assinatura/webhook_mercadopago.php
 *
 * Eventos tratados:
 *  payment.updated → approved  → ativarAssinatura
 *  payment.updated → refunded|charged_back → cancelarAssinatura
 */
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/assinatura.php';

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$rawBody    = file_get_contents('php://input');
$data       = json_decode($rawBody, true);
$xSignature = $_SERVER['HTTP_X_SIGNATURE']   ?? '';
$xRequestId = $_SERVER['HTTP_X_REQUEST_ID']  ?? '';
$dataId     = $data['data']['id']             ?? '';

// Registrar para debug
error_log('[MP Webhook] type=' . ($data['type'] ?? '-') . ' action=' . ($data['action'] ?? '-') . ' data.id=' . $dataId);

// ── Validação de assinatura ──────────────────────────────────────────────
$cfg    = getMercadoPagoConfig();
$secret = $cfg['mercadopago_webhook_secret'] ?? '';

if ($secret && $xSignature) {
    if (!validarAssinaturaMercadoPago($xSignature, $xRequestId, $dataId, $secret)) {
        error_log('[MP Webhook] Assinatura inválida — ignorado');
        http_response_code(401);
        exit;
    }
}

// ── Só tratar eventos de pagamento ──────────────────────────────────────
if (($data['type'] ?? '') !== 'payment') {
    http_response_code(200);
    exit;
}

if (!$dataId) {
    http_response_code(400);
    exit;
}

// ── Buscar detalhes do pagamento na API do MP ────────────────────────────
$accessToken = getMercadoPagoAccessToken();
if (!$accessToken) {
    error_log('[MP Webhook] Access token não configurado');
    http_response_code(500);
    exit;
}

$ch = curl_init('https://api.mercadopago.com/v1/payments/' . $dataId);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
    CURLOPT_TIMEOUT        => 15,
]);
$paymentJson = curl_exec($ch);
$httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    error_log('[MP Webhook] Erro ao buscar pagamento ' . $dataId . ': HTTP ' . $httpCode);
    http_response_code(200); // Responder 200 para MP não retentar
    exit;
}

$payment = json_decode($paymentJson, true);
$status  = $payment['status'] ?? '';
$extRef  = $payment['external_reference'] ?? ''; // formato: "userId:plan"
$amount  = (float)($payment['transaction_amount'] ?? 0);

// Parsear external_reference
$parts  = explode(':', $extRef, 2);
$userId = $parts[0] ?? '';
$plan   = $parts[1] ?? 'mensal';

if (!$userId) {
    error_log('[MP Webhook] external_reference inválido: ' . $extRef);
    http_response_code(200);
    exit;
}

// ── Agir conforme status ─────────────────────────────────────────────────
switch ($status) {
    case 'approved':
        ativarAssinatura(
            userId:     $userId,
            gateway:    'mercadopago',
            externalId: (string)$dataId,
            plan:       $plan,
            amount:     $amount,
            rawPayload: $rawBody
        );
        error_log("[MP Webhook] ✓ Assinatura ativada — user=$userId plan=$plan");
        break;

    case 'refunded':
    case 'charged_back':
        cancelarAssinatura(
            externalId: (string)$dataId,
            gateway:    'mercadopago',
            rawPayload: $rawBody
        );
        error_log("[MP Webhook] ✗ Assinatura cancelada — paymentId=$dataId status=$status");
        break;

    default:
        // pending, in_process, rejected, cancelled — sem ação
        error_log("[MP Webhook] Status ignorado: $status (paymentId=$dataId)");
        break;
}

http_response_code(200);
echo 'OK';
