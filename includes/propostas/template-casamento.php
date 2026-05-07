<?php
/**
 * Template Casamento - Distinto Wedding
 * 19 Páginas/Slides conforme roteiro do cliente
 */

// Extrair dados do JSON
$dados = json_decode($proposta['dados_json'], true);
$nomeNoivo = $dados['nome_noivo'] ?? '';
$nomeNoiva = $dados['nome_noiva'] ?? '';
$nomeCasal = ($nomeNoivo && $nomeNoiva) ? "{$nomeNoivo} & {$nomeNoiva}" : $proposta['cliente_nome'];
$primeiroNomeNoiva = explode(' ', trim($nomeNoiva))[0];
$primeiroNomeNoivo = explode(' ', trim($nomeNoivo))[0];
$saudacaoCasal = "Olá, " . (($primeiroNomeNoivo && $primeiroNomeNoiva) ? "{$primeiroNomeNoivo} & {$primeiroNomeNoiva}" : $proposta['cliente_nome']) . "!";

// Formatação de Moeda Helper
if (!function_exists('fmt')) {
    function fmt($valor)
    {
        if (empty($valor))
            return 'Sob consulta';
        if (is_numeric($valor))
            return 'R$ ' . number_format($valor, 2, ',', '.');
        return $valor;
    }
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Inter:wght@100..900&family=Dancing+Script:wght@400..700&family=Montserrat:wght@100..900&display=swap"
    rel="stylesheet">

<style>
    :root {
        --wedding-gold: #c5a880;
        --wedding-dark: #1a1a1a;
        --wedding-bg: #fafafa;
        --wedding-serif: "Playfair Display", serif;
        --wedding-sans: "Inter", sans-serif;
        --wedding-script: "Dancing Script", cursive;
        --wedding-montserrat: "Montserrat", sans-serif;
    }

    body {
        background: #111;
    }

    .wedding-proposal {
        background: var(--wedding-bg);
        color: var(--wedding-dark);
        font-family: var(--wedding-sans);
        scroll-snap-type: y mandatory;
        height: 100vh;
        overflow-y: scroll;
        scroll-behavior: smooth;
    }

    .slide {
        height: 100vh;
        width: 100%;
        scroll-snap-align: start;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 80px;
        box-sizing: border-box;
    }

    /* Utilitários */
    .text-serif {
        font-family: var(--wedding-serif);
    }

    .text-gold {
        color: var(--wedding-gold);
    }

    .uppercase {
        text-transform: uppercase;
        letter-spacing: 0.2em;
    }

    .italic {
        font-style: italic;
    }

    h1 {
        font-size: 5rem;
        line-height: 1;
        margin: 0;
    }

    h2 {
        font-size: 3.5rem;
        line-height: 1.1;
        margin: 0;
    }

    h3 {
        font-size: 1.5rem;
        letter-spacing: 0.1em;
        font-weight: 300;
    }

    p {
        font-size: 1.1rem;
        line-height: 1.8;
        color: #444;
    }

    .line {
        width: 60px;
        height: 1px;
        background: var(--wedding-gold);
        margin: 30px 0;
    }

    .line-center {
        margin-left: auto;
        margin-right: auto;
    }

    /* Estilos Específicos */
    .bg-dark {
        background: #0a0a0a;
        color: white;
    }

    .bg-dark p {
        color: #888;
    }

    .img-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 0;
        opacity: 0.6;
    }

    .content-overlay {
        position: relative;
        z-index: 10;
        max-width: 900px;
    }

    .center {
        text-align: center;
        align-items: center;
    }

    /* Grid de Pacotes */
    .package-card {
        background: white;
        padding: 60px;
        border: 1px solid #eee;
        transition: all 0.5s ease;
    }

    .package-card:hover {
        border-color: var(--wedding-gold);
        transform: translateY(-10px);
    }

    .price-tag {
        font-family: var(--wedding-serif);
        font-size: 2.5rem;
        color: var(--wedding-gold);
        margin-top: 20px;
    }

    .badge {
        display: inline-block;
        padding: 4px 12px;
        background: var(--wedding-gold);
        color: white;
        font-size: 10px;
        font-weight: 800;
        border-radius: 4px;
        margin-bottom: 15px;
    }

    @media (max-width: 768px) {
        .slide {
            padding: 40px 20px;
        }

        h1 {
            font-size: 3rem;
        }

        h2 {
            font-size: 2.5rem;
        }
    }
