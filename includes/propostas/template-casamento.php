<?php
/**
 * Template Casamento
 */
?>
<style>
    .type-casamento { background-color: #faf9f6; }
    .type-casamento .hero-bg { 
        background: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.2)), url('<?= $dados['capa_imagem'] ?? 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=2069' ?>'); 
        background-size: cover;
        background-position: center;
    }
    .type-casamento h1 { font-family: 'Playfair Display', serif; font-size: 72px; margin: 0; color: white; }
    .type-casamento p.subtitle { font-family: 'Inter', sans-serif; text-transform: uppercase; letter-spacing: 4px; font-size: 14px; margin-bottom: 20px; color: white; }
    
    .type-casamento .section-title { font-family: 'Playfair Display', serif; font-size: 54px; color: #4a3728; margin-bottom: 30px; font-style: italic; }
    .type-casamento .intro-text { font-family: 'Inter', sans-serif; font-size: 22px; line-height: 1.8; color: #6b5a4c; max-width: 800px; margin: 0 auto; }
    
    .type-casamento .investment-box { background: #fff; padding: 80px; border: 1px solid #e8e6e1; display: inline-block; }
    .type-casamento .price { font-family: 'Playfair Display', serif; font-size: 64px; color: #4a3728; margin: 20px 0; }
    
    @media (max-width: 768px) {
        .type-casamento h1 { font-size: 42px; }
    }
</style>

<div class="theme-casamento">
    <!-- Slide 1: Hero -->
    <section class="proposal-page hero-bg">
        <div class="page-content" style="align-items: center; text-align: center;">
            <p class="subtitle">O Começo de uma Nova História</p>
            <h1><?= $cliente ?></h1>
            <div style="width: 60px; height: 1px; background: white; margin-top: 30px;"></div>
        </div>
    </section>

    <!-- Slide 2: O Sonho -->
    <section class="proposal-page">
        <div class="page-content" style="text-align: center;">
            <h2 class="section-title">O Sonho</h2>
            <div class="intro-text">
                <?= $dados['secoes']['intro'] ?? 'Texto emocional gerado pela IA sobre o casamento.' ?>
            </div>
        </div>
    </section>

    <!-- Slide 3: A Visão -->
    <section class="proposal-page">
        <div class="page-content" style="text-align: center;">
            <h2 class="section-title">A Nossa Visão</h2>
            <div class="intro-text">
                <?= $dados['secoes']['visao'] ?? 'Texto sobre o cuidado artístico da Distinto.' ?>
            </div>
        </div>
    </section>

    <!-- Slide 4: Investimento -->
    <section class="proposal-page">
        <div class="page-content" style="align-items: center; text-align: center;">
            <div class="investment-box">
                <p style="text-transform: uppercase; letter-spacing: 2px; font-size: 12px; color: #a39385;">Investimento para o Grande Dia</p>
                <div class="price"><?= formatarMoeda($proposta['valor_total']) ?></div>
                <div style="font-size: 14px; color: #a39385;">Condições exclusivas válidas por 15 dias.</div>
            </div>
            <p style="margin-top: 60px; color: #a39385; font-size: 13px;">Distinto Eventos | <?= $configEmpresa['nome'] ?></p>
        </div>
    </section>
</div>
