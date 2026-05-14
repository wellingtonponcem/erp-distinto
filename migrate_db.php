<?php
require_once __DIR__ . '/config/database.php';
$db = Database::get();
try {
    $db->exec("ALTER TABLE servicos ADD COLUMN categoria VARCHAR(50) DEFAULT 'marketing' AFTER nome");
    $db->exec("ALTER TABLE servicos ADD COLUMN tipo VARCHAR(20) DEFAULT 'servico' AFTER categoria");
    echo "Sucesso!";
} catch(Exception $e) {
    echo "Erro ou colunas ja existem: " . $e->getMessage();
}
