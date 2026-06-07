<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::get();
    echo "Conectado com sucesso!\n";
    
    // Test if contratos table exists
    $stmt = $db->query("SELECT count(*) FROM contratos");
    $count = $stmt->fetchColumn();
    echo "Tabela 'contratos' existe! Total de registros: $count\n";
} catch (Exception $e) {
    echo "Erro ao conectar ou consultar: " . $e->getMessage() . "\n";
}
