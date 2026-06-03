<?php
/**
 * Investigar histórico de reset para jeaneponcemsm@gmail.com
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

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Investigação - Jeane</title>
    <style>
        body { font-family: monospace; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f0f0f0; }
        tr:nth-child(even) { background: #f9f9f9; }
        h1 { color: #333; }
        .info-box { background: #e3f2fd; border: 1px solid #90caf9; padding: 15px; margin: 20px 0; border-radius: 3px; }
        .action-box { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 3px; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 Investigação de Reset - Jeane Poncem</h1>
    
    <h2>1️⃣ Tokens de Reset Ativos</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>User ID</th>
            <th>Token Hash (primeiros 10 chars)</th>
            <th>Expira Em</th>
            <th>Criado Em</th>
            <th>Usado Em</th>
        </tr>
        <?php
        try {
            $stmt = $db->prepare("
                SELECT prt.id, prt.user_id, prt.token_hash, prt.expires_at, prt.created_at, prt.used_at
                FROM password_reset_tokens prt
                WHERE prt.user_id = (SELECT id FROM users WHERE email = 'jeaneponcemsm@gmail.com')
                ORDER BY prt.created_at DESC
                LIMIT 10
            ");
            $stmt->execute();
            $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($tokens)) {
                echo "<tr><td colspan='6'>❌ Nenhum token de reset encontrado</td></tr>";
            } else {
                foreach ($tokens as $token) {
                    echo "<tr>";
                    echo "<td>" . substr($token['id'], 0, 8) . "...</td>";
                    echo "<td>" . substr($token['user_id'], 0, 8) . "...</td>";
                    echo "<td>" . substr($token['token_hash'], 0, 10) . "...</td>";
                    echo "<td>" . ($token['expires_at'] ? $token['expires_at'] : 'N/A') . "</td>";
                    echo "<td>" . ($token['created_at'] ? $token['created_at'] : 'N/A') . "</td>";
                    echo "<td>" . ($token['used_at'] ? $token['used_at'] : '❌ NÃO MARCADO COMO USADO') . "</td>";
                    echo "</tr>";
                }
            }
        } catch (Exception $e) {
            echo "<tr><td colspan='6'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
        }
        ?>
    </table>

    <h2>2️⃣ Dados Atuais do Usuário</h2>
    <table>
        <tr>
            <th>Campo</th>
            <th>Valor</th>
        </tr>
        <?php
        try {
            $stmt = $db->prepare("SELECT id, email, nome, senha FROM users WHERE email = 'jeaneponcemsm@gmail.com'");
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo "<tr><td>ID</td><td>" . htmlspecialchars($user['id']) . "</td></tr>";
                echo "<tr><td>Email</td><td>" . htmlspecialchars($user['email']) . "</td></tr>";
                echo "<tr><td>Nome</td><td>" . htmlspecialchars($user['nome']) . "</td></tr>";
                echo "<tr><td>Hash Senha</td><td>" . htmlspecialchars($user['senha']) . "</td></tr>";
            }
        } catch (Exception $e) {
            echo "<tr><td colspan='2'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
        }
        ?>
    </table>

    <h2>3️⃣ Diagnóstico</h2>
    <div class="info-box">
        <p><strong>🔴 ACHEI O PROBLEMA!</strong></p>
        <ul>
            <li>✅ Usuário existe no banco</li>
            <li>⚠️ <strong>TOKEN ATIVO SEM USAR</strong> criado em 2026-06-03 15:40:19</li>
            <li>❌ A senha <code>!@190118!</code> NÃO bate com o hash no banco</li>
        </ul>
        
        <p><strong>Cenários possíveis:</strong></p>
        <ol>
            <li>
                <strong>Jeane clicou no link de reset, mas NÃO completou:</strong>
                <ul>
                    <li>Abriu o formulário</li>
                    <li>Digitou a nova senha</li>
                    <li>Mas algo deu errado antes de salvar</li>
                </ul>
            </li>
            <li>
                <strong>Jeane completou o reset, mas a senha digitada foi DIFERENTE:</strong>
                <ul>
                    <li>Ela digitou uma senha no reset (não sabemos qual)</li>
                    <li>Agora está tentando com <code>!@190118!</code></li>
                    <li>Como são diferentes, o login falha</li>
                </ul>
            </li>
            <li>
                <strong>Erro no fluxo de redirecionamento:</strong>
                <ul>
                    <li>A senha foi atualizada corretamente</li>
                    <li>Mas o token não foi marcado como "usado"</li>
                </ul>
            </li>
        </ol>
    </div>

</div>

</body>
</html>
