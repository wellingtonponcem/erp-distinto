<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
deslogarUsuario();

$redir = str_contains($_SERVER['HTTP_REFERER'] ?? '', '/roteiros/') ? '../../login-roteiros.php' : '../../index.php';
header('Location: ' . $redir);
exit;
