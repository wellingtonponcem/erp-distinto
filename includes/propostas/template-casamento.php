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

// Novas Variáveis Dinâmicas
$mensagemPessoal = $dados['mensagem_pessoal'] ?? 'Na Distinto, entendemos que o nosso papel vai muito além de apertar um botão: nossa missão é registrar histórias de amor com autenticidade e emoção.';
$prazoPrevias = $dados['prazo_previas'] ?? '48 horas';
$prazoFinal = $dados['prazo_final'] ?? '60 dias úteis';
$validadeProposta = $dados['validade_proposta'] ?? '7';
$instagramHandle = $dados['instagram_handle'] ?? '@distintowedding';
$emailContato = $dados['email_contato'] ?? 'contato@wedistinto.com';
$whatsappNumero = $dados['whatsapp_numero'] ?? '+55 27 9 8858-6935';
// Buscar 2 depoimentos ativos da categoria 'casamento' do banco
$depoimento01Texto = 'Foi a melhor escolha que fizemos. Eles capturaram a essência do nosso dia de uma forma que nunca imaginamos.';
$depoimento01Autor = 'Fernanda & Thiago';
$depoimento02Texto = 'A sensibilidade da equipe é indescritível. Cada vez que vemos o vídeo, nos emocionamos como se estivéssemos lá de novo.';
$depoimento02Autor = 'Mariana & Lucas';
try {
    $dbDep = Database::get();
    $stmtDep = $dbDep->prepare("SELECT texto, autor FROM depoimentos WHERE categoria = 'casamento' AND ativo = 1 ORDER BY ordem ASC, RAND() LIMIT 2");
    $stmtDep->execute();
    $depRows = $stmtDep->fetchAll();
    if (!empty($depRows[0])) {
        $depoimento01Texto = $depRows[0]['texto'];
        $depoimento01Autor = $depRows[0]['autor'];
    }
    if (!empty($depRows[1])) {
        $depoimento02Texto = $depRows[1]['texto'];
        $depoimento02Autor = $depRows[1]['autor'];
    }
} catch (Exception $e) {
    // Usa os defaults acima se a tabela ainda não existir
}

