<?php
/**
 * API: Upload de Conhecimento (Roteiros)
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/ia_roteiros.php';
require_once __DIR__ . '/../../includes/assinatura.php';

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

$usuario = usuarioAtual();
$userId  = $usuario['id'];

// Verificar limite de arquivos (50 por usuário)
$limiteConhec = verificarLimiteConhecimento($userId);
if (!$limiteConhec['ok']) {
    responderJson([
        'success' => false,
        'error'   => "Limite de " . CONHECIMENTO_LIMITE . " arquivos atingido. Remova arquivos antigos para adicionar novos.",
        'total'   => $limiteConhec['total'],
        'limite'  => $limiteConhec['limite'],
    ], 403);
}

$arquivo    = $_FILES['arquivo'];
$ext        = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
$permitidos = ['pdf', 'txt', 'md', 'png', 'jpg', 'jpeg'];

if (!in_array($ext, $permitidos)) {
    responderJson(['erro' => 'Formato não permitido. Use PDF, TXT, MD ou Imagens (JPG, PNG).'], 422);
}

// Diretório isolado por usuário
$targetDir = __DIR__ . '/../../uploads/roteiros/' . $userId . '/conhecimento/';
if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

$novoNome   = uniqid() . '_' . basename($arquivo['name']);
$targetPath = $targetDir . $novoNome;
$caminhoDb  = 'uploads/roteiros/' . $userId . '/conhecimento/' . $novoNome;

if (move_uploaded_file($arquivo['tmp_name'], $targetPath)) {
    try {
        $texto = '';

        if ($ext === 'txt' || $ext === 'md') {
            $texto = file_get_contents($targetPath);

        } elseif ($ext === 'pdf') {
            $base64 = base64_encode(file_get_contents($targetPath));
            $texto  = IARoteiros::processarPdf($base64, $userId);
            if (strpos($texto, 'Erro') === 0) throw new Exception($texto);

        } elseif (in_array($ext, ['png', 'jpg', 'jpeg'])) {
            $base64   = base64_encode(file_get_contents($targetPath));
            $mimeType = ($ext === 'png') ? 'image/png' : 'image/jpeg';
            $texto    = IARoteiros::processarImagem($base64, $mimeType, $userId);
            if (strpos($texto, 'Erro') === 0) throw new Exception($texto);
        }

        $db = Database::get();

        $db->exec("ALTER TABLE roteiros_conhecimento ADD COLUMN IF NOT EXISTS sincronizado BOOLEAN DEFAULT FALSE");

        $stmt = $db->prepare(
            "INSERT INTO roteiros_conhecimento (nome_arquivo, caminho_arquivo, tipo_arquivo, texto_extraido, user_id)
             VALUES (?, ?, ?, ?, ?) RETURNING id, nome_arquivo, tipo_arquivo, created_at, ativo"
        );
        $stmt->execute([$arquivo['name'], $caminhoDb, $ext, $texto, $userId]);
        $arquivo_salvo = $stmt->fetch(PDO::FETCH_ASSOC);

        // Consolidar memória do usuário
        $consolidou = IARoteiros::consolidarMemoria($texto, $userId);

        if ($consolidou) {
            $db->prepare("UPDATE roteiros_conhecimento SET sincronizado = TRUE WHERE id = ? AND user_id = ?")
               ->execute([$arquivo_salvo['id'], $userId]);
        }

        $novaMemoria = $db->prepare("SELECT conteudo FROM roteiros_memoria WHERE user_id = ? LIMIT 1");
        $novaMemoria->execute([$userId]);
        $memoriaConteudo = $novaMemoria->fetchColumn();

        $arquivo_salvo['sincronizado'] = $consolidou;

        responderJson([
            'success'      => true,
            'arquivo'      => $arquivo_salvo,
            'nova_memoria' => $memoriaConteudo ?: '',
        ]);

    } catch (Exception $e) {
        responderJson(['success' => false, 'error' => $e->getMessage()], 500);
    }
} else {
    responderJson(['success' => false, 'error' => 'Falha ao salvar arquivo no servidor.'], 400);
}
