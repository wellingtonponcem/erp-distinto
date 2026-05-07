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
            <div class="mission-text" style="color: #fff; font-size: 1rem; line-height: 1.6; opacity: 0.9;">
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
            <div class="objective-text" style="color: #333; font-size: 1.3rem; line-height: 1.6; opacity: 0.9;">
                <?php if (!empty($dados['secoes']['objetivo'])): ?>
                    <?= nl2br($dados['secoes']['objetivo']) ?>
                <?php else: ?>
                    Após uma análise do posicionamento estratégico da marca, identificamos uma oportunidade de fortalecer sua percepção de valor e autoridade. Nosso foco é claro: gerar resultados reais e posicionar seu negócio como referência no mercado.
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Slide 4: Etapas do Projeto -->
    <section class="proposal-page is-etapas">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%; visibility: hidden;">
                ETAPAS DO PROJETO
            </h2>
        </div>

        <!-- Coluna 2: Lista de Etapas -->
        <div class="page-content" style="grid-column: 2; flex-direction: row; align-items: center; padding-left: 0;">
            <!-- Lista de Pílulas -->
            <div style="display: flex; flex-direction: column; gap: 12px; width: 220px; flex-shrink: 0;">
                <div style="padding: 12px 30px; border-radius: 50px; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; position: relative;">
                    IMERSÃO
                    <div style="position: absolute; right: -40px; top: 50%; width: 40px; height: 1px; background: rgba(0,0,0,0.2);"></div>
                </div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">DIAGNÓSTICO</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">PLANEJAMENTO</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">LINGUAGEM VISUAL</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">ENTREGA</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">GESTÃO</div>
            </div>

            <!-- Texto Explicativo -->
            <div style="margin-left: 60px; max-width: 380px;">
                <p style="font-size: 16px; line-height: 1.5; color: #333; margin-bottom: 25px;">
                    A primeira etapa do projeto é uma imersão sobre o seu negócio. Serão dois momentos - presenciais ou online - que aplicamos juntos a nossa metodologia, para definir pontos importantes sobre seu negócio.
                </p>
                <p style="font-size: 16px; line-height: 1.5; color: #333; margin-bottom: 35px;">
                    Esses momentos serão importantes para reunir informações necessárias para este projeto, para servir como um guia de como expressar a marca na criação da autoridade no mercado off-line e on-line.
                </p>
                
                <!-- Cápsula de Tempo -->
                <div style="padding: 15px 25px; border-radius: 50px; border: 1px solid rgba(0,0,0,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: center; line-height: 1.3; color: #000; letter-spacing: 0.5px;">
                    PRIMEIRA E SEGUNDA SEMANA<br>E PONTUALMENTE DURANTE O PROCESSO
                </div>
            </div>
        </div>
    </section>

    <!-- Slide 5: Etapas do Projeto (DIAGNÓSTICO) -->
    <section class="proposal-page is-etapas">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%; visibility: hidden;">
                ETAPAS DO PROJETO
            </h2>
        </div>

        <!-- Coluna 2: Lista de Etapas -->
        <div class="page-content" style="grid-column: 2; flex-direction: row; align-items: center; padding-left: 0;">
            <!-- Lista de Pílulas -->
            <div style="display: flex; flex-direction: column; gap: 12px; width: 220px; flex-shrink: 0;">
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">IMERSÃO</div>
                <div style="padding: 12px 30px; border-radius: 50px; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; position: relative;">
                    DIAGNÓSTICO
                    <div style="position: absolute; right: -40px; top: 50%; width: 40px; height: 1px; background: rgba(0,0,0,0.2);"></div>
                </div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">PLANEJAMENTO</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">LINGUAGEM VISUAL</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">ENTREGA</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">GESTÃO</div>
            </div>

            <!-- Texto Explicativo -->
            <div style="margin-left: 60px; max-width: 380px;">
                <p style="font-size: 16px; line-height: 1.5; color: #333; margin-bottom: 20px;">
                    Depois da imersão concluída, é o momento de desenvolver o diagnóstico do negócio. Através desses resultados, teremos a definição da plataforma da marca com as seguintes entregas:
                </p>
                <ul style="list-style: none; padding: 0; margin: 0 0 35px 0; font-size: 16px; color: #000; line-height: 1.8; font-weight: 500;">
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> Proposta de Marca</li>
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> Diferenciais estratégicos</li>
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> Entregas funcionais e emocionais</li>
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> Personalidade de marca</li>
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> Tom de voz</li>
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> *Arquétipo</li>
                </ul>
                
                <!-- Cápsula de Tempo -->
                <div style="padding: 15px 25px; border-radius: 50px; border: 1px solid rgba(0,0,0,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: center; line-height: 1.3; color: #000; letter-spacing: 0.5px; width: fit-content; margin: 0 auto;">
                    TERCEIRA SEMANA
                </div>
            </div>
        </div>
    </section>

    <!-- Slide 6: Etapas do Projeto (PLANEJAMENTO) -->
    <section class="proposal-page is-etapas">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%; visibility: hidden;">
                ETAPAS DO PROJETO
            </h2>
        </div>

        <!-- Coluna 2: Lista de Etapas -->
        <div class="page-content" style="grid-column: 2; flex-direction: row; align-items: center; padding-left: 0;">
            <!-- Lista de Pílulas -->
            <div style="display: flex; flex-direction: column; gap: 12px; width: 220px; flex-shrink: 0;">
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">IMERSÃO</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">DIAGNÓSTICO</div>
                <div style="padding: 12px 30px; border-radius: 50px; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; position: relative;">
                    PLANEJAMENTO
                    <div style="position: absolute; right: -40px; top: 50%; width: 40px; height: 1px; background: rgba(0,0,0,0.2);"></div>
                </div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">LINGUAGEM VISUAL</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">ENTREGA</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">GESTÃO</div>
            </div>

            <!-- Texto Explicativo (Aprimorado) -->
            <div style="margin-left: 60px; max-width: 480px;">
                <p style="font-size: 15px; line-height: 1.4; color: #333; margin-bottom: 15px;">
                    A fase de planejamento é o núcleo estratégico do projeto, onde estruturamos o "como" e o "onde" para garantir que cada ação tenha um propósito claro e mensurável. Nosso planejamento 360º abrange:
                </p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px;">
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 12px; color: #000; line-height: 1.5; font-weight: 500;">
                        <li style="margin-bottom: 4px;">• DNA do Conteúdo da Marca</li>
                        <li style="margin-bottom: 4px;">• Definição de Personalidade e Voz</li>
                        <li style="margin-bottom: 4px;">• Canais de Atuação Estratégica</li>
                        <li style="margin-bottom: 4px;">• Análise de Concorrência</li>
                        <li style="margin-bottom: 4px;">• Criação de Personas</li>
                        <li style="margin-bottom: 4px;">• Pesquisa de Palavras-chave</li>
                        <li style="margin-bottom: 4px;">• Jornada de Compra</li>
                    </ul>
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 12px; color: #000; line-height: 1.5; font-weight: 500;">
                        <li style="margin-bottom: 4px;">• Definição de Linguagem Visual</li>
                        <li style="margin-bottom: 4px;">• Projeto Estrutural do Site</li>
                        <li style="margin-bottom: 4px;">• Linhas Editoriais</li>
                        <li style="margin-bottom: 4px;">• Calendário Trimestral</li>
                        <li style="margin-bottom: 4px;">• Estratégias por Canal</li>
                        <li style="margin-bottom: 4px;">• Fluxos de Automação</li>
                        <li style="margin-bottom: 4px;">• Planejamento de Tráfego</li>
                    </ul>
                </div>
                
                <!-- Cápsula de Tempo -->
                <div style="margin-top: 25px; padding: 12px 20px; border-radius: 50px; border: 1px solid rgba(0,0,0,0.3); font-size: 10px; font-weight: 700; text-transform: uppercase; text-align: center; color: #000; letter-spacing: 0.5px; width: fit-content;">
                    QUARTA À SÉTIMA SEMANA
                </div>
            </div>
        </div>
    </section>

    <!-- Slide 7: Etapas do Projeto (LINGUAGEM VISUAL) -->
    <section class="proposal-page is-etapas">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%; visibility: hidden;">
                ETAPAS DO PROJETO
            </h2>
        </div>

        <!-- Coluna 2: Lista de Etapas -->
        <div class="page-content" style="grid-column: 2; flex-direction: row; align-items: center; padding-left: 0;">
            <!-- Lista de Pílulas -->
            <div style="display: flex; flex-direction: column; gap: 12px; width: 220px; flex-shrink: 0;">
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">IMERSÃO</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">DIAGNÓSTICO</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">PLANEJAMENTO</div>
                <div style="padding: 12px 30px; border-radius: 50px; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; position: relative;">
                    LINGUAGEM VISUAL
                    <div style="position: absolute; right: -40px; top: 50%; width: 40px; height: 1px; background: rgba(0,0,0,0.2);"></div>
                </div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">ENTREGA</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">GESTÃO</div>
            </div>

            <!-- Texto Explicativo (Aprimorado) -->
            <div style="margin-left: 60px; max-width: 420px;">
                <p style="font-size: 16px; line-height: 1.5; color: #333; margin-bottom: 20px;">
                    A materialização da estratégia ocorre através da <strong>Linguagem Visual</strong>. Definimos um padrão estético de alta autoridade que reflete o posicionamento do seu negócio em cada ponto de contato.
                </p>
                <p style="font-size: 14px; color: #000; margin-bottom: 10px; font-weight: 600;">O resultado é consolidado no Manual de Identidade Visual, contemplando:</p>
                <ul style="list-style: none; padding: 0; margin: 0 0 25px 0; font-size: 14px; color: #444; line-height: 1.8;">
                    <li>• Tipografia Estratégica</li>
                    <li>• Paleta de Cores com Psicologia Aplicada</li>
                    <li>• Estilo de Elementos Gráficos</li>
                    <li>• Referências de Aplicação</li>
                    <li>• Modelos de Posts e Guidelines para Redes Sociais</li>
                </ul>
                <p style="font-size: 14px; line-height: 1.5; color: #666; margin-bottom: 30px;">
                    Garantimos uma presença digital forte, coerente e pronta para escalar sua comunicação com profissionalismo.
                </p>
                
                <!-- Cápsula de Tempo -->
                <div style="padding: 15px 25px; border-radius: 50px; border: 1px solid rgba(0,0,0,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: center; line-height: 1.3; color: #000; letter-spacing: 0.5px; width: fit-content;">
                    QUARTA À QUINTA SEMANA
                </div>
            </div>
        </div>
    </section>

    <!-- Slide 8: Etapas do Projeto (ENTREGA) -->
    <section class="proposal-page is-etapas">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%; visibility: hidden;">
                ETAPAS DO PROJETO
            </h2>
        </div>

        <!-- Coluna 2: Lista de Etapas -->
        <div class="page-content" style="grid-column: 2; flex-direction: row; align-items: center; padding-left: 0;">
            <!-- Lista de Pílulas -->
            <div style="display: flex; flex-direction: column; gap: 12px; width: 220px; flex-shrink: 0;">
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">IMERSÃO</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">DIAGNÓSTICO</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">PLANEJAMENTO</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">LINGUAGEM VISUAL</div>
                <div style="padding: 12px 30px; border-radius: 50px; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; position: relative;">
                    ENTREGA
                    <div style="position: absolute; right: -40px; top: 50%; width: 40px; height: 1px; background: rgba(0,0,0,0.2);"></div>
                </div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">GESTÃO</div>
            </div>

            <!-- Texto Explicativo (Aprimorado) -->
            <div style="margin-left: 60px; max-width: 420px;">
                <p style="font-size: 16px; line-height: 1.5; color: #333; margin-bottom: 25px;">
                    A culminância do nosso trabalho estratégico. O <strong>Planejamento</strong> e a <strong>Identidade Visual</strong> são apresentados em uma reunião executiva, garantindo o alinhamento total de cada decisão tomada.
                </p>
                <p style="font-size: 15px; line-height: 1.6; color: #333; margin-bottom: 35px;">
                    Após a validação, todo o ecossistema do projeto é disponibilizado em uma <strong>plataforma web exclusiva</strong>. Este hub serve como guia central para sua equipe e parceiros, garantindo a integridade da marca em qualquer futura expansão.
                </p>
                
                <!-- Cápsula de Tempo -->
                <div style="padding: 15px 25px; border-radius: 50px; border: 1px solid rgba(0,0,0,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: center; line-height: 1.3; color: #000; letter-spacing: 0.5px; width: fit-content;">
                    DÉCIMA PRIMEIRA SEMANA
                </div>
            </div>
        </div>
    </section>

    <!-- Slide 9: Etapas do Projeto (GESTÃO) -->
    <section class="proposal-page is-etapas">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%; visibility: hidden;">
                ETAPAS DO PROJETO
            </h2>
        </div>

        <!-- Coluna 2: Lista de Etapas -->
        <div class="page-content" style="grid-column: 2; flex-direction: row; align-items: center; padding-left: 0;">
            <!-- Lista de Pílulas -->
            <div style="display: flex; flex-direction: column; gap: 12px; width: 220px; flex-shrink: 0;">
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">IMERSÃO</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">DIAGNÓSTICO</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">PLANEJAMENTO</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">LINGUAGEM VISUAL</div>
                <div style="padding: 12px 30px; border-radius: 50px; border: 1.5px solid #000; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">ENTREGA</div>
                <div style="padding: 12px 30px; border-radius: 50px; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; position: relative;">
                    GESTÃO
                    <div style="position: absolute; right: -40px; top: 50%; width: 40px; height: 1px; background: rgba(0,0,0,0.2);"></div>
                </div>
            </div>

            <!-- Texto Explicativo (Aprimorado) -->
            <div style="margin-left: 60px; max-width: 480px;">
                <p style="font-size: 15px; line-height: 1.5; color: #333; margin-bottom: 20px;">
                    A transição da estratégia para a alta performance. Após a entrega das diretrizes, iniciamos o processo de <strong>gestão contínua</strong>, onde a teoria se torna execução prática e resultados reais.
                </p>
                <p style="font-size: 14px; line-height: 1.6; color: #333; margin-bottom: 20px;">
                    Focamos na ativação dos canais, distribuição de conteúdo e <strong>gestão de tráfego pago</strong>. Nosso objetivo é claro: atrair leads qualificados e converter a autoridade construída em oportunidades de negócio.
                </p>
                <p style="font-size: 14px; line-height: 1.6; color: #333; margin-bottom: 30px;">
                    Você receberá <strong>relatórios estratégicos mensais</strong> com análise de impacto, desempenho de campanhas e propostas de otimização constantes, garantindo que sua marca nunca estagne.
                </p>
                
                <!-- Cápsula de Tempo -->
                <div style="padding: 15px 25px; border-radius: 50px; border: 1px solid rgba(0,0,0,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: center; line-height: 1.3; color: #000; letter-spacing: 0.5px; width: fit-content;">
                    A PARTIR DA DÉCIMA SEGUNDA SEMANA
                </div>
            </div>
        </div>
    </section>

    <!-- Slide 10: Resumo do Cronograma -->
    <section class="proposal-page dark-page is-etapas">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #fff; width: 80%; visibility: hidden;">
                ETAPAS DO PROJETO
            </h2>
        </div>

        <!-- Coluna 2: Lista de Etapas (Tudo Conectado) -->
        <div class="page-content" style="grid-column: 2; flex-direction: row; align-items: center; padding-left: 0;">
            <!-- Lista de Pílulas Ativas -->
            <div style="display: flex; flex-direction: column; gap: 12px; width: 220px; flex-shrink: 0; position: relative;">
                <div style="padding: 12px 30px; border-radius: 50px; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">IMERSÃO</div>
                <div style="padding: 12px 30px; border-radius: 50px; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">DIAGNÓSTICO</div>
                <div style="padding: 12px 30px; border-radius: 50px; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">PLANEJAMENTO</div>
                <div style="padding: 12px 30px; border-radius: 50px; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">LINGUAGEM VISUAL</div>
                <div style="padding: 12px 30px; border-radius: 50px; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">ENTREGA</div>
                <div style="padding: 12px 30px; border-radius: 50px; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">GESTÃO</div>

                <!-- Chave de Conexão (Bracket) -->
                <div style="position: absolute; right: -40px; top: 20px; bottom: 20px; width: 20px; border: 1.5px solid rgba(255,255,255,0.3); border-left: 0;"></div>
            </div>

            <!-- Texto de Cronograma -->
            <div style="margin-left: 60px; max-width: 480px;">
                <?php 
                    $dataInicioRaw = $dados['data_inicio'] ?? date('Y-m-d');
                    $dataObj = new DateTime($dataInicioRaw);
                    $diaIni = $dataObj->format('d');
                    $mesIni = $mesesPt[$dataObj->format('n')] ?? 'JUNHO';
                ?>
                <p style="font-size: 15px; line-height: 1.5; color: #fff; margin-bottom: 25px;">
                    O planejamento estratégico poderá ser iniciado a partir do dia <strong><?= $diaIni ?> DE <?= $mesIni ?></strong>, com previsão de duração de <strong>40 DIAS ÚTEIS</strong>.
                </p>
                <p style="font-size: 13px; line-height: 1.5; color: rgba(255,255,255,0.7); margin-bottom: 25px;">
                    Estas datas são uma previsão do cronograma do projeto. Porém, é possível que ocorram alterações no cronograma durante o projeto, pelos seguintes motivos: Indisponibilidade de agenda do cliente, alterações no escopo do projeto e o tempo para as aprovações de cada etapa.
                </p>
                <p style="font-size: 13px; line-height: 1.5; color: #fff;">
                    Já a etapa de gestão tem a duração de 9 meses, o que totaliza 12 meses de contrato. E a gestão se inicia logo após a aprovação do planejamento estratégico.
                </p>
            </div>
        </div>
    </section>

    <!-- Slide 11: Investimento Detalhado -->
    <section class="proposal-page">
        <!-- Coluna 1: Título e Validade -->
        <div class="page-content" style="grid-column: 1; flex-direction: column; justify-content: space-between; padding-bottom: 120px;">
            <div style="margin-top: auto; margin-bottom: auto;">
                <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 90%;">
                    QUAL SERÁ O INVESTIMENTO PARA ESTE PROJETO
                </h2>
            </div>

            <!-- Validade -->
            <?php 
            $hoje = date('Y-m-d');
            $vencida = ($proposta['validade'] < $hoje);
            $validadeFormatada = date('d/m/Y', strtotime($proposta['validade']));
            ?>
            <div style="padding: 12px 25px; border-radius: 50px; border: 1px solid <?= $vencida ? '#ff4d4d' : 'rgba(0,0,0,0.3)' ?>; font-size: 10px; font-weight: 700; text-transform: uppercase; text-align: center; color: <?= $vencida ? '#ff4d4d' : '#000' ?>; letter-spacing: 1px; width: fit-content; display: flex; align-items: center; gap: 8px;">
                <?php if ($vencida): ?>
                    <i data-lucide="alert-circle" style="width: 14px; height: 14px;"></i>
                    PROPOSTA VENCIDA EM <?= $validadeFormatada ?>
                <?php else: ?>
                    ESTA PROPOSTA É VÁLIDA ATÉ <?= $validadeFormatada ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Coluna 2: Detalhamento -->
        <div class="page-content" style="grid-column: 2; flex-direction: column; align-items: center; justify-content: center; overflow-y: auto; max-height: 85vh; scrollbar-width: none;">
            <?php
                $mesesContrato = $dados['meses_contrato'] ?? 12;
                $valorMensal = $proposta['valor_total'] / ($mesesContrato > 0 ? $mesesContrato : 1);
                $isCartao = ($dados['forma_pagamento'] ?? 'boleto_pix') === 'cartao';
                
                if ($isCartao) {
                    $valorMensal = $valorMensal * 1.0213;
                }
            ?>

            <!-- Valor Mensal -->
            <div style="padding: 8px 40px; border-radius: 50px; background: #333; color: #fff; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">
                VALOR INVESTIDO NO PROJETO /MÊS
            </div>
            <div style="font-family: var(--font-heading); font-size: 64px; font-weight: 800; color: #000; margin-bottom: <?= $isCartao ? '5px' : '50px' ?>;">
                <?= formatarMoeda($valorMensal) ?>
            </div>
            <?php if ($isCartao): ?>
                <div style="font-size: 10px; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 50px;">
                    * VALOR COM ACRÉSCIMO DE 2,13% PARA CARTÃO
                </div>
            <?php endif; ?>

            <!-- Serviços Inclusos -->
            <div style="width: 100%; display: flex; flex-direction: column; gap: 40px;">
                <?php foreach ($dados['servicos'] ?? [] as $servico): ?>
                    <div style="width: 100%;">
                        <div style="padding: 8px 30px; border-radius: 50px; background: #333; color: #fff; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; width: fit-content; margin-bottom: 20px;">
                            <?= $servico['nome'] ?>
                        </div>
                        <div style="font-size: 13px; font-weight: 700; color: #000; margin-bottom: 15px;">
                            <?= formatarMoeda($servico['valor_individual'] ?? 0) ?> - Inclui:
                        </div>
                        <ul style="margin: 0; padding-left: 15px; list-style-type: none;">
                            <?php 
                                // Divide a descrição em pontos se houver (por ponto final ou ponto e vírgula)
                                $pontos = preg_split('/[.;\n]+/', $servico['descricao']);
                                foreach ($pontos as $ponto): 
                                    $ponto = trim($ponto);
                                    if (empty($ponto)) continue;
                            ?>
                                <li style="font-size: 12px; color: #444; margin-bottom: 8px; position: relative;">
                                    <span style="position: absolute; left: -15px; color: #000;">•</span>
                                    <?= $ponto ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>

                <!-- Opção Adicional -->
                <?php if (!empty($dados['adicional']['titulo'])): ?>
                    <div style="width: 100%; padding: 30px; background: rgba(0,0,0,0.05); border-radius: 20px; border: 1px solid rgba(0,0,0,0.1);">
                        <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: #000; margin-bottom: 10px;">
                            OPÇÃO ADICIONAL MENSAL – <?= $dados['adicional']['titulo'] ?> + <?= formatarMoeda($dados['adicional']['valor'] ?? 0) ?>/mês
                        </div>
                        <p style="font-size: 12px; color: #444; line-height: 1.5; margin: 0;">
                            <?= nl2br($dados['adicional']['descricao'] ?? '') ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Slide 12: Condições de Pagamento -->
    <section class="proposal-page">
        <!-- Coluna 1: Título e Validade -->
        <div class="page-content" style="grid-column: 1; flex-direction: column; justify-content: space-between; padding-bottom: 120px;">
            <div style="margin-top: auto; margin-bottom: auto;">
                <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 90%;">
                    CONDIÇÕES DE<br>PAGAMENTO
                </h2>
            </div>

            <!-- Validade -->
            <?php 
            $hoje = date('Y-m-d');
            $vencida = ($proposta['validade'] < $hoje);
            $validadeFormatada = date('d/m/Y', strtotime($proposta['validade']));
            ?>
            <div style="padding: 12px 25px; border-radius: 50px; border: 1px solid <?= $vencida ? '#ff4d4d' : 'rgba(0,0,0,0.3)' ?>; font-size: 10px; font-weight: 700; text-transform: uppercase; text-align: center; color: <?= $vencida ? '#ff4d4d' : '#000' ?>; letter-spacing: 1px; width: fit-content; display: flex; align-items: center; gap: 8px;">
                <?php if ($vencida): ?>
                    <i data-lucide="alert-circle" style="width: 14px; height: 14px;"></i>
                    PROPOSTA VENCIDA EM <?= $validadeFormatada ?>
                <?php else: ?>
                    ESTA PROPOSTA É VÁLIDA ATÉ <?= $validadeFormatada ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Coluna 2: Detalhes do Pagamento -->
        <div class="page-content" style="grid-column: 2; flex-direction: column; align-items: flex-start; justify-content: center;">
            <div style="padding: 8px 40px; border-radius: 50px; background: #333; color: #fff; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 40px;">
                FORMA DE PAGAMENTO
            </div>
            
            <p style="font-size: 14px; line-height: 1.6; color: #333; margin-bottom: 30px;">
                O pagamento referente ao valor mensal do projeto deverá ser realizado no momento da assinatura do contrato, que será enviado para assinatura digital via e-mail.
            </p>
            <p style="font-size: 14px; line-height: 1.6; color: #333; margin-bottom: 30px;">
                A partir da confirmação do pagamento, iniciaremos o processo de estruturação e execução do plano mensal, incluindo gestão de redes sociais, tráfego pago e demais serviços contratados.
            </p>

            <?php if ($isCartao): ?>
                <div style="padding: 20px; background: rgba(0,0,0,0.05); border-radius: 15px; border-left: 4px solid #000; margin-bottom: 30px; width: 100%;">
                    <p style="font-size: 13px; font-weight: 700; color: #000; margin-bottom: 5px;">PAGAMENTO VIA CARTÃO DE CRÉDITO</p>
                    <p style="font-size: 12px; color: #444; margin: 0;">Para esta modalidade, há um acréscimo de <strong>2,13%</strong> referente às taxas operacionais da plataforma de pagamento.</p>
                </div>
            <?php endif; ?>

            <div style="margin-top: 20px;">
                <p style="font-size: 12px; font-weight: 700; color: #000; margin-bottom: 10px;">Observação:</p>
                <p style="font-size: 12px; color: #666; line-height: 1.6;">
                    O valor referente ao investimento em mídia (anúncios) é de responsabilidade do cliente, sendo pago diretamente à plataforma de anúncios (Meta/Facebook Ads), via boleto bancário ou cartão de crédito cadastrado na conta de anúncios.
                </p>
            </div>
        </div>
    </section>
    <!-- Slide 13: Finalização -->
    <section class="proposal-page dark-page">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; flex-direction: column; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 82px; line-height: 0.9; margin: 0; text-transform: uppercase; letter-spacing: -2px; color: #fff;">
                VAMOS JUNTOS CONSTRUIR ESTE PROJETO?
            </h2>
        </div>

        <!-- Coluna 2: Mensagem e Contato -->
        <div class="page-content" style="grid-column: 2; flex-direction: column; align-items: flex-start; justify-content: center; padding-left: 40px;">
            <p style="font-size: 18px; line-height: 1.6; color: #fff; margin-bottom: 40px; font-weight: 300;">
                Será um imenso prazer entrar com você nesta jornada e desenvolver um projeto para alavancar o seu negócio.
            </p>
            <p style="font-size: 16px; line-height: 1.6; color: rgba(255,255,255,0.7); margin-bottom: 60px;">
                Qualquer dúvida sobre esta proposta ou o meu trabalho, entre em contato.
            </p>

            <div style="display: flex; flex-direction: column; gap: 15px;">
                <a href="mailto:hello@jeaneponcem.com.br" style="color: #fff; text-decoration: none; font-size: 16px; font-weight: 600;">hello@jeaneponcem.com.br</a>
                <a href="https://wa.me/5527988586935" target="_blank" style="color: #fff; text-decoration: none; font-size: 16px; font-weight: 600;">WhatsApp: (27) 9 8858-6935</a>
            </div>

            <div style="margin-top: 60px; font-size: 14px; color: rgba(255,255,255,0.5); line-height: 1.6;">
                Rod. Sol, 2780, SL 1307 - Praia de Itaparica<br>
                Vila Velha - ES
            </div>
        </div>
    </section>
</div>
