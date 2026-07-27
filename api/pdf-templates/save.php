<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/pdf_templates.php';

exigirAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Metodo nao permitido'], 405);
}

$d = lerCorpo();
$id = $d['id'] ?? gerarId();
$nome = trim($d['nome'] ?? '');
$tipo = $d['tipo'] ?? '';
$ativo = !empty($d['ativo']) ? 1 : 0;
$config = $d['config'] ?? ['pages' => []];

if (!$nome || !in_array($tipo, ['casamento', '15anos', 'filmmaker', 'marketing'], true)) {
    responderJson(['erro' => 'Nome e tipo sao obrigatorios'], 422);
}

$db = Database::get();
garantirTabelasPdfTemplates($db);

if ($ativo) {
    $stmtOff = $db->prepare("UPDATE pdf_templates SET ativo = 0 WHERE tipo = ? AND id <> ?");
    $stmtOff->execute([$tipo, $id]);
}

$json = json_encode($config, JSON_UNESCAPED_UNICODE);
$stmtExists = $db->prepare("SELECT id FROM pdf_templates WHERE id = ?");
$stmtExists->execute([$id]);

if ($stmtExists->fetch()) {
    $stmt = $db->prepare("UPDATE pdf_templates SET nome = ?, tipo = ?, ativo = ?, config_json = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$nome, $tipo, $ativo, $json, $id]);
} else {
    $stmt = $db->prepare("INSERT INTO pdf_templates (id, nome, tipo, ativo, config_json) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$id, $nome, $tipo, $ativo, $json]);
}

responderJson(['success' => true, 'id' => $id]);
