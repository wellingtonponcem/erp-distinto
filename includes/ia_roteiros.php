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
            'temperature' => 0.8,
            'max_tokens'  => 2200
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
            'temperature'    => 0.8,
            'max_tokens'     => 2200,
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
            if ($userId) {
                $stmt = $db->prepare("SELECT conteudo FROM roteiros_memoria WHERE user_id = ? LIMIT 1");
                $stmt->execute([$userId]);
            } else {
                $stmt = $db->query("SELECT conteudo FROM roteiros_memoria LIMIT 1");
            }
            $memoria = $stmt->fetchColumn();

            if ($memoria && $clienteId === '') return $memoria;

            $base = $memoria ?: '';

            if ($userId) {
                if ($clienteId !== '') {
                    $stmt = $db->prepare("
                        SELECT texto_extraido
                        FROM roteiros_conhecimento
                        WHERE ativo = TRUE
                          AND user_id = ?
                          AND (cliente_id IS NULL OR cliente_id = '' OR cliente_id = ?)
                    ");
                    $stmt->execute([$userId, $clienteId]);
                } else {
                    $stmt = $db->prepare("SELECT texto_extraido FROM roteiros_conhecimento WHERE ativo = TRUE AND user_id = ?");
                    $stmt->execute([$userId]);
                }
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

    private static function getMelhoresRoteiros(string $userId = ''): string
    {
        try {
            $db = Database::get();
            if ($userId) {
                $stmt = $db->prepare("SELECT titulo, gancho, quebra_crenca, desenvolvimento, conexao, fechamento, cta FROM roteiros WHERE score > 0 AND user_id = ? ORDER BY score DESC LIMIT 3");
                $stmt->execute([$userId]);
            } else {
                $stmt = $db->query("SELECT titulo, gancho, quebra_crenca, desenvolvimento, conexao, fechamento, cta FROM roteiros WHERE score > 0 ORDER BY score DESC LIMIT 3");
            }
            $roteiros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $texto = "";
            foreach ($roteiros as $r) {
                $texto .= "EXEMPLO DE ALTO SCORE:\n";
                $texto .= "Título: {$r['titulo']}\nGancho: {$r['gancho']}\nConteúdo: {$r['quebra_crenca']} {$r['desenvolvimento']} {$r['conexao']}\nCTA: {$r['cta']}\n\n";
            }
            return $texto;
        } catch (Exception $e) {
            return "";
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
        $exemplos     = self::getMelhoresRoteiros($userId);
        
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

        $respostaRaw = self::chamarOpenRouterRoteiro([
            ['role' => 'system', 'content' => "Você é um Estrategista de Social Media e Roteirista de Elite.
Crie roteiros de alto impacto baseados no contexto abaixo.

$vozEstilo
### BASE DE CONHECIMENTO:
" . ($conhecimento ?: "Nenhuma base cadastrada. Use seu conhecimento geral de marketing de alto nível.") . "

### EXEMPLOS DE SUCESSO:
" . ($exemplos ?: "Nenhum exemplo disponível. Crie algo inovador.") . "

### REGRAS:
1. Responda APENAS em JSON válido.
2. Campos obrigatórios: titulo, gancho, quebra_crenca, desenvolvimento, conexao, fechamento, cta.
3. NUNCA use emojis. Português do Brasil. " . ($vozEstilo ? "Siga rigorosamente a identidade do autor acima." : "Tom direto e focado em autoridade.")],
            ['role' => 'user', 'content' => $briefing
                ? "Gere um roteiro completo. Briefing: $briefing"
                : "Gere um roteiro inédito seguindo o padrão dos exemplos de sucesso."]
        ], $userId);

        $json  = preg_replace('/```json\n?|\n?```/', '', $respostaRaw);
        $dados = json_decode(trim($json), true);

        if (!$dados || !isset($dados['titulo'])) {
            return [
                'titulo'        => 'Roteiro Gerado',
                'gancho'        => 'Gancho não gerado corretamente.',
                'quebra_crenca' => $respostaRaw,
                'desenvolvimento' => '',
                'conexao'       => '',
                'fechamento'    => '',
                'cta'           => ''
            ];
        }

        return $dados;
    }
}
