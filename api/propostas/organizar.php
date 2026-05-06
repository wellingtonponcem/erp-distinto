<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Apenas autenticados
if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$db = Database::get();
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Ação não informada']);
    exit;
}

try {
    switch ($data['action']) {
        case 'move':
            $stmt = $db->prepare("UPDATE propostas SET pasta_id = ? WHERE id = ?");
            $stmt->execute([$data['pasta_id'], $data['proposta_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'create_folder':
            $stmt = $db->prepare("INSERT INTO pastas_propostas (id, nome) VALUES (?, ?)");
            $stmt->execute([$data['id'], $data['nome']]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_folder':
            // 1. Desvincular propostas
            $stmt = $db->prepare("UPDATE propostas SET pasta_id = NULL WHERE pasta_id = ?");
            $stmt->execute([$data['id']]);
            // 2. Deletar pasta
            $stmt = $db->prepare("DELETE FROM pastas_propostas WHERE id = ?");
            $stmt->execute([$data['id']]);
            echo json_encode(['success' => true]);
            break;

        case 'rename_folder':
            $stmt = $db->prepare("UPDATE pastas_propostas SET nome = ? WHERE id = ?");
            $stmt->execute([$data['nome'], $data['id']]);
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação inválida']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
