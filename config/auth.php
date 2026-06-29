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
    // Permitir pre-flight OPTIONS sem autenticação para CORS
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept");
        header("Access-Control-Max-Age: 86400");
        exit;
    }

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
 * Garante que o usuário logado é do sistema Distinto (CRM).
 * Usuários que se registraram via Roteiros (SaaS) jamais acessam o CRM.
 */
function exigirDistinto(): void {
    exigirAutenticacao();
    $usuario = usuarioAtual();
    
    // Se na sessão diz que é distinto, fazemos uma verificação dupla no banco
    // para garantir que não é um usuário de roteiros em uma sessão antiga.
    if ($usuario['sistema_origem'] === 'distinto') {
        try {
            $db = Database::get();
            $stmt = $db->prepare("SELECT sistema_origem FROM users WHERE id = ?");
            $stmt->execute([$usuario['id']]);
            $origemReal = $stmt->fetchColumn();
            
            if ($origemReal && $origemReal !== 'distinto') {
                // Atualiza a sessão e bloqueia
                $_SESSION['user_sistema_origem'] = $origemReal;
                header('Location: ' . raizUrl('/roteiros/index.php'));
                exit;
            }
        } catch (Exception $e) {
            // Se falhar o banco, seguimos o que está na sessão por segurança (fallback)
        }
    }

    if ($usuario['sistema_origem'] !== 'distinto') {
        header('Location: ' . raizUrl('/roteiros/index.php'));
        exit;
    }
}

/**
 * Garante que o usuário logado é um administrador do CRM.
 */
function exigirAdmin(): void {
    exigirDistinto(); // Primeiro precisa ser usuário Distinto
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
        'id'                  => $id,
        'nome'                => $_SESSION['user_nome'] ?? '',
        'email'               => $email,
        'nivel'               => $_SESSION['user_nivel'] ?? 0,
        'sistema_origem'      => $_SESSION['user_sistema_origem'] ?? 'distinto', 
        'roteiros_workspace_id' => $_SESSION['user_roteiros_workspace_id'] ?? '',
        'subscription_status' => $_SESSION['user_subscription_status'] ?? 'trial',
        'subscription_plan'   => $_SESSION['user_subscription_plan'] ?? null,
    ];
}

function usuarioEhDistinto(?array $usuario = null): bool {
    $usuario = $usuario ?? usuarioAtual();
    return ($usuario['sistema_origem'] ?? '') === 'distinto';
}

function roteirosWorkspaceId(array $usuario): string {
    $workspaceId = trim((string)($usuario['roteiros_workspace_id'] ?? ''));
    if ($workspaceId !== '') {
        return $workspaceId;
    }

    return usuarioEhDistinto($usuario)
        ? 'distinto'
        : (string)($usuario['id'] ?? '');
}

