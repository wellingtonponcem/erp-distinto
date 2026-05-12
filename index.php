<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Já logado → ir para dashboard
if (estaAutenticado()) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/config/database.php';
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if ($email && $senha) {
        $db   = Database::get();
        $stmt = $db->prepare('SELECT id, nome, email, senha FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha'])) {
            logarUsuario($user);
            header('Location: dashboard.php');
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
    <link href="<?= raizUrl('/assets/css/tailwind.css') ?>" rel="stylesheet">
    <style>
        body { background-color: #0c0c18; font-family: system-ui, sans-serif; }
        .input { background: rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#f1f5f9; border-radius:8px; padding:11px 16px; font-size:15px; width:100%; outline:none; transition:border-color 0.15s; }
        .input:focus { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.15); }
        .input::placeholder { color:#4b5563; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">

<div style="width:100%; max-width:400px;">

    <!-- Logo -->
    <div style="text-align:center; margin-bottom:36px;">
        <div style="width:52px; height:52px; background:linear-gradient(135deg,#7c3aed,#3b82f6); border-radius:14px; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="white"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        </div>
        <h1 style="font-size:24px; font-weight:700; color:#f1f5f9; letter-spacing:-0.5px;">Distinto</h1>
        <p style="font-size:14px; color:#6b7280; margin-top:4px;">Gestão Financeira para Agências</p>
    </div>

    <!-- Card de login -->
    <div style="background:#1a1a2e; border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:32px;">
        <h2 style="font-size:18px; font-weight:600; color:#e2e8f0; margin-bottom:24px;">Acessar sistema</h2>

        <?php if ($erro): ?>
        <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:8px; padding:12px 16px; margin-bottom:20px; font-size:14px; color:#f87171;">
            <?= sanitizar($erro) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div style="margin-bottom:16px;">
                <label style="font-size:12px; font-weight:500; color:#94a3b8; display:block; margin-bottom:6px;">E-mail</label>
                <input class="input" type="email" name="email" placeholder="seu@email.com.br" required autocomplete="email" value="<?= sanitizar($_POST['email'] ?? '') ?>">
            </div>
            <div style="margin-bottom:24px;">
                <label style="font-size:12px; font-weight:500; color:#94a3b8; display:block; margin-bottom:6px;">Senha</label>
                <input class="input" type="password" name="senha" placeholder="••••••••" required autocomplete="current-password">
            </div>
            <button type="submit" style="width:100%; background:linear-gradient(135deg,#7c3aed,#6d28d9); color:white; padding:12px; border-radius:10px; font-size:15px; font-weight:600; border:none; cursor:pointer; transition:opacity 0.15s;">
                Entrar
            </button>
        </form>
    </div>

    <div style="text-align:center; margin-top:20px; display:flex; flex-direction:column; gap:10px;">
        <a href="<?= raizUrl('/registro.php') ?>" style="display:block; background:rgba(99,102,241,0.1); border:1px solid rgba(99,102,241,0.3); color:#818cf8; padding:12px; border-radius:10px; font-size:14px; font-weight:600; text-decoration:none; transition:all 0.15s;">
            ✨ Criar conta grátis — 35 dias sem cartão
        </a>
        <p style="font-size:12px; color:#4b5563;">
            Ou acesse <a href="<?= raizUrl('/landing.php') ?>" style="color:#6b7280; text-decoration:underline;">a página de apresentação</a>
        </p>
    </div>
</div>

</body>
</html>
