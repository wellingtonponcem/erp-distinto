<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

// Já logado → vai direto para os roteiros
if (estaAutenticado()) {
    header('Location: ' . raizUrl('/roteiros/index.php'));
    exit;
}

$erro = '';
$mensagem = ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['senha_redefinida']))
    ? 'Senha redefinida com sucesso. Entre com a nova senha.'
    : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if ($email && $senha) {
        $db   = Database::get();
        $stmt = $db->prepare('SELECT * FROM users WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) LIMIT 1');
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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — Meus Roteiros</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0a0a; --surface: #111; --surface2: #181818;
            --border: rgba(255,255,255,0.07); --accent: #E8FF47; 
            --text: #F0EDE6; --muted: #888;
            --serif: 'Instrument Serif', Georgia, serif;
            --sans: 'DM Sans', sans-serif;
            --display: 'Bebas Neue', sans-serif;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--bg); color: var(--text);
            font-family: var(--sans); min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 2rem 1rem;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 3rem 2.5rem;
            width: 100%; max-width: 400px;
        }
        .logo {
            font-size: 32px;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .logo span:first-child {
            font-family: var(--display);
            color: var(--text);
            text-transform: uppercase;
        }
        .logo span:last-child {
            font-family: var(--serif);
            font-style: italic;
            color: var(--accent);
            font-weight: 400;
        }
        .headline {
            font-family: var(--serif);
            font-style: italic;
            font-size: 20px;
            line-height: 1.3;
            margin-bottom: 2.5rem;
            color: var(--text);
            text-align: center;
            opacity: 0.8;
        }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block; font-size: 11px;
            color: var(--muted); margin-bottom: 6px;
            text-transform: uppercase; letter-spacing: 0.1em;
        }
        .form-control {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 12px 14px;
            border-radius: 8px;
            font-family: var(--sans);
            font-size: 15px;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: rgba(232,255,71,0.4);
        }
        .btn {
            width: 100%;
            padding: 14px;
            border-radius: 100px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            margin-top: 1rem;
            background: var(--accent);
            color: #0a0a0a;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(232,255,71,0.2); }
        .erro-msg {
            background: rgba(255,71,71,0.1);
            border: 1px solid rgba(255,71,71,0.3);
            color: #ff6b6b;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .sucesso-msg {
            background: rgba(232,255,71,0.08);
            border: 1px solid rgba(232,255,71,0.25);
            color: var(--accent);
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .footer-link {
            text-align: center;
            margin-top: 2rem;
            font-size: 14px;
            color: var(--muted);
        }
        .footer-link a { color: var(--accent); text-decoration: none; font-weight: 600; }
        .footer-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="card">
        <div class="logo"><span>MEUS</span> <span>Roteiros</span></div>
        <div class="headline">Roteiros e Narrativas</div>

        <?php if ($erro): ?>
        <div class="erro-msg"><?= $erro ?></div>
        <?php endif; ?>
        <?php if ($mensagem): ?>
        <div class="sucesso-msg"><?= sanitizar($mensagem) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email" class="form-control" 
                    placeholder="seu@email.com" required autocomplete="email" value="<?= sanitizar($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" class="form-control" 
                    placeholder="••••••••" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn">Acessar roteiros →</button>
        </form>

        <div class="footer-link" style="margin-top:1rem;">
            <a href="<?= raizUrl('/esqueci-senha.php') ?>">Esqueci minha senha</a>
        </div>

        <div class="footer-link">
            Não tem uma conta? <a href="registro.php">Criar agora →</a>
        </div>
    </div>

</body>
</html>
