<?php
/**
 * API para atualizar etapa da oportunidade (Kanban drag-and-drop)
 * POST: { id, etapa }
 */
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido']);
    exit;
}

exigirAutenticacao();

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? '';
$etapa = $data['etapa'] ?? '';

$etapasValidas = ['novo', 'qualificado', 'proposta', 'negociacao', 'ganha', 'perdida'];

if (!$id || !in_array($etapa, $etapasValidas)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Parâmetros inválidos']);
    exit;
}

try {
    $db = Database::get();
    $stmt = $db->prepare("UPDATE oportunidades SET etapa = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$etapa, $id]);

    // Se ganhou ou perdeu, atualizar status da proposta vinculada também
    if ($etapa === 'ganha') {
        $db->prepare("UPDATE propostas SET status = 'aceita' WHERE oportunidade_id = ? AND status != 'aceita'")->execute([$id]);
    } elseif ($etapa === 'perdida') {
        $db->prepare("UPDATE propostas SET status = 'recusada' WHERE oportunidade_id = ? AND status NOT IN ('aceita', 'recusada')")->execute([$id]);
    }

    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
