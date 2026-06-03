<?php
/**
 * Investigar o token específico que Jeane usou
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/html; charset=UTF-8');

try {
    require_once __DIR__ . '/config/env.php';
    require_once __DIR__ . '/config/database.php';
} catch (Exception $e) {
    die("ERRO: " . $e->getMessage());
}

$db = Database::get();
$token_recebido = 'uKpPnJoL05jCmw4Sm_uSn78MZBWnK1Hi6foYIaf6BYM';
$token_hash = hash('sha256', $token_recebido);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Investigação - Token Jeane</title>
    <style>
        body { font-family: monospace; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        .step { margin: 15px 0; padding: 15px; border-left: 4px solid #ccc; }
        .step.success { border-left-color: #28a745; background: #e8f5e9; }
        .step.error { border-left-color: #dc3545; background: #ffebee; }
        .step.warning { border-left-color: #ffc107; background: #fff3cd; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 Investigação do Token de Jeane</h1>

    <h2>1️⃣ Token Recebido</h2>
    <table>
        <tr>
            <th>Item</th>
            <th>Valor</th>
        </tr>
        <tr>
            <td>Token (claro)</td>
            <td><code><?php echo htmlspecialchars($token_recebido); ?></code></td>
        </tr>
        <tr>
            <td>Hash SHA256</td>
            <td><code><?php echo substr($token_hash, 0, 20); ?>...</code></td>
        </tr>
    </table>

    <h2>2️⃣ Buscando Token no Banco</h2>
    <?php
    try {
        $stmt = $db->prepare("
            SELECT 
                prt.id,
                prt.user_id,
                prt.token_hash,
                prt.expires_at,
                prt.used_at,
                prt.created_at,
                u.email,
                u.nome
            FROM password_reset_tokens prt
            JOIN users u ON u.id = prt.user_id
            WHERE prt.token_hash = ?
        ");
        $stmt->execute([$token_hash]);
        $tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tokenRow) {
            echo "<div class='step error'>";
            echo "❌ Token NÃO encontrado no banco!<br>";
            echo "Possíveis razões:<br>";
            echo "  1. Token foi digitado incorretamente<br>";
            echo "  2. Token foi deletado<br>";
            echo "  3. Sistema usa outro hash que não SHA256<br>";
            echo "</div>";
        } else {
            echo "<div class='step success'>";
            echo "✓ Token encontrado no banco!<br>";
            echo "</div>";
            
            echo "<table>";
            echo "<tr><th>Campo</th><th>Valor</th></tr>";
            echo "<tr><td>User ID</td><td>" . htmlspecialchars($tokenRow['user_id']) . "</td></tr>";
            echo "<tr><td>Email</td><td>" . htmlspecialchars($tokenRow['email']) . "</td></tr>";
            echo "<tr><td>Nome</td><td>" . htmlspecialchars($tokenRow['nome']) . "</td></tr>";
            echo "<tr><td>Criado em</td><td>" . $tokenRow['created_at'] . "</td></tr>";
            echo "<tr><td>Expira em</td><td>" . $tokenRow['expires_at'] . "</td></tr>";
            echo "<tr><td>Usado em</td><td>" . ($tokenRow['used_at'] ? $tokenRow['used_at'] : '❌ NÃO USADO') . "</td></tr>";
            echo "</table>";
            
            // Verificar se expirou
            $now = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
            $expires = new DateTime($tokenRow['expires_at'], new DateTimeZone('America/Sao_Paulo'));
            
            echo "<div class='step'>";
            if ($now > $expires) {
                echo "<div class='step error'>";
                echo "❌ Token EXPIROU!<br>";
                echo "Expirou em: " . $tokenRow['expires_at'] . "<br>";
                echo "Hora atual: " . $now->format('Y-m-d H:i:s') . "<br>";
                echo "Jeane precisa solicitar um novo reset de senha.";
                echo "</div>";
            } else {
                echo "<div class='step success'>";
                echo "✓ Token ainda é VÁLIDO (não expirou)<br>";
                echo "Expira em: " . $tokenRow['expires_at'];
                echo "</div>";
            }
            echo "</div>";
            
            // Verificar se já foi usado
            echo "<div class='step'>";
            if ($tokenRow['used_at']) {
                echo "<div class='step warning'>";
                echo "⚠️ Token JÁ FOI USADO em: " . $tokenRow['used_at'] . "<br>";
                echo "Isso significa que o reset foi completado antes!<br>";
                echo "Jeane deveria estar conseguindo fazer login com a senha que ela colocou.";
                echo "</div>";
            } else {
                echo "<div class='step warning'>";
                echo "⚠️ Token NUNCA FOI USADO<br>";
                echo "Isso significa que o reset foi enviado, mas Jeane ainda não clicou em 'Salvar'<br>";
                echo "OU clicou mas algo deu errado durante o processo.";
                echo "</div>";
            }
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div class='step error'>❌ Erro ao consultar: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    ?>

    <h2>3️⃣ Recomendações</h2>
    <div class="step warning">
        <strong>O que fazer agora:</strong><br><br>
        
        <?php if ($tokenRow): ?>
            <?php $now = new DateTime('now', new DateTimeZone('America/Sao_Paulo')); ?>
            <?php $expires = new DateTime($tokenRow['expires_at'], new DateTimeZone('America/Sao_Paulo')); ?>
            
            <?php if ($now > $expires): ?>
                <p>1. ❌ Token expirou - Jeane deve fazer um novo reset de senha</p>
                <p><a href="/sistema/esqueci-senha.php">Clique aqui para solicitar novo reset</a></p>
            <?php elseif ($tokenRow['used_at']): ?>
                <p>✓ Token foi marcado como usado em: <?php echo $tokenRow['used_at']; ?></p>
                <p>Isso significa o reset foi completado! Teste agora com a senha que Jeane colocou.</p>
            <?php else: ?>
                <p>⚠️ Token é válido mas NÃO foi marcado como usado</p>
                <p>Isso pode significar que Jeane começou o reset mas não finalizou.</p>
                <p>Peça para ela tentar o reset novamente clicando no link e completando o formulário.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
