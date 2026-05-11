<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::get();
    $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'users'");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo $col['column_name'] . " (" . $col['data_type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
