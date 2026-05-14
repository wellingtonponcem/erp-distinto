<?php
require_once __DIR__ . '/../sistema/config/env.php';
require_once __DIR__ . '/../sistema/config/auth.php';
require_once __DIR__ . '/../sistema/config/database.php';
require_once __DIR__ . '/../sistema/includes/helpers.php';
require_once __DIR__ . '/../sistema/includes/assinatura.php';

exigirAutenticacao();

$db      = Database::get();
$usuario = usuarioAtual();
$userId  = $usuario['id'];

$dadosSub  = getDadosAssinatura($userId);
$subStatus = $dadosSub['status'] ?? 'trial';
$isAtivo   = $dadosSub['assinante_ativo'] ?? false;

// Estatísticas do usuário
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM roteiros WHERE user_id = ?"); $stmt->execute([$userId]); $totalRoteiros = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COUNT(*) FROM roteiros WHERE user_id = ? AND status = 'postado'"); $stmt->execute([$userId]); $totalPostados = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COUNT(*) FROM roteiros_conhecimento WHERE user_id = ? AND ativo = TRUE"); $stmt->execute([$userId]); $totalConhec = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COALESCE(AVG(score),0) FROM roteiros WHERE user_id = ? AND score > 0"); $stmt->execute([$userId]); $scoreMedia = round((float)$stmt->fetchColumn(), 1);
    $stmt = $db->prepare("SELECT MIN(created_at) FROM roteiros WHERE user_id = ?"); $stmt->execute([$userId]); $primeiroRoteiro = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalRoteiros = $totalPostados = $totalConhec = 0;
    $scoreMedia = 0;
    $primeiroRoteiro = null;
}

// Processamento do formulário
$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome        = trim($_POST['nome']        ?? '');
    $email       = trim($_POST['email']       ?? '');
    $senhaAtual  = $_POST['senha_atual']  ?? '';
    $novaSenha   = $_POST['nova_senha']   ?? '';

    try {
        $stmt = $db->prepare("SELECT senha FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userDb = $stmt->fetch();

        if (!password_verify($senhaAtual, $userDb['senha'])) {
            $erro = 'A senha atual está incorreta.';
        } else {
            if ($novaSenha) {
                if (strlen($novaSenha) < 8) {
                    $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
                } else {
                    $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
                    $db->prepare("UPDATE users SET nome = ?, email = ?, senha = ? WHERE id = ?")
                       ->execute([$nome, $email, $hash, $userId]);
                    $mensagem = 'Perfil e senha atualizados!';
                }
            } else {
                $db->prepare("UPDATE users SET nome = ?, email = ? WHERE id = ?")
                   ->execute([$nome, $email, $userId]);
                $mensagem = 'Perfil atualizado!';
            }

            if (!$erro) {
                $_SESSION['user_nome']  = $nome;
                $_SESSION['user_email'] = $email;
                $usuario = usuarioAtual();
            }
        }
    } catch (Exception $e) {
        $erro = 'Erro: ' . $e->getMessage();
    }
}

$inicialNome = strtoupper(mb_substr($usuario['nome'], 0, 1));
$membro_desde = $dadosSub['trial_started_at'] ? date('M/Y', strtotime($dadosSub['trial_started_at'])) : '—';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil — Roteiros</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --bg: #0a0a0a; --surface: #111; --surface2: #181818;
            --border: rgba(255,255,255,0.07); --accent: #E8FF47; --accent2: #FF4747;
            --text: #F0EDE6; --muted: #888;
            --serif: 'Instrument Serif', Georgia, serif;
            --sans: 'DM Sans', sans-serif;
            --display: 'Bebas Neue', sans-serif;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); color: var(--text); font-family: var(--sans); min-height: 100vh; }

        .field-input {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 13px 16px;
            border-radius: 10px;
            font-size: 15px;
            font-family: var(--sans);
            transition: border-color 0.2s;
            outline: none;
        }
        .field-input:focus { border-color: rgba(232,255,71,0.4); }
        .field-input::placeholder { color: var(--muted); }

        .field-label {
            display: block;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px 16px;
            text-align: center;
        }
        .stat-val {
            font-family: var(--display);
            font-size: 38px;
            color: var(--accent);
            line-height: 1;
            margin-bottom: 6px;
        }
        .stat-label {
            font-size: 11px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .section-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
        }

        .divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 24px 0;
        }
    </style>
