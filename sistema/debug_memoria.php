<?php
require_once __DIR__ . '/config/database.php';
$db = Database::get();
try {
    $cols = $db->query("DESCRIBE configuracao_empresa")->fetchAll(PDO::FETCH_ASSOC);
    echo "COLUNAS EM configuracao_empresa:\n";
    foreach($cols as $col) {
        echo "{$col['Field']} ({$col['Type']})\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
