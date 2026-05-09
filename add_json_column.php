<?php
require_once __DIR__ . '/config/database.php';
$db = Database::get();
try {
    $db->exec("ALTER TABLE servicos ADD COLUMN itens_json TEXT NULL AFTER tipo");
    echo "Sucesso!";
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage();
}
