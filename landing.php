<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/assinatura.php';

// Buscar preços do banco
$mpCfg       = getMercadoPagoConfig();
$precoMensal = (float)($mpCfg['plano_mensal_preco'] ?? PLANO_MENSAL_PRECO);
$precoAnual  = (float)($mpCfg['plano_anual_preco']  ?? PLANO_ANUAL_PRECO);
$economiaAno = ($precoMensal * 12) - $precoAnual;
$mensal_eq   = number_format($precoAnual / 12, 2, ',', '.');
$precoMsStr  = number_format($precoMensal, 0, ',', '.');
$precoAnStr  = number_format($precoAnual,  0, ',', '.');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distinto — Gestão Financeira Inteligente para Agências</title>
    <meta name="description" content="O controle total da sua agência em um só lugar. Fluxo de caixa, propostas e rentabilidade com design premium.">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            font-size: 16px; 
            line-height: 1.6; 
            overflow-x: hidden;
        }
        
        a { color: inherit; text-decoration: none; }

        /* ── Nav ────────────────────────────────── */
        .nav {
            position: fixed; top: 0; width: 100%; z-index: 1000;
            background: rgba(5,5,5,0.8); backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 0 6vw;
            display: flex; align-items: center; justify-content: space-between;
            height: 72px;
        }
        
        .nav-logo { display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 20px; }
        .nav-logo img { width: 28px; height: 28px; }
        
        .nav-cta { display: flex; gap: 24px; align-items: center; }
        .btn-ghost { font-size: 14px; font-weight: 500; color: var(--muted); transition: color 0.2s; }
        .btn-ghost:hover { color: var(--text); }
        
        .btn-cta {
            background: var(--accent); color: #000;
            padding: 10px 24px; border-radius: 12px;
            font-weight: 600; font-size: 14px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
<<<<<<< HEAD
        .btn-cta:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(255,255,255,0.1); }

        /* ── Hero ───────────────────────────────── */
        section { padding: 100px 6vw; }
        .container { max-width: 1100px; margin: 0 auto; }
        
        .hero { padding-top: 180px; padding-bottom: 120px; text-align: center; }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.05); border: 1px solid var(--border);
            padding: 6px 16px; border-radius: 100px; font-size: 13px; font-weight: 500;
            margin-bottom: 32px; color: var(--muted);
        }
        .hero-badge span { color: #fff; font-weight: 600; }
        
        .hero h1 {
            font-size: clamp(40px, 6vw, 84px);
            font-weight: 700; line-height: 1.05; letter-spacing: -0.03em;
            margin-bottom: 24px;
        }
        
        .hero h1 span {
            background: linear-gradient(180deg, #fff 0%, rgba(255,255,255,0.4) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .hero-sub { 
            font-size: clamp(18px, 2vw, 22px); color: var(--muted); 
            max-width: 600px; margin: 0 auto 40px; font-weight: 400;
        }
        
        .hero-cta-group { display: flex; gap: 16px; justify-content: center; }
        .btn-hero-primary {
            background: #fff; color: #000;
            padding: 16px 40px; border-radius: 14px;
            font-weight: 600; font-size: 16px; transition: all 0.3s;
        }
        .btn-hero-primary:hover { transform: scale(1.02); box-shadow: 0 20px 40px rgba(255,255,255,0.15); }
        
        /* ── Mockup ────────────────────────────── */
        .mockup-container {
            margin-top: 80px;
            perspective: 1000px;
        }
        
        .mock-dashboard {
            background: #0d0d0d;
            border: 1px solid var(--border);
            border-radius: 24px;
            aspect-ratio: 16/9;
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            box-shadow: 0 40px 100px rgba(0,0,0,0.8);
            transform: rotateX(5deg);
        }
        
        .mock-sidebar { width: 60px; height: 100%; border-right: 1px solid var(--border); display: flex; flex-direction: column; gap: 20px; padding: 20px 10px; }
        .mock-item { height: 10px; background: rgba(255,255,255,0.05); border-radius: 4px; }
        
        .mock-content { flex: 1; padding: 30px; }
        .mock-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .mock-card { background: var(--surface2); border: 1px solid var(--border); border-radius: 16px; height: 120px; padding: 20px; }
        .mock-chart { height: 200px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 20px; margin-top: 20px; }

        /* ── Features ───────────────────────────── */
        .section-tag { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); margin-bottom: 12px; }
        .section-title { font-size: clamp(32px, 4vw, 48px); font-weight: 700; margin-bottom: 48px; letter-spacing: -0.02em; }
        
        .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        
        .feature-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 24px; padding: 32px; transition: all 0.3s;
            display: flex; flex-direction: column; gap: 20px;
        }
        
        .feature-card:hover { border-color: rgba(255,255,255,0.2); transform: translateY(-5px); }
        
        .feature-icon { 
            width: 48px; height: 48px; background: rgba(255,255,255,0.05); 
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: #fff;
        }
        
        .feature-title { font-size: 20px; font-weight: 600; }
        .feature-text { color: var(--muted); font-size: 15px; line-height: 1.6; }

        /* ── Pricing ────────────────────────────── */
        .pricing-section { background: var(--surface); }
        .pricing-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; max-width: 800px; margin: 0 auto; }
        
        .pricing-card {
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 32px; padding: 48px; position: relative;
            display: flex; flex-direction: column; gap: 24px;
        }
        
        .pricing-card.popular { border-color: #fff; }
        .popular-badge {
            position: absolute; top: -14px; left: 50%; transform: translateX(-50%);
            background: #fff; color: #000; padding: 4px 16px; border-radius: 100px;
            font-size: 11px; font-weight: 700; text-transform: uppercase;
        }
        
        .price { font-size: 56px; font-weight: 700; line-height: 1; }
        .price span { font-size: 16px; font-weight: 500; color: var(--muted); }
        
        .plan-features { list-style: none; display: flex; flex-direction: column; gap: 14px; margin: 24px 0; }
        .plan-features li { font-size: 14px; color: var(--muted); display: flex; align-items: center; gap: 12px; }
        .plan-features li i { color: #fff; font-size: 12px; }

        /* ── FAQ ────────────────────────────────── */
        .faq-list { max-width: 700px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }
        .faq-item { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
        .faq-q {
            width: 100%; text-align: left; background: none; border: none; color: #fff;
            padding: 24px; cursor: pointer; font-family: inherit; font-size: 16px; font-weight: 500;
            display: flex; justify-content: space-between; align-items: center;
        }
        .faq-a { padding: 0 24px 24px; color: var(--muted); line-height: 1.6; font-size: 15px; }

        /* ── Footer ─────────────────────────────── */
        footer {
            padding: 60px 6vw; border-top: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
            color: var(--muted); font-size: 14px;
        }
        
        .footer-links { display: flex; gap: 32px; }
        .footer-links a:hover { color: #fff; }

        /* ── Mobile ────────────────────────────── */
        @media (max-width: 900px) {
            .features-grid, .pricing-grid { grid-template-columns: 1fr; }
            .nav-cta .btn-ghost { display: none; }
            section { padding: 80px 6vw; }
            .hero h1 { font-size: 48px; }
        }
    </style>
</head>
<body x-data="{ faq: null }">

    <!-- NAV -->
    <nav class="nav">
        <div class="nav-logo">
            <img src="favicon.svg" alt="Distinto">
            <span>DISTINTO</span>
        </div>
        <div class="nav-cta">
            <a href="<?= raizUrl('/index.php') ?>" class="btn-ghost">Entrar</a>
            <a href="<?= raizUrl('/registro.php') ?>" class="btn-cta">Começar Agora</a>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero">
>>>>>>> b4dc8144255ea2fef2a63c58f2f05b4d78db6922
        <div class="container">
            <div class="hero-badge">✨ Gestão financeira <span>simplificada</span> para agências</div>
            <h1><span>O controle total da<br>sua agência.</span></h1>
            <p class="hero-sub">Transforme a gestão financeira da sua agência com design premium e processos inteligentes. Tudo em um só lugar.</p>
            
            <div class="hero-cta-group">
                <a href="<?= raizUrl('/registro.php') ?>" class="btn-hero-primary">Criar conta gratuita</a>
            </div>
            
            <div class="mockup-container">
                <div class="mock-dashboard">
                    <div style="display: flex; height: 100%;">
                        <div class="mock-sidebar">
                            <div class="mock-item" style="width: 100%; background: #fff;"></div>
                            <div class="mock-item" style="width: 80%;"></div>
                            <div class="mock-item" style="width: 70%;"></div>
                            <div class="mock-item" style="width: 90%;"></div>
                        </div>
                        <div class="mock-content">
                            <div class="mock-grid">
                                <div class="mock-card">
                                    <div style="font-size: 10px; color: var(--muted); margin-bottom: 8px;">RECEITA MENSAL</div>
                                    <div style="font-size: 24px; font-weight: 600;">R$ 42.500</div>
                                </div>
                                <div class="mock-card">
                                    <div style="font-size: 10px; color: var(--muted); margin-bottom: 8px;">RENTABILIDADE</div>
                                    <div style="font-size: 24px; font-weight: 600; color: #4ade80;">+24%</div>
                                </div>
                                <div class="mock-card">
                                    <div style="font-size: 10px; color: var(--muted); margin-bottom: 8px;">PROJETOS ATIVOS</div>
                                    <div style="font-size: 24px; font-weight: 600;">12</div>
                                </div>
                            </div>
                            <div class="mock-chart">
                                <div style="display: flex; align-items: flex-end; height: 100%; padding: 20px; gap: 10px;">
                                    <div style="flex: 1; height: 40%; background: rgba(255,255,255,0.1); border-radius: 4px;"></div>
                                    <div style="flex: 1; height: 60%; background: rgba(255,255,255,0.1); border-radius: 4px;"></div>
                                    <div style="flex: 1; height: 80%; background: #fff; border-radius: 4px;"></div>
                                    <div style="flex: 1; height: 50%; background: rgba(255,255,255,0.1); border-radius: 4px;"></div>
                                    <div style="flex: 1; height: 70%; background: rgba(255,255,255,0.1); border-radius: 4px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section>
        <div class="container">
            <div class="section-tag">Funcionalidades</div>
            <h2 class="section-title">Construído para agências<br>que buscam escala.</h2>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="feature-title">Fluxo de Caixa</div>
                    <p class="feature-text">Visualize entradas e saídas de forma clara. Saiba exatamente quanto dinheiro sua agência terá no próximo mês.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="feature-title">Propostas Premium</div>
                    <div class="feature-text">Crie propostas comerciais que encantam seus clientes. Orçamentos detalhados e profissionais em poucos cliques.</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-project-diagram"></i></div>
                    <div class="feature-title">Rentabilidade de Projetos</div>
                    <div class="feature-text">Entenda quais projetos são lucrativos e quais estão consumindo sua margem. Decisões baseadas em dados.</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-users"></i></div>
                    <div class="feature-title">Gestão de Clientes</div>
                    <div class="feature-text">Centralize todo o histórico financeiro e contratual dos seus clientes em uma interface minimalista.</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-clock"></i></div>
                    <div class="feature-title">Automação de Cobrança</div>
                    <div class="feature-text">Reduza a inadimplência com lembretes automáticos e controle rigoroso de vencimentos.</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="feature-title">Segurança de Dados</div>
                    <div class="feature-text">Suas informações financeiras estão protegidas com criptografia de ponta a ponta e backups automáticos.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- PRICING -->
    <section class="pricing-section">
        <div class="container">
            <div style="text-align: center; margin-bottom: 60px;">
                <div class="section-tag">Planos</div>
                <h2 class="section-title">O investimento certo para<br>o seu crescimento.</h2>
            </div>
            
            <div class="pricing-grid">
                <div class="pricing-card">
                    <div>
                        <div style="font-weight: 600; color: var(--muted); margin-bottom: 8px;">PLANO MENSAL</div>
                        <div class="price">R$<?= $precoMsStr ?><span>/mês</span></div>
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Gestão de Fluxo de Caixa</li>
                        <li><i class="fas fa-check"></i> Propostas Ilimitadas</li>
                        <li><i class="fas fa-check"></i> Relatórios de Rentabilidade</li>
                        <li><i class="fas fa-check"></i> Suporte Prioritário</li>
                    </ul>
                    <a href="<?= raizUrl('/registro.php') ?>" class="btn-cta" style="text-align: center; background: transparent; border: 1px solid var(--border); color: #fff;">Começar Grátis</a>
                </div>
                
                <div class="pricing-card popular">
                    <div class="popular-badge">Mais escolhido</div>
                    <div>
                        <div style="font-weight: 600; color: var(--muted); margin-bottom: 8px;">PLANO ANUAL</div>
                        <div class="price">R$<?= $precoAnStr ?><span>/ano</span></div>
                        <div style="font-size: 13px; color: #4ade80; margin-top: 8px;">Equivalente a R$<?= $mensal_eq ?>/mês</div>
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Tudo do plano Mensal</li>
                        <li><i class="fas fa-check"></i> Economia de R$<?= number_format($economiaAno, 0, ',', '.') ?></li>
                        <li><i class="fas fa-check"></i> Acesso antecipado a recursos</li>
                        <li><i class="fas fa-check"></i> Consultoria de Implementação</li>
                    </ul>
                    <a href="<?= raizUrl('/registro.php') ?>" class="btn-cta" style="text-align: center;">Garantir Desconto</a>
                </div>
            </div>
            
            <p style="text-align: center; margin-top: 40px; color: var(--muted); font-size: 14px;">
                <i class="fas fa-lock" style="margin-right: 8px;"></i> Pagamento seguro processado pelo Mercado Pago.
            </p>
        </div>
    </section>

    <!-- FAQ -->
    <section>
        <div class="container">
            <div style="text-align: center; margin-bottom: 60px;">
                <div class="section-tag">FAQ</div>
                <h2 class="section-title">Perguntas Frequentes</h2>
            </div>
            
            <div class="faq-list">
                <div class="faq-item">
                    <button class="faq-q" @click="faq = faq === 0 ? null : 0">
                        O Distinto é focado apenas em agências?
                        <i class="fas" :class="faq === 0 ? 'fa-minus' : 'fa-plus'"></i>
                    </button>
                    <div class="faq-a" x-show="faq === 0" x-collapse>
                        Sim. Toda a nossa interface e processos foram desenhados especificamente para as necessidades de agências de marketing, design e publicidade.
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-q" @click="faq = faq === 1 ? null : 1">
                        Posso cancelar minha assinatura a qualquer momento?
                        <i class="fas" :class="faq === 1 ? 'fa-minus' : 'fa-plus'"></i>
                    </button>
                    <div class="faq-a" x-show="faq === 1" x-collapse>
                        Com certeza. No plano mensal você pode cancelar quando quiser sem fidelidade. No plano anual, o cancelamento interrompe a renovação automática.
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-q" @click="faq = faq === 2 ? null : 2">
                        Meus dados financeiros estão seguros?
                        <i class="fas" :class="faq === 2 ? 'fa-minus' : 'fa-plus'"></i>
                    </button>
                    <div class="faq-a" x-show="faq === 2" x-collapse>
                        A segurança é nossa prioridade. Utilizamos criptografia de nível bancário e backups diários para garantir que suas informações nunca sejam perdidas ou acessadas por terceiros.
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-q" @click="faq = faq === 3 ? null : 3">
                        Existe um período de teste gratuito?
                        <i class="fas" :class="faq === 3 ? 'fa-minus' : 'fa-plus'"></i>
                    </button>
                    <div class="faq-a" x-show="faq === 3" x-collapse>
                        Sim! Você pode começar agora mesmo e utilizar as funcionalidades básicas para conhecer o sistema antes de decidir pelo plano premium.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA FINAL -->
    <section style="text-align: center; background: linear-gradient(180deg, var(--bg) 0%, #0a0a0a 100%);">
        <div class="container">
            <h2 class="section-title" style="margin-bottom: 24px;">Assuma o controle da sua agência hoje.</h2>
            <p style="color: var(--muted); margin-bottom: 40px; max-width: 500px; margin-left: auto; margin-right: auto;">
                Junte-se a dezenas de agências que profissionalizaram sua gestão financeira com o Distinto.
            </p>
            <a href="<?= raizUrl('/registro.php') ?>" class="btn-hero-primary">Começar Agora</a>
        </div>
    </section>

    <footer>
        <div class="nav-logo">
            <img src="favicon.svg" alt="Distinto">
            <span style="color: #fff;">DISTINTO</span>
        </div>
        <div>© <?= date('Y') ?> Distinto. Todos os direitos reservados.</div>
        <div class="footer-links">
            <a href="#">Privacidade</a>
            <a href="#">Termos</a>
            <a href="<?= raizUrl('/index.php') ?>">Login</a>
        </div>
    </footer>

</body>
</html>
