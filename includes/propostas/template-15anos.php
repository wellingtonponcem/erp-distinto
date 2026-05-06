<?php
/**
 * Template 15 Anos / Audiovisual
 */
?>
<style>
    .type-15anos { background-color: #000; color: #fff; }
    .type-15anos .hero { padding: 120px 60px; border-bottom: 1px solid #333; }
    .type-15anos .hero h1 { font-family: 'Montserrat', sans-serif; font-weight: 900; font-size: 82px; letter-spacing: -4px; line-height: 0.8; margin: 0; color: #fff; }
    .type-15anos .hero span { color: #7c3aed; }
    
    .type-15anos .section { padding: 80px 60px; }
    .type-15anos .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1px; background: #333; border: 1px solid #333; }
    .type-15anos .grid-item { background: #000; padding: 40px; }
    .type-15anos .grid-item h3 { color: #7c3aed; margin-top: 0; font-size: 14px; text-transform: uppercase; }
    
    .type-15anos .footer { padding: 100px 60px; background: #111; }
    .type-15anos .price-tag { font-size: 72px; font-weight: 900; letter-spacing: -2px; color: #fff; }
    
    @media (max-width: 768px) {
        .type-15anos .hero { padding: 80px 20px; }
        .type-15anos .hero h1 { font-size: 48px; letter-spacing: -2px; }
        .type-15anos .section { padding: 40px 20px; }
    }
</style>

<header class="hero animate-fade-in">
    <h1>IT'S HER <span>TIME</span>.</h1>
    <p style="font-size: 24px; font-weight: 700; margin-top: 20px; color: #7c3aed;"><?= $cliente ?></p>
</header>

<section class="section animate-fade-in" style="animation-delay: 0.2s">
    <div style="font-size: 14px; text-transform: uppercase; letter-spacing: 4px; color: #666; margin-bottom: 20px;">The Concept</div>
    <div style="font-size: 32px; line-height: 1.2; font-weight: 600; max-width: 600px;">
        <?= $dados['secoes']['intro'] ?? 'Transformando o sonho dos 15 anos em uma experiência audiovisual cinematográfica.' ?>
    </div>
</section>

<section class="section animate-fade-in" style="animation-delay: 0.4s">
    <div class="grid">
        <div class="grid-item">
            <h3>Visuals</h3>
            <p><?= $dados['secoes']['visuals'] ?? 'Captação 4K com estética de cinema e edição dinâmica.' ?></p>
        </div>
        <div class="grid-item">
            <h3>Experience</h3>
            <p><?= $dados['secoes']['experiencia'] ?? 'Imersão total no evento com cobertura em tempo real.' ?></p>
        </div>
        <div class="grid-item">
            <h3>Deliverables</h3>
            <p><?= $dados['secoes']['entregaveis'] ?? 'Filme principal, teaser para redes sociais e fotos tratadas.' ?></p>
        </div>
    </div>
</section>

<section class="footer animate-fade-in" style="animation-delay: 0.6s">
    <div style="color: #7c3aed; font-weight: 700; text-transform: uppercase; letter-spacing: 2px;">O Investimento</div>
    <div class="price-tag"><?= formatarMoeda($proposta['valor_total']) ?></div>
    <p style="color: #666; margin-top: 20px;">Digital Presence by DISTINTO</p>
</section>
