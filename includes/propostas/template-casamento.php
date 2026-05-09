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
// Gerar mensagem personalizada para o WhatsApp via IA
require_once __DIR__ . '/../ia_propostas.php';
$mensagemWA = IAPropostas::gerarMensagemWhatsApp($nomeNoivo, $nomeNoiva, $nomeCasal);

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
} catch (Exception $e) {}

// PLANOS E SERVIÇOS (HARDCODED)
$servicosWedding = [
    'cobertura_6h' => ['nome' => 'Cobertura Fotográfica 6h', 'valor' => 0],
    'cobertura_8h' => ['nome' => 'Cobertura Cinematográfica 8h', 'valor' => 0],
    'cobertura_full' => ['nome' => 'Cobertura Documental Completa', 'valor' => 0],
    'album_20x20' => ['nome' => 'Álbum 20x20 - 40 fotos', 'valor' => 800],
    'album_30x30' => ['nome' => 'Álbum 30x30 - 60 fotos', 'valor' => 1500],
    'prewedding' => ['nome' => 'Ensaio Pré-Wedding', 'valor' => 1200],
    'boudoir' => ['nome' => 'Boudoir da Noiva', 'valor' => 800],
    'pencard' => ['nome' => 'Pencard Exclusivo', 'valor' => 250]
];

$planosWedding = [
    [
        'id' => 'essencial',
        'nome' => 'Registro Essencial',
        'preco_venda' => (float)($dados['valor_essencial'] ?? 2800),
        'descricao' => 'Cobertura Fotográfica 6h',
        'prazo_minimo' => 5,
        'itens_json' => json_encode(['cobertura_6h' => 'incluso', 'pencard' => 'incluso', 'album_20x20' => 'opcional'])
    ],
    [
        'id' => 'cinematic',
        'nome' => 'Experiência Cinematic',
        'preco_venda' => (float)($dados['valor_cinematic'] ?? 4500),
        'descricao' => 'Cobertura Cinematográfica 8h',
        'prazo_minimo' => 6,
        'itens_json' => json_encode(['cobertura_8h' => 'incluso', 'album_20x20' => 'incluso', 'prewedding' => 'opcional', 'boudoir' => 'opcional'])
    ],
    [
        'id' => 'heritage',
        'nome' => 'Experiência Heritage',
        'preco_venda' => (float)($dados['valor_heritage'] ?? 7900),
        'descricao' => 'Cobertura Documental Completa',
        'prazo_minimo' => 6,
        'itens_json' => json_encode(['cobertura_full' => 'incluso', 'album_30x30' => 'incluso', 'prewedding' => 'incluso', 'boudoir' => 'incluso'])
    ]
];

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

    /* Resetar estilos globais para este template */
    html, body {
        height: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
    }

    /* Forçar o wrapper global a ser o único container de scroll */
    .type-casamento .proposal-wrapper {
        height: 100vh !important;
        overflow-y: scroll !important;
        scroll-snap-type: y proximity !important;
        scroll-behavior: smooth !important;
        -webkit-overflow-scrolling: touch;
    }

    /* A div interna não deve ter scroll próprio */
    .wedding-proposal {
        height: auto !important;
        overflow: visible !important;
        background: var(--wedding-bg);
        color: var(--wedding-dark);
        font-family: var(--wedding-sans);
    }

    .slide {
        height: 100vh !important;
        width: 100%;
        scroll-snap-align: start !important;
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
            font-size: 2.2rem;
        }

        /* Ajustes Capa Mobile */
        .capa-content {
            padding: 5vh 6vw !important;
            justify-content: flex-start !important;
            gap: 15px !important;
        }

        .capa-titulo {
            font-size: 3.5rem !important;
        }

        .capa-subtitulo {
            font-size: 0.8rem !important;
            letter-spacing: 0.25em !important;
            margin-top: 0px !important;
        }

        .capa-bottom-box {
            text-align: center !important;
            max-width: 100% !important;
            margin-top: 10px !important;
        }

        .capa-casal {
            font-size: 1.8rem !important;
            margin-bottom: 5px !important;
        }

        .capa-desc {
            font-size: 0.95rem !important;
            margin-bottom: 10px !important;
            max-width: 100% !important;
            line-height: 1.4 !important;
        }

        .capa-line {
            margin: 0 auto 15px auto !important;
        }

        /* Ajustes Boas-vindas Mobile */
        .boas-vindas-slide {
            flex-direction: column !important;
            height: auto !important;
            min-height: 100vh;
        }

        .boas-vindas-img-col {
            flex: none !important;
            height: 45vh !important;
            width: 100% !important;
            padding-right: 0 !important;
            justify-content: center !important;
        }

        .boas-vindas-img-decor {
            width: 30px !important;
        }

        .boas-vindas-text-col {
            flex: none !important;
            padding: 50px 30px !important;
            height: auto !important;
            text-align: left !important;
        }

        .boas-vindas-titulo {
            font-size: 2.2rem !important;
            line-height: 1.1 !important;
            margin-bottom: 25px !important;
        }

        .boas-vindas-logo {
            display: none !important;
        }

        /* Ajustes Manifesto Mobile */
        .manifesto-slide {
            flex-direction: column !important;
            height: auto !important;
            min-height: 100vh;
        }

        .manifesto-img-col {
            flex: none !important;
            height: 40vh !important;
            width: 100% !important;
        }

        .manifesto-text-col {
            flex: none !important;
            padding: 40px 30px !important;
            height: auto !important;
        }

        .manifesto-texto {
            font-size: 1.4rem !important;
            line-height: 1.4 !important;
        }

        .manifesto-titulo {
            font-size: 2.5rem !important;
            line-height: 1.1 !important;
            margin-bottom: 20px !important;
            letter-spacing: 0.05em !important;
        }

        /* Ajustes Visão e Missão Mobile */
        .missao-visao-slide {
            height: auto !important;
        }

        .missao-visao-content {
            padding: 60px 30px !important;
        }

        .missao-visao-titulo {
            font-size: 2rem !important;
            margin-bottom: 10px !important;
            text-align: center;
        }

        .missao-visao-subtitulo {
            font-size: 1rem !important;
            text-align: center;
            margin-bottom: 40px !important;
        }

        .missao-visao-grid {
            flex-direction: column !important;
            gap: 40px !important;
        }

        .missao-visao-decor {
            display: none !important;
        }

        .missao-visao-square {
            display: none !important;
        }

        /* Ajustes Perspectiva Mobile */
        .perspectiva-titulo {
            font-size: 2rem !important;
            margin-bottom: 30px !important;
        }

        .perspectiva-grid {
            flex-direction: column !important;
            gap: 20px !important;
        }

        .perspectiva-box {
            padding: 30px 25px !important;
        }

        .perspectiva-slide {
            height: auto !important;
            min-height: 100vh;
            display: block !important;
        }

        .perspectiva-img-box {
            height: 30vh !important;
            aspect-ratio: auto !important;
        }

        .perspectiva-content-box {
            padding: 40px 30px !important;
            justify-content: flex-start !important;
            height: auto !important;
        }

        /* Ajustes Experiências Mobile */
        .experiencias-slide {
            flex-direction: column !important;
            height: auto !important;
            min-height: 100vh;
        }

        .experiencias-text-col {
            flex: none !important;
            padding: 30px 30px !important;
            height: auto !important;
            order: 2;
        }

        .experiencias-img-col {
            flex: none !important;
            height: 45vh !important;
            width: 100% !important;
            order: 1;
        }

        .experiencias-img-box {
            width: 90% !important;
            height: 90% !important;
        }

        .experiencias-titulo {
            font-size: 2.2rem !important;
            line-height: 1.1 !important;
            margin-bottom: 25px !important;
        }

        .experiencias-decor {
            display: none !important;
        }

        /* Ajustes Pacotes Mobile */
        .package-slide {
            flex-direction: column !important;
            height: auto !important;
            padding: 0 !important;
            overflow: visible !important;
        }

        .package-img-col {
            flex: none !important;
            height: 60vh !important;
            width: 100% !important;
            background: transparent !important;
        }

        .package-text-col {
            flex: none !important;
            padding: 50px 30px !important;
            height: auto !important;
            background: #fff !important;
        }

        .package-img-box {
            width: 100% !important;
            height: 100% !important;
        }

        .package-titulo {
            font-size: 2.2rem !important;
            line-height: 1.1 !important;
            margin-bottom: 25px !important;
        }

        .package-decor {
            display: none !important;
        }

        /* Ajustes Portfolio Capa Mobile */
        .portfolio-capa-slide {
            flex-direction: column !important;
            height: auto !important;
        }

        .portfolio-capa-content {
            flex-direction: column !important;
            height: auto !important;
        }

        .portfolio-capa-text-box {
            padding: 40px 30px !important;
            order: 2;
        }

        .portfolio-capa-title-box {
            padding: 40px 30px !important;
            order: 1;
            text-align: center;
        }

        .portfolio-capa-titulo {
            font-size: 2.5rem !important;
            margin-bottom: 10px !important;
        }

        .portfolio-capa-subtitulo {
            font-size: 1.1rem !important;
        }

        .portfolio-capa-img-box {
            height: 45vh !important;
            order: 3;
        }

        /* Ajustes Portfolio Feed Mobile */
        .portfolio-slide {
            flex-direction: column !important;
            height: auto !important;
            gap: 2px !important;
        }

        .portfolio-left-col,
        .portfolio-right-col {
            flex: none !important;
            width: 100% !important;
            height: auto !important;
            display: flex !important;
            flex-direction: column !important;
        }

        .portfolio-img-item {
            height: 50vh !important;
            flex: none !important;
        }

        .portfolio-img-v {
            height: 75vh !important;
        }

        .portfolio-label {
            top: 20px !important;
            left: 20px !important;
        }

        .portfolio-label p {
            font-size: 0.8rem !important;
        }

        /* Ajustes Equipe Mobile */
        .team-slide {
            height: auto !important;
            padding: 60px 20px !important;
        }

        .team-header {
            margin-bottom: 30px !important;
        }

        .team-title {
            font-size: 2.2rem !important;
            line-height: 1.1 !important;
        }

        .team-decor-bar {
            display: none !important;
        }

        .team-grid {
            flex-direction: row !important;
            flex-wrap: wrap !important;
            gap: 20px !important;
            width: 100% !important;
            justify-content: center !important;
        }

        .team-item {
            flex: none !important;
            width: 45% !important;
        }

        .team-item div {
            width: 100% !important;
            margin: 0 auto 10px !important;
        }

        .team-item h4 {
            font-size: 0.9rem !important;
        }

        .team-item p {
            font-size: 0.75rem !important;
        }

        /* Ajustes Depoimentos Mobile */
        .depo-slide {
            height: auto !important;
            padding: 60px 20px !important;
        }

        .depo-container {
            flex-direction: column !important;
            gap: 50px !important;
            width: 100% !important;
        }

        .depo-col-left,
        .depo-col-right {
            flex: none !important;
            width: 100% !important;
        }

        .depo-title {
            font-size: 2.2rem !important;
            margin-bottom: 30px !important;
        }

        .depo-col-right {
            padding: 40px 30px !important;
        }

        .depo-col-right h2,
        .depo-col-right p {
            font-size: 1.5rem !important;
        }

        /* Ajustes Contato Mobile */
        .contato-slide {
            flex-direction: column !important;
            height: auto !important;
        }

        .contato-col-text {
            flex: none !important;
            width: 100% !important;
            padding: 50px 30px !important;
            order: 2;
        }

        .contato-col-img {
            flex: none !important;
            width: 100% !important;
            height: 45vh !important;
            order: 1;
        }

        .contato-title {
            font-size: 2.2rem !important;
            margin-bottom: 30px !important;
        }

        /* Ajustes Thank You Mobile */
        .thanks-title {
            font-size: 3.5rem !important;
            margin-bottom: 15px !important;
            letter-spacing: 0.05em !important;
        }

        .thanks-subtitle {
            font-size: 0.9rem !important;
            line-height: 1.4 !important;
            padding: 0 10px !important;
        }
    }

    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: scale(0.98);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }
