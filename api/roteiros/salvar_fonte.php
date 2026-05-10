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
        // Pega as primeiras 40 letras para o título de forma instantânea
        $tituloLimpo = strip_tags($value);
        $nome = mb_substr($tituloLimpo, 0, 40) . (mb_strlen($tituloLimpo) > 40 ? '...' : '');
        if (!$nome) $nome = "Texto Copiado (" . date('H:i') . ")";
        
        $texto = $value;
    } elseif ($type === 'url') {
        $host = parse_url($value, PHP_URL_HOST);
        $nome = "Link: " . $host;
        
        // Scraping básico
        $ctx = stream_context_create(['http' => ['timeout' => 10, 'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"]]);
        $html = @file_get_contents($value, false, $ctx);
        
        if (!$html) throw new Exception("Não foi possível acessar a URL. Verifique se o site permite acesso.");
        
        // Tentar pegar o título da página
        if (preg_match('/<title>(.*?)<\/title>/is', $html, $matches)) {
            $nome = "Link: " . trim($matches[1]);
        }

        // Limpeza de HTML
        $texto = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $texto = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $texto);
        $texto = strip_tags($texto);
        $texto = html_entity_decode($texto);
        $texto = preg_replace('/\s+/', ' ', $texto); 
        $texto = trim($texto);

        // Se for YouTube, adicionar nota
        if (strpos($host, 'youtube.com') !== false || strpos($host, 'youtu.be') !== false) {
            $texto = "Conteúdo de Vídeo (YouTube): \n" . $texto;
        }
    }

    $stmt = $db->prepare("INSERT INTO roteiros_conhecimento (nome_arquivo, caminho_arquivo, tipo_arquivo, texto_extraido) VALUES (?, ?, ?, ?) RETURNING id");
    $stmt->execute([$nome, $type === 'url' ? $value : 'manual_entry', $type, $texto]);
    
    // RECONSTRUÇÃO DA MEMÓRIA
    IARoteiros::reconstruirMemoria();

    responderJson(['success' => true]);

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
