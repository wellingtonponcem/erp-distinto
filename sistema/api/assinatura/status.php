<?php
/**
 * API: Status da assinatura do usuário logado
 * Retorna dados de trial/assinatura + limite diário atual
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/assinatura.php';

exigirAutenticacao();

$usuario = usuarioAtual();
$userId  = $usuario['id'];

$dados  = getDadosAssinatura($userId);
$limite = verificarLimiteDiario($userId);

responderJson([
    'success'             => true,
    'status'              => $dados['status'],
    'plano'               => $dados['plan'],
    'dias_trial'          => $dados['dias_trial'] ?? null,
    'dias_restantes'      => $dados['dias_restantes'] ?? null,
    'pode_criar'          => $limite['ok'],
    'motivo'              => $limite['motivo'],
    'limite_diario'       => $limite['limite'],
    'usados_hoje'         => $limite['usados'],
    'restantes_hoje'      => $limite['restantes'],
    'expires_at'          => $dados['expires_at'] ?? null,
]);
