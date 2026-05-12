<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();

$usuario = usuarioAtual();
$tituloPagina = 'Meu Perfil';

// Processamento de formulário
$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    
    try {
        $db = Database::get();
        
        // Validar senha atual se quiser mudar algo sensível ou apenas mudar a senha
        $stmt = $db->prepare("SELECT senha FROM users WHERE id = ?");
        $stmt->execute([$usuario['id']]);
        $userDb = $stmt->fetch();
        
        if (!password_verify($senhaAtual, $userDb['senha'])) {
            $erro = 'A senha atual está incorreta.';
        } else {
            if ($novaSenha) {
                if (strlen($novaSenha) < 8) {
                    $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
                } else {
                    $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET nome = ?, email = ?, senha = ? WHERE id = ?");
                    $stmt->execute([$nome, $email, $hash, $usuario['id']]);
                    $mensagem = 'Perfil e senha atualizados com sucesso!';
                }
            } else {
                $stmt = $db->prepare("UPDATE users SET nome = ?, email = ? WHERE id = ?");
                $stmt->execute([$nome, $email, $usuario['id']]);
                $mensagem = 'Perfil atualizado com sucesso!';
            }
            
            if (!$erro) {
                // Atualizar sessão
                $_SESSION['user_nome'] = $nome;
                $_SESSION['user_email'] = $email;
                $usuario = usuarioAtual();
            }
        }
    } catch (Exception $e) {
        $erro = 'Erro ao atualizar: ' . $e->getMessage();
    }
}

include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper" style="display:flex; min-height:100vh;">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" style="flex:1; padding:40px; background: #0a0a0a; color: #fff;">
        <div style="max-width: 600px; margin: 0 auto;">
            
            <div style="margin-bottom: 40px;">
                <h1 style="font-family: var(--display); font-size: 42px; line-height: 1; margin-bottom: 8px;">Meu <em>Perfil</em></h1>
                <p style="color: var(--muted); font-size: 15px;">Gerencie suas informações de acesso e segurança.</p>
            </div>

            <?php if ($mensagem): ?>
                <div style="background: rgba(232,255,71,0.1); border: 1px solid var(--accent); color: var(--accent); padding: 16px; border-radius: 12px; margin-bottom: 24px; font-size: 14px;">
                    <?= $mensagem ?>
                </div>
            <?php endif; ?>

            <?php if ($erro): ?>
                <div style="background: rgba(255,71,71,0.1); border: 1px solid #ff4747; color: #ff4747; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-size: 14px;">
                    <?= $erro ?>
                </div>
            <?php endif; ?>

            <form method="POST" style="background: var(--surface); border: 1px solid var(--border); padding: 32px; border-radius: 24px; display: flex; flex-direction: column; gap: 24px;">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Nome Completo</label>
                        <input type="text" name="nome" value="<?= sanitizar($usuario['nome']) ?>" required
                               style="width: 100%; background: var(--surface2); border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 8px; font-size: 15px;">
                    </div>

                    <div class="form-group">
                        <label style="display: block; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">E-mail</label>
                        <input type="email" name="email" value="<?= sanitizar($usuario['email']) ?>" required
                               style="width: 100%; background: var(--surface2); border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 8px; font-size: 15px;">
                    </div>
                </div>

                <hr style="border: none; border-top: 1px solid var(--border); margin: 8px 0;">

                <div>
                    <h3 style="font-family: var(--serif); font-style: italic; font-size: 20px; margin-bottom: 16px; color: var(--accent);">Alterar Senha</h3>
                    
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Senha Atual <span style="color: var(--accent2);">*</span></label>
                        <input type="password" name="senha_atual" required placeholder="Confirme sua senha atual"
                               style="width: 100%; background: var(--surface2); border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 8px; font-size: 15px;">
                    </div>

                    <div class="form-group">
                        <label style="display: block; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Nova Senha <span style="color: var(--muted);">(deixe em branco para não alterar)</span></label>
                        <input type="password" name="nova_senha" placeholder="Mínimo 8 caracteres"
                               style="width: 100%; background: var(--surface2); border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 8px; font-size: 15px;">
                    </div>
                </div>

                <button type="submit" style="background: var(--accent); color: #000; border: none; padding: 16px; border-radius: 100px; font-weight: 700; cursor: pointer; font-size: 15px; transition: transform 0.2s;">
                    Salvar Alterações
                </button>

            </form>

        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
