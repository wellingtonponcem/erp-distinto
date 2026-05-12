<?php
require_once __DIR__ . '/env.php';

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
    if (!estaAutenticado()) {
        // Se for uma requisição de API, responde com JSON em vez de redirecionar
        if (str_contains($_SERVER['SCRIPT_NAME'], '/api/')) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['erro' => 'Sessão expirada ou não autenticado. Faça login novamente.']);
            exit;
        }

        // Tenta usar a APP_URL se disponível, senão usa lógica de path
        if (defined('APP_URL')) {
            $loginPage = str_contains($_SERVER['SCRIPT_NAME'], '/roteiros/') ? '/login-roteiros.php' : '/index.php';
            header('Location: ' . rtrim(APP_URL, '/') . $loginPage);
            exit;
        }

        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        // Se já estivermos no index.php da raiz, não redireciona (evita loop)
        if ($base === '' || $base === '/' || $base === '/sistema') {
             // Se cair aqui e não estiver autenticado, algo está errado no roteamento
             // mas vamos evitar o redirect loop forçado
             die("Acesso negado. Faça login na página inicial.");
        }

        header('Location: ../index.php');
        exit;
    }
}

/**
 * Garante que o usuário logado é um administrador (Distinto).
 * Se for nível 0 (Roteiros), redireciona para a área de roteiros.
 */
function exigirAdmin(): void {
    exigirAutenticacao();
    $usuario = usuarioAtual();
    if ($usuario['nivel'] != 1) {
        header('Location: ' . raizUrl('/roteiros/index.php'));
        exit;
    }
}

function usuarioAtual(): array {
    iniciarSessao();
    return [
        'id'                  => $_SESSION['user_id'] ?? '',
        'nome'                => $_SESSION['user_nome'] ?? '',
        'email'               => $_SESSION['user_email'] ?? '',
        'nivel'               => $_SESSION['user_nivel'] ?? 0,
        'subscription_status' => $_SESSION['user_subscription_status'] ?? 'trial',
        'subscription_plan'   => $_SESSION['user_subscription_plan'] ?? null,
    ];
}

function logarUsuario(array $user): void {
    iniciarSessao();
    session_regenerate_id(true);
    $_SESSION['user_id']                  = $user['id'];
    $_SESSION['user_nome']                = $user['nome'];
    $_SESSION['user_email']               = $user['email'];
    $_SESSION['user_nivel']               = $user['nivel'] ?? 0;
    $_SESSION['user_subscription_status'] = $user['subscription_status'] ?? 'trial';
    $_SESSION['user_subscription_plan']   = $user['subscription_plan'] ?? null;
}

/**
 * Atualiza dados de assinatura na sessão ativa (após webhook ou refresh).
 */
function atualizarSessaoAssinatura(string $userId): void
{
    try {
        require_once __DIR__ . '/../config/database.php';
        $db   = Database::get();
        $stmt = $db->prepare(
            "SELECT subscription_status, subscription_plan FROM users WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            iniciarSessao();
            $_SESSION['user_subscription_status'] = $user['subscription_status'];
            $_SESSION['user_subscription_plan']   = $user['subscription_plan'];
        }
    } catch (Exception $e) {
        // Silencioso — não quebrar o fluxo
    }
}

function deslogarUsuario(): void {
    iniciarSessao();
    session_unset();
    session_destroy();
}
