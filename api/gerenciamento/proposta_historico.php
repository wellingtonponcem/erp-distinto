<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');
exigirAutenticacao();

$metodo = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::get();
} catch (Exception $e) {
    echo json_encode(['erro' => 'Falha no banco: ' . $e->getMessage()]);
    exit;
}

if ($metodo === 'GET') {
    $proposta_id = $_GET['id'] ?? null;
    if (!$proposta_id) {
        echo json_encode(['erro' => 'ID da proposta não informado.']);
        exit;
    }

    try {
        // Verificar se a tabela existe
        $check = $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_name='propostas_historico'");
        if ($check->fetchColumn() == 0) {
            // Criar tabela se não existir
            $db->exec("CREATE TABLE propostas_historico (
                id SERIAL PRIMARY KEY,
                proposta_id TEXT NOT NULL,
                user_id TEXT NOT NULL,
                tipo TEXT DEFAULT 'nota',
                conteudo TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        }

        $stmt = $db->prepare("
            SELECT h.*, COALESCE(u.nome, 'Sistema') as usuario_nome 
            FROM propostas_historico h
            LEFT JOIN users u ON CAST(h.user_id AS TEXT) = CAST(u.id AS TEXT)
            WHERE h.proposta_id = ?
            ORDER BY h.created_at DESC
        ");
        $stmt->execute([$proposta_id]);
        $rows = $stmt->fetchAll();
        echo json_encode($rows);
    } catch (Exception $e) {
        echo json_encode(['erro' => 'Erro GET: ' . $e->getMessage()]);
    }
    exit;
}

if ($metodo === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $proposta_id = $data['proposta_id'] ?? null;
        $tipo = $data['tipo'] ?? 'nota';
        $conteudo = trim($data['conteudo'] ?? '');
        $user_id = $_SESSION['user_id'] ?? '';

        if (!$proposta_id || !$conteudo) {
            echo json_encode(['erro' => 'Dados incompletos. proposta_id=' . ($proposta_id ?? 'null') . ' conteudo=' . ($conteudo ?: 'vazio')]);
            exit;
        }

        if (!$user_id) {
            echo json_encode(['erro' => 'Sessão sem user_id.']);
            exit;
        }

        // Verificar se a tabela existe
        $check = $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_name='propostas_historico'");
        if ($check->fetchColumn() == 0) {
            $db->exec("CREATE TABLE propostas_historico (
                id SERIAL PRIMARY KEY,
                proposta_id TEXT NOT NULL,
                user_id TEXT NOT NULL,
                tipo TEXT DEFAULT 'nota',
                conteudo TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        }

        $stmt = $db->prepare("
            INSERT INTO propostas_historico (proposta_id, user_id, tipo, conteudo)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$proposta_id, $user_id, $tipo, $conteudo]);

        echo json_encode(['sucesso' => true, 'debug' => ['proposta_id' => $proposta_id, 'user_id' => $user_id, 'tipo' => $tipo]]);
    } catch (Exception $e) {
        echo json_encode(['erro' => 'Erro POST: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['erro' => 'Método não suportado: ' . $metodo]);
