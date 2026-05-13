<?php
require_once __DIR__ . '/config/database.php';
$db = Database::get();
try {
    $stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'lancamentos'");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo implode(", ", $cols);
} catch (Exception $e) {
    echo $e->getMessage();
}
