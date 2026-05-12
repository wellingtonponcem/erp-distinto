<?php
/**
 * API: Sincronizar/Reconstruir Memória Mestra
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/ia_roteiros.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

// Aumentar limites para processamento longo
set_time_limit(300);
ini_set('memory_limit', '512M');

try {
    $usuario = usuarioAtual();
    $userId  = $usuario['id'];

    // Reconstrói o cérebro da IA para este usuário
    $sucesso = IARoteiros::reconstruirMemoria($userId);

    if ($sucesso === true) {
        $db   = Database::get();
        $stmt = $db->prepare("SELECT conteudo FROM roteiros_memoria WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $novaMemoria = $stmt->fetchColumn();
        responderJson(['success' => true, 'nova_memoria' => $novaMemoria ?: '']);
    } else {
        responderJson(['success' => false, 'error' => $sucesso ?: 'Falha na reconstrução da memória.'], 500);
    }

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
