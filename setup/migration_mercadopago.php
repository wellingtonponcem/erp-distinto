<?php
/**
 * Migration: Substituir Abacate Pay → Mercado Pago
 * Acesse: /setup/migration_mercadopago.php?token=distinto_saas_2025
 */
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

if (($_GET['token'] ?? '') !== 'distinto_saas_2025') {
    http_response_code(403);
    die(json_encode(['erro' => 'Token inválido']));
}

$db  = Database::get();
$log = [];

$migracoes = [
    // Mercado Pago — colunas na configuracao_empresa
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS mercadopago_access_token  TEXT",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS mercadopago_public_key     TEXT",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS mercadopago_webhook_secret TEXT",

    // Manter preços (já existem da migration_saas, mas idempotente)
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS plano_mensal_preco DECIMAL(10,2) DEFAULT 15.00",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS plano_anual_preco  DECIMAL(10,2) DEFAULT 158.00",
];

foreach ($migracoes as $sql) {
    try {
        $db->exec($sql);
        $log[] = ['ok' => true,  'sql' => substr($sql, 0, 80)];
    } catch (Exception $e) {
        $log[] = ['ok' => false, 'sql' => substr($sql, 0, 80), 'erro' => $e->getMessage()];
    }
}

header('Content-Type: application/json');
echo json_encode(['migrações' => $log], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
