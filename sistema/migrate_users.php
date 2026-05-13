<?php
require_once __DIR__ . '/config/database.php';
$db = Database::get();

try {
    echo "Iniciando migração...\n";
    $db->exec("ALTER TABLE users ADD COLUMN nivel INT NOT NULL DEFAULT 0");
    echo "Coluna 'nivel' adicionada.\n";
} catch (Exception $e) {
    echo "Coluna 'nivel' já existe ou erro: " . $e->getMessage() . "\n";
}

try {
    $db->exec("UPDATE users SET nivel = 1 ORDER BY criado_em ASC LIMIT 1");
    echo "Primeiro usuário definido como Admin.\n";
} catch (Exception $e) {
    echo "Erro ao definir admin: " . $e->getMessage() . "\n";
}

echo "Migração concluída.\n";
