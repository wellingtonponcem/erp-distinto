<?php
session_start();
require_once dirname(__DIR__) . '/includes/db.php';
if (isset($_SESSION['admin_logged'])) {
    header('Location: ' . url('admin/'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    try {
        $stmt = db()->prepare('SELECT id, email, senha, nivel FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $user]);
        $db_user = $stmt->fetch();

        if ($db_user && password_verify($pass, $db_user['senha']) && (int)$db_user['nivel'] >= 1) {
            $_SESSION['admin_logged'] = true;
            header('Location: ' . url('admin/'));
            exit;
        }
    } catch (PDOException $e) {
        $error = 'Erro de conexão ou tabela inexistente.';
    }
    
    if (!$error) {
        $error = 'E-mail ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Distinto Studio</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>body{background:#131313;color:#e2e2e2;font-family:'Space Grotesk',sans-serif}</style>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700;900&display=swap">
</head>
<body class="min-h-screen flex items-center justify-center">
<div class="w-full max-w-sm p-12 border border-white/10">
    <img src="/assets/imgs/distinto_footes.png" alt="Distinto" class="w-40 mb-12 opacity-80">
    <h1 class="text-xl font-black uppercase tracking-tighter mb-8">Acesso Admin</h1>
    <?php if ($error): ?>
        <p class="text-red-400 text-xs font-mono mb-6"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST" class="space-y-6">
        <div>
            <label class="text-xs text-white/40 uppercase tracking-widest block mb-2">E-mail</label>
            <input name="username" type="email" class="w-full bg-transparent border border-white/10 focus:border-white px-4 py-3 outline-none text-sm" required>
        </div>
        <div>
            <label class="text-xs text-white/40 uppercase tracking-widest block mb-2">Senha</label>
            <input name="password" type="password" class="w-full bg-transparent border border-white/10 focus:border-white px-4 py-3 outline-none text-sm" required>
        </div>
        <button type="submit" class="w-full border border-white/20 hover:border-white py-3 text-xs uppercase tracking-widest font-bold transition-all hover:bg-white hover:text-black">
            ENTRAR
        </button>
    </form>
</div>
</body>
</html>
