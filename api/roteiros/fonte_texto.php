<?php
/**
 * API: Buscar ou atualizar o texto extraído de uma fonte de conhecimento.
 * GET  ?id=X         → retorna o texto
 * POST id + texto    → salva o texto editado
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

try {
    $db  = Database::get();
    $id  = $_GET['id'] ?? $_POST['id'] ?? '';

    if (empty($id)) {
        responderJson(['success' => false, 'error' => 'ID inválido.'], 400);
        exit;
    }

    // --- GET: buscar texto ---
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $db->prepare("SELECT id, nome_arquivo, tipo_arquivo, texto_extraido FROM roteiros_conhecimento WHERE id = ?");
        $stmt->execute([$id]);
        $fonte = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fonte) {
            responderJson(['success' => false, 'error' => 'Fonte não encontrada.'], 404);
            exit;
        }

        responderJson(['success' => true, 'fonte' => $fonte]);
        exit;
    }

    // --- POST: salvar texto editado ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $novoTexto = trim($_POST['texto'] ?? '');

        $stmt = $db->prepare("UPDATE roteiros_conhecimento SET texto_extraido = ?, sincronizado = FALSE WHERE id = ?");
        $stmt->execute([$novoTexto, $id]);

        responderJson(['success' => true, 'message' => 'Texto atualizado. A fonte precisará ser sincronizada novamente.']);
        exit;
    }

    responderJson(['success' => false, 'error' => 'Método não permitido.'], 405);

} catch (Throwable $e) {
    responderJson(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()], 500);
}
