<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/assinatura.php';

exigirAutenticacao();

$tituloPagina = 'Assinar';
$usuario      = usuarioAtual();
$userId       = $usuario['id'];

$dados        = getDadosAssinatura($userId);
$abacateCfg   = getAbacateConfig();

$urlMensal    = $abacateCfg['abacatepay_checkout_mensal'] ?? '';
$urlAnual     = $abacateCfg['abacatepay_checkout_anual']  ?? '';
$precoMensal  = $abacateCfg['plano_mensal_preco']         ?? PLANO_MENSAL_PRECO;
$precoAnual   = $abacateCfg['plano_anual_preco']          ?? PLANO_ANUAL_PRECO;

// Adicionar user_id como metadata nas URLs de checkout
$metaQuery    = '?metadata[user_id]=' . urlencode($userId);
if ($urlMensal) $urlMensal .= $metaQuery;
if ($urlAnual)  $urlAnual  .= $metaQuery;

// Mensalidade equivalente do plano anual
$mensalidadeAnual = number_format($precoAnual / 12, 2, ',', '.');
$precoMensalFmt   = number_format($precoMensal, 2, ',', '.');
$precoAnualFmt    = number_format($precoAnual,  2, ',', '.');

// Dias restantes e cor do banner de alerta
$diasRestantes = $dados['dias_restantes'] ?? 0;
$corAlerta     = corBannerTrial($diasRestantes);

$isAtivo       = $dados['assinante_ativo'] ?? false;
$isExpirado    = $dados['trial_expirado']  ?? false;

include __DIR__ . '/includes/layout/head.php';
?>

