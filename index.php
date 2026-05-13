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
            
            // Redirecionamento inteligente baseado na origem
            $target = ($user['sistema_origem'] === 'roteiros') ? 'roteiros/index.php' : 'dashboard.php';
            header('Location: ' . raizUrl('/' . $target));
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
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link href="<?= raizUrl('/assets/css/tailwind.css') ?>" rel="stylesheet">
    <style>
        body { 
            background-color: #050505; 
            font-family: 'Outfit', sans-serif; 
            color: #f1f5f9;
        }
        .login-card {
            background: #0a0a0a;
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
        }
        .input { 
            background: rgba(255,255,255,0.03); 
            border: 1px solid rgba(255,255,255,0.08); 
            color: #f1f5f9; 
            border-radius: 12px; 
            padding: 12px 16px; 
            font-size: 15px; 
            width: 100%; 
            outline: none; 
            transition: all 0.2s; 
        }
        .input:focus { 
            border-color: rgba(255,255,255,0.2); 
            background: rgba(255,255,255,0.05);
            box-shadow: 0 0 0 4px rgba(255,255,255,0.03); 
        }
        .input::placeholder { color: #4b5563; }
        
        .btn-primary {
            background: #f1f5f9;
            color: #050505;
            padding: 12px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            background: #ffffff;
            box-shadow: 0 10px 20px -5px rgba(255,255,255,0.1);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .logo-container {
            width: 64px;
            height: 64px;
            background: #111;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">

<div style="width:100%; max-width:400px;">

    <!-- Logo -->
    <div style="text-align:center; margin-bottom:40px;">
        <div class="logo-container">
            <img src="<?= raizUrl('/favicon.svg') ?>" alt="Logo Distinto" style="width:36px; height:36px; filter: invert(1);">
        </div>
        <h1 style="font-size:28px; font-weight:800; color:#fff; letter-spacing:-0.05em; margin-bottom: 4px;">Distinto</h1>
        <p style="font-size:14px; color:#6b7280; font-weight: 500;">Gestão Financeira para Agências</p>
    </div>

    <!-- Card de login -->
    <div class="login-card" style="border-radius:24px; padding:40px;">
        <h2 style="font-size:20px; font-weight:700; color:#fff; margin-bottom:28px; letter-spacing: -0.02em;">Acessar sistema</h2>

        <?php if ($erro): ?>
        <div style="background:rgba(239,68,68,0.05); border:1px solid rgba(239,68,68,0.2); border-radius:12px; padding:12px 16px; margin-bottom:24px; font-size:14px; color:#f87171; font-weight: 500;">
            <?= sanitizar($erro) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div style="margin-bottom:20px;">
                <label style="font-size:12px; font-weight:700; color:#4b5563; display:block; margin-bottom:8px; text-transform: uppercase; letter-spacing: 0.05em;">E-mail</label>
                <input class="input" type="email" name="email" placeholder="seu@email.com.br" required autocomplete="email" value="<?= sanitizar($_POST['email'] ?? '') ?>">
            </div>
            <div style="margin-bottom:32px;">
                <label style="font-size:12px; font-weight:700; color:#4b5563; display:block; margin-bottom:8px; text-transform: uppercase; letter-spacing: 0.05em;">Senha</label>
                <input class="input" type="password" name="senha" placeholder="••••••••" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-primary" style="width:100%;">
                Entrar no Sistema
            </button>
        </form>
    </div>

    <div style="text-align:center; margin-top:32px; display:flex; flex-direction:column; gap:16px;">
        <a href="<?= raizUrl('/registro.php') ?>" style="display:block; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); color:#fff; padding:14px; border-radius:14px; font-size:14px; font-weight:600; text-decoration:none; transition:all 0.2s;">
            ✨ Criar conta grátis — Teste por 30 dias
        </a>
        <p style="font-size:13px; color:#4b5563; font-weight: 500;">
            Ou acesse <a href="<?= raizUrl('/landing.php') ?>" style="color:#9ca3af; text-decoration:none; border-bottom: 1px solid #374151;">a página de apresentação</a>
        </p>
    </div>
</div>

</body>
</html>
