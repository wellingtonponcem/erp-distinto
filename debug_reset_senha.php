<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

echo "=== DEBUG: Testando Reset de Senha ===\n\n";

try {
    $db = Database::get();
    echo "✓ Conectado ao Neon\n\n";

    // 1. Listar usuários
    $stmt = $db->query("SELECT id, email, senha FROM users LIMIT 5");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        die("❌ Nenhum usuário encontrado no banco!\n");
    }
    
    echo "Usuários encontrados:\n";
    foreach ($users as $u) {
        $senhaLen = strlen($u['senha']);
        echo "  - {$u['email']}: ID={$u['id']}, Senha length={$senhaLen}\n";
    }
    
    // 2. Testar UPDATE com user_id (como está no novo código)
    echo "\n\n=== Teste 1: UPDATE usando user_id ===\n";
    
    $testUser = $users[0];
    $testId = $testUser['id'];
    $testEmail = $testUser['email'];
    $oldSenha = $testUser['senha'];
    
    echo "Usuário de teste: {$testEmail} (ID: {$testId})\n";
    echo "Senha atual (hash): " . substr($oldSenha, 0, 20) . "...\n\n";
    
    // Gerar nova senha e hash
    $novaSenha = 'teste123456';
    $novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);
    
    echo "Nova senha: {$novaSenha}\n";
    echo "Novo hash: " . substr($novoHash, 0, 20) . "...\n\n";
    
    // Fazer UPDATE
    $stmt = $db->prepare("UPDATE users SET senha = ? WHERE id = ?");
    $stmt->execute([$novoHash, $testId]);
    $rowsAffected = $stmt->rowCount();
    
    echo "Linhas afetadas pelo UPDATE: {$rowsAffected}\n\n";
    
    if ($rowsAffected < 1) {
        echo "❌ PROBLEMA: Nenhuma linha foi atualizada!\n";
        echo "   - Verifique se o user_id existe\n";
        echo "   - Verifique permissões do banco\n\n";
    }
    
    // 3. Verificar se a senha foi atualizada
    echo "=== Teste 2: Verificar UPDATE ===\n";
    
    $stmt = $db->prepare("SELECT senha FROM users WHERE id = ?");
    $stmt->execute([$testId]);
    $senhaAtual = $stmt->fetchColumn();
    
    echo "Senha no banco agora: " . substr($senhaAtual, 0, 20) . "...\n";
    
    if ($senhaAtual === $oldSenha) {
        echo "❌ PROBLEMA: A senha não foi atualizada!\n";
        echo "   - Verificar se há trigger no banco\n";
        echo "   - Verificar se há constraint\n\n";
    } else {
        echo "✓ Senha foi atualizada no banco\n\n";
    }
    
    // 4. Testar password_verify
    echo "=== Teste 3: Verificar password_verify ===\n";
    
    $verificaNovaHash = password_verify($novaSenha, $senhaAtual);
    $verificaHashAntigo = password_verify($novaSenha, $oldSenha);
    
    echo "password_verify('{$novaSenha}', senha_nova): " . ($verificaNovaHash ? '✓ true' : '❌ false') . "\n";
    echo "password_verify('{$novaSenha}', senha_antiga): " . ($verificaHashAntigo ? 'true (PROBLEMA!)' : '✓ false') . "\n\n";
    
    if (!$verificaNovaHash) {
        echo "❌ PROBLEMA: A nova senha não confere com o hash gravado!\n\n";
    }
    
    // 5. Revertendo (UPDATE com hash antigo)
    echo "=== Revertendo alterações ===\n";
    $stmt = $db->prepare("UPDATE users SET senha = ? WHERE id = ?");
    $stmt->execute([$oldSenha, $testId]);
    echo "✓ Senha revertida para o original\n\n";
    
    echo "=== FIM DO DEBUG ===\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
