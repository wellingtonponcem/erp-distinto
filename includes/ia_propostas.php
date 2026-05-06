<?php
/**
 * Serviço de IA para Propostas
 * Utiliza Groq para gerar cópias persuasivas e personalizadas.
 */

require_once __DIR__ . '/../config/env.php';

class IAPropostas {
    
    private static function chamarGroq(array $mensagens) {
        $apiKey = GROQ_API_KEY;
        if (!$apiKey) return "Erro: GROQ_API_KEY não configurada.";

        $payload = json_encode([
            'model' => GROQ_MODEL,
            'messages' => $mensagens,
            'temperature' => 0.7,
            'max_tokens' => 1500
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
        curl_close($ch);

        if ($httpCode !== 200) {
            return "Erro na API da IA (Código $httpCode).";
        }

        $dados = json_decode($resposta, true);
        return $dados['choices'][0]['message']['content'] ?? "Erro ao processar resposta da IA.";
    }

    public static function gerarTextoSecao(string $tipo, string $secao, array $contexto) {
        $cliente = $contexto['cliente'] ?? 'Cliente';
        $detalhes = $contexto['detalhes'] ?? '';
        $servicos = $contexto['servicos'] ?? '';

        $prompts = [
            'marketing' => [
                'intro' => "Escreva um texto curto e impactante (máx 300 caracteres) sobre o 'Desafio do Crescimento' para $cliente. Fale sobre como o mercado atual exige velocidade e precisão.",
                'desafio' => "Descreva em 2 parágrafos os gargalos comuns que impedem empresas como $cliente de escalarem, focando em como a Distinto resolve isso com $servicos.",
            ],
            'casamento' => [
                'intro' => "Escreva uma introdução emocional e sofisticada para uma proposta de casamento para o casal $cliente. Foco em sonhos e exclusividade.",
                'visao' => "Descreva a visão artística e o cuidado com os detalhes (como se fosse um filme) para o casamento de $cliente. Detalhes: $detalhes.",
            ],
            'filmmaker' => [
                'intro' => "Crie uma introdução cinemática para uma proposta de filme/vídeo para $cliente. Use termos como 'storytelling', 'narrativa visual' e 'emoção em cada frame'.",
                'visao' => "Descreva a 'Visão Criativa' para o projeto de vídeo de $cliente. Explique como transformaremos o evento em uma obra de cinema. Contexto: $detalhes.",
            ],
            '15anos' => [
                'intro' => "Escreva uma introdução moderna e vibrante para uma proposta de festa de 15 anos para $cliente. Foco em experiência única e tendência.",
                'experiencia' => "Descreva como a festa de 15 anos de $cliente será inesquecível e tecnológica. Detalhes: $detalhes.",
            ]
        ];

        $promptSistema = "Você é um redator sênior de uma agência de marketing e produção cinematográfica de alto padrão. Seu texto deve ser sofisticado, persuasivo e direto. Use Português do Brasil. NUNCA use emojis.";
        $promptUsuario = $prompts[$tipo][$secao] ?? "Escreva um texto sobre $secao para o cliente $cliente.";

        return self::chamarGroq([
            ['role' => 'system', 'content' => $promptSistema],
            ['role' => 'user', 'content' => $promptUsuario]
        ]);
    }
}
