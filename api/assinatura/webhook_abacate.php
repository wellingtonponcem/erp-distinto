<?php
/**
 * Webhook: Abacate Pay (PIX + Cartão)
 *
 * Abacate Pay envia um POST com header X-AbacatePay-Token = webhook_secret.
 * O user_id do assinante vem em data.metadata.user_id (passado no link de checkout).
 *
 * Eventos tratados:
 *   PAYMENT_PAID        → ativar/renovar assinatura
 *   SUBSCRIPTION_CANCELLED → cancelar assinatura
 *   PAYMENT_REFUNDED    → revogar assinatura
 *   CHARGE_CHARGEBACK   → revogar assinatura
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/assinatura.php';

// ── 1. Validar token do webhook ───────────────────────────────────────────────
$db = Database::get();

$abacateConfig = getAbacateConfig();
$webhookSecret = $abacateConfig['webhook_secret'] ?? '';

$tokenEnviado = $_SERVER['HTTP_X_ABACATEPAY_TOKEN'] ?? '';

if ($webhookSecret && $tokenEnviado !== $webhookSecret) {
    http_response_code(401);
    echo json_encode(['erro' => 'Token inválido']);
    exit;
}

// ── 2. Ler payload ────────────────────────────────────────────────────────────
$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody, true);

if (!$payload) {
    http_response_code(400);
    echo json_encode(['erro' => 'Payload inválido']);
    exit;
}

// ── 3. Registrar payload bruto para debug ─────────────────────────────────────
$evento     = $payload['event']           ?? '';
$data       = $payload['data']            ?? [];
$externalId = $data['id']                ?? ($data['billing']['id'] ?? '');
$userId     = $data['metadata']['user_id'] ?? ($data['customer']['metadata']['user_id'] ?? '');
$plan       = $data['metadata']['plan']   ?? 'mensal';   // mensal | anual
$amount     = (float)($data['amount']     ?? $data['billing']['amount'] ?? 0) / 100; // centavos → reais

// Expiração: mensal = 31 dias, anual = 366 dias a partir de agora
$diasPlano   = ($plan === 'anual') ? 366 : 31;
$expiresAt   = date('Y-m-d H:i:s', strtotime("+{$diasPlano} days"));

// ── 4. Processar evento ───────────────────────────────────────────────────────
try {
    switch ($evento) {
        case 'PAYMENT_PAID':
        case 'SUBSCRIPTION_RENEWED':
            if (!$userId) {
                // Tentar localizar pelo external_id em subscriptions existentes
                $stmt = $db->prepare("SELECT user_id FROM subscriptions WHERE external_id = ? LIMIT 1");
                $stmt->execute([$externalId]);
                $userId = $stmt->fetchColumn() ?: '';
            }

            if ($userId) {
                ativarAssinatura($userId, 'abacatepay', $externalId, $plan, $amount, $expiresAt);
                // Log do payload bruto
                $db->prepare("UPDATE subscriptions SET raw_payload = ? WHERE external_id = ? ORDER BY created_at DESC LIMIT 1")
                   ->execute([json_encode($payload), $externalId]);
            }
            break;

        case 'SUBSCRIPTION_CANCELLED':
        case 'PAYMENT_REFUNDED':
        case 'CHARGE_CHARGEBACK':
            if ($externalId) {
                cancelarAssinatura($externalId, 'abacatepay');
            }
            break;

        default:
            // Evento não tratado — apenas loga e responde 200
            break;
    }

    http_response_code(200);
    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
