<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

if (empty($_FILES['imagem'])) {
    responderJson(['erro' => 'Nenhuma imagem enviada.'], 422);
}

$uploadErro = $_FILES['imagem']['error'] ?? UPLOAD_ERR_NO_FILE;
if ($uploadErro !== UPLOAD_ERR_OK) {
    $mensagens = [
        UPLOAD_ERR_INI_SIZE => 'Imagem maior que o limite do servidor.',
        UPLOAD_ERR_FORM_SIZE => 'Imagem maior que o limite permitido.',
        UPLOAD_ERR_PARTIAL => 'Upload incompleto.',
        UPLOAD_ERR_NO_FILE => 'Nenhuma imagem selecionada.',
        UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária indisponível.',
        UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar arquivo.',
        UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão.',
    ];
    responderJson(['erro' => $mensagens[$uploadErro] ?? 'Falha no upload.'], 422);
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

$dir = __DIR__ . '/../../uploads/produtos';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

if (!is_dir($dir) || !is_writable($dir)) {
    responderJson(['erro' => 'Pasta uploads/produtos sem permissão de escrita.'], 500);
}

$nome = gerarId() . '.' . $ext;
$destino = $dir . '/' . $nome;
if (!move_uploaded_file($tmp, $destino)) {
    responderJson(['erro' => 'Falha ao salvar imagem.'], 500);
}

responderJson([
    'success' => true,
    'url' => raizUrl('/uploads/produtos/' . $nome),
]);