</style>

<div class="wedding-proposal">

    <!-- PÁGINA 01: CAPA -->
    <section class="slide" style="padding: 0; display: block; background: #eee;">
        <img src="<?= raizUrl('/imagens-proposta-casamento/bg-section-01.jpg') ?>" class="img-bg"
            style="opacity: 1; z-index: 1;">

        <div class="content-overlay capa-content"
            style="height: 100%; width: 100%; display: flex; flex-direction: column; justify-content: space-between; padding: 10vh 8vw; box-sizing: border-box; max-width: 100%;">
            <!-- Topo Centro -->
            <div style="text-align: center; width: 100%;">
                <h1 class="capa-titulo"
                    style="font-family: var(--wedding-script); font-size: 8rem; color: #1a1a1a; margin-bottom: 0; font-weight: 400; text-transform: none; letter-spacing: 0;">
                    Casamento</h1>
                <p class="capa-subtitulo"
                    style="font-family: var(--wedding-montserrat); font-size: 1.8rem; letter-spacing: 0.6em; color: #1a1a1a; margin-top: -10px; font-weight: 300;">
                    FOTOGRAFIA E FILMMAKING</p>
            </div>

            <!-- Baixo Esquerda -->
            <div class="capa-bottom-box" style="text-align: left; max-width: 500px; color: #1a1a1a;">
                <h2 class="capa-casal"
                    style="font-family: var(--wedding-montserrat); font-size: 2.2rem; font-weight: 800; letter-spacing: 0.05em; line-height: 1.2; margin-bottom: 20px;">
                    <?php
                    $noivoUpper = mb_strtoupper($primeiroNomeNoivo);
                    $noivaUpper = mb_strtoupper($primeiroNomeNoiva);
                    echo "{$noivoUpper} &<br>{$noivaUpper}";
                    ?>
                </h2>
                <p class="capa-desc"
                    style="font-family: var(--wedding-montserrat); font-size: 1.4rem; line-height: 1.6; font-weight: 400; margin-bottom: 20px; opacity: 0.8;">
                    Toda história tem sua beleza. Nós entregamos a nossa versão da sua sob a nossa perspectiva.
                </p>
                <div class="capa-line"
                    style="width: 40px; height: 1px; background: #1a1a1a; margin-bottom: 20px; opacity: 0.5;"></div>
                <p
                    style="font-family: var(--wedding-montserrat); font-size: 1rem; font-weight: 400; letter-spacing: 0.05em; opacity: 0.8;">
                    by Distinto wedding</p>
            </div>
        </div>
    </section>

    <!-- PÁGINA 02: BOAS-VINDAS -->
    <section class="slide boas-vindas-slide"
        style="padding: 0; background: #fff; overflow: hidden; display: flex; flex-direction: row; height: 100vh; width: 100%;">
        <!-- Coluna Esquerda: Imagem -->
        <div class="boas-vindas-img-col"
            style="flex: 1; background: #f0f0f0; display: flex; align-items: center; justify-content: flex-end; padding-right: 5vw; position: relative; height: 100%;">
            <!-- Retângulo decorativo cinza (esquerda) -->
            <div class="boas-vindas-img-decor"
                style="position: absolute; left: 0; top: 0; width: 50px; height: 100%; background: #dcdcdc; z-index: 1;">
            </div>

            <div
                style="width: 75%; aspect-ratio: 3/4; position: relative; z-index: 2; overflow: hidden; box-shadow: 20px 20px 0px rgba(0,0,0,0.02);">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-02.jpg') ?>"
                    style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </div>

        <!-- Coluna Direita: Conteúdo -->
        <div class="boas-vindas-text-col"
            style="flex: 1.2; padding: 0 8vw; display: flex; flex-direction: column; justify-content: center; position: relative; background: #fff; height: 100%;">
            <h1 class="boas-vindas-titulo"
                style="font-family: var(--wedding-montserrat); font-size: 3.8rem; font-weight: 700; line-height: 1.1; margin-bottom: 40px; color: #1a1a1a; text-transform: uppercase; letter-spacing: -1px;">
                BEM-VINDOS<br>AO INÍCIO DA<br>MEMÓRIA DE<br>VOCÊS
            </h1>

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
            <div class="boas-vindas-logo" style="position: absolute; bottom: 8vh; right: 6vw; width: 120px;">
                <img src="<?= raizUrl('/assets/distinto_logo.svg') ?>"
                    style="width: 100%; filter: brightness(0); opacity: 0.8;">
            </div>

            <!-- Elemento decorativo cinza (topo direito) -->
            <div style="position: absolute; top: 10vh; right: 0; width: 50px; height: 35px; background: #dcdcdc;"></div>
        </div>
    </section>

    <!-- PÁGINA 03: ONDE O TEMPO PARA (MANIFESTO) -->
    <section class="slide manifesto-slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: row; align-items: center; justify-content: center; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Lado Esquerdo: Foto com Barra Decorativa -->
        <div class="manifesto-img-col"
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
        <div class="reveal-item manifesto-text-col"
            style="flex: 1; padding: 0 6vw; display: flex; flex-direction: column; justify-content: center; position: relative;">
            <h2 class="manifesto-titulo"
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
    <section class="slide missao-visao-slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: column; overflow: hidden;">
        <!-- Topo: Textos -->
        <div class="missao-visao-content"
            style="flex: 1.2; padding: 10vh 10vw; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative;">
            <div class="missao-visao-square"
                style="position: absolute; top: 0; left: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>

            <h2 class="missao-visao-titulo"
                style="font-family: var(--wedding-montserrat); font-size: 3.5rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase;">
                VISÃO E MISSÃO</h2>
            <p class="missao-visao-subtitulo"
                style="font-family: var(--wedding-montserrat); font-size: 1.4rem; font-weight: 300; color: #444; margin-bottom: 6vh;">
                A meta é arrepiar e eternizar o extraordinário.</p>

            <div class="missao-visao-grid" style="display: flex; gap: 8vw; width: 100%; max-width: 1100px;">
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
        <div class="missao-visao-decor"
            style="position: absolute; right: 0; top: 0; width: 50px; height: 100%; background: #959595ff; z-index: 5; opacity: 0.8;">
        </div>
    </section>

    <!-- PÁGINA 05: PERSPECTIVA -->
    <section class="slide perspectiva-slide"
        style="padding: 0; background: #fff; display: flex; flex-direction: column; overflow: hidden; position: relative;">
        <!-- Topo: Imagem -->
        <div class="perspectiva-img-box"
            style="width: 100%; aspect-ratio: 343/68; position: relative; overflow: hidden; background: #eee;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-04.jpg') ?>"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>

        <!-- Base: Textos -->
        <div class="perspectiva-content-box"
            style="flex: 1; padding: 8vh 10vw; background: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <h2 class="perspectiva-titulo"
                style="font-family: var(--wedding-montserrat); font-size: 3.5rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase; margin-bottom: 6vh; text-align: center; line-height: 1.1;">
                MAIS QUE UM ESTÚDIO,<br>UMA PERSPECTIVA
            </h2>

            <div class="perspectiva-grid" style="display: flex; gap: 20px; width: 100%; max-width: 1100px;">
                <div class="perspectiva-box"
                    style="flex: 1; background: #d9d9d9; padding: 40px; box-sizing: border-box;">
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
                <div class="perspectiva-box"
                    style="flex: 1; background: #d9d9d9; padding: 40px; box-sizing: border-box;">
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
    <section class="slide experiencias-slide"
        style="padding: 0; background: #fff; display: flex; flex-direction: row; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Lado Esquerdo: Textos -->
        <div class="experiencias-text-col"
            style="flex: 1; padding: 0 8vw; display: flex; flex-direction: column; justify-content: center; position: relative; height: 100%;">
            <!-- Decorativo Superior Esquerdo -->
            <div class="experiencias-decor"
                style="position: absolute; top: 0; left: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>

            <h2 class="experiencias-titulo"
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
            <div class="experiencias-decor"
                style="position: absolute; bottom: 10vh; left: 8vw; width: 180px; height: 60px; background: #dcdcdc;">
            </div>
        </div>

        <!-- Lado Direito: Imagem -->
        <div class="experiencias-img-col"
            style="flex: 1; position: relative; display: flex; align-items: center; justify-content: center; height: 100%;">
            <!-- Fundo Cinza Decorativo na Direita -->
            <div class="experiencias-decor"
                style="position: absolute; top: 0; right: 0; width: 35%; height: 80%; background: #dcdcdc; z-index: 1;">
            </div>

            <div class="package-img-box" style="width: 80%; height: 80%; position: relative; z-index: 2;">
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
    <section class="slide package-slide"
        style="padding: 0; background: #fff; display: flex; flex-direction: row; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Lado Esquerdo: Imagem -->
        <div class="package-img-col" style="flex: 1; height: 100%;">
            <picture>
                <source media="(max-width: 768px)"
                    srcset="<?= raizUrl('/imagens-proposta-casamento/foto-section-07-mobile.png') ?>">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-07.png') ?>"
                    style="width: 100%; height: 100%; object-fit: cover;">
            </picture>
        </div>

        <!-- Lado Direito: Detalhes -->
        <div class="package-text-col"
            style="flex: 1.2; padding: 0 6vw; display: flex; flex-direction: column; justify-content: center; position: relative; height: 100%; background: #f9f9f9;">
            <!-- Decorativos -->
            <div class="package-decor"
                style="position: absolute; top: 0; right: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div class="package-decor"
                style="position: absolute; bottom: 0; right: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div class="package-decor"
                style="position: absolute; top: 0; left: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div class="package-decor"
                style="position: absolute; bottom: 0; left: 0; width: 140px; height: 80px; background: #dcdcdc;"></div>

            <h2 class="package-titulo"
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
                    foreach ($linhas as $linha):
                        if (empty($linha))
                            continue;
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
                </div>
            </div>

        </div>
    </section>

    <!-- PÁGINA 09: EXPERIÊNCIA CINEMATIC -->
    <section class="slide package-slide"
        style="padding: 0; background: #fff; display: flex; flex-direction: row; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Lado Esquerdo: Detalhes -->
        <div class="package-text-col"
            style="flex: 1; padding: 0 8vw; display: flex; flex-direction: column; justify-content: center; position: relative; height: 100%;">
            <!-- Decorativo Superior Esquerdo -->
            <div class="package-decor"
                style="position: absolute; top: 0; left: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>

            <h2 class="package-titulo"
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
                    foreach ($linhas as $linha):
                        if (empty($linha))
                            continue;
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
            <div class="package-decor"
                style="position: absolute; bottom: 0; left: 8vw; width: 180px; height: 60px; background: #dcdcdc;">
            </div>
        </div>

        <!-- Lado Direito: Imagem -->
        <div class="package-img-col"
            style="flex: 1.2; position: relative; display: flex; align-items: center; justify-content: center; background: #f0f0f0;">
            <!-- Decorativo Superior Direito -->
            <div class="package-decor"
                style="position: absolute; top: 0; right: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div class="package-decor"
                style="position: absolute; bottom: 0; left: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>

            <div class="package-img-box" style="width: 80%; height: 80%; position: relative; z-index: 2;">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-08.png') ?>"
                    style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </div>
    </section>

    <!-- PÁGINA 10: REGISTRO ESSENCIAL -->
    <section class="slide package-slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: row; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Lado Esquerdo: Imagem -->
        <div class="package-img-col"
            style="flex: 1; height: 100%; display: flex; align-items: center; justify-content: center;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-09.png') ?>"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>

        <!-- Lado Direito: Detalhes -->
        <div class="package-text-col"
            style="flex: 1.2; padding: 0 6vw; display: flex; flex-direction: column; justify-content: center; position: relative; height: 100%;">
            <!-- Decorativos -->
            <div class="package-decor"
                style="position: absolute; top: 0; right: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div class="package-decor"
                style="position: absolute; bottom: 0; right: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div class="package-decor"
                style="position: absolute; top: 0; left: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div class="package-decor"
                style="position: absolute; bottom: 0; left: 0; width: 140px; height: 80px; background: #dcdcdc;"></div>

            <h2 class="package-titulo"
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
                    foreach ($linhas as $linha):
                        if (empty($linha))
                            continue;
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

    <!-- PÁGINA 11: ESCOLHA SEU PACOTE — INTERATIVO -->
    <?php
    $pHeritage = is_numeric($dados['valor_heritage'] ?? '') ? (float) $dados['valor_heritage'] : 7900;
    $pCinematic = is_numeric($dados['valor_cinematic'] ?? '') ? (float) $dados['valor_cinematic'] : 4500;
    $pEssencial = is_numeric($dados['valor_essencial'] ?? '') ? (float) $dados['valor_essencial'] : 2800;
    $pBoudoir = is_numeric($dados['valor_boudoir'] ?? '') ? (float) $dados['valor_boudoir'] : 800;
    $pPrewedding = is_numeric($dados['valor_prewedding'] ?? '') ? (float) $dados['valor_prewedding'] : 1200;
    $condHC = $dados['condicoes_heritage_cinematic'] ?? 'Entrada de 20% + saldo parcelado em até 6x';
    $condE = $dados['condicoes_essencial'] ?? 'Entrada de 25% + saldo parcelado em até 5x';
    $clausula = $dados['condicoes_reserva'] ?? 'A reserva da data é oficializada mediante a assinatura do contrato e o pagamento do sinal (entrada). Oferecemos flexibilidade para que o saldo seja quitado de forma equilibrada até a data do evento.';
    $hItem1 = trim(explode("\n", trim($itensHeritage))[0] ?? 'Cobertura Documental Completa');
    $cItem1 = trim(explode("\n", trim($itensCinematic))[0] ?? 'Cobertura Cinematográfica 8h');
    $eItem1 = trim(explode("\n", trim($itensEssencial))[0] ?? 'Cobertura Fotográfica 6h');
    ?>
    <div id="slide-pacote"
        style="display: none; position: fixed; inset: 0; z-index: 10000; background: #1a1a1a; overflow: hidden; flex-direction: row; font-family: var(--wedding-montserrat); animation: modalFadeIn 0.3s ease;">

        <!-- Botão Fechar -->
        <button onclick="window.closeInteractiveModal()"
            style="position: absolute; top: 30px; right: 30px; z-index: 10001; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #fff; transition: all 0.3s;">
            <i data-lucide="x"></i>
        </button>

        <style>
            #slide-pacote .plan-card {
                transition: border-color 0.2s, background 0.2s;
            }

            #slide-pacote .plan-card:hover {
                background: rgba(255, 255, 255, 0.05) !important;
            }

            #slide-pacote .toggle-track {
                width: 36px;
                height: 20px;
                border-radius: 20px;
                background: rgba(255, 255, 255, 0.12);
                cursor: pointer;
                flex-shrink: 0;
                position: relative;
                transition: background 0.2s;
            }

            #slide-pacote .toggle-track.on {
                background: var(--wedding-gold);
            }

            #slide-pacote .toggle-thumb {
                width: 14px;
                height: 14px;
                border-radius: 50%;
                background: #fff;
                position: absolute;
                top: 3px;
                left: 3px;
                transition: left 0.2s;
            }

            #slide-pacote .toggle-track.on .toggle-thumb {
                left: 19px;
            }

            #slide-pacote .linha-upgrade { transition: opacity 0.2s; }

            /* Mobile UI Overhaul */
            @media (max-width: 768px) {
                .wedding-proposal-modal {
                    flex-direction: column !important;
                    background: rgba(26, 26, 26, 0.98) !important;
                    backdrop-filter: blur(15px) !important;
                    overflow-y: auto !important;
                    display: none !important; /* Começar escondido */
                }
                .modal-selection-col, .modal-summary-col {
                    flex: none !important;
                    width: 100% !important;
                    height: auto !important;
                    padding: 30px 20px !important;
                    background: transparent !important;
                }
                .modal-selection-col {
                    padding-top: 80px !important;
                    border-right: none !important;
                    border-bottom: 1px solid rgba(255,255,255,0.05) !important;
                }
                .modal-summary-col {
                    padding-bottom: 120px !important; /* Espaço para o rodapé fixo */
                }
                #slide-pacote .plan-card {
                    padding: 18px 20px !important;
                    margin-bottom: 5px !important;
                }
                #slide-pacote .plan-card p {
                    font-size: 0.75rem !important;
                }
                #total-display {
                    font-size: 1.6rem !important;
                }
                #whatsapp-btn {
                    display: none !important;
                }
                /* Rodapé fixo para mobile */
                .modal-mobile-footer {
                    display: flex !important;
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    width: 100%;
                    background: #1a1a1a;
                    padding: 15px 20px;
                    border-top: 1px solid rgba(255,255,255,0.1);
                    z-index: 10005;
                    box-shadow: 0 -10px 30px rgba(0,0,0,0.5);
                    flex-direction: column;
                    gap: 10px;
                }
                .modal-mobile-total-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: baseline;
                }
            }
            @media (min-width: 769px) {
                .modal-mobile-footer { display: none !important; }
            }
        </style>

        <!-- Coluna Esquerda: Seleção de Plano + Upgrades -->
        <div class="modal-selection-col"
            style="flex: 1.4; padding: 5vh 5vw; display: flex; flex-direction: column; justify-content: center; gap: 0; border-right: 1px solid rgba(255,255,255,0.06); overflow-y: auto;">

            <p
                style="font-size: 0.6rem; font-weight: 700; letter-spacing: 0.28em; text-transform: uppercase; color: var(--wedding-gold); margin: 0 0 16px;">
                ESCOLHA SEU PACOTE</p>

            <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 25px;">
                <?php foreach ($planosWedding as $p): ?>
                    <div id="plan-<?= $p['id'] ?>" class="plan-card" onclick="selectPlan('<?= $p['id'] ?>')"
                        style="display: flex; align-items: center; gap: 15px; padding: 20px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.07); background: rgba(255,255,255,0.02); cursor: pointer; position: relative;">
                        <div class="plan-radio"
                            style="width: 18px; height: 18px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.25); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <div class="plan-radio-dot"
                                style="width: 10px; height: 10px; border-radius: 50%; background: var(--wedding-gold); opacity: 0; transition: opacity 0.2s;">
                            </div>
                        </div>
                        <div style="flex: 1;">
                            <p
                                style="font-size: 0.75rem; font-weight: 500; color: rgba(255,255,255,0.85); margin: 0 0 2px; text-transform: uppercase; letter-spacing: 0.05em;">
                                <?= htmlspecialchars($p['nome']) ?>
                            </p>
                            <p style="font-size: 0.7rem; font-weight: 300; color: rgba(255,255,255,0.4); margin: 0;">
                                <?= htmlspecialchars($p['descricao'] ?: 'Veja os itens inclusos abaixo') ?>
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <p style="font-size: 0.85rem; font-weight: 400; color: #fff; margin: 0;">
                                R$ <?= number_format($p['preco_venda'], 0, ',', '.') ?>
                            </p>
                        </div>
                        <span class="badge-selecionado"
                            style="display: none; position: absolute; top: 10px; right: 10px; font-size: 0.58rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--wedding-gold); border: 1px solid var(--wedding-gold); padding: 2px 8px; border-radius: 20px; white-space: nowrap; flex-shrink: 0;">Selecionado</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Divisor -->
            <div style="border-top: 1px solid rgba(255,255,255,0.07); margin-bottom: 20px;"></div>

            <!-- Serviços Dinâmicos -->
            <p
                style="font-size: 0.6rem; font-weight: 700; letter-spacing: 0.28em; text-transform: uppercase; color: rgba(255,255,255,0.35); margin: 0 0 14px;">
                ITENS DO PLANO E ADICIONAIS</p>
            
            <div id="servicos-dinamicos-container" style="display: flex; flex-direction: column; gap: 0;">
                <p style="font-size: 0.75rem; color: rgba(255,255,255,0.3); font-style: italic; text-align: center; padding: 20px;">Selecione um plano acima para configurar os itens.</p>
            </div>
        </div>

        <!-- Coluna Direita: Resumo + Condições + Cláusula -->
        <div class="modal-summary-col"
            style="flex: 1; padding: 5vh 4vw; display: flex; flex-direction: column; justify-content: center; gap: 0; overflow-y: auto;">

            <!-- Resumo -->
            <p
                style="font-size: 0.6rem; font-weight: 700; letter-spacing: 0.28em; text-transform: uppercase; color: rgba(255,255,255,0.35); margin: 0 0 14px;">
                RESUMO</p>

            <div id="resumo-linhas" style="display: flex; flex-direction: column; gap: 0; margin-bottom: 6px;">
                <div id="linha-plano" class="linha-upgrade"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.06); opacity: 0.35;">
                    <span id="linha-plano-nome" style="font-size: 0.78rem; color: rgba(255,255,255,0.7);">Nenhum plano
                        selecionado</span>
                    <span id="linha-plano-valor" style="font-size: 0.78rem; color: rgba(255,255,255,0.7);">—</span>
                </div>
                <div id="linha-boudoir" class="linha-upgrade"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.06); opacity: 0.35;">
                    <span style="font-size: 0.78rem; color: rgba(255,255,255,0.7);">Upgrade Boudoir</span>
                    <span id="linha-boudoir-valor"
                        style="font-size: 0.78rem; color: rgba(255,255,255,0.4); text-decoration: line-through;">R$ <?= number_format($pBoudoir, 0, ',', '.') ?></span>
                </div>
                <div id="linha-prewedding" class="linha-upgrade"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.06); opacity: 0.35;">
                    <span style="font-size: 0.78rem; color: rgba(255,255,255,0.7);">Upgrade Pré-Wedding</span>
                    <span id="linha-prewedding-valor"
                        style="font-size: 0.78rem; color: rgba(255,255,255,0.4); text-decoration: line-through;">R$ <?= number_format($pPrewedding, 0, ',', '.') ?></span>
                </div>
            </div>

            <div
                style="display: flex; justify-content: space-between; align-items: baseline; padding: 14px 0; margin-bottom: 22px;">
                <span
                    style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.5);">Total
                    do pacote</span>
                <span id="total-display"
                    style="font-size: 1.8rem; font-weight: 300; letter-spacing: -0.02em; color: #fff;">—</span>
            </div>

            <!-- Divisor -->
            <div style="border-top: 1px solid rgba(255,255,255,0.07); margin-bottom: 18px;"></div>

            <p
                style="font-size: 0.6rem; font-weight: 700; letter-spacing: 0.28em; text-transform: uppercase; color: var(--wedding-gold); margin: 0 0 8px;">
                CONDIÇÕES DE PAGAMENTO</p>
            <p id="condicoes-display"
                style="font-size: 0.82rem; font-weight: 300; line-height: 1.65; color: rgba(255,255,255,0.45); margin: 0 0 20px;">
                Selecione um plano para ver as condições.</p>

            <!-- Botão WhatsApp -->
            <button id="whatsapp-btn" onclick="sendWhatsApp()"
                style="width: 100%; padding: 18px; background: #25d366; color: #fff; border: none; border-radius: 6px; font-family: var(--wedding-montserrat); font-weight: 700; font-size: 0.85rem; letter-spacing: 0.05em; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 25px; transition: all 0.3s; opacity: 0.3; pointer-events: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                    </path>
                </svg>
                Confirmar e enviar WhatsApp
            </button>

            <!-- Divisor -->
            <div style="border-top: 1px solid rgba(255,255,255,0.07); margin-bottom: 18px;"></div>

            <!-- Cláusula de Reserva -->
            <p
                style="font-size: 0.6rem; font-weight: 700; letter-spacing: 0.28em; text-transform: uppercase; color: rgba(255,255,255,0.35); margin: 0 0 10px;">
                CLÁUSULA DE RESERVA</p>
            <div style="border-left: 2px solid rgba(197,168,128,0.4); padding-left: 14px;">
                <p
                    style="font-size: 0.78rem; font-weight: 300; line-height: 1.7; color: rgba(255,255,255,0.45); margin: 0;">
                    <?= htmlspecialchars($clausula) ?>
                </p>
            </div>
            </div>
        </div>

        <!-- Rodapé Mobile para Conversão -->
        <div class="modal-mobile-footer">
            <div class="modal-mobile-total-row">
                <span style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: rgba(255,255,255,0.5);">Total do pacote</span>
                <span id="total-display-mobile" style="font-size: 1.4rem; font-weight: 300; color: #fff;">—</span>
            </div>
            <button onclick="sendWhatsApp()" style="width: 100%; padding: 15px; background: #25d366; color: #fff; border: none; border-radius: 6px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase; display: flex; align-items: center; justify-content: center; gap: 8px;">
                Confirmar via WhatsApp
            </button>
        </div>

        <!-- Rodapé Mobile para Conversão -->
        <div class="modal-mobile-footer">
            <div class="modal-mobile-total-row">
                <span style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: rgba(255,255,255,0.5);">Total do pacote</span>
                <span id="total-display-mobile" style="font-size: 1.4rem; font-weight: 300; color: #fff;">—</span>
            </div>
            <button onclick="sendWhatsApp()" style="width: 100%; padding: 15px; background: #25d366; color: #fff; border: none; border-radius: 6px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase; display: flex; align-items: center; justify-content: center; gap: 8px;">
                Confirmar via WhatsApp
            </button>
        </div>
        <script>
            (function () {
                // Catálogo de Serviços vindo do Banco
                const allServices = <?= json_encode($servicosWedding) ?>;

                // Definição dos Planos vindo do Banco
                const planPresets = {};
                <?php foreach ($planosWedding as $p): ?>
                planPresets['<?= $p['id'] ?>'] = {
                    nome: '<?= addslashes($p['nome']) ?>',
                    valorBase: <?= (float)$p['preco_venda'] ?>,
                    condicoes: '<?= addslashes($p['prazo_minimo'] > 0 ? "Saldo parcelado em até {$p['prazo_minimo']}x" : "Condições sob consulta") ?>',
                    servicos: <?= $p['itens_json'] ?: '{}' ?>
                };
                <?php endforeach; ?>

                let selectedPlan = null;
                let activeUpgrades = {};

                const fmt = (v) => 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

                window.selectPlan = function (id) {
                    selectedPlan = id;
                    activeUpgrades = {}; 
                    
                    document.querySelectorAll('.plan-card').forEach(c => {
                        c.style.borderColor = 'rgba(255,255,255,0.1)';
                        c.style.background = 'rgba(255,255,255,0.03)';
                        c.querySelector('.badge-selecionado').style.display = 'none';
                        c.querySelector('.plan-radio').style.borderColor = 'rgba(255,255,255,0.25)';
                        c.querySelector('.plan-radio-dot').style.opacity = '0';
                    });
                    const card = document.getElementById('plan-' + id);
                    if (card) {
                        card.style.borderColor = 'var(--wedding-gold)';
                        card.style.background = 'rgba(197,168,128,0.07)';
                        card.querySelector('.badge-selecionado').style.display = 'block';
                        card.querySelector('.plan-radio').style.borderColor = 'var(--wedding-gold)';
                        card.querySelector('.plan-radio-dot').style.opacity = '1';
                    }

                    renderServicesList();
                    atualizarResumo();
                };

                function renderServicesList() {
                    const container = document.getElementById('servicos-dinamicos-container');
                    const plan = planPresets[selectedPlan];
                    if (!plan) return;
                    container.innerHTML = '';

                    Object.entries(plan.servicos).forEach(([sId, status]) => {
                        const s = allServices[sId];
                        if (!s) return;
                        const isOptional = status === 'opcional';
                        const div = document.createElement('div');
                        div.className = 'service-item-row';
                        div.style = `display: flex; align-items: center; gap: 14px; padding: 12px 16px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.07); background: rgba(255,255,255,0.02); margin-bottom: 10px; transition: all 0.3s; ${!isOptional ? 'opacity: 0.8;' : ''}`;
                        
                        div.innerHTML = `
                            <div style="flex: 1;">
                                <p style="font-size: 0.75rem; font-weight: 500; color: rgba(255,255,255,0.82); margin: 0 0 1px; text-transform: uppercase; letter-spacing: 0.04em;">${s.nome}</p>
                                <p style="font-size: 0.72rem; font-weight: 300; color: rgba(255,255,255,0.45); margin: 0;">${isOptional ? fmt(s.valor) : 'Já incluso no pacote'}</p>
                            </div>
                            ${isOptional ? `
                                <span style="font-size: 0.55rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--wedding-gold); border: 1px solid var(--wedding-gold); padding: 2px 8px; border-radius: 20px; opacity: 0.8;">Opcional</span>
                                <div class="toggle-track ${activeUpgrades[sId] ? 'on' : ''}" onclick="toggleDynamicService('${sId}')">
                                    <div class="toggle-thumb"></div>
                                </div>
                            ` : `
                                <span style="font-size: 0.55rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #10b981; border: 1px solid #10b981; padding: 2px 8px; border-radius: 20px;">✓ Incluso</span>
                            `}
                        `;
                        container.appendChild(div);
                    });
                }

                window.toggleDynamicService = function(sId) {
                    activeUpgrades[sId] = !activeUpgrades[sId];
                    renderServicesList(); 
                    atualizarResumo();
                };

                function atualizarResumo() {
                    if (!selectedPlan) return;
                    const plan = planPresets[selectedPlan];
                    const resumoCont = document.getElementById('resumo-linhas');
                    resumoCont.innerHTML = '';

                    let total = plan.valorBase;

                    const divBase = document.createElement('div');
                    divBase.className = 'linha-upgrade';
                    divBase.style = "display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.06);";
                    divBase.innerHTML = `<span style="font-size: 0.78rem; color: rgba(255,255,255,0.7);">${plan.nome}</span><span style="font-size: 0.78rem; color: rgba(255,255,255,0.7);">${fmt(plan.valorBase)}</span>`;
                    resumoCont.appendChild(divBase);

                    Object.entries(activeUpgrades).forEach(([sId, active]) => {
                        if (active) {
                            const s = allServices[sId];
                            total += s.valor;
                            const div = document.createElement('div');
                            div.className = 'linha-upgrade';
                            div.style = "display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.06);";
                            div.innerHTML = `<span style="font-size: 0.78rem; color: rgba(255,255,255,0.7);">+ ${s.nome}</span><span style="font-size: 0.78rem; color: rgba(255,255,255,0.7);">${fmt(s.valor)}</span>`;
                            resumoCont.appendChild(div);
                        }
                    });

                    const totalText = fmt(total);
                    document.getElementById('total-display').textContent = totalText;
                    document.getElementById('total-display-mobile').textContent = totalText;

                    document.getElementById('condicoes-display').textContent = plan.condicoes;
                    document.getElementById('condicoes-display').style.color = 'rgba(255,255,255,0.7)';

                    const btnWA = document.getElementById('whatsapp-btn');
                    btnWA.style.opacity = '1';
                    btnWA.style.pointerEvents = 'auto';
                }

                window.sendWhatsApp = function () {
                    if (!selectedPlan) return;

                    const plan = planPresets[selectedPlan];
                    let total = plan.valorBase;
                    let msg = `Olá! Gostaria de confirmar meu interesse na proposta de casamento:\n\n`;
                    msg += `*PLANO BASE:* ${plan.nome} (${fmt(plan.valorBase)})\n`;

                    Object.entries(activeUpgrades).forEach(([sId, active]) => {
                        if (active) {
                            const s = allServices[sId];
                            msg += `*+ ${s.nome}* (${fmt(s.valor)})\n`;
                            total += s.valor;
                        }
                    });

                    msg += `\n*INVESTIMENTO TOTAL:* ${fmt(total)}\n`;
                    msg += `\nAguardo o retorno para os próximos passos!`;

                    const encodedMsg = encodeURIComponent(msg);
                    const url = `https://wa.me/5527988586935?text=${encodedMsg}`;
                    window.open(url, '_blank');
                };
            })();
        </script>
    </div>

    <!-- PÁGINA 12: WEDDING PORTFOLIO CAPA -->
    <section id="wedding-portfolio" class="slide portfolio-capa-slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: column; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Topo: Textos -->
        <div class="portfolio-capa-content" style="height: 50%; display: flex; flex-direction: row; width: 100%;">
            <!-- Lado Esquerdo: Manifesto -->
            <div class="portfolio-capa-text-box"
                style="flex: 2; padding: 6vh 6vw; display: flex; flex-direction: column; justify-content: center;">
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
                        que está nas entrelinhas.
                    </p>
                </div>
            </div>

            <!-- Lado Direito: Título -->
            <div class="portfolio-capa-title-box"
                style="flex: 1.5; padding: 6vh 4vw; display: flex; flex-direction: column; justify-content: center; position: relative;">
                <!-- Decorativo Superior Direito -->
                <div class="package-decor"
                    style="position: absolute; top: 0; right: 0; width: 60px; height: 80px; background: #dcdcdc;">
                </div>

                <h2 class="portfolio-capa-titulo"
                    style="font-family: var(--wedding-montserrat); font-size: 4.5rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase; line-height: 1; margin-bottom: 5px;">
                    WEDDING<br>PORTFOLIO</h2>
                <p class="portfolio-capa-subtitulo"
                    style="font-family: var(--wedding-montserrat); font-size: 1.4rem; font-weight: 400; color: #444; letter-spacing: 0.2em; text-transform: uppercase;">
                    VERSÕES DA HISTÓRIA</p>
            </div>
        </div>

        <!-- Base: Grid de Fotos -->
        <div class="portfolio-capa-img-box" style="height: 50%; width: 100%;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-11.png') ?>"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>
    </section>

    <!-- PÁGINA 13: PORTFÓLIO PEDRO E VANESSA -->
    <section class="slide portfolio-slide"
        style="padding: 0; background: #000; display: flex; flex-direction: row; gap: 2px; overflow: hidden; height: 100vh; width: 100%;">

        <!-- Coluna Esquerda (Duas fotos empilhadas) -->
        <div class="portfolio-left-col" style="flex: 2; display: flex; flex-direction: column; gap: 2px; height: 100%;">
            <!-- Foto Cima -->
            <div class="reveal-item portfolio-img-item" style="flex: 1; position: relative; overflow: hidden;">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-cima-12.png') ?>"
                    style="width: 100%; height: 100%; object-fit: cover;">
                <div class="portfolio-label" style="position: absolute; top: 40px; left: 40px; z-index: 10;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; color: #fff; letter-spacing: 0.15em; text-transform: uppercase; font-weight: 300; opacity: 0.8;">
                        PEDRO E VANESSA - BEFORE THE BLOOM
                    </p>
                </div>
            </div>
            <!-- Foto Baixo -->
            <div class="reveal-item portfolio-img-item" style="flex: 1; overflow: hidden;">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-baixo-12.png') ?>"
                    style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </div>

        <!-- Coluna Direita (Foto inteira) -->
        <div class="reveal-item portfolio-right-col portfolio-img-item portfolio-img-v"
            style="flex: 1.1; overflow: hidden; height: 100%;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-direita-12.png') ?>"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>
    </section>

    <!-- PÁGINA 14: PORTFÓLIO GABRIEL E JULIA -->
    <section class="slide portfolio-slide"
        style="padding: 0; background: #000; display: flex; flex-direction: row; gap: 2px; overflow: hidden; height: 100vh; width: 100%;">

        <!-- Coluna Esquerda (Duas fotos empilhadas) -->
        <div class="portfolio-left-col"
            style="flex: 1.8; display: flex; flex-direction: column; gap: 2px; height: 100%;">
            <!-- Foto Cima -->
            <div class="reveal-item portfolio-img-item" style="flex: 1; overflow: hidden;">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-cima-13.png') ?>"
                    style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <!-- Foto Baixo -->
            <div class="reveal-item portfolio-img-item" style="flex: 1.1; position: relative; overflow: hidden;">
                <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-baixo-13.png') ?>"
                    style="width: 100%; height: 100%; object-fit: cover;">
                <div class="portfolio-label" style="position: absolute; bottom: 40px; left: 40px; z-index: 10;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; color: #fff; letter-spacing: 0.15em; text-transform: uppercase; font-weight: 300; opacity: 0.8;">
                        GABRIEL E JULIA - PRÉ-WEDDING
                    </p>
                </div>
            </div>
        </div>

        <!-- Coluna Direita (Foto inteira) -->
        <div class="reveal-item portfolio-right-col portfolio-img-item"
            style="flex: 1; overflow: hidden; height: 100%;">
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
    <section class="slide team-slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Cabeçalho -->
        <div class="reveal-item team-header" style="text-align: center; margin-bottom: 60px; z-index: 10;">
            <h2 class="team-title"
                style="font-family: var(--wedding-montserrat); font-size: 4rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase; margin-bottom: 10px;">
                OS OLHARES POR TRÁS DAS LENTES
            </h2>
            <p style="font-family: var(--wedding-montserrat); font-size: 1.2rem; color: #888; font-weight: 400;">
                Não somos apenas técnicos. Somos contadores de histórias.
            </p>
        </div>

        <!-- Barra Decorativa Cinza -->
        <div class="team-decor-bar"
            style="position: absolute; top: 50%; left: 0; width: 100%; height: 120px; background: #dcdcdc; transform: translateY(-50%); z-index: 1;">
        </div>

        <!-- Grid da Equipe -->
        <div class="team-grid"
            style="display: flex; flex-direction: row; gap: 40px; z-index: 10; width: 90%; max-width: 1400px; justify-content: center;">

            <!-- Jeane -->
            <div class="reveal-item team-item" style="flex: 1; text-align: center;">
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
            <div class="reveal-item team-item" style="flex: 1; text-align: center;">
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
            <div class="reveal-item team-item" style="flex: 1; text-align: center;">
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
            <div class="reveal-item team-item" style="flex: 1; text-align: center;">
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
    <section class="slide depo-slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <div class="depo-container"
            style="display: flex; flex-direction: row; width: 90%; max-width: 1200px; gap: 80px; z-index: 10;">
            <!-- Lado Esquerdo: Depoimentos -->
            <div class="depo-col-left" style="flex: 1.5;">
                <h2 class="depo-title"
                    style="font-family: var(--wedding-montserrat); font-size: 3rem; font-weight: 300; letter-spacing: 0.1em; text-transform: uppercase; color: #1a1a1a; line-height: 1.1; margin-bottom: 50px;">
                    O QUE DIZEM<br><span style="color: var(--wedding-gold);">NOSSOS CASAIS</span>
                </h2>

                <div class="reveal-item"
                    style="margin-bottom: 40px; border-left: 2px solid var(--wedding-gold); padding-left: 30px;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; font-weight: 300; line-height: 1.7; font-style: italic; margin-bottom: 15px; color: #444;">
                        "<?= $depoimento01Texto ?>"
                    </p>
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em; color: var(--wedding-gold);">
                        — <?= $depoimento01Autor ?>
                    </p>
                </div>

                <div class="reveal-item" style="border-left: 2px solid var(--wedding-gold); padding-left: 30px;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; font-weight: 300; line-height: 1.7; font-style: italic; margin-bottom: 15px; color: #444;">
                        "<?= $depoimento02Texto ?>"
                    </p>
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em; color: var(--wedding-gold);">
                        — <?= $depoimento02Autor ?>
                    </p>
                </div>
            </div>

            <!-- Lado Direito: Compromisso -->
            <div class="depo-col-right"
                style="flex: 1; background: #fff; padding: 50px; border-radius: 4px; display: flex; flex-direction: column; justify-content: center;">
                <h3
                    style="font-family: var(--wedding-montserrat); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.25em; text-transform: uppercase; color: var(--wedding-gold); margin-bottom: 40px; border-bottom: 1px solid #e5e5e5; padding-bottom: 20px;">
                    NOSSO COMPROMISSO
                </h3>

                <div class="reveal-item" style="margin-bottom: 30px;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 0.7rem; font-weight: 400; text-transform: uppercase; letter-spacing: 0.15em; color: #888; margin-bottom: 8px;">
                        Prévias do Casamento</p>
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 2rem; font-weight: 300; letter-spacing: 0.05em; color: #1a1a1a;">
                        <?= $prazoPrevias ?>
                    </p>
                </div>

                <div class="reveal-item">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 0.7rem; font-weight: 400; text-transform: uppercase; letter-spacing: 0.15em; color: #888; margin-bottom: 8px;">
                        Material Final</p>
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 2rem; font-weight: 300; letter-spacing: 0.05em; color: #1a1a1a;">
                        <?= $prazoFinal ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Fundo Decorativo -->
        <div
            style="position: absolute; top: 0; right: 0; width: 30%; height: 100%; background: linear-gradient(to right, transparent, rgba(197, 168, 128, 0.06));">
        </div>
    </section>

    <!-- PÁGINA 19: VAMOS DAR O PRÓXIMO PASSO? -->
    <section class="slide contato-slide"
        style="padding: 0; background: #fff; display: flex; flex-direction: row; overflow: hidden; height: 100vh; width: 100%;">

        <!-- Lado Esquerdo: Conteúdo -->
        <div class="contato-col-text"
            style="flex: 1; padding: 8vh 6vw; display: flex; flex-direction: column; justify-content: center; background: #f4f4f4; position: relative;">
            <!-- Decorativos -->
            <div class="package-decor"
                style="position: absolute; top: 0; left: 0; width: 80px; height: 40px; background: #dcdcdc;"></div>
            <div class="package-decor"
                style="position: absolute; bottom: 0; left: 0; width: 40px; height: 80px; background: #dcdcdc;"></div>

            <h2 class="reveal-item contato-title"
                style="font-family: var(--wedding-montserrat); font-size: 4rem; font-weight: 300; letter-spacing: 0.05em; color: #1a1a1a; text-transform: uppercase; line-height: 1.1; margin-bottom: 40px;">
                VAMOS DAR O<br>PRÓXIMO PASSO?
            </h2>

            <!-- Contatos -->
            <div class="reveal-item"
                style="margin-bottom: 40px; font-family: var(--wedding-montserrat); font-size: 1.2rem; line-height: 2; color: #1a1a1a;">
                <?php
                $wa_clean = str_replace([' ', '-', '+'], '', $whatsappNumero);
                $wa_link = "https://wa.me/{$wa_clean}?text=" . urlencode($mensagemWA);
                ?>
                <a href="<?= $wa_link ?>" target="_blank"
                    style="display: block; text-decoration: none; color: #1a1a1a; transition: all 0.3s;">
                    <span style="border-bottom: 1px solid #ccc;"><?= $whatsappNumero ?></span>
                </a>
                <a href="mailto:<?= $emailContato ?>"
                    style="display: block; text-decoration: none; color: #1a1a1a; transition: all 0.3s;">
                    <?= $emailContato ?>
                </a>
                <a href="https://instagram.com/<?= str_replace('@', '', $instagramHandle) ?>" target="_blank"
                    style="display: block; text-decoration: none; color: #1a1a1a; transition: all 0.3s;">
                    <?= $instagramHandle ?>
                </a>
            </div>

            <!-- Texto de Apoio -->
            <div class="reveal-item"
                style="font-family: var(--wedding-montserrat); font-size: 0.95rem; line-height: 1.8; color: #555; font-style: italic; max-width: 480px;">
                <p style="margin-bottom: 20px;">
                    Se algo aqui ainda não fez o coração de vocês vibrar, vamos trocar uma ideia. Estamos prontos para
                    encontrar uma solução para o seu caso em particular, moldando cada detalhe para que esta experiência
                    seja totalmente nova e única para vocês.
                </p>
                <p style="margin-bottom: 20px;">
                    Este é o primeiro capítulo da história oficial de <strong><?= $nomeCasal ?></strong>, e nossa meta é
                    uma só: fazer todas as variáveis desse dia ganharem o mais bonito sentido, garantindo que o arrepio
                    do 'sim' dure para sempre através do nosso olhar.
                </p>
                <p
                    style="font-size: 0.8rem; color: var(--wedding-gold); font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 10px;">
                    * Proposta válida por <?= $validadeProposta ?> dias.
                </p>
            </div>

            <!-- Footer -->
            <div
                style="margin-top: 60px; opacity: 0.3; font-family: var(--wedding-montserrat); font-size: 0.7rem; letter-spacing: 0.3em; text-transform: uppercase;">
                Distinto Wedding © <?= date('Y') ?>
            </div>
        </div>

        <!-- Lado Direito: Imagem -->
        <div class="reveal-item contato-col-img" style="flex: 1.2; height: 100%; position: relative;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-18.png') ?>"
                style="width: 100%; height: 100%; object-fit: cover;">
            <!-- Elemento decorativo topo direito -->
            <div class="package-decor"
                style="position: absolute; top: 0; right: 0; width: 60px; height: 100px; background: #dcdcdc; opacity: 0.8;">
            </div>
        </div>
    </section>

    <!-- PÁGINA 19: THANK YOU -->
    <section class="slide thanks-slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: column; overflow: hidden; height: 100vh; width: 100%;">

        <!-- Topo: Mensagem de Agradecimento -->
        <div class="thanks-header"
            style="height: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 0 4vw;">
            <h2 class="reveal-item thanks-title"
                style="font-family: var(--wedding-montserrat); font-size: 6rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase; margin-bottom: 20px;">
                THANK YOU
            </h2>
            <p class="reveal-item thanks-subtitle"
                style="font-family: var(--wedding-montserrat); font-size: 1.4rem; color: #333; font-weight: 400; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 10px;">
                REGISTRANDO HISTÓRIAS DE AMOR COM AUTENTICIDADE E EMOÇÃO
            </p>
            <p class="reveal-item"
                style="font-family: var(--wedding-montserrat); font-size: 0.9rem; color: #888; letter-spacing: 0.1em;">
                by Distinto
            </p>
        </div>

        <!-- Base: Imagem Panorâmica -->
        <div class="reveal-item" style="height: 50%; width: 100%; overflow: hidden;">
            <img src="<?= raizUrl('/imagens-proposta-casamento/foto-section-19.png') ?>"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>
    </section>

</div>

<script>
    // Inicializar ícones se necessário
    if (window.lucide) lucide.createIcons();
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Scroll automático para o portfólio no container correto
        const wrapper = document.querySelector('.proposal-wrapper');
        const portfolioSection = document.getElementById('wedding-portfolio');
        
        if (wrapper && portfolioSection) {
            // Pequeno delay para garantir que o layout renderizou
            setTimeout(() => {
                portfolioSection.scrollIntoView({ behavior: 'auto', block: 'start' });
            }, 100);
        }

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

        // Funções para o Modal Interativo
        window.openInteractiveModal = function () {
            const modal = document.getElementById('slide-pacote');
            if (modal) {
                modal.style.display = 'flex';
                const container = document.querySelector('.wedding-proposal');
                if (container) container.style.overflow = 'hidden';
                if (window.lucide) lucide.createIcons();
            }
        };

        window.closeInteractiveModal = function () {
            const modal = document.getElementById('slide-pacote');
            if (modal) {
                modal.style.display = 'none';
                const container = document.querySelector('.wedding-proposal');
                if (container) container.style.overflowY = 'scroll';
            }
        };
    });
</script>