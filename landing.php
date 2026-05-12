<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/assinatura.php';

// Buscar preços do banco
$abCfg       = getAbacateConfig();
$precoMensal = (float)($abCfg['plano_mensal_preco'] ?? PLANO_MENSAL_PRECO);
$precoAnual  = (float)($abCfg['plano_anual_preco']  ?? PLANO_ANUAL_PRECO);
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
    <title>Distinto Roteiros — IA que aprende o seu estilo</title>
    <meta name="description" content="Crie roteiros para vídeos com IA que aprende o seu estilo. 35 dias grátis, sem cartão.">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --bg: #0a0a0a; --surface: #111; --surface2: #181818;
            --border: rgba(255,255,255,0.07); --accent: #E8FF47;
            --text: #F0EDE6; --muted: #888;
            --serif: 'Instrument Serif', Georgia, serif;
            --sans: 'DM Sans', sans-serif;
            --display: 'Bebas Neue', sans-serif;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); color: var(--text); font-family: var(--sans); font-size: 16px; line-height: 1.65; }

        a { color: inherit; text-decoration: none; }

        /* Nav */
        .nav {
            position: sticky; top: 0; z-index: 100;
            background: rgba(10,10,10,0.92); backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            padding: 0 6vw;
            display: flex; align-items: center; justify-content: space-between;
            height: 60px;
        }
        .nav-logo { font-family: var(--display); font-size: 22px; letter-spacing: 0.05em; color: var(--text); }
        .nav-logo span { color: var(--accent); }
        .nav-cta { display: flex; gap: 12px; align-items: center; }
        .btn-ghost { font-size: 13px; color: var(--muted); transition: color 0.2s; }
        .btn-ghost:hover { color: var(--text); }
        .btn-cta {
            background: var(--accent); color: #0a0a0a;
            padding: 9px 22px; border-radius: 100px;
            font-weight: 700; font-size: 13px;
            transition: transform 0.2s;
        }
        .btn-cta:hover { transform: translateY(-1px); }

        /* Sections */
        section { padding: 6rem 6vw; }
        .container { max-width: 960px; margin: 0 auto; }

        /* Hero */
        .hero { padding: 7rem 6vw 5rem; text-align: center; }
        .hero-badge {
            display: inline-block;
            background: rgba(232,255,71,0.08); border: 1px solid rgba(232,255,71,0.2);
            color: var(--accent); font-size: 12px; font-weight: 600;
            padding: 5px 16px; border-radius: 100px; letter-spacing: 0.08em;
            text-transform: uppercase; margin-bottom: 2rem;
        }
        .hero h1 {
            font-family: var(--display);
            font-size: clamp(48px, 8vw, 88px);
            line-height: 0.95; letter-spacing: 0.01em;
            margin-bottom: 1.5rem;
        }
        .hero h1 em { font-family: var(--serif); font-style: italic; color: var(--accent); }
        .hero-sub {
            font-size: clamp(16px, 2.5vw, 20px); color: var(--muted);
            max-width: 560px; margin: 0 auto 2.5rem;
        }
        .hero-cta-group { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn-hero-primary {
            background: var(--accent); color: #0a0a0a;
            padding: 16px 36px; border-radius: 100px;
            font-weight: 700; font-size: 16px; transition: transform 0.2s;
        }
        .btn-hero-primary:hover { transform: translateY(-2px); }
        .hero-meta { font-size: 12px; color: var(--muted); margin-top: 1.25rem; }

        /* Problem */
        .problem { background: #0d0d0d; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
        .section-tag { font-size: 11px; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase; color: var(--accent); margin-bottom: 1rem; }
        .section-title { font-family: var(--display); font-size: clamp(32px, 5vw, 56px); line-height: 1; margin-bottom: 1.5rem; }
        .section-title em { font-family: var(--serif); font-style: italic; }
        .section-text { font-size: 16px; color: var(--muted); max-width: 560px; line-height: 1.7; }

        /* Steps */
        .steps-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 3rem; }
        .step-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 16px; padding: 28px;
        }
        .step-num { font-family: var(--display); font-size: 52px; color: var(--accent); line-height: 1; margin-bottom: 16px; opacity: 0.6; }
        .step-title { font-weight: 600; font-size: 17px; margin-bottom: 10px; }
        .step-text { font-size: 14px; color: var(--muted); line-height: 1.6; }

        /* Features */
        .features-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-top: 3rem; }
        .feature-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 16px; padding: 24px;
            display: flex; gap: 18px; align-items: flex-start;
        }
        .feature-icon { font-size: 24px; flex-shrink: 0; margin-top: 2px; }
        .feature-title { font-weight: 600; font-size: 15px; margin-bottom: 6px; }
        .feature-text { font-size: 13px; color: var(--muted); line-height: 1.5; }

        /* Trial timeline */
        .timeline { display: flex; gap: 16px; margin-top: 3rem; overflow-x: auto; padding-bottom: 8px; }
        .timeline-item {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 12px; padding: 20px 24px; min-width: 180px; flex: 1;
            text-align: center;
        }
        .timeline-day { font-family: var(--display); font-size: 13px; letter-spacing: 0.1em; color: var(--accent); margin-bottom: 6px; }
        .timeline-limit { font-family: var(--display); font-size: 38px; color: var(--text); line-height: 1; margin-bottom: 4px; }
        .timeline-desc { font-size: 12px; color: var(--muted); }

        /* Pricing */
        .pricing-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 3rem; max-width: 660px; }
        .pricing-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 20px; padding: 32px; position: relative;
        }
        .pricing-card.popular { border-color: rgba(99,102,241,0.5); }
        .popular-badge {
            position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
            background: #6366f1; color: #fff; font-size: 11px; font-weight: 700;
            padding: 4px 14px; border-radius: 100px; white-space: nowrap;
        }
        .pricing-plan { font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); margin-bottom: 12px; }
        .pricing-price { font-family: var(--display); font-size: 52px; line-height: 1; margin-bottom: 4px; }
        .pricing-sub { font-size: 13px; color: var(--muted); margin-bottom: 24px; }
        .pricing-features { list-style: none; margin-bottom: 28px; }
        .pricing-features li { font-size: 14px; color: var(--muted); padding: 7px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--border); }
        .pricing-features li:last-child { border: none; }
        .pricing-features li::before { content: '✓'; color: var(--accent); font-weight: 700; flex-shrink: 0; }
        .btn-plan {
            display: block; text-align: center; padding: 14px;
            border-radius: 12px; font-weight: 700; font-size: 15px;
            background: var(--accent); color: #0a0a0a; transition: transform 0.2s;
        }
        .btn-plan:hover { transform: translateY(-2px); }
        .btn-plan.outline { background: transparent; border: 1px solid var(--border); color: var(--text); }

        /* FAQ */
        .faq-list { margin-top: 3rem; display: flex; flex-direction: column; gap: 8px; max-width: 680px; }
        .faq-item {
            background: var(--surface); border: 1px solid var(--border); border-radius: 12px;
            overflow: hidden;
        }
        .faq-q {
            width: 100%; text-align: left; background: none; border: none;
            color: var(--text); font-size: 15px; font-weight: 500; font-family: var(--sans);
            padding: 18px 20px; cursor: pointer;
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
        }
        .faq-a { font-size: 14px; color: var(--muted); padding: 0 20px 18px; line-height: 1.6; }

        /* Footer */
        footer {
            border-top: 1px solid var(--border); padding: 2.5rem 6vw;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
            font-size: 13px; color: var(--muted);
        }

        @media (max-width: 700px) {
            .steps-grid, .features-grid, .pricing-grid { grid-template-columns: 1fr; }
            .nav-cta .btn-ghost { display: none; }
        }
    </style>
