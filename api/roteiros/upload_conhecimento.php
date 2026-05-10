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
    try {
        $texto = "";

        if ($ext === 'txt' || $ext === 'md') {
            $texto = file_get_contents($targetPath);

        } elseif ($ext === 'pdf') {
            // Gemini lê PDF nativamente via base64
            $base64 = base64_encode(file_get_contents($targetPath));
            $texto  = IARoteiros::processarPdf($base64);
            if (strpos($texto, 'Erro') === 0) throw new Exception($texto);

        } elseif (in_array($ext, ['png', 'jpg', 'jpeg'])) {
            // Gemini Vision via base64
            $base64   = base64_encode(file_get_contents($targetPath));
            $mimeType = ($ext === 'png') ? 'image/png' : 'image/jpeg';
            $texto    = IARoteiros::processarImagem($base64, $mimeType);
            if (strpos($texto, 'Erro') === 0) throw new Exception($texto);
        }

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

        $stmt = $db->prepare("INSERT INTO roteiros_conhecimento (nome_arquivo, caminho_arquivo, tipo_arquivo, texto_extraido) VALUES (?, ?, ?, ?) RETURNING id, nome_arquivo, tipo_arquivo, created_at, ativo");
        $stmt->execute([$arquivo['name'], 'uploads/roteiros/conhecimento/' . $novoNome, $ext, $texto]);
        $arquivo_salvo = $stmt->fetch(PDO::FETCH_ASSOC);

        // Consolidação de Memória
        IARoteiros::consolidarMemoria($texto);

        // Busca a memória atualizada para retornar ao frontend
        $novaMemoria = $db->query("SELECT conteudo FROM roteiros_memoria LIMIT 1")->fetchColumn();

        responderJson([
            'success'       => true,
            'arquivo'       => $arquivo_salvo,
            'nova_memoria'  => $novaMemoria ?: '',
        ]);

    } catch (Exception $e) {
        responderJson(['success' => false, 'error' => $e->getMessage()], 500);
    }
} else {
    responderJson(['success' => false, 'error' => 'Falha ao salvar arquivo no servidor.'], 400);
}

// PDFs agora são lidos pelo Gemini (base64) — extrator manual removido.
