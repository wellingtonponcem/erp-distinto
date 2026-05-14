<?php
require_once __DIR__ . '/config/database.php';
$db = Database::get();

$tables = ['clientes', 'fornecedores', 'propostas', 'oportunidades', 'lancamentos'];

foreach ($tables as $table) {
    echo "\n--- Table: $table ---\n";
    try {
        $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '$table' ORDER BY ordinal_position");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "{$row['column_name']} ({$row['data_type']})\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
