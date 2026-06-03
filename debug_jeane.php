<?php
/**
 * Debug para testar reset de jeaneponcemsm@gmail.com
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/html; charset=UTF-8');

try {
    require_once __DIR__ . '/config/env.php';
    require_once __DIR__ . '/config/database.php';
} catch (Exception $e) {
    die("ERRO ao carregar: " . $e->getMessage());
}

$db = Database::get();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Debug - Jeane Poncems</title>
    <style>
        body { font-family: monospace; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        .step { margin: 15px 0; padding: 15px; border-left: 4px solid #ccc; }
        .step.success { border-left-color: #28a745; background: #e8f5e9; }
        .step.error { border-left-color: #dc3545; background: #ffebee; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 20px; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .info-box { background: #e3f2fd; border: 1px solid #90caf9; padding: 15px; margin: 20px 0; border-radius: 3px; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 Debug: jeaneponcemsm@gmail.com</h1>
    
    <div class="info-box">
        <strong>Email:</strong> jeaneponcemsm@gmail.com<br>
        <strong>Senha Testada:</strong> !@190118!<br>
        <strong>Objetivo:</strong> Verificar se usuário existe e testar login
    </div>

    <h2>📋 Etapas do Debug</h2>

    <?php
    // 1. Buscar usuário
    echo "<div class='step'>";
    try {
        $stmt = $db->prepare("SELECT id, email, nome, senha FROM users WHERE LOWER(TRIM(email)) = LOWER(TRIM(?))");
        $stmt->execute(['jeaneponcemsm@gmail.com']);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            echo "<div class='step error'>❌ Usuário NÃO encontrado no banco</div>";
        } else {
            echo "<div class='step success'>";
            echo "✓ Usuário encontrado:<br>";
            echo "  - ID: " . htmlspecialchars($usuario['id']) . "<br>";
            echo "  - Email: " . htmlspecialchars($usuario['email']) . "<br>";
            echo "  - Nome: " . htmlspecialchars($usuario['nome']) . "<br>";
            echo "  - Hash no banco: " . substr($usuario['senha'], 0, 15) . "...<br>";
            echo "</div>";
            
            // 2. Testar password_verify
            echo "<div class='step'>";
            $senha_teste = '!@190118!';
            if (password_verify($senha_teste, $usuario['senha'])) {
                echo "<div class='step success'>";
                echo "✓ password_verify FUNCIONA com a senha: $senha_teste";
                echo "</div>";
            } else {
                echo "<div class='step error'>";
                echo "❌ password_verify FALHOU com a senha: $senha_teste<br>";
                echo "Hash esperado: " . $usuario['senha'] . "<br>";
                echo "Testando se é um hash bcrypt válido...<br>";
                
                // Testar com uma senha conhecida para ver se o hash é válido
                $test_hash = password_hash('teste123', PASSWORD_DEFAULT);
                if (password_verify('teste123', $test_hash)) {
                    echo "✓ password_hash/verify funciona com hashes novos<br>";
                    echo "⚠ Problema: O hash no banco parece corrompido ou não é bcrypt";
                } else {
                    echo "❌ Até password_hash/verify não funciona - problema no servidor PHP";
                }
                echo "</div>";
            }
            
            // 3. Verificar tipo de hash
            echo "<div class='step'>";
            $senha_banco = $usuario['senha'];
            echo "Análise do hash no banco:<br>";
            echo "  - Comprimento: " . strlen($senha_banco) . " caracteres<br>";
            echo "  - Primeiros 4 caracteres: " . substr($senha_banco, 0, 4) . "<br>";
            
            if (strlen($senha_banco) === 60 && substr($senha_banco, 0, 4) === '$2y$') {
                echo "  - ✓ É um hash bcrypt válido (PASSWORD_DEFAULT)<br>";
            } elseif (strlen($senha_banco) === 60 && substr($senha_banco, 0, 4) === '$2a$') {
                echo "  - ⚠ É um hash bcrypt antigo ($2a$) - pode ter problemas<br>";
            } elseif (substr($senha_banco, 0, 6) === '$argon') {
                echo "  - ✓ É um hash Argon2 (mais seguro)<br>";
            } else {
                echo "  - ❌ NÃO é um hash bcrypt válido - parece estar corrompido ou em plain text<br>";
            }
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div class='step error'>❌ Erro ao consultar: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    ?>

    <h2>📝 Resumo</h2>
    <div class="info-box">
        <?php if ($usuario): ?>
            <?php if (password_verify('!@190118!', $usuario['senha'])): ?>
                <p>✅ <strong>Usuário e senha estão corretos no banco!</strong></p>
                <p>Se o login não funciona, o problema pode estar em:</p>
                <ul>
                    <li>Sessão não sendo criada corretamente</li>
                    <li>Cookies não sendo salvos</li>
                    <li>Redirecionamento falhando após login</li>
                    <li>Verificação de autenticação nas páginas protegidas</li>
                </ul>
                <p>Verifique <a href="/sistema/login-roteiros.php?debug=1">login-roteiros.php</a></p>
            <?php else: ?>
                <p>❌ <strong>A senha NÃO bate com o hash no banco!</strong></p>
                <p>Possíveis causas:</p>
                <ul>
                    <li>Usuário digitou a senha errada ao fazer reset</li>
                    <li>Hash no banco está corrompido</li>
                    <li>Reset de senha não gravou corretamente</li>
                </ul>
            <?php endif; ?>
        <?php else: ?>
            <p>❌ <strong>Usuário não existe no banco!</strong></p>
            <p>Verifique se o email está correto: jeaneponcemsm@gmail.com</p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
