<?php
/**
 * API: Gerar Roteiro via IA
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/ia_roteiros.php';
require_once __DIR__ . '/../../includes/assinatura.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$d       = lerCorpo();
$usuario = usuarioAtual();
$userId  = function_exists('roteirosUserId') ? roteirosUserId($usuario) : $usuario['id'];

// Verificar limite antes de consumir tokens de IA
$limite = verificarLimiteDiario($userId);
if (!$limite['ok']) {
    $payloadErro = ['success' => false, 'paywall' => true, 'motivo' => $limite['motivo']];
    if ($limite['motivo'] === 'trial_expirado') {
        $payloadErro['mensagem'] = 'Seu período de teste encerrou. Assine para continuar criando roteiros.';
    } else {
        $payloadErro['mensagem'] = "Você atingiu o limite de {$limite['limite']} roteiros hoje.";
        $payloadErro['limite']   = $limite['limite'];
        $payloadErro['usados']   = $limite['usados'];
    }
    responderJson($payloadErro, 403);
}

try {
    $db = Database::get();
    if (($usuario['sistema_origem'] ?? '') === 'distinto' && function_exists('normalizarRoteirosDistinto')) normalizarRoteirosDistinto($db);
    $roteiro = IARoteiros::gerarRoteiro($d['briefing'] ?? '', $userId, $d['cliente_id'] ?? '');
    
    responderJson([
        'success' => true, 
        'roteiro' => $roteiro
    ]);

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
