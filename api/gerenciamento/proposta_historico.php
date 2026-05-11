<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');
exigirAutenticacao();

$db = Database::get();
$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $proposta_id = $_GET['id'] ?? null;
    if (!$proposta_id) {
        die(json_encode(['erro' => 'ID da proposta não informado.']));
    }

    $stmt = $db->prepare("
        SELECT h.*, u.nome as usuario_nome 
        FROM propostas_historico h
        JOIN users u ON h.user_id = u.id
        WHERE h.proposta_id = ?
        ORDER BY h.created_at DESC
    ");
    $stmt->execute([$proposta_id]);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($metodo === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $proposta_id = $data['proposta_id'] ?? null;
    $tipo = $data['tipo'] ?? 'nota';
    $conteudo = trim($data['conteudo'] ?? '');
    $user_id = $_SESSION['user_id'];

    if (!$proposta_id || !$conteudo) {
        die(json_encode(['erro' => 'Dados incompletos.']));
    }

    $stmt = $db->prepare("
        INSERT INTO propostas_historico (proposta_id, user_id, tipo, conteudo)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$proposta_id, $user_id, $tipo, $conteudo]);

    echo json_encode(['sucesso' => true]);
    exit;
}
