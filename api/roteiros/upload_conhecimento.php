<?php
/**
 * API: Upload de Conhecimento (Roteiros)
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/ia_roteiros.php';

exigirAutenticacao();

// DEBUG: Ativar logs de erro para descobrir o motivo do 500
error_reporting(E_ALL);
ini_set('display_errors', 1);
$logFile = __DIR__ . '/../../uploads/roteiros/conhecimento/erro_upload.log';

// Aumentar tempo de execução para processamento da IA
set_time_limit(600); 
ini_set('memory_limit', '1024M');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

if (empty($_FILES['arquivo'])) {
    responderJson(['erro' => 'Nenhum arquivo enviado.'], 422);
}

$arquivo = $_FILES['arquivo'];
$ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
$permitidos = ['pdf', 'txt', 'md', 'png', 'jpg', 'jpeg'];

if (!in_array($ext, $permitidos)) {
    responderJson(['erro' => 'Formato não permitido. Use PDF, TXT, MD ou Imagens (JPG, PNG).'], 422);
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
        $texto = extrairTextoPdfBasico($targetPath);
    } elseif (in_array($ext, ['png', 'jpg', 'jpeg'])) {
        // IA Vision: Transforma imagem em texto
        $imageData = base64_encode(file_get_contents($targetPath));
        $mimeType = ($ext === 'png') ? 'image/png' : 'image/jpeg';
        $texto = IARoteiros::descreverImagem($imageData, $mimeType);
        
        if (strpos($texto, 'Erro') === 0) {
            throw new Exception($texto);
        }
    }

    try {
        $db = Database::get();
        
        // Auto-migração: Garantir que a tabela exista
        $db->exec("CREATE TABLE IF NOT EXISTS roteiros_conhecimento (
            id SERIAL PRIMARY KEY,
            nome_arquivo TEXT NOT NULL,
            caminho_arquivo TEXT NOT NULL,
            tipo_arquivo TEXT,
            texto_extraido TEXT,
            ativo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $stmt = $db->prepare("INSERT INTO roteiros_conhecimento (nome_arquivo, caminho_arquivo, tipo_arquivo, texto_extraido) VALUES (?, ?, ?, ?) RETURNING id");
        $stmt->execute([$arquivo['name'], 'uploads/roteiros/conhecimento/' . $novoNome, $ext, $texto]);
        $newId = $stmt->fetchColumn();

        // NOVIDADE: Consolidação de Memória
        // A IA lê o que já sabe + o novo arquivo e cria uma memória única destilada
        IARoteiros::consolidarMemoria($texto);

        responderJson([
            'success' => true,
            'id' => $newId,
            'nome' => $arquivo['name']
        ]);
    } catch (Exception $e) {
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
        responderJson(['success' => false, 'error' => $e->getMessage()], 500);
    }
} else {
    responderJson(['success' => false, 'error' => 'Falha ao salvar arquivo no servidor.'], 400);
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
