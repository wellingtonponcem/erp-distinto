<?php
/**
 * Serviço de IA para Propostas e Contratos
 * - Utiliza o Google Gemini (gemini-2.5-flash) para geração de anexos e otimização jurídica de cláusulas
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

class IAPropostas
{
    private static function getGeminiKey(): ?string
    {
        try {
            $db = Database::get();
            $stmt = $db->query("SELECT gemini_api_key FROM configuracao_empresa WHERE id = 'principal' LIMIT 1");
            $key = $stmt->fetchColumn();
        } catch (Exception $e) {
            $key = null;
        }
        if (!$key) $key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
        if (!$key || strpos($key, 'SUA_') === 0) return null;
        return $key;
    }

    public static function chamarGemini(array $parts, string $model = 'gemini-2.5-flash'): string
    {
        $apiKey = self::getGeminiKey();
        if (!$apiKey) return "Erro: Chave da API do Gemini não configurada nas configurações da empresa.";

        $payload = json_encode([
            'contents' => [['parts' => $parts]],
            'generationConfig' => [
                'temperature'    => 0.3,
                'maxOutputTokens' => 8192
            ]
        ]);

        $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 90,
        ]);

        $resposta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $err = json_decode($resposta, true);
            $msg = $err['error']['message'] ?? $resposta;
            return "Erro Gemini (HTTP $httpCode): $msg";
        }

        $dados = json_decode($resposta, true);
        return $dados['candidates'][0]['content']['parts'][0]['text'] ?? "Erro: resposta inesperada da API.";
    }

    /**
     * Otimiza a redação de uma cláusula contratual para torná-la mais segura e profissional.
     */
    public static function otimizarClausula(string $texto, string $tipoContrato = ''): string
    {
        $prompt = "Você é um advogado especialista em direito civil e contratos de prestação de serviços comerciais para agências de marketing, produções audiovisuais e eventos sociais de luxo (casamentos e festas de 15 anos).
Sua tarefa é revisar e aprimorar a cláusula contratual enviada abaixo para torná-la mais clara, profissional e juridicamente segura para ambas as partes.
Mantenha rigorosamente o sentido original, mas melhore a redação, elimine ambiguidades e garanta conformidade com as melhores práticas de redação de contratos comerciais.
Tipo de Contrato: " . ($tipoContrato ?: "Prestação de Serviços") . "

Texto da Cláusula:
\"\"\"
$texto
\"\"\"

Retorne APENAS o texto revisado e otimizado da cláusula, sem introduções, sem explicações, sem comentários e sem aspas.";

        $resposta = self::chamarGemini([['text' => $prompt]]);
        return trim(preg_replace('/^["\']|["\']$/', '', trim($resposta)));
    }

    /**
     * Gera o Anexo I (Descrição dos Serviços) formatado em HTML com base nos dados da proposta.
     */
    public static function gerarAnexoI(array $proposta): string
    {
        $propostaJson = json_encode($proposta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        $prompt = "Você é um analista de operações e especialista em contratos de prestação de serviços de luxo (casamentos, 15 anos) e produções de marketing/filmmaker.
Sua tarefa é gerar o 'Anexo I - Descrição Detalhada dos Serviços' com base nos dados da proposta comercial fornecidos abaixo em formato JSON.

O anexo deve detalhar:
1. Escopo dos Serviços (serviços selecionados, pacotes, itens).
2. Entregáveis e Prazos (arquivos finais, quantidade de fotos/vídeos, álbuns físicos, relatórios, tempo estimado de entrega pós-evento).
3. Limitações de Escopo (o que NÃO está incluso, como alimentação adicional, horas extras, taxas de deslocamento/hospedagem que fiquem a cargo do contratante).

Regras de Formatação:
- Responda APENAS em HTML estrutural limpo (utilizando somente <h4>, <p>, <ul>, <li>, <strong>, sem CSS inline nem classes complexas).
- O texto final deve ser perfeitamente encaixado em um contrato oficial.
- Seja formal, detalhado e operacionalmente preciso.
- Não inclua blocos markdown do tipo ```html ou ```. Retorne apenas o código HTML direto.

Dados da Proposta:
$propostaJson";

        $resposta = self::chamarGemini([['text' => $prompt]]);
        
        // Remove delimitadores markdown se a IA insistir em colocá-los
        $clean = preg_replace('/^```(?:html)?\s*/i', '', trim($resposta));
        $clean = preg_replace('/\s*```$/', '', $clean);
        
        return $clean;
    }
}