<div id="app-wrapper" style="display:flex; min-height:100vh;">
    <?php include __DIR__ . '/includes/layout/sidebar.php'; ?>

    <main id="main-content" style="flex:1; padding:28px 32px; overflow-y:auto; max-width:calc(100vw - 240px);">

        <!-- Cabeçalho -->
        <div style="margin-bottom:32px; text-align:center;">
            <h1 style="font-size:28px; font-weight:800; color:#f1f5f9; letter-spacing:-0.5px;">
                <?= $isAtivo ? 'Sua Assinatura' : 'Assine e Desbloqueie Tudo' ?>
            </h1>
            <p style="font-size:15px; color:#94a3b8; margin-top:6px;">
                <?php if ($isAtivo): ?>
                    Você é assinante ativo. Obrigado pelo suporte!
                <?php elseif ($isExpirado): ?>
                    Seu período de trial expirou. Assine para continuar criando roteiros.
                <?php else: ?>
                    <?= $diasRestantes ?> dia<?= $diasRestantes !== 1 ? 's' : '' ?> restantes no seu trial gratuito.
                <?php endif; ?>
            </p>
        </div>

        <!-- Banner de status ativo -->
        <?php if ($isAtivo): ?>
        <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); border-radius:12px; padding:20px 24px; margin-bottom:32px; max-width:500px; margin-left:auto; margin-right:auto; text-align:center;">
            <div style="font-size:32px; margin-bottom:8px;">✅</div>
            <div style="font-size:16px; font-weight:700; color:#34d399; margin-bottom:4px;">Assinatura Ativa</div>
            <div style="font-size:13px; color:#6b7280;">
                Plano: <strong style="color:#94a3b8;"><?= ucfirst($dados['plan'] ?? '') ?></strong>
                <?php if (!empty($dados['expires_at'])): ?>
                    · Renova em <strong style="color:#94a3b8;"><?= date('d/m/Y', strtotime($dados['expires_at'])) ?></strong>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!$isAtivo): ?>

        <!-- Aviso de trial expirado -->
        <?php if ($isExpirado): ?>
        <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:10px; padding:16px 20px; margin-bottom:28px; max-width:600px; margin-left:auto; margin-right:auto; text-align:center; font-size:14px; color:#fca5a5;">
            ⛔ Seu trial de 35 dias encerrou. Assine para criar novos roteiros — seus roteiros existentes continuam disponíveis para leitura.
        </div>
        <?php elseif ($diasRestantes <= 7): ?>
        <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:10px; padding:16px 20px; margin-bottom:28px; max-width:600px; margin-left:auto; margin-right:auto; text-align:center; font-size:14px; color:#fca5a5;">
            🔴 Apenas <strong><?= $diasRestantes ?> dia<?= $diasRestantes !== 1 ? 's' : '' ?></strong> restantes no trial. Assine agora para não perder o acesso.
        </div>
        <?php elseif ($diasRestantes <= 14): ?>
        <div style="background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.3); border-radius:10px; padding:16px 20px; margin-bottom:28px; max-width:600px; margin-left:auto; margin-right:auto; text-align:center; font-size:14px; color:#fbbf24;">
            ⚠️ <strong><?= $diasRestantes ?> dias</strong> restantes no trial.
        </div>
        <?php endif; ?>

        <!-- Cards de plano -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; max-width:700px; margin:0 auto 40px;">

            <!-- Plano Mensal -->
            <div class="card" style="padding:28px; text-align:center; border:1px solid #334155;">
                <div style="font-size:13px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:1px; margin-bottom:12px;">Mensal</div>
                <div style="font-size:42px; font-weight:800; color:#f1f5f9; line-height:1;">
                    R$<?= $precoMensalFmt ?>
                </div>
                <div style="font-size:13px; color:#6b7280; margin-top:4px; margin-bottom:24px;">por mês</div>

                <ul style="list-style:none; padding:0; margin:0 0 28px; text-align:left; font-size:14px; color:#94a3b8;">
                    <li style="margin-bottom:10px;">✓ Roteiros ilimitados</li>
                    <li style="margin-bottom:10px;">✓ Até 50 fontes de conhecimento</li>
                    <li style="margin-bottom:10px;">✓ IA aprende seu estilo</li>
                    <li style="margin-bottom:10px;">✓ Modo leitura offline</li>
                    <li>✓ Cancele quando quiser</li>
                </ul>

                <?php if ($urlMensal): ?>
                    <a href="<?= sanitizar($urlMensal) ?>" target="_blank" rel="noopener"
                       class="btn-primary" style="display:block; text-align:center; text-decoration:none; padding:12px;">
                        Assinar por R$<?= $precoMensalFmt ?>/mês
                    </a>
                <?php else: ?>
                    <button class="btn-primary" disabled style="display:block; width:100%; opacity:0.5; cursor:not-allowed;">
                        Em breve
                    </button>
                <?php endif; ?>
            </div>

            <!-- Plano Anual (destaque) -->
            <div class="card" style="padding:28px; text-align:center; border:2px solid #6366f1; position:relative;">
                <div style="position:absolute; top:-12px; left:50%; transform:translateX(-50%); background:#6366f1; color:#fff; font-size:11px; font-weight:700; padding:4px 14px; border-radius:20px; white-space:nowrap;">
                    MAIS POPULAR · ECONOMIZE R$<?= number_format(($precoMensal * 12) - $precoAnual, 0, ',', '.') ?>
                </div>
                <div style="font-size:13px; font-weight:600; color:#818cf8; text-transform:uppercase; letter-spacing:1px; margin-bottom:12px;">Anual</div>
                <div style="font-size:42px; font-weight:800; color:#f1f5f9; line-height:1;">
                    R$<?= $precoAnualFmt ?>
                </div>
                <div style="font-size:13px; color:#6b7280; margin-top:4px; margin-bottom:24px;">
                    por ano · <span style="color:#818cf8;">R$<?= $mensalidadeAnual ?>/mês</span>
                </div>

                <ul style="list-style:none; padding:0; margin:0 0 28px; text-align:left; font-size:14px; color:#94a3b8;">
                    <li style="margin-bottom:10px;">✓ Tudo do plano mensal</li>
                    <li style="margin-bottom:10px; color:#a5b4fc;">✓ ~1,8 meses grátis vs mensal</li>
                    <li style="margin-bottom:10px;">✓ Roteiros ilimitados</li>
                    <li style="margin-bottom:10px;">✓ Até 50 fontes de conhecimento</li>
                    <li>✓ Prioridade em novos recursos</li>
                </ul>

                <?php if ($urlAnual): ?>
                    <a href="<?= sanitizar($urlAnual) ?>" target="_blank" rel="noopener"
                       style="display:block; text-align:center; text-decoration:none; padding:12px; background:#6366f1; color:#fff; border-radius:8px; font-weight:600; font-size:14px;">
                        Assinar por R$<?= $precoAnualFmt ?>/ano
                    </a>
                <?php else: ?>
                    <button disabled style="display:block; width:100%; padding:12px; background:#6366f1; color:#fff; border-radius:8px; font-weight:600; font-size:14px; opacity:0.5; cursor:not-allowed; border:none;">
                        Em breve
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Garantia / Segurança -->
        <div style="text-align:center; max-width:500px; margin:0 auto 32px; font-size:13px; color:#6b7280;">
            <div style="margin-bottom:8px;">🔒 Pagamento seguro via Abacate Pay · PIX ou cartão de crédito</div>
            <div>Sem cobrança automática após o trial. Você escolhe quando assinar.</div>
        </div>

        <!-- FAQ rápido -->
        <div style="max-width:600px; margin:0 auto;">
            <h3 style="font-size:15px; font-weight:600; color:#e2e8f0; margin-bottom:16px;">Perguntas frequentes</h3>
            <div style="display:flex; flex-direction:column; gap:12px;">
                <?php foreach ([
                    ['Posso cancelar a qualquer momento?', 'Sim. Cancele quando quiser. Você mantém acesso até o final do período pago.'],
                    ['O que acontece quando o trial expira?', 'Seus roteiros existentes ficam disponíveis para leitura. Para criar novos, é necessário assinar.'],
                    ['Posso mudar de plano mensal para anual?', 'Sim. Basta assinar o plano anual — o valor é cobrado separadamente e você pode cancelar o mensal.'],
                    ['Meus dados ficam seguros?', 'Sim. Cada usuário tem sua base de conhecimento isolada. Nenhum dado é compartilhado entre contas.'],
                ] as [$pergunta, $resposta]): ?>
                <div class="card" style="padding:16px;">
                    <div style="font-size:14px; font-weight:600; color:#e2e8f0; margin-bottom:6px;"><?= $pergunta ?></div>
                    <div style="font-size:13px; color:#6b7280;"><?= $resposta ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php endif; // !$isAtivo ?>

    </main>
</div>

<?php include __DIR__ . '/includes/layout/footer.php'; ?>
