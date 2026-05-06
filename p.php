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
    
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/propostas.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="type-<?= $tipo ?>">

    <!-- Moldura Global (HUD) -->
    <div class="proposal-frame">
        <div class="frame-item">
            <div class="frame-top"><?= $categoriaProjeto ?></div>
            <div class="frame-bottom logo-container" id="dynamic-logo">
                <img src="<?= APP_URL ?>/assets/distinto_logo.svg" alt="Distinto" id="logo-svg">
                <span class="logo-text">PONCEM STUDIO | DISTINTO</span>
            </div>
        </div>
        <div class="frame-item">
            <div class="frame-top"><?= $mesNome ?></div>
            <div class="frame-bottom">CLIENTE: <?= $cliente ?></div>
        </div>
        <div class="frame-item">
            <div class="frame-top"><?= $ano ?></div>
            <div class="frame-bottom">PROPOSTA</div>
        </div>
    </div>

    <div class="proposal-wrapper">
        <?php include $templateFile; ?>
    </div>

    <!-- Botão Flutuante de Aprovação -->
    <a href="https://wa.me/<?= preg_replace('/\D/', '', $configEmpresa['telefone'] ?? '') ?>?text=Olá! Gostaria de aprovar a proposta: <?= $proposta['titulo'] ?> (Ref: <?= $slug ?>)" 
       id="btn-approve" class="btn-floating">
        <span>Aprovar Proposta</span>
        <i data-lucide="check-circle"></i>
    </a>

    <script>
        // Inicializar ícones
        lucide.createIcons();
    </script>
    <script src="<?= APP_URL ?>/assets/js/propostas.js"></script>
</body>
</html>
