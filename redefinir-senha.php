<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$erro = '';
$mensagem = '';
$tokenValido = false;
$resetRow = null;

function garantirEstruturaResetSenha(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id VARCHAR(36) PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL,
        token_hash VARCHAR(64) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        used_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $isMysql = (DB_PORT == 3306);

    if ($isMysql) {
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'senha'");
        $col = $stmt->fetch(PDO::FETCH_ASSOC);
        $type = strtolower($col['Type'] ?? '');
        if ($type !== '' && preg_match('/varchar\((\d+)\)/', $type, $m) && (int)$m[1] < 255) {
            $db->exec("ALTER TABLE users MODIFY senha VARCHAR(255) NOT NULL");
        }
    } else {
        $stmt = $db->query("
            SELECT data_type, character_maximum_length
            FROM information_schema.columns
            WHERE table_name = 'users' AND column_name = 'senha'
            LIMIT 1
        ");
        $col = $stmt->fetch(PDO::FETCH_ASSOC);
        $max = (int)($col['character_maximum_length'] ?? 0);
        if ($col && $col['data_type'] !== 'text' && ($max > 0 && $max < 255)) {
            $db->exec("ALTER TABLE users ALTER COLUMN senha TYPE VARCHAR(255)");
        }
    }
}

function buscarTokenReset(PDO $db, string $token): ?array {
    if ($token === '') return null;

    $tokenHash = hash('sha256', $token);
    $stmt = $db->prepare("
        SELECT prt.id, prt.user_id, u.email
        FROM password_reset_tokens prt
        INNER JOIN users u ON u.id = prt.user_id
        WHERE prt.token_hash = ?
          AND prt.used_at IS NULL
          AND prt.expires_at > CURRENT_TIMESTAMP
        LIMIT 1
    ");
    $stmt->execute([$tokenHash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

try {
    $db = Database::get();
    garantirEstruturaResetSenha($db);
    $resetRow = buscarTokenReset($db, $token);
    $tokenValido = (bool)$resetRow;
} catch (Exception $e) {
    $erro = 'Link invalido ou expirado.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';

    if (!$tokenValido) {
        $erro = 'Link invalido ou expirado. Solicite uma nova redefinicao.';
    } elseif (strlen($senha) < 8) {
        $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
    } elseif ($senha !== $confirmar) {
        $erro = 'As senhas nao coincidem.';
    } else {
        try {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $db->beginTransaction();

            $stmtUser = $db->prepare("UPDATE users SET senha = ? WHERE id = ?");
            $stmtUser->execute([$hash, $resetRow['user_id']]);

            if ($stmtUser->rowCount() < 1) {
                throw new RuntimeException('Nenhum usuario encontrado para atualizar senha pelo token.');
            }

            $stmtCheck = $db->prepare("SELECT senha FROM users WHERE id = ?");
            $stmtCheck->execute([$resetRow['user_id']]);
            $senhasAtualizadas = $stmtCheck->fetchAll(PDO::FETCH_COLUMN);
            $senhaConfirmada = $senhasAtualizadas !== [];
            foreach ($senhasAtualizadas as $senhaBanco) {
                if (!password_verify($senha, $senhaBanco)) {
                    $senhaConfirmada = false;
                    break;
                }
            }

            if (!$senhaConfirmada) {
                throw new RuntimeException('Senha gravada nao confere com a nova senha informada.');
            }

            $db->prepare("
                UPDATE password_reset_tokens
                SET used_at = CURRENT_TIMESTAMP
                WHERE used_at IS NULL
                  AND user_id = ?
            ")->execute([$resetRow['user_id']]);

            $db->commit();
            error_log("✓ RESET SUCESSO: Email={$resetRow['email']}, User_id={$resetRow['user_id']}");
            deslogarUsuario();
            header('Location: ' . raizUrl('/index.php?senha_redefinida=1'));
            exit;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('Erro ao redefinir senha: ' . $e->getMessage());
            $erro = 'Nao foi possivel redefinir a senha agora. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir senha</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#0a0a0a; --surface:#111; --surface2:#181818; --border:rgba(255,255,255,.07); --accent:#E8FF47; --text:#F0EDE6; --muted:#888; --sans:'DM Sans', sans-serif; --display:'Bebas Neue', sans-serif; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { background:var(--bg); color:var(--text); font-family:var(--sans); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem 1rem; }
        .card { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:3rem 2.5rem; width:100%; max-width:430px; }
        h1 { font-family:var(--display); font-size:38px; line-height:.95; text-align:center; margin-bottom:.7rem; }
        .sub { color:var(--muted); font-size:14px; text-align:center; margin-bottom:2rem; line-height:1.5; }
        .group { margin-bottom:1rem; }
        label { display:block; font-size:11px; color:var(--muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:.1em; }
        input { width:100%; background:var(--surface2); border:1px solid var(--border); color:var(--text); padding:12px 14px; border-radius:8px; font-family:var(--sans); font-size:15px; outline:none; }
        input:focus { border-color:rgba(232,255,71,.4); }
        .btn { width:100%; padding:14px; border-radius:100px; border:0; background:var(--accent); color:#0a0a0a; font-size:15px; font-weight:700; cursor:pointer; margin-top:.5rem; }
        .msg, .err { padding:12px 14px; border-radius:8px; font-size:14px; margin-bottom:1.2rem; text-align:center; }
        .msg { background:rgba(232,255,71,.08); border:1px solid rgba(232,255,71,.25); color:var(--accent); }
        .err { background:rgba(255,71,71,.1); border:1px solid rgba(255,71,71,.3); color:#ff6b6b; }
        .link { text-align:center; margin-top:1.5rem; font-size:14px; color:var(--muted); }
        .link a { color:var(--accent); text-decoration:none; font-weight:700; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Nova senha</h1>
        <p class="sub">Crie uma nova senha com pelo menos 8 caracteres.</p>

        <?php if ($mensagem): ?><div class="msg"><?= sanitizar($mensagem) ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="err"><?= sanitizar($erro) ?></div><?php endif; ?>

        <?php if ($tokenValido): ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?= sanitizar($token) ?>">
            <div class="group">
                <label>Nova senha</label>
                <input type="password" name="senha" required autocomplete="new-password" minlength="8">
            </div>
            <div class="group">
                <label>Confirmar nova senha</label>
                <input type="password" name="confirmar_senha" required autocomplete="new-password" minlength="8">
            </div>
            <button type="submit" class="btn">Redefinir senha</button>
        </form>
        <?php endif; ?>

        <div class="link">
            <a href="<?= raizUrl('/index.php') ?>">Ir para o login</a>
            <?php if (!$tokenValido && !$mensagem): ?>
                <br><br><a href="<?= raizUrl('/esqueci-senha.php') ?>">Solicitar novo link</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
