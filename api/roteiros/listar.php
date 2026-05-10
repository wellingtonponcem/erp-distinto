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

    $query = "SELECT * FROM roteiros WHERE 1=1";
    $params = [];

    if ($status) {
        $query .= " AND status = ?";
        $params[] = $status;
    }

    if ($tag) {
        $query .= " AND tags LIKE ?";
        $params[] = "%$tag%";
    }

    $query .= " ORDER BY created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $roteiros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    responderJson(['success' => true, 'data' => $roteiros]);

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
