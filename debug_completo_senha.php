<?php
/**
 * Script de debug completo para rastrear problema de reset de senha
 * Testa: 1) Se senha está sendo atualizada, 2) Se redirecionamento funciona, 3) Se login funciona
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/html; charset=UTF-8');

try {
    require_once __DIR__ . '/config/env.php';
    require_once __DIR__ . '/config/auth.php';
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/includes/helpers.php';
} catch (Exception $e) {
    die("ERRO ao carregar arquivos: " . $e->getMessage());
}

// Função para simular o fluxo de reset
function testarFluxoReset(string $email, string $senhaNova): array {
    global $db;
    
    $resultado = [
        'email' => $email,
        'passos' => []
    ];
    
    try {
        // 1. Buscar usuário
        $stmt = $db->prepare("SELECT id, email, senha as senha_antiga FROM users WHERE LOWER(TRIM(email)) = LOWER(TRIM(?))");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            $resultado['passos'][] = '❌ Usuário não encontrado';
            return $resultado;
        }
        
        $resultado['passos'][] = "✓ Usuário encontrado: id={$usuario['id']}, email={$usuario['email']}";
        $user_id = $usuario['id'];
        $senha_antiga = $usuario['senha_antiga'];
        
        // 2. Verificar que a senha antiga ainda funciona
        if (!password_verify($email, $senha_antiga)) {
            // Tentar com a senha teste conhecida
            if (!password_verify('t33180724', $senha_antiga)) {
                $resultado['passos'][] = "⚠ Senha antiga não verifica com nenhum valor conhecido";
            } else {
                $resultado['passos'][] = "✓ Senha antiga (t33180724) verifica com sucesso";
            }
        }
        
        // 3. Atualizar senha no banco
        $hash = password_hash($senhaNova, PASSWORD_DEFAULT);
        $stmtUpdate = $db->prepare("UPDATE users SET senha = ? WHERE id = ?");
        $stmtUpdate->execute([$hash, $user_id]);
        
        if ($stmtUpdate->rowCount() < 1) {
            $resultado['passos'][] = "❌ UPDATE não afetou nenhuma linha";
            return $resultado;
        }
        
        $resultado['passos'][] = "✓ UPDATE executado: 1 linha afetada";
        
        // 4. Verificar que a nova senha foi gravada
        $stmtCheck = $db->prepare("SELECT senha FROM users WHERE id = ?");
        $stmtCheck->execute([$user_id]);
        $senhaGravada = $stmtCheck->fetchColumn();
        
        if (!$senhaGravada) {
            $resultado['passos'][] = "❌ Não conseguiu ler a senha do banco após UPDATE";
            return $resultado;
        }
        
        $resultado['passos'][] = "✓ Senha lida do banco: " . substr($senhaGravada, 0, 10) . "...";
        
        // 5. Verificar password_verify com a nova senha
        if (!password_verify($senhaNova, $senhaGravada)) {
            $resultado['passos'][] = "❌ password_verify FALHOU com a nova senha";
            $resultado['passos'][] = "   - Nova senha: $senhaNova";
            $resultado['passos'][] = "   - Hash no banco: " . $senhaGravada;
            return $resultado;
        }
        
        $resultado['passos'][] = "✓ password_verify SUCESSO com a nova senha";
        
        // 6. Verificar que a senha antiga NÃO funciona mais
        if (password_verify('t33180724', $senhaGravada)) {
            $resultado['passos'][] = "❌ PROBLEMA: Senha antiga AINDA FUNCIONA!";
            return $resultado;
        }
        
        $resultado['passos'][] = "✓ Senha antiga não funciona mais (esperado)";
        
        $resultado['sucesso'] = true;
        $resultado['user_id'] = $user_id;
        
    } catch (Exception $e) {
        $resultado['passos'][] = "❌ Exception: " . $e->getMessage();
    }
    
    return $resultado;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Debug Completo - Reset de Senha</title>
    <style>
        body { font-family: monospace; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #ccc; }
        .step.success { border-left-color: #28a745; background: #e8f5e9; }
        .step.error { border-left-color: #dc3545; background: #ffebee; }
        .step.info { border-left-color: #17a2b8; background: #e1f5fe; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
        .info-box { background: #e3f2fd; border: 1px solid #90caf9; padding: 15px; margin: 20px 0; border-radius: 3px; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 Debug Completo: Reset de Senha</h1>
    
    <div class="info-box">
        <strong>Objetivo:</strong> Simular o fluxo completo de reset de senha para diagnosticar o bug<br>
        <strong>Email Teste:</strong> faustinosdg@gmail.com<br>
        <strong>Senha Nova:</strong> !@Jeane&w#1<br>
        <strong>Banco de Dados:</strong> <?php echo DB_HOST; ?> (<?php echo DB_NAME; ?>)<br>
        <strong>Usuário DB:</strong> <?php echo DB_USER; ?>
    </div>
    
    <h2>📋 Teste de Fluxo Completo</h2>
    
    <?php
    // Executar o teste
    $resultado = testarFluxoReset('faustinosdg@gmail.com', '!@Jeane&w#1');
    
    // Exibir resultado
    foreach ($resultado['passos'] as $passo) {
        $classe = 'info';
        if (strpos($passo, '✓') === 0) $classe = 'success';
        if (strpos($passo, '❌') === 0) $classe = 'error';
        if (strpos($passo, '⚠') === 0) $classe = 'error';
        
        echo "<div class='step $classe'>$passo</div>";
    }
    
    if (isset($resultado['sucesso']) && $resultado['sucesso']) {
        echo "<div class='step success' style='margin-top: 30px; background: #c8e6c9; border-left-color: #2e7d32; font-weight: bold;'>";
        echo "✅ SUCESSO: O fluxo funcionou corretamente!<br>";
        echo "A senha foi atualizada e password_verify funciona.<br>";
        echo "Se o login ainda não funciona, o problema está em outro lugar (sessão, cookies, redirecionamento).";
        echo "</div>";
    } else {
        echo "<div class='step error' style='margin-top: 30px; background: #ffcdd2; border-left-color: #c62828; font-weight: bold;'>";
        echo "❌ FALHA: O fluxo não completou com sucesso.<br>";
        echo "Verifique os passos acima para encontrar onde falhou.";
        echo "</div>";
    }
    ?>
    
    <h2>📊 Informações do Servidor</h2>
    <div class="info-box">
        <strong>PHP Version:</strong> <?php echo phpversion(); ?><br>
        <strong>SCRIPT_NAME:</strong> <?php echo $_SERVER['SCRIPT_NAME']; ?><br>
        <strong>SCRIPT_FILENAME:</strong> <?php echo $_SERVER['SCRIPT_FILENAME']; ?><br>
        <strong>REQUEST_URI:</strong> <?php echo $_SERVER['REQUEST_URI']; ?><br>
        <strong>BASE URL (raizUrl):</strong> <?php echo raizUrl(''); ?><br>
        <strong>APP_URL Configurado:</strong> <?php echo APP_URL; ?>
    </div>
    
    <h2>📝 Próximas Ações</h2>
    <div class="info-box">
        <?php if (isset($resultado['sucesso']) && $resultado['sucesso']): ?>
            <p>1. Teste o login em <a href="<?php echo raizUrl('/login-roteiros.php'); ?>"><?php echo raizUrl('/login-roteiros.php'); ?></a></p>
            <p>2. Use as credenciais: email=faustinosdg@gmail.com | senha=!@Jeane&w#1</p>
            <p>3. Verificar se login funciona agora</p>
        <?php else: ?>
            <p>1. Corrija o problema identificado acima</p>
            <p>2. Recarregue esta página para testar novamente</p>
            <p>3. Verifique os logs do PHP para mais detalhes</p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
