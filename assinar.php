<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/assinatura.php';

exigirAutenticacao();

$usuario   = usuarioAtual();
$userId    = $usuario['id'];
$dados     = getDadosAssinatura($userId);
$subStatus = $dados['status'] ?? 'trial';

$mpCfg       = getMercadoPagoConfig();
$temMP       = !empty($mpCfg['mercadopago_access_token']);
$precoMensal = (float)($mpCfg['plano_mensal_preco'] ?? PLANO_MENSAL_PRECO);
$precoAnual  = (float)($mpCfg['plano_anual_preco']  ?? PLANO_ANUAL_PRECO);

$precoMensalFmt   = number_format($precoMensal, 2, ',', '.');
$precoAnualFmt    = number_format($precoAnual,  2, ',', '.');
$mensalidadeAnual = number_format($precoAnual / 12, 2, ',', '.');
$economiaAnual    = number_format(($precoMensal * 12) - $precoAnual, 0, ',', '.');

// Status de retorno do Mercado Pago
$statusMP = $_GET['status'] ?? '';
$erroMP   = $_GET['erro']   ?? '';

$isAtivo    = $dados['assinante_ativo'] ?? false;
$isExpirado = $dados['trial_expirado']  ?? false;
$diasRest   = $dados['dias_restantes']  ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinatura — Roteiros</title>
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

        /* Cards de plano */
        .plan-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 36px 28px;
            position: relative;
            transition: border-color 0.2s, transform 0.2s;
            display: flex;
            flex-direction: column;
        }
        .plan-card:hover { border-color: rgba(232,255,71,0.15); transform: translateY(-2px); }
        .plan-card.featured {
            border-color: var(--accent);
            background: rgba(232,255,71,0.03);
        }
        .plan-card.featured:hover { border-color: var(--accent); }

        .plan-badge {
            position: absolute;
            top: -13px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--accent);
            color: #0a0a0a;
            font-size: 10px;
            font-weight: 800;
            padding: 4px 16px;
            border-radius: 100px;
            white-space: nowrap;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .plan-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 14px;
        }
        .plan-card.featured .plan-label { color: var(--accent); }

        .plan-price {
            font-family: var(--display);
            font-size: 58px;
            line-height: 1;
            color: var(--text);
            margin-bottom: 4px;
        }

        .plan-per {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 28px;
        }
        .plan-card.featured .plan-per span { color: var(--accent); }

        .plan-features {
            list-style: none;
            flex: 1;
            margin-bottom: 28px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .plan-features li {
            font-size: 14px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }
        .plan-features li:last-child { border: none; padding-bottom: 0; }
        .plan-features li .check {
            color: var(--accent);
            font-size: 12px;
            flex-shrink: 0;
        }
        .plan-features li.highlight { color: var(--text); font-weight: 500; }

        .btn-plan-mensal {
            display: block;
            text-align: center;
            padding: 15px;
            border-radius: 100px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid var(--border);
            color: var(--text);
            background: transparent;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-plan-mensal:hover { border-color: var(--accent); color: var(--accent); }

        .btn-plan-anual {
            display: block;
            text-align: center;
            padding: 15px;
            border-radius: 100px;
            font-size: 15px;
            font-weight: 800;
            text-decoration: none;
            background: var(--accent);
            color: #0a0a0a;
            cursor: pointer;
            transition: transform 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .btn-plan-anual:hover { transform: translateY(-2px); }

        /* FAQ accordion */
        .faq-item {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
        }
        .faq-q {
            width: 100%; text-align: left; background: none; border: none;
            color: var(--text); font-size: 14px; font-weight: 600; font-family: var(--sans);
            padding: 18px 20px; cursor: pointer;
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
        }
        .faq-a { font-size: 13px; color: var(--muted); padding: 0 20px 16px; line-height: 1.6; }
    </style>
</head>
<body x-data="{ faq: null }">
<div style="display:flex; min-height:100vh;">

    <?php include __DIR__ . '/roteiros/includes/sidebar.php'; ?>

    <main style="flex:1; overflow-y:auto; padding:2.5rem 2rem 5rem; max-width:calc(100vw - 52px);">
        <div style="max-width:720px; margin:0 auto;">

            <!-- Cabeçalho -->
            <div style="margin-bottom:2.5rem; text-align:center;">
                <div style="font-size:10px; letter-spacing:0.2em; text-transform:uppercase; color:var(--accent); margin-bottom:12px; font-weight:600;">Conta</div>
                <h1 style="font-family:var(--display); font-size:clamp(40px,6vw,64px); line-height:0.95; margin-bottom:14px;">
                    <?= $isAtivo ? 'Sua <em style="font-family:var(--serif); font-style:italic; color:var(--accent);">Assinatura</em>' : 'Assine e <em style="font-family:var(--serif); font-style:italic; color:var(--accent);">Desbloqueie</em> tudo' ?>
                </h1>
                <p style="font-size:15px; color:var(--muted);">
                    <?php if ($isAtivo): ?>
                        Você é assinante ativo — roteiros ilimitados, sem restrições.
                    <?php elseif ($isExpirado): ?>
                        Seu trial encerrou. Assine para criar novos roteiros.
                    <?php else: ?>
                        <?= $diasRest ?> dia<?= $diasRest !== 1 ? 's' : '' ?> restantes no trial gratuito.
                    <?php endif; ?>
                </p>
            </div>

            <!-- Status ativo -->
            <?php if ($isAtivo): ?>
            <div style="background:rgba(232,255,71,0.06); border:1px solid rgba(232,255,71,0.2); border-radius:20px; padding:28px; margin-bottom:32px; text-align:center;">
                <div style="font-family:var(--display); font-size:48px; color:var(--accent); line-height:1; margin-bottom:8px;">✦</div>
                <div style="font-size:18px; font-weight:700; color:var(--text); margin-bottom:6px;">Assinante Pro</div>
                <div style="font-size:13px; color:var(--muted);">
                    Plano <strong style="color:var(--text);"><?= ucfirst($dados['plan'] ?? '') ?></strong>
                    <?php if (!empty($dados['expires_at'])): ?>
                        · Renova em <strong style="color:var(--text);"><?= date('d/m/Y', strtotime($dados['expires_at'])) ?></strong>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$isAtivo): ?>

            <!-- Alert de urgência -->
            <?php if ($isExpirado): ?>
            <div style="background:rgba(255,71,71,0.06); border:1px solid rgba(255,71,71,0.25); border-radius:14px; padding:16px 20px; margin-bottom:28px; font-size:14px; color:#ff7070; text-align:center; line-height:1.5;">
                ⛔ Seu trial encerrou. Seus roteiros existentes estão disponíveis para leitura, mas você não pode criar novos.
            </div>
            <?php elseif ($diasRest <= 7): ?>
            <div style="background:rgba(255,71,71,0.06); border:1px solid rgba(255,71,71,0.25); border-radius:14px; padding:16px 20px; margin-bottom:28px; font-size:14px; color:#ff7070; text-align:center;">
                🔴 Apenas <strong><?= $diasRest ?> dia<?= $diasRest !== 1 ? 's' : '' ?></strong> restantes — assine agora para não perder o acesso.
            </div>
            <?php elseif ($diasRest <= 14): ?>
            <div style="background:rgba(232,255,71,0.06); border:1px solid rgba(232,255,71,0.15); border-radius:14px; padding:14px 20px; margin-bottom:28px; font-size:14px; color:var(--accent); text-align:center;">
                ⚡ <?= $diasRest ?> dias restantes no trial.
            </div>
            <?php endif; ?>

            <!-- Alertas de retorno do Mercado Pago -->
            <?php if ($statusMP === 'sucesso'): ?>
            <div style="background:rgba(52,211,153,0.08); border:1px solid rgba(52,211,153,0.3); border-radius:14px; padding:16px 20px; margin-bottom:28px; font-size:14px; color:#34d399; text-align:center;">
                ✅ Pagamento confirmado! Seu acesso será ativado em instantes.
            </div>
            <?php elseif ($statusMP === 'pendente'): ?>
            <div style="background:rgba(251,191,36,0.08); border:1px solid rgba(251,191,36,0.3); border-radius:14px; padding:16px 20px; margin-bottom:28px; font-size:14px; color:#fbbf24; text-align:center;">
                ⏳ Pagamento em análise. Você receberá a confirmação em breve.
            </div>
            <?php elseif ($statusMP === 'falha' || $erroMP): ?>
            <div style="background:rgba(255,71,71,0.06); border:1px solid rgba(255,71,71,0.25); border-radius:14px; padding:16px 20px; margin-bottom:28px; font-size:14px; color:#ff7070; text-align:center;">
                ❌ <?= $erroMP === 'checkout_falhou' ? 'Erro ao iniciar o checkout. Tente novamente ou fale com o suporte.' : 'O pagamento não foi concluído. Tente novamente.' ?>
            </div>
            <?php endif; ?>

            <!-- Cards de plano -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:36px;">

                <!-- Mensal -->
                <div class="plan-card">
                    <div class="plan-label">Mensal</div>
                    <div class="plan-price">R$<?= $precoMensalFmt ?></div>
                    <div class="plan-per">por mês</div>
                    <ul class="plan-features">
                        <li><span class="check">✓</span>Roteiros ilimitados</li>
                        <li><span class="check">✓</span>50 fontes de conhecimento</li>
                        <li><span class="check">✓</span>IA aprende seu estilo</li>
                        <li><span class="check">✓</span>Modo leitura offline</li>
                        <li><span class="check">✓</span>Cancele quando quiser</li>
                    </ul>
                    <?php if ($temMP): ?>
                        <form method="POST" action="<?= raizUrl('/api/assinatura/criar_preferencia.php') ?>">
                            <input type="hidden" name="plano" value="mensal">
                            <button type="submit" class="btn-plan-mensal" style="width:100%; font-family:inherit;">
                                Assinar por R$<?= $precoMensalFmt ?>/mês
                            </button>
                        </form>
                    <?php else: ?>
                        <button class="btn-plan-mensal" disabled style="opacity:0.4; cursor:not-allowed;">Em breve</button>
                    <?php endif; ?>
                </div>

                <!-- Anual (destaque) -->
                <div class="plan-card featured">
                    <div class="plan-badge">Mais popular · economize R$<?= $economiaAnual ?></div>
                    <div class="plan-label">Anual</div>
                    <div class="plan-price">R$<?= $precoAnualFmt ?></div>
                    <div class="plan-per">por ano · <span>R$<?= $mensalidadeAnual ?>/mês</span></div>
                    <ul class="plan-features">
                        <li class="highlight"><span class="check">✓</span>Tudo do mensal</li>
                        <li class="highlight"><span class="check">✓</span>~1,8 meses grátis</li>
                        <li><span class="check">✓</span>Roteiros ilimitados</li>
                        <li><span class="check">✓</span>50 fontes de conhecimento</li>
                        <li><span class="check">✓</span>Prioridade em novos recursos</li>
                    </ul>
                    <?php if ($temMP): ?>
                        <form method="POST" action="<?= raizUrl('/api/assinatura/criar_preferencia.php') ?>">
                            <input type="hidden" name="plano" value="anual">
                            <button type="submit" class="btn-plan-anual" style="width:100%; font-family:inherit;">
                                Assinar por R$<?= $precoAnualFmt ?>/ano
                            </button>
                        </form>
                    <?php else: ?>
                        <button class="btn-plan-anual" disabled style="opacity:0.4; cursor:not-allowed;">Em breve</button>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Segurança -->
            <div style="text-align:center; font-size:13px; color:var(--muted); margin-bottom:40px; line-height:1.8;">
                <div>🔒 Pagamento seguro via <strong style="color:var(--text);">Mercado Pago</strong> · PIX, cartão de crédito ou boleto</div>
                <div>Sem cobrança automática após o trial. Você escolhe quando assinar.</div>
            </div>

            <!-- FAQ -->
            <div style="margin-bottom:12px;">
                <h3 style="font-family:var(--serif); font-style:italic; font-size:22px; color:var(--text); margin-bottom:16px;">Perguntas frequentes</h3>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php foreach ([
                        [0, 'Preciso de cartão para o trial?', 'Não. O trial de 35 dias é totalmente gratuito, sem nenhuma informação de pagamento.'],
                        [1, 'O que acontece quando o trial expira?', 'Seus roteiros existentes ficam disponíveis para leitura. Para criar novos, é necessário assinar.'],
                        [2, 'Posso cancelar a qualquer momento?', 'Sim. Cancele quando quiser e você mantém o acesso até o final do período pago.'],
                        [3, 'Posso mudar de mensal para anual?', 'Sim. Basta assinar o plano anual — você cancela o mensal e passa a usar o anual.'],
                    ] as [$i, $pergunta, $resposta]): ?>
                    <div class="faq-item">
                        <button class="faq-q" @click="faq = faq === <?= $i ?> ? null : <?= $i ?>">
                            <span><?= $pergunta ?></span>
                            <span x-text="faq === <?= $i ?> ? '−' : '+'" style="font-size:20px; color:var(--accent); flex-shrink:0;"></span>
                        </button>
                        <div class="faq-a" x-show="faq === <?= $i ?>" x-collapse><?= $resposta ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php endif; // !$isAtivo ?>

        </div>
    </main>
</div>
</body>
</html>
