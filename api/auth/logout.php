<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
deslogarUsuario();

$redir = str_contains($_SERVER['HTTP_REFERER'] ?? '', '/roteiros/') ? raizUrl('/login-roteiros.php') : raizUrl('/index.php');
header('Location: ' . $redir);
exit;
