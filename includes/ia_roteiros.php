<?php
/**
 * Serviço de IA para Roteiros
 * Utiliza Groq para gerar roteiros baseados em conhecimento prévio (NotebookLM).
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

class IARoteiros {
    
    private static function chamarGroq(array $mensagens) {
        $apiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
        if (!$apiKey) return "Erro: GROQ_API_KEY não configurada.";

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
     * Obtém o conteúdo de toda a base de conhecimento ativa.
     */
    private static function getBaseConhecimento() {
        try {
            $db = Database::get();
            $stmt = $db->query("SELECT texto_extraido FROM roteiros_conhecimento WHERE ativo = TRUE");
            $textos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            return implode("\n\n---\n\n", $textos);
        } catch (Exception $e) {
            return "";
        }
    }

    /**
     * Gera um novo roteiro baseado em um tema e no conhecimento prévio.
     */
    public static function gerarRoteiro(string $tema, string $briefing = '') {
        $conhecimento = self::getBaseConhecimento();
        
        $promptSistema = "Você é um Estrategista de Social Media e Roteirista de Elite, especializado em conteúdo de alto impacto para Instagram.
Sua missão é criar roteiros baseados na METODOLOGIA do usuário, fornecida no CONTEXTO abaixo.

### CONTEXTO DE CONHECIMENTO (DIRETRIZES DO USUÁRIO):
$conhecimento

### INSTRUÇÕES DE FORMATO:
1. Use um tom direto, provocativo e estratégico.
2. Estrutura obrigatória:
   - TÍTULO ESTRATÉGICO
   - GANCHO (3 primeiros segundos)
   - DESENVOLVIMENTO (com quebra de crença)
   - FECHAMENTO IMPACTANTE
   - CTA (Chamada para ação)
3. NUNCA use emojis.
4. Use Português do Brasil.
5. Foque em autoridade e conversão.";

        $promptUsuario = "Gere um novo roteiro para o Instagram.
Tema: $tema
Detalhes adicionais/Briefing: $briefing";

        return self::chamarGroq([
            ['role' => 'system', 'content' => $promptSistema],
            ['role' => 'user', 'content' => $promptUsuario]
        ]);
    }
}
