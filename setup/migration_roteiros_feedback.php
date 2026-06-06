<?php
/**
 * Migration: segurança dos links públicos e histórico de aprendizado dos roteiros.
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::get();

$queries = [
    "ALTER TABLE roteiros ADD COLUMN IF NOT EXISTS public_token VARCHAR(64)",
    "ALTER TABLE roteiros_clientes ADD COLUMN IF NOT EXISTS public_token VARCHAR(64)",
    "CREATE TABLE IF NOT EXISTS roteiros_sugestoes (
        id VARCHAR(32) PRIMARY KEY,
        roteiro_id VARCHAR(32) NOT NULL,
        campo VARCHAR(80) NOT NULL,
        texto_original TEXT NULL,
        texto_sugerido TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'pendente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS roteiros_feedback_historico (
        id VARCHAR(32) PRIMARY KEY,
        roteiro_id VARCHAR(32) NOT NULL,
        user_id VARCHAR(32) NOT NULL,
        cliente_id VARCHAR(36) NULL,
        tipo VARCHAR(40) NOT NULL,
        campo VARCHAR(80) NULL,
        conteudo TEXT NOT NULL,
        metadata TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE INDEX IF NOT EXISTS idx_roteiros_public_token ON roteiros (public_token)",
    "CREATE INDEX IF NOT EXISTS idx_roteiros_clientes_public_token ON roteiros_clientes (public_token)",
    "CREATE INDEX IF NOT EXISTS idx_roteiros_feedback_cliente ON roteiros_feedback_historico (user_id, cliente_id, created_at)",
    "CREATE INDEX IF NOT EXISTS idx_roteiros_sugestoes_roteiro ON roteiros_sugestoes (roteiro_id, status)"
];

foreach ($queries as $sql) {
    $db->exec($sql);
}

$stmt = $db->query("SELECT id FROM roteiros WHERE public_token IS NULL OR public_token = ''");
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
    $db->prepare("UPDATE roteiros SET public_token = ? WHERE id = ?")->execute([bin2hex(random_bytes(24)), $id]);
}

$stmt = $db->query("SELECT id FROM roteiros_clientes WHERE public_token IS NULL OR public_token = ''");
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
    $db->prepare("UPDATE roteiros_clientes SET public_token = ? WHERE id = ?")->execute([bin2hex(random_bytes(24)), $id]);
}

echo "Migration de roteiros concluida.\n";
