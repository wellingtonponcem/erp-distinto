<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/database.php';

function iniciarSessao(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function estaAutenticado(): bool {
    iniciarSessao();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function exigirAutenticacao(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowed = ['https://wedistinto.com','https://www.wedistinto.com'];
        $isAllowed = in_array($origin, $allowed, true) || str_ends_with($origin, '.vercel.app');
        if ($isAllowed) {
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Credentials: true");
        }
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept, asaas-access-token, X-Asaas-Signature");
        header("Access-Control-Max-Age: 86400");
        exit;
    }

    if (!estaAutenticado()) {
        if (str_contains($_SERVER['SCRIPT_NAME'], '/api/')) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['erro' => 'Sessão expirada ou não autenticado. Faça login novamente.']);
            exit;
        }

        if (defined('APP_URL')) {
            header('Location: ' . rtrim(APP_URL, '/') . '/index.php');
            exit;
        }

        header('Location: ../index.php');
        exit;
    }
}

function exigirAdmin(): void {
    exigirAutenticacao();
    $usuario = usuarioAtual();
    if ($usuario['nivel'] != 1) {
        header('Location: ' . raizUrl('/dashboard.php'));
        exit;
    }
}

function usuarioAtual(): array {
    iniciarSessao();

    $id    = $_SESSION['user_id'] ?? '';
    $email = $_SESSION['user_email'] ?? '';

    return [
        'id'    => $id,
        'nome'  => $_SESSION['user_nome'] ?? '',
        'email' => $email,
        'nivel' => $_SESSION['user_nivel'] ?? 0,
    ];
}

function logarUsuario(array $user): void {
    iniciarSessao();
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_nome']  = $user['nome'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_nivel'] = $user['nivel'] ?? 0;
}

function deslogarUsuario(): void {
    iniciarSessao();
    session_unset();
    session_destroy();
}
