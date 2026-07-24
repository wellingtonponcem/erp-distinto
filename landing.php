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
    <title>Meus Roteiros — IA que aprende o seu estilo</title>
    <meta name="description" content="Crie roteiros para vídeos com IA que aprende o seu estilo. 35 dias grátis, sem cartão.">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
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

        /* ── Nav ────────────────────────────────── */
        .nav {
            position: sticky; top: 0; z-index: 100;
            background: rgba(10,10,10,0.92); backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            padding: 0 6vw;
            display: flex; align-items: center; justify-content: space-between;
            height: 60px;
        }
        .nav-logo { display: flex; align-items: center; gap: 6px; font-size: 20px; letter-spacing: -0.01em; }
        .nav-logo span:first-child { font-family: var(--display); color: var(--text); text-transform: uppercase; }
        .nav-logo span:last-child  { font-family: var(--serif); font-style: italic; color: var(--accent); font-weight: 400; }
        .nav-cta { display: flex; gap: 12px; align-items: center; }
        .btn-ghost { font-size: 13px; color: var(--muted); transition: color 0.2s; }
        .btn-ghost:hover { color: var(--text); }
        .btn-cta {
            background: var(--accent); color: #0a0a0a;
            padding: 9px 22px; border-radius: 100px;
            font-weight: 700; font-size: 13px; transition: transform 0.2s;
        }
        .btn-cta:hover { transform: translateY(-1px); }

        /* ── Layout helpers ─────────────────────── */
        section { padding: 6rem 6vw; }
        .container { max-width: 980px; margin: 0 auto; }
        .section-tag { font-size: 11px; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase; color: var(--accent); margin-bottom: 1rem; }
        .section-title { font-family: var(--display); font-size: clamp(32px,5vw,56px); line-height: 1; margin-bottom: 1.5rem; }
        .section-title em { font-family: var(--serif); font-style: italic; }
        .section-text { font-size: 16px; color: var(--muted); max-width: 560px; line-height: 1.7; }

        /* ── Hero ───────────────────────────────── */
        .hero { padding: 6rem 6vw 2rem; }
        .hero-grid {
            display: grid;
            grid-template-columns: 1fr 1.1fr;
            gap: 4rem;
            align-items: center;
            max-width: 1180px;
            margin: 0 auto;
        }
        .hero-badge {
            display: inline-block;
            background: rgba(232,255,71,0.08); border: 1px solid rgba(232,255,71,0.2);
            color: var(--accent); font-size: 12px; font-weight: 600;
            padding: 5px 16px; border-radius: 100px; letter-spacing: 0.08em;
            text-transform: uppercase; margin-bottom: 1.5rem;
        }
        .hero h1 {
            font-family: var(--display);
            font-size: clamp(42px, 4.8vw, 72px);
            line-height: 0.95; letter-spacing: 0.01em;
            margin-bottom: 1.25rem;
        }
        .hero h1 em { font-family: var(--serif); font-style: italic; color: var(--accent); }
        .hero-sub { font-size: 16px; color: var(--muted); max-width: 440px; margin-bottom: 2rem; line-height: 1.65; }
        .hero-cta-group { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-start; }
        .btn-hero-primary {
            background: var(--accent); color: #0a0a0a;
            padding: 15px 34px; border-radius: 100px;
            font-weight: 700; font-size: 15px; transition: transform 0.2s;
        }
        .btn-hero-primary:hover { transform: translateY(-2px); }
        .hero-meta { font-size: 12px; color: var(--muted); margin-top: 1rem; }

        /* ── Mock Browser ───────────────────────── */
        .mock-wrap {
            position: relative;
        }
        .mock-browser {
            background: #0d0d0d;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 18px;
            overflow: hidden;
            box-shadow:
                0 32px 80px rgba(0,0,0,0.6),
                0 0 0 1px rgba(255,255,255,0.04),
                0 0 80px 20px rgba(232,255,71,0.14),
                0 0 160px 60px rgba(232,255,71,0.07);
        }
        .mock-topbar {
            background: #1c1c1c;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 6px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .mock-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
        .mock-dot.r { background: #ff5f57; }
        .mock-dot.y { background: #febc2e; }
        .mock-dot.g { background: #28c840; }
        .mock-url { font-size: 11px; color: #444; margin-left: 10px; font-family: monospace; }

        .mock-body { padding: 20px; }
        .mock-label {
            font-size: 9px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase;
            color: #555; margin-bottom: 8px;
        }
        .mock-title { font-size: 13px; font-weight: 600; color: #E8FF47; margin-bottom: 12px; }
        .mock-typewriter-wrap {
            background: #0a0a0a;
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 10px;
            padding: 16px;
            min-height: 130px;
            margin-bottom: 14px;
        }
        #tw-text {
            font-size: 13px; line-height: 1.65; color: #ccc;
        }
        .cursor-blink {
            display: inline-block; width: 2px; height: 14px;
            background: #E8FF47; margin-left: 2px;
            vertical-align: text-bottom;
            animation: blink 0.9s step-end infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }

        .mock-pills { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
        .mock-pill {
            font-size: 11px; padding: 4px 12px; border-radius: 100px;
            border: 1px solid rgba(255,255,255,0.08); color: #666;
        }
        .mock-pill.active { background: rgba(232,255,71,0.1); border-color: rgba(232,255,71,0.3); color: #E8FF47; }

        .mock-metrics { display: flex; gap: 10px; }
        .mock-metric {
            flex: 1; background: #111; border: 1px solid rgba(255,255,255,0.06);
            border-radius: 8px; padding: 10px; text-align: center;
        }
        .mock-metric-val { font-family: 'Bebas Neue', sans-serif; font-size: 22px; color: #E8FF47; line-height: 1; }
        .mock-metric-lbl { font-size: 10px; color: #555; margin-top: 3px; }

        /* ── Score Spotlight ────────────────────── */
        .score-section { background: #0d0d0d; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
        .score-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 4rem; align-items: center;
        }
        .score-visual-wrap { position: relative; }
        .score-mock-card {
            background: #111;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            overflow: hidden;
        }
        .score-mock-header {
            padding: 16px 20px;
            background: #161616;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            font-size: 12px; color: #555; font-weight: 600; letter-spacing: 0.08em;
        }
        .score-row {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .score-row:last-child { border: none; }
        .score-badge {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Bebas Neue', sans-serif; font-size: 18px;
            flex-shrink: 0;
        }
        .score-badge.s-hi  { background: rgba(232,255,71,0.12); color: #E8FF47; }
        .score-badge.s-mid { background: rgba(255,255,255,0.06); color: #aaa; }
        .score-badge.s-lo  { background: rgba(255,80,80,0.08); color: #f87; }
        .score-row-info { flex: 1; min-width: 0; }
        .score-row-title { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #ddd; }
        .score-row-meta  { font-size: 11px; color: #555; margin-top: 2px; }
        .score-bar-wrap  { width: 80px; flex-shrink: 0; }
        .score-bar-bg    { height: 4px; background: rgba(255,255,255,0.06); border-radius: 2px; }
        .score-bar-fill  { height: 4px; border-radius: 2px; transition: width 1.2s cubic-bezier(0.4,0,0.2,1); }

        .score-stat-pill {
            position: absolute; right: -18px; top: 50%; transform: translateY(-50%);
            background: var(--accent); color: #0a0a0a;
            border-radius: 16px; padding: 14px 20px; text-align: center;
            box-shadow: 0 16px 40px rgba(232,255,71,0.25);
        }
        .score-stat-pill-val { font-family: 'Bebas Neue', sans-serif; font-size: 36px; line-height: 1; }
        .score-stat-pill-lbl { font-size: 10px; font-weight: 700; letter-spacing: 0.1em; opacity: 0.7; }

        /* ── Problem ───────────────────────────── */
        .problem { background: #0d0d0d; border-bottom: 1px solid var(--border); }

        /* ── Steps ─────────────────────────────── */
        .steps-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 3rem; }
        .step-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 16px; padding: 28px; position: relative; overflow: hidden;
        }
        .step-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, var(--accent) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .step-card:hover::before { opacity: 1; }
        .step-num { font-family: var(--display); font-size: 52px; color: var(--accent); line-height: 1; margin-bottom: 16px; opacity: 0.5; }
        .step-title { font-weight: 600; font-size: 17px; margin-bottom: 10px; }
        .step-text { font-size: 14px; color: var(--muted); line-height: 1.6; }

        /* ── Features ───────────────────────────── */
        .features-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-top: 3rem; }
        .feature-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 16px; padding: 24px;
            display: flex; gap: 18px; align-items: flex-start;
            transition: border-color 0.2s;
        }
        .feature-card:hover { border-color: rgba(232,255,71,0.15); }
        .feature-icon { font-size: 24px; flex-shrink: 0; margin-top: 2px; }
        .feature-title { font-weight: 600; font-size: 15px; margin-bottom: 6px; }
        .feature-text { font-size: 13px; color: var(--muted); line-height: 1.5; }

        /* ── Timeline ───────────────────────────── */
        .timeline { display: flex; gap: 16px; margin-top: 3rem; overflow-x: auto; padding-bottom: 8px; }
        .timeline-item {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 12px; padding: 20px 24px; min-width: 180px; flex: 1; text-align: center;
        }
        .timeline-day { font-family: var(--display); font-size: 13px; letter-spacing: 0.1em; color: var(--accent); margin-bottom: 6px; }
        .timeline-limit { font-family: var(--display); font-size: 38px; color: var(--text); line-height: 1; margin-bottom: 4px; }
        .timeline-desc { font-size: 12px; color: var(--muted); }

        /* ── Pricing ────────────────────────────── */
        .pricing-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 3rem; max-width: 660px; }
        .pricing-card { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 32px; position: relative; }
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
        .btn-plan { display: block; text-align: center; padding: 14px; border-radius: 12px; font-weight: 700; font-size: 15px; background: var(--accent); color: #0a0a0a; transition: transform 0.2s; }
        .btn-plan:hover { transform: translateY(-2px); }
        .btn-plan.outline { background: transparent; border: 1px solid var(--border); color: var(--text); }

        /* ── FAQ ────────────────────────────────── */
        .faq-list { margin-top: 3rem; display: flex; flex-direction: column; gap: 8px; max-width: 680px; }
        .faq-item { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
        .faq-q {
            width: 100%; text-align: left; background: none; border: none;
            color: var(--text); font-size: 15px; font-weight: 500; font-family: var(--sans);
            padding: 18px 20px; cursor: pointer;
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
        }
        .faq-a { font-size: 14px; color: var(--muted); padding: 0 20px 18px; line-height: 1.6; }

        /* ── Footer ─────────────────────────────── */
        footer {
            border-top: 1px solid var(--border); padding: 2.5rem 6vw;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
            font-size: 13px; color: var(--muted);
        }

        /* ── Responsive ─────────────────────────── */
        @media (max-width: 800px) {
            .hero-grid        { grid-template-columns: 1fr; gap: 3rem; }
            .score-grid       { grid-template-columns: 1fr; }
            .score-stat-pill  { position: static; transform: none; margin-top: 1.5rem; display: inline-block; }
            .steps-grid, .features-grid, .pricing-grid { grid-template-columns: 1fr; }
            .nav-cta .btn-ghost { display: none; }
        }
    </style>
</head>
<body x-data="{ faq: null }">

    <!-- NAV -->
    <nav class="nav">
        <div class="nav-logo"><span>MEUS</span>&nbsp;<span>Roteiros</span></div>
        <div class="nav-cta">
            <a href="<?= raizUrl('/login-roteiros.php') ?>" class="btn-ghost">Entrar</a>
            <a href="<?= raizUrl('/registro.php') ?>" class="btn-cta">Começar grátis</a>
        </div>
    </nav>

    <!-- HERO -->
    <div class="hero">
        <div class="hero-grid">

            <!-- Texto -->
            <div>
                <div class="hero-badge">✨ 35 dias grátis · Sem cartão</div>
                <h1>Crie roteiros<br>que viram <em>vídeos</em></h1>
                <p class="hero-sub">A IA que aprende o seu estilo de fala, sua narrativa e sua voz — para criar roteiros prontos para gravar.</p>
                <div class="hero-cta-group">
                    <a href="<?= raizUrl('/registro.php') ?>" class="btn-hero-primary">Começar gratuitamente</a>
                </div>
                <p class="hero-meta">35 dias grátis · Sem cartão · Cancele quando quiser</p>
            </div>

            <!-- Mock animado -->
            <div class="mock-wrap">
                <div class="mock-browser">
                    <div class="mock-topbar">
                        <span class="mock-dot r"></span>
                        <span class="mock-dot y"></span>
                        <span class="mock-dot g"></span>
                        <span class="mock-url">meusroteiros.com · Gerar roteiro</span>
                    </div>
                    <div class="mock-body">
                        <div class="mock-pills" style="margin-bottom:12px;">
                            <span class="mock-pill active">Briefing: "Por que você não cresce no YouTube"</span>
                        </div>
                        <div class="mock-label">Roteiro gerado</div>
                        <div class="mock-typewriter-wrap">
                            <span id="tw-text"></span><span class="cursor-blink"></span>
                        </div>
                        <div class="mock-metrics">
                            <div class="mock-metric">
                                <div class="mock-metric-val">94</div>
                                <div class="mock-metric-lbl">Score</div>
                            </div>
                            <div class="mock-metric">
                                <div class="mock-metric-val">48K</div>
                                <div class="mock-metric-lbl">Views</div>
                            </div>
                            <div class="mock-metric">
                                <div class="mock-metric-val">2.1K</div>
                                <div class="mock-metric-lbl">Likes</div>
                            </div>
                            <div class="mock-metric">
                                <div class="mock-metric-val">312</div>
                                <div class="mock-metric-lbl">Shares</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- PROBLEMA -->
    <section class="problem">
        <div class="container">
            <div class="section-tag">O problema</div>
            <div class="section-title">Ficar sem ideia<br><em>trava seu crescimento</em></div>
            <p class="section-text">Você sabe o que quer falar, mas na hora de sentar e escrever o roteiro a cabeça esvazia. Ou você escreve, mas na frente da câmera parece artificial — não parece você.</p>
            <p class="section-text" style="margin-top:1rem;">O Meus Roteiros resolve isso: uma IA que aprende com você, não uma IA genérica que produz conteúdo sem identidade.</p>
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

    <!-- SCORE SPOTLIGHT -->
    <section class="score-section">
        <div class="container">
            <div class="score-grid">

                <!-- Visual -->
                <div class="score-visual-wrap">
                    <div class="score-mock-card" id="score-card">
                        <div class="score-mock-header">Seus roteiros · ordenados por score</div>

                        <div class="score-row">
                            <div class="score-badge s-hi">94</div>
                            <div class="score-row-info">
                                <div class="score-row-title">Por que você não cresce no YouTube</div>
                                <div class="score-row-meta">48K views · 2.1K likes · 312 shares</div>
                            </div>
                            <div class="score-bar-wrap">
                                <div class="score-bar-bg"><div class="score-bar-fill" style="width:0%; background:#E8FF47;" data-w="94%"></div></div>
                            </div>
                        </div>

                        <div class="score-row">
                            <div class="score-badge s-hi">87</div>
                            <div class="score-row-info">
                                <div class="score-row-title">O erro que todo creator comete no início</div>
                                <div class="score-row-meta">31K views · 1.4K likes · 190 shares</div>
                            </div>
                            <div class="score-bar-wrap">
                                <div class="score-bar-bg"><div class="score-bar-fill" style="width:0%; background:#E8FF47;" data-w="87%"></div></div>
                            </div>
                        </div>

                        <div class="score-row">
                            <div class="score-badge s-mid">71</div>
                            <div class="score-row-info">
                                <div class="score-row-title">Como organizar ideias sem travar</div>
                                <div class="score-row-meta">19K views · 880 likes · 95 shares</div>
                            </div>
                            <div class="score-bar-wrap">
                                <div class="score-bar-bg"><div class="score-bar-fill" style="width:0%; background:#aaa;" data-w="71%"></div></div>
                            </div>
                        </div>

                        <div class="score-row">
                            <div class="score-badge s-lo">48</div>
                            <div class="score-row-info">
                                <div class="score-row-title">Meu dia a dia como criador de conteúdo</div>
                                <div class="score-row-meta">7K views · 310 likes · 22 shares</div>
                            </div>
                            <div class="score-bar-wrap">
                                <div class="score-bar-bg"><div class="score-bar-fill" style="width:0%; background:#f87;" data-w="48%"></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Pill flutuante -->
                    <div class="score-stat-pill">
                        <div class="score-stat-pill-val">+2x</div>
                        <div class="score-stat-pill-lbl">mais views<br>nos top 3</div>
                    </div>
                </div>

                <!-- Texto explicativo -->
                <div>
                    <div class="section-tag">Score de Performance</div>
                    <div class="section-title">A IA aprende o que<br><em>gera resultado</em></div>
                    <p class="section-text">Cada roteiro tem um score calculado a partir das métricas reais do seu vídeo — views, likes, comentários e compartilhamentos.</p>
                    <p class="section-text" style="margin-top:1rem;">Com o tempo, a IA passa a gerar novos roteiros inspirados nos padrões dos seus maiores sucessos. Quanto mais você usa, mais afinada fica.</p>
                    <div style="display:flex; gap:20px; margin-top:2rem; flex-wrap:wrap;">
                        <div>
                            <div style="font-family:'Bebas Neue',sans-serif; font-size:40px; color:var(--accent); line-height:1;">Score</div>
                            <div style="font-size:13px; color:var(--muted);">por roteiro</div>
                        </div>
                        <div>
                            <div style="font-family:'Bebas Neue',sans-serif; font-size:40px; color:var(--text); line-height:1;">Ranking</div>
                            <div style="font-size:13px; color:var(--muted);">automático</div>
                        </div>
                        <div>
                            <div style="font-family:'Bebas Neue',sans-serif; font-size:40px; color:var(--text); line-height:1;">Padrões</div>
                            <div style="font-size:13px; color:var(--muted);">aprendidos</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section>
        <div class="container">
            <div class="section-tag">Recursos</div>
            <div class="section-title">Tudo que você<br><em>precisa</em></div>
            <div class="features-grid">
                <?php foreach ([
                    ['📚', 'Base de Conhecimento', 'Envie até 50 fontes — textos, PDFs, imagens, links, YouTube. A IA absorve tudo e usa para criar roteiros no seu estilo.'],
                    ['📴', 'Acesso Offline', 'Salve roteiros pendentes para ler no navegador sem internet. Perfeito para abrir antes de gravar, mesmo sem sinal.'],
                    ['⚡', 'Geração Instantânea', 'De um briefing simples a um roteiro completo em segundos. Gancho, desenvolvimento, fechamento e CTA — prontos para gravar.'],
                    ['🎯', 'Gancho em 3 Segundos', 'Todo roteiro começa com um gancho estruturado para prender a atenção nos primeiros 3 segundos — onde a maioria perde o espectador.'],
                    ['📊', 'Score & Métricas', 'Registre as métricas de cada vídeo. O sistema pontua e ranqueia seus roteiros para que a próxima geração seja ainda melhor.'],
                    ['🔒', 'Dados 100% Isolados', 'Cada conta tem sua base de conhecimento completamente isolada. Nenhum dado é compartilhado entre usuários.'],
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
    <section style="background:#0d0d0d; border-top:1px solid var(--border); border-bottom:1px solid var(--border);">
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
    <section>
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
                        <li>Acesso offline aos roteiros</li>
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
            <p style="font-size:13px; color:var(--muted); margin-top:1.5rem;">🔒 Pagamento seguro via Mercado Pago · PIX, cartão ou boleto</p>
        </div>
    </section>

    <!-- FAQ -->
    <section style="background:#0d0d0d; border-top:1px solid var(--border);">
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
                    ['Preciso instalar alguma coisa?', 'Não. O Meus Roteiros funciona direto no navegador — sem instalação, sem download. E seus roteiros podem ser salvos para leitura offline, mesmo sem internet.'],
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
        <div>© <?= date('Y') ?> Meus Roteiros</div>
        <div style="display:flex; gap:20px;">
            <a href="<?= raizUrl('/login-roteiros.php') ?>">Entrar</a>
            <a href="<?= raizUrl('/registro.php') ?>">Criar conta</a>
        </div>
    </footer>

    <!-- ── Scripts ─────────────────────────────────────── -->
    <script>
    /* ---------- Typewriter ---------- */
    (function () {
        const frases = [
            "GANCHO:\nVocê já perdeu uma hora olhando para o cursor piscando — sem saber como começar o roteiro?\n\nDESENVOLVIMENTO:\nIsso acontece porque a maioria dos criadores começa pelo tema errado. O truque não é ter mais ideias — é ter um sistema que transforma qualquer ideia em roteiro em menos de 2 minutos.",
            "GANCHO:\n80% dos criadores desistem no primeiro ano. Não por falta de talento — mas por falta de consistência.\n\nDESENVOLVIMENTO:\nConsistência não é força de vontade. É ter um processo que funciona mesmo nos dias em que você não está inspirado.",
            "GANCHO:\nO algoritmo não te pune por postar menos — ele te recompensa por prender mais.\n\nDESENVOLVIMENTO:\nUm vídeo com 70% de retenção bate qualquer canal que posta todo dia sem estrutura. O segredo está nos primeiros 8 segundos."
        ];
        let frasei = 0;
        let chari  = 0;
        let apagando = false;
        const el = document.getElementById('tw-text');

        function tick() {
            const frase = frases[frasei];

            if (!apagando) {
                if (chari <= frase.length) {
                    el.textContent = frase.slice(0, chari);
                    chari++;
                    setTimeout(tick, chari === 1 ? 400 : 22);
                } else {
                    setTimeout(() => { apagando = true; tick(); }, 3200);
                }
            } else {
                if (chari > 0) {
                    chari--;
                    el.textContent = frase.slice(0, chari);
                    setTimeout(tick, 8);
                } else {
                    apagando = false;
                    frasei   = (frasei + 1) % frases.length;
                    setTimeout(tick, 500);
                }
            }
        }
        tick();
    })();

    /* ---------- Score bars animadas ---------- */
    (function () {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    document.querySelectorAll('.score-bar-fill').forEach(bar => {
                        bar.style.width = bar.dataset.w;
                    });
                    observer.disconnect();
                }
            });
        }, { threshold: 0.3 });

        const card = document.getElementById('score-card');
        if (card) observer.observe(card);
    })();
    </script>

</body>
</html>
