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

    public static function gerarMensagemWhatsApp(string $nomeNoivo, string $nomeNoiva, string $nomeCasal): string {
        $primeiro = $nomeNoivo ? explode(' ', trim($nomeNoivo))[0] : '';
        $primeiraNoiva = $nomeNoiva ? explode(' ', trim($nomeNoiva))[0] : '';

        $promptSistema = "Você é o casal que está visualizando uma proposta de fotografia e vídeo de casamento da Distinto Wedding. Escreva uma mensagem de WhatsApp que o casal enviaria para o estúdio após visualizar a proposta. A mensagem deve soar natural, humana, calorosa — como alguém que já teve contato com o estúdio e quer conversar mais. Pode ser uma curiosidade, uma dúvida, ou só um interesse genuíno. NUNCA seja genérico ou formal. NUNCA use emojis. Máximo de 2 frases curtas. Escreva na primeira pessoa do plural (nós, nosso, nossa). Use português do Brasil coloquial mas elegante.";

        $ctx = $primeiro && $primeiraNoiva ? "O casal se chama {$primeiro} e {$primeiraNoiva}." : "O casal é {$nomeCasal}.";

        $promptUsuario = "{$ctx} Escreva a mensagem de WhatsApp que eles enviariam para a Distinto Wedding após ver a proposta. Lembre-se: eles já tiveram algum contato antes, então não é uma primeira abordagem fria. Retorne apenas o texto da mensagem, sem aspas, sem explicações.";

        $msg = self::chamarGroq([
            ['role' => 'system', 'content' => $promptSistema],
            ['role' => 'user', 'content' => $promptUsuario]
        ]);

        // Fallback se a API falhar
        if (str_starts_with($msg, 'Erro')) {
            $saudacao = $primeiro ? "Oi! Sou {$primeiro}" . ($primeiraNoiva ? " e {$primeiraNoiva}" : '') : "Oi";
            return "{$saudacao}, acabamos de ver a proposta da Distinto e queríamos conversar sobre alguns detalhes.";
        }

        return $msg;
    }

    public static function recomendarProximoPasso(array $proposta, array $historico = []): string {
        $cliente = $proposta['cliente_nome'] ?? '';
        if (empty($cliente) && !empty($proposta['nome_noivo']) && !empty($proposta['nome_noiva'])) {
            $cliente = trim($proposta['nome_noivo'] . ' & ' . $proposta['nome_noiva']);
        }
        $tipo = $proposta['tipo'] ?? 'proposta';
        $status = $proposta['status'] ?? '';
        $responsavel = $proposta['responsavel'] ?? '';
        $titulo = $proposta['titulo'] ?? '';
        $servicos = '';

        $dadosJson = $proposta['dados_json'] ?? [];
        if (is_string($dadosJson)) {
            $dadosJson = json_decode($dadosJson, true) ?: [];
        }

        if (!empty($dadosJson['servicos']) && is_array($dadosJson['servicos'])) {
            $servicos = implode(', ', array_map(fn($s) => trim($s['nome'] ?? $s), $dadosJson['servicos']));
        }

        if (empty($servicos) && !empty($proposta['servicos']) && is_array($proposta['servicos'])) {
            $servicos = implode(', ', array_map(fn($s) => trim($s['nome'] ?? $s), $proposta['servicos']));
        }

        $ultimas = [];
        foreach (array_slice($historico, 0, 3) as $item) {
            $tipoEvento = trim($item['tipo'] ?? '');
            $conteudo = trim($item['conteudo'] ?? '');
            if (!$conteudo) continue;
            $ultimas[] = ($tipoEvento ? ucfirst($tipoEvento) . ': ' : '') . $conteudo;
        }
        $ultimasTexto = $ultimas ? implode(' | ', $ultimas) : 'Nenhuma interação recente registrada.';

        $promptSistema = "Você é um assistente CRM inteligente para vendas e relacionamento. Analise os dados da proposta e o histórico recente para sugerir o próximo passo mais eficaz. Responda com uma recomendação prática, curta e diretamente acionável. Use Português do Brasil. Sem emojis.";
        $promptUsuario = "Proposta: {$titulo}. Cliente: {$cliente}. Tipo: {$tipo}. Status: {$status}. Contato principal: {$responsavel}. Serviços: {$servicos}. Histórico recente: {$ultimasTexto}. Sugira apenas o próximo passo comercial mais relevante para avançar essa negociação. Seja objetivo e claro.";

        $recomendacao = self::chamarGroq([
            ['role' => 'system', 'content' => $promptSistema],
            ['role' => 'user', 'content' => $promptUsuario]
        ]);

        if (str_starts_with($recomendacao, 'Erro')) {
            return 'Faça um follow-up personalizado com o cliente, reforçando prazos e próximos passos e buscando validar detalhes pendentes.';
        }

        return trim($recomendacao);
    }

    public static function melhorarObjetivo(string $objetivoOriginal, array $contexto) {
        $cliente = $contexto['cliente'] ?? 'o cliente';
        $servicos = $contexto['servicos'] ?? '';

        $promptSistema = "Você é um estrategista de negócios de alto padrão. Sua tarefa é melhorar o texto de objetivos informado, tornando-o narrativo, sofisticado e persuasivo para uma proposta comercial. Use Português do Brasil. NUNCA use emojis. O texto deve ser direto, focado em resultados, autoridade e clareza. Máximo de 1020 caracteres.";
        
        $promptUsuario = "Melhore estrategicamente o seguinte texto de objetivo para o projeto de $cliente. \n\nObjetivo original: '$objetivoOriginal'. \n\nServiços envolvidos: $servicos. \n\nInstruções: \n1. Mantenha a essência do texto original, mas refine a linguagem para ser mais profissional e estratégica. \n2. Destaque o valor da marca e os resultados esperados. \n3. Use uma linguagem que transmita exclusividade. \n4. Estruture o texto em parágrafos curtos e limpos.";

        return self::chamarGroq([
            ['role' => 'system', 'content' => $promptSistema],
            ['role' => 'user', 'content' => $promptUsuario]
        ]);
    }
}
