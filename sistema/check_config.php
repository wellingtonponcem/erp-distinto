<?php
require_once __DIR__ . '/config/database.php';
$db = Database::get();
try {
    $stmt = $db->query("DESCRIBE configuracao_empresa");
    echo json_encode($stmt->fetchAll());
} catch(Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