</head>
<body x-data="{}">
<div style="display:flex; min-height:100vh;">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main style="flex:1; overflow-y:auto; padding:2.5rem 2rem 5rem; max-width:calc(100vw - 52px);">
        <div style="max-width:680px; margin:0 auto;">

            <!-- Cabeçalho -->
            <div style="margin-bottom:2.5rem;">
                <div style="font-size:10px; letter-spacing:0.2em; text-transform:uppercase; color:var(--accent); margin-bottom:10px; font-weight:600;">Conta</div>
                <h1 style="font-family:var(--display); font-size:clamp(40px,6vw,60px); line-height:0.95; margin-bottom:10px;">
                    Meu <em style="font-family:var(--serif); font-style:italic; color:var(--accent);">Perfil</em>
                </h1>
                <p style="color:var(--muted); font-size:15px;">Informações de conta e segurança.</p>
            </div>

            <!-- Avatar + status -->
            <div style="display:flex; align-items:center; gap:20px; margin-bottom:2rem; background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:24px;">
                <div style="
                    width:70px; height:70px; border-radius:18px; flex-shrink:0;
                    background:var(--accent); color:#0a0a0a;
                    font-family:var(--display); font-size:32px;
                    display:flex; align-items:center; justify-content:center;
                    letter-spacing:0.05em;">
                    <?= $inicialNome ?>
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:20px; font-weight:700; color:var(--text); margin-bottom:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <?= sanitizar($usuario['nome']) ?>
                    </div>
                    <div style="font-size:13px; color:var(--muted); margin-bottom:8px;">
                        <?= sanitizar($usuario['email']) ?>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <?php if ($isAtivo): ?>
                        <span style="background:rgba(52,211,153,0.1); border:1px solid rgba(52,211,153,0.3); color:#34d399; font-size:11px; font-weight:700; padding:3px 10px; border-radius:100px; letter-spacing:0.05em;">
                            ✦ ASSINANTE PRO
                        </span>
                        <?php elseif ($subStatus === 'trial'): ?>
                        <span style="background:rgba(232,255,71,0.08); border:1px solid rgba(232,255,71,0.2); color:var(--accent); font-size:11px; font-weight:700; padding:3px 10px; border-radius:100px; letter-spacing:0.05em;">
                            TRIAL · <?= $dadosSub['dias_restantes'] ?> dias restantes
                        </span>
                        <?php else: ?>
                        <span style="background:rgba(255,71,71,0.1); border:1px solid rgba(255,71,71,0.3); color:#ff4747; font-size:11px; font-weight:700; padding:3px 10px; border-radius:100px;">
                            ACESSO EXPIRADO
                        </span>
                        <?php endif; ?>
                        <span style="font-size:11px; color:var(--muted);">Membro desde <?= $membro_desde ?></span>
                    </div>
                </div>
                <?php if (!$isAtivo): ?>
                <a href="<?= raizUrl('/assinar.php') ?>"
                   style="display:inline-block; background:var(--accent); color:#0a0a0a; padding:10px 18px; border-radius:100px; font-size:13px; font-weight:700; text-decoration:none; white-space:nowrap; flex-shrink:0;">
                    Ver planos
                </a>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:2rem;">
                <div class="stat-card">
                    <div class="stat-val"><?= $totalRoteiros ?></div>
                    <div class="stat-label">Roteiros</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val"><?= $totalPostados ?></div>
                    <div class="stat-label">Postados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val"><?= $totalConhec ?></div>
                    <div class="stat-label">Fontes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val"><?= $scoreMedia > 0 ? $scoreMedia : '—' ?></div>
                    <div class="stat-label">Score Médio</div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($mensagem): ?>
            <div style="background:rgba(232,255,71,0.08); border:1px solid rgba(232,255,71,0.25); color:var(--accent); padding:14px 18px; border-radius:12px; margin-bottom:20px; font-size:14px; display:flex; align-items:center; gap:10px;">
                <i class="fa-solid fa-check-circle"></i> <?= sanitizar($mensagem) ?>
            </div>
            <?php endif; ?>
            <?php if ($erro): ?>
            <div style="background:rgba(255,71,71,0.08); border:1px solid rgba(255,71,71,0.25); color:#ff7070; padding:14px 18px; border-radius:12px; margin-bottom:20px; font-size:14px; display:flex; align-items:center; gap:10px;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= sanitizar($erro) ?>
            </div>
            <?php endif; ?>

            <!-- Formulário -->
            <div class="section-card">
                <h2 style="font-family:var(--serif); font-style:italic; font-size:22px; color:var(--text); margin-bottom:24px;">
                    Editar informações
                </h2>

                <form method="POST" x-data="{ showPass: false }">

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
                        <div>
                            <label class="field-label">Nome Completo</label>
                            <input class="field-input" type="text" name="nome"
                                   value="<?= sanitizar($usuario['nome']) ?>" required>
                        </div>
                        <div>
                            <label class="field-label">E-mail</label>
                            <input class="field-input" type="email" name="email"
                                   value="<?= sanitizar($usuario['email']) ?>" required>
                        </div>
                    </div>

                    <hr class="divider">

                    <!-- Senha -->
                    <div style="margin-bottom:16px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                            <h3 style="font-size:14px; font-weight:600; color:var(--text);">Alterar senha</h3>
                            <button type="button" @click="showPass = !showPass"
                                    style="font-size:12px; color:var(--muted); background:none; border:none; cursor:pointer; text-decoration:underline;">
                                <span x-text="showPass ? 'Cancelar' : 'Alterar senha'"></span>
                            </button>
                        </div>

                        <div x-show="showPass" x-collapse style="display:flex; flex-direction:column; gap:14px;">
                            <div>
                                <label class="field-label">Senha Atual <span style="color:var(--accent2);">*</span></label>
                                <input class="field-input" type="password" name="senha_atual"
                                       :required="showPass" placeholder="Confirme sua senha atual">
                            </div>
                            <div>
                                <label class="field-label">Nova Senha</label>
                                <input class="field-input" type="password" name="nova_senha"
                                       placeholder="Mínimo 8 caracteres">
                            </div>
                        </div>

                        <div x-show="!showPass">
                            <!-- Senha oculta obrigatória mesmo quando não altera -->
                            <label class="field-label">Confirme sua senha atual para salvar</label>
                            <input class="field-input" type="password" name="senha_atual"
                                   required placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" style="
                        width:100%; background:var(--accent); color:#0a0a0a;
                        border:none; padding:16px; border-radius:100px;
                        font-weight:800; font-size:15px; cursor:pointer;
                        transition:transform 0.2s; text-transform:uppercase; letter-spacing:0.05em;
                        margin-top:8px;">
                        Salvar alterações
                    </button>

                </form>
            </div>

            <!-- Zona de perigo -->
            <div style="margin-top:24px; padding:20px; border:1px solid rgba(255,71,71,0.15); border-radius:16px;">
                <h3 style="font-size:14px; font-weight:600; color:rgba(255,71,71,0.7); margin-bottom:6px;">Zona de perigo</h3>
                <p style="font-size:13px; color:var(--muted); margin-bottom:14px;">Encerrar sessão em todos os dispositivos.</p>
                <a href="<?= raizUrl('/api/auth/logout.php') ?>"
                   style="font-size:13px; color:#ff4747; text-decoration:none; font-weight:600; border:1px solid rgba(255,71,71,0.3); padding:8px 16px; border-radius:8px; display:inline-block; transition:all 0.2s;"
                   onmouseover="this.style.background='rgba(255,71,71,0.08)'"
                   onmouseout="this.style.background='transparent'">
                    <i class="fa-solid fa-right-from-bracket" style="margin-right:6px;"></i> Sair da conta
                </a>
            </div>

        </div>
    </main>
</div>
</body>
</html>
