<?php
/**
 * Debug: Por que o token não é considerado válido?
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
$token = 'uKpPnJoL05jCmw4Sm_uSn78MZBWnK1Hi6foYIaf6BYM';
$tokenHash = hash('sha256', $token);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Debug - Por que token não é válido</title>
    <style>
        body { font-family: monospace; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        .step { margin: 15px 0; padding: 15px; border-left: 4px solid #ccc; }
        .step.success { border-left-color: #28a745; background: #e8f5e9; }
        .step.error { border-left-color: #dc3545; background: #ffebee; }
        .step.warning { border-left-color: #ffc107; background: #fff3cd; }
        h1, h2 { color: #333; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 Debug: Por que redefinir-senha.php diz token inválido?</h1>

    <h2>1️⃣ Verificação Básica</h2>
    <div class="step success">
        Token: <code><?php echo htmlspecialchars($token); ?></code><br>
        Hash SHA256: <code><?php echo substr($tokenHash, 0, 20); ?>...</code>
    </div>

    <h2>2️⃣ Query de Validação (EXATAMENTE como redefinir-senha.php usa)</h2>
    <?php
    try {
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
        $resetRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resetRow) {
            echo "<div class='step success'>";
            echo "✓ Query retornou resultado!<br>";
            echo "  - ID: " . htmlspecialchars($resetRow['id']) . "<br>";
            echo "  - User ID: " . htmlspecialchars($resetRow['user_id']) . "<br>";
            echo "  - Email: " . htmlspecialchars($resetRow['email']) . "<br>";
            echo "</div>";
        } else {
            echo "<div class='step error'>";
            echo "❌ Query NÃO retornou resultado!<br>";
            echo "Testando cada condição separadamente...";
            echo "</div>";

            // Testar cada condição
            echo "<h2>3️⃣ Testando Condições Separadamente</h2>";

            // 1. Token hash
            echo "<h3>Condição 1: token_hash = ?</h3>";
            $stmt1 = $db->prepare("SELECT id, token_hash, expires_at, used_at FROM password_reset_tokens WHERE token_hash = ?");
            $stmt1->execute([$tokenHash]);
            $row1 = $stmt1->fetch(PDO::FETCH_ASSOC);
            if ($row1) {
                echo "<div class='step success'>";
                echo "✓ Token hash ENCONTRADO<br>";
                echo "  - Expires at: " . $row1['expires_at'] . "<br>";
                echo "  - Used at: " . ($row1['used_at'] ?? 'NULL') . "<br>";
                echo "</div>";
            } else {
                echo "<div class='step error'>❌ Token hash NÃO ENCONTRADO</div>";
            }

            // 2. Used at
            if ($row1) {
                echo "<h3>Condição 2: used_at IS NULL</h3>";
                if ($row1['used_at'] === null) {
                    echo "<div class='step success'>✓ used_at é NULL</div>";
                } else {
                    echo "<div class='step error'>❌ used_at NÃO é NULL - é: " . htmlspecialchars($row1['used_at']) . "</div>";
                }

                // 3. Expiration
                echo "<h3>Condição 3: expires_at > CURRENT_TIMESTAMP</h3>";
                $stmt3 = $db->prepare("
                    SELECT 
                        expires_at,
                        CURRENT_TIMESTAMP as now,
                        CASE WHEN expires_at > CURRENT_TIMESTAMP THEN 'SIM' ELSE 'NÃO' END as valid
                    FROM password_reset_tokens 
                    WHERE id = ?
                ");
                $stmt3->execute([$row1['id']]);
                $row3 = $stmt3->fetch(PDO::FETCH_ASSOC);

                echo "<div class='step'>";
                echo "Expires at: <code>" . $row3['expires_at'] . "</code><br>";
                echo "CURRENT_TIMESTAMP: <code>" . $row3['now'] . "</code><br>";
                echo "Valid: <code>" . $row3['valid'] . "</code><br>";
                
                if ($row3['valid'] === 'SIM') {
                    echo "<div class='step success'>✓ Token ainda não expirou</div>";
                } else {
                    echo "<div class='step error'>❌ Token EXPIROU!</div>";
                }
                echo "</div>";
            }
        }
    } catch (Exception $e) {
        echo "<div class='step error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    ?>

    <h2>4️⃣ Conclusão</h2>
    <div class="step warning">
        <?php if ($resetRow): ?>
            ✓ Token deveria ser válido<br>
            Se redefinir-senha.php ainda assim rejeita, pode ser:<br>
            1. Cache do navegador (tente CTRL+Shift+Delete)<br>
            2. Problema de timezone (UTC vs America/Sao_Paulo)<br>
            3. Problema com a função buscarTokenReset()
        <?php else: ?>
            ❌ Token não é considerado válido<br>
            Verificar as condições acima para descobrir por quê
        <?php endif; ?>
    </div>

</div>

</body>
</html>
