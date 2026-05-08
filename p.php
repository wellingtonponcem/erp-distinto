<?php
/**
 * Visualizador Público de Propostas
 * wedistinto.com/p/[slug]
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

$slug = $_GET['slug'] ?? '';

if (!$slug) {
    die("Proposta não encontrada.");
}

$db = Database::get();
$stmt = $db->prepare("SELECT * FROM propostas WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$proposta = $stmt->fetch();

if (!$proposta) {
    die("Proposta não encontrada ou expirada.");
}

$dados = json_decode($proposta['dados_json'], true);
$tipo = $proposta['tipo'];
$cliente = $proposta['cliente_nome'];

// Metadados para a Moldura
$dataCriacao = new DateTime($proposta['created_at']);
$mesesPt = [
    '1' => 'JANEIRO', '2' => 'FEVEREIRO', '3' => 'MARÇO',
    '4' => 'ABRIL', '5' => 'MAIO', '6' => 'JUNHO',
    '7' => 'JULHO', '8' => 'AGOSTO', '9' => 'SETEMBRO',
    '10' => 'OUTUBRO', '11' => 'NOVEMBRO', '12' => 'DEZEMBRO'
];
$mesNome = $mesesPt[$dataCriacao->format('n')] ?? 'JUNHO';
$ano = $dataCriacao->format('Y');
$categoriaProjeto = $dados['categoria_projeto'] ?? 'PROJETO DE ESTRATÉGIA';

// Definir arquivo de template
$templateFile = __DIR__ . "/includes/propostas/template-{$tipo}.php";
if (!file_exists($templateFile)) {
    die("Template de proposta não configurado.");
}

// Configurações da Empresa para o Rodapé/Capa
$configEmpresa = $db->query("SELECT * FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $proposta['titulo'] ?> — <?= $cliente ?></title>
    
    <link rel="stylesheet" href="<?= raizUrl('/assets/css/propostas.css') ?>">
    <link rel="stylesheet" href="<?= raizUrl('/assets/css/propostas-mobile.css') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= raizUrl('/favicon.svg') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= raizUrl('/favicon_io/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= raizUrl('/favicon_io/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= raizUrl('/favicon_io/favicon-16x16.png') ?>">
    <link rel="manifest" href="<?= raizUrl('/favicon_io/site.webmanifest') ?>">
    <!-- Bibliotecas Externas -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body class="type-<?= $tipo ?>">

    <!-- Header Mobile (visível apenas em telas ≤ 768px via CSS) -->
    <header class="mobile-header no-print" style="display: none;">
        <span class="mobile-header-logo">DISTINTO</span>
        <span class="mobile-header-title"><?= htmlspecialchars($proposta['titulo']) ?> — <?= htmlspecialchars($cliente) ?></span>
    </header>

    <!-- Moldura Global (HUD) -->
    <div class="proposal-hud-lines"></div>
    <div class="proposal-frame">
        <div class="frame-item">
            <div class="frame-top"><?= $categoriaProjeto ?></div>
            <div class="frame-bottom logo-container" id="dynamic-logo">
                <img src="<?= raizUrl('/assets/distinto_logo.svg') ?>" alt="Distinto" id="logo-svg">
                <span class="logo-text">PONCEM STUDIO | DISTINTO</span>
            </div>
        </div>
        <div class="frame-item">
            <div class="frame-top"><?= $mesNome ?></div>
            <div class="frame-bottom">
                <?php 
                    $textoResponsavel = '';
                    if (!empty($dados['responsavel'])) {
                        // Regex robusto para separar por ' e ', ' E ', vírgula ou ponto e vírgula
                        $partesBrutas = preg_split('/(?:\s+[eE]\s+|[,;]\s*)/', $dados['responsavel']);
                        $nomesFinais = [];
                        foreach ($partesBrutas as $p) {
                            $p = trim($p);
                            if (!$p) continue;
                            // Pega apenas o primeiro nome
                            $palavras = explode(' ', $p);
                            $nomesFinais[] = $palavras[0];
                        }
                        
                        $total = count($nomesFinais);
                        if ($total === 1) {
                            $textoResponsavel = $nomesFinais[0];
                        } elseif ($total === 2) {
                            $textoResponsavel = $nomesFinais[0] . ' e ' . $nomesFinais[1];
                        } elseif ($total > 2) {
                            $ultimo = array_pop($nomesFinais);
                            $textoResponsavel = implode(', ', $nomesFinais) . ' e ' . $ultimo;
                        }
                    }
                    echo mb_strtoupper($cliente) . ($textoResponsavel ? " | " . mb_strtoupper($textoResponsavel) : "");
                ?>
            </div>
        </div>
        <div class="frame-item">
            <div class="frame-top"><?= $ano ?></div>
            <div class="frame-bottom">PROPOSTA</div>
        </div>
    </div>

    <div class="proposal-wrapper">
        <!-- Título Fixo para Seções Agrupadas -->
        <?php if ($tipo !== 'casamento'): ?>
        <div class="fixed-section-title">
            <h2>ETAPAS DO<br>PROJETO</h2>
        </div>
        <?php endif; ?>

        <?php include $templateFile; ?>
    </div>

    <!-- Botão Exportar Topo -->
    <button class="btn-export-top no-print" onclick="window.showExportModal()">
        <i data-lucide="file-down"></i>
        <span>PDF</span>
    </button>

    <a href="https://wa.me/<?= preg_replace('/\D/', '', $configEmpresa['telefone'] ?? '') ?>?text=Olá! Gostaria de aprovar a proposta: <?= $proposta['titulo'] ?> (Ref: <?= $slug ?>)" 
       id="btn-approve" class="btn-floating no-print">
        <span>Aprovar Proposta</span>
        <i data-lucide="check-circle"></i>
    </a>

    <!-- Action Bar Mobile (visível apenas em telas ≤ 768px via CSS) -->
    <div class="mobile-action-bar no-print" style="display: none;">
        <a href="https://wa.me/<?= preg_replace('/\D/', '', $configEmpresa['telefone'] ?? '') ?>?text=<?= rawurlencode('Olá! Gostaria de aprovar a proposta: ' . $proposta['titulo'] . ' (Ref: ' . $slug . ')') ?>"
           class="mobile-btn-approve">
            <i data-lucide="check-circle"></i>
            <span>Aprovar</span>
        </a>
        <button onclick="window.showExportModal()" class="mobile-btn-pdf">
            <i data-lucide="file-down"></i>
            <span>PDF</span>
        </button>
    </div>

    <!-- Modal de Exportação -->
    <div id="export-modal" class="export-modal no-print" style="display: none;">
        <div class="export-modal-content">
            <h3>Exportar Proposta</h3>
            <p>Escolha o formato de visualização para o PDF:</p>
            
            <div class="export-options">
                <button onclick="window.exportPDF('horizontal')" class="export-option">
                    <div class="option-preview horizontal">
                        <div class="mac-screen"></div>
                    </div>
                    <span>Horizontal (Mac M1)</span>
                </button>
                <button onclick="window.exportPDF('vertical')" class="export-option">
                    <div class="option-preview vertical">
                        <div class="phone-screen"></div>
                    </div>
                    <span>Vertical (Mobile)</span>
                </button>
            </div>
            
            <button onclick="window.hideExportModal()" class="btn-cancel-export">Cancelar</button>
        </div>
    </div>

    <script>
        // Inicializar ícones
        lucide.createIcons();

        // Persistência de Scroll
        const proposalSlug = '<?= $proposta['slug'] ?>';
        window.addEventListener('load', () => {
            const savedScroll = localStorage.getItem('scroll_' + proposalSlug);
            if (savedScroll) {
                window.scrollTo({ top: parseInt(savedScroll), behavior: 'instant' });
            }
        });

        let scrollTimeout;
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                localStorage.setItem('scroll_' + proposalSlug, window.scrollY);
            }, 100);
        });
    </script>
    <script src="<?= raizUrl('/assets/js/propostas.js') ?>"></script>
</body>
</html>
