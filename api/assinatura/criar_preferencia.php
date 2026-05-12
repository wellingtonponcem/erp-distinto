<?php
/**
 * Cria preferência de pagamento no Mercado Pago e redireciona para o checkout.
 * POST /api/assinatura/criar_preferencia.php
 * Body: plano=mensal|anual
 */
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/assinatura.php';

exigirAutenticacao();

$usuario = usuarioAtual();
$userId  = $usuario['id'];
$plano   = $_POST['plano'] ?? '';

if (!in_array($plano, ['mensal', 'anual'])) {
    header('Location: ' . raizUrl('/assinar.php?erro=plano_invalido'));
    exit;
}

$cfg   = getMercadoPagoConfig();
$preco = $plano === 'anual'
    ? (float)($cfg['plano_anual_preco']  ?? PLANO_ANUAL_PRECO)
    : (float)($cfg['plano_mensal_preco'] ?? PLANO_MENSAL_PRECO);

$titulo = $plano === 'anual'
    ? 'Meus Roteiros — Plano Anual'
    : 'Meus Roteiros — Plano Mensal';

$base   = rtrim(APP_URL, '/');

try {
    $initPoint = criarPreferenciaMercadoPago(
        userId:          $userId,
        plan:            $plano,
        preco:           $preco,
        titulo:          $titulo,
        notificationUrl: $base . '/api/assinatura/webhook_mercadopago.php',
        successUrl:      $base . '/assinar.php?status=sucesso',
        failureUrl:      $base . '/assinar.php?status=falha',
        pendingUrl:      $base . '/assinar.php?status=pendente',
    );

    header('Location: ' . $initPoint);
    exit;

} catch (RuntimeException $e) {
    error_log('[MP] criar_preferencia erro: ' . $e->getMessage());
    header('Location: ' . raizUrl('/assinar.php?erro=checkout_falhou'));
    exit;
}
