<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::get();
try {
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS sistema_origem VARCHAR(20) DEFAULT 'distinto'");
    echo "Coluna 'sistema_origem' adicionada com sucesso!\n";
} catch (Exception $e) {
    echo "Erro ou coluna já existe: " . $e->getMessage() . "\n";
}
