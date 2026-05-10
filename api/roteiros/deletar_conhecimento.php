<?php
/**
 * API: Deletar Fonte de Conhecimento
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/ia_roteiros.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$d = lerCorpo();

if (empty($d['id'])) {
    responderJson(['erro' => 'ID obrigatório'], 422);
}

try {
    $db = Database::get();
    
    // Primeiro pegamos o caminho do arquivo para deletar se necessário
    $stmt = $db->prepare("SELECT caminho_arquivo FROM roteiros_conhecimento WHERE id = ?");
    $stmt->execute([$d['id']]);
    $arquivo = $stmt->fetch(PDO::FETCH_ASSOC);

    // Deleta do banco
    $stmt = $db->prepare("DELETE FROM roteiros_conhecimento WHERE id = ?");
    $stmt->execute([$d['id']]);

    // Deleta arquivo físico se existir
    if ($arquivo && !empty($arquivo['caminho_arquivo'])) {
        $fullPath = __DIR__ . '/../../' . $arquivo['caminho_arquivo'];
        if (file_exists($fullPath)) unlink($fullPath);
    }

    // RECONSTRUÇÃO DA MEMÓRIA
    // Como uma fonte foi removida, precisamos refazer a memória mestra com o que sobrou
    IARoteiros::reconstruirMemoria();

    responderJson(['success' => true]);

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
