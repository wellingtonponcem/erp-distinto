<?php
/**
 * Serviço de IA para Roteiros
 * Utiliza Groq para gerar roteiros baseados em conhecimento prévio (NotebookLM).
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

class IARoteiros
{

    private static function chamarGroq(array $mensagens)
    {
        $apiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
        if (!$apiKey)
            return "Erro: GROQ_API_KEY não configurada.";

        $payload = json_encode([
            'model' => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.3-70b-versatile',
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
        return $dados['choices'][0]['message']['content'] ?? "Erro ao processar resposta da IA.";
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
            $stmt = $db->query("SELECT texto_extraido FROM roteiros_conhecimento WHERE ativo = TRUE");
            $textos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($textos)) {
                $db->exec("DELETE FROM roteiros_memoria");
                return true;
            }

            // Unifica todos os textos para uma única destilação potente
            $textoUnificado = implode("\n\n---\n\n", $textos);
            
            $promptSistema = "Você é um Engenheiro de Conhecimento. Sua tarefa é ler diversos fragmentos de conhecimento e criar uma ÚNICA Memória Mestra Organizada.
Extraia a essência estratégica, metodologias e diretrizes. Remova repetições. O resultado deve ser denso e pronto para orientar uma IA roteirista.";

            $promptUsuario = "Abaixo estão as fontes de conhecimento:\n\n$textoUnificado\n\nGere a Memória Mestra Consolidada:";

            $novaMemoria = self::chamarGroq([
                ['role' => 'system', 'content' => $promptSistema],
                ['role' => 'user', 'content' => $promptUsuario]
            ]);

            if (strpos($novaMemoria, 'Erro') === 0) return false;

            $db->exec("DELETE FROM roteiros_memoria");
            $stmt = $db->prepare("INSERT INTO roteiros_memoria (conteudo) VALUES (?)");
            $stmt->execute([$novaMemoria]);
            
            return true;
        } catch (Exception $e) {
            return false;
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
