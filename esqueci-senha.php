<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

$mensagem = '';
$erro = '';

function garantirTabelaResetSenha(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id VARCHAR(36) PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL,
        token_hash VARCHAR(64) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        used_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    try { $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_password_reset_token_hash ON password_reset_tokens (token_hash)"); } catch (Exception $e) {}
    try { $db->exec("CREATE INDEX IF NOT EXISTS idx_password_reset_user_id ON password_reset_tokens (user_id)"); } catch (Exception $e) {}
}

function enviarEmailResetSenha(string $email, string $nome, string $url): bool {
    $host = parse_url(APP_URL, PHP_URL_HOST) ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $host = preg_replace('/[^a-zA-Z0-9.-]/', '', $host) ?: 'localhost';
    $from = 'no-reply@' . $host;
    $subject = 'Redefinicao de senha';
    $body = "Ola" . ($nome ? ", {$nome}" : "") . ".\n\n"
        . "Recebemos uma solicitacao para redefinir sua senha.\n\n"
        . "Use este link nas proximas 1 hora:\n{$url}\n\n"
        . "Se voce nao solicitou isso, ignore este e-mail.\n";
    $headers = [
        'From: ' . $from,
        'Reply-To: ' . $from,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion(),
    ];

    return @mail($email, $subject, $body, implode("\r\n", $headers));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail valido.';
    } else {
        try {
            $db = Database::get();
            garantirTabelaResetSenha($db);

            $stmt = $db->prepare("SELECT id, nome, email FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
                $tokenHash = hash('sha256', $token);
                $expiresAt = date('Y-m-d H:i:s', time() + 3600);

                $db->prepare("UPDATE password_reset_tokens SET used_at = CURRENT_TIMESTAMP WHERE user_id = ? AND used_at IS NULL")
                   ->execute([$user['id']]);

                $db->prepare("INSERT INTO password_reset_tokens (id, user_id, token_hash, expires_at) VALUES (?, ?, ?, ?)")
                   ->execute([gerarId(), $user['id'], $tokenHash, $expiresAt]);

                $appScheme = parse_url(APP_URL, PHP_URL_SCHEME) ?: 'https';
                $appHost = parse_url(APP_URL, PHP_URL_HOST) ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');
                $appPort = parse_url(APP_URL, PHP_URL_PORT);
                $appOrigin = $appScheme . '://' . $appHost . ($appPort ? ':' . $appPort : '');
                $resetUrl = rtrim($appOrigin, '/') . raizUrl('/redefinir-senha.php?token=' . urlencode($token));
                enviarEmailResetSenha($user['email'], $user['nome'] ?? '', $resetUrl);
            }

            $mensagem = 'Se o e-mail estiver cadastrado, enviaremos um link de redefinicao em alguns minutos.';
        } catch (Exception $e) {
            $erro = 'Nao foi possivel iniciar a recuperacao agora. Tente novamente em instantes.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#0a0a0a; --surface:#111; --surface2:#181818; --border:rgba(255,255,255,.07); --accent:#E8FF47; --text:#F0EDE6; --muted:#888; --sans:'DM Sans', sans-serif; --display:'Bebas Neue', sans-serif; --serif:'Instrument Serif', Georgia, serif; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { background:var(--bg); color:var(--text); font-family:var(--sans); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem 1rem; }
        .card { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:3rem 2.5rem; width:100%; max-width:430px; }
        h1 { font-family:var(--display); font-size:38px; line-height:.95; text-align:center; margin-bottom:.7rem; }
        .sub { color:var(--muted); font-size:14px; text-align:center; margin-bottom:2rem; line-height:1.5; }
        label { display:block; font-size:11px; color:var(--muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:.1em; }
        input { width:100%; background:var(--surface2); border:1px solid var(--border); color:var(--text); padding:12px 14px; border-radius:8px; font-family:var(--sans); font-size:15px; outline:none; }
        input:focus { border-color:rgba(232,255,71,.4); }
        .btn { width:100%; padding:14px; border-radius:100px; border:0; background:var(--accent); color:#0a0a0a; font-size:15px; font-weight:700; cursor:pointer; margin-top:1.3rem; }
        .msg, .err { padding:12px 14px; border-radius:8px; font-size:14px; margin-bottom:1.2rem; text-align:center; }
        .msg { background:rgba(232,255,71,.08); border:1px solid rgba(232,255,71,.25); color:var(--accent); }
        .err { background:rgba(255,71,71,.1); border:1px solid rgba(255,71,71,.3); color:#ff6b6b; }
        .link { text-align:center; margin-top:1.5rem; font-size:14px; color:var(--muted); }
        .link a { color:var(--accent); text-decoration:none; font-weight:700; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Recuperar senha</h1>
        <p class="sub">Digite seu e-mail. Se ele estiver cadastrado, enviaremos um link seguro para criar uma nova senha.</p>

        <?php if ($mensagem): ?><div class="msg"><?= sanitizar($mensagem) ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="err"><?= sanitizar($erro) ?></div><?php endif; ?>

        <form method="POST">
            <label>E-mail</label>
            <input type="email" name="email" required autocomplete="email" value="<?= sanitizar($_POST['email'] ?? '') ?>" placeholder="seu@email.com">
            <button type="submit" class="btn">Enviar link de redefinicao</button>
        </form>

        <div class="link"><a href="<?= raizUrl('/login-roteiros.php') ?>">Voltar ao login</a></div>
    </div>
</body>
</html>
