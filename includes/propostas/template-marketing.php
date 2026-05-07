<?php
/**
 * Template Marketing Digital - Modelo DISTINCTO
 */
?>
<div class="theme-marketing">
    <!-- Slide 1: Hero (Capa seguindo o modelo exato) -->
    <section class="proposal-page">
        <div class="page-content" style="grid-column: 1; justify-content: center; padding: 0;">
            <h1 style="font-family: var(--font-heading);font-weight: 800;font-size: 3rem;line-height: 1;margin: 0;text-transform: uppercase;letter-spacing: -2px;color: #000; width: 80%;">
                <?= !empty($proposta['titulo_refinado']) ? $proposta['titulo_refinado'] : (!empty($proposta['titulo']) ? $proposta['titulo'] : 'PROPOSTA ESTRATÉGICA') ?>
            </h1>
            <?php if (!empty($proposta['subtitulo'])): ?>
            <p style="font-size: 14px; text-transform: uppercase; letter-spacing: 3px; color: rgba(0,0,0,0.4); font-weight: 700; margin-top: 40px; line-height: 1.4;">
                <?= $proposta['subtitulo'] ?>
            </p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Slide 2: Introdução / Missão -->
    <section class="proposal-page dark-page">
        <!-- Coluna 1: Título de Impacto -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #fff;width: 60%;">
                <?php 
                    $tipo = strtolower($proposta['tipo_projeto'] ?? $proposta['titulo'] ?? '');
                    if (strpos($tipo, 'vídeo') !== false || strpos($tipo, 'video') !== false || strpos($tipo, 'filmmaker') !== false) {
                        echo "CINEMATIC NARRATIVES THAT SELL.";
                    } else if (strpos($tipo, 'design') !== false) {
                        echo "VISUAL IDENTITY THAT COMMANDS RESPECT.";
                    } else {
                        echo "STRATEGIC PLANNING THAT MAKES SENSE.";
                    }
                ?>
            </h2>
        </div>

        <!-- Coluna 2: Texto de Boas-vindas -->
        <div class="page-content" style="grid-column: 2; justify-content: center; padding-left: 40px;">
            <div class="mission-text" style="color: #fff; font-size: 15px; line-height: 1.6; opacity: 0.9;">
                <h3 style="font-family: var(--font-heading); font-size: 32px; font-weight: 800; margin-bottom: 25px; text-transform: uppercase; color: #fff;">
                    OLÁ <?= explode(' ', trim($proposta['cliente_nome'] ?? 'CLIENTE'))[0] ?>!
                </h3>
                <p style="font-weight: 700; margin-bottom: 20px;">Seja bem-vindo à Poncem Studio | Distinto.</p>
                <p style="margin-bottom: 15px;">Aqui, não somos apenas uma agência. Somos estrategistas que transformam negócios em marcas fortes, relevantes e altamente lucrativas.</p>
                <p style="margin-bottom: 15px;">Nossa missão é clara: traduzir a essência de empresas em posicionamento, comunicação inteligente e execução de alto padrão. Atuamos no Brasil e na Europa, impactando mercados como saúde, indústria, serviços de alto valor e automotivo.</p>
                <p style="margin-bottom: 15px;">Nosso trabalho vai muito além de criar posts ou alimentar redes sociais. Desenvolvemos posicionamento, estratégia e narrativa. Entregamos clareza, autoridade e diferenciação para marcas que entenderam que se posicionar corretamente não é uma opção, é uma necessidade para quem busca crescimento, relevância e escala.</p>
                <p style="margin-bottom: 15px;">Se você chegou até aqui, é porque sabe que sua empresa carrega um potencial que precisa ser visto, percebido e reconhecido. E é exatamente isso que fazemos: potencializamos negócios e transformamos marcas em referências no seu mercado.</p>
                <p style="font-weight: 700; margin-top: 25px;">Vamos juntos?</p>
            </div>
        </div>

        <!-- Coluna 3: Gradiente Abstrato -->
        <div class="side-gradient-container" style="grid-column: 3; position: relative; height: 100%; overflow: hidden;">
            <div class="abstract-gradient"></div>
        </div>
    </section>

    <!-- Slide 3: Objetivo do Projeto -->
    <section class="proposal-page">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 90%;">
                PARA ESTE PROJETO, QUAL SERÁ O NOSSO OBJETIVO?
            </h2>
        </div>

        <!-- Coluna 2: Texto Estratégico (IA) -->
        <div class="page-content" style="grid-column: 2; justify-content: center; padding-left: 40px;">
            <div class="objective-text" style="color: #333; font-size: 15px; line-height: 1.6; opacity: 0.9;">
                <?php if (!empty($dados['secoes']['objetivo'])): ?>
                    <?= nl2br($dados['secoes']['objetivo']) ?>
                <?php else: ?>
                    Após uma análise do posicionamento estratégico da marca, identificamos uma oportunidade de fortalecer sua percepção de valor e autoridade. Nosso foco é claro: gerar resultados reais e posicionar seu negócio como referência no mercado.
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Slide 4: Investimento -->
    <section class="proposal-page">
        <div class="page-content" style="align-items: center; text-align: center;">
            <div style="font-family: var(--font-heading); font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 2px; color: #000; margin-bottom: 40px;">
                04. Investimento & Parceria
            </div>
            <div style="background: #000; color: #fff; padding: 60px 100px; border-radius: 0; position: relative;">
                <p style="text-transform: uppercase; font-size: 14px; letter-spacing: 2px; opacity: 0.6; margin-bottom: 10px;">Valor Mensal do Projeto</p>
                <div style="font-family: var(--font-heading); font-size: 72px; font-weight: 800;"><?= formatarMoeda($proposta['valor_total']) ?></div>
                <div style="margin-top: 30px; font-size: 14px; opacity: 0.5;">
                    Válido até <?= formatarData($proposta['validade'] ?? date('Y-m-d', strtotime('+7 days'))) ?>
                </div>
            </div>
            <p style="margin-top: 40px; max-width: 500px; font-size: 16px; color: #666;">
                Este investimento contempla toda a infraestrutura técnica, criativa e estratégica necessária para atingirmos os resultados propostos.
            </p>
        </div>
    </section>
</div>
