<?php
/**
 * API: Processa uma única fonte de conhecimento e a destila na memória mestra.
 * Suporta: texto, url, pdf, imagem, youtube
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/ia_roteiros.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();
set_time_limit(300);
ini_set('memory_limit', '1024M');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    responderJson(['success' => false, 'error' => 'ID de fonte inválido.'], 400);
    exit;
}

$db = Database::get();
$stmt = $db->prepare("SELECT * FROM roteiros_conhecimento WHERE id = ?");
$stmt->execute([$id]);
$fonte = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fonte) {
    responderJson(['success' => false, 'error' => 'Fonte não encontrada.'], 404);
    exit;
}

try {
    $texto = trim($fonte['texto_extraido'] ?? '');
    $tipo  = $fonte['tipo_arquivo'] ?? '';
    $nome  = $fonte['nome_arquivo'] ?? '';
    $ext   = strtolower(pathinfo($nome, PATHINFO_EXTENSION));

    // --- Extração via Gemini para tipos binários sem texto salvo ---
    if (empty($texto)) {
        $caminho = __DIR__ . '/../../' . $fonte['caminho_arquivo'];

        if ($ext === 'pdf' && file_exists($caminho)) {
            $base64 = base64_encode(file_get_contents($caminho));
            $texto  = IARoteiros::processarPdf($base64);
            if (strpos($texto, 'Erro') === 0) throw new Exception($texto);

        } elseif (in_array($ext, ['png', 'jpg', 'jpeg']) && file_exists($caminho)) {
            $base64   = base64_encode(file_get_contents($caminho));
            $mimeType = ($ext === 'png') ? 'image/png' : 'image/jpeg';
            $texto    = IARoteiros::processarImagem($base64, $mimeType);
            if (strpos($texto, 'Erro') === 0) throw new Exception($texto);

        } elseif ($tipo === 'url' && !empty($fonte['caminho_arquivo'])) {
            // YouTube ou site: re-processa a URL
            $url = $fonte['caminho_arquivo'];
            if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
                $texto = IARoteiros::processarYoutube($url);
            } else {
                $conteudo = @file_get_contents($url);
                $texto = $conteudo ? IARoteiros::resumirConteudoUrl($conteudo, $url) : '';
            }
            if (strpos($texto ?? '', 'Erro') === 0) throw new Exception($texto);
        }

        // Salva o texto extraído de volta no banco para não reprocessar
        if (!empty($texto)) {
            $db->prepare("UPDATE roteiros_conhecimento SET texto_extraido = ? WHERE id = ?")
               ->execute([$texto, $id]);
        }
    }

    // Se ainda não tem texto, marca como sincronizado mas sem contribuir à memória
    if (empty($texto)) {
        $db->prepare("UPDATE roteiros_conhecimento SET sincronizado = TRUE WHERE id = ?")
           ->execute([$id]);
        responderJson(['success' => true, 'sincronizado' => true, 'nova_memoria' => '', 'aviso' => 'Fonte sem conteúdo extraível.']);
        exit;
    }

    // --- Destilação no Groq → Memória Mestra ---
    $consolidou = IARoteiros::consolidarMemoria($texto);

    if ($consolidou) {
        $db->prepare("UPDATE roteiros_conhecimento SET sincronizado = TRUE WHERE id = ?")
           ->execute([$id]);
    }

    $novaMemoria = $db->query("SELECT conteudo FROM roteiros_memoria LIMIT 1")->fetchColumn();

    responderJson([
        'success'      => true,
        'sincronizado' => (bool) $consolidou,
        'nova_memoria' => $novaMemoria ?: '',
    ]);

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
