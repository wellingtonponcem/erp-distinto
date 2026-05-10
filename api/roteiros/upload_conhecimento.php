<?php
/**
 * API: Upload de Conhecimento (Roteiros)
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

if (empty($_FILES['arquivo'])) {
    responderJson(['erro' => 'Nenhum arquivo enviado.'], 422);
}

$arquivo = $_FILES['arquivo'];
$ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
$permitidos = ['pdf', 'txt', 'md'];

if (!in_array($ext, $permitidos)) {
    responderJson(['erro' => 'Formato de arquivo não permitido. Use PDF, TXT ou MD.'], 422);
}

$targetDir = __DIR__ . '/../../uploads/roteiros/conhecimento/';
if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

$novoNome = uniqid() . '_' . basename($arquivo['name']);
$targetPath = $targetDir . $novoNome;

if (move_uploaded_file($arquivo['tmp_name'], $targetPath)) {
    $texto = "";

    if ($ext === 'txt' || $ext === 'md') {
        $texto = file_get_contents($targetPath);
    } elseif ($ext === 'pdf') {
        // Tentativa de extração básica de PDF (sem bibliotecas externas)
        $texto = extrairTextoPdfBasico($targetPath);
    }

    try {
        $db = Database::get();
        $stmt = $db->prepare("INSERT INTO roteiros_conhecimento (nome_arquivo, caminho_arquivo, tipo_arquivo, texto_extraido) VALUES (?, ?, ?, ?)");
        $stmt->execute([$arquivo['name'], 'uploads/roteiros/conhecimento/' . $novoNome, $ext, $texto]);

        responderJson([
            'success' => true,
            'id' => $db->lastInsertId(),
            'nome' => $arquivo['name']
        ]);
    } catch (Exception $e) {
        responderJson(['success' => false, 'error' => $e->getMessage()], 500);
    }
} else {
    responderJson(['erro' => 'Falha ao salvar arquivo no servidor.'], 500);
}

/**
 * Extrator básico de texto de PDF
 * Nota: Funciona apenas para PDFs que não estão comprimidos ou encriptados de forma complexa.
 * O ideal seria uma biblioteca como Smalot/PdfParser.
 */
function extrairTextoPdfBasico($filename) {
    $content = file_get_contents($filename);
    
    // Tenta encontrar blocos de texto (BT ... ET)
    preg_match_all("/BT\s*(.*?)\s*ET/s", $content, $matches);
    $text = "";
    foreach ($matches[1] as $match) {
        // Tenta extrair strings dentro de parênteses ( ... )
        preg_match_all("/\((.*?)\)/", $match, $strings);
        foreach ($strings[1] as $str) {
            $text .= $str . " ";
        }
    }
    
    if (empty(trim($text))) {
        // Fallback: Tenta pegar qualquer coisa que pareça texto se o PDF for muito simples
        $text = preg_replace('/[^\x20-\x7E\s]/', '', $content);
    }

    return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
}
