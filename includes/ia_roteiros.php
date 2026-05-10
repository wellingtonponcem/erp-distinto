<?php
/**
 * Serviço de IA para Roteiros
 * Utiliza Groq para gerar roteiros baseados em conhecimento prévio (NotebookLM).
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

class IARoteiros
{

    private static function getConfig(string $chave) {
        try {
            $db = Database::get();
            $stmt = $db->prepare("SELECT valor FROM sistema_config WHERE chave = ?");
            $stmt->execute([$chave]);
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return null;
        }
    }

    public static function chamarGroq(array $mensagens, string $model = null)
    {
        $apiKey = self::getConfig('groq_api_key') ?: (defined('GROQ_API_KEY') ? GROQ_API_KEY : '');
        if (!$apiKey || strpos($apiKey, 'SUA_') === 0)
            return "Erro: GROQ_API_KEY não configurada no banco de dados ou env.php.";

        $payload = json_encode([
            'model' => $model ?: (defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.3-70b-versatile'),
            'messages' => $mensagens,
            'temperature' => 0.8,
            'max_tokens' => 2000
        ]);

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]
        ]);

        $resposta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            return "Erro na API da IA (Código $httpCode): " . $resposta;
        }

        $dados = json_decode($resposta, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return "Erro: Resposta da IA não é um JSON válido. Body: " . substr($resposta, 0, 200);
        }
        return $dados['choices'][0]['message']['content'] ?? "Erro: Resposta da IA não contém conteúdo. Body: " . json_encode($dados);
    }

    public static function chamarGemini(array $parts)
    {
        $apiKey = self::getConfig('gemini_api_key') ?: (defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '');
        if (!$apiKey || strpos($apiKey, 'SUA_') === 0)
            return "Erro: GEMINI_API_KEY não configurada no banco de dados ou env.php.";

        $payload = json_encode([
            'contents' => [
                ['parts' => $parts]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 4096
            ]
        ]);

        $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=$apiKey");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);

        $resposta = curl_exec($ch);
        $dados = json_decode($resposta, true);

        return $dados['candidates'][0]['content']['parts'][0]['text'] ?? "Erro ao processar Gemini: " . $resposta;
    }

    /**
     * Utiliza o modelo Vision (Gemini 1.5 Flash ou Groq) para extrair texto de uma imagem
     */
    public static function descreverImagem(string $base64Image, string $mimeType) {
        $prompt = "Extraia todo o texto estratégico, diretrizes, metodologias ou ideias contidas nesta imagem. 
Se for um print de rede social, identifique o tom de voz e os ganchos utilizados. 
Responda apenas com o texto extraído e organizado.";

        // Tenta usar Gemini primeiro (Melhor para visão)
        if (defined('GEMINI_API_KEY') && strpos(GEMINI_API_KEY, 'SUA_') === false) {
            return self::chamarGemini([
                ['text' => $prompt],
                ['inline_data' => ['mime_type' => $mimeType, 'data' => $base64Image]]
            ]);
        }

        // Fallback para Groq Vision
        $mensagens = [
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    [
                        'type' => 'image_url',
                        'image_url' => ['url' => 'data:' . $mimeType . ';base64,' . $base64Image]
                    ]
                ]
            ]
        ];

        return self::chamarGroq($mensagens, 'llama-3.2-90b-vision-preview');
    }

    /**
     * Obtém os roteiros com melhor score para servir de exemplo.
     */
    private static function getMelhoresRoteiros()
    {
        try {
            $db = Database::get();
            $stmt = $db->query("SELECT titulo, gancho, quebra_crenca, desenvolvimento, conexao, fechamento, cta FROM roteiros WHERE score > 0 ORDER BY score DESC LIMIT 3");
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

    /**
     * Obtém a memória consolidada ou a base de conhecimento bruta.
     */
    private static function getBaseConhecimento()
    {
        try {
            $db = Database::get();

            // Tenta pegar a memória consolidada primeiro
            $stmt = $db->query("SELECT conteudo FROM roteiros_memoria LIMIT 1");
            $memoria = $stmt->fetchColumn();

            if ($memoria)
                return $memoria;

            // Fallback: Base bruta se não houver memória
            $stmt = $db->query("SELECT texto_extraido FROM roteiros_conhecimento WHERE ativo = TRUE");
            $textos = $stmt->fetchAll(PDO::FETCH_COLUMN);

            return implode("\n\n---\n\n", $textos);
        } catch (Exception $e) {
            return "";
        }
    }

    /**
     * Pega o conhecimento bruto e consolida na memória mestra.
     */
    public static function consolidarMemoria(string $novoTexto)
    {
        $memoriaAtual = self::getBaseConhecimento();

        $promptSistema = "Você é um Engenheiro de Conhecimento e Estrategista.
Sua tarefa é REUNIR e DESTILAR informações em uma única 'Memória Mestra'.

### OBJETIVO:
1. Extrair APENAS a essência estratégica, metodologias, tom de voz e diretrizes.
2. Eliminar redundâncias e informações irrelevantes.
3. Mesclar o 'Novo Conteúdo' com a 'Memória Atual' de forma fluida.
4. O resultado final deve ser um guia denso, organizado e pronto para orientar uma IA a escrever roteiros perfeitos.

### REGRAS:
- Nunca use emojis.
- Seja direto e técnico.
- Se houver contradições, priorize o conteúdo mais recente.
- Mantenha o texto em Português do Brasil.";

        $promptUsuario = "### MEMÓRIA ATUAL (O que você já sabe):
$memoriaAtual

### NOVO CONTEÚDO (O que você acabou de aprender):
$novoTexto

Gere a nova Memória Mestra Consolidada:";

        $novaMemoria = self::chamarGroq([
            ['role' => 'system', 'content' => $promptSistema],
            ['role' => 'user', 'content' => $promptUsuario]
        ]);

        if (strpos($novaMemoria, 'Erro') === 0)
            return false;

        try {
            $db = Database::get();
            // Garante tabela de memória
            $db->exec("CREATE TABLE IF NOT EXISTS roteiros_memoria (id SERIAL PRIMARY KEY, conteudo TEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

            // Limpa e insere a nova memória (Sempre mantemos apenas 1 registro mestre)
            $db->exec("DELETE FROM roteiros_memoria");
            $stmt = $db->prepare("INSERT INTO roteiros_memoria (conteudo) VALUES (?)");
            $stmt->execute([$novaMemoria]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Reconstrói a memória mestra do zero usando todas as fontes ativas.
     * Útil quando uma fonte é removida.
     */
    public static function reconstruirMemoria() {
        try {
            $db = Database::get();

            // Garante tabela de memória antes de qualquer coisa
            $db->exec("CREATE TABLE IF NOT EXISTS roteiros_memoria (id SERIAL PRIMARY KEY, conteudo TEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

            $stmt = $db->query("SELECT texto_extraido FROM roteiros_conhecimento WHERE ativo = TRUE");
            $textos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($textos)) {
                $db->exec("DELETE FROM roteiros_memoria");
                return true;
            }

            // Filtra textos vazios para não confundir a IA
            $textosFiltrados = array_filter($textos, fn($t) => !empty(trim($t)));
            
            if (empty($textosFiltrados)) {
                $db->exec("DELETE FROM roteiros_memoria");
                return true;
            }

            $textoUnificado = implode("\n\n--- PRÓXIMA FONTE ---\n\n", $textosFiltrados);
            
            $promptSistema = "Você é um Engenheiro de Conhecimento Senior. Sua missão é criar o 'Cérebro Digital' do usuário.
Você receberá múltiplos fragmentos de fontes diferentes (PDFs, Notas, Sites).
### REGRAS CRÍTICAS:
1. NÃO ignore nenhuma fonte. Se houver 3 fontes, a memória deve refletir o conhecimento das 3.
2. Organize por tópicos (Diretrizes, Tom de Voz, Gatilhos, Estruturas).
3. Seja denso. Se uma fonte fala de 'Gatilhos Mentais' e outra de 'Copywriting', combine ambas sem perder detalhes técnicos.
4. Nunca use emojis. Responda apenas com a Memória Mestra Consolidada.";

            $promptUsuario = "Abaixo estão todas as fontes carregadas pelo usuário:\n\n$textoUnificado\n\nPor favor, consolide TODO esse conhecimento em uma Memória Mestra única e organizada:";

            $novaMemoria = self::chamarGroq([
                ['role' => 'system', 'content' => $promptSistema],
                ['role' => 'user', 'content' => $promptUsuario]
            ]);

            if (strpos($novaMemoria, 'Erro') === 0) return $novaMemoria;

            $db->exec("DELETE FROM roteiros_memoria");
            $stmt = $db->prepare("INSERT INTO roteiros_memoria (conteudo) VALUES (?)");
            $stmt->execute([$novaMemoria]);
            
            return true;
        } catch (Exception $e) {
            return "Erro Interno: " . $e->getMessage();
        }
    }

    /**
     * Gera um novo roteiro baseado em um tema e no conhecimento prévio.
     */
    public static function gerarRoteiro(string $briefing = '')
    {
        $conhecimento = self::getBaseConhecimento();
        $exemplos = self::getMelhoresRoteiros();

        $promptSistema = "Você é um Estrategista de Social Media e Roteirista de Elite.
Sua missão é criar roteiros de alto impacto baseados no contexto abaixo.

### BASE DE CONHECIMENTO (DIRETRIZES):
" . ($conhecimento ?: "Nenhuma base de conhecimento cadastrada ainda. Use seu conhecimento geral de marketing de alto nível.") . "

### EXEMPLOS DE SUCESSO (ESTILO DO USUÁRIO):
" . ($exemplos ?: "Nenhum exemplo disponível. Crie algo inovador.") . "

### REGRAS CRÍTICAS:
1. Responda APENAS em formato JSON válido.
2. Campos obrigatórios no JSON: 
   - 'titulo' (Atraente e curto)
   - 'gancho' (3 primeiros segundos)
   - 'quebra_crenca' (O que as pessoas pensam vs Realidade)
   - 'desenvolvimento' (O corpo do vídeo)
   - 'conexao' (Toque emocional/vulnerabilidade)
   - 'fechamento' (Resumo impactante)
   - 'cta' (Chamada para ação direta)
3. NUNCA use emojis.
4. Use Português do Brasil.
5. Tom direto, provocativo e focado em autoridade.";

        $promptUsuario = "Gere um novo roteiro completo.
" . ($briefing ? "Briefing/Instruções: $briefing" : "Como não foi fornecido um briefing, analise os exemplos de sucesso e crie um roteiro inédito que siga o mesmo padrão de qualidade e estilo.");

        $respostaRaw = self::chamarGroq([
            ['role' => 'system', 'content' => $promptSistema],
            ['role' => 'user', 'content' => $promptUsuario]
        ]);

        // Limpar possíveis Markdown fences
        $json = preg_replace('/```json\n?|\n?```/', '', $respostaRaw);
        $dados = json_decode(trim($json), true);

        if (!$dados || !isset($dados['titulo'])) {
            // Fallback se o JSON falhar
            return [
                'titulo' => 'Roteiro Gerado automaticamente',
                'gancho' => 'Gancho não gerado corretamente.',
                'quebra_crenca' => $respostaRaw, // Coloca a resposta bruta aqui para não perder nada
                'desenvolvimento' => '',
                'conexao' => '',
                'fechamento' => '',
                'cta' => ''
            ];
        }

        return $dados;
    }
}
