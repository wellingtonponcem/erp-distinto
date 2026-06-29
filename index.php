<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if (estaAutenticado()) {
    header('Location: ' . raizUrl('/dashboard.php'));
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/config/database.php';
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if ($email && $senha) {
        $db   = Database::get();
        $stmt = $db->prepare('SELECT id, nome, email, senha, nivel, sistema_origem, roteiros_workspace_id, subscription_status, subscription_plan FROM users WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($senha, $user['senha'])) {
            logarUsuario($user);
            header('Location: ' . raizUrl('/dashboard.php'));
            exit;
        }
    }
    $erro = 'E-mail ou senha incorretos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= raizUrl('/favicon.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="<?= raizUrl('/assets/css/tailwind.css') ?>" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: 'Outfit', sans-serif;
            background: #050505;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #0a0a0a;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 32px;
            padding: 48px 40px 40px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
        }
        .login-logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #7c3aed, #3b82f6);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .login-title {
            text-align: center;
            margin-bottom: 36px;
        }
        .login-title h1 {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #f1f5f9;
            margin: 0 0 4px;
        }
        .login-title p {
            font-size: 14px;
            color: #6b7280;
            font-weight: 400;
            margin: 0;
        }
        .input-group {
            margin-bottom: 16px;
        }
        .input-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 6px;
            letter-spacing: 0.02em;
        }
        .input-group input {
            width: 100%;
            padding: 13px 16px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            color: #f1f5f9;
            font-size: 14px;
            font-family: 'Outfit', sans-serif;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-group input:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
        }
        .input-group input::placeholder {
            color: #4b5563;
        }
        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.15s;
            margin-top: 8px;
        }
        .btn-login:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
        }
        .login-footer a {
            color: #6b7280;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: color 0.2s;
        }
        .login-footer a:hover {
            color: #a78bfa;
        }
        .erro-box {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #f87171;
            font-weight: 500;
            text-align: center;
        }
        @media (max-width: 480px) {
            body { padding: 16px; }
            .login-card { padding: 32px 24px 28px; }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="white">
                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
            </svg>
        </div>

        <div class="login-title">
            <h1>Distinto</h1>
            <p>Gestão Financeira para Agências</p>
        </div>

        <?php if ($erro): ?>
        <div class="erro-box"><?= sanitizar($erro) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group">
                <label>E-mail</label>
                <input type="email" name="email" placeholder="seu@email.com.br" required autocomplete="email" value="<?= sanitizar($_POST['email'] ?? '') ?>">
            </div>
            <div class="input-group">
                <label>Senha</label>
                <input type="password" name="senha" placeholder="••••••••" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-login">Entrar</button>
        </form>

        <div class="login-footer">
            <a href="<?= raizUrl('/esqueci-senha.php') ?>">Esqueci minha senha</a>
        </div>
    </div>
</body>
</html>
