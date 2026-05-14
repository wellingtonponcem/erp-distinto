<?php
/**
 * Migração SaaS — Executar UMA VEZ via browser (acesso restrito).
 * Acesse: https://wedistinto.com/sistema/setup/migration_saas.php
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

// Proteção mínima: só admin pode rodar (via IP ou token de URL)
$token = $_GET['token'] ?? '';
if ($token !== 'distinto_saas_2025') {
    http_response_code(403);
    die('Acesso negado. Use ?token=distinto_saas_2025');
}

$db = Database::get();
$erros = [];
$ok    = [];

function runSQL(PDO $db, string $descricao, string $sql, array &$ok, array &$erros): void {
    try {
        $db->exec($sql);
        $ok[] = "✅ $descricao";
    } catch (Exception $e) {
        $erros[] = "❌ $descricao — " . $e->getMessage();
    }
}

// ─── 1. Tabela users — colunas de assinatura ───────────────────────────────
runSQL($db, 'users.trial_started_at',
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS trial_started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    $ok, $erros);

runSQL($db, 'users.subscription_status',
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS subscription_status VARCHAR(20) DEFAULT 'trial'",
    $ok, $erros);

runSQL($db, 'users.subscription_expires_at',
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS subscription_expires_at TIMESTAMP",
    $ok, $erros);

runSQL($db, 'users.subscription_plan',
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS subscription_plan VARCHAR(20)",
    $ok, $erros);

// ─── 2. Tabelas de roteiros — user_id ─────────────────────────────────────
runSQL($db, 'roteiros.user_id',
    "ALTER TABLE roteiros ADD COLUMN IF NOT EXISTS user_id VARCHAR(32)",
    $ok, $erros);

runSQL($db, 'roteiros_conhecimento.user_id',
    "ALTER TABLE roteiros_conhecimento ADD COLUMN IF NOT EXISTS user_id VARCHAR(32)",
    $ok, $erros);

runSQL($db, 'roteiros_memoria.user_id',
    "ALTER TABLE roteiros_memoria ADD COLUMN IF NOT EXISTS user_id VARCHAR(32)",
    $ok, $erros);

// ─── 3. Índices ────────────────────────────────────────────────────────────
runSQL($db, 'idx_roteiros_user',
    "CREATE INDEX IF NOT EXISTS idx_roteiros_user ON roteiros(user_id)",
    $ok, $erros);

runSQL($db, 'idx_conhecimento_user',
    "CREATE INDEX IF NOT EXISTS idx_conhecimento_user ON roteiros_conhecimento(user_id)",
    $ok, $erros);

runSQL($db, 'idx_memoria_user',
    "CREATE INDEX IF NOT EXISTS idx_memoria_user ON roteiros_memoria(user_id)",
    $ok, $erros);

// ─── 4. Tabela subscriptions ───────────────────────────────────────────────
runSQL($db, 'tabela subscriptions',
    "CREATE TABLE IF NOT EXISTS subscriptions (
        id           VARCHAR(32) PRIMARY KEY,
        user_id      VARCHAR(32) NOT NULL,
        gateway      VARCHAR(20) NOT NULL DEFAULT 'abacatepay',
        external_id  VARCHAR(150),
        plan         VARCHAR(20) NOT NULL DEFAULT 'mensal',
        status       VARCHAR(20) NOT NULL DEFAULT 'active',
        amount       DECIMAL(10,2),
        currency     VARCHAR(3) DEFAULT 'BRL',
        started_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at   TIMESTAMP,
        cancelled_at TIMESTAMP,
        raw_payload  TEXT,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    $ok, $erros);

runSQL($db, 'idx_subscriptions_user',
    "CREATE INDEX IF NOT EXISTS idx_subscriptions_user ON subscriptions(user_id)",
    $ok, $erros);

runSQL($db, 'idx_subscriptions_ext',
    "CREATE INDEX IF NOT EXISTS idx_subscriptions_ext ON subscriptions(external_id)",
    $ok, $erros);

// ─── 5. Tabela log_ia_calls ────────────────────────────────────────────────
runSQL($db, 'tabela log_ia_calls',
    "CREATE TABLE IF NOT EXISTS log_ia_calls (
        id         BIGSERIAL PRIMARY KEY,
        user_id    VARCHAR(32),
        provider   VARCHAR(20),
        operacao   VARCHAR(50),
        tokens_in  INTEGER DEFAULT 0,
        tokens_out INTEGER DEFAULT 0,
        custo_usd  DECIMAL(10,6) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    $ok, $erros);

runSQL($db, 'idx_ia_calls_user',
    "CREATE INDEX IF NOT EXISTS idx_ia_calls_user ON log_ia_calls(user_id)",
    $ok, $erros);

runSQL($db, 'idx_ia_calls_date',
    "CREATE INDEX IF NOT EXISTS idx_ia_calls_date ON log_ia_calls(created_at)",
    $ok, $erros);

// ─── 6. Tabela custos_infraestrutura ──────────────────────────────────────
runSQL($db, 'tabela custos_infraestrutura',
    "CREATE TABLE IF NOT EXISTS custos_infraestrutura (
        id         SERIAL PRIMARY KEY,
        descricao  VARCHAR(100) NOT NULL,
        valor      DECIMAL(10,2) NOT NULL,
        moeda      VARCHAR(3) DEFAULT 'BRL',
        periodo    VARCHAR(10) DEFAULT 'mensal',
        ativo      BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    $ok, $erros);

// ─── 7. configuracao_empresa — colunas de pagamento e custos ───────────────
$colunasConfig = [
    ['abacatepay_api_key',            'TEXT',                                      'Abacate Pay API Key'],
    ['abacatepay_webhook_secret',      'TEXT',                                      'Abacate Pay Webhook Secret'],
    ['abacatepay_checkout_mensal',     'TEXT',                                      'Abacate Pay checkout mensal URL'],
    ['abacatepay_checkout_anual',      'TEXT',                                      'Abacate Pay checkout anual URL'],
    ['plano_mensal_preco',             'DECIMAL(10,2) DEFAULT 15.00',               'Preço plano mensal'],
    ['plano_anual_preco',              'DECIMAL(10,2) DEFAULT 158.00',              'Preço plano anual'],
    ['custo_usd_brl',                  'DECIMAL(6,2) DEFAULT 5.70',                 'Taxa câmbio USD/BRL'],
    ['groq_custo_por_1k_tokens',       'DECIMAL(8,6) DEFAULT 0.000000',             'Groq custo por 1k tokens'],
    ['gemini_custo_por_1k_tokens',     'DECIMAL(8,6) DEFAULT 0.000000',             'Gemini custo por 1k tokens'],
];

foreach ($colunasConfig as [$coluna, $tipo, $desc]) {
    runSQL($db, "configuracao_empresa.$coluna",
        "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS $coluna $tipo",
        $ok, $erros);
}

// ─── 8. Atribuir user_id do primeiro admin aos roteiros existentes ──────────
try {
    $admin = $db->query("SELECT id FROM users WHERE nivel = 1 ORDER BY criado_em ASC LIMIT 1")->fetchColumn();
    if ($admin) {
        $db->prepare("UPDATE roteiros SET user_id = ? WHERE user_id IS NULL")->execute([$admin]);
        $db->prepare("UPDATE roteiros_conhecimento SET user_id = ? WHERE user_id IS NULL")->execute([$admin]);
        $db->prepare("UPDATE roteiros_memoria SET user_id = ? WHERE user_id IS NULL")->execute([$admin]);
        $db->prepare("UPDATE users SET trial_started_at = CURRENT_TIMESTAMP, subscription_status = 'active' WHERE id = ?")->execute([$admin]);
        $ok[] = "✅ Dados existentes associados ao admin ($admin) com status 'active'";
    }
} catch (Exception $e) {
    $erros[] = "❌ Associação de dados existentes — " . $e->getMessage();
}

// ─── Resultado ────────────────────────────────────────────────────────────
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head><title>Migração SaaS</title>
<style>
    body { font-family: monospace; background: #0a0a0a; color: #f0f0f0; padding: 2rem; }
    .ok { color: #4ade80; }
    .erro { color: #f87171; }
    h1 { color: #e8ff47; }
</style>
</head>
<body>
<h1>Migração SaaS — Distinto Roteiros</h1>
<p>Data/hora: <?= date('d/m/Y H:i:s') ?></p>
<hr>
<?php foreach ($ok as $msg): ?>
    <div class="ok"><?= htmlspecialchars($msg) ?></div>
<?php endforeach; ?>
<?php foreach ($erros as $msg): ?>
    <div class="erro"><?= htmlspecialchars($msg) ?></div>
<?php endforeach; ?>
<hr>
<p><?= count($ok) ?> operações com sucesso · <?= count($erros) ?> erros</p>
<?php if (empty($erros)): ?>
    <p class="ok"><strong>✅ Migração concluída com sucesso! Apague ou proteja este arquivo.</strong></p>
<?php else: ?>
    <p class="erro"><strong>⚠️ Migração concluída com erros. Verifique acima.</strong></p>
<?php endif; ?>
</body>
</html>
