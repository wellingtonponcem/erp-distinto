<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Já logado → ir para roteiros
if (estaAutenticado()) {
    header('Location: ' . raizUrl('/roteiros/index.php'));
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/config/database.php';
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if ($email && $senha) {
        $db   = Database::get();
        $stmt = $db->prepare('SELECT id, nome, email, senha, sistema_origem FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha'])) {
            logarUsuario($user);
            header('Location: ' . raizUrl('/roteiros/index.php'));
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
    <title>Login — Meus Roteiros</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { 
            background-color: #050505; 
            font-family: 'Outfit', sans-serif; 
            color: #f1f5f9;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            background: #0a0a0a;
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .input { 
            background: rgba(255,255,255,0.03); 
            border: 1px solid rgba(255,255,255,0.08); 
            color: #f1f5f9; 
            border-radius: 12px; 
            padding: 12px 16px; 
            font-size: 15px; 
            width: 100%; 
            box-sizing: border-box;
            outline: none; 
            transition: all 0.2s; 
            margin-top: 8px;
        }
        .input:focus { 
            border-color: rgba(255,255,255,0.2); 
            background: rgba(255,255,255,0.05);
            box-shadow: 0 0 0 4px rgba(255,255,255,0.03); 
        }
        .btn-primary {
            background: #fff;
            color: #050505;
            padding: 14px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            width: 100%;
            margin-top: 24px;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px -5px rgba(255,255,255,0.1);
        }
        .logo-container {
            text-align: center;
            margin-bottom: 32px;
        }
        .logo-icon {
            font-size: 40px;
            margin-bottom: 12px;
            display: block;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo-container">
        <span class="logo-icon">🎬</span>
        <h1 style="font-size:24px; margin:0; font-weight:800;">Meus Roteiros</h1>
        <p style="color:#666; font-size:14px; margin-top:4px;">Criação de conteúdo com IA</p>
    </div>

    <?php if ($erro): ?>
    <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2); border-radius:12px; padding:12px; margin-bottom:20px; font-size:14px; color:#f87171; text-align:center;">
        <?= htmlspecialchars($erro) ?>
    </div>
    <?php endif; ?>

    <form method="POST">
        <div style="margin-bottom:16px;">
            <label style="font-size:12px; font-weight:700; color:#444; text-transform:uppercase;">E-mail</label>
            <input class="input" type="email" name="email" placeholder="seu@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div style="margin-bottom:8px;">
            <label style="font-size:12px; font-weight:700; color:#444; text-transform:uppercase;">Senha</label>
            <input class="input" type="password" name="senha" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn-primary">Acessar Roteiros</button>
    </form>

    <div style="text-align:center; margin-top:24px; font-size:13px; color:#444;">
        Ainda não tem conta? <a href="<?= raizUrl('/registro.php') ?>" style="color:#fff; text-decoration:none; font-weight:600;">Criar agora</a>
    </div>
</div>

</body>
</html>
