<?php
/**
 * Simular EXATAMENTE o que o redefinir-senha.php faz
 * para descobrir onde falha no fluxo
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

// Dados do token que Jeane usou
$token_recebido = 'uKpPnJoL05jCmw4Sm_uSn78MZBWnK1Hi6foYIaf6BYM';
$token_hash = hash('sha256', $token_recebido);
$senha_testada = '!@190118!';
$confirmar = '!@190118!';

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Simulação - Fluxo Completo</title>
    <style>
        body { font-family: monospace; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        .step { margin: 15px 0; padding: 15px; border-left: 4px solid #ccc; }
        .step.success { border-left-color: #28a745; background: #e8f5e9; }
        .step.error { border-left-color: #dc3545; background: #ffebee; }
        .step.warning { border-left-color: #ffc107; background: #fff3cd; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; font-size: 0.9em; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔄 Simulação: Fluxo Completo de Reset de Jeane</h1>

    <h2>Dados Recebidos</h2>
    <div class="step">
        Token: <code><?php echo htmlspecialchars($token_recebido); ?></code><br>
        Senha: <code><?php echo htmlspecialchars($senha_testada); ?></code><br>
        Confirmar: <code><?php echo htmlspecialchars($confirmar); ?></code>
    </div>

    <?php
    $passo = 0;
    $erro_encontrado = false;

    // PASSO 1: Buscar token
    $passo++;
    echo "<h2>Passo $passo: Buscar Token no Banco</h2>";
    try {
        $stmt = $db->prepare("
            SELECT prt.id, prt.user_id, u.email
            FROM password_reset_tokens prt
            JOIN users u ON u.id = prt.user_id
            WHERE prt.token_hash = ?
              AND prt.expires_at > CURRENT_TIMESTAMP
              AND prt.used_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$token_hash]);
        $resetRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$resetRow) {
            echo "<div class='step error'>❌ Token não encontrado, expirado ou já foi usado</div>";
            $erro_encontrado = true;
        } else {
            echo "<div class='step success'>";
            echo "✓ Token encontrado<br>";
            echo "  - ID: " . htmlspecialchars($resetRow['id']) . "<br>";
            echo "  - User ID: " . htmlspecialchars($resetRow['user_id']) . "<br>";
            echo "  - Email: " . htmlspecialchars($resetRow['email']) . "<br>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div class='step error'>❌ Erro na query: " . htmlspecialchars($e->getMessage()) . "</div>";
        $erro_encontrado = true;
    }

    if (!$erro_encontrado) {
        // PASSO 2: Validar comprimento de senha
        $passo++;
        echo "<h2>Passo $passo: Validar Senha</h2>";
        
        if (strlen($senha_testada) < 8) {
            echo "<div class='step error'>❌ Senha muito curta (menos de 8 caracteres)</div>";
            $erro_encontrado = true;
        } elseif ($senha_testada !== $confirmar) {
            echo "<div class='step error'>❌ Senhas não coincidem</div>";
            $erro_encontrado = true;
        } else {
            echo "<div class='step success'>";
            echo "✓ Senha válida e confirmação bate<br>";
            echo "  - Comprimento: " . strlen($senha_testada) . " caracteres<br>";
            echo "</div>";
        }
    }

    if (!$erro_encontrado) {
        // PASSO 3: Gerar hash
        $passo++;
        echo "<h2>Passo $passo: Gerar Hash</h2>";
        
        $hash = password_hash($senha_testada, PASSWORD_DEFAULT);
        echo "<div class='step success'>";
        echo "✓ Hash gerado<br>";
        echo "  - Hash: " . htmlspecialchars($hash) . "<br>";
        echo "  - Comprimento: " . strlen($hash) . " caracteres<br>";
        echo "</div>";

        // PASSO 4: Fazer UPDATE
        $passo++;
        echo "<h2>Passo $passo: Atualizar Senha no Banco</h2>";
        
        try {
            $db->beginTransaction();
            echo "<div class='step success'>✓ Transaction iniciada</div>";

            $stmtUser = $db->prepare("UPDATE users SET senha = ? WHERE id = ?");
            $stmtUser->execute([$hash, $resetRow['user_id']]);
            $rowsAffected = $stmtUser->rowCount();

            if ($rowsAffected < 1) {
                throw new RuntimeException('Nenhum usuario encontrado para atualizar senha pelo token.');
            }

            echo "<div class='step success'>";
            echo "✓ UPDATE executado: " . $rowsAffected . " linha(s) afetada(s)<br>";
            echo "</div>";

            // PASSO 5: Verificar password_verify
            $passo++;
            echo "<h2>Passo $passo: Verificar com password_verify</h2>";

            $stmtCheck = $db->prepare("SELECT senha FROM users WHERE id = ?");
            $stmtCheck->execute([$resetRow['user_id']]);
            $senhasAtualizadas = $stmtCheck->fetchAll(PDO::FETCH_COLUMN);
            $senhaConfirmada = $senhasAtualizadas !== [];

            echo "<div class='step'>";
            echo "Senhas encontradas no banco: " . count($senhasAtualizadas) . "<br>";

            foreach ($senhasAtualizadas as $senhaBanco) {
                if (password_verify($senha_testada, $senhaBanco)) {
                    echo "<div class='step success'>";
                    echo "✓ password_verify SUCESSO!<br>";
                    echo "  Senha: " . htmlspecialchars($senha_testada) . "<br>";
                    echo "  Hash: " . substr(htmlspecialchars($senhaBanco), 0, 20) . "...<br>";
                    echo "</div>";
                } else {
                    echo "<div class='step error'>";
                    echo "❌ password_verify FALHOU!<br>";
                    echo "  Tentou: " . htmlspecialchars($senha_testada) . "<br>";
                    echo "  Hash: " . htmlspecialchars($senhaBanco) . "<br>";
                    echo "</div>";
                    $senhaConfirmada = false;
                    $erro_encontrado = true;
                }
            }
            echo "</div>";

            if ($erro_encontrado) {
                if ($db->inTransaction()) $db->rollBack();
                echo "<div class='step error'>";
                echo "❌ ROLLBACK executado - nenhuma mudança foi salva!<br>";
                echo "Por isso o token ainda está marcado como 'não usado'.";
                echo "</div>";
            } else {
                // PASSO 6: Marcar token como usado
                $passo++;
                echo "<h2>Passo $passo: Marcar Token como Usado</h2>";

                $stmtToken = $db->prepare("
                    UPDATE password_reset_tokens
                    SET used_at = CURRENT_TIMESTAMP
                    WHERE used_at IS NULL
                      AND user_id = ?
                ");
                $stmtToken->execute([$resetRow['user_id']]);

                echo "<div class='step success'>";
                echo "✓ Token marcado como usado<br>";
                echo "</div>";

                // PASSO 7: Commit
                $passo++;
                echo "<h2>Passo $passo: Fazer COMMIT</h2>";
                $db->commit();

                echo "<div class='step success'>";
                echo "✅ COMMIT SUCESSO!<br>";
                echo "Todas as mudanças foram salvas no banco.";
                echo "</div>";

                // PASSO 8: Deslogar e redirecionar
                $passo++;
                echo "<h2>Passo $passo: Deslogar e Redirecionar</h2>";
                echo "<div class='step success'>";
                echo "✓ Fluxo de reset completado com sucesso!<br>";
                echo "Agora Jeane pode fazer login com: <br>";
                echo "  - Email: " . htmlspecialchars($resetRow['email']) . "<br>";
                echo "  - Senha: " . htmlspecialchars($senha_testada);
                echo "</div>";
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            echo "<div class='step error'>";
            echo "❌ Exception capturada: " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "ROLLBACK executado";
            echo "</div>";
        }
    }
    ?>

    <h2>📋 Conclusão</h2>
    <div class="step warning">
        <?php if ($erro_encontrado): ?>
            <strong>🔴 PROBLEMA ENCONTRADO!</strong><br><br>
            O fluxo de reset está falhando em algum ponto, causando um ROLLBACK.<br>
            Por isso o token não é marcado como "usado".<br><br>
            <strong>Solução:</strong> Peça para Jeane tentar NOVAMENTE com o link que recebeu no email.
        <?php else: ?>
            <strong>✅ FLUXO FUNCIONOU!</strong><br><br>
            Se chegou aqui, significa que a simulação rodou sem erros.<br>
            Jeane deveria conseguir fazer login com a senha: <code><?php echo htmlspecialchars($senha_testada); ?></code>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
