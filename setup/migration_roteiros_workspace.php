<?php
/**
 * Migration: workspace compartilhado para a equipe Distinto nos roteiros.
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::get();

$db->exec("CREATE TABLE IF NOT EXISTS roteiros_workspaces (
    id VARCHAR(64) PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    owner_user_id VARCHAR(32) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

try {
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS roteiros_workspace_id VARCHAR(64)");
} catch (Exception $e) {
    try {
        $stmt = $db->query("SELECT 1 FROM information_schema.columns WHERE table_name='users' AND column_name='roteiros_workspace_id'");
        if (!$stmt->fetchColumn()) {
            $db->exec("ALTER TABLE users ADD COLUMN roteiros_workspace_id VARCHAR(64)");
        }
    } catch (Exception $ignored) {}
}

$stmt = $db->prepare("SELECT id FROM roteiros_workspaces WHERE id = ? LIMIT 1");
$stmt->execute(['distinto']);
if (!$stmt->fetchColumn()) {
    $db->prepare("INSERT INTO roteiros_workspaces (id, nome) VALUES (?, ?)")
        ->execute(['distinto', 'Equipe Distinto']);
}

$db->exec("
    UPDATE users
    SET roteiros_workspace_id = 'distinto'
    WHERE sistema_origem = 'distinto'
      AND (roteiros_workspace_id IS NULL OR roteiros_workspace_id = '')
");

$stmt = $db->prepare("SELECT owner_user_id FROM roteiros_workspaces WHERE id = ? LIMIT 1");
$stmt->execute(['distinto']);
$ownerId = trim((string)$stmt->fetchColumn());

if ($ownerId === '') {
    $stmt = $db->query("
        SELECT id
        FROM users
        WHERE roteiros_workspace_id = 'distinto'
           OR sistema_origem = 'distinto'
        ORDER BY
            CASE WHEN criado_em IS NULL THEN 1 ELSE 0 END,
            criado_em ASC,
            id ASC
        LIMIT 1
    ");
    $ownerId = trim((string)$stmt->fetchColumn());

    if ($ownerId !== '') {
        $db->prepare("UPDATE roteiros_workspaces SET owner_user_id = ? WHERE id = ?")
            ->execute([$ownerId, 'distinto']);
    }
}

echo "Migration de workspace dos roteiros concluida.\n";
