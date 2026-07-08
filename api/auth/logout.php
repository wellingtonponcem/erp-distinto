<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
deslogarUsuario();

header('Location: ' . raizUrl('/index.php'));
exit;
