<?php
/**
 * Template 15 Anos / Audiovisual
 */
?>
<style>
    .theme-15anos { --accent: #7c3aed; }
    .type-15anos { background-color: #000; color: #fff; }
    .type-15anos h1 { font-family: 'Montserrat', sans-serif; font-weight: 900; font-size: 82px; letter-spacing: -4px; line-height: 0.8; margin: 0; color: #fff; }
    .type-15anos h1 span { color: var(--accent); }
    
    .type-15anos .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1px; background: #333; border: 1px solid #333; width: 100%; }
    .type-15anos .grid-item { background: #000; padding: 40px; }
    .type-15anos .grid-item h3 { color: var(--accent); margin-top: 0; font-size: 14px; text-transform: uppercase; }
    
    .type-15anos .price-tag { font-size: 72px; font-weight: 900; letter-spacing: -2px; color: #fff; }
    
    @media (max-width: 768px) {
        .type-15anos h1 { font-size: 48px; letter-spacing: -2px; }
    }
</style>

<div class="theme-15anos">
    <!-- Slide 1: Hero -->
    <section class="proposal-page">
        <div class="page-content">
            <h1>IT'S HER <br><span>TIME</span>.</h1>
            <p style="font-size: 24px; font-weight: 700; margin-top: 20px; color: var(--accent);"><?= $cliente ?></p>
        </div>
    </section>

    <!-- Slide 2: The Concept -->
    <section class="proposal-page">
        <div class="page-content">
            <div style="font-size: 14px; text-transform: uppercase; letter-spacing: 4px; color: #666; margin-bottom: 20px;">The Concept</div>
            <div style="font-size: 48px; line-height: 1.1; font-weight: 600; max-width: 800px;">
                <?= $dados['secoes']['intro'] ?? 'Transformando o sonho dos 15 anos em uma experiência audiovisual cinematográfica.' ?>
            </div>
        </div>
    </section>

    <!-- Slide 3: Grid Details -->
    <section class="proposal-page">
        <div class="page-content">
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
        </div>
    </section>

    <!-- Slide 4: Investimento -->
    <section class="proposal-page">
        <div class="page-content" style="align-items: center; text-align: center;">
            <div style="color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px;">O Investimento</div>
            <div class="price-tag"><?= formatarMoeda($proposta['valor_total']) ?></div>
            <p style="color: #666; margin-top: 40px; font-size: 12px; letter-spacing: 2px; text-transform: uppercase;">Digital Presence by DISTINTO</p>
        </div>
    </section>
</div>
