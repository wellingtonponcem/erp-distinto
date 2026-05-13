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
$publicKey   = $mpCfg['mercadopago_public_key'] ?? '';
$precoMensal = (float)($mpCfg['plano_mensal_preco'] ?? PLANO_MENSAL_PRECO);
$precoAnual  = (float)($mpCfg['plano_anual_preco']  ?? PLANO_ANUAL_PRECO);

$precoMensalFmt   = number_format($precoMensal, 2, ',', '.');
$precoAnualFmt    = number_format($precoAnual,  2, ',', '.');
$mensalidadeAnual = number_format($precoAnual / 12, 2, ',', '.');
$economiaAnual    = number_format(($precoMensal * 12) - $precoAnual, 0, ',', '.');

$isAtivo    = $dados['assinante_ativo'] ?? false;
$isExpirado = $dados['trial_expirado']  ?? false;
$diasRest   = $dados['dias_restantes']  ?? 0;
$statusMP   = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinatura — Meus Roteiros</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- MP SDK carrega antes do Alpine para estar disponível na interação -->
    <script src="https://sdk.mercadopago.com/js/v2"></script>
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
            transition: border-color 0.2s, transform 0.2s, opacity 0.2s;
            display: flex;
            flex-direction: column;
            cursor: pointer;
        }
        .plan-card:hover { border-color: rgba(232,255,71,0.2); transform: translateY(-2px); }
        .plan-card.featured { border-color: var(--accent); background: rgba(232,255,71,0.03); }
        .plan-card.featured:hover { border-color: var(--accent); }
        .plan-card.selected { border-color: var(--accent) !important; box-shadow: 0 0 0 1px var(--accent); transform: none; }
        .plan-card.dimmed  { opacity: 0.4; transform: none; cursor: pointer; }

        .plan-badge {
            position: absolute; top: -13px; left: 50%; transform: translateX(-50%);
            background: var(--accent); color: #0a0a0a;
            font-size: 10px; font-weight: 800; padding: 4px 16px;
            border-radius: 100px; white-space: nowrap; letter-spacing: 0.05em; text-transform: uppercase;
        }
        .plan-label { font-size: 11px; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase; color: var(--muted); margin-bottom: 14px; }
        .plan-card.featured .plan-label { color: var(--accent); }
        .plan-card.selected .plan-label { color: var(--accent); }

        .plan-price { font-family: var(--display); font-size: 58px; line-height: 1; color: var(--text); margin-bottom: 4px; }
        .plan-per   { font-size: 13px; color: var(--muted); margin-bottom: 24px; }
        .plan-card.featured .plan-per span { color: var(--accent); }

        .plan-features { list-style: none; flex: 1; margin-bottom: 24px; display: flex; flex-direction: column; gap: 10px; }
        .plan-features li {
            font-size: 14px; color: var(--muted);
            display: flex; align-items: center; gap: 10px;
            padding-bottom: 10px; border-bottom: 1px solid var(--border);
        }
        .plan-features li:last-child { border: none; padding-bottom: 0; }
        .plan-features li .check { color: var(--accent); font-size: 12px; flex-shrink: 0; }
        .plan-features li.highlight { color: var(--text); font-weight: 500; }

        .btn-select {
            display: block; width: 100%; text-align: center; padding: 14px;
            border-radius: 100px; font-size: 14px; font-weight: 700; font-family: var(--sans);
            border: 1px solid var(--border); color: var(--text); background: transparent;
            cursor: pointer; transition: all 0.2s;
        }
        .btn-select:hover { border-color: var(--accent); color: var(--accent); }
        .btn-select.featured-btn {
            background: var(--accent); color: #0a0a0a; border: none;
            font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em;
        }
        .btn-select.featured-btn:hover { opacity: 0.9; }
        .btn-select.active-btn {
            background: var(--accent); color: #0a0a0a; border: none;
        }

        /* Seção de pagamento */
        .payment-section {
            background: var(--surface);
            border: 1px solid rgba(232,255,71,0.2);
            border-radius: 24px;
            padding: 28px;
            margin-bottom: 28px;
        }
        .payment-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px; padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }
        .payment-title { font-size: 14px; font-weight: 700; color: var(--text); }
        .payment-amount { font-family: var(--display); font-size: 28px; color: var(--accent); line-height: 1; }
        .payment-change {
            font-size: 12px; color: var(--muted); cursor: pointer; text-decoration: underline;
            background: none; border: none; font-family: var(--sans);
        }
        .payment-change:hover { color: var(--text); }

        /* FAQ */
        .faq-item { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
        .faq-q {
            width: 100%; text-align: left; background: none; border: none;
            color: var(--text); font-size: 14px; font-weight: 600; font-family: var(--sans);
            padding: 18px 20px; cursor: pointer;
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
        }
        .faq-a { font-size: 13px; color: var(--muted); padding: 0 20px 16px; line-height: 1.6; }

        /* MP Brick dark override */
        #paymentBrick_container { min-height: 200px; }

        /*
         * Esconde o badge "Parcelamento disponível" que o MP exibe
         * estaticamente na lista de métodos de pagamento.
         * Com maxInstallments:1 o usuário não consegue parcelar de qualquer
         * forma, mas o badge aparece por padrão — ocultamos via CSS.
         */
        #paymentBrick_container .andes-badge,
        #paymentBrick_container [class*="Badge"],
        #paymentBrick_container [class*="badge"],
        #paymentBrick_container [class*="installment"],
        #paymentBrick_container [class*="Installment"],
        #paymentBrick_container [data-testid*="installment"],
        #paymentBrick_container [data-testid*="badge"] {
            display: none !important;
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="assinaturaApp()" x-cloak>
<div style="display:flex; min-height:100vh;">

    <?php include __DIR__ . '/roteiros/includes/sidebar.php'; ?>

    <main style="flex:1; overflow-y:auto; padding:2.5rem 2rem 5rem; max-width:calc(100vw - 52px);">
        <div style="max-width:720px; margin:0 auto;">

            <!-- Cabeçalho -->
            <div style="margin-bottom:2.5rem; text-align:center;">
                <div style="font-size:10px; letter-spacing:0.2em; text-transform:uppercase; color:var(--accent); margin-bottom:12px; font-weight:600;">Conta</div>
                <h1 style="font-family:var(--display); font-size:clamp(40px,6vw,64px); line-height:0.95; margin-bottom:14px;">
                    <?= $isAtivo
                        ? 'Sua <em style="font-family:var(--serif);font-style:italic;color:var(--accent);">Assinatura</em>'
                        : 'Assine e <em style="font-family:var(--serif);font-style:italic;color:var(--accent);">Desbloqueie</em> tudo' ?>
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
            <div style="background:rgba(232,255,71,0.06); border:1px solid rgba(232,255,71,0.2); border-radius:20px; padding:28px; text-align:center;">
                <div style="font-family:var(--display); font-size:48px; color:var(--accent); line-height:1; margin-bottom:8px;">✦</div>
                <div style="font-size:18px; font-weight:700; color:var(--text); margin-bottom:6px;">Assinante Pro</div>
                <div style="font-size:13px; color:var(--muted);">
                    Plano <strong style="color:var(--text);"><?= ucfirst($dados['plan'] ?? '') ?></strong>
                    <?php if (!empty($dados['expires_at'])): ?>
                        · Renova em <strong style="color:var(--text);"><?= date('d/m/Y', strtotime($dados['expires_at'])) ?></strong>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: // !$isAtivo ?>

            <!-- Alertas de urgência -->
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

            <!-- Alerta sucesso -->
            <?php if ($statusMP === 'sucesso'): ?>
            <div style="background:rgba(52,211,153,0.08); border:1px solid rgba(52,211,153,0.3); border-radius:14px; padding:16px 20px; margin-bottom:28px; font-size:14px; color:#34d399; text-align:center;">
                ✅ Pagamento confirmado! Seu acesso está sendo ativado.
            </div>
            <?php elseif ($statusMP === 'pendente'): ?>
            <div style="background:rgba(251,191,36,0.08); border:1px solid rgba(251,191,36,0.3); border-radius:14px; padding:16px 20px; margin-bottom:28px; font-size:14px; color:#fbbf24; text-align:center;">
                ⏳ Pagamento em análise. Seu acesso será liberado após a confirmação.
            </div>
            <?php endif; ?>

            <!-- Cards de plano -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:28px;">

                <!-- Mensal -->
                <div class="plan-card"
                     :class="{ selected: plano === 'mensal', dimmed: plano && plano !== 'mensal' }"
                     @click="selecionarPlano('mensal')">
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
                        <button class="btn-select" :class="{ 'active-btn': plano === 'mensal' }"
                                @click.stop="selecionarPlano('mensal')">
                            <span x-text="plano === 'mensal' ? '✓ Selecionado' : 'Assinar por R$<?= $precoMensalFmt ?>/mês'"></span>
                        </button>
                    <?php else: ?>
                        <button class="btn-select" disabled style="opacity:0.4;cursor:not-allowed;">Em breve</button>
                    <?php endif; ?>
                </div>

                <!-- Anual -->
                <div class="plan-card featured"
                     :class="{ selected: plano === 'anual', dimmed: plano && plano !== 'anual' }"
                     @click="selecionarPlano('anual')">
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
                        <button class="btn-select featured-btn" :class="{ 'active-btn': plano === 'anual' }"
                                @click.stop="selecionarPlano('anual')">
                            <span x-text="plano === 'anual' ? '✓ Selecionado' : 'ASSINAR POR R$<?= $precoAnualFmt ?>/ANO'"></span>
                        </button>
                    <?php else: ?>
                        <button class="btn-select featured-btn" disabled style="opacity:0.4;cursor:not-allowed;">Em breve</button>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Seção de pagamento (Payment Brick) -->
            <?php if ($temMP && $publicKey): ?>
            <div class="payment-section" x-show="plano" x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-ref="paymentSection">

                <div class="payment-header">
                    <div>
                        <div class="payment-title" x-text="plano === 'anual' ? 'Plano Anual' : 'Plano Mensal'"></div>
                        <div style="font-size:12px; color:var(--muted); margin-top:2px;" x-text="plano === 'anual' ? 'Cobrança única por 12 meses' : 'Renovação mensal'"></div>
                    </div>
                    <div style="text-align:right;">
                        <div class="payment-amount" x-text="plano === 'anual' ? 'R$<?= $precoAnualFmt ?>' : 'R$<?= $precoMensalFmt ?>'"></div>
                        <button class="payment-change" @click="plano = null; unmountBrick()">Trocar plano</button>
                    </div>
                </div>

                <!-- Brick vai aqui -->
                <div id="paymentBrick_container"></div>

                <!-- Estado de loading -->
                <div x-show="brickLoading" style="text-align:center; padding:40px; color:var(--muted); font-size:14px;">
                    <i class="fa-solid fa-circle-notch fa-spin" style="margin-right:8px;"></i>Carregando formulário de pagamento...
                </div>

                <!-- Erro de pagamento -->
                <div x-show="paymentError" style="background:rgba(255,71,71,0.08); border:1px solid rgba(255,71,71,0.25); border-radius:12px; padding:14px 18px; margin-top:16px; font-size:14px; color:#ff7070;">
                    <i class="fa-solid fa-circle-exclamation" style="margin-right:6px;"></i>
                    <span x-text="paymentError"></span>
                </div>

            </div>
            <?php elseif (!$temMP): ?>
            <div style="text-align:center; padding:24px; background:var(--surface); border:1px solid var(--border); border-radius:16px; margin-bottom:28px;">
                <div style="font-size:13px; color:var(--muted);">Pagamento em configuração. Disponível em breve.</div>
            </div>
            <?php endif; ?>

            <!-- Segurança -->
            <div style="text-align:center; font-size:13px; color:var(--muted); margin-bottom:40px; line-height:1.8;">
                <div>🔒 Pagamento seguro via <strong style="color:var(--text);">Mercado Pago</strong> · PIX, cartão de crédito ou boleto</div>
                <div>Sem cobrança automática após o trial. Você escolhe quando assinar.</div>
            </div>

            <!-- FAQ -->
            <div x-data="{ faq: null }" style="margin-bottom:12px;">
                <h3 style="font-family:var(--serif); font-style:italic; font-size:22px; color:var(--text); margin-bottom:16px;">Perguntas frequentes</h3>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php foreach ([
                        [0, 'Preciso de cartão para o trial?', 'Não. O trial de 35 dias é totalmente gratuito, sem nenhuma informação de pagamento.'],
                        [1, 'O que acontece quando o trial expira?', 'Seus roteiros existentes ficam disponíveis para leitura. Para criar novos, é necessário assinar.'],
                        [2, 'Posso cancelar a qualquer momento?', 'Sim. Cancele quando quiser e você mantém o acesso até o final do período pago.'],
                        [3, 'Posso mudar de mensal para anual?', 'Sim. Assine o plano anual e cancele o mensal no painel do Mercado Pago.'],
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

<script>
const MP_PUBLIC_KEY     = <?= json_encode($publicKey) ?>;
const PRECO_MENSAL      = <?= json_encode($precoMensal) ?>;
const PRECO_ANUAL       = <?= json_encode($precoAnual) ?>;
const URL_PROCESSAR     = <?= json_encode(raizUrl('/api/assinatura/processar_pagamento.php')) ?>;
const URL_SUCESSO       = <?= json_encode(raizUrl('/assinar.php?status=sucesso')) ?>;
const TEM_MP            = <?= json_encode($temMP && !empty($publicKey)) ?>;
const PAYER_EMAIL       = <?= json_encode($usuario['email'] ?? '') ?>;

function assinaturaApp() {
    return {
        plano: null,
        brickLoading: false,
        brickController: null,
        paymentError: null,

        async selecionarPlano(p) {
            if (this.plano === p) return; // já selecionado
            this.plano = p;
            this.paymentError = null;

            if (!TEM_MP) return;

            await this.$nextTick();
            // Scroll suave até o formulário
            this.$refs.paymentSection?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            this.initBrick(p);
        },

        unmountBrick() {
            if (this.brickController) {
                try { this.brickController.unmount(); } catch(e) {}
                this.brickController = null;
            }
            this.paymentError = null;
        },

        async initBrick(plano) {
            this.unmountBrick();
            this.brickLoading = true;

            try {
                const mp      = new MercadoPago(MP_PUBLIC_KEY, { locale: 'pt-BR' });
                const builder = mp.bricks();
                const amount  = plano === 'anual' ? PRECO_ANUAL : PRECO_MENSAL;

                this.brickController = await builder.create('payment', 'paymentBrick_container', {
                    initialization: {
                        amount,
                        payer: {
                            email: PAYER_EMAIL,
                        },
                    },
                    customization: {
                        paymentMethods: {
                            creditCard:      'all',
                            debitCard:       'all',
                            ticket:          'all',  // boleto
                            bankTransfer:    'all',  // PIX
                            // Mensal: sem parcelamento (R$15 não faz sentido parcelar)
                            // Anual: até 3x (R$158 ÷ 3 = R$52,67/mês)
                            maxInstallments: plano === 'anual' ? 3 : 1,
                        },
                        visual: {
                            style: {
                                theme: 'dark',
                                customVariables: {
                                    // Fundo
                                    formBackgroundColor:  '#111111',
                                    // Inputs
                                    inputBackgroundColor: '#181818',
                                    inputBorderColor:     'rgba(255,255,255,0.10)',
                                    // Textos
                                    textPrimaryColor:     '#F0EDE6',
                                    textSecondaryColor:   '#888888',
                                    // Accent (seleção, botão, radio)
                                    baseColor:            '#E8FF47',
                                    // Border radius
                                    borderRadiusLarge:    '16px',
                                    borderRadiusMedium:   '10px',
                                    borderRadiusSmall:    '6px',
                                    // Botão pagar
                                    buttonHeight:         '52px',
                                },
                            },
                            hideFormTitle:    true,
                            hidePaymentButton: false,
                        },
                    },
                    callbacks: {
                        onReady: () => { this.brickLoading = false; },

                        onSubmit: ({ formData }) => {
                            this.paymentError = null;
                            return new Promise((resolve, reject) => {
                                fetch(URL_PROCESSAR, {
                                    method:  'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body:    JSON.stringify({ ...formData, plano: this.plano }),
                                })
                                .then(r => r.json())
                                .then(result => {
                                    if (result.error) {
                                        this.paymentError = result.error;
                                        reject(result);
                                        return;
                                    }
                                    if (result.status === 'approved') {
                                        // Ativar e redirecionar
                                        resolve(result);
                                        setTimeout(() => { window.location.href = URL_SUCESSO; }, 800);
                                    } else {
                                        // pending (PIX), in_process, rejected — Brick trata o UI
                                        resolve(result);
                                    }
                                })
                                .catch(err => {
                                    this.paymentError = 'Erro de conexão. Tente novamente.';
                                    reject(err);
                                });
                            });
                        },

                        onError: (error) => {
                            console.error('[MP Brick]', error);
                            this.brickLoading = false;
                        },
                    },
                });
            } catch(e) {
                console.error('[MP init]', e);
                this.brickLoading = false;
                this.paymentError = 'Erro ao carregar o formulário de pagamento.';
            }
        },
    };
}
</script>
</body>
</html>
