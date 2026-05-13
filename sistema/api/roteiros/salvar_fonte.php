<?php
/**
 * API: Salvar Fonte de Conhecimento (Texto ou URL)
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/ia_roteiros.php';
require_once __DIR__ . '/../../includes/assinatura.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$d     = lerCorpo();
$type  = $d['type']  ?? '';
$value = $d['value'] ?? '';

if (!$type || !$value) {
    responderJson(['erro' => 'Dados incompletos'], 422);
}

$usuario = usuarioAtual();
$userId  = $usuario['id'];

// Verificar limite de arquivos (50 por usuário)
$limiteConhec = verificarLimiteConhecimento($userId);
if (!$limiteConhec['ok']) {
    responderJson([
        'success' => false,
        'error'   => "Limite de " . CONHECIMENTO_LIMITE . " fontes atingido. Remova fontes antigas para adicionar novas.",
        'total'   => $limiteConhec['total'],
        'limite'  => $limiteConhec['limite'],
    ], 403);
}

// Aumentar limites para processamento da IA
set_time_limit(180);

try {
    $db = Database::get();
    $nome = "";
    $texto = "";

    if ($type === 'text') {
        $tituloLimpo = strip_tags($value);
        $nome  = mb_substr($tituloLimpo, 0, 40) . (mb_strlen($tituloLimpo) > 40 ? '...' : '');
        if (!$nome) $nome = "Texto Copiado (" . date('H:i') . ")";
        $texto = $value;

    } elseif ($type === 'url') {
        $host    = parse_url($value, PHP_URL_HOST) ?: $value;
        $nome    = "Link: " . $host;
        $isYouTube = strpos($host, 'youtube.com') !== false || strpos($host, 'youtu.be') !== false;

        if ($isYouTube) {
            // ── YouTube: Gemini lê o vídeo diretamente pela URL ──────────────
            $nome  = "YouTube: " . $value;
            $texto = IARoteiros::processarYoutube($value, $userId);
            if (strpos($texto, 'Erro') === 0) throw new Exception($texto);

        } else {
            // ── Site/artigo: scraping + Gemini resume o conteúdo ─────────────
            $ctx  = stream_context_create(['http' => [
                'timeout' => 15,
                'header'  => "User-Agent: Mozilla/5.0 (compatible; Googlebot/2.1)\r\n"
            ]]);
            $html = @file_get_contents($value, false, $ctx);
            if (!$html) throw new Exception("Não foi possível acessar a URL. Verifique se o site permite acesso.");

            if (preg_match('/<title>(.*?)<\/title>/is', $html, $m)) {
                $nome = "Link: " . html_entity_decode(trim($m[1]));
            }

            // Limpeza básica de HTML
            $bruto = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
            $bruto = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $bruto);
            $bruto = strip_tags($bruto);
            $bruto = html_entity_decode($bruto, ENT_QUOTES, 'UTF-8');
            $bruto = preg_replace('/\s+/', ' ', $bruto);
            $bruto = trim($bruto);

            // Gemini extrai apenas o que é estratégico
            $texto = IARoteiros::resumirConteudoUrl($bruto, $value, $userId);
            if (strpos($texto, 'Erro') === 0) throw new Exception($texto);
        }
    }

    $stmt = $db->prepare(
        "INSERT INTO roteiros_conhecimento (nome_arquivo, caminho_arquivo, tipo_arquivo, texto_extraido, user_id)
         VALUES (?, ?, ?, ?, ?) RETURNING id, nome_arquivo, tipo_arquivo, created_at, ativo"
    );
    $stmt->execute([$nome, $type === 'url' ? $value : 'manual_entry', $type, $texto, $userId]);
    $arquivo_salvo = $stmt->fetch(PDO::FETCH_ASSOC);

    // RECONSTRUÇÃO DA MEMÓRIA DO USUÁRIO
    IARoteiros::reconstruirMemoria($userId);

    // Retorna dados atualizados para o frontend
    $stmtMem  = $db->prepare("SELECT conteudo FROM roteiros_memoria WHERE user_id = ? LIMIT 1");
    $stmtMem->execute([$userId]);
    $novaMemoria = $stmtMem->fetchColumn();

    responderJson([
        'success'      => true,
        'arquivo'      => $arquivo_salvo,
        'nova_memoria' => $novaMemoria ?: '',
    ]);

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
