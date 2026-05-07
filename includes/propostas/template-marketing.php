<?php
/**
 * Template Marketing Digital - Modelo DISTINCTO
 */
?>
<div class="theme-marketing">
    <!-- Slide 1: Hero (Capa seguindo o modelo exato) -->
    <section class="proposal-page">
        <div class="page-content" style="grid-column: 1; justify-content: center; padding: 0;">
            <h1 style="font-family: var(--font-heading);font-weight: 800;font-size: 2rem;line-height: 1;margin: 0;text-transform: uppercase;letter-spacing: -2px;color: #000; width: 80%;">
                <?= !empty($proposta['titulo_refinado']) ? $proposta['titulo_refinado'] : (!empty($proposta['titulo']) ? $proposta['titulo'] : 'PROPOSTA ESTRATÉGICA') ?>
            </h1>
            <?php if (!empty($proposta['subtitulo'])): ?>
            <p style="font-size: 0.8em; text-transform: uppercase; letter-spacing: 3px; color: rgba(0,0,0,0.4); font-weight: 700; margin-top: 2.5rem; line-height: 1.4;">
                <?= $proposta['subtitulo'] ?>
            </p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Slide 2: Introdução / Missão -->
    <section class="proposal-page dark-page">
        <!-- Coluna 1: Título de Impacto -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3.25rem; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #fff;width: 60%;">
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
        <div class="page-content" style="grid-column: 2; justify-content: center; padding-left: 2.5rem; height: 100vh; padding-top: 0; padding-bottom: 0;">
            <div class="mission-text" style="color: #fff; font-size: clamp(14px, 0.8rem, 28px); line-height: 1.5; opacity: 0.9;">
                <h3 style="font-family: var(--font-heading); font-size: clamp(24px, 1.75rem, 56px); font-weight: 800; margin-bottom: 1rem; text-transform: uppercase; color: #fff;">
                    <?php 
                        $saudacao = 'CLIENTE';
                        if (!empty($dados['responsavel'])) {
                            // Regex robusto para separar por ' e ', ' E ', vírgula ou ponto e vírgula
                            $nomesBrutos = preg_split('/(?:\s+[eE]\s+|[,;]\s*)/', $dados['responsavel']);
                            $primeirosNomes = array_map(function($n) {
                                return explode(' ', trim($n))[0];
                            }, array_filter($nomesBrutos));
                            
                            $total = count($primeirosNomes);
                            if ($total === 1) {
                                $saudacao = $primeirosNomes[0];
                            } elseif ($total === 2) {
                                $saudacao = $primeirosNomes[0] . ' e ' . $primeirosNomes[1];
                            } elseif ($total > 2) {
                                $ultimo = array_pop($primeirosNomes);
                                $saudacao = implode(', ', $primeirosNomes) . ' e ' . $ultimo;
                            }
                        } else {
                            $saudacao = explode(' ', trim($proposta['cliente_nome'] ?? 'CLIENTE'))[0];
                        }
                        echo "OLÁ " . mb_strtoupper($saudacao) . "!";
                    ?>
                </h3>
                <p style="font-weight: 700; margin-bottom: 1.25rem;">Seja bem-vindo à Poncem Studio | Distinto.</p>
                <p style="margin-bottom: 0.9375rem;">Mais do que uma agência, somos uma empresa de posicionamento, estratégia e direção criativa para marcas que desejam crescer com clareza, autoridade e percepção de valor.<br><br>
                    Acreditamos que negócios fortes não se constroem apenas com presença digital.<break><br>
                    Eles se constroem com narrativa, identidade, direção e execução inteligente.<br><br>
                    Por isso, nosso trabalho vai além de produzir conteúdo ou gerenciar redes sociais.<br><br>
                    Desenvolvemos marcas que comunicam com intenção, geram conexão e ocupam um espaço relevante no mercado.<br><br>
                    Unimos estratégia, comunicação e audiovisual para transformar empresas em marcas percebidas, desejadas e lembradas.<br><br>
                    Cada projeto que passa pela Distinto é pensado para transmitir valor de forma autêntica desde o posicionamento até a forma como a marca é vista, sentida e reconhecida pelas pessoas.<br><br>
                    Atuamos com empresas que entenderam que imagem sem estratégia gera apenas movimento. Mas estratégia alinhada à comunicação certa gera autoridade, crescimento e diferenciação.<br><br>
                    Se você chegou até aqui, provavelmente entende que sua marca possui algo valioso demais para parecer comum.</p>
                <p style="font-weight: 700; margin-top: 1.5rem;">Vamos juntos?</p>
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
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3.25rem; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 90%;">
                PARA ESTE PROJETO, QUAL SERÁ O NOSSO OBJETIVO?
            </h2>
        </div>

        <!-- Coluna 2: Texto Estratégico (IA) -->
        <div class="page-content" style="grid-column: 2; justify-content: center; padding-left: 2.5rem;">
            <div class="objective-text" style="color: #333; font-size: 0.8em; line-height: 1.6; opacity: 0.9;">
                <?php if (!empty($dados['secoes']['objetivo'])): ?>
                    <?= nl2br($dados['secoes']['objetivo']) ?>
                <?php else: ?>
                    Após uma análise do posicionamento estratégico da marca, identificamos uma oportunidade de fortalecer sua percepção de valor e autoridade. Nosso foco é claro: gerar resultados reais e posicionar seu negócio como referência no mercado.
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <?php 
    // Lógica de visibilidade das etapas
    $etapasAtivas = $dados['etapas_ativas'] ?? ['imersao', 'diagnostico', 'planejamento', 'linguagem_visual', 'entrega', 'gestao'];
    ?>

    <!-- Slide 4: Etapas do Projeto -->
    <?php if (in_array('imersao', $etapasAtivas)): ?>
    <section class="proposal-page is-etapas">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3.25rem; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%; visibility: hidden;">
                ETAPAS DO<br>PROJETO
            </h2>
        </div>

        <!-- Coluna 2 e 3: Conteúdo das Etapas -->
        <div class="page-content" style="grid-column: 2 / span 2; flex-direction: row; align-items: center; padding-left: 0; margin-left: -7rem;">
            <!-- Lista de Pílulas -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem; width: 14rem; flex-shrink: 0; position: relative; z-index: 9999;">
                <div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px; position: relative;">
                    IMERSÃO
                    <div style="position: absolute; right: -29px; top: 50%; width: 29px; height: 1px; background: rgba(0,0,0,0.2);"></div>
                </div>
                <?php if (in_array('diagnostico', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">DIAGNÓSTICO</div><?php endif; ?>
                <?php if (in_array('planejamento', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">PLANEJAMENTO</div><?php endif; ?>
                <?php if (in_array('linguagem_visual', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">LINGUAGEM VISUAL</div><?php endif; ?>
                <?php if (in_array('entrega', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">ENTREGA</div><?php endif; ?>
                <?php if (in_array('gestao', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">GESTÃO</div><?php endif; ?>
            </div>

            <!-- Texto Explicativo -->
            <div style="margin-left: 2rem; max-width: 27rem;">
                <p style="font-size: 1rem; line-height: 1.5; color: #333; margin-bottom: 1.5625rem;">
                    A primeira etapa do projeto é uma imersão sobre o seu negócio. Serão dois momentos - presenciais ou online - que aplicamos juntos a nossa metodologia, para definir pontos importantes sobre seu negócio.
                </p>
                <p style="font-size: 1rem; line-height: 1.5; color: #333; margin-bottom: 35px;">
                    Esses momentos serão importantes para reunir informações necessárias para este projeto, para servir como um guia de como expressar a marca na criação da autoridade no mercado off-line e on-line.
                </p>
                
                <!-- Cápsula de Tempo -->
                <div style="padding: 15px 25px; border-radius: 3.125rem; border: 1px solid rgba(0,0,0,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: center; line-height: 1.3; color: #000; letter-spacing: 0.5px; width: fit-content; margin-top: 1.5rem;">
                    PRIMEIRA E SEGUNDA SEMANA<br>E PONTUALMENTE DURANTE O PROCESSO
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Slide 5: Etapas do Projeto (DIAGNÓSTICO) -->
    <?php if (in_array('diagnostico', $etapasAtivas)): ?>
    <section class="proposal-page is-etapas">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3.25rem; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%; visibility: hidden;">
                ETAPAS DO<br>PROJETO
            </h2>
        </div>

        <!-- Coluna 2 e 3: Conteúdo das Etapas -->
        <div class="page-content" style="grid-column: 2 / span 2; flex-direction: row; align-items: center; padding-left: 0; margin-left: -7rem;">
            <!-- Lista de Pílulas -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem; width: 14rem; flex-shrink: 0; position: relative; z-index: 9999;">
                <?php if (in_array('imersao', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">IMERSÃO</div><?php endif; ?>
                <div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px; position: relative;">
                    DIAGNÓSTICO
                    <div style="position: absolute; right: -29px; top: 50%; width: 29px; height: 1px; background: rgba(0,0,0,0.2);"></div>
                </div>
                <?php if (in_array('planejamento', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">PLANEJAMENTO</div><?php endif; ?>
                <?php if (in_array('linguagem_visual', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">LINGUAGEM VISUAL</div><?php endif; ?>
                <?php if (in_array('entrega', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">ENTREGA</div><?php endif; ?>
                <?php if (in_array('gestao', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">GESTÃO</div><?php endif; ?>
            </div>

            <!-- Texto Explicativo -->
            <div style="margin-left: 2rem; max-width: 27rem;">
                <p style="font-size: 1rem; line-height: 1.5; color: #333; margin-bottom: 1.25rem;">
                    Depois da imersão concluída, é o momento de desenvolver o diagnóstico do negócio. Através desses resultados, teremos a definição da plataforma da marca com as seguintes entregas:
                </p>
                <ul style="list-style: none; padding: 0; margin: 0 0 35px 0; font-size: 1rem; color: #000; line-height: 1.8; font-weight: 500;">
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> Proposta de Marca</li>
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> Diferenciais estratégicos</li>
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> Entregas funcionais e emocionais</li>
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> Personalidade de marca</li>
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> Tom de voz</li>
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> *Arquétipo</li>
                </ul>
                
                <!-- Cápsula de Tempo -->
                <div style="padding: 15px 25px; border-radius: 3.125rem; border: 1px solid rgba(0,0,0,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: center; line-height: 1.3; color: #000; letter-spacing: 0.5px; width: fit-content; margin: 0 auto;">
                    TERCEIRA SEMANA
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Slide 6: Etapas do Projeto (PLANEJAMENTO) -->
    <?php if (in_array('planejamento', $etapasAtivas)): ?>
    <section class="proposal-page is-etapas">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3.25rem; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%; visibility: hidden;">
                ETAPAS DO<br>PROJETO
            </h2>
        </div>

        <!-- Coluna 2 e 3: Conteúdo das Etapas -->
        <div class="page-content" style="grid-column: 2 / span 2; flex-direction: row; align-items: center; padding-left: 0; margin-left: -7rem;">
            <!-- Lista de Pílulas -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem; width: 14rem; flex-shrink: 0; position: relative; z-index: 9999;">
                <?php if (in_array('imersao', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">IMERSÃO</div><?php endif; ?>
                <?php if (in_array('diagnostico', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">DIAGNÓSTICO</div><?php endif; ?>
                <div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px; position: relative;">
                    PLANEJAMENTO
                    <div style="position: absolute; right: -29px; top: 50%; width: 29px; height: 1px; background: rgba(0,0,0,0.2);"></div>
                </div>
                <?php if (in_array('linguagem_visual', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">LINGUAGEM VISUAL</div><?php endif; ?>
                <?php if (in_array('entrega', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">ENTREGA</div><?php endif; ?>
                <?php if (in_array('gestao', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">GESTÃO</div><?php endif; ?>
            </div>

            <!-- Texto Explicativo (Aprimorado) -->
            <div style="margin-left: 2rem; max-width: 27rem;">
                <p style="font-size: 1rem; line-height: 1.4; color: #333; margin-bottom: 0.9375rem;">
                    A fase de planejamento é o núcleo estratégico do projeto, onde estruturamos o "como" e o "onde" para garantir que cada ação tenha um propósito claro e mensurável. Nosso planejamento 360º abrange:
                </p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px;">
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.8rem; color: #000; line-height: 1.5; font-weight: 500;">
                        <li style="margin-bottom: 4px;">• DNA do Conteúdo da Marca</li>
                        <li style="margin-bottom: 4px;">• Definição de Personalidade e Voz</li>
                        <li style="margin-bottom: 4px;">• Canais de Atuação Estratégica</li>
                        <li style="margin-bottom: 4px;">• Análise de Concorrência</li>
                        <li style="margin-bottom: 4px;">• Criação de Personas</li>
                        <li style="margin-bottom: 4px;">• Pesquisa de Palavras-chave</li>
                        <li style="margin-bottom: 4px;">• Jornada de Compra</li>
                    </ul>
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.8rem; color: #000; line-height: 1.5; font-weight: 500;">
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
                <div style="margin-top: 25px; padding: 12px 20px; border-radius: 3.125rem; border: 1px solid rgba(0,0,0,0.3); font-size: 10px; font-weight: 700; text-transform: uppercase; text-align: center; color: #000; letter-spacing: 0.5px; width: fit-content;">
                    QUARTA À SÉTIMA SEMANA
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Slide 7: Etapas do Projeto (LINGUAGEM VISUAL) -->
    <?php if (in_array('linguagem_visual', $etapasAtivas)): ?>
    <section class="proposal-page is-etapas">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3.25rem; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%; visibility: hidden;">
                ETAPAS DO<br>PROJETO
            </h2>
        </div>

        <!-- Coluna 2 e 3: Conteúdo das Etapas -->
        <div class="page-content" style="grid-column: 2 / span 2; flex-direction: row; align-items: center; padding-left: 0; margin-left: -7rem;">
            <!-- Lista de Pílulas -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem; width: 14rem; flex-shrink: 0; position: relative; z-index: 9999;">
                <?php if (in_array('imersao', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">IMERSÃO</div><?php endif; ?>
                <?php if (in_array('diagnostico', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">DIAGNÓSTICO</div><?php endif; ?>
                <?php if (in_array('planejamento', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">PLANEJAMENTO</div><?php endif; ?>
                <div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px; position: relative;">
                    LINGUAGEM VISUAL
                    <div style="position: absolute; right: -29px; top: 50%; width: 29px; height: 1px; background: rgba(0,0,0,0.2);"></div>
                </div>
                <?php if (in_array('entrega', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">ENTREGA</div><?php endif; ?>
                <?php if (in_array('gestao', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">GESTÃO</div><?php endif; ?>
            </div>

            <!-- Texto Explicativo (Aprimorado) -->
            <div style="margin-left: 2rem; max-width: 27rem;">
                <p style="font-size: 1rem; line-height: 1.5; color: #333; margin-bottom: 1.25rem;">
                    A materialização da estratégia ocorre através da <strong>Linguagem Visual</strong>. Definimos um padrão estético de alta autoridade que reflete o posicionamento do seu negócio em cada ponto de contato.
                </p>
                <p style="font-size: 0.8em; color: #000; margin-bottom: 10px; font-weight: 600;">O resultado é consolidado no Manual de Identidade Visual, contemplando:</p>
                <ul style="list-style: none; padding: 0; margin: 0 0 25px 0; font-size: 0.8em; color: #444; line-height: 1.8;">
                    <li>• Tipografia Estratégica</li>
                    <li>• Paleta de Cores com Psicologia Aplicada</li>
                    <li>• Estilo de Elementos Gráficos</li>
                    <li>• Referências de Aplicação</li>
                    <li>• Modelos de Posts e Guidelines para Redes Sociais</li>
                </ul>
                <p style="font-size: 0.8em; line-height: 1.5; color: #666; margin-bottom: 1.875rem;">
                    Garantimos uma presença digital forte, coerente e pronta para escalar sua comunicação com profissionalismo.
                </p>
                
                <!-- Cápsula de Tempo -->
                <div style="padding: 15px 25px; border-radius: 3.125rem; border: 1px solid rgba(0,0,0,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: center; line-height: 1.3; color: #000; letter-spacing: 0.5px; width: fit-content; margin-top: 1.5rem;">
                    QUARTA À QUINTA SEMANA
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Slide 8: Etapas do Projeto (ENTREGA) -->
    <?php if (in_array('entrega', $etapasAtivas)): ?>
    <section class="proposal-page is-etapas">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3.25rem; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%; visibility: hidden;">
                ETAPAS DO<br>PROJETO
            </h2>
        </div>

        <!-- Coluna 2 e 3: Conteúdo das Etapas -->
        <div class="page-content" style="grid-column: 2 / span 2; flex-direction: row; align-items: center; padding-left: 0; margin-left: -7rem;">
            <!-- Lista de Pílulas -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem; width: 14rem; flex-shrink: 0; position: relative; z-index: 9999;">
                <?php if (in_array('imersao', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">IMERSÃO</div><?php endif; ?>
                <?php if (in_array('diagnostico', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">DIAGNÓSTICO</div><?php endif; ?>
                <?php if (in_array('planejamento', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">PLANEJAMENTO</div><?php endif; ?>
                <?php if (in_array('linguagem_visual', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">LINGUAGEM VISUAL</div><?php endif; ?>
                <div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px; position: relative;">
                    ENTREGA
                    <div style="position: absolute; right: -29px; top: 50%; width: 29px; height: 1px; background: rgba(0,0,0,0.2);"></div>
                </div>
                <?php if (in_array('gestao', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">GESTÃO</div><?php endif; ?>
            </div>

            <!-- Texto Explicativo (Aprimorado) -->
            <div style="margin-left: 2rem; max-width: 27rem;">
                <p style="font-size: 1rem; line-height: 1.5; color: #333; margin-bottom: 1.5625rem;">
                    A culminância do nosso trabalho estratégico. O <strong>Planejamento</strong> e a <strong>Identidade Visual</strong> são apresentados em uma reunião executiva, garantindo o alinhamento total de cada decisão tomada.
                </p>
                <p style="font-size: 1rem; line-height: 1.6; color: #333; margin-bottom: 35px;">
                    Após a validação, todo o ecossistema do projeto é disponibilizado em uma <strong>plataforma web exclusiva</strong>. Este hub serve como guia central para sua equipe e parceiros, garantindo a integridade da marca em qualquer futura expansão.
                </p>
                
                <!-- Cápsula de Tempo -->
                <div style="padding: 15px 25px; border-radius: 3.125rem; border: 1px solid rgba(0,0,0,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: center; line-height: 1.3; color: #000; letter-spacing: 0.5px; width: fit-content; margin-top: 1.5rem;">
                    DÉCIMA PRIMEIRA SEMANA
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Slide 9: Etapas do Projeto (GESTÃO) -->
    <?php if (in_array('gestao', $etapasAtivas)): ?>
    <section class="proposal-page is-etapas">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3.25rem; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%; visibility: hidden;">
                ETAPAS DO<br>PROJETO
            </h2>
        </div>

        <!-- Coluna 2 e 3: Conteúdo das Etapas -->
        <div class="page-content" style="grid-column: 2 / span 2; flex-direction: row; align-items: center; padding-left: 0; margin-left: -7rem;">
            <!-- Lista de Pílulas -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem; width: 14rem; flex-shrink: 0; position: relative; z-index: 9999;">
                <?php if (in_array('imersao', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">IMERSÃO</div><?php endif; ?>
                <?php if (in_array('diagnostico', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">DIAGNÓSTICO</div><?php endif; ?>
                <?php if (in_array('planejamento', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">PLANEJAMENTO</div><?php endif; ?>
                <?php if (in_array('linguagem_visual', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">LINGUAGEM VISUAL</div><?php endif; ?>
                <?php if (in_array('entrega', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">ENTREGA</div><?php endif; ?>
                <div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px; position: relative;">
                    GESTÃO
                    <div style="position: absolute; right: -29px; top: 50%; width: 29px; height: 1px; background: rgba(0,0,0,0.2);"></div>
                </div>
            </div>

            <!-- Texto Explicativo (Aprimorado) -->
            <div style="margin-left: 2rem; max-width: 27rem;">
                <p style="font-size: 1rem; line-height: 1.5; color: #333; margin-bottom: 1.25rem;">
                    A transição da estratégia para a alta performance. Após a entrega das diretrizes, iniciamos o processo de <strong>gestão contínua</strong>, onde a teoria se torna execução prática e resultados reais.
                </p>
                <p style="font-size: 0.8em; line-height: 1.6; color: #333; margin-bottom: 1.25rem;">
                    Focamos na ativação dos canais, distribuição de conteúdo e <strong>gestão de tráfego pago</strong>. Nosso objetivo é claro: atrair leads qualificados e converter a autoridade construída em oportunidades de negócio.
                </p>
                <p style="font-size: 0.8em; line-height: 1.6; color: #333; margin-bottom: 1.875rem;">
                    Você receberá <strong>relatórios estratégicos mensais</strong> com análise de impacto, desempenho de campanhas e propostas de otimização constantes, garantindo que sua marca nunca estagne.
                </p>
                
                <!-- Cápsula de Tempo -->
                <div style="padding: 15px 25px; border-radius: 3.125rem; border: 1px solid rgba(0,0,0,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: center; line-height: 1.3; color: #000; letter-spacing: 0.5px; width: fit-content; margin-top: 1.5rem;">
                    A PARTIR DA DÉCIMA SEGUNDA SEMANA
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Slide 10: Resumo do Cronograma -->
    <section class="proposal-page dark-page is-etapas">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3.25rem; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #fff; width: 80%; visibility: hidden;">
                ETAPAS DO<br>PROJETO
            </h2>
        </div>

        <!-- Coluna 2 e 3: Conteúdo das Etapas -->
        <div class="page-content" style="grid-column: 2 / span 2; flex-direction: row; align-items: center; padding-left: 0; margin-left: -7rem;">
            <!-- Lista de Pílulas Ativas -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem; width: 14rem; flex-shrink: 0; position: relative; z-index: 9999;">
                <?php if (in_array('imersao', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">IMERSÃO</div><?php endif; ?>
                <?php if (in_array('diagnostico', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">DIAGNÓSTICO</div><?php endif; ?>
                <?php if (in_array('planejamento', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">PLANEJAMENTO</div><?php endif; ?>
                <?php if (in_array('linguagem_visual', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">LINGUAGEM VISUAL</div><?php endif; ?>
                <?php if (in_array('entrega', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">ENTREGA</div><?php endif; ?>
                <?php if (in_array('gestao', $etapasAtivas)): ?><div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">GESTÃO</div><?php endif; ?>

                <!-- Chave de Conexão (Bracket) -->
                <div style="position: absolute; right: -20px; top: 20px; bottom: 20px; width: 20px; border: 1.5px solid rgba(255,255,255,0.3); border-left: 0;"></div>
            </div>

            <!-- Texto de Cronograma (legado - mantido como resumo) -->
            <div style="margin-left: 2rem; max-width: 27rem;">
                <?php
                    $dataInicioRaw = $dados['data_inicio'] ?? date('Y-m-d');
                    $dataObj = new DateTime($dataInicioRaw);
                    $diaIni = $dataObj->format('d');
                    $mesIni = $mesesPt[$dataObj->format('n')] ?? 'JUNHO';
                    $fasesCron = $dados['fases_cronograma'] ?? [];
                    $totalDias = array_sum(array_column($fasesCron, 'dias'));
                ?>
                <p style="font-size: 1rem; line-height: 1.5; color: #fff; margin-bottom: 1.5625rem;">
                    O projeto poderá ser iniciado a partir do dia <strong><?= $diaIni ?> DE <?= $mesIni ?></strong><?php if ($totalDias > 0): ?>, com previsão de <strong><?= $totalDias ?> DIAS</strong> até o início das publicações<?php endif; ?>.
                </p>
                <p style="font-size: 0.85rem; line-height: 1.5; color: rgba(255,255,255,0.7);">
                    As datas são uma previsão e podem ser ajustadas conforme disponibilidade de agenda, aprovações e alterações de escopo.
                </p>
            </div>
        </div>
    </section>

    <?php if (!empty($dados['fases_cronograma'])): ?>
    <!-- Slide Cronograma de Entrega (dinâmico) -->
    <section class="proposal-page">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center; padding-right: 2rem;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3rem; line-height: 1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000;">
                CRONOGRAMA<br>DE<br>ENTREGA
            </h2>
            <?php
                $totalDiasSlide = array_sum(array_column($dados['fases_cronograma'], 'dias'));
                if ($totalDiasSlide > 0):
            ?>
            <div style="margin-top: 1.5rem; padding: 8px 18px; border-radius: 30px; background: #000; color: #fff; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; width: fit-content;">
                <?= $totalDiasSlide ?> dias até o início
            </div>
            <?php endif; ?>
        </div>

        <!-- Coluna 2-3: Timeline das fases -->
        <div class="page-content" style="grid-column: 2 / span 2; flex-direction: column; justify-content: center; padding: 4vh 4vw 4vh 2vw; gap: 0;">
            <?php
                $diasAcumulados = 0;
                $totalFases = count($dados['fases_cronograma']);
            ?>
            <?php foreach ($dados['fases_cronograma'] as $i => $fase): ?>
            <?php
                $diasFase = (int)($fase['dias'] ?? 0);
                $isUltima = ($i === $totalFases - 1);
                $isSim = ($diasFase === 0);
            ?>
            <div style="display: flex; align-items: stretch; gap: 0;">
                <!-- Linha vertical + círculo -->
                <div style="display: flex; flex-direction: column; align-items: center; width: 32px; flex-shrink: 0;">
                    <div style="width: 28px; height: 28px; border-radius: 50%; background: <?= $isSim ? '#e5e7eb' : '#000' ?>; border: 2px solid #000; display: flex; align-items: center; justify-content: center; flex-shrink: 0; z-index: 1;">
                        <span style="color: <?= $isSim ? '#000' : '#fff' ?>; font-size: 10px; font-weight: 800;"><?= $i + 1 ?></span>
                    </div>
                    <?php if (!$isUltima): ?>
                    <div style="width: 2px; flex: 1; background: rgba(0,0,0,0.15); margin: 4px 0; min-height: 40px;"></div>
                    <?php endif; ?>
                </div>

                <!-- Conteúdo da fase -->
                <div style="padding: 0 0 <?= $isUltima ? '0' : '28px' ?> 16px; flex: 1;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px; flex-wrap: wrap;">
                        <span style="font-family: var(--font-heading); font-size: 0.85rem; font-weight: 800; color: #000; text-transform: uppercase; letter-spacing: 0.5px;">
                            <?= sanitizar($fase['nome']) ?>
                        </span>
                        <?php if ($isSim): ?>
                            <span style="font-size: 9px; font-weight: 700; color: #6b7280; background: #f3f4f6; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">simultâneo</span>
                        <?php else: ?>
                            <span style="font-size: 9px; font-weight: 700; color: #fff; background: #000; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">+<?= $diasFase ?> dias</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($fase['descricao'])): ?>
                    <p style="font-size: 0.78rem; color: #6b7280; line-height: 1.5; margin: 0;">
                        <?= sanitizar($fase['descricao']) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php $diasAcumulados += $diasFase; ?>
            <?php endforeach; ?>

            <p style="font-size: 10px; color: #9ca3af; margin-top: 20px; padding-left: 48px; font-style: italic;">
                * Prazos estimados. Sujeitos a ajustes conforme aprovações e disponibilidade.
            </p>
        </div>
    </section>
    <?php endif; ?>

    <!-- Slide 11: Investimento Detalhado -->
    <section class="proposal-page is-investimento">
        <!-- Coluna 1: Título e Validade -->
        <div class="page-content" style="grid-column: 1; flex-direction: column; justify-content: center; gap: 2.5rem; height: 100vh; padding: 0;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3.25rem; line-height: 1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 90%;">
                QUAL SERÁ O INVESTIMENTO PARA ESTE PROJETO
            </h2>

            <!-- Validade -->
            <?php 
            $hoje = date('Y-m-d');
            $vencida = ($proposta['validade'] < $hoje);
            $validadeFormatada = date('d/m/Y', strtotime($proposta['validade']));
            ?>
            <div style="padding: 12px 25px; border-radius: 3.125rem; border: 1px solid <?= $vencida ? '#ff4d4d' : 'rgba(0,0,0,0.3)' ?>; font-size: 10px; font-weight: 700; text-transform: uppercase; text-align: center; color: <?= $vencida ? '#ff4d4d' : '#000' ?>; letter-spacing: 1px; width: fit-content; display: flex; align-items: center; gap: 8px;">
                <?php if ($vencida): ?>
                    <i data-lucide="alert-circle" style="width: 0.8em; height: 0.8em;"></i>
                    PROPOSTA VENCIDA EM <?= $validadeFormatada ?>
                <?php else: ?>
                    ESTA PROPOSTA É VÁLIDA ATÉ <?= $validadeFormatada ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Coluna 2: Detalhamento -->
        <div class="page-content" style="grid-column: 2; flex-direction: column; align-items: center; justify-content: center; height: 100vh; padding: 0;">
            <div style="width: 100%; max-height: 85vh; overflow-y: auto; scrollbar-width: none; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2.5rem 0;">
            <?php
                $mesesContrato = max(1, (int)($dadosJson['meses_contrato'] ?? 12));
                // valor_total = total do contrato ÷ meses = valor mensal após desconto
                $valorMensalFinal = round($proposta['valor_total'] / $mesesContrato, 2);

                // Carregar preços do catálogo para calcular valor_mensal real de cada serviço
                $catalogoPrecos = [];
                $idsServicos = array_filter(array_column($dados['servicos'] ?? [], 'id'));
                if (!empty($idsServicos)) {
                    $placeholders = implode(',', array_fill(0, count($idsServicos), '?'));
                    $stmtCat = $db->prepare("SELECT id, preco_venda, preco_venda_pontual FROM servicos WHERE id IN ($placeholders)");
                    $stmtCat->execute(array_values($idsServicos));
                    foreach ($stmtCat->fetchAll() as $sc) {
                        $catalogoPrecos[$sc['id']] = $sc;
                    }
                }

                // Calcular valor_mensal de cada serviço (mesmo critério do formulário JS)
                $servicosComCalculo = [];
                $subtotalMensal = 0;
                foreach ($dados['servicos'] ?? [] as $sv) {
                    $tipo  = $sv['tipo_cobranca'] ?? 'recorrente';
                    $freq  = max(1, (int)($sv['frequencia'] ?? 1));
                    $valIndividual = (float)($sv['valor_individual'] ?? 0);
                    $cat   = $catalogoPrecos[$sv['id'] ?? ''] ?? null;

                    if (isset($sv['valor_mensal']) && $sv['valor_mensal'] > 0) {
                        // Já foi salvo corretamente — usar diretamente
                        $vmCalculado = (float)$sv['valor_mensal'];
                    } elseif ($tipo === 'pontual') {
                        if ($freq > 1) {
                            // Frequência mensal: usa preco_venda (recorrente) × freq
                            $precoRecorrente = $cat ? (float)$cat['preco_venda'] : $valIndividual;
                            $vmCalculado = round($precoRecorrente * $freq, 2);
                        } else {
                            // Pontual único: dilui pelo tempo de contrato
                            $vmCalculado = round($valIndividual / $mesesContrato, 2);
                        }
                    } else {
                        // Recorrente: é o próprio valor mensal
                        $vmCalculado = $valIndividual;
                    }

                    $subtotalMensal += $vmCalculado;
                    $servicosComCalculo[] = array_merge($sv, [
                        '_vm'   => $vmCalculado,
                        '_tipo' => $tipo,
                        '_freq' => $freq,
                        '_val_unico' => $valIndividual,
                    ]);
                }
                $subtotalMensal = round($subtotalMensal, 2);

                // Percentual de desconto global
                $percentDesconto = ($subtotalMensal > 0.01 && $valorMensalFinal < $subtotalMensal - 0.01)
                    ? round((1 - $valorMensalFinal / $subtotalMensal) * 100, 1)
                    : 0;

                $isCartao = ($dadosJson['forma_pagamento'] ?? 'boleto_pix') === 'cartao';
                $valorMensalExibido = $isCartao ? round($valorMensalFinal * 1.0213, 2) : $valorMensalFinal;
            ?>

            <!-- Valor Mensal -->
            <div style="padding: 8px 40px; border-radius: 3.125rem; background: #333; color: #fff; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.75rem;">
                VALOR INVESTIDO NO PROJETO /MÊS
            </div>

            <?php if ($percentDesconto > 0): ?>
            <div style="font-family: var(--font-heading); font-size: 1.6rem; font-weight: 700; color: #000; opacity: 0.2; text-decoration: line-through; margin-bottom: 4px; letter-spacing: -1px;">
                <?= formatarMoeda($subtotalMensal) ?>
            </div>
            <?php endif; ?>

            <div style="font-family: var(--font-heading); font-size: 4rem; font-weight: 800; color: #000; margin-bottom: <?= $isCartao ? '5px' : '50px' ?>;">
                <?= formatarMoeda($valorMensalExibido) ?>
            </div>
            <?php if ($isCartao): ?>
                <div style="font-size: 10px; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 3.125rem;">
                    * VALOR COM ACRÉSCIMO DE 2,13% PARA CARTÃO
                </div>
            <?php endif; ?>

            <!-- Serviços Inclusos -->
            <div style="width: 100%; display: flex; flex-direction: column; gap: 2.5rem;">
                <?php foreach ($servicosComCalculo as $servico): ?>
                    <div style="width: 100%;">
                        <div style="padding: 8px 1.25rem; border-radius: 3.125rem; background: #333; color: #fff; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; width: fit-content; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 8px;">
                            <span><?= sanitizar($servico['nome']) ?></span>
                            <?php if ($servico['_tipo'] === 'pontual'): ?>
                                <span style="background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 4px; font-size: 8px;">
                                    <?= $servico['_freq'] > 1 ? $servico['_freq'] . 'X/MÊS' : 'PONTUAL' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 13px; font-weight: 700; color: #000; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <?= formatarMoeda($servico['_vm']) ?>/mês
                            <?php if ($percentDesconto > 0): ?>
                                <span style="font-size: 10px; font-weight: 700; color: #fff; background: #222; padding: 2px 8px; border-radius: 20px; letter-spacing: 0.5px;">
                                    desconto de <?= number_format($percentDesconto, 0, ',', '.') ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($servico['_tipo'] === 'pontual' && $servico['_freq'] <= 1): ?>
                        <div style="font-size: 11px; color: #888; margin-bottom: 0.9375rem; font-style: italic;">
                            (valor único <?= formatarMoeda($servico['_val_unico']) ?> ÷ <?= $mesesContrato ?> meses)
                        </div>
                        <?php else: ?>
                        <div style="margin-bottom: 0.6rem;"></div>
                        <?php endif; ?>
                        <div style="font-size: 12px; color: #555; margin-bottom: 0.5rem; font-weight: 600;">Inclui:</div>
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
                    <div style="width: 100%; padding: 1.875rem; background: rgba(0,0,0,0.05); border-radius: 20px; border: 1px solid rgba(0,0,0,0.1);">
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
        <div class="page-content" style="grid-column: 1; flex-direction: column; justify-content: center; gap: 2.5rem; height: 100vh; padding: 0;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3.25rem; line-height: 1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 90%;">
                CONDIÇÕES DE<br>PAGAMENTO
            </h2>

            <!-- Validade -->
            <?php 
            $hoje = date('Y-m-d');
            $vencida = ($proposta['validade'] < $hoje);
            $validadeFormatada = date('d/m/Y', strtotime($proposta['validade']));
            ?>
            <div style="padding: 12px 25px; border-radius: 3.125rem; border: 1px solid <?= $vencida ? '#ff4d4d' : 'rgba(0,0,0,0.3)' ?>; font-size: 10px; font-weight: 700; text-transform: uppercase; text-align: center; color: <?= $vencida ? '#ff4d4d' : '#000' ?>; letter-spacing: 1px; width: fit-content; display: flex; align-items: center; gap: 8px;">
                <?php if ($vencida): ?>
                    <i data-lucide="alert-circle" style="width: 0.8em; height: 0.8em;"></i>
                    PROPOSTA VENCIDA EM <?= $validadeFormatada ?>
                <?php else: ?>
                    ESTA PROPOSTA É VÁLIDA ATÉ <?= $validadeFormatada ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Coluna 2: Detalhes do Pagamento -->
        <div class="page-content" style="grid-column: 2; flex-direction: column; align-items: flex-start; justify-content: center; height: 100vh; padding: 0;">
            <div style="width: 100%; padding: 2.5rem 0;">
            <div style="padding: 8px 40px; border-radius: 3.125rem; background: #333; color: #fff; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2.5rem;">
                FORMA DE PAGAMENTO
            </div>
            
            <p style="font-size: 0.8em; line-height: 1.6; color: #333; margin-bottom: 1.875rem;">
                O pagamento referente ao valor mensal do projeto deverá ser realizado no momento da assinatura do contrato, que será enviado para assinatura digital via e-mail.
            </p>
            <p style="font-size: 0.8em; line-height: 1.6; color: #333; margin-bottom: 1.875rem;">
                A partir da confirmação do pagamento, iniciaremos o processo de estruturação e execução do plano mensal, incluindo gestão de redes sociais, tráfego pago e demais serviços contratados.
            </p>

            <?php if ($isCartao): ?>
                <div style="padding: 20px; background: rgba(0,0,0,0.05); border-radius: 15px; border-left: 4px solid #000; margin-bottom: 1.875rem; width: 100%;">
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
        </div>
    </section>
    <!-- Slide 13: Finalização -->
    <section class="proposal-page dark-page">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; flex-direction: column; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 4rem; line-height: 0.9; margin: 0; text-transform: uppercase; letter-spacing: -2px; color: #fff;">
                VAMOS JUNTOS CONSTRUIR<br>ESTE PROJETO?
            </h2>
        </div>

        <!-- Coluna 2: Mensagem e Contato -->
        <div class="page-content" style="grid-column: 2; flex-direction: column; align-items: flex-start; justify-content: center; padding-left: 2.5rem;">
            <p style="font-size: 18px; line-height: 1.6; color: #fff; margin-bottom: 2.5rem; font-weight: 300;">
                Será um imenso prazer entrar com você nesta jornada e desenvolver um projeto para alavancar o seu negócio.
            </p>
            <p style="font-size: 1rem; line-height: 1.6; color: rgba(255,255,255,0.7); margin-bottom: 60px;">
                Qualquer dúvida sobre esta proposta ou o meu trabalho, entre em contato.
            </p>

            <div style="display: flex; flex-direction: column; gap: 15px;">
                <a href="mailto:contato@wedistinto.com" style="color: #fff; text-decoration: none; font-size: 1rem; font-weight: 600;">contato@wedistinto.com</a>
                <a href="https://wa.me/5527988586935" target="_blank" style="color: #fff; text-decoration: none; font-size: 1rem; font-weight: 600;">WhatsApp: (27) 9 8858-6935</a>
            </div>

                Rod. Sol, 2780, SL 1307 - Praia de Itaparica<br>
                Vila Velha - ES
            </div>
        </div>
    </section>
</div>
