<?php
/**
 * Qual é a senha atual de Jeane no banco?
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
    <title>Qual é a senha de Jeane?</title>
    <style>
        body { font-family: monospace; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        .step { margin: 15px 0; padding: 15px; border-left: 4px solid #ccc; }
        .step.success { border-left-color: #28a745; background: #e8f5e9; }
        .step.error { border-left-color: #dc3545; background: #ffebee; }
        h1, h2 { color: #333; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔎 Qual é a Senha Atual de Jeane?</h1>

    <h2>Informações de Jeane</h2>
    <table>
        <tr>
            <th>Campo</th>
            <th>Valor</th>
        </tr>
        <?php
        try {
            $stmt = $db->prepare("
                SELECT 
                    id,
                    email,
                    nome,
                    senha
                FROM users 
                WHERE LOWER(TRIM(email)) = LOWER(TRIM('jeaneponcemsm@gmail.com'))
            ");
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo "<tr><td>ID</td><td>" . htmlspecialchars($user['id']) . "</td></tr>";
                echo "<tr><td>Email</td><td>" . htmlspecialchars($user['email']) . "</td></tr>";
                echo "<tr><td>Nome</td><td>" . htmlspecialchars($user['nome']) . "</td></tr>";
                echo "<tr><td>Hash Senha</td><td>" . htmlspecialchars($user['senha']) . "</td></tr>";
                echo "<tr><td>Comprimento Hash</td><td>" . strlen($user['senha']) . " caracteres</td></tr>";
                
                // Testar senhas conhecidas
                echo "<tr style='background: #f9f9f9;'>";
                echo "<td><strong>Testando Senhas:</strong></td>";
                echo "<td>";
                
                $senhas_teste = [
                    '!@190118!' => 'Senha que Jeane colocou no reset',
                    '!@190118' => 'Sem exclamação final',
                    ' !@190118!' => 'Com espaço início',
                    '!@190118! ' => 'Com espaço final',
                ];
                
                foreach ($senhas_teste as $teste_senha => $descricao) {
                    if (password_verify($teste_senha, $user['senha'])) {
                        echo "<div style='background: #c8e6c9; padding: 10px; border-radius: 5px; margin: 5px 0;'>";
                        echo "<strong>✓ ENCONTRADA:</strong> <code>$teste_senha</code><br>";
                        echo "Descrição: $descricao";
                        echo "</div>";
                    }
                }
                
                echo "</td>";
                echo "</tr>";
            } else {
                echo "<tr><td colspan='2'>❌ Usuário não encontrado</td></tr>";
            }
        } catch (Exception $e) {
            echo "<tr><td colspan='2'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
        }
        ?>
    </table>

    <h2>📝 Próximas Ações</h2>
    <div class="step success">
        Agora teste fazer o login em <a href="/sistema/login-roteiros.php">/sistema/login-roteiros.php</a> com:<br><br>
        <strong>Email:</strong> jeaneponcemsm@gmail.com<br>
        <strong>Senha:</strong> !@190118!<br><br>
        Se não funcionar, volte para este debug e veja qual senha foi identificada como correta.
    </div>

</div>

</body>
</html>
