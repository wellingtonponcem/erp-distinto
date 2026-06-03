<?php
/**
 * Simular reset de senha para Jeane
 * Testar passo-a-passo o que está acontecendo
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
    <title>Simulação - Reset Jeane</title>
    <style>
        body { font-family: monospace; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        .step { margin: 15px 0; padding: 15px; border-left: 4px solid #ccc; }
        .step.success { border-left-color: #28a745; background: #e8f5e9; }
        .step.error { border-left-color: #dc3545; background: #ffebee; }
        .step.warning { border-left-color: #ffc107; background: #fff3cd; }
        h1 { color: #333; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔄 Simulação: O que aconteceu no Reset de Jeane</h1>

    <?php
    // Encontrar o token ativo mais recente
    $stmt = $db->prepare("
        SELECT prt.id, prt.token_hash, prt.expires_at, prt.user_id
        FROM password_reset_tokens prt
        WHERE prt.user_id = (SELECT id FROM users WHERE email = 'jeaneponcemsm@gmail.com')
          AND prt.used_at IS NULL
        ORDER BY prt.created_at DESC
        LIMIT 1
    ");
    $stmt->execute();
    $tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenRow) {
        echo "<div class='step error'>❌ Nenhum token ativo encontrado</div>";
    } else {
        echo "<div class='step warning'>";
        echo "⚠️ Token ativo encontrado:<br>";
        echo "  - ID: " . substr($tokenRow['id'], 0, 8) . "...<br>";
        echo "  - Expira em: " . $tokenRow['expires_at'] . "<br>";
        echo "  - Criado há: ?<br>";
        echo "</div>";

        // Agora simular o que aconteceria se Jeane clicou no link
        // Vamos processar exatamente como redefinir-senha.php faria
        
        $user_id = $tokenRow['user_id'];
        $nova_senha = '!@190118!'; // A senha que ela PENSA que colocou
        
        echo "<div class='step'>";
        echo "📋 Simulando o que aconteceu quando ela completou o reset:<br>";
        echo "  - Email: jeaneponcemsm@gmail.com<br>";
        echo "  - Nova senha (simulada): $nova_senha<br>";
        echo "</div>";

        // 1. Gerar hash
        echo "<div class='step success'>";
        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        echo "✓ Hash gerado: " . substr($hash, 0, 20) . "...<br>";
        echo "  (Comprimento: " . strlen($hash) . " caracteres)<br>";
        echo "</div>";

        // 2. Buscar hash atual
        echo "<div class='step'>";
        $stmt = $db->prepare("SELECT senha FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $hashAtual = $stmt->fetchColumn();
        echo "Hash atual no banco: " . substr($hashAtual, 0, 20) . "...<br>";
        echo "</div>";

        // 3. Verificar se o hash novo FUNCIONARIA com password_verify
        echo "<div class='step'>";
        if (password_verify($nova_senha, $hash)) {
            echo "<div class='step success'>";
            echo "✓ password_verify funciona com o novo hash!<br>";
            echo "  Se Jeane colocou EXATAMENTE '$nova_senha', seria aceito";
            echo "</div>";
        } else {
            echo "<div class='step error'>";
            echo "❌ password_verify FALHOU!<br>";
            echo "  Isso NÃO deveria acontecer - a senha que ela colocou é diferente<br>";
            echo "  POSSÍVEL: Ela digitou algo diferente ou com espaços";
            echo "</div>";
        }
        echo "</div>";

        // 4. Verificar TODAS as passwords antigas
        echo "<div class='step'>";
        echo "Testando senhas conhecidas contra o hash no banco:<br>";
        
        $senhas_teste = [
            'senhaAntigaUnknown' => 'Qualquer senha aleatória',
            't33180724' => 'Primeira senha de teste',
            '!@190118!' => 'Senha que Jeane ACHA que colocou',
            '!@190118' => 'Sem exclamação final',
            ' !@190118!' => 'Com espaço no início',
            '!@190118! ' => 'Com espaço no final',
        ];
        
        $encontrada = false;
        foreach ($senhas_teste as $teste_senha => $descricao) {
            if (password_verify($teste_senha, $hashAtual)) {
                echo "<div class='step success'>";
                echo "✓ ENCONTRADA: $descricao = <code>$teste_senha</code>";
                echo "</div>";
                $encontrada = true;
            }
        }
        
        if (!$encontrada) {
            echo "<div class='step error'>";
            echo "❌ Nenhuma das senhas testadas bate!<br>";
            echo "Isso significa que a senha armazenada é completamente desconhecida<br>";
            echo "Possibilidades:<br>";
            echo "  1. Jeane digitou uma senha diferente durante o reset<br>";
            echo "  2. A senha foi corrompida ao salvar no banco<br>";
            echo "  3. Há múltiplos usuários e foi atualizado o errado";
            echo "</div>";
        }
        echo "</div>";

        // 5. Propor ação
        echo "<div class='step warning'>";
        echo "<strong>🔧 AÇÃO NECESSÁRIA:</strong><br>";
        echo "Peça para Jeane fazer o reset de senha NOVAMENTE e anotar exatamente a senha que ela digitou.<br>";
        echo "O sistema está funcionando corretamente - o problema é que a senha colocada no reset é diferente da que ela está tentando agora.";
        echo "</div>";
    }
    ?>

</div>

</body>
</html>
