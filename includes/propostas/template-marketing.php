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
    <section class="proposal-page">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%;">
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
    <section class="proposal-page">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%;">
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
    <section class="proposal-page">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%;">
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
    <section class="proposal-page">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%;">
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
    <section class="proposal-page">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%;">
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
    <section class="proposal-page">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%;">
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
    <section class="proposal-page">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 52px; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%;">
                ETAPAS DO PROJETO
            </h2>
        </div>

        <!-- Coluna 2: Lista de Etapas (Tudo Conectado) -->
        <div class="page-content" style="grid-column: 2; flex-direction: column; align-items: flex-start;">
            <!-- Indicador de Mês -->
            <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: #000; margin-top: 40px; margin-bottom: 40px;">
                <?= $mesNome ?>
            </div>

            <div style="display: flex; flex-direction: row; align-items: center; width: 100%;">
                <!-- Lista de Pílulas Ativas -->
                <div style="display: flex; flex-direction: column; gap: 8px; width: 180px; flex-shrink: 0; position: relative;">
                <div style="padding: 10px 20px; border-radius: 50px; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">IMERSÃO</div>
                <div style="padding: 10px 20px; border-radius: 50px; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">DIAGNÓSTICO</div>
                <div style="padding: 10px 20px; border-radius: 50px; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">PLANEJAMENTO</div>
                <div style="padding: 10px 20px; border-radius: 50px; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">LINGUAGEM VISUAL</div>
                <div style="padding: 10px 20px; border-radius: 50px; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">ENTREGA</div>
                <div style="padding: 10px 20px; border-radius: 50px; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">GESTÃO</div>

                <!-- Chave de Conexão (Bracket) -->
                <div style="position: absolute; right: -25px; top: 15px; bottom: 15px; width: 15px; border: 1px solid rgba(0,0,0,0.3); border-left: 0;"></div>
            </div>

            <!-- Texto de Cronograma -->
            <div style="margin-left: 55px; max-width: 440px;">
                <?php 
                    $dataInicioRaw = $dados['data_inicio'] ?? date('Y-m-d');
                    $dataObj = new DateTime($dataInicioRaw);
                    $diaIni = $dataObj->format('d');
                    $mesIni = $mesesPt[$dataObj->format('n')] ?? 'JUNHO';
                ?>
                <p style="font-size: 15px; line-height: 1.5; color: #333; margin-bottom: 25px;">
                    O planejamento estratégico poderá ser iniciado a partir do dia <strong><?= $diaIni ?> DE <?= $mesIni ?></strong>, com previsão de duração de <strong>40 DIAS ÚTEIS</strong>.
                </p>
                <p style="font-size: 13px; line-height: 1.5; color: #666; margin-bottom: 25px;">
                    Estas datas são uma previsão do cronograma do projeto. Porém, é possível que ocorram alterações no cronograma durante o projeto, pelos seguintes motivos: Indisponibilidade de agenda do cliente, alterações no escopo do projeto e o tempo para as aprovações de cada etapa.
                </p>
                <p style="font-size: 13px; line-height: 1.5; color: #333;">
                    Já a etapa de gestão tem a duração de 9 meses, o que totaliza 12 meses de contrato. E a gestão se inicia logo após a aprovação do planejamento estratégico.
                </p>
            </div>
        </div>
    </section>

    <!-- Slide 11: Investimento -->
    <section class="proposal-page">
        <div class="page-content" style="align-items: center; text-align: center;">
            <div style="font-family: var(--font-heading); font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 2px; color: #000; margin-bottom: 40px;">
                11. Investimento & Parceria
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
