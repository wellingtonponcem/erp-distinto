<?php
/**
 * ServiÃ§o de IA para Propostas e Contratos
 * - Utiliza o Google Gemini (gemini-2.5-flash) para geraÃ§Ã£o de anexos e otimizaÃ§Ã£o jurÃ­dica de clÃ¡usulas
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
        if (!$key) $key = getenv('GEMINI_API_KEY') ?: '';
        if (!$key || strpos($key, 'SUA_') === 0) return null;
        return $key;
    }

    public static function chamarGemini(array $parts, string $model = 'gemini-2.5-flash'): string
    {
        $apiKey = self::getGeminiKey();
        if (!$apiKey) return "Erro: Chave da API do Gemini não configurada nas configurações da empresa.";

        $modelsToTry = [$model];
        // Fallback models if primary is overloaded (503)
        $primary = $model;
        if ($primary !== 'gemini-2.0-flash') $modelsToTry[] = 'gemini-2.0-flash';
        if ($primary !== 'gemini-1.5-flash') $modelsToTry[] = 'gemini-1.5-flash';

        $lastError = '';

        foreach ($modelsToTry as $attemptModel) {
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                $payload = json_encode([
                    'contents' => [['parts' => $parts]],
                    'generationConfig' => [
                        'temperature'    => 0.3,
                        'maxOutputTokens' => 8192
                    ]
                ]);

                $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$attemptModel}:generateContent?key={$apiKey}");
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

                if ($httpCode === 200) {
                    $dados = json_decode($resposta, true);
                    return $dados['candidates'][0]['content']['parts'][0]['text'] ?? "Erro: resposta inesperada da API.";
                }

                $err = json_decode($resposta, true);
                $msg = $err['error']['message'] ?? $resposta;
                $lastError = "Erro Gemini (HTTP $httpCode): $msg";

                // If it's a 429 (quota) or 403 (auth), don't retry or try other models
                if (in_array($httpCode, [429, 403, 401, 400])) {
                    return $lastError;
                }

                // 503 (overloaded) — wait and retry with next model
                if ($httpCode === 503) {
                    usleep(500_000); // 500ms before retry with fallback model
                    break; // Try next model
                }

                // Other server errors — small wait then retry once
                usleep(300_000);
            }
        }

        return $lastError ?: "Erro: não foi possível contactar a API do Gemini após múltiplas tentativas.";
    }

    /**
     * Otimiza a redaÃ§Ã£o de uma clÃ¡usula contratual para tornÃ¡-la mais segura e profissional.
     */
    public static function otimizarClausula(string $texto, string $tipoContrato = ''): string
    {
        $prompt = "VocÃª Ã© um advogado especialista em direito civil e contratos de prestaÃ§Ã£o de serviÃ§os comerciais para agÃªncias de marketing, produÃ§Ãµes audiovisuais e eventos sociais de luxo (casamentos e festas de 15 anos).
Sua tarefa Ã© revisar e aprimorar a clÃ¡usula contratual enviada abaixo para tornÃ¡-la mais clara, profissional e juridicamente segura para ambas as partes.
Mantenha rigorosamente o sentido original, mas melhore a redaÃ§Ã£o, elimine ambiguidades e garanta conformidade com as melhores prÃ¡ticas de redaÃ§Ã£o de contratos comerciais.
Tipo de Contrato: " . ($tipoContrato ?: "PrestaÃ§Ã£o de ServiÃ§os") . "

Texto da ClÃ¡usula:
\"\"\"
$texto
\"\"\"

Retorne APENAS o texto revisado e otimizado da clÃ¡usula, sem introduÃ§Ãµes, sem explicaÃ§Ãµes, sem comentÃ¡rios e sem aspas.";

        $resposta = self::chamarGemini([['text' => $prompt]]);
        return trim(preg_replace('/^["\']|["\']$/', '', trim($resposta)));
    }

    /**
     * Gera o Anexo I (DescriÃ§Ã£o dos ServiÃ§os) formatado em HTML com base nos dados da proposta.
     */
    public static function gerarAnexoI(array $proposta): string
    {
        $propostaJson = json_encode($proposta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt = "VocÃª Ã© um analista de operaÃ§Ãµes e especialista em contratos de prestaÃ§Ã£o de serviÃ§os de luxo (casamentos, 15 anos) e produÃ§Ãµes de marketing/filmmaker.
Sua tarefa Ã© gerar o 'Anexo I - DescriÃ§Ã£o Detalhada dos ServiÃ§os' com base nos dados da proposta comercial fornecidos abaixo em formato JSON.

O anexo deve detalhar:
1. Escopo dos ServiÃ§os (serviÃ§os selecionados, pacotes, itens).
2. EntregÃ¡veis e Prazos (arquivos finais, quantidade de fotos/vÃ­deos, Ã¡lbuns fÃ­sicos, relatÃ³rios, tempo estimado de entrega pÃ³s-evento).
3. LimitaÃ§Ãµes de Escopo (o que NÃƒO estÃ¡ incluso, como alimentaÃ§Ã£o adicional, horas extras, taxas de deslocamento/hospedagem que fiquem a cargo do contratante).

Regras de FormataÃ§Ã£o:
- Responda APENAS em HTML estrutural limpo (utilizando somente <h4>, <p>, <ul>, <li>, <strong>, sem CSS inline nem classes complexas).
- O texto final deve ser perfeitamente encaixado em um contrato oficial.
- Seja formal, detalhado e operacionalmente preciso.
- NÃ£o inclua blocos markdown do tipo ```html ou ```. Retorne apenas o cÃ³digo HTML direto.

Dados da Proposta:
$propostaJson";

        $resposta = self::chamarGemini([['text' => $prompt]]);

        // Remove delimitadores markdown se a IA insistir em colocÃ¡-los
        $clean = preg_replace('/^```(?:html)?\s*/i', '', trim($resposta));
        $clean = preg_replace('/\s*```$/', '', $clean);

        return $clean;
    }

    /**
     * Gera textos curtos para secoes comerciais da proposta, com fallback local.
     */
    public static function gerarTextoSecao(string $tipo, string $secao, array $contexto = []): string
    {
        $tipo = trim($tipo);
        $secao = trim($secao);
        $briefing = trim((string)($contexto['briefing'] ?? $contexto['objetivo'] ?? ''));
        $cliente = trim((string)($contexto['cliente_nome'] ?? $contexto['cliente'] ?? 'cliente'));

        $fallback = self::textoSecaoFallback($tipo, $secao, $cliente, $briefing);

        try {
            if (!self::getGeminiKey()) {
                return $fallback;
            }

            $contextoJson = json_encode($contexto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $prompt = "Voce e um estrategista comercial da Distinto.
Escreva uma secao curta, clara e humana para uma proposta do tipo '{$tipo}'.
Secao solicitada: '{$secao}'.
O texto deve ser em portugues do Brasil, facil de entender, sem jargoes e sem markdown.
Use no maximo 2 paragrafos.

Contexto:
{$contextoJson}";

            $resposta = trim(self::chamarGemini([['text' => $prompt]]));
            if ($resposta === '' || str_starts_with($resposta, 'Erro')) {
                return $fallback;
            }
            return $resposta;
        } catch (Exception $e) {
            return $fallback;
        }
    }

    /**
     * Melhora o objetivo informado pelo admin sem depender obrigatoriamente da IA.
     */
    public static function melhorarObjetivo(string $objetivo, array $contexto = []): string
    {
        $objetivo = trim($objetivo);
        if ($objetivo === '') {
            return self::gerarTextoSecao((string)($contexto['tipo'] ?? 'marketing'), 'objetivo', $contexto);
        }

        try {
            if (!self::getGeminiKey()) {
                return $objetivo;
            }

            $prompt = "Reescreva o objetivo abaixo para uma proposta comercial da Distinto.
Use portugues simples, direto e profissional. Nao invente dados.
Retorne apenas o texto final, sem explicacoes.

Objetivo:
{$objetivo}";

            $resposta = trim(self::chamarGemini([['text' => $prompt]]));
            if ($resposta === '' || str_starts_with($resposta, 'Erro')) {
                return $objetivo;
            }
            return $resposta;
        } catch (Exception $e) {
            return $objetivo;
        }
    }

    /**
     * Sugere o proximo passo operacional para o administrador.
     */
    public static function recomendarProximoPasso(array $proposta, array $historico = []): string
    {
        $tipo = (string)($proposta['tipo'] ?? '');
        $status = (string)($proposta['status'] ?? '');
        $dados = json_decode((string)($proposta['dados_json'] ?? ''), true) ?: [];

        if ($tipo === 'casamento') {
            $plano = $dados['cliente_escolha']['plano_id'] ?? $dados['pacote_dado_andamento'] ?? '';
            $valor = (float)($dados['cliente_escolha']['valor_total'] ?? $proposta['valor_total'] ?? 0);
            if ($status === 'aceita' && $plano && $valor > 0) {
                return 'A proposta esta pronta para virar contrato. Confira CPF, e-mail de assinatura e locais do casamento antes de enviar.';
            }
            if (!$plano) {
                return 'Defina o plano escolhido pelo casal e os opcionais antes de gerar o contrato.';
            }
            if ($valor <= 0) {
                return 'Confira o valor final do fechamento antes de gerar o contrato.';
            }
            return 'Revise as condicoes de pagamento e gere o contrato quando os dados de assinatura estiverem completos.';
        }

        if ($status === 'rascunho') {
            return 'Revise os dados principais, visualize a proposta e envie para o cliente.';
        }
        if ($status === 'pendente') {
            return 'Faca o acompanhamento com o cliente e registre o retorno no historico.';
        }
        if ($status === 'aceita') {
            return 'Gere o contrato e confira os dados financeiros antes da assinatura.';
        }
        if ($status === 'recusada') {
            return 'Registre o motivo da recusa para melhorar as proximas propostas.';
        }

        return 'Revise a proposta e escolha o proximo passo no funil comercial.';
    }

    private static function textoSecaoFallback(string $tipo, string $secao, string $cliente, string $briefing): string
    {
        if ($tipo === 'casamento') {
            return $briefing !== ''
                ? "Preparamos esta proposta pensando no que voces compartilharam: {$briefing}. A ideia e registrar o casamento com cuidado, sensibilidade e clareza em cada entrega."
                : "Preparamos esta proposta para contar a historia do casal com cuidado, sensibilidade e uma entrega visual que continue fazendo sentido com o passar dos anos.";
        }

        if ($secao === 'desafio') {
            return $briefing !== ''
                ? "O principal desafio e transformar o contexto apresentado em um plano claro, viavel e mensuravel: {$briefing}."
                : "O principal desafio e organizar as prioridades do projeto e transformar as necessidades do cliente em acoes claras.";
        }

        if ($secao === 'objetivo') {
            return "O objetivo e entregar uma solucao clara, bem executada e alinhada ao momento de {$cliente}, com foco em resultado e consistencia.";
        }

        return "Esta proposta organiza o escopo, os prazos e os investimentos para que {$cliente} tenha clareza sobre o trabalho e os proximos passos.";
    }

    /**
     * Gera uma mensagem personalizada para o WhatsApp utilizando IA com fallback seguro.
     */
    public static function gerarMensagemWhatsApp(string $nomeNoivo, string $nomeNoiva, string $nomeCasal): string
    {
        $nomeNoivaSimples = explode(' ', trim($nomeNoiva))[0];
        $nomeNoivoSimples = explode(' ', trim($nomeNoivo))[0];
        $nomes = ($nomeNoivaSimples && $nomeNoivoSimples) ? "{$nomeNoivaSimples} e {$nomeNoivoSimples}" : $nomeCasal;

        $fallback = "OlÃ¡ Wellington! Ficamos encantados com a proposta do nosso casamento ({$nomes}). GostarÃ­amos de conversar para alinhar os detalhes e dar o prÃ³ximo passo! âœ¨";

        try {
            $apiKey = self::getGeminiKey();
            if (!$apiKey) {
                return $fallback;
            }

            $prompt = "VocÃª Ã© um assistente simpÃ¡tico e caloroso de um estÃºdio de fotografia e filmmaking de luxo para casamentos chamado Distinto.
Gere uma mensagem curta, calorosa e engajadora que os noivos ($nomes) enviariam pelo WhatsApp para o estÃºdio para demonstrar interesse em fechar a proposta do casamento deles.
A mensagem deve ser escrita na perspectiva dos noivos enviando para o estÃºdio.
Exemplo de tom: 'OlÃ¡ Wellington! Amamos a proposta comercial e a forma como vocÃªs enxergam nosso casamento. Queremos conversar sobre os prÃ³ximos passos! âœ¨'
Retorne APENAS a mensagem direta, sem aspas, sem explicaÃ§Ãµes e sem introduÃ§Ãµes.";

            $resposta = self::chamarGemini([['text' => $prompt]]);
            $resposta = trim(preg_replace('/^["\']|["\']$/', '', trim($resposta)));

            return $resposta ?: $fallback;
        } catch (Exception $e) {
            return $fallback;
        }
    }
}