</head>
<body x-data="{ faq: null }">

    <!-- NAV -->
    <nav class="nav">
        <div class="nav-logo">DISTINTO <span>ROTEIROS</span></div>
        <div class="nav-cta">
            <a href="<?= raizUrl('/index.php') ?>" class="btn-ghost">Entrar</a>
            <a href="<?= raizUrl('/registro.php') ?>" class="btn-cta">Começar grátis</a>
        </div>
    </nav>

    <!-- HERO -->
    <div class="hero">
        <div class="container">
            <div class="hero-badge">✨ 35 dias grátis · Sem cartão</div>
            <h1>Crie roteiros que<br>viram <em>vídeos</em></h1>
            <p class="hero-sub">A IA que aprende o seu estilo de fala, sua narrativa e sua voz — para criar roteiros prontos para gravar.</p>
            <div class="hero-cta-group">
                <a href="<?= raizUrl('/registro.php') ?>" class="btn-hero-primary">Começar gratuitamente</a>
            </div>
            <p class="hero-meta">35 dias grátis · Sem cartão · Cancele quando quiser</p>
        </div>
    </div>

    <!-- PROBLEMA -->
    <section class="problem">
        <div class="container">
            <div class="section-tag">O problema</div>
            <div class="section-title">Ficar sem ideia<br><em>trava seu crescimento</em></div>
            <p class="section-text">Você sabe o que quer falar, mas na hora de sentar e escrever o roteiro a cabeça esvazia. Ou você escreve, mas na frente da câmera parece artificial — não parece você.</p>
            <p class="section-text" style="margin-top:1rem;">O Distinto Roteiros resolve isso: uma IA que aprende com você, não uma IA genérica que produz conteúdo sem identidade.</p>
        </div>
    </section>

    <!-- SOLUÇÃO / COMO FUNCIONA -->
    <section>
        <div class="container">
            <div class="section-tag">A solução</div>
            <div class="section-title">Como <em>funciona</em></div>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-num">01</div>
                    <div class="step-title">Treine sua base</div>
                    <p class="step-text">Envie textos, PDFs, links de artigos ou vídeos do YouTube. A IA absorve sua narrativa, seu vocabulário e sua forma de pensar.</p>
                </div>
                <div class="step-card">
                    <div class="step-num">02</div>
                    <div class="step-title">IA gera seu roteiro</div>
                    <p class="step-text">Com um briefing simples (ou sem nada), a IA cria um roteiro completo — gancho, desenvolvimento, fechamento e CTA — no seu estilo.</p>
                </div>
                <div class="step-card">
                    <div class="step-num">03</div>
                    <div class="step-title">Você grava</div>
                    <p class="step-text">Leia o roteiro, ajuste o que quiser e grave. Depois registre a performance — views, likes, shares — para a IA aprender o que funciona.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section style="background:#0d0d0d; border-top:1px solid var(--border); border-bottom:1px solid var(--border);">
        <div class="container">
            <div class="section-tag">Recursos</div>
            <div class="section-title">Tudo que você<br><em>precisa</em></div>
            <div class="features-grid">
                <?php foreach ([
                    ['📚', 'Base de Conhecimento', 'Envie até 50 fontes — textos, PDFs, imagens, links, YouTube. A IA absorve tudo e usa para criar roteiros no seu estilo.'],
                    ['📴', 'Modo Leitura Offline', 'Abra o app sem internet e leia todos os seus roteiros pendentes. Perfeito para momentos antes de gravar.'],
                    ['⚡', 'Geração com IA Groq', 'Roteiros gerados em segundos com o modelo mais rápido do mercado. Briefing simples → roteiro completo.'],
                    ['📊', 'Score de Performance', 'Registre as métricas de cada vídeo e o sistema pontua qual tipo de roteiro gera mais resultados.'],
                    ['🎯', 'Gancho em 3 Segundos', 'Todo roteiro começa com um gancho estruturado para prender a atenção nos primeiros 3 segundos.'],
                    ['🔒', 'Dados Isolados', 'Cada conta tem sua base de conhecimento 100% isolada. Nenhum dado é compartilhado entre usuários.'],
                ] as [$icon, $titulo, $desc]): ?>
                <div class="feature-card">
                    <div class="feature-icon"><?= $icon ?></div>
                    <div>
                        <div class="feature-title"><?= $titulo ?></div>
                        <p class="feature-text"><?= $desc ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- TRIAL -->
    <section>
        <div class="container">
            <div class="section-tag">35 dias grátis</div>
            <div class="section-title">Sem cartão.<br><em>Sem risco.</em></div>
            <p class="section-text">Comece hoje e use à vontade. Os limites diários existem apenas para você não saturar a IA logo de cara — eles aumentam naturalmente conforme você usa.</p>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-day">DIA 1</div>
                    <div class="timeline-limit">35</div>
                    <div class="timeline-desc">roteiros — bora criar tudo!</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-day">DIA 2</div>
                    <div class="timeline-limit">10</div>
                    <div class="timeline-desc">roteiros por dia</div>
                </div>
                <div class="timeline-item" style="border-color:rgba(232,255,71,0.15);">
                    <div class="timeline-day">DIA 3 – 35</div>
                    <div class="timeline-limit" style="color:var(--accent);">4</div>
                    <div class="timeline-desc">roteiros por dia · ritmo sustentável</div>
                </div>
                <div class="timeline-item" style="border-color:rgba(99,102,241,0.3);">
                    <div class="timeline-day">ASSINANTE</div>
                    <div class="timeline-limit" style="color:#818cf8;">∞</div>
                    <div class="timeline-desc">sem limite diário</div>
                </div>
            </div>
        </div>
    </section>

    <!-- PREÇO -->
    <section style="background:#0d0d0d; border-top:1px solid var(--border); border-bottom:1px solid var(--border);">
        <div class="container" style="text-align:center;">
            <div class="section-tag" style="text-align:center;">Planos</div>
            <div class="section-title" style="text-align:center;">Simples e <em>honesto</em></div>
            <div class="pricing-grid" style="margin-left:auto; margin-right:auto;">

                <!-- Mensal -->
                <div class="pricing-card">
                    <div class="pricing-plan">Mensal</div>
                    <div class="pricing-price">R$<?= $precoMsStr ?></div>
                    <div class="pricing-sub">por mês</div>
                    <ul class="pricing-features">
                        <li>Roteiros ilimitados</li>
                        <li>50 fontes de conhecimento</li>
                        <li>Modo leitura offline</li>
                        <li>IA aprende seu estilo</li>
                        <li>Cancele quando quiser</li>
                    </ul>
                    <a href="<?= raizUrl('/registro.php') ?>" class="btn-plan outline">Começar grátis</a>
                </div>

                <!-- Anual -->
                <div class="pricing-card popular">
                    <div class="popular-badge">MAIS POPULAR · ECONOMIZE R$<?= number_format($economiaAno, 0, ',', '.') ?></div>
                    <div class="pricing-plan" style="color:#818cf8;">Anual</div>
                    <div class="pricing-price" style="color:var(--text);">R$<?= $precoAnStr ?></div>
                    <div class="pricing-sub"><span style="color:#818cf8;">R$<?= $mensal_eq ?>/mês</span> · cobrado uma vez</div>
                    <ul class="pricing-features">
                        <li>Tudo do mensal</li>
                        <li style="color:var(--accent);">~1,8 meses grátis</li>
                        <li>Roteiros ilimitados</li>
                        <li>50 fontes de conhecimento</li>
                        <li>Prioridade em novos recursos</li>
                    </ul>
                    <a href="<?= raizUrl('/registro.php') ?>" class="btn-plan">Começar grátis</a>
                </div>

            </div>
            <p style="font-size:13px; color:var(--muted); margin-top:1.5rem;">🔒 Pagamento seguro via Abacate Pay · PIX ou cartão</p>
        </div>
    </section>

    <!-- FAQ -->
    <section>
        <div class="container">
            <div class="section-tag">Dúvidas</div>
            <div class="section-title"><em>Perguntas</em> frequentes</div>
            <div class="faq-list">
                <?php foreach ([
                    ['Preciso de cartão para começar?', 'Não. O trial de 35 dias é 100% gratuito, sem cartão. Você escolhe assinar quando quiser.'],
                    ['O que acontece quando o trial expira?', 'Seus roteiros existentes ficam disponíveis para leitura. Para criar novos, é necessário assinar um plano.'],
                    ['A IA realmente aprende o meu estilo?', 'Sim. Quanto mais você usar a base de conhecimento (seus textos, links, vídeos) e registrar métricas, mais personalizada fica a geração.'],
                    ['Posso cancelar quando quiser?', 'Sim, a qualquer momento. Você mantém o acesso até o fim do período pago.'],
                    ['Minha base de conhecimento é privada?', 'Completamente. Cada usuário tem seus dados isolados. Nenhuma informação é compartilhada entre contas.'],
                    ['O roteiro gerado parece robô?', 'O objetivo é justamente o contrário. A IA usa sua base de conhecimento para gerar no seu vocabulário e tom de voz. Quanto mais você treina, mais natural fica.'],
                    ['Posso usar sem internet?', 'Sim. O Modo Leitura Offline salva seus roteiros pendentes localmente para você ler a qualquer hora, inclusive sem sinal.'],
                    ['E se eu não gostar?', 'Você não paga nada nos primeiros 35 dias. Se assinar e não se adaptar, cancele e pronto.'],
                ] as $i => [$q, $a]): ?>
                <div class="faq-item">
                    <button class="faq-q" @click="faq = faq === <?= $i ?> ? null : <?= $i ?>">
                        <span><?= $q ?></span>
                        <span x-text="faq === <?= $i ?> ? '−' : '+'" style="font-size:20px; color:var(--accent); flex-shrink:0;"></span>
                    </button>
                    <div class="faq-a" x-show="faq === <?= $i ?>" x-collapse><?= $a ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- RODAPÉ / CTA FINAL -->
    <section style="text-align:center; padding:5rem 6vw 6rem;">
        <div class="container">
            <div class="section-title">Pronto para criar<br><em>roteiros que convencem?</em></div>
            <p style="font-size:16px; color:var(--muted); margin:1.5rem auto 2.5rem; max-width:480px;">Comece hoje mesmo. 35 dias grátis, sem cartão, sem compromisso.</p>
            <a href="<?= raizUrl('/registro.php') ?>" class="btn-hero-primary">Criar minha conta grátis</a>
        </div>
    </section>

    <footer>
        <div>© <?= date('Y') ?> Distinto Roteiros</div>
        <div style="display:flex; gap:20px;">
            <a href="<?= raizUrl('/index.php') ?>">Entrar</a>
            <a href="<?= raizUrl('/registro.php') ?>">Criar conta</a>
        </div>
    </footer>

</body>
</html>
