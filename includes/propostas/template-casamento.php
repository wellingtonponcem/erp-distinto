<?php
/**
 * Template Casamento
 */
?>
<style>
    .type-casamento { background-color: #faf9f6; }
    .type-casamento .hero { 
        height: 80vh; 
        background: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.2)), url('<?= $dados['capa_imagem'] ?? 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=2069' ?>'); 
        background-size: cover;
        background-position: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: white;
        text-align: center;
        padding: 40px;
    }
    .type-casamento .hero h1 { font-family: 'Playfair Display', serif; font-size: 72px; margin: 0; }
    .type-casamento .hero p { font-family: 'Inter', sans-serif; text-transform: uppercase; letter-spacing: 4px; font-size: 14px; margin-top: 10px; }
    
    .type-casamento .section { padding: 100px 60px; max-width: 800px; margin: 0 auto; text-align: center; }
    .type-casamento .section-title { font-family: 'Playfair Display', serif; font-size: 42px; color: #4a3728; margin-bottom: 30px; font-style: italic; }
    
    .type-casamento .intro-text { font-family: 'Inter', sans-serif; font-size: 18px; line-height: 1.8; color: #6b5a4c; }
    
    .type-casamento .investment { background: #fff; padding: 60px; border: 1px solid #e8e6e1; margin-top: 40px; }
    .type-casamento .price { font-family: 'Playfair Display', serif; font-size: 54px; color: #4a3728; margin: 20px 0; }
    
    @media (max-width: 768px) {
        .type-casamento .hero h1 { font-size: 42px; }
        .type-casamento .section { padding: 60px 20px; }
    }
</style>

<header class="hero animate-fade-in">
    <p>O Começo de uma Nova História</p>
    <h1><?= $cliente ?></h1>
    <div style="width: 60px; height: 1px; background: white; margin-top: 30px;"></div>
</header>

<section class="section animate-fade-in" style="animation-delay: 0.2s">
    <h2 class="section-title">O Sonho</h2>
    <div class="intro-text">
        <?= $dados['secoes']['intro'] ?? 'Texto emocional gerado pela IA sobre o casamento.' ?>
    </div>
</section>

<section class="section animate-fade-in" style="animation-delay: 0.4s">
    <h2 class="section-title">A Nossa Visão</h2>
    <div class="intro-text">
        <?= $dados['secoes']['visao'] ?? 'Texto sobre o cuidado artístico da Distinto.' ?>
    </div>
</section>

<section class="section animate-fade-in" style="animation-delay: 0.6s">
    <div class="investment">
        <p style="text-transform: uppercase; letter-spacing: 2px; font-size: 12px; color: #a39385;">Investimento para o Grande Dia</p>
        <div class="price"><?= formatarMoeda($proposta['valor_total']) ?></div>
        <div style="font-size: 14px; color: #a39385;">Condições exclusivas válidas por 15 dias.</div>
    </div>
</section>

<footer style="padding: 60px; text-align: center; color: #a39385; font-size: 13px;">
    <p>Distinto Eventos | <?= $configEmpresa['nome'] ?></p>
</footer>
