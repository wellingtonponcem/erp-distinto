<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
exigirAutenticacao();

$db = Database::get();

// Garante que a tabela exista
$db->exec("CREATE TABLE IF NOT EXISTS sistema_config (
    chave TEXT PRIMARY KEY,
    valor TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configs = [
        'groq_api_key' => $_POST['groq_key'] ?? '',
        'gemini_api_key' => $_POST['gemini_key'] ?? ''
    ];

    foreach ($configs as $chave => $valor) {
        $stmt = $db->prepare("INSERT INTO sistema_config (chave, valor, updated_at) 
                             VALUES (?, ?, CURRENT_TIMESTAMP) 
                             ON CONFLICT (chave) DO UPDATE SET valor = EXCLUDED.valor, updated_at = CURRENT_TIMESTAMP");
        $stmt->execute([$chave, $valor]);
    }
    $mensagem = "Configurações salvas com sucesso!";
}

// Buscar valores atuais
$stmt = $db->query("SELECT chave, valor FROM sistema_config");
$dbConfigs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$groqKey = $dbConfigs['groq_api_key'] ?? '';
$geminiKey = $dbConfigs['gemini_api_key'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações IA — Meus Roteiros</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #0a0a0a;
            --surface: #111111;
            --border: rgba(255,255,255,0.07);
            --accent: #E8FF47;
            --text: #F0EDE6;
            --muted: #888;
        }
        body { background: var(--bg); color: var(--text); font-family: 'DM Sans', sans-serif; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 5rem auto; padding: 2rem; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 24px; padding: 3rem; }
        h1 { font-family: 'Bebas Neue', sans-serif; font-size: 42px; margin-bottom: 2rem; letter-spacing: 1px; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); margin-bottom: 8px; font-weight: 600; }
        input { width: 100%; padding: 14px; background: #000; border: 1px solid var(--border); border-radius: 12px; color: #fff; font-family: inherit; outline: none; }
        input:focus { border-color: var(--accent); }
        .btn-save { background: var(--accent); color: #000; border: none; padding: 14px; width: 100%; border-radius: 12px; font-weight: 700; cursor: pointer; margin-top: 1rem; transition: transform 0.2s; }
        .btn-save:hover { transform: scale(1.02); }
        .alert { background: rgba(71, 255, 132, 0.1); color: #47ff84; padding: 12px; border-radius: 8px; margin-bottom: 2rem; font-size: 14px; text-align: center; }
        .back { text-decoration: none; color: var(--muted); font-size: 13px; display: block; margin-bottom: 2rem; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back">← Voltar</a>
        <div class="card">
            <h1>Chaves de <em>IA</em></h1>
            
            <?php if ($mensagem): ?>
                <div class="alert"><?php echo $mensagem; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Chave API Groq</label>
                    <input type="password" name="groq_key" value="<?php echo htmlspecialchars($groqKey); ?>" placeholder="gsk_...">
                </div>

                <div class="form-group">
                    <label>Chave API Gemini (Google)</label>
                    <input type="password" name="gemini_key" value="<?php echo htmlspecialchars($geminiKey); ?>" placeholder="AIza...">
                    <small style="font-size: 10px; color: var(--muted); margin-top: 5px; display: block;">Utilizada para Visão (OCR) e leitura de imagens.</small>
                </div>

                <button type="submit" class="btn-save">Salvar Configurações</button>
            </form>
        </div>
    </div>
</body>
</html>
