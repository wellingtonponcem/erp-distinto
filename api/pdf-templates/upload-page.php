<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Metodo nao permitido'], 405);
}

if (empty($_FILES['imagem'])) {
    responderJson(['erro' => 'Imagem nao enviada. Verifique o tamanho do arquivo.'], 422);
}

$uploadErro = $_FILES['imagem']['error'] ?? UPLOAD_ERR_NO_FILE;
if ($uploadErro !== UPLOAD_ERR_OK) {
    $mensagens = [
        UPLOAD_ERR_INI_SIZE => 'Imagem maior que o limite do servidor.',
        UPLOAD_ERR_FORM_SIZE => 'Imagem maior que o limite permitido.',
        UPLOAD_ERR_PARTIAL => 'Upload incompleto. Tente novamente.',
        UPLOAD_ERR_NO_FILE => 'Nenhuma imagem selecionada.',
        UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporaria do servidor indisponivel.',
        UPLOAD_ERR_CANT_WRITE => 'Servidor nao conseguiu gravar o arquivo.',
        UPLOAD_ERR_EXTENSION => 'Upload bloqueado por uma extensao do servidor.',
    ];
    responderJson(['erro' => $mensagens[$uploadErro] ?? 'Falha no upload da imagem.'], 422);
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
    responderJson(['erro' => 'Formato invalido. Use PNG, JPG ou WEBP.'], 422);
}

$dir = __DIR__ . '/../../uploads/pdf-templates';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

if (!is_dir($dir) || !is_writable($dir)) {
    responderJson(['erro' => 'Pasta uploads/pdf-templates sem permissao de escrita.'], 500);
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
