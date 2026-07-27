<?php
/**
 * Asaas Service - Integração técnica com a API v3 do Asaas
 * Suporta Sandbox (testes) e Produção.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

class AsaasService {
    private string $apiKey = '';
    private string $mode = 'test';
    private string $baseUrl = '';
    private ?PDO $db = null;

    public function __construct(?string $apiKey = null, ?string $mode = null) {
        $this->db = Database::get();

        if ($apiKey !== null && $mode !== null) {
            $this->apiKey = $apiKey;
            $this->mode = $mode;
        } else {
            // Carrega configurações do banco
            $stmt = $this->db->query("SELECT asaas_api_key, asaas_mode FROM configuracao_empresa WHERE id = 'principal' LIMIT 1");
            $config = $stmt->fetch();
            if ($config) {
                $this->apiKey = $config['asaas_api_key'] ?? '';
                $this->mode = $config['asaas_mode'] ?? 'test';
            }
        }

        // URLs oficiais da API v3 do Asaas
        $this->baseUrl = ($this->mode === 'prod') 
            ? 'https://api.asaas.com/v3' 
            : 'https://sandbox.asaas.com/api/v3';
    }

    /**
     * Verifica se o serviço está configurado com API Key
     */
    public function estaConfigurado(): bool {
        return !empty($this->apiKey);
    }

    /**
     * Faz requisição HTTP para a API do Asaas de forma resiliente
     */
    private function request(string $endpoint, string $method = 'GET', ?array $payload = null): array {
        if (empty($this->apiKey)) {
            throw new Exception("API Key do Asaas não está configurada no painel.");
        }

        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $ch = curl_init($url);

        $headers = [
            'access_token: ' . $this->apiKey,
            'Content-Type: application/json',
            'User-Agent: ERP-Distinto/1.0'
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($payload !== null && in_array($method, ['POST', 'PUT', 'DELETE'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }

        // Tentativa de execução com resiliência básica (retry em caso de falha de conexão)
        $response = false;
        $maxRetries = 2;
        for ($i = 0; $i < $maxRetries; $i++) {
            $response = curl_exec($ch);
            if ($response !== false) {
                break;
            }
            usleep(500000); // Aguarda 500ms
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception("Falha de conexão com Asaas: " . $curlError);
        }

        $decoded = json_decode($response, true);
        
        if ($httpCode < 200 || $httpCode >= 300) {
            $erroMsg = '';
            if (isset($decoded['errors']) && is_array($decoded['errors'])) {
                $erros = [];
                foreach ($decoded['errors'] as $err) {
                    $erros[] = ($err['description'] ?? 'Erro desconhecido');
                }
                $erroMsg = implode(', ', $erros);
            } else {
                $erroMsg = $decoded['message'] ?? $response;
            }
            throw new Exception("Erro Asaas (HTTP $httpCode): " . $erroMsg);
        }

        return $decoded ?: [];
    }

    /**
     * Obtém ou cria o cadastro de cliente no Asaas
     */
    public function obterOuCriarCliente(string $clienteId, array $dados): string {
        // 1. Verifica se já temos o asaas_customer_id localmente
        $stmt = $this->db->prepare("SELECT asaas_customer_id, nome, cpf_cnpj, contato FROM clientes WHERE id = ?");
        $stmt->execute([$clienteId]);
        $cliente = $stmt->fetch();

        if ($cliente && !empty($cliente['asaas_customer_id'])) {
            return $cliente['asaas_customer_id'];
        }

        $nome = trim($dados['nome'] ?? ($cliente['nome'] ?? ''));
        $cpfCnpj = preg_replace('/\D/', '', $dados['cpf_cnpj'] ?? ($cliente['cpf_cnpj'] ?? ''));
        $email = trim($dados['email'] ?? ($cliente['contato'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = ''; // Evita enviar string de contato inválida (como whats) no campo email
        }
        $telefone = preg_replace('/\D/', '', $dados['telefone'] ?? '');

        if (empty($nome)) {
            throw new Exception("Nome do cliente é obrigatório para cadastrar no Asaas.");
        }

        // 2. Tenta buscar no Asaas por CPF/CNPJ para evitar duplicidade
        if (!empty($cpfCnpj)) {
            try {
                $busca = $this->request("customers?cpfCnpj=" . $cpfCnpj);
                if (!empty($busca['data'][0]['id'])) {
                    $asaasCustomerId = $busca['data'][0]['id'];
                    // Atualiza localmente
                    $stmtUp = $this->db->prepare("UPDATE clientes SET asaas_customer_id = ? WHERE id = ?");
                    $stmtUp->execute([$asaasCustomerId, $clienteId]);
                    return $asaasCustomerId;
                }
            } catch (Exception $e) {
                // Se falhar a busca, tenta cadastrar diretamente
            }
        }

        // 3. Cria o cliente no Asaas
        $payload = [
            'name' => $nome,
            'cpfCnpj' => $cpfCnpj,
            'email' => $email,
            'mobilePhone' => $telefone ?: null,
            'externalReference' => $clienteId
        ];

        $res = $this->request("customers", "POST", $payload);
        if (empty($res['id'])) {
            throw new Exception("Falha ao criar cliente no Asaas: Resposta inválida.");
        }

        $asaasCustomerId = $res['id'];

        // Salva localmente o customer ID
        $stmtUp = $this->db->prepare("UPDATE clientes SET asaas_customer_id = ? WHERE id = ?");
        $stmtUp->execute([$asaasCustomerId, $clienteId]);

        return $asaasCustomerId;
    }

    /**
     * Cria uma cobrança no Asaas
     * Suporta cobrança única ou parcelada.
     */
    public function criarCobranca(array $dados): array {
        $clienteId = $dados['cliente_id'] ?? '';
        $valorTotal = (float)($dados['valor_total'] ?? 0);
        $vencimento = $dados['vencimento'] ?? '';
        $billingType = $dados['billing_type'] ?? 'UNDEFINED'; // UNDEFINED = Cliente escolhe no link do Asaas
        $descricao = $dados['descricao'] ?? '';
        $externalReference = $dados['external_reference'] ?? '';
        
        // Parcelamento
        $totalParcelas = (int)($dados['total_parcelas'] ?? 1);
        $valorSinal = (float)($dados['valor_sinal'] ?? 0);

        if ($valorTotal <= 0) {
            throw new Exception("O valor total da cobrança deve ser maior que zero.");
        }
        if (empty($vencimento)) {
            throw new Exception("Data de vencimento é obrigatória.");
        }

        // Obtém/Cria cliente no Asaas
        $dadosCliente = [
            'nome' => $dados['cliente_nome'] ?? '',
            'cpf_cnpj' => $dados['cliente_cpf_cnpj'] ?? '',
            'email' => $dados['cliente_email'] ?? '',
            'telefone' => $dados['cliente_telefone'] ?? ''
        ];
        $asaasCustomerId = $this->obterOuCriarCliente($clienteId, $dadosCliente);

        $payload = [
            'customer' => $asaasCustomerId,
            'billingType' => $billingType,
            'description' => $descricao,
            'externalReference' => $externalReference
        ];

        if ($billingType === 'CREDIT_CARD') {
            if ($totalParcelas > 1) {
                $payload['dueDate'] = $vencimento;
                $payload['installmentCount'] = $totalParcelas;
                $payload['installmentValue'] = round($valorTotal / $totalParcelas, 2);
                $payload['description'] = "[Cartão] " . $descricao;
            } else {
                $payload['dueDate'] = $vencimento;
                $payload['value'] = $valorTotal;
                $payload['description'] = "[Cartão] " . $descricao;
            }

            return $this->request("payments", "POST", $payload);
        }

        // Se houver valor de sinal, este deve ser gerado como uma cobrança separada (vencimento imediato)
        // e o saldo restante parcelado.
        if ($valorSinal > 0 && $valorSinal < $valorTotal) {
            // Sinal/Entrada
            $sinalPayload = $payload;
            $sinalPayload['value'] = $valorSinal;
            $sinalPayload['dueDate'] = $dados['sinal_vencimento'] ?? date('Y-m-d');
            $sinalPayload['description'] = "[Entrada/Sinal] " . $descricao;

            // Envia o sinal primeiro
            $sinalRes = $this->request("payments", "POST", $sinalPayload);

            // Agora cria o faturamento para o saldo restante
            $saldoRestante = $valorTotal - $valorSinal;
            $saldoPayload = $payload;
            $saldoPayload['dueDate'] = $vencimento; // Primeira parcela do saldo restante
            
            if ($totalParcelas > 1) {
                // Cria parcelamento do saldo restante
                $saldoPayload['installmentCount'] = $totalParcelas;
                $saldoPayload['installmentValue'] = round($saldoRestante / $totalParcelas, 2);
                $saldoPayload['description'] = "[Parcelado] " . $descricao;
                $saldoRes = $this->request("payments", "POST", $saldoPayload);
            } else {
                // Parcela única do saldo restante
                $saldoPayload['value'] = $saldoRestante;
                $saldoPayload['description'] = "[Saldo] " . $descricao;
                $saldoRes = $this->request("payments", "POST", $saldoPayload);
            }

            return [
                'sinal' => $sinalRes,
                'saldo' => $saldoRes,
                'multiplo' => true
            ];
        }

        // Sem sinal - Fluxo padrão simples ou parcelado direto
        if ($totalParcelas > 1) {
            $payload['installmentCount'] = $totalParcelas;
            $payload['installmentValue'] = round($valorTotal / $totalParcelas, 2);
            $payload['dueDate'] = $vencimento;
            $res = $this->request("payments", "POST", $payload);
        } else {
            $payload['value'] = $valorTotal;
            $payload['dueDate'] = $vencimento;
            $res = $this->request("payments", "POST", $payload);
        }

        return $res;
    }

    /**
     * Cancela uma cobrança no Asaas
     */
    public function cancelarCobranca(string $asaasId): bool {
        try {
            $res = $this->request("payments/{$asaasId}", "DELETE");
            return isset($res['deleted']) && $res['deleted'] === true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Consulta detalhes de uma cobrança
     */
    public function obterCobranca(string $asaasId): array {
        return $this->request("payments/{$asaasId}");
    }

    public function listarCobrancasPorParcelamento(string $installmentId, int $limite = 100): array {
        if ($installmentId === '') {
            return [];
        }

        $res = $this->request('payments?installment=' . urlencode($installmentId) . '&limit=' . max(1, min(100, $limite)));
        return $res['data'] ?? [];
    }

    public function listarCobrancasPorReferencia(string $externalReference, int $limite = 100): array {
        if ($externalReference === '') {
            return [];
        }

        $res = $this->request('payments?externalReference=' . urlencode($externalReference) . '&limit=' . max(1, min(100, $limite)));
        return $res['data'] ?? [];
    }

    public function listarCobrancas(int $limit = 100, int $offset = 0): array {
        return $this->request('payments?limit=' . max(1, min(100, $limit)) . '&offset=' . max(0, $offset));
    }

    /**
     * Consulta o extrato e saldo financeiro
     */
    public function obterSaldoEExtrato(int $limite = 15): array {
        $saldo = $this->request("finance/balance");
        $cobranças = $this->request("payments?limit={$limite}&order=desc");

        return [
            'saldo' => (float)($saldo['balance'] ?? 0.0),
            'cobrancas' => $cobranças['data'] ?? []
        ];
    }

    /**
     * Consulta o extrato financeiro de transações na conta Asaas
     * Percorre automaticamente todas as páginas disponíveis.
     */
    public function obterExtratoFinanceiro(string $startDate, string $finishDate, int $limit = 100): array {
        $offset = 0;
        $allData = [];
        
        do {
            $endpoint = "financialTransactions?startDate={$startDate}&finishDate={$finishDate}&limit={$limit}&offset={$offset}&order=asc";
            $res = $this->request($endpoint);
            $pageData = $res['data'] ?? [];
            $allData = array_merge($allData, $pageData);
            $hasMore = !empty($res['hasMore']);
            $offset += $limit;
        } while ($hasMore);
        
        return $allData;
    }
}
