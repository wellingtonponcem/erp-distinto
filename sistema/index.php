<?php
/**
 * sistema/index.php — ponto de entrada do módulo Meus Roteiros
 * Redireciona para o login dedicado ou, se já autenticado, para os roteiros.
 */
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if (estaAutenticado()) {
    // Já logado → vai direto para os roteiros
    header('Location: ' . raizUrl('/roteiros/index.php'));
} else {
    // Não logado → página de login dos Roteiros
    header('Location: ' . raizUrl('/login-roteiros.php'));
}
exit;
