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
    $db      = Database::get();
    $usuario = usuarioAtual();
    $userId = function_exists('roteirosUserId') ? roteirosUserId($usuario) : $usuario['id'];
    if (($usuario['sistema_origem'] ?? '') === 'distinto' && function_exists('normalizarRoteirosDistinto')) normalizarRoteirosDistinto($db);

    $stmt = $db->prepare("DELETE FROM roteiros WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    responderJson(['success' => true]);

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
