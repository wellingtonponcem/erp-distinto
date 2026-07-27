<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$d = json_decode(file_get_contents('php://input'), true);
if (!isset($d['acao'])) {
    http_response_code(400);
    exit;
}

$db = Database::get();

try {
    if ($d['acao'] === 'renomear' && !empty($d['de']) && !empty($d['para'])) {
        $de = trim($d['de']);
        $para = trim(strtolower($d['para']));
        
        $stmt = $db->prepare("UPDATE lancamentos SET categoria = ? WHERE categoria = ?");
        $stmt->execute([$para, $de]);
        
        $stmt2 = $db->prepare("UPDATE custos_fixos SET categoria = ? WHERE categoria = ?");
        $stmt2->execute([$para, $de]);
        
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($d['acao'] === 'excluir' && !empty($d['categoria'])) {
        $cat = trim($d['categoria']);
        if ($cat === 'outros') {
            echo json_encode(['ok' => true]); // Ignore
            exit;
        }
        
        $stmt = $db->prepare("UPDATE lancamentos SET categoria = 'outros' WHERE categoria = ?");
        $stmt->execute([$cat]);
        
        $stmt2 = $db->prepare("UPDATE custos_fixos SET categoria = 'outros' WHERE categoria = ?");
        $stmt2->execute([$cat]);
        
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['erro' => 'Ação inválida']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno ao modificar categorias no banco de dados.']);
}
