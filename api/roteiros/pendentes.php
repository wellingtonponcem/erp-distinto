<?php
/**
 * API: Roteiros Pendentes com Conteúdo Completo (para modo offline)
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

try {
    $db = Database::get();

    $usuario = usuarioAtual();
    $userId = function_exists('roteirosUserId') ? roteirosUserId($usuario) : $usuario['id'];
    if (($usuario['sistema_origem'] ?? '') === 'distinto' && function_exists('normalizarRoteirosDistinto')) normalizarRoteirosDistinto($db);

    $stmt = $db->prepare(
        "SELECT id, numero, titulo, gancho, quebra_crenca, desenvolvimento, conexao, fechamento, cta, intencao, tema, status, score
         FROM roteiros
         WHERE status = 'pendente' AND user_id = ?
         ORDER BY numero ASC NULLS LAST, created_at DESC"
    );
    $stmt->execute([$userId]);
    $roteiros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    responderJson([
        'success'   => true,
        'data'      => $roteiros,
        'synced_at' => date('Y-m-d H:i:s'),
    ]);

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
