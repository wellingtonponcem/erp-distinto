<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/pdf_templates.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    responderJson(['erro' => 'Slug obrigatório'], 422);
}

$db = Database::get();
$stmt = $db->prepare("SELECT * FROM propostas WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$proposta = $stmt->fetch();

if (!$proposta) {
    responderJson(['erro' => 'Proposta não encontrada'], 404);
}

$template = templatePdfAtivo($db, $proposta['tipo']);
if (!$template) {
    responderJson(['template' => null, 'values' => []]);
}

responderJson([
    'template' => [
        'id' => $template['id'],
        'nome' => $template['nome'],
        'tipo' => $template['tipo'],
        'config' => $template['config'],
    ],
    'values' => dadosPdfProposta($proposta),
]);
