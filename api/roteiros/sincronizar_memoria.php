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
    // Chama a função que lê todas as fontes e reconstrói o cérebro da IA
    $sucesso = IARoteiros::reconstruirMemoria();

    if ($sucesso === true) {
        $db = Database::get();
        $novaMemoria = $db->query("SELECT conteudo FROM roteiros_memoria LIMIT 1")->fetchColumn();
        responderJson(['success' => true, 'nova_memoria' => $novaMemoria ?: '']);
    } else {
        responderJson(['success' => false, 'error' => $sucesso ?: 'Falha na reconstrução da memória.'], 500);
    }

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
