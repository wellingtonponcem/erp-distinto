<?php
/**
 * API: Listar Roteiros
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

try {
    $db = Database::get();
    
    $status = $_GET['status'] ?? null;
    $tag = $_GET['tag'] ?? null;

    $query = "SELECT id, titulo, status, formato, score, created_at FROM roteiros ORDER BY created_at DESC";
    $params = [];

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $roteiros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    responderJson(['success' => true, 'data' => $roteiros]);

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