</style>

<div class="wedding-proposal">

    <!-- PÁGINA 01: CAPA -->
    <section class="slide" style="padding: 0; display: block; background: #eee;">
        <img src="<?= raizUrl('/imagens-proposta-casamento/bg-section-01.jpg') ?>" class="img-bg"
            style="opacity: 1; z-index: 1;">

        <div class="content-overlay"
            style="height: 100%; width: 100%; display: flex; flex-direction: column; justify-content: space-between; padding: 10vh 8vw; box-sizing: border-box; max-width: 100%;">
            <!-- Topo Centro -->
            <div style="text-align: center; width: 100%;">
                <h1
                    style="font-family: var(--wedding-script); font-size: 8rem; color: #1a1a1a; margin-bottom: 0; font-weight: 400; text-transform: none; letter-spacing: 0;">
                    Casamento</h1>
                <p
                    style="font-family: var(--wedding-montserrat); font-size: 1.8rem; letter-spacing: 0.6em; color: #1a1a1a; margin-top: -10px; font-weight: 300;">
                    FOTOGRAFIA E FILMMAKING</p>
            </div>

            <!-- Baixo Esquerda -->
            <div style="text-align: left; max-width: 500px; color: #1a1a1a;">
                <h2
                    style="font-family: var(--wedding-montserrat); font-size: 2.2rem; font-weight: 800; letter-spacing: 0.05em; line-height: 1.2; margin-bottom: 20px;">
                    <?php
                    $noivoUpper = mb_strtoupper($primeiroNomeNoivo);
                    $noivaUpper = mb_strtoupper($primeiroNomeNoiva);
                    echo "{$noivoUpper} &<br>{$noivaUpper}";
                    ?>
                </h2>
                <p
                    style="font-family: var(--wedding-montserrat); font-size: 1.4rem; line-height: 1.6; font-weight: 400; margin-bottom: 20px; opacity: 0.8;">
                    Toda história tem sua beleza. Nós entregamos a nossa versão da sua sob a nossa perspectiva.
                </p>
                <div style="width: 40px; height: 1px; background: #1a1a1a; margin-bottom: 20px; opacity: 0.5;"></div>
                <p
                    style="font-family: var(--wedding-montserrat); font-size: 1rem; font-weight: 400; letter-spacing: 0.05em; opacity: 0.8;">
                    by Distinto wedding</p>
            </div>
        </div>
    </section>

    <!-- PÁGINA 02: BOAS-VINDAS -->
    <section class="slide" style="padding: 0; background: #fff; overflow: hidden; display: flex; flex-direction: row; height: 100vh; width: 100%;">
        <!-- Coluna Esquerda: Imagem -->
        <div style="flex: 1; background: #f0f0f0; display: flex; align-items: center; justify-content: flex-end; padding-right: 5vw; position: relative; height: 100%;">
            <!-- Retângulo decorativo cinza (esquerda) -->
            <div style="position: absolute; left: 0; top: 0; width: 50px; height: 100%; background: #dcdcdc; z-index: 1;"></div>
            
            <div style="width: 75%; aspect-ratio: 3/4; position: relative; z-index: 2; overflow: hidden; box-shadow: 20px 20px 0px rgba(0,0,0,0.02);">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-02.jpg') ?>" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </div>

        <!-- Coluna Direita: Conteúdo -->
        <div style="flex: 1.2; padding: 0 8vw; display: flex; flex-direction: column; justify-content: center; position: relative; background: #fff; height: 100%;">
            <h2 style="font-family: var(--wedding-montserrat); font-size: 4rem; font-weight: 700; line-height: 1.1; margin-bottom: 40px; color: #1a1a1a; text-transform: uppercase; letter-spacing: -1px;">
                BEM-VINDOS<br>AO INÍCIO DA<br>MEMÓRIA DE<br>VOCÊS
            </h2>
            
            <div style="max-width: 480px;">
                <p style="font-family: var(--wedding-montserrat); font-size: 1.2rem; font-weight: 700; color: #1a1a1a; margin-bottom: 10px;">
                    <?= $saudacaoCasal ?>
                </p>
                <p style="font-family: var(--wedding-montserrat); font-size: 1.1rem; line-height: 1.8; color: #444; font-weight: 400;">
                    Na Distinto, entendemos que o nosso papel vai muito além de apertar um botão: nossa missão é registrar histórias de amor com autenticidade e emoção.
                </p>
            </div>

            <!-- Logo Distinto no canto inferior direito -->
            <div style="position: absolute; bottom: 8vh; right: 6vw; width: 120px;">
                <img src="<?= raizUrl('/assets/distinto_logo.svg') ?>" style="width: 100%; filter: brightness(0); opacity: 0.8;">
            </div>

            <!-- Elemento decorativo cinza (topo direito) -->
            <div style="position: absolute; top: 10vh; right: 0; width: 50px; height: 35px; background: #dcdcdc;"></div>
        </div>
    </section>

    <!-- PÁGINA 03: CONCEITO 1 -->
    <section class="slide">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-20 items-center">
            <div>
                <h3 class="uppercase text-gold">Nossa Visão</h3>
                <h2 class="text-serif mt-4 italic">A arte de capturar o que é invisível.</h2>
                <div class="line"></div>
                <p>
                    Não buscamos apenas a nitidez técnica, mas a clareza do sentimento.
                    Nossa fotografia é documental e artística, focada em momentos reais,
                    sorrisos espontâneos e aquela lágrima que insiste em cair.
                </p>
            </div>
            <div class="relative h-[60vh]">
                <img src="https://images.unsplash.com/photo-1511285560929-80b456fea0bc?q=80&w=2069"
                    class="w-full h-full object-cover rounded-sm">
            </div>
        </div>
    </section>

    <!-- PÁGINA 04: CONCEITO 2 -->
    <section class="slide bg-dark">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-20 items-center">
            <div class="relative h-[60vh] order-2 md:order-1">
                <img src="https://images.unsplash.com/photo-1583939003579-730e3918a45a?q=80&w=1974"
                    class="w-full h-full object-cover rounded-sm">
            </div>
            <div class="order-1 md:order-2">
                <h3 class="uppercase text-gold">A Intenção</h3>
                <h2 class="text-serif mt-4 italic">Cada detalhe importa.</h2>
                <div class="line"></div>
                <p>
                    Desde o toque sutil das mãos até a grandiosidade da celebração,
                    nossa perspectiva é moldada para que, daqui a 20 anos, vocês
                    possam sentir o mesmo frio na barriga ao abrir o álbum.
                </p>
            </div>
        </div>
    </section>

    <!-- PÁGINA 05: APRESENTAÇÃO DAS PROPOSTAS -->
    <section class="slide center">
        <div class="content-overlay">
            <h3 class="uppercase text-gold">Experiências Distintas</h3>
            <h2 class="text-serif mt-4">Nossa meta é uma só: arrepiar.</h2>
            <div class="line line-center"></div>
            <p style="max-width: 800px;">
                Na Distinto, não começamos com ideias soltas. Começamos com clareza.
                Apresentamos nossas propostas de investimento. Cada uma delas foi pensada para transformar
                o seu casamento em uma experiência totalmente nova... para que a história de
                <strong class="text-gold"><?= $nomeCasal ?></strong> seja preservada com a nobreza que merece.
            </p>
        </div>
    </section>

    <!-- PÁGINA 07: HERITAGE (PLANO COMPLETO) -->
    <section class="slide">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-20 items-center">
            <div>
                <span class="badge">A EXPERIÊNCIA MAIS COMPLETA</span>
                <h2 class="text-serif">Heritage</h2>
                <div class="line"></div>
                <div class="space-y-4 text-sm">
                    <?php
                    $itensHeritage = explode(',', $dados['itens_heritage'] ?? '');
                    foreach ($itensHeritage as $item):
                        ?>
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span><?= trim($item) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-12 p-8 bg-white border border-zinc-100 shadow-sm">
                    <p class="uppercase text-[10px] font-bold text-zinc-400">Investimento Heritage</p>
                    <div class="price-tag"><?= fmt($dados['valor_heritage']) ?></div>
                    <?php if (!empty($dados['condicao_especial'])): ?>
                        <p class="text-xs mt-2 text-zinc-500 italic">* <?= $dados['condicao_especial'] ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="h-full">
                <img src="https://images.unsplash.com/photo-1519225495810-751bd511ccf7?q=80&w=2070"
                    class="w-full h-full object-cover">
            </div>
        </div>
    </section>

    <!-- PÁGINA 08: CINEMATIC -->
    <section class="slide bg-[#f4f2ee]">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-20 items-center">
            <div class="h-full order-2 md:order-1">
                <img src="https://images.unsplash.com/photo-1520854221256-17451cc331bf?q=80&w=2070"
                    class="w-full h-full object-cover">
            </div>
            <div class="order-1 md:order-2">
                <h2 class="text-serif">Cinematic</h2>
                <div class="line"></div>
                <div class="space-y-4 text-sm">
                    <?php
                    $itensCinematic = explode(',', $dados['itens_cinematic'] ?? '');
                    foreach ($itensCinematic as $item):
                        ?>
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span><?= trim($item) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-12 p-8 bg-white border border-zinc-100 shadow-sm">
                    <p class="uppercase text-[10px] font-bold text-zinc-400">Investimento Cinematic</p>
                    <div class="price-tag"><?= fmt($dados['valor_cinematic']) ?></div>
                    <?php if (!empty($dados['data_limite_desconto'])): ?>
                        <p class="text-[10px] mt-4 p-2 bg-gold/10 text-gold font-bold rounded">
                            10% DE DESCONTO PARA CONTRATOS ATÉ <?= $dados['data_limite_desconto'] ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- PÁGINA 09: ESSENCIAL E UPGRADES -->
    <section class="slide">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="package-card flex flex-col justify-between">
                <div>
                    <h3 class="uppercase text-zinc-400 text-xs font-bold mb-4">Registro Base</h3>
                    <h2 class="text-serif text-3xl">Essencial</h2>
                    <div class="line"></div>
                    <p class="text-sm">
                        <?= $dados['itens_essencial'] ?? 'Cobertura fotográfica essencial para o seu grande dia.' ?>
                    </p>
                </div>
                <div class="price-tag text-2xl mt-8"><?= fmt($dados['valor_essencial']) ?></div>
            </div>

            <div class="package-card flex flex-col justify-between border-gold/30 bg-gold/[0.02]">
                <div>
                    <h3 class="uppercase text-gold text-xs font-bold mb-4">Upgrade Artístico</h3>
                    <h2 class="text-serif text-3xl">Boudoir</h2>
                    <div class="line"></div>
                    <p class="text-sm">
                        Um ensaio íntimo e delicado, celebrando a feminilidade e a expectativa antes do "sim".
                    </p>
                </div>
                <div class="price-tag text-2xl mt-8"><?= fmt($dados['valor_boudoir']) ?></div>
            </div>

            <div class="package-card flex flex-col justify-between">
                <div>
                    <h3 class="uppercase text-zinc-400 text-xs font-bold mb-4">Sessão Externa</h3>
                    <h2 class="text-serif text-3xl">Pré-Wedding</h2>
                    <div class="line"></div>
                    <p class="text-sm">
                        Conexão e leveza em uma locação especial, aquecendo o coração para o grande dia.
                    </p>
                </div>
                <div class="price-tag text-2xl mt-8"><?= fmt($dados['valor_prewedding']) ?></div>
            </div>
        </div>
    </section>

    <!-- PÁGINA 10: INVESTIMENTO E PLANEJAMENTO -->
    <section class="slide bg-dark center">
        <div class="content-overlay">
            <h3 class="uppercase text-gold">Planejamento</h3>
            <h2 class="text-serif mt-4">Condições de Investimento</h2>
            <div class="line line-center"></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 text-left mt-12">
                <div class="p-8 border border-white/10 rounded-lg">
                    <h4 class="text-gold font-bold mb-4">Reserva de Data</h4>
                    <p class="text-sm">
                        Para garantir a sua data em nosso calendário, solicitamos um sinal de 20% a 25% do valor total
                        do contrato.
                    </p>
                </div>
                <div class="p-8 border border-white/10 rounded-lg">
                    <h4 class="text-gold font-bold mb-4">Formas de Pagamento</h4>
                    <p class="text-sm">
                        Parcelamento disponível via PIX/Boleto até 10 dias antes do evento, ou via Cartão de Crédito
                        (consulte taxas).
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- PÁGINAS 11 A 16: PORTFÓLIO (TRANSICAO) -->
    <section class="slide bg-dark center">
        <img src="https://images.unsplash.com/photo-1515934751635-c81c6bc9a2d8?q=80&w=2070" class="img-bg"
            style="opacity: 0.2;">
        <div class="content-overlay">
            <h3 class="uppercase text-gold">Portfolio</h3>
            <h1 class="text-serif italic">Wedding Portfolio</h1>
            <p class="uppercase mt-6">Versões da sua história</p>
        </div>
    </section>

    <!-- SLIDES DE FOTOS (SIMULADOS EM UM GRID PARA O PDF) -->
    <section class="slide">
        <div class="grid grid-cols-2 gap-4 h-full">
            <div class="bg-zinc-100 h-full relative overflow-hidden group">
                <img src="https://images.unsplash.com/photo-1532712938310-34cb3982ef74?q=80&w=2070"
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                <div class="absolute bottom-6 left-6 text-white z-10">
                    <p class="text-[10px] uppercase font-bold tracking-widest opacity-80">Wedding Stories</p>
                    <h4 class="text-serif text-xl">Lucas & Mariana</h4>
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
            </div>
            <div class="grid grid-rows-2 gap-4">
                <div class="bg-zinc-100 relative overflow-hidden group">
                    <img src="https://images.unsplash.com/photo-1544078751-58fee2d8a03b?q=80&w=2070"
                        class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                </div>
                <div class="bg-zinc-100 relative overflow-hidden group">
                    <img src="https://images.unsplash.com/photo-1510076857177-7470076d4098?q=80&w=2072"
                        class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- PÁGINA 17: EQUIPE -->
    <section class="slide center">
        <div class="content-overlay">
            <h3 class="uppercase text-gold">Os olhares por trás das lentes</h3>
            <div class="line line-center"></div>
            <h2 class="text-serif">Jeane Poncem, Wellington Poncem<br>& Gabryel Oliveira.</h2>
            <p class="mt-8 italic">Uma equipe unida pela paixão de eternizar momentos.</p>
        </div>
    </section>

    <!-- PÁGINA 18: FECHAMENTO EMOCIONAL -->
    <section class="slide center bg-[#f9f7f4]">
        <div class="content-overlay" style="max-width: 800px;">
            <h2 class="text-serif italic">Onde o tempo para...</h2>
            <div class="line line-center"></div>
            <p class="text-lg leading-relaxed">
                Para nós, a melhor foto não é a mais nítida... A "melhor foto" do nosso portfólio não é um troféu na
                estante, mas sim aquele frame que captura o extraordinário no comum.
                Seja o aperto de mão firme de um pai, a lágrima contida de um amigo ou o <strong
                    class="text-gold">brilho no olhar da <?= $primeiroNomeNoiva ?></strong> ao ver o seu grande amor no
                altar.
            </p>
        </div>
    </section>

    <!-- PÁGINA 19: PRÓXIMO PASSO -->
    <section class="slide bg-dark center">
        <div class="content-overlay">
            <h2 class="text-serif">Vamos dar o próximo passo?</h2>
            <div class="line line-center"></div>
            <p class="mb-12">
                Este é o primeiro capítulo da história oficial de <?= $nomeCasal ?>.
            </p>

            <div class="space-y-4 text-gold uppercase tracking-widest text-sm font-bold">
                <p>+55 27 9 8858-6935</p>
                <p>distintoag@gmail.com</p>
                <p>@distintoag</p>
            </div>

            <div class="mt-20 opacity-30 text-[10px] uppercase tracking-[0.5em]">
                Distinto Wedding © <?= date('Y') ?>
            </div>
        </div>
    </section>

</div>

<script>
    // Inicializar ícones se necessário
    if (window.lucide) lucide.createIcons();
</script>