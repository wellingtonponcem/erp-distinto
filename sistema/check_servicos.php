<?php
require_once __DIR__ . '/config/database.php';
header('Content-Type: application/json');
try {
    $db = Database::get();
    $cols = $db->query("DESCRIBE servicos")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cols, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
