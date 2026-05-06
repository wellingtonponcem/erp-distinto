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
        $responsavel = $contexto['responsavel'] ?? '';
        $termoResponsavel = $contexto['termo_responsavel'] ?? 'o responsável';
        $detalhes = $contexto['detalhes'] ?? '';
        $servicos = $contexto['servicos'] ?? '';

        $prompts = [
            'marketing' => [
                'intro' => "Escreva um texto impactante sobre o 'Desafio do Crescimento' para $cliente. Enfatize para $termoResponsavel ($responsavel) que postar em redes sociais sem impulsionamento é ineficiente no cenário atual e que nossa estratégia integra conteúdo e tráfego.",
                'desafio' => "Explique para $termoResponsavel ($responsavel) da empresa $cliente que a Gestão de Redes Sociais agora é indissociável da Gestão de Tráfego. Destaque como usaremos $servicos para unir conteúdo estratégico e anúncios pagos, garantindo que a marca não apenas apareça, mas converta.",
            ],
            'casamento' => [
                'intro' => "Escreva uma introdução emocional e sofisticada para uma proposta de casamento para $responsavel. Foco em sonhos, exclusividade e na importância de cada detalhe para o grande dia.",
                'visao' => "Descreva a visão artística e o cuidado com os detalhes para o evento de $responsavel. Detalhes: $detalhes.",
            ],
            'filmmaker' => [
                'intro' => "Crie uma introdução cinemática para uma proposta de filme/vídeo para $cliente, endereçada a $responsavel. Use termos como 'storytelling', 'narrativa visual' e 'emoção em cada frame'.",
                'visao' => "Descreva a 'Visão Criativa' para o projeto de vídeo de $cliente e $responsavel. Explique como transformaremos o evento em uma obra de cinema. Contexto: $detalhes.",
            ],
            '15anos' => [
                'intro' => "Escreva uma introdução moderna e vibrante para uma proposta de festa de 15 anos para $cliente e $responsavel. Foco em experiência única e tendência.",
                'experiencia' => "Descreva como a festa de 15 anos de $responsavel será inesquecível e tecnológica. Detalhes: $detalhes.",
            ]
        ];

        $promptSistema = "Você é um redator sênior de uma agência de marketing e produção cinematográfica de alto padrão. Seu texto deve ser sofisticado, persuasivo e direto. Use Português do Brasil. NUNCA use emojis.";
        $promptUsuario = $prompts[$tipo][$secao] ?? "Escreva um texto sobre $secao para o cliente $cliente.";

        return self::chamarGroq([
            ['role' => 'system', 'content' => $promptSistema],
            ['role' => 'user', 'content' => $promptUsuario]
        ]);
    }

    public static function refinarTitulo(string $tituloOriginal, string $servicos) {
        $promptSistema = "Você é um estrategista de negócios sênior. Sua tarefa é transformar um título simples de proposta em um título altamente profissional, estratégico e impactante. Use termos como 'Planejamento Estratégico', 'Gestão de Performance', 'Inteligência', 'Crescimento'. Mantenha em CAIXA ALTA. Máximo 15 palavras. NUNCA use emojis.";
        $promptUsuario = "Refine este título de proposta comercial: '$tituloOriginal'. Contexto dos serviços inclusos: $servicos. Retorne apenas o novo título, sem explicações.";

        return self::chamarGroq([
            ['role' => 'system', 'content' => $promptSistema],
            ['role' => 'user', 'content' => $promptUsuario]
        ]);
    }
}
