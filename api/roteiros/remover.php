<?php
/**
 * API: Remover Roteiro
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

$id = $_GET['id'] ?? '';

if (empty($id)) {
    responderJson(['success' => false, 'error' => 'ID inválido.'], 400);
    exit;
}

try {
    $db = Database::get();
    
    $stmt = $db->prepare("DELETE FROM roteiros WHERE id = ?");
    $stmt->execute([$id]);

    responderJson(['success' => true]);

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
