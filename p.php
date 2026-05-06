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
