<?php
/**
 * Serviço de IA para Roteiros
 * - Gemini 2.0 Flash: imagens (base64), PDFs (base64), YouTube (URL nativa), URLs/sites
 * - Groq llama-3.3-70b: geração de roteiros, consolidação de memória (texto)
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

class IARoteiros
{
    // =========================================================================
    // INFRAESTRUTURA — chamadas às APIs
    // =========================================================================

    private static function logarChamadaIA(string $userId, string $provider, string $operacao, int $tokensIn, int $tokensOut): void
    {
        if (!$userId) return;

        try {
            $db = Database::get();
            
            // Buscar custo por 1k tokens
            if ($provider === 'openrouter') {
                $custo1k = 0.0;
            } else {
                $colunaCusto = ($provider === 'groq') ? 'groq_custo_por_1k_tokens' : 'gemini_custo_por_1k_tokens';
                $custo1k = (float) $db->query("SELECT $colunaCusto FROM configuracao_empresa WHERE id = 'principal' LIMIT 1")->fetchColumn();
            }
            
            $totalTokens = $tokensIn + $tokensOut;
            $custoUsd = ($totalTokens / 1000) * $custo1k;

            $stmt = $db->prepare("INSERT INTO log_ia_calls (user_id, provider, operacao, tokens_in, tokens_out, custo_usd) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $provider, $operacao, $tokensIn, $tokensOut, $custoUsd]);
        } catch (Exception $e) {
            // Silencioso para não quebrar a experiência do usuário se o log falhar
        }
    }

    private static function getConfig(string $chave)
    {
        try {
            $db   = Database::get();
            $stmt = $db->query("SELECT $chave FROM configuracao_empresa WHERE id = 'principal' LIMIT 1");
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return null;
        }
    }

    private static function getGroqKey(): ?string
    {
        $key = self::getConfig('groq_api_key');
        if (!$key) $key = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
        if (!$key || strpos($key, 'SUA_') === 0) return null;
        return $key;
    }

    private static function getOpenRouterKey(): ?string
    {
        $key = self::getConfig('openrouter_api_key');
        if (!$key) $key = defined('OPENROUTER_API_KEY') ? OPENROUTER_API_KEY : '';
        if (!$key || strpos($key, 'SUA_') === 0 || strpos($key, '<') === 0) return null;
        return $key;
    }

    private static function getGeminiKey(): ?string
    {
        // Verifica no banco de dados (coluna gemini_api_key se existir) e depois env.php
        try {
            $key = self::getConfig('gemini_api_key');
        } catch (Exception $e) {
            $key = null;
        }
        if (!$key) $key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
        if (!$key || strpos($key, 'SUA_') === 0) return null;
        return $key;
    }

    private static function temGemini(): bool
    {
        return self::getGeminiKey() !== null;
    }

    private static function extrairJson(string $texto): ?array
    {
        $json = trim(preg_replace('/```(?:json)?\s*|\s*```/', '', $texto));
        $dados = json_decode($json, true);
        if (is_array($dados)) return $dados;

        $inicio = strpos($json, '{');
        $fim = strrpos($json, '}');
        if ($inicio === false || $fim === false || $fim <= $inicio) return null;

        $trecho = substr($json, $inicio, $fim - $inicio + 1);
        $dados = json_decode($trecho, true);
        return is_array($dados) ? $dados : null;
    }

    private static function limparCampoRoteiro($valor, int $limite = 700): string
    {
        $texto = trim((string) $valor);
        $texto = preg_replace('/^\s*#{1,6}\s*/m', '', $texto);
        $texto = preg_replace('/\*\*(.*?)\*\*/s', '$1', $texto);
        $texto = preg_replace('/^\s*[-*]\s+/m', '', $texto);
        $texto = preg_replace('/\n{3,}/', "\n\n", $texto);

        if (mb_strlen($texto) > $limite) {
            $texto = rtrim(mb_substr($texto, 0, $limite), " \t\n\r\0\x0B.,;:") . '.';
        }

        return $texto;
    }

    private static function normalizarRoteiroGerado(array $dados): array
    {
        return [
            'titulo' => self::limparCampoRoteiro($dados['titulo'] ?? 'Roteiro Gerado', 140),
            'gancho' => self::limparCampoRoteiro($dados['gancho'] ?? '', 550),
            'quebra_crenca' => self::limparCampoRoteiro($dados['quebra_crenca'] ?? '', 1200),
            'desenvolvimento' => self::limparCampoRoteiro($dados['desenvolvimento'] ?? '', 1400),
            'conexao' => self::limparCampoRoteiro($dados['conexao'] ?? '', 1200),
            'fechamento' => self::limparCampoRoteiro($dados['fechamento'] ?? '', 900),
            'cta' => self::limparCampoRoteiro($dados['cta'] ?? '', 650),
            'tags' => self::limparCampoRoteiro($dados['tags'] ?? 'marketing, autoridade, reels', 180),
            'intencao' => self::limparCampoRoteiro($dados['intencao'] ?? 'CONSTRUIR AUTORIDADE', 80),
            'tema' => self::limparCampoRoteiro($dados['tema'] ?? '', 120),
        ];
    }

    private static function scoreSql(string $coluna = 'score'): string
    {
        return (defined('DB_PORT') && (int)DB_PORT === 3306)
            ? "COALESCE(CAST(NULLIF({$coluna}, '') AS DECIMAL(10,2)), 0)"
            : "COALESCE(NULLIF({$coluna}::text, '')::numeric, 0)";
    }

    // ─── Groq ─────────────────────────────────────────────────────────────────

    public static function chamarGroq(array $mensagens, ?string $model = null, string $userId = '', string $operacao = 'Generativa')
    {
        $apiKey = self::getGroqKey();
        if (!$apiKey) return "Erro: GROQ_API_KEY não configurada.";

        $payload = json_encode([
            'model'       => $model ?: (defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.3-70b-versatile'),
            'messages'    => $mensagens,
            'temperature' => 0.8,
            'max_tokens'  => 2000
        ]);

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 60,
        ]);

        $resposta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            return "Erro Groq (HTTP $httpCode): " . $resposta;
        }

        $dados = json_decode($resposta, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return "Erro: resposta Groq não é JSON válido. Body: " . substr($resposta, 0, 200);
        }

        // Log de consumo
        if (isset($dados['usage'])) {
            $tokensIn  = (int) ($dados['usage']['prompt_tokens'] ?? 0);
            $tokensOut = (int) ($dados['usage']['completion_tokens'] ?? 0);
            self::logarChamadaIA($userId, 'groq', $operacao, $tokensIn, $tokensOut);
        }

        return $dados['choices'][0]['message']['content']
            ?? "Erro: resposta Groq sem conteúdo. Body: " . json_encode($dados);
    }

    // ─── OpenRouter ───────────────────────────────────────────────────────────

    /**
     * Chamada ao OpenRouter no formato compatível com Chat Completions.
     */
    public static function chamarOpenRouter(array $mensagens, string $model, string $userId = '', string $operacao = 'Gerar Roteiro'): string
    {
        $apiKey = self::getOpenRouterKey();
        if (!$apiKey) return "Erro: OPENROUTER_API_KEY nao configurada.";

        $payload = json_encode([
            'model'       => $model,
            'messages'    => $mensagens,
            'temperature' => 0.75,
            'max_tokens'  => 3600
        ], JSON_UNESCAPED_UNICODE);

        $referer = defined('APP_URL') ? APP_URL : 'https://wedistinto.com';

        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'HTTP-Referer: ' . $referer,
                'X-Title: Meus Roteiros'
            ],
            CURLOPT_TIMEOUT => 90,
        ]);

        $resposta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($resposta === false) {
            return "Erro OpenRouter ($model): " . ($curlError ?: 'falha na chamada.');
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return "Erro OpenRouter ($model HTTP $httpCode): " . substr((string) $resposta, 0, 500);
        }

        $dados = json_decode($resposta, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return "Erro: resposta OpenRouter ($model) nao e JSON valido. Body: " . substr($resposta, 0, 200);
        }

        if (isset($dados['usage'])) {
            $tokensIn  = (int) ($dados['usage']['prompt_tokens'] ?? 0);
            $tokensOut = (int) ($dados['usage']['completion_tokens'] ?? 0);
            self::logarChamadaIA($userId, 'openrouter', $operacao . " ($model)", $tokensIn, $tokensOut);
        }

        return $dados['choices'][0]['message']['content']
            ?? "Erro: resposta OpenRouter ($model) sem conteudo. Body: " . json_encode($dados);
    }

    public static function chamarOpenRouterRoteiro(array $mensagens, string $userId = ''): string
    {
        $principal = 'qwen/qwen3-next-80b-a3b-instruct:free';
        $fallback  = 'openrouter/free';

        $resposta = self::chamarOpenRouter($mensagens, $principal, $userId, 'Gerar Roteiro');
        if (strpos($resposta, 'Erro') !== 0) {
            return $resposta;
        }

        $respostaFallback = self::chamarOpenRouterStream($mensagens, $fallback, $userId, 'Gerar Roteiro Fallback');
        if (strpos($respostaFallback, 'Erro') !== 0) {
            return $respostaFallback;
        }

        return $resposta . "\n\nFallback: " . $respostaFallback;
    }

    public static function chamarOpenRouterStream(array $mensagens, string $model, string $userId = '', string $operacao = 'Gerar Roteiro Fallback'): string
    {
        $apiKey = self::getOpenRouterKey();
        if (!$apiKey) return "Erro: OPENROUTER_API_KEY nao configurada.";

        $payload = json_encode([
            'model'          => $model,
            'messages'       => $mensagens,
            'temperature'    => 0.75,
            'max_tokens'     => 3600,
            'stream'         => true,
            'stream_options' => ['include_usage' => true]
        ], JSON_UNESCAPED_UNICODE);

        $referer = defined('APP_URL') ? APP_URL : 'https://wedistinto.com';
        $conteudo = '';
        $buffer = '';
        $tokensIn = 0;
        $tokensOut = 0;

        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'HTTP-Referer: ' . $referer,
                'X-Title: Meus Roteiros'
            ],
            CURLOPT_TIMEOUT => 120,
            CURLOPT_WRITEFUNCTION => function ($ch, string $chunk) use (&$buffer, &$conteudo, &$tokensIn, &$tokensOut) {
                $buffer .= $chunk;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);

                    if ($line === '' || strpos($line, 'data:') !== 0) {
                        continue;
                    }

                    $data = trim(substr($line, 5));
                    if ($data === '[DONE]') {
                        continue;
                    }

                    $json = json_decode($data, true);
                    if (!is_array($json)) {
                        continue;
                    }

                    $delta = $json['choices'][0]['delta']['content'] ?? '';
                    if ($delta !== '') {
                        $conteudo .= $delta;
                    }

                    if (isset($json['usage'])) {
                        $tokensIn = (int) ($json['usage']['prompt_tokens'] ?? 0);
                        $tokensOut = (int) ($json['usage']['completion_tokens'] ?? 0);
                    }
                }

                return strlen($chunk);
            }
        ]);

        $ok = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($ok === false) {
            return "Erro OpenRouter ($model): " . ($curlError ?: 'falha na chamada em stream.');
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return "Erro OpenRouter ($model HTTP $httpCode): falha na chamada em stream.";
        }

        if ($tokensIn || $tokensOut) {
            self::logarChamadaIA($userId, 'openrouter', $operacao . " ($model)", $tokensIn, $tokensOut);
        }

        return trim($conteudo) !== ''
            ? $conteudo
            : "Erro: resposta OpenRouter ($model) em stream sem conteudo.";
    }

    // ─── Gemini ───────────────────────────────────────────────────────────────

    /**
     * Chamada genérica ao Gemini.
     * $parts: array de partes no formato da API REST do Gemini
     *   texto    → ['text' => '...']
     *   base64   → ['inline_data' => ['mime_type' => '...', 'data' => '<base64>']]
     *   arquivo  → ['file_data'   => ['mime_type' => '...', 'file_uri' => 'https://...']]
     */
    public static function chamarGemini(array $parts, string $model = 'gemini-2.5-flash', string $userId = '', string $operacao = 'Vision/Análise'): string
    {
        $apiKey = self::getGeminiKey();
        if (!$apiKey) return "Erro: GEMINI_API_KEY não configurada em config/env.php.";

        $payload = json_encode([
            'contents' => [['parts' => $parts]],
            'generationConfig' => [
                'temperature'    => 0.4,
                'maxOutputTokens' => 8192
            ]
        ]);

        $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 120,
        ]);

        $resposta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            $err = json_decode($resposta, true);
            $msg = $err['error']['message'] ?? $resposta;
            return "Erro Gemini (HTTP $httpCode): $msg";
        }

        $dados = json_decode($resposta, true);

        // Log de consumo
        if (isset($dados['usageMetadata'])) {
            $tokensIn  = (int) ($dados['usageMetadata']['promptTokenCount'] ?? 0);
            $tokensOut = (int) ($dados['usageMetadata']['candidatesTokenCount'] ?? 0);
            self::logarChamadaIA($userId, 'gemini', $operacao, $tokensIn, $tokensOut);
        }

        return $dados['candidates'][0]['content']['parts'][0]['text']
            ?? "Erro: resposta inesperada do Gemini. Body: " . substr($resposta, 0, 300);
    }

    // =========================================================================
    // PROCESSAMENTO DE MÍDIA — Gemini para tudo
    // =========================================================================

    /**
     * Extrai conhecimento de uma IMAGEM usando Gemini Vision (base64).
     * Suporta PNG, JPG, JPEG, WEBP, GIF.
     */
    public static function processarImagem(string $base64, string $mimeType, string $userId = ''): string
    {
        if (!self::temGemini()) {
            return "Erro: GEMINI_API_KEY não configurada. Adicione sua chave em config/env.php.";
        }

        return self::chamarGemini([
            ['text' => "Extraia todo o texto estratégico, diretrizes, metodologias ou ideias contidas nesta imagem.
Se for um print de rede social, identifique o tom de voz, os ganchos utilizados e a estrutura do post.
Responda apenas com o conteúdo extraído, organizado em português. Não descreva o visual — foque no texto e nas estratégias."],
            ['inline_data' => ['mime_type' => $mimeType, 'data' => $base64]]
        ], 'gemini-2.5-flash', $userId, 'Processar Imagem');
    }

    /**
     * Extrai conhecimento de um PDF usando Gemini (base64, até ~20MB).
     */
    public static function processarPdf(string $base64, string $userId = ''): string
    {
        if (!self::temGemini()) {
            return "Erro: GEMINI_API_KEY não configurada. Adicione sua chave em config/env.php.";
        }

        return self::chamarGemini([
            ['text' => "Leia este PDF e extraia todo o conteúdo estratégico relevante: metodologias, frameworks, diretrizes de comunicação, tom de voz, estruturas de copywriting, gatilhos mentais, conceitos-chave e qualquer insight útil para marketing e criação de conteúdo.
Organize por tópicos em português. Descarte sumários, índices e cabeçalhos sem conteúdo."],
            ['inline_data' => ['mime_type' => 'application/pdf', 'data' => $base64]]
        ], 'gemini-2.5-flash', $userId, 'Processar PDF');
    }

    /**
     * Extrai conhecimento de um vídeo do YouTube usando Gemini (URL nativa).
     * O Gemini 2.0 Flash lê a transcrição e o contexto do vídeo diretamente.
     */
    public static function processarYoutube(string $url, string $userId = ''): string
    {
        if (!self::temGemini()) {
            return "Erro: GEMINI_API_KEY não configurada. Adicione sua chave em config/env.php.";
        }

        return self::chamarGemini([
            ['text' => "Analise este vídeo do YouTube e extraia:
- O tema central e as principais teses apresentadas
- Metodologias, frameworks ou estratégias ensinadas
- Ganchos e estrutura narrativa usados pelo criador
- Tom de voz, vocabulário e estilo de comunicação
- Insights e diretrizes aplicáveis à criação de conteúdo e marketing

Responda em português, organizado por tópicos. Foque no conteúdo estratégico."],
            ['file_data' => ['mime_type' => 'video/*', 'file_uri' => $url]]
        ], 'gemini-2.5-flash', $userId, 'Processar YouTube');
    }

    /**
     * Resume e extrai conhecimento estratégico do conteúdo de uma URL/site.
     * Usa Gemini se disponível, senão cai para Groq.
     */
    public static function resumirConteudoUrl(string $conteudo, string $url, string $userId = ''): string
    {
        $prompt = "URL: $url

Extraia e organize apenas as informações estratégicas relevantes do conteúdo abaixo: metodologias, diretrizes, tom de voz, ganchos de copywriting, conceitos-chave e insights para marketing e criação de conteúdo. Descarte menus, rodapés, anúncios e conteúdo irrelevante.

Conteúdo:
" . mb_substr($conteudo, 0, 30000);

        if (self::temGemini()) {
            return self::chamarGemini([['text' => $prompt]], 'gemini-2.5-flash', $userId, 'Resumir URL');
        }

        // Fallback Groq
        return self::chamarGroq([
            ['role' => 'system', 'content' => 'Você é um Estrategista de Conteúdo. Extraia apenas informações estratégicas relevantes.'],
            ['role' => 'user',   'content' => mb_substr($prompt, 0, 15000)]
        ], null, $userId, 'Resumir URL (Fallback)');
    }

    // =========================================================================
    // MEMÓRIA — consolidação com Groq
    // =========================================================================

    private static function getBaseConhecimento(string $userId = '', string $clienteId = ''): string
    {
        try {
            $db = Database::get();
            $clienteId = trim($clienteId);

            if ($clienteId !== '') {
                if (!$userId) return "";

                $stmtMemoria = $db->prepare("SELECT conteudo FROM roteiros_memoria WHERE user_id = ? LIMIT 1");
                $stmtMemoria->execute([$userId]);
                $memoriaGlobal = trim((string)$stmtMemoria->fetchColumn());

                $stmtGlobal = $db->prepare("
                    SELECT texto_extraido
                    FROM roteiros_conhecimento
                    WHERE ativo = TRUE
                      AND user_id = ?
                      AND (cliente_id IS NULL OR cliente_id = '')
                    ORDER BY created_at DESC
                ");
                $stmtGlobal->execute([$userId]);
                $fontesGlobais = $stmtGlobal->fetchAll(PDO::FETCH_COLUMN);

                $stmtCliente = $db->prepare("
                    SELECT texto_extraido
                    FROM roteiros_conhecimento
                    WHERE ativo = TRUE
                      AND user_id = ?
                      AND cliente_id = ?
                    ORDER BY created_at DESC
                ");
                $stmtCliente->execute([$userId, $clienteId]);
                $fontesCliente = $stmtCliente->fetchAll(PDO::FETCH_COLUMN);

                $blocos = [];
                $global = trim(implode("\n\n---\n\n", array_filter(array_merge([$memoriaGlobal], $fontesGlobais))));
                $cliente = trim(implode("\n\n---\n\n", array_filter($fontesCliente)));

                if ($global !== '') {
                    $blocos[] = "### CONHECIMENTO GLOBAL (METODOLOGIA E INSTRUÇÕES DE CRIAÇÃO)\nUse este bloco apenas para orientar estrutura, qualidade, tom estratégico e forma de criar roteiros. Não trate informações de nicho, produto, mercado ou cliente deste bloco como fatos sobre o cliente selecionado.\n\n" . $global;
                }

                if ($cliente !== '') {
                    $blocos[] = "### BASE INDIVIDUAL DO CLIENTE (CONTEXTO FACTUAL DO CLIENTE SELECIONADO)\nUse este bloco para entender o cliente, mercado, oferta, público, diferenciais e temas permitidos.\n\n" . $cliente;
                }

                return trim(implode("\n\n---\n\n", $blocos));
            }

            if ($userId) {
                $stmt = $db->prepare("SELECT conteudo FROM roteiros_memoria WHERE user_id = ? LIMIT 1");
                $stmt->execute([$userId]);
            } else {
                $stmt = $db->query("SELECT conteudo FROM roteiros_memoria LIMIT 1");
            }
            $memoria = $stmt->fetchColumn();

            if ($memoria) return $memoria;

            $base = $memoria ?: '';

            if ($userId) {
                $stmt = $db->prepare("SELECT texto_extraido FROM roteiros_conhecimento WHERE ativo = TRUE AND user_id = ?");
                $stmt->execute([$userId]);
            } else {
                $stmt = $db->query("SELECT texto_extraido FROM roteiros_conhecimento WHERE ativo = TRUE");
            }
            $textos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $fontes = implode("\n\n---\n\n", array_filter($textos));
            return trim($base . ($base && $fontes ? "\n\n--- FONTES COMPLEMENTARES ---\n\n" : '') . $fontes);
        } catch (Exception $e) {
            return "";
        }
    }

    private static function getMelhoresRoteiros(string $userId = '', string $clienteId = ''): string
    {
        try {
            $db = Database::get();
            $scoreSql = self::scoreSql();
            if ($userId) {
                if ($clienteId !== '') {
                    $stmt = $db->prepare("
                        SELECT titulo, gancho, quebra_crenca, desenvolvimento, conexao, fechamento, cta, status, score, created_at
                        FROM roteiros
                        WHERE user_id = ?
                          AND cliente_id = ?
                          AND (
                            COALESCE(titulo, '') <> ''
                            OR COALESCE(gancho, '') <> ''
                            OR COALESCE(quebra_crenca, '') <> ''
                            OR COALESCE(desenvolvimento, '') <> ''
                          )
                        ORDER BY
                          CASE
                            WHEN LOWER(COALESCE(status, '')) IN ('aprovado', 'postado', 'gravado') THEN 0
                            WHEN {$scoreSql} > 0 THEN 1
                            ELSE 2
                          END,
                          {$scoreSql} DESC,
                          created_at DESC
                        LIMIT 9
                    ");
                    $stmt->execute([$userId, $clienteId]);
                } else {
                    $stmt = $db->prepare("SELECT titulo, gancho, quebra_crenca, desenvolvimento, conexao, fechamento, cta, status, score FROM roteiros WHERE {$scoreSql} > 0 AND user_id = ? ORDER BY {$scoreSql} DESC LIMIT 5");
                    $stmt->execute([$userId]);
                }
            } else {
                $stmt = $db->query("SELECT titulo, gancho, quebra_crenca, desenvolvimento, conexao, fechamento, cta, status, score FROM roteiros WHERE {$scoreSql} > 0 ORDER BY {$scoreSql} DESC LIMIT 5");
            }
            $roteiros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $texto = "";
            foreach ($roteiros as $r) {
                $status = $r['status'] ?? 'referencia';
                $score = (string)($r['score'] ?? '0');
                $texto .= "ROTEIRO ANTERIOR DO CLIENTE PARA IMITAR PADRAO (status {$status}, score {$score}):\n";
                $texto .= "Titulo: {$r['titulo']}\n";
                $texto .= "Gancho: {$r['gancho']}\n";
                $texto .= "Quebra de crenca: {$r['quebra_crenca']}\n";
                $texto .= "Desenvolvimento: {$r['desenvolvimento']}\n";
                $texto .= "Conexao emocional: {$r['conexao']}\n";
                $texto .= "Fechamento: {$r['fechamento']}\n";
                $texto .= "CTA: {$r['cta']}\n\n";
            }
            return mb_substr($texto, 0, 18000);
        } catch (Exception $e) {
            error_log('IA Roteiros: falha ao buscar roteiros anteriores do cliente: ' . $e->getMessage());
            return "";
        }
    }

    private static function getContextoCliente(string $userId = '', string $clienteId = ''): string
    {
        if (!$userId || !$clienteId) return '';

        try {
            $db = Database::get();
            $stmt = $db->prepare("
                SELECT
                    c.nome,
                    c.perfil,
                    cfg.nicho,
                    cfg.publico_alvo,
                    cfg.palavras_usa,
                    cfg.palavras_evita,
                    cfg.frases_exemplo,
                    cfg.ganchos_fav
                FROM roteiros_clientes c
                LEFT JOIN roteiros_config_cliente cfg
                  ON cfg.cliente_id = c.id
                 AND cfg.user_id = c.user_id
                WHERE c.id = ?
                  AND c.user_id = ?
                LIMIT 1
            ");
            $stmt->execute([$clienteId, $userId]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cliente) return '';

            $texto = "### CLIENTE SELECIONADO (USAR SOMENTE ESTE CONTEXTO):\n";
            $texto .= "Cliente: " . trim((string)$cliente['nome']) . "\n";
            if (!empty($cliente['perfil'])) $texto .= "Perfil: " . trim((string)$cliente['perfil']) . "\n";
            if (!empty($cliente['nicho'])) $texto .= "Nicho: " . trim((string)$cliente['nicho']) . "\n";
            if (!empty($cliente['publico_alvo'])) $texto .= "Público-alvo: " . trim((string)$cliente['publico_alvo']) . "\n";
            if (!empty($cliente['palavras_usa'])) $texto .= "Palavras que usa: " . trim((string)$cliente['palavras_usa']) . "\n";
            if (!empty($cliente['palavras_evita'])) $texto .= "Palavras que evita: " . trim((string)$cliente['palavras_evita']) . "\n";
            if (!empty($cliente['frases_exemplo'])) $texto .= "Frases de exemplo: " . trim((string)$cliente['frases_exemplo']) . "\n";
            if (!empty($cliente['ganchos_fav'])) $texto .= "Ganchos favoritos: " . trim((string)$cliente['ganchos_fav']) . "\n";
            $texto .= "Não use informações, nichos, exemplos ou bases de outros clientes.\n";
            return $texto;
        } catch (Exception $e) {
            error_log('IA Roteiros: falha ao buscar contexto do cliente: ' . $e->getMessage());
            return '';
        }
    }

    private static function getAprendizadoCliente(string $userId = '', string $clienteId = ''): string
    {
        if (!$userId || !$clienteId) return '';

        try {
            $db = Database::get();
            $scoreSql = self::scoreSql();
            $partes = [];

            $stmtRoteiros = $db->prepare("
                SELECT titulo, gancho, quebra_crenca, desenvolvimento, conexao, fechamento, cta, status, score
                FROM roteiros
                WHERE user_id = ?
                  AND cliente_id = ?
                  AND (
                    LOWER(COALESCE(status, '')) IN ('aprovado', 'postado', 'gravado')
                    OR {$scoreSql} > 0
                    OR COALESCE(gancho, '') <> ''
                    OR COALESCE(quebra_crenca, '') <> ''
                    OR COALESCE(desenvolvimento, '') <> ''
                  )
                ORDER BY
                  CASE WHEN LOWER(COALESCE(status, '')) = 'aprovado' THEN 0 ELSE 1 END,
                  {$scoreSql} DESC,
                  created_at DESC
                LIMIT 9
            ");
            $stmtRoteiros->execute([$userId, $clienteId]);
            $roteiros = $stmtRoteiros->fetchAll(PDO::FETCH_ASSOC);
            foreach ($roteiros as $r) {
                $statusNormalizado = strtolower((string)($r['status'] ?? ''));
                $scoreNumerico = (float)($r['score'] ?? 0);
                $rotulo = (in_array($statusNormalizado, ['aprovado', 'postado', 'gravado'], true) || $scoreNumerico > 0)
                    ? 'ROTEIRO VALIDADO'
                    : 'ROTEIRO ANTERIOR DO CLIENTE';

                $partes[] =
                    "{$rotulo} ({$r['status']}, score {$r['score']}):\n" .
                    "Título: {$r['titulo']}\n" .
                    "Gancho: {$r['gancho']}\n" .
                    "Corpo: " . trim(($r['quebra_crenca'] ?? '') . " " . ($r['desenvolvimento'] ?? '') . " " . ($r['conexao'] ?? '')) . "\n" .
                    "Fechamento/CTA: " . trim(($r['fechamento'] ?? '') . " " . ($r['cta'] ?? ''));
            }

            try {
                $stmtHist = $db->prepare("
                    SELECT tipo, campo, conteudo, created_at
                    FROM roteiros_feedback_historico
                    WHERE user_id = ?
                      AND cliente_id = ?
                    ORDER BY created_at DESC
                    LIMIT 12
                ");
                $stmtHist->execute([$userId, $clienteId]);
                $historico = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
                foreach ($historico as $h) {
                    $campo = $h['campo'] ? " ({$h['campo']})" : '';
                    $partes[] = "SINAL DE APRENDIZADO {$h['tipo']}{$campo}: {$h['conteudo']}";
                }
            } catch (Exception $e) {}

            try {
                $stmtSug = $db->prepare("
                    SELECT campo, texto_original, texto_sugerido, status
                    FROM roteiros_sugestoes s
                    INNER JOIN roteiros r ON r.id = s.roteiro_id
                    WHERE r.user_id = ?
                      AND r.cliente_id = ?
                      AND s.status IN ('aceita', 'pendente')
                    ORDER BY s.created_at DESC
                    LIMIT 8
                ");
                $stmtSug->execute([$userId, $clienteId]);
                $sugestoes = $stmtSug->fetchAll(PDO::FETCH_ASSOC);
                foreach ($sugestoes as $s) {
                    $partes[] =
                        "SUGESTÃO DO CLIENTE ({$s['status']}, campo {$s['campo']}): trocar \"" .
                        mb_substr((string)$s['texto_original'], 0, 240) .
                        "\" por \"" .
                        mb_substr((string)$s['texto_sugerido'], 0, 300) .
                        "\"";
                }
            } catch (Exception $e) {}

            if (empty($partes)) return '';

            return "### APRENDIZADO EVOLUTIVO DO CLIENTE\nUse estes sinais para repetir padrões aprovados, evitar padrões recusados e incorporar ajustes manuais recorrentes.\n\n" .
                mb_substr(implode("\n\n---\n\n", $partes), 0, 12000);
        } catch (Exception $e) {
            error_log('IA Roteiros: falha ao buscar aprendizado do cliente: ' . $e->getMessage());
            return '';
        }
    }

    private static function salvarMemoria(string $novaMemoria, string $userId = ''): void
    {
        $db = Database::get();
        $db->exec("ALTER TABLE roteiros_memoria ADD COLUMN IF NOT EXISTS user_id VARCHAR(32)");
        if ($userId) {
            $db->prepare("DELETE FROM roteiros_memoria WHERE user_id = ?")->execute([$userId]);
            $db->prepare("INSERT INTO roteiros_memoria (conteudo, user_id) VALUES (?, ?)")->execute([$novaMemoria, $userId]);
        } else {
            $db->exec("DELETE FROM roteiros_memoria WHERE user_id IS NULL");
            $db->prepare("INSERT INTO roteiros_memoria (conteudo) VALUES (?)")->execute([$novaMemoria]);
        }
    }

    public static function consolidarMemoria(string $novoTexto, string $userId = ''): bool
    {
        $memoriaAtual = self::getBaseConhecimento($userId);

        $novaMemoria = self::chamarGroq([
            ['role' => 'system', 'content' => "Você é um Engenheiro de Conhecimento e Estrategista.
Sua tarefa é REUNIR e DESTILAR informações em uma única 'Memória Mestra'.
OBJETIVO: extrair apenas a essência estratégica, metodologias, tom de voz e diretrizes.
Elimine redundâncias, mescle o novo conteúdo com a memória atual de forma fluida.
REGRAS: Nunca use emojis. Seja direto. Priorize o conteúdo mais recente em contradições. Português do Brasil."],
            ['role' => 'user', 'content' => "### MEMÓRIA ATUAL:\n" . mb_substr($memoriaAtual, 0, 14000) . "\n\n### NOVO CONTEÚDO:\n" . mb_substr($novoTexto, 0, 10000) . "\n\nGere a nova Memória Mestra Consolidada:"]
        ], null, $userId, 'Consolidar Memória');

        if (strpos($novaMemoria, 'Erro') === 0) return false;

        try {
            self::salvarMemoria($novaMemoria, $userId);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function reconstruirMemoria(string $userId = '')
    {
        try {
            $db = Database::get();
            $db->exec("ALTER TABLE roteiros_memoria ADD COLUMN IF NOT EXISTS user_id VARCHAR(32)");

            if ($userId) {
                $stmt = $db->prepare("SELECT texto_extraido FROM roteiros_conhecimento WHERE ativo = TRUE AND user_id = ?");
                $stmt->execute([$userId]);
            } else {
                $stmt = $db->query("SELECT texto_extraido FROM roteiros_conhecimento WHERE ativo = TRUE");
            }
            $textos = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($textos)) {
                if ($userId) {
                    $db->prepare("DELETE FROM roteiros_memoria WHERE user_id = ?")->execute([$userId]);
                } else {
                    $db->exec("DELETE FROM roteiros_memoria WHERE user_id IS NULL");
                }
                return true;
            }

            $textosFiltrados = array_filter($textos, fn($t) => !empty(trim($t)));
            if (empty($textosFiltrados)) {
                if ($userId) $db->prepare("DELETE FROM roteiros_memoria WHERE user_id = ?")->execute([$userId]);
                return true;
            }

            $textoUnificado = mb_substr(
                implode("\n\n--- PRÓXIMA FONTE ---\n\n", $textosFiltrados),
                0, 24000
            );

            $novaMemoria = self::chamarGroq([
                ['role' => 'system', 'content' => "Você é um Engenheiro de Conhecimento Senior. Crie o 'Cérebro Digital' do usuário.
REGRAS CRÍTICAS:
1. NÃO ignore nenhuma fonte.
2. Organize por tópicos (Diretrizes, Tom de Voz, Gatilhos, Estruturas).
3. Seja denso e técnico. Nunca use emojis. Português do Brasil."],
                ['role' => 'user', 'content' => "Fontes carregadas:\n\n$textoUnificado\n\nConsolide TODO esse conhecimento em uma Memória Mestra única e organizada:"]
            ], null, $userId, 'Reconstruir Memória');

            if (strpos($novaMemoria, 'Erro') === 0) return $novaMemoria;

            self::salvarMemoria($novaMemoria, $userId);
            return true;

        } catch (Exception $e) {
            return "Erro Interno: " . $e->getMessage();
        }
    }

    // =========================================================================
    // GERAÇÃO DE ROTEIROS — Groq
    // =========================================================================

    public static function gerarRoteiro(string $briefing = '', string $userId = '', string $clienteId = ''): array
    {
        $conhecimento = self::getBaseConhecimento($userId, $clienteId);
        $exemplos     = self::getMelhoresRoteiros($userId, $clienteId);
        $contextoCliente = self::getContextoCliente($userId, $clienteId);
        $aprendizadoCliente = self::getAprendizadoCliente($userId, $clienteId);
        
        // Buscar Voz & Estilo do usuário
        $vozEstilo = "";
        if ($userId) {
            try {
                $db = Database::get();
                if ($clienteId !== '') {
                    $stmt = $db->prepare("SELECT persona, estilo, tom_voz FROM roteiros_config_cliente WHERE user_id = ? AND cliente_id = ? LIMIT 1");
                    $stmt->execute([$userId, $clienteId]);
                    $v = $stmt->fetch();
                } else {
                    $v = null;
                }

                if (!$v) {
                    $stmt = $db->prepare("SELECT persona, estilo, tom_voz FROM roteiros_config_usuario WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    $v = $stmt->fetch();
                }
                if ($v) {
                    $vozEstilo = "### IDENTIDADE DO AUTOR (OBRIGATÓRIO SEGUIR):\n";
                    if ($v['persona']) $vozEstilo .= "Persona: {$v['persona']}\n";
                    if ($v['estilo'])  $vozEstilo .= "Estilo de Escrita: {$v['estilo']}\n";
                    if ($v['tom_voz']) $vozEstilo .= "Tom de Voz: {$v['tom_voz']}\n";
                }
            } catch (Exception $e) {}
        }

        $mensagensRoteiro = [
            ['role' => 'system', 'content' => "Você é um Estrategista de Social Media e Roteirista de Elite.
Crie UM roteiro completo para Reels/TikTok, separado exatamente nos campos que a interface exibe.
O roteiro deve ter profundidade, nexo estrategico e densidade parecida com os melhores roteiros anteriores do cliente.
Nao gere metaforas genericas, historias infantis ou exemplos aleatorios se isso nao estiver na base do cliente.

$vozEstilo
$contextoCliente
$aprendizadoCliente
### BASE DE CONHECIMENTO:
" . ($conhecimento ?: ($clienteId !== '' ? "Nenhuma base específica cadastrada para este cliente. Use o perfil do cliente, o briefing e conhecimento geral de marketing. Não use informações de outros clientes." : "Nenhuma base cadastrada. Use seu conhecimento geral de marketing de alto nível.")) . "

### ROTEIROS ANTERIORES DO CLIENTE / EXEMPLOS DE SUCESSO:
" . ($exemplos ?: "Nenhum exemplo disponivel para este cliente. Use a base individual, o perfil do cliente e o briefing com profundidade.") . "

### CONTRATO DE SAÍDA OBRIGATÓRIO:
Responda somente com um objeto JSON válido, sem texto antes ou depois.
Use exatamente estas chaves: titulo, gancho, quebra_crenca, desenvolvimento, conexao, fechamento, cta, tags, intencao, tema.

### FORMATO ESPERADO:
{
  \"titulo\": \"título específico e curto\",
  \"gancho\": \"1 a 2 frases fortes para os 3 primeiros segundos\",
  \"quebra_crenca\": \"1 paragrafo denso quebrando uma crenca real do publico\",
  \"desenvolvimento\": \"1 a 2 paragrafos desenvolvendo a ideia central com raciocinio claro\",
  \"conexao\": \"1 paragrafo com conexao emocional ou analogia pertinente ao universo do cliente\",
  \"fechamento\": \"1 paragrafo de fechamento impactante conectando a ideia a promessa do conteudo\",
  \"cta\": \"1 a 2 frases objetivas de chamada para acao\",
  \"tags\": \"3 a 5 tags separadas por vírgula\",
  \"intencao\": \"intenção estratégica em caixa alta\",
  \"tema\": \"tema específico do roteiro\"
}

### REGRAS:
1. Não use Markdown.
2. Não use títulos como ###, numeração, timestamps, cenas, narração, bullets ou listas.
3. Não coloque o roteiro inteiro em um único campo.
4. Nao seja raso: quebra_crenca, desenvolvimento, conexao e fechamento precisam ter substancia. O roteiro completo deve parecer pronto para gravacao, nao um resumo.
5. NUNCA use emojis. Português do Brasil.
6. " . ($clienteId !== '' ? "Use o CONHECIMENTO GLOBAL apenas como metodologia/instrução de criação. Dados factuais sobre nicho, produto, mercado, oferta e público devem vir somente do CLIENTE SELECIONADO, da BASE INDIVIDUAL DO CLIENTE ou do briefing." : "Use a base geral disponível.") . "
7. " . ($clienteId !== '' ? "É proibido assumir que informações de outro cliente, nicho ou mercado presentes no conhecimento global pertencem ao cliente selecionado." : "Mantenha coerência com a base geral.") . "
8. Reaproveite padroes de estrutura, profundidade, tom e tipo de raciocinio dos roteiros anteriores do cliente, sem copiar literalmente.
9. " . ($vozEstilo ? "Siga rigorosamente a identidade do autor acima." : "Tom direto e focado em autoridade.")],
            ['role' => 'user', 'content' => $briefing
                ? "Gere o roteiro para este briefing: $briefing\n\nIMPORTANTE: responda somente com JSON válido começando com { e terminando com }. Não escreva explicações."
                : "Gere um roteiro inedito seguindo o padrao, profundidade e raciocinio dos roteiros anteriores do cliente.\n\nIMPORTANTE: responda somente com JSON valido comecando com { e terminando com }. Nao escreva explicacoes."]
        ];

        $respostaRaw = self::chamarOpenRouterRoteiro($mensagensRoteiro, $userId);

        $dados = self::extrairJson($respostaRaw);

        if (!$dados || !isset($dados['titulo'])) {
            throw new RuntimeException('A IA respondeu fora do formato esperado. Gere novamente o roteiro.');
        }

        $roteiro = self::normalizarRoteiroGerado($dados);
        $densidade = mb_strlen(trim(
            ($roteiro['quebra_crenca'] ?? '') . ' ' .
            ($roteiro['desenvolvimento'] ?? '') . ' ' .
            ($roteiro['conexao'] ?? '') . ' ' .
            ($roteiro['fechamento'] ?? '')
        ));

        if ($densidade < 700) {
            $mensagensRoteiro[] = ['role' => 'assistant', 'content' => $respostaRaw];
            $mensagensRoteiro[] = [
                'role' => 'user',
                'content' => 'A resposta anterior ficou curta, rasa ou generica. Gere novamente em JSON valido, com mais profundidade estrategica, usando os roteiros anteriores do cliente como padrao de densidade. Nao use exemplos aleatorios fora da base individual do cliente.'
            ];

            $respostaRaw = self::chamarOpenRouterRoteiro($mensagensRoteiro, $userId);
            $dados = self::extrairJson($respostaRaw);

            if (!$dados || !isset($dados['titulo'])) {
                throw new RuntimeException('A IA respondeu fora do formato esperado. Gere novamente o roteiro.');
            }

            $roteiro = self::normalizarRoteiroGerado($dados);
            $densidade = mb_strlen(trim(
                ($roteiro['quebra_crenca'] ?? '') . ' ' .
                ($roteiro['desenvolvimento'] ?? '') . ' ' .
                ($roteiro['conexao'] ?? '') . ' ' .
                ($roteiro['fechamento'] ?? '')
            ));

            if ($densidade < 700) {
                throw new RuntimeException('A IA gerou um roteiro curto demais mesmo apos revisao. Ajuste o briefing ou gere novamente.');
            }
        }

        return $roteiro;
    }
}