function garantirWorkspaceRoteiros(PDO $db): void {
    static $executado = false;
    if ($executado) return;
    $executado = true;

    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS roteiros_workspaces (
                id VARCHAR(64) PRIMARY KEY,
                nome VARCHAR(120) NOT NULL,
                owner_user_id VARCHAR(32) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    } catch (Exception $e) {}

    try {
        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS roteiros_workspace_id VARCHAR(64)");
    } catch (Exception $e) {}

    try {
        $stmt = $db->prepare("SELECT id FROM roteiros_workspaces WHERE id = ? LIMIT 1");
        $stmt->execute(['distinto']);
        if (!$stmt->fetchColumn()) {
            $db->prepare("INSERT INTO roteiros_workspaces (id, nome) VALUES (?, ?)")
                ->execute(['distinto', 'Equipe Distinto']);
        }
    } catch (Exception $e) {}

    try {
        $db->exec("
            UPDATE users
            SET roteiros_workspace_id = 'distinto'
            WHERE sistema_origem = 'distinto'
              AND (roteiros_workspace_id IS NULL OR roteiros_workspace_id = '')
        ");
    } catch (Exception $e) {}
}

function roteirosWorkspaceOwnerUserId(PDO $db, string $workspaceId, ?array $usuario = null): string {
    static $cache = [];

    $usuario = $usuario ?? usuarioAtual();
    $fallbackUserId = (string)($usuario['id'] ?? '');

    if ($workspaceId === '' || ($workspaceId !== 'distinto' && $workspaceId === $fallbackUserId)) {
        return $fallbackUserId;
    }

    if (isset($cache[$workspaceId])) {
        return $cache[$workspaceId];
    }

    garantirWorkspaceRoteiros($db);

    try {
        $stmt = $db->prepare("SELECT owner_user_id FROM roteiros_workspaces WHERE id = ? LIMIT 1");
        $stmt->execute([$workspaceId]);
        $ownerId = trim((string)$stmt->fetchColumn());
        if ($ownerId !== '') {
            $cache[$workspaceId] = $ownerId;
            return $ownerId;
        }
    } catch (Exception $e) {}

    try {
        if ($workspaceId === 'distinto') {
            $stmt = $db->prepare("
                SELECT id
                FROM users
                WHERE roteiros_workspace_id = ?
                   OR sistema_origem = 'distinto'
                ORDER BY
                    CASE WHEN criado_em IS NULL THEN 1 ELSE 0 END,
                    criado_em ASC,
                    id ASC
                LIMIT 1
            ");
            $stmt->execute([$workspaceId]);
        } else {
            $stmt = $db->prepare("SELECT id FROM users WHERE roteiros_workspace_id = ? ORDER BY id ASC LIMIT 1");
            $stmt->execute([$workspaceId]);
        }

        $ownerId = trim((string)$stmt->fetchColumn());
        if ($ownerId !== '') {
            try {
                $db->prepare("UPDATE roteiros_workspaces SET owner_user_id = ? WHERE id = ?")
                    ->execute([$ownerId, $workspaceId]);
            } catch (Exception $e) {}

            $cache[$workspaceId] = $ownerId;
            return $ownerId;
        }
    } catch (Exception $e) {}

    return $fallbackUserId;
}

function roteirosEquipeDistintoUserId(): string {
    try {
        $db = Database::get();
        return roteirosWorkspaceOwnerUserId($db, 'distinto', usuarioAtual());
    } catch (Exception $e) {}

    $usuario = usuarioAtual();
    return (string)($usuario['id'] ?? '');
}

function roteirosUserId(array $usuario): string {
    try {
        $db = Database::get();
        return roteirosWorkspaceOwnerUserId($db, roteirosWorkspaceId($usuario), $usuario);
    } catch (Exception $e) {
        return (string)($usuario['id'] ?? '');
    }
}

function normalizarRoteirosDistinto(PDO $db): void {
    static $executado = false;
    if ($executado) return;
    $executado = true;

    garantirWorkspaceRoteiros($db);

    $ownerId = roteirosWorkspaceOwnerUserId($db, 'distinto', usuarioAtual());
    if ($ownerId === '') return;

    $whereDistinto = "
        user_id IN (
            SELECT id
            FROM users
            WHERE roteiros_workspace_id = 'distinto'
               OR sistema_origem = 'distinto'
        )
        AND user_id <> ?
    ";

    try {
        $db->prepare("
            DELETE FROM roteiros_config_usuario
            WHERE {$whereDistinto}
              AND EXISTS (SELECT 1 FROM roteiros_config_usuario WHERE user_id = ?)
        ")->execute([$ownerId, $ownerId]);
    } catch (Exception $e) {}

    try {
        $db->prepare("
            DELETE FROM roteiros_config_cliente c
            WHERE c.user_id IN (
                SELECT id
                FROM users
                WHERE roteiros_workspace_id = 'distinto'
                   OR sistema_origem = 'distinto'
            )
              AND c.user_id <> ?
              AND EXISTS (
                  SELECT 1
                  FROM roteiros_config_cliente owner_cfg
                  WHERE owner_cfg.user_id = ?
                    AND owner_cfg.cliente_id = c.cliente_id
              )
        ")->execute([$ownerId, $ownerId]);
    } catch (Exception $e) {}

    foreach (['roteiros_clientes', 'roteiros_conhecimento', 'roteiros_memoria', 'roteiros_config_usuario', 'roteiros_config_cliente', 'roteiros'] as $tabela) {
        try {
            $db->prepare("UPDATE {$tabela} SET user_id = ? WHERE {$whereDistinto}")->execute([$ownerId, $ownerId]);
        } catch (Exception $e) {}
    }
}

function logarUsuario(array $user): void {
    iniciarSessao();
    session_regenerate_id(true);
    $_SESSION['user_id']                  = $user['id'];
    $_SESSION['user_nome']                = $user['nome'];
    $_SESSION['user_email']               = $user['email'];
    $_SESSION['user_nivel']               = $user['nivel'] ?? 0;
    $_SESSION['user_sistema_origem']      = $user['sistema_origem'] ?? 'distinto';
    $_SESSION['user_roteiros_workspace_id'] = $user['roteiros_workspace_id'] ?? (($user['sistema_origem'] ?? 'distinto') === 'distinto' ? 'distinto' : ($user['id'] ?? ''));
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
