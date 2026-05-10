<?php
/**
 * API: Gerar Roteiro via IA
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/ia_roteiros.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$d = lerCorpo();

if (empty($d['tema'])) {
    responderJson(['erro' => 'O tema é obrigatório.'], 422);
}

try {
    $roteiro = IARoteiros::gerarRoteiro($d['tema'], $d['briefing'] ?? '');
    
    responderJson([
        'success' => true, 
        'roteiro' => $roteiro
    ]);

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
