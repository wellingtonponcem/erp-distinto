<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

try {
    $db = Database::get();
    $metodo = $_SERVER['REQUEST_METHOD'];

    switch ($metodo) {
        case 'GET':
            $categoria = $_GET['categoria'] ?? null;
            if ($categoria) {
                $stmt = $db->prepare("SELECT * FROM depoimentos WHERE categoria = ? ORDER BY ordem ASC, created_at ASC");
                $stmt->execute([$categoria]);
            } else {
                $stmt = $db->query("SELECT * FROM depoimentos ORDER BY categoria ASC, ordem ASC");
            }
            responderJson($stmt->fetchAll());
            break;

        case 'POST':
            $d = lerCorpo();
            if (empty($d['texto']) || empty($d['autor']) || empty($d['categoria'])) {
                responderJson(['erro' => 'Texto, autor e categoria são obrigatórios'], 422);
            }
            $categorias = ['casamento', 'filmmaker', '15anos', 'marketing'];
            if (!in_array($d['categoria'], $categorias)) {
                responderJson(['erro' => 'Categoria inválida'], 422);
            }
            $id = gerarId();
            $stmt = $db->prepare("INSERT INTO depoimentos (id, texto, autor, categoria, ativo, ordem) VALUES (?, ?, ?, ?, 1, ?)");
            $stmt->execute([$id, $d['texto'], $d['autor'], $d['categoria'], (int)($d['ordem'] ?? 0)]);
            responderJson(['ok' => true, 'id' => $id], 201);
            break;

        case 'PUT':
            $d = lerCorpo();
            if (empty($d['id'])) responderJson(['erro' => 'ID obrigatório'], 422);

            // Toggle ativo
            if (isset($d['ativo']) && count($d) === 2) {
                $stmt = $db->prepare("UPDATE depoimentos SET ativo = ? WHERE id = ?");
                $stmt->execute([(int)$d['ativo'], $d['id']]);
                responderJson(['ok' => true]);
                break;
            }

            if (empty($d['texto']) || empty($d['autor']) || empty($d['categoria'])) {
                responderJson(['erro' => 'Texto, autor e categoria são obrigatórios'], 422);
            }
            $stmt = $db->prepare("UPDATE depoimentos SET texto=?, autor=?, categoria=?, ativo=?, ordem=? WHERE id=?");
            $stmt->execute([$d['texto'], $d['autor'], $d['categoria'], (int)($d['ativo'] ?? 1), (int)($d['ordem'] ?? 0), $d['id']]);
            responderJson(['ok' => true]);
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? '';
            if (!$id) responderJson(['erro' => 'ID obrigatório'], 422);
            $db->prepare("DELETE FROM depoimentos WHERE id = ?")->execute([$id]);
            responderJson(['ok' => true]);
            break;

        default:
            responderJson(['erro' => 'Método não permitido'], 405);
    }
} catch (Exception $e) {
    responderJson(['erro' => 'Erro interno: ' . $e->getMessage()], 500);
}
