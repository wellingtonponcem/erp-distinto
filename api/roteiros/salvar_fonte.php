<?php
/**
 * API: Salvar Fonte de Conhecimento (Texto ou URL)
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
$type = $d['type'] ?? '';
$value = $d['value'] ?? '';

if (!$type || !$value) {
    responderJson(['erro' => 'Dados incompletos'], 422);
}

// Aumentar limites para processamento da IA
set_time_limit(180);

try {
    $db = Database::get();
    $nome = "";
    $texto = "";

    if ($type === 'text') {
        $nome = "Texto Copiado (" . date('H:i') . ")";
        $texto = $value;
    } elseif ($type === 'url') {
        $nome = "Link: " . parse_url($value, PHP_URL_HOST);
        
        // Scraping básico
        $html = @file_get_contents($value);
        if (!$html) throw new Exception("Não foi possível acessar a URL.");
        
        // Limpeza básica de HTML
        $texto = strip_tags($html);
        $texto = preg_replace('/\s+/', ' ', $texto); // Remove espaços duplos e quebras excessivas
        $texto = trim($texto);
    }

    $stmt = $db->prepare("INSERT INTO roteiros_conhecimento (nome_arquivo, caminho_arquivo, tipo_arquivo, texto_extraido) VALUES (?, ?, ?, ?) RETURNING id");
    $stmt->execute([$nome, $type === 'url' ? $value : 'manual_entry', $type, $texto]);
    
    // RECONSTRUÇÃO DA MEMÓRIA
    IARoteiros::reconstruirMemoria();

    responderJson(['success' => true]);

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
