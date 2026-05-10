<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::get();
    $stmt = $db->query("SELECT * FROM roteiros");
    $roteiros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($roteiros);
} catch (Exception $e) {
    echo $e->getMessage();
}
