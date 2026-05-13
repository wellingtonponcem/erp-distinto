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
    <title>Assinatura — Distinto</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        :root {
            --bg: #050505; 
            --surface: #0a0a0a; 
            --surface2: #121212;
            --border: rgba(255,255,255,0.06); 
            --accent: #ffffff;
            --text: #ffffff; 
            --muted: #888;
            --font-family: 'Outfit', sans-serif;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            background: var(--bg); 
            color: var(--text); 
            font-family: var(--font-family); 
            min-height: 100vh;
            line-height: 1.6;
        }

        .layout { display: flex; min-height: 100vh; }
        
        main { 
            flex: 1; 
            padding: 60px 40px; 
            max-width: 1200px; 
            margin: 0 auto;
        }

        .header { text-align: center; margin-bottom: 60px; }
        .header h1 { font-size: 40px; font-weight: 700; margin-bottom: 12px; }
        .header p { color: var(--muted); font-size: 16px; }

        /* Plan Cards */
        .plans-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 40px; }
        
        .plan-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 24px; padding: 40px; cursor: pointer;
            transition: all 0.3s; position: relative;
        }
        
        .plan-card:hover { border-color: rgba(255,255,255,0.2); transform: translateY(-4px); }
        .plan-card.selected { border-color: #fff; background: rgba(255,255,255,0.02); }
        
        .plan-badge {
            position: absolute; top: -14px; left: 50%; transform: translateX(-50%);
            background: #fff; color: #000; padding: 4px 16px; border-radius: 100px;
            font-size: 11px; font-weight: 700; text-transform: uppercase;
        }
        
        .plan-name { font-size: 14px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 12px; }
        .plan-price { font-size: 56px; font-weight: 700; line-height: 1; margin-bottom: 8px; }
        .plan-price span { font-size: 16px; color: var(--muted); font-weight: 500; }
        
        .plan-features { list-style: none; margin: 32px 0; display: flex; flex-direction: column; gap: 14px; }
        .plan-features li { font-size: 14px; color: var(--muted); display: flex; align-items: center; gap: 12px; }
        .plan-features li i { color: #fff; font-size: 12px; }

        .btn-select {
            width: 100%; padding: 14px; border-radius: 12px;
            background: transparent; border: 1px solid var(--border);
            color: #fff; font-weight: 600; font-size: 15px; cursor: pointer; transition: all 0.3s;
        }
        .btn-select.active { background: #fff; color: #000; border-color: #fff; }
        
        /* Payment Section */
        .payment-box {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 24px; padding: 32px; margin-top: 40px;
            max-width: 600px; margin-left: auto; margin-right: auto;
        }
        
        .payment-summary {
            display: flex; justify-content: space-between; align-items: center;
            padding-bottom: 24px; border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="assinaturaApp()" x-cloak>

    <div class="layout">
        <?php include __DIR__ . '/includes/layout/sidebar.php'; ?>

        <main>
            <div class="header">
                <div style="font-size: 12px; font-weight: 600; text-transform: uppercase; color: var(--muted); letter-spacing: 0.1em; margin-bottom: 16px;">Assinatura</div>
                <h1>
                    <?= $isAtivo ? 'Sua Conta Pro' : 'Escolha seu Plano' ?>
                </h1>
                <p>
                    <?php if ($isAtivo): ?>
                        Você possui acesso completo a todas as ferramentas do Distinto.
                    <?php else: ?>
                        Potencialize a gestão da sua agência com ferramentas profissionais.
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($isAtivo): ?>
                <div style="max-width: 600px; margin: 0 auto; background: var(--surface); border: 1px solid var(--border); border-radius: 24px; padding: 40px; text-align: center;">
                    <div style="font-size: 48px; color: #fff; margin-bottom: 24px;"><i class="fas fa-crown"></i></div>
                    <h2 style="font-size: 24px; margin-bottom: 8px;">Assinante Ativo</h2>
                    <p style="color: var(--muted); margin-bottom: 32px;">
                        Seu plano <strong><?= strtoupper($dados['plan'] ?? 'Pro') ?></strong> está ativo.
                        <?php if (!empty($dados['expires_at'])): ?>
                            <br>Próxima renovação em <?= date('d/m/Y', strtotime($dados['expires_at'])) ?>.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                
                <div class="plans-grid">
                    <!-- Mensal -->
                    <div class="plan-card" :class="{ 'selected': plano === 'mensal' }" @click="selecionarPlano('mensal')">
                        <div class="plan-name">Mensal</div>
                        <div class="plan-price">R$<?= $precoMensalFmt ?><span>/mês</span></div>
                        <ul class="plan-features">
                            <li><i class="fas fa-check"></i> Gestão de Fluxo de Caixa</li>
                            <li><i class="fas fa-check"></i> Propostas Web Ilimitadas</li>
                            <li><i class="fas fa-check"></i> Relatórios de Rentabilidade</li>
                            <li><i class="fas fa-check"></i> Suporte Prioritário</li>
                        </ul>
                        <button class="btn-select" :class="{ 'active': plano === 'mensal' }">
                            <span x-text="plano === 'mensal' ? '✓ Selecionado' : 'Selecionar Plano'"></span>
                        </button>
                    </div>

                    <!-- Anual -->
                    <div class="plan-card" :class="{ 'selected': plano === 'anual' }" @click="selecionarPlano('anual')">
                        <div class="plan-badge">Economize R$<?= $economiaAnual ?></div>
                        <div class="plan-name">Anual</div>
                        <div class="plan-price">R$<?= $precoAnualFmt ?><span>/ano</span></div>
                        <div style="font-size: 13px; color: #4ade80; margin-top: 4px;">Equivalente a R$<?= $mensalidadeAnual ?>/mês</div>
                        <ul class="plan-features">
                            <li><i class="fas fa-check"></i> Tudo do plano mensal</li>
                            <li><i class="fas fa-check"></i> Desconto exclusivo anual</li>
                            <li><i class="fas fa-check"></i> Acesso antecipado a novas IAs</li>
                            <li><i class="fas fa-check"></i> Consultoria de setup incluída</li>
                        </ul>
                        <button class="btn-select" :class="{ 'active': plano === 'anual' }">
                            <span x-text="plano === 'anual' ? '✓ Selecionado' : 'Selecionar Plano'"></span>
                        </button>
                    </div>
                </div>

                <!-- Seção de Pagamento -->
                <div class="payment-box" x-show="plano" x-transition>
                    <div class="payment-summary">
                        <div>
                            <div style="font-weight: 600; font-size: 18px;" x-text="plano === 'anual' ? 'Plano Anual' : 'Plano Mensal'"></div>
                            <div style="font-size: 13px; color: var(--muted);">Pagamento seguro processado via Mercado Pago</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 24px; font-weight: 700;" x-text="plano === 'anual' ? 'R$<?= $precoAnualFmt ?>' : 'R$<?= $precoMensalFmt ?>'"></div>
                            <button @click="plano = null; unmountBrick()" style="background: none; border: none; color: var(--muted); font-size: 11px; cursor: pointer; text-decoration: underline;">Trocar plano</button>
                        </div>
                    </div>

                    <div id="paymentBrick_container"></div>
                    
                    <div x-show="brickLoading" style="text-align: center; padding: 40px; color: var(--muted);">
                        <i class="fas fa-circle-notch fa-spin" style="margin-right: 8px;"></i> Carregando Mercado Pago...
                    </div>
                </div>

            <?php endif; ?>

            <div style="text-align: center; margin-top: 60px; color: var(--muted); font-size: 13px;">
                <p><i class="fas fa-lock" style="margin-right: 8px;"></i> Pagamento 100% seguro via Mercado Pago</p>
                <p style="margin-top: 8px;">Dúvidas? Entre em contato com nosso suporte.</p>
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
                if (this.plano === p) return;
                this.plano = p;
                if (!TEM_MP) return;
                await this.$nextTick();
                this.initBrick(p);
            },

            unmountBrick() {
                if (this.brickController) {
                    try { this.brickController.unmount(); } catch(e) {}
                    this.brickController = null;
                }
            },

            async initBrick(plano) {
                this.unmountBrick();
                this.brickLoading = true;

                try {
                    const mp = new MercadoPago(MP_PUBLIC_KEY, { locale: 'pt-BR' });
                    const builder = mp.bricks();
                    const amount = plano === 'anual' ? PRECO_ANUAL : PRECO_MENSAL;

                    this.brickController = await builder.create('payment', 'paymentBrick_container', {
                        initialization: {
                            amount,
                            payer: { email: PAYER_EMAIL },
                        },
                        customization: {
                            paymentMethods: {
                                creditCard: 'all',
                                debitCard: 'all',
                                ticket: 'all',
                                bankTransfer: 'all',
                                maxInstallments: plano === 'anual' ? 3 : 1,
                            },
                            visual: {
                                style: {
                                    theme: 'dark',
                                    customVariables: {
                                        formBackgroundColor: '#0a0a0a',
                                        inputBackgroundColor: '#121212',
                                        inputBorderColor: 'rgba(255,255,255,0.1)',
                                        textPrimaryColor: '#ffffff',
                                        baseColor: '#ffffff',
                                        borderRadiusLarge: '12px'
                                    }
                                },
                                hideFormTitle: true
                            }
                        },
                        callbacks: {
                            onReady: () => { this.brickLoading = false; },
                            onSubmit: ({ formData }) => {
                                return new Promise((resolve, reject) => {
                                    fetch(URL_PROCESSAR, {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({ ...formData, plano: this.plano }),
                                    })
                                    .then(r => r.json())
                                    .then(result => {
                                        if (result.error) {
                                            reject(result);
                                            return;
                                        }
                                        if (result.status === 'approved') {
                                            resolve(result);
                                            setTimeout(() => { window.location.href = URL_SUCESSO; }, 800);
                                        } else {
                                            resolve(result);
                                        }
                                    })
                                    .catch(err => reject(err));
                                });
                            },
                            onError: (error) => {
                                console.error('[MP Brick]', error);
                                this.brickLoading = false;
                            }
                        }
                    });
                } catch(e) {
                    console.error('[MP init]', e);
                    this.brickLoading = false;
                }
            }
        };
    }
    </script>

</body>
</html>
