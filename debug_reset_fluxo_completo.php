<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

echo "=== DEBUG: Fluxo Completo de Reset de Senha ===\n\n";

try {
    $db = Database::get();
    echo "✓ Conectado ao Neon\n\n";

    // 1. Obter um usuário de teste
    $stmt = $db->query("SELECT id, email FROM users LIMIT 1");
    $testUser = $stmt->fetch();
    
    if (!$testUser) {
        die("❌ Nenhum usuário encontrado!\n");
    }
    
    $userId = $testUser['id'];
    $userEmail = $testUser['email'];
    echo "Usuário de teste: {$userEmail} (ID: {$userId})\n\n";
    
    // 2. Verificar se a tabela de tokens existe
    echo "=== Verificando tabela password_reset_tokens ===\n";
    try {
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM password_reset_tokens");
        $count = $stmt->fetchColumn();
        echo "✓ Tabela existe. Tokens atuais: {$count}\n\n";
    } catch (Exception $e) {
        echo "❌ Tabela não existe: " . $e->getMessage() . "\n\n";
    }
    
    // 3. Simular criação de token
    echo "=== Simulando Criação de Token ===\n";
    $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);
    
    echo "Token gerado: " . substr($token, 0, 20) . "...\n";
    echo "Hash do token: " . substr($tokenHash, 0, 20) . "...\n";
    echo "Expira em: {$expiresAt}\n\n";
    
    // 4. Limpar tokens antigos
    echo "=== Limpando tokens antigos ===\n";
    $stmt = $db->prepare("DELETE FROM password_reset_tokens WHERE user_id = ? AND used_at IS NULL");
    $stmt->execute([$userId]);
    $deleted = $stmt->rowCount();
    echo "✓ Tokens antigos removidos: {$deleted}\n\n";
    
    // 5. Inserir novo token
    echo "=== Inserindo novo token ===\n";
    $stmt = $db->prepare("INSERT INTO password_reset_tokens (id, user_id, token_hash, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([gerarId(), $userId, $tokenHash, $expiresAt]);
    echo "✓ Token inserido\n\n";
    
    // 6. Buscar token (como redefinir-senha.php faz)
    echo "=== Buscando token (como redefinir-senha.php) ===\n";
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
    $resetRow = $stmt->fetch();
    
    if (!$resetRow) {
        echo "❌ PROBLEMA: Token não foi encontrado!\n";
        die("\n");
    }
    
    echo "✓ Token encontrado\n";
    echo "  - user_id: {$resetRow['user_id']}\n";
    echo "  - email: {$resetRow['email']}\n\n";
    
    // 7. Simular redefinição de senha (como redefinir-senha.php faz)
    echo "=== Simulando Redefinição de Senha ===\n";
    
    $novaSenha = 'NovaSenha123!@#';
    $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
    
    echo "Nova senha: {$novaSenha}\n";
    echo "Hash: " . substr($hash, 0, 30) . "...\n\n";
    
    // 8. FAZER A TRANSAÇÃO (COMO NO CÓDIGO REAL)
    echo "=== Executando Transação ===\n";
    
    try {
        $db->beginTransaction();
        echo "✓ Transação iniciada\n";
        
        // Update user
        $stmtUser = $db->prepare("UPDATE users SET senha = ? WHERE id = ?");
        $stmtUser->execute([$hash, $resetRow['user_id']]);
        $rowCount = $stmtUser->rowCount();
        
        echo "  - UPDATE users: {$rowCount} linhas\n";
        
        if ($rowCount < 1) {
            throw new RuntimeException('UPDATE não afetou nenhuma linha!');
        }
        
        // Verificar se a senha foi gravada
        $stmtCheck = $db->prepare("SELECT senha FROM users WHERE id = ?");
        $stmtCheck->execute([$resetRow['user_id']]);
        $senhasAtualizadas = $stmtCheck->fetchAll(PDO::FETCH_COLUMN);
        
        echo "  - SELECT senha: " . count($senhasAtualizadas) . " linha(s)\n";
        
        $senhaConfirmada = $senhasAtualizadas !== [];
        foreach ($senhasAtualizadas as $senhaBanco) {
            if (!password_verify($novaSenha, $senhaBanco)) {
                $senhaConfirmada = false;
                break;
            }
        }
        
        if (!$senhaConfirmada) {
            throw new RuntimeException('Senha gravada não confere com a nova senha!');
        }
        
        echo "  - password_verify: ✓ OK\n";
        
        // Marcar token como usado
        $stmtToken = $db->prepare("
            UPDATE password_reset_tokens
            SET used_at = CURRENT_TIMESTAMP
            WHERE used_at IS NULL
              AND user_id = ?
        ");
        $stmtToken->execute([$resetRow['user_id']]);
        echo "  - UPDATE token: " . $stmtToken->rowCount() . " linha(s)\n";
        
        $db->commit();
        echo "\n✓ Transação committed com sucesso!\n\n";
        
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        echo "❌ ERRO NA TRANSAÇÃO: " . $e->getMessage() . "\n\n";
    }
    
    // 9. Verificar se a senha está de verdade no banco
    echo "=== Verificação Final ===\n";
    $stmt = $db->prepare("SELECT senha FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $senhaFinal = $stmt->fetchColumn();
    
    $verificaNovaHash = password_verify($novaSenha, $senhaFinal);
    
    echo "Senha no banco: " . substr($senhaFinal, 0, 30) . "...\n";
    echo "password_verify('{$novaSenha}', senha_banco): " . ($verificaNovaHash ? '✓ true' : '❌ false') . "\n\n";
    
    if ($verificaNovaHash) {
        echo "✅ SUCESSO: A senha foi de verdade atualizada no banco!\n";
        echo "   O problema pode estar em outro lugar (sessão, cookie, etc)\n";
    } else {
        echo "❌ PROBLEMA CONFIRMADO: A senha não foi atualizada no banco!\n";
        echo "   - Verificar permissões do usuário do banco\n";
        echo "   - Verificar se há constraint/trigger\n";
        echo "   - Verificar collation da coluna senha\n";
    }
    
    echo "\n=== FIM DO DEBUG ===\n";
    
} catch (Exception $e) {
    echo "❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo "Classe: " . get_class($e) . "\n";
    if (method_exists($e, 'errorInfo')) {
        $info = $e->errorInfo;
        echo "Error Info: " . print_r($info, true) . "\n";
    }
}
?>
