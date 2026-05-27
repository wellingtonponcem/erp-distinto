<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

if (empty($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
    responderJson(['erro' => 'Imagem não enviada'], 422);
}

$tmp = $_FILES['imagem']['tmp_name'];
$mime = mime_content_type($tmp);
$ext = match ($mime) {
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    default => null,
};

if (!$ext) {
    responderJson(['erro' => 'Formato inválido. Use PNG, JPG ou WEBP.'], 422);
}

$dir = __DIR__ . '/../../uploads/pdf-templates';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$nome = gerarId() . '.' . $ext;
$destino = $dir . '/' . $nome;
if (!move_uploaded_file($tmp, $destino)) {
    responderJson(['erro' => 'Falha ao salvar imagem'], 500);
}

responderJson([
    'success' => true,
    'url' => raizUrl('/uploads/pdf-templates/' . $nome),
]);