// Itens dos Pacotes
$itensHeritage = $dados['itens_heritage'] ?? "Cobertura Documental Completa: Presença ilimitada no evento.\nO Álbum Heritage: Álbum luxo panorâmico 25x30cm.\nRéplicas para a Família: 02 Mini Álbuns réplicas.\nProdução Cinematográfica 4K: Filme completo (8 a 12 min).\nShort Film & Teasers: Vídeos curtos para redes sociais.\nUso de Drone Profissional: Imagens aéreas cinematográficas.\nEcossistema Digital: Galeria online vitalícia.";
$itensCinematic = $dados['itens_cinematic'] ?? "Cobertura Cinematográfica 8h: Foco narrativo e estético.\nSessão Engagement (Pré-Wedding): Ensaio externo com fotos e vídeo.\nShort Film: Filme de 4 a 6 minutos.\nSocial Content Kit: Material otimizado para Instagram.\nMaking Of Completo: Registro dos preparativos do casal.\nBônus: Pendrive de luxo com arquivos em alta resolução.";
$itensEssencial = $dados['itens_essencial'] ?? "Cobertura Fotográfica 6h: Foco no essencial do evento.\nGaleria Online: Entrega digital em alta resolução.\nEdição Especial: Curadoria de fotos com tratamento Distinto.\nEntrega em até 45 dias.";

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
        pointer-events: none;
        -webkit-user-drag: none;
        user-select: none;
    }

    /* Proteção Geral de Imagens */
    img {
        -webkit-user-drag: none;
        user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
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

    /* Animações de Revelação */
    .reveal-item {
        opacity: 0;
        transform: translateY(30px);
        transition: all 1s cubic-bezier(0.21, 1, 0.36, 1);
    }

    .reveal-item.active {
        opacity: 1;
        transform: translateY(0);
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
    <section class="slide"
        style="padding: 0; background: #fff; overflow: hidden; display: flex; flex-direction: row; height: 100vh; width: 100%;">
        <!-- Coluna Esquerda: Imagem -->
        <div
            style="flex: 1; background: #f0f0f0; display: flex; align-items: center; justify-content: flex-end; padding-right: 5vw; position: relative; height: 100%;">
            <!-- Retângulo decorativo cinza (esquerda) -->
            <div
                style="position: absolute; left: 0; top: 0; width: 50px; height: 100%; background: #dcdcdc; z-index: 1;">
            </div>

            <div
                style="width: 75%; aspect-ratio: 3/4; position: relative; z-index: 2; overflow: hidden; box-shadow: 20px 20px 0px rgba(0,0,0,0.02);">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-02.jpg') ?>"
                    style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </div>

        <!-- Coluna Direita: Conteúdo -->
        <div
            style="flex: 1.2; padding: 0 8vw; display: flex; flex-direction: column; justify-content: center; position: relative; background: #fff; height: 100%;">
            <h2
                style="font-family: var(--wedding-montserrat); font-size: 4rem; font-weight: 700; line-height: 1.1; margin-bottom: 40px; color: #1a1a1a; text-transform: uppercase; letter-spacing: -1px;">
                BEM-VINDOS<br>AO INÍCIO DA<br>MEMÓRIA DE<br>VOCÊS
            </h2>

            <div style="max-width: 480px;">
                <p
                    style="font-family: var(--wedding-montserrat); font-size: 1.2rem; font-weight: 700; color: #1a1a1a; margin-bottom: 10px;">
                    <?= $saudacaoCasal ?>
                </p>
                <p
                    style="font-family: var(--wedding-montserrat); font-size: 1.1rem; line-height: 1.8; color: #444; font-weight: 400;">
                    <?= nl2br($mensagemPessoal) ?>
                </p>
            </div>

            <!-- Logo Distinto no canto inferior direito -->
            <div style="position: absolute; bottom: 8vh; right: 6vw; width: 120px;">
                <img src="<?= raizUrl('/assets/distinto_logo.svg') ?>"
                    style="width: 100%; filter: brightness(0); opacity: 0.8;">
            </div>

            <!-- Elemento decorativo cinza (topo direito) -->
            <div style="position: absolute; top: 10vh; right: 0; width: 50px; height: 35px; background: #dcdcdc;"></div>
        </div>
    </section>

    <!-- PÁGINA 03: ONDE O TEMPO PARA (MANIFESTO) -->
    <section class="slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: row; align-items: center; justify-content: center; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Lado Esquerdo: Foto com Barra Decorativa -->
        <div
            style="flex: 1.2; height: 100%; position: relative; display: flex; align-items: center; justify-content: center;">
            <!-- Barra Cinza -->
            <div
                style="position: absolute; top: 0; left: 0; width: 60%; height: 100%; background: #dcdcdc; z-index: 1;">
            </div>
            <!-- Foto -->
            <div class="reveal-item"
                style="width: 80%; height: 80%; z-index: 2; position: relative; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-17.png') ?>"
                    style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </div>

        <!-- Lado Direito: Texto -->
        <div class="reveal-item"
            style="flex: 1; padding: 0 6vw; display: flex; flex-direction: column; justify-content: center; position: relative;">
            <h2
                style="font-family: var(--wedding-montserrat); font-size: 4.5rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase; line-height: 1; margin-bottom: 40px;">
                ONDE O<br>TEMPO PARA
            </h2>

            <div
                style="font-family: var(--wedding-montserrat); font-size: 1.1rem; line-height: 1.8; color: #333; font-weight: 400; max-width: 500px;">
                <p style="margin-bottom: 25px;">
                    Para nós, a melhor foto não é a mais nítida ou a que segue todas as regras. É aquela que faz vocês
                    sentirem tudo de novo.
                </p>
                <p style="margin-bottom: 25px;">
                    Acreditamos que toda história tem sua beleza. A "melhor foto" do nosso portfólio não é um troféu na
                    estante, mas sim aquele frame que captura o extraordinário no comum: o sussurro do noivo, o brilho
                    no olhar da <strong><?= $primeiroNomeNoiva ?></strong> ou a emoção genuína dos seus convidados.
                </p>
                <p style="margin-bottom: 25px;">
                    <strong>O Arrepio em um Clique.</strong> Nossa busca constante é pela imagem que faz o tempo parar.
                    Queremos que a fotografia seja uma experiência totalmente nova, permitindo que vocês vejam o
                    casamento de vocês sob uma perspectiva artística e sensível. É o registro que transforma variáveis,
                    como a luz e a fé que os une, no mais lindo sentido.
                </p>
            </div>

            <!-- Assinatura -->
            <div style="margin-top: 40px; text-align: right; width: 100%; max-width: 500px;">
                <p
                    style="font-family: var(--wedding-montserrat); font-size: 0.9rem; color: #666; letter-spacing: 0.1em; text-transform: uppercase;">
                    by Wellington Poncem
                </p>
            </div>
        </div>
    </section>

    <!-- PÁGINA 04: VISÃO E MISSÃO -->
    <section class="slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: column; overflow: hidden;">
        <!-- Topo: Textos -->
        <div
            style="flex: 1.2; padding: 10vh 10vw; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative;">
            <div style="position: absolute; top: 0; left: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>

            <h2
                style="font-family: var(--wedding-montserrat); font-size: 3.5rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase;">
                VISÃO E MISSÃO</h2>
            <p
                style="font-family: var(--wedding-montserrat); font-size: 1.4rem; font-weight: 300; color: #444; margin-bottom: 6vh;">
                A meta é arrepiar e eternizar o extraordinário.</p>

            <div style="display: flex; gap: 8vw; width: 100%; max-width: 1100px;">
                <!-- Missão -->
                <div style="flex: 1;">
                    <h3
                        style="font-family: var(--wedding-montserrat); font-size: 1.5rem; font-weight: 700; color: #1a1a1a; margin-bottom: 15px; text-transform: uppercase; text-align: center;">
                        MISSÃO</h3>
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; line-height: 1.8; color: #555; text-align: justify;">
                        Nossa missão é registrar histórias de amor com autenticidade e emoção. No Distinto, não buscamos
                        apenas o registro oficial, mas o "arrepio" que cada momento carrega. Estamos aqui para capturar
                        o que é real, do sussurro no altar à oração silenciosa, garantindo que cada detalhe seja
                        preservado com a verdade que ele merece, fazendo com que todas as variáveis do dia ganhem o mais
                        bonito sentido.
                    </p>
                </div>
                <!-- Visão -->
                <div style="flex: 1;">
                    <h3
                        style="font-family: var(--wedding-montserrat); font-size: 1.5rem; font-weight: 700; color: #1a1a1a; margin-bottom: 15px; text-transform: uppercase; text-align: center;">
                        VISÃO</h3>
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; line-height: 1.8; color: #555; text-align: justify;">
                        Ser o portal que permitirá reviverem a emoção do seu "sim" para sempre, reforçando que toda
                        história tem sua beleza. Enxergamos o nosso trabalho como uma ferramenta para transformar o
                        casamento de vocês em uma experiência totalmente nova, onde a nossa perspectiva artística cria
                        uma herança visual que se torna mais valiosa a cada ano que passa.
                    </p>
                </div>
            </div>
        </div>

        <!-- Base: Imagem -->
        <div style="width: 100%; aspect-ratio: 343/68; position: relative; overflow: hidden; background: #eee;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-03.jpg') ?>"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>

        <!-- Elemento decorativo cinza (lateral ocupando 100% da altura) -->
        <div
            style="position: absolute; right: 0; top: 0; width: 50px; height: 100%; background: #959595ff; z-index: 5; opacity: 0.8;">
        </div>
    </section>

    <!-- PÁGINA 05: PERSPECTIVA -->
    <section class="slide"
        style="padding: 0; background: #fff; display: flex; flex-direction: column; overflow: hidden; position: relative;">
        <!-- Topo: Imagem -->
        <div style="width: 100%; aspect-ratio: 343/68; position: relative; overflow: hidden; background: #eee;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-04.jpg') ?>"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>

        <!-- Base: Textos -->
        <div
            style="flex: 1; padding: 8vh 10vw; background: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <h2
                style="font-family: var(--wedding-montserrat); font-size: 3.5rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase; margin-bottom: 6vh; text-align: center; line-height: 1.1;">
                MAIS QUE UM ESTÚDIO,<br>UMA PERSPECTIVA
            </h2>

            <div style="display: flex; gap: 20px; width: 100%; max-width: 1100px;">
                <div style="flex: 1; background: #d9d9d9; padding: 40px; box-sizing: border-box;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; line-height: 1.8; color: #333; text-align: left;">
                        Não somos apenas um estúdio que aperta botões; somos uma equipe de especialistas focada em
                        comunicação inteligente e em transformar desafios em direção estratégica.
                        Não começamos com ideias soltas. Começamos com clareza. Nós analisamos a essência da história de
                        vocês para garantir que a nossa presença seja distinta e posicionada. Nossa meta é uma só:
                        arrepiar, entregando versões da história de vocês sob uma perspectiva que torna o casamento uma
                        experiência totalmente nova.
                    </p>
                </div>
                <div style="flex: 1; background: #d9d9d9; padding: 40px; box-sizing: border-box;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; line-height: 1.8; color: #333; text-align: left;">
                        Conteúdo com estratégia, não só estética. Acreditamos que a beleza ganha força quando tem
                        propósito. Por isso, nosso olhar vai além do "bonito". Criamos narrativas com intenção e foco no
                        sentir, capturando desde o sussurro do noivo até as lágrimas incontidas, em uma sequência que
                        transporta vocês de volta para o íntimo daquele momento.
                        Por que ser um DISTINTO? Porque acreditamos que toda história tem sua beleza e que ela merece
                        ser contada com clareza de posicionamento.
                    </p>
                </div>
            </div>
        </div>

    </section>

    <!-- PÁGINA 06: EXPERIÊNCIAS DISTINTAS -->
    <section class="slide"
        style="padding: 0; background: #fff; display: flex; flex-direction: row; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Lado Esquerdo: Textos -->
        <div
            style="flex: 1; padding: 0 8vw; display: flex; flex-direction: column; justify-content: center; position: relative; height: 100%;">
            <!-- Decorativo Superior Esquerdo -->
            <div style="position: absolute; top: 0; left: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>

            <h2
                style="font-family: var(--wedding-montserrat); font-size: 4rem; font-weight: 300; letter-spacing: 0.05em; color: #1a1a1a; text-transform: uppercase; line-height: 1.1; margin-bottom: 40px;">
                EXPERIÊNCIAS<br>DISTINTAS
            </h2>

            <div
                style="max-width: 700px; font-family: var(--wedding-montserrat); font-size: 1.1rem; line-height: 1.8; color: #444;">
                <p style="margin-bottom: 20px;">Na Distinto, não começamos com ideias soltas. Começamos com clareza.</p>

                <p style="margin-bottom: 20px;">
                    Desenhamos três caminhos estratégicos para que a história de <strong><?= $nomeCasal ?></strong> seja
                    preservada com a força e a verdade que merecem.
                </p>

                <p style="margin-bottom: 20px;">
                    Apresentamos nossas propostas de investimento. Cada uma delas foi pensada para transformar o seu
                    casamento em uma experiência totalmente nova, onde a nossa perspectiva artística garante que todas
                    as variáveis do dia ganhem o mais bonito sentido.
                </p>

                <p style="margin-bottom: 20px;">Escolham o caminho que melhor se conecta com o sonho de vocês.</p>

                <p style="font-weight: 700; color: #1a1a1a;">Nossa meta é uma só: arrepiar.</p>
            </div>

            <!-- Decorativo Inferior Esquerdo -->
            <div style="position: absolute; bottom: 10vh; left: 8vw; width: 180px; height: 60px; background: #dcdcdc;">
            </div>
        </div>

        <!-- Lado Direito: Imagem -->
        <div
            style="flex: 1; position: relative; display: flex; align-items: center; justify-content: center; height: 100%;">
            <!-- Fundo Cinza Decorativo na Direita -->
            <div
                style="position: absolute; top: 0; right: 0; width: 35%; height: 80%; background: #dcdcdc; z-index: 1;">
            </div>

            <div style="width: 80%; height: 80%; position: relative; z-index: 2;">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-05.png') ?>"
                    style="width: 100%; height: 100%; object-fit: contain;">
            </div>
        </div>
    </section>

    <!-- PÁGINA 07: FULL IMAGE -->
    <section class="slide" style="padding: 0; background: #000;">
        <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-06.jpg') ?>" class="img-bg"
            style="opacity: 1; z-index: 1;">
    </section>

    <!-- PÁGINA 08: EXPERIÊNCIA HERITAGE -->
    <section class="slide"
        style="padding: 0; background: #fff; display: flex; flex-direction: row; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Lado Esquerdo: Imagem -->
        <div style="flex: 1; height: 100%;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-07.png') ?>"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>

        <!-- Lado Direito: Detalhes -->
        <div
            style="flex: 1.2; padding: 0 6vw; display: flex; flex-direction: column; justify-content: center; position: relative; height: 100%; background: #f9f9f9;">
            <!-- Decorativos -->
            <div style="position: absolute; top: 0; right: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div style="position: absolute; bottom: 0; right: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div style="position: absolute; top: 0; left: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div style="position: absolute; bottom: 0; left: 0; width: 140px; height: 80px; background: #dcdcdc;"></div>

            <h2
                style="font-family: var(--wedding-montserrat); font-size: 3.5rem; font-weight: 300; letter-spacing: 0.05em; color: #1a1a1a; text-transform: uppercase; line-height: 1.1; margin-bottom: 30px;">
                EXPERIÊNCIA<br>HERITAGE
            </h2>

            <div style="font-family: var(--wedding-montserrat); font-size: 0.95rem; line-height: 1.6; color: #444;">
                <p style="margin-bottom: 25px; font-weight: 400;">
                    Este é o plano definitivo para casais que não aceitam lacunas. É a garantia de uma cobertura
                    onipresente, focada na construção da herança visual da sua família, do papel à tela.
                </p>

                <ul style="list-style: none; padding: 0; margin-bottom: 30px;">
                    <?php 
                    $linhas = explode("\n", trim($itensHeritage));
                    foreach($linhas as $linha): if(empty($linha)) continue; 
                    ?>
                    <li style="margin-bottom: 12px; position: relative; padding-left: 20px;">
                        <span style="position: absolute; left: 0; color: #1a1a1a;">•</span>
                        <?= $linha ?>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <div style="margin-top: auto; padding-top: 20px; position: relative; z-index: 10;">
                    <p style="font-style: italic; color: #666; font-size: 1.1rem; margin: 0;">
                        Investimento: <?= $dados['valor_heritage'] ? fmt($dados['valor_heritage']) : 'R$ 7.900,00' ?>
                        <?php if (!empty($dados['condicao_especial'])): ?>
                            <span style="font-size: 0.9rem;">(<?= $dados['condicao_especial'] ?>)</span>
                        <?php else: ?>
                            <span style="font-size: 0.9rem;">(Condição especial p/ amigos lagoinha)</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

        </div>
    </section>

    <!-- PÁGINA 09: EXPERIÊNCIA CINEMATIC -->
    <section class="slide"
        style="padding: 0; background: #fff; display: flex; flex-direction: row; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Lado Esquerdo: Detalhes -->
        <div
            style="flex: 1; padding: 0 8vw; display: flex; flex-direction: column; justify-content: center; position: relative; height: 100%;">
            <!-- Decorativo Superior Esquerdo -->
            <div style="position: absolute; top: 0; left: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>

            <h2
                style="font-family: var(--wedding-montserrat); font-size: 3.5rem; font-weight: 300; letter-spacing: 0.05em; color: #1a1a1a; text-transform: uppercase; line-height: 1.1; margin-bottom: 25px;">
                EXPERIÊNCIA<br>CINEMATIC
            </h2>

            <div style="font-family: var(--wedding-montserrat); font-size: 0.95rem; line-height: 1.6; color: #444;">
                <p style="margin-bottom: 20px; font-weight: 400;">
                    A união entre a fotografia artística e a dinâmica do vídeo moderno. Ideal para casamentos íntimos
                    (60 convidados) que buscam impacto visual e compartilhamento imediato.
                </p>

                <ul style="list-style: none; padding: 0; margin-bottom: 20px;">
                    <?php 
                    $linhas = explode("\n", trim($itensCinematic));
                    foreach($linhas as $linha): if(empty($linha)) continue; 
                    ?>
                    <li style="margin-bottom: 10px; position: relative; padding-left: 20px;">
                        <span style="position: absolute; left: 0; color: #1a1a1a;">•</span>
                        <?= $linha ?>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <div style="margin-top: auto; padding-top: 20px; position: relative; z-index: 10;">
                    <p style="font-style: italic; color: #666; font-size: 1.1rem; margin-bottom: 5px;">
                        Investimento: <?= $dados['valor_cinematic'] ? fmt($dados['valor_cinematic']) : 'R$ 4.500,00' ?>
                        <?php if (!empty($dados['condicao_especial_cinematic'])): ?>
                            <span style="font-size: 0.9rem;">(<?= $dados['condicao_especial_cinematic'] ?>)</span>
                        <?php else: ?>
                            <span style="font-size: 0.9rem;">(10% de desconto na entrada para contratos até
                                05/04/2026)</span>
                        <?php endif; ?>
                    </p>

                    <p style="font-weight: 700; color: #1a1a1a; font-size: 0.9rem; margin: 0;">
                        • Upgrade Família: Adicione o Álbum Master por apenas R$ 950,00.
                    </p>
                </div>
            </div>

            <!-- Decorativo Inferior Esquerdo -->
            <div style="position: absolute; bottom: 0; left: 8vw; width: 180px; height: 60px; background: #dcdcdc;">
            </div>
        </div>

        <!-- Lado Direito: Imagem -->
        <div
            style="flex: 1; position: relative; display: flex; align-items: center; justify-content: center; height: 100%;">
            <!-- Fundo Cinza Decorativo na Direita -->
            <div
                style="position: absolute; top: 0; right: 0; width: 35%; height: 80%; background: #dcdcdc; z-index: 1;">
            </div>

            <div style="width: 80%; height: 80%; position: relative; z-index: 2;">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-08.png') ?>"
                    style="width: 100%; height: 100%; object-fit: contain;">
            </div>
        </div>
    </section>

    <!-- PÁGINA 10: REGISTRO ESSENCIAL -->
    <section class="slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: row; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Lado Esquerdo: Imagem -->
        <div style="flex: 1; height: 100%; display: flex; align-items: center; justify-content: center;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-09.png') ?>"
                style="width: 100%; height: 80%; object-fit: contain;">
        </div>

        <!-- Lado Direito: Detalhes -->
        <div
            style="flex: 1.2; padding: 0 6vw; display: flex; flex-direction: column; justify-content: center; position: relative; height: 100%;">
            <!-- Decorativos -->
            <div style="position: absolute; top: 0; right: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div style="position: absolute; bottom: 0; right: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>

            <h2
                style="font-family: var(--wedding-montserrat); font-size: 3.5rem; font-weight: 300; letter-spacing: 0.05em; color: #1a1a1a; text-transform: uppercase; line-height: 1.1; margin-bottom: 25px;">
                REGISTRO<br>ESSENCIAL
            </h2>

            <div style="font-family: var(--wedding-montserrat); font-size: 0.95rem; line-height: 1.5; color: #444;">
                <p style="margin-bottom: 20px; font-weight: 400;">
                    Um registro focado estritamente no protocolo, ideal para cerimônias curtas e objetivas que exigem um
                    olhar profissional sobre os momentos principais.
                </p>

                <ul style="list-style: none; padding: 0; margin-bottom: 20px;">
                    <?php 
                    $linhas = explode("\n", trim($itensEssencial));
                    foreach($linhas as $linha): if(empty($linha)) continue; 
                    ?>
                    <li style="margin-bottom: 10px; position: relative; padding-left: 20px;">
                        <span style="position: absolute; left: 0; color: #1a1a1a;">•</span>
                        <?= $linha ?>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <p style="font-style: italic; color: #333; font-size: 1.1rem; margin-bottom: 25px;">
                    Investimento: <?= $dados['valor_essencial'] ? fmt($dados['valor_essencial']) : 'R$ 2.800,00' ?>
                </p>

                <div style="margin-top: 10px; border-top: 1px solid #dcdcdc; padding-top: 20px;">
                    <p
                        style="font-weight: 700; color: #1a1a1a; margin-bottom: 15px; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 0.05em;">
                        Upgrades que fazem toda diferença:
                    </p>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 10px; position: relative; padding-left: 20px;">
                            <span style="position: absolute; left: 0; color: #1a1a1a;">•</span>
                            <strong>Boudoir da Noiva (no dia do casamento):</strong> Um ensaio de 1 h realizado após a
                            maquiagem para registrar a beleza da noiva por R$ 500,00
                        </li>
                        <li style="margin-bottom: 10px; position: relative; padding-left: 20px;">
                            <span style="position: absolute; left: 0; color: #1a1a1a;">•</span>
                            <strong>Ensaio Pré-Wedding:</strong> Caso desejem apenas o ensaio externo antes do
                            casamento, ele pode ser contratado separadamente por R$ 1.100,00 (incluindo pencard e 30
                            fotos reveladas)
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- PÁGINA 11: INVESTIMENTO E PLANEJAMENTO -->
    <section class="slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: column; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Topo: Imagem -->
        <div style="height: 40%; width: 100%;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-10.png') ?>"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>

        <!-- Conteúdo -->
        <div
            style="flex: 1; padding: 40px 8vw; display: flex; flex-direction: column; align-items: center; text-align: center;">
            <h2
                style="font-family: var(--wedding-montserrat); font-size: 3rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase; margin-bottom: 5px;">
                INVESTIMENTO E PLANEJAMENTO
            </h2>
            <p
                style="font-family: var(--wedding-montserrat); font-size: 1.2rem; font-weight: 400; color: #444; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 40px;">
                FORMAS DE RESERVA E PAGAMENTO
            </p>

            <!-- Blocos de Condições -->
            <div style="width: 100%; max-width: 1200px;">
                <!-- Bloco Geral -->
                <div
                    style="background: #dcdcdc; padding: 25px 40px; margin-bottom: 15px; border-radius: 2px; text-align: center; font-family: var(--wedding-montserrat); font-size: 0.9rem; line-height: 1.6; color: #333;">
                    <?= nl2br($dados['condicoes_reserva'] ?? "A reserva da data é oficializada mediante a assinatura do contrato e o pagamento do sinal (entrada), que pode ser de 20% ou 25% do valor do pacote escolhido.\nOpções de Parcelamento: Oferecemos flexibilidade para que o saldo seja quitado de forma equilibrada até a data do evento:") ?>
                </div>

                <!-- Blocos Lado a Lado -->
                <div style="display: flex; gap: 15px; width: 100%;">
                    <div
                        style="flex: 1; background: #dcdcdc; padding: 25px 40px; border-radius: 2px; text-align: left;">
                        <h4
                            style="font-family: var(--wedding-montserrat); font-size: 0.95rem; font-weight: 700; color: #1a1a1a; text-transform: uppercase; margin-bottom: 10px;">
                            PARA EXPERIÊNCIA HERITAGE & EXPERIÊNCIA CINEMATIC</h4>
                        <p
                            style="font-family: var(--wedding-montserrat); font-size: 0.85rem; line-height: 1.5; color: #444;">
                            <?= nl2br($dados['condicoes_heritage_cinematic'] ?? "Entrada de 20% + Saldo parcelado em até 6x (dependendo do pacote selecionado)") ?>
                        </p>
                    </div>
                    <div
                        style="flex: 1; background: #dcdcdc; padding: 25px 40px; border-radius: 2px; text-align: left;">
                        <h4
                            style="font-family: var(--wedding-montserrat); font-size: 0.95rem; font-weight: 700; color: #1a1a1a; text-transform: uppercase; margin-bottom: 10px;">
                            PARA O REGISTRO ESSENCIAL</h4>
                        <p
                            style="font-family: var(--wedding-montserrat); font-size: 0.85rem; line-height: 1.5; color: #444;">
                            <?= nl2br($dados['condicoes_essencial'] ?? "Entrada de 25% + Saldo parcelado em até 5x (dependendo do pacote selecionado)") ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PÁGINA 12: WEDDING PORTFOLIO CAPA -->
    <section class="slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: column; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Topo: Textos -->
        <div style="height: 50%; display: flex; flex-direction: row; width: 100%;">
            <!-- Lado Esquerdo: Manifesto -->
            <div style="flex: 2; padding: 6vh 6vw; display: flex; flex-direction: column; justify-content: center;">
                <div
                    style="font-family: var(--wedding-montserrat); font-size: 1.15rem; line-height: 1.6; color: #333; font-weight: 400;">
                    <p style="margin-bottom: 20px;">
                        Acreditamos que, daqui a vinte anos, o que restará não serão apenas arquivos digitais, mas a
                        sensação exata do que foi o dia <strong>do seu casamento</strong>.
                    </p>
                    <p style="margin-bottom: 20px;">
                        Para nós, toda história tem sua beleza, e a beleza de vocês reside na intimidade de um
                        <strong>'sim'</strong> compartilhado entre amigos e na fé que os une.
                    </p>
                    <p style="font-size: 1rem; color: #444;">
                        Mais do que um registro, uma perspectiva. <strong>Sim</strong>, o casamento de vocês será
                        incrível, mas queremos que o filme e as fotos sejam uma experiência à parte. Nosso olhar busca o
                        que está nas entrelinhas. Desde o sussurro do noivo no altar até o riso solto dos convidados. Do
                        caos vibrante do making of à paz profunda da oração antes da cerimônia. Dos detalhes das mãos
                        que se entrelaçam às grandes paisagens que moldam o dia.
                    </p>
                </div>
            </div>

            <!-- Lado Direito: Título -->
            <div
                style="flex: 1.5; padding: 6vh 4vw; display: flex; flex-direction: column; justify-content: center; position: relative;">
                <!-- Decorativo Superior Direito -->
                <div style="position: absolute; top: 0; right: 0; width: 60px; height: 80px; background: #dcdcdc;">
                </div>

                <h2
                    style="font-family: var(--wedding-montserrat); font-size: 4.5rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase; line-height: 1; margin-bottom: 5px;">
                    WEDDING<br>PORTFOLIO
                </h2>
                <p
                    style="font-family: var(--wedding-montserrat); font-size: 1.4rem; font-weight: 400; color: #444; letter-spacing: 0.2em; text-transform: uppercase;">
                    VERSÕES DA SUA HISTÓRIA
                </p>
            </div>
        </div>

        <!-- Base: Grid de Fotos -->
        <div style="height: 50%; width: 100%;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-11.png') ?>"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>
    </section>

    <!-- PÁGINA 13: PORTFÓLIO PEDRO E VANESSA -->
    <section class="slide portfolio-slide"
        style="padding: 0; background: #000; display: flex; flex-direction: row; gap: 2px; overflow: hidden; height: 100vh; width: 100%;">

        <!-- Coluna Esquerda (Duas fotos empilhadas) -->
        <div style="flex: 2; display: flex; flex-direction: column; gap: 2px; height: 100%;">
            <!-- Foto Cima -->
            <div class="reveal-item" style="flex: 1; position: relative; overflow: hidden;">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-cima-12.png') ?>"
                    style="width: 100%; height: 100%; object-fit: cover;">
                <div style="position: absolute; top: 40px; left: 40px; z-index: 10;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; color: #fff; letter-spacing: 0.15em; text-transform: uppercase; font-weight: 300; opacity: 0.8;">
                        PEDRO E VANESSA - BEFORE THE BLOOM
                    </p>
                </div>
            </div>
            <!-- Foto Baixo -->
            <div class="reveal-item" style="flex: 1; overflow: hidden;">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-baixo-12.png') ?>"
                    style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </div>

        <!-- Coluna Direita (Foto inteira) -->
        <div class="reveal-item" style="flex: 1.1; overflow: hidden; height: 100%;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-direita-12.png') ?>"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>
    </section>

    <!-- PÁGINA 14: PORTFÓLIO GABRIEL E JULIA -->
    <section class="slide portfolio-slide"
        style="padding: 0; background: #000; display: flex; flex-direction: row; gap: 2px; overflow: hidden; height: 100vh; width: 100%;">

        <!-- Coluna Esquerda (Duas fotos empilhadas) -->
        <div style="flex: 1.8; display: flex; flex-direction: column; gap: 2px; height: 100%;">
            <!-- Foto Cima -->
            <div class="reveal-item" style="flex: 1; overflow: hidden;">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-cima-13.png') ?>"
                    style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <!-- Foto Baixo -->
            <div class="reveal-item" style="flex: 1.1; position: relative; overflow: hidden;">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-baixo-13.png') ?>"
                    style="width: 100%; height: 100%; object-fit: cover;">
                <div style="position: absolute; bottom: 40px; left: 40px; z-index: 10;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; color: #fff; letter-spacing: 0.15em; text-transform: uppercase; font-weight: 300; opacity: 0.8;">
                        GABRIEL E JULIA - PRÉ-WEDDING
                    </p>
                </div>
            </div>
        </div>

        <!-- Coluna Direita (Foto inteira) -->
        <div class="reveal-item" style="flex: 1; overflow: hidden; height: 100%;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-direita-13.png') ?>"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>
    </section>

    <!-- PÁGINA 15: PORTFÓLIO BRUNA E ROBSON -->
    <section class="slide portfolio-slide"
        style="padding: 0; background: #000; display: flex; flex-direction: row; gap: 2px; overflow: hidden; height: 100vh; width: 100%;">

        <!-- Coluna Esquerda (Duas fotos empilhadas) -->
        <div style="flex: 1.8; display: flex; flex-direction: column; gap: 2px; height: 100%;">
            <!-- Foto Cima -->
            <div class="reveal-item" style="flex: 1.1; overflow: hidden;">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-14-cima.png') ?>"
                    style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <!-- Foto Baixo -->
            <div class="reveal-item" style="flex: 1; position: relative; overflow: hidden;">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-14-baixo.png') ?>"
                    style="width: 100%; height: 100%; object-fit: cover;">
                <div style="position: absolute; bottom: 40px; left: 40px; z-index: 10;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; color: #fff; letter-spacing: 0.15em; text-transform: uppercase; font-weight: 300; opacity: 0.8;">
                        BRUNA E ROBSON - CASAMENTO CARTÓRIO
                    </p>
                </div>
            </div>
        </div>

        <!-- Coluna Direita (Foto inteira) -->
        <div class="reveal-item" style="flex: 1; overflow: hidden; height: 100%;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-14-direita.png') ?>"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>
    </section>

    <!-- PÁGINA 16: PORTFÓLIO CHRISTIAN E ALINE -->
    <section class="slide portfolio-slide"
        style="padding: 0; background: #000; display: flex; flex-direction: row; gap: 2px; overflow: hidden; height: 100vh; width: 100%;">

        <!-- Coluna Esquerda (Foto Vertical) -->
        <div class="reveal-item" style="flex: 1; height: 100%; overflow: hidden;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-15-esquerda.png') ?>"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>

        <!-- Coluna Direita (Composto) -->
        <div style="flex: 2.2; display: flex; flex-direction: column; gap: 2px; height: 100%;">
            <!-- Topo Direita -->
            <div class="reveal-item" style="flex: 1.2; position: relative; overflow: hidden;">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-15-cima.png') ?>"
                    style="width: 100%; height: 100%; object-fit: cover;">
                <div style="position: absolute; bottom: 40px; right: 40px; z-index: 10;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; color: #fff; letter-spacing: 0.15em; text-transform: uppercase; font-weight: 300; opacity: 0.8;">
                        CHRISTIAN E ALINE - WEDDING DAY
                    </p>
                </div>
            </div>
            <!-- Base Direita (Duas fotos) -->
            <div style="flex: 1; display: flex; flex-direction: row; gap: 2px; height: 100%;">
                <div class="reveal-item" style="flex: 1; overflow: hidden;">
                    <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-15-baixo-esquerda.png') ?>"
                        style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div class="reveal-item" style="flex: 1; overflow: hidden;">
                    <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-15-baixo-direita.png') ?>"
                        style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            </div>
        </div>
    </section>

    <!-- PÁGINA 17: OS OLHARES POR TRÁS DAS LENTES (EQUIPE) -->
    <section class="slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Cabeçalho -->
        <div class="reveal-item" style="text-align: center; margin-bottom: 60px; z-index: 10;">
            <h2
                style="font-family: var(--wedding-montserrat); font-size: 4rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase; margin-bottom: 10px;">
                OS OLHARES POR TRÁS DAS LENTES
            </h2>
            <p style="font-family: var(--wedding-montserrat); font-size: 1.2rem; color: #888; font-weight: 400;">
                Não somos apenas técnicos. Somos contadores de histórias.
            </p>
        </div>

        <!-- Barra Decorativa Cinza -->
        <div
            style="position: absolute; top: 50%; left: 0; width: 100%; height: 120px; background: #dcdcdc; transform: translateY(-50%); z-index: 1;">
        </div>

        <!-- Grid da Equipe -->
        <div
            style="display: flex; flex-direction: row; gap: 40px; z-index: 10; width: 90%; max-width: 1400px; justify-content: center;">

            <!-- Jeane -->
            <div class="reveal-item" style="flex: 1; text-align: center;">
                <div style="width: 100%; aspect-ratio: 1/1; overflow: hidden; margin-bottom: 20px; border-radius: 2px;">
                    <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-16-jeane.png') ?>"
                        style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <h4
                    style="font-family: var(--wedding-montserrat); font-size: 1.1rem; font-weight: 700; color: #1a1a1a; margin-bottom: 5px;">
                    Jeane Poncem</h4>
                <p style="font-family: var(--wedding-montserrat); font-size: 0.85rem; color: #666; font-style: italic;">
                    Curadora de Sonhos &<br>Guardiã da Narrativa</p>
            </div>

            <!-- Wellington -->
            <div class="reveal-item" style="flex: 1; text-align: center;">
                <div style="width: 100%; aspect-ratio: 1/1; overflow: hidden; margin-bottom: 20px; border-radius: 2px;">
                    <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-16-wellington.png') ?>"
                        style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <h4
                    style="font-family: var(--wedding-montserrat); font-size: 1.1rem; font-weight: 700; color: #1a1a1a; margin-bottom: 5px;">
                    Wellington Poncem</h4>
                <p style="font-family: var(--wedding-montserrat); font-size: 0.85rem; color: #666; font-style: italic;">
                    O Arquiteto de Emoções</p>
            </div>

            <!-- Isabelly -->
            <div class="reveal-item" style="flex: 1; text-align: center;">
                <div style="width: 100%; aspect-ratio: 1/1; overflow: hidden; margin-bottom: 20px; border-radius: 2px;">
                    <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-16-isabelly.png') ?>"
                        style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <h4
                    style="font-family: var(--wedding-montserrat); font-size: 1.1rem; font-weight: 700; color: #1a1a1a; margin-bottom: 5px;">
                    Isabelly Gomes</h4>
                <p style="font-family: var(--wedding-montserrat); font-size: 0.85rem; color: #666; font-style: italic;">
                    A Curadora da Verdade</p>
            </div>

            <!-- Gabryel -->
            <div class="reveal-item" style="flex: 1; text-align: center;">
                <div style="width: 100%; aspect-ratio: 1/1; overflow: hidden; margin-bottom: 20px; border-radius: 2px;">
                    <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-16-gabriel.png') ?>"
                        style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <h4
                    style="font-family: var(--wedding-montserrat); font-size: 1.1rem; font-weight: 700; color: #1a1a1a; margin-bottom: 5px;">
                    Gabryel Oliveira</h4>
                <p style="font-family: var(--wedding-montserrat); font-size: 0.85rem; color: #666; font-style: italic;">
                    O Contador de Instantes</p>
            </div>

        </div>
    </section>

    <!-- PÁGINA 18: PROVA SOCIAL & COMPROMISSO -->
    <section class="slide" style="padding: 0; background: #1a1a1a; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden; position: relative; height: 100vh; width: 100%; color: #fff;">
        
        <div style="display: flex; flex-direction: row; width: 90%; max-width: 1200px; gap: 80px; z-index: 10;">
            <!-- Lado Esquerdo: Depoimentos -->
            <div style="flex: 1.5;">
                <h2 style="font-family: var(--wedding-serif); font-size: 3rem; italic; color: var(--wedding-gold); margin-bottom: 50px;">O que dizem<br>nossos casais...</h2>
                
                <div class="reveal-item" style="margin-bottom: 40px; border-left: 2px solid var(--wedding-gold); padding-left: 30px;">
                    <p style="font-family: var(--wedding-montserrat); font-size: 1.1rem; line-height: 1.6; font-style: italic; margin-bottom: 15px; opacity: 0.9;">
                        "<?= $depoimento01Texto ?>"
                    </p>
                    <p style="font-family: var(--wedding-montserrat); font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--wedding-gold);">
                        — <?= $depoimento01Autor ?>
                    </p>
                </div>

                <div class="reveal-item" style="border-left: 2px solid var(--wedding-gold); padding-left: 30px;">
                    <p style="font-family: var(--wedding-montserrat); font-size: 1.1rem; line-height: 1.6; font-style: italic; margin-bottom: 15px; opacity: 0.9;">
                        "<?= $depoimento02Texto ?>"
                    </p>
                    <p style="font-family: var(--wedding-montserrat); font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--wedding-gold);">
                        — <?= $depoimento02Autor ?>
                    </p>
                </div>
            </div>

            <!-- Lado Direito: Cronograma -->
            <div style="flex: 1; background: rgba(255,255,255,0.05); padding: 50px; border-radius: 4px; display: flex; flex-direction: column; justify-content: center;">
                <h3 style="font-family: var(--wedding-montserrat); font-size: 1.2rem; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase; margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px;">
                    NOSSO COMPROMISSO
                </h3>
                
                <div class="reveal-item" style="margin-bottom: 30px;">
                    <p style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: #888; margin-bottom: 5px;">Prévias do Casamento</p>
                    <p style="font-family: var(--wedding-serif); font-size: 2rem; color: #fff;"><?= $prazoPrevias ?></p>
                </div>

                <div class="reveal-item">
                    <p style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: #888; margin-bottom: 5px;">Material Final</p>
                    <p style="font-family: var(--wedding-serif); font-size: 2rem; color: #fff;"><?= $prazoFinal ?></p>
                </div>
            </div>
        </div>

        <!-- Fundo Decorativo -->
        <div style="position: absolute; top: 0; right: 0; width: 30%; height: 100%; background: linear-gradient(to right, transparent, rgba(197, 168, 128, 0.05));"></div>
    </section>

    <!-- PÁGINA 19: VAMOS DAR O PRÓXIMO PASSO? -->
    <section class="slide" style="padding: 0; background: #fff; display: flex; flex-direction: row; overflow: hidden; height: 100vh; width: 100%;">
        
        <!-- Lado Esquerdo: Conteúdo -->
        <div style="flex: 1; padding: 8vh 6vw; display: flex; flex-direction: column; justify-content: center; background: #f4f4f4; position: relative;">
            <!-- Decorativos -->
            <div style="position: absolute; top: 0; left: 0; width: 80px; height: 40px; background: #dcdcdc;"></div>
            <div style="position: absolute; bottom: 0; left: 0; width: 40px; height: 80px; background: #dcdcdc;"></div>

            <h2 class="reveal-item" style="font-family: var(--wedding-montserrat); font-size: 4rem; font-weight: 300; letter-spacing: 0.05em; color: #1a1a1a; text-transform: uppercase; line-height: 1.1; margin-bottom: 40px;">
                VAMOS DAR O<br>PRÓXIMO PASSO?
            </h2>

            <!-- Contatos -->
            <div class="reveal-item" style="margin-bottom: 40px; font-family: var(--wedding-montserrat); font-size: 1.2rem; line-height: 2; color: #1a1a1a;">
                <?php 
                    $wa_clean = str_replace([' ', '-', '+'], '', $whatsappNumero);
                    $wa_msg = urlencode("Olá! Acabamos de ver a proposta da Distinto para o nosso casamento e ficamos encantados com o olhar de vocês. Gostaríamos de conversar sobre os próximos passos para garantir nossa data!");
                    $wa_link = "https://wa.me/{$wa_clean}?text=" . $wa_msg;
                ?>
                <a href="<?= $wa_link ?>" target="_blank" style="display: block; text-decoration: none; color: #1a1a1a; transition: all 0.3s;">
                    <span style="border-bottom: 1px solid #ccc;"><?= $whatsappNumero ?></span>
                </a>
                <a href="mailto:<?= $emailContato ?>" style="display: block; text-decoration: none; color: #1a1a1a; transition: all 0.3s;">
                    <?= $emailContato ?>
                </a>
                <a href="https://instagram.com/<?= str_replace('@', '', $instagramHandle) ?>" target="_blank" style="display: block; text-decoration: none; color: #1a1a1a; transition: all 0.3s;">
                    <?= $instagramHandle ?>
                </a>
            </div>

            <!-- Texto de Apoio -->
            <div class="reveal-item" style="font-family: var(--wedding-montserrat); font-size: 0.95rem; line-height: 1.8; color: #555; font-style: italic; max-width: 480px;">
                <p style="margin-bottom: 20px;">
                    Se algo aqui ainda não fez o coração de vocês vibrar, vamos trocar uma ideia. Estamos prontos para encontrar uma solução para o seu caso em particular, moldando cada detalhe para que esta experiência seja totalmente nova e única para vocês.
                </p>
                <p style="margin-bottom: 20px;">
                    Este é o primeiro capítulo da história oficial de <strong><?= $nomeCasal ?></strong>, e nossa meta é uma só: fazer todas as variáveis desse dia ganharem o mais bonito sentido, garantindo que o arrepio do 'sim' dure para sempre através do nosso olhar.
                </p>
                <p style="font-size: 0.8rem; color: var(--wedding-gold); font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 10px;">
                    * Proposta válida por <?= $validadeProposta ?> dias.
                </p>
            </div>

            <!-- Footer -->
            <div style="margin-top: 60px; opacity: 0.3; font-family: var(--wedding-montserrat); font-size: 0.7rem; letter-spacing: 0.3em; text-transform: uppercase;">
                Distinto Wedding © <?= date('Y') ?>
            </div>
        </div>

        <!-- Lado Direito: Imagem -->
        <div class="reveal-item" style="flex: 1.2; height: 100%; position: relative;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-18.png') ?>" style="width: 100%; height: 100%; object-fit: cover;">
            <!-- Elemento decorativo topo direito -->
            <div style="position: absolute; top: 0; right: 0; width: 60px; height: 100px; background: #dcdcdc; opacity: 0.8;"></div>
        </div>
    </section>

    <!-- PÁGINA 19: THANK YOU -->
    <section class="slide" style="padding: 0; background: #f4f4f4; display: flex; flex-direction: column; overflow: hidden; height: 100vh; width: 100%;">
        
        <!-- Topo: Mensagem de Agradecimento -->
        <div style="height: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 0 4vw;">
            <h2 class="reveal-item" style="font-family: var(--wedding-montserrat); font-size: 6rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase; margin-bottom: 20px;">
                THANK YOU
            </h2>
            <p class="reveal-item" style="font-family: var(--wedding-montserrat); font-size: 1.4rem; color: #333; font-weight: 400; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 10px;">
                REGISTRANDO HISTÓRIAS DE AMOR COM AUTENTICIDADE E EMOÇÃO
            </p>
            <p class="reveal-item" style="font-family: var(--wedding-montserrat); font-size: 0.9rem; color: #888; letter-spacing: 0.1em;">
                by Distinto
            </p>
        </div>

        <!-- Base: Imagem Panorâmica -->
        <div class="reveal-item" style="height: 50%; width: 100%; overflow: hidden;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-19.png') ?>" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
    </section>

</div>

<script>
    // Inicializar ícones se necessário
    if (window.lucide) lucide.createIcons();
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const items = entry.target.querySelectorAll('.reveal-item');
                    items.forEach((item, index) => {
                        setTimeout(() => {
                            item.classList.add('active');
                        }, index * 150);
                    });
                }
            });
        }, observerOptions);

        document.querySelectorAll('.slide').forEach(slide => {
            observer.observe(slide);
        });

        // Bloquear clique direito em imagens
        document.addEventListener('contextmenu', (e) => {
            if (e.target.tagName === 'IMG') {
                e.preventDefault();
                return false;
            }
        }, false);

        // Bloquear arraste de imagens
        document.addEventListener('dragstart', (e) => {
            if (e.target.tagName === 'IMG') {
                e.preventDefault();
                return false;
            }
        }, false);
    });
</script>