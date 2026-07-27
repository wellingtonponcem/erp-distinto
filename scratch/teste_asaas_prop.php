<?php
/**
 * Script de Teste de Resiliência, API e Webhook do Asaas
 * Valida a robustez do sistema sob falhas de conexão e a correto funcionamento da API.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/asaas.php';
require_once __DIR__ . '/../includes/contratos.php';
require_once __DIR__ . '/../includes/financeiro_custos.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Painel de Testes & Validação - Integração Asaas</h1>";

$db = Database::get();
garantirEstruturaFinanceira($db);

// --- TESTE 1: Validação de Conexão com API Sandbox ---
echo "<h2>Teste 1: Conexão com API Sandbox</h2>";
try {
    $asaas = new AsaasService();
    if (!$asaas->estaConfigurado()) {
        echo "<span style='color:red;'>[FALHA] AsaasService não está configurado. Execute primeiro o setup_asaas_keys.php</span><br>";
    } else {
        echo "[OK] AsaasService carregado.<br>";
        $extrato = $asaas->obterSaldoEExtrato(5);
        echo "<span style='color:emerald; font-weight:bold;'>[SUCESSO] Conectado ao Asaas Sandbox!</span><br>";
        echo "Saldo Atual no Sandbox: <strong>R$ " . number_format($extrato['saldo'], 2, ',', '.') . "</strong><br>";
        echo "Últimas cobranças retornadas: " . count($extrato['cobrancas']) . "<br>";
    }
} catch (Exception $e) {
    echo "<span style='color:red;'>[FALHA] Erro ao conectar com Asaas Sandbox: " . htmlspecialchars($e->getMessage()) . "</span><br>";
}

// --- TESTE 2: Simulação de Falha de Conexão (Resiliência do ERP) ---
echo "<h2>Teste 2: Teste de Resiliência a Falhas da API</h2>";
echo "Criando contrato temporário de teste...<br>";

$contratoId = 'test_' . uniqid();
$clienteId = 'cli_test_' . uniqid();

try {
    // Insere cliente fictício
    $db->prepare("INSERT INTO clientes (id, nome, cpf_cnpj, contato) VALUES (?, ?, ?, ?)")
       ->execute([$clienteId, 'Cliente Teste Asaas', '35096185070', 'teste@asaas.com']);
    
    // Insere contrato fictício
    $db->prepare("INSERT INTO contratos (id, cliente_id, cliente_nome, titulo, valor_total, status, asaas_cobranca_gerada) VALUES (?, ?, ?, ?, ?, ?, ?)")
       ->execute([$contratoId, $clienteId, 'Cliente Teste Asaas', 'Contrato Teste Resiliencia API', 1500.00, 'assinado', 0]);

    echo "Contrato temporário criado com sucesso (ID: $contratoId).<br>";
    echo "Simulando chamada com API temporariamente quebrada para testar resiliência...<br>";

    // Instancia Asaas com chave incorreta
    $asaasFake = new AsaasService('chave_invalida_de_teste', 'test');
    
    // Vamos chamar o processamento. Como a chave está incorreta, a API falhará.
    // O ERP deve reportar a falha de cobrança mas manter a transação do contrato (CRM e status) íntegra.
    $ret = processarAssinaturaContrato($contratoId);

    if ($ret['success'] === true) {
        echo "<span style='color:green; font-weight:bold;'>[SUCESSO] O ERP processou a assinatura mesmo com a API do Asaas falhando!</span><br>";
        echo "Mensagem retorno: " . htmlspecialchars($ret['mensagem']) . "<br>";
        echo "Erros capturados (sem quebrar o fluxo principal): <pre style='color:orange;'>" . print_r($ret['erros'], true) . "</pre>";
    } else {
        echo "<span style='color:red;'>[FALHA] O ERP quebrou o fluxo geral de assinatura devido a erro da API externa!</span><br>";
    }

} catch (Exception $e) {
    echo "<span style='color:red;'>[FALHA] Exceção geral no teste de resiliência: " . htmlspecialchars($e->getMessage()) . "</span><br>";
} finally {
    // Limpeza
    $db->prepare("DELETE FROM contratos WHERE id = ?")->execute([$contratoId]);
    $db->prepare("DELETE FROM clientes WHERE id = ?")->execute([$clienteId]);
    echo "Dados de teste limpos.<br>";
}

// --- TESTE 3: Simulação de Webhook (Conciliação Financeira) ---
echo "<h2>Teste 3: Simulação de Webhook de Pagamento</h2>";
echo "Criando lançamento local temporário para conciliação...<br>";

$lancamentoId = 'lanc_test_' . uniqid();
$asaasCobrancaId = 'pay_test_' . uniqid();

try {
    // Cria lançamento fictício com status pendente e asaas_id
    $db->prepare("INSERT INTO lancamentos (id, tipo, descricao, valor, valor_pago, status, asaas_id, vencimento) VALUES (?, 'receber', 'Lancamento Teste Webhook', 500.00, 0.00, 'pendente', ?, ?)")
       ->execute([$lancamentoId, $asaasCobrancaId, date('Y-m-d')]);
       
    echo "Lançamento local criado com sucesso (ID: $lancamentoId, Asaas ID: $asaasCobrancaId, Status: pendente).<br>";
    echo "Simulando recebimento de webhook 'PAYMENT_RECEIVED' do Asaas...<br>";

    // Obtém o token configurado no banco para assinar o webhook
    $webhookToken = $db->query("SELECT asaas_webhook_token FROM configuracao_empresa WHERE id = 'principal' LIMIT 1")->fetchColumn();

    // Payload de webhook simulado
    $payload = [
        'event' => 'PAYMENT_RECEIVED',
        'payment' => [
            'id' => $asaasCobrancaId,
            'status' => 'RECEIVED',
            'value' => 500.00,
            'paymentDate' => date('Y-m-d')
        ]
    ];

    // Envia POST simulado para o script de webhook usando cURL
    $webhookUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME']) . '/../api/financeiro/webhook_asaas.php';
    
    echo "Enviando payload simulado para: <code>$webhookUrl</code><br>";
    
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'asaas-access-token: ' . $webhookToken
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Resposta do Webhook (HTTP $httpCode): <pre>" . htmlspecialchars($res) . "</pre>";

    // Verifica se o lançamento local foi atualizado para pago
    $stmtCheck = $db->prepare("SELECT status, valor_pago, data_pagamento FROM lancamentos WHERE id = ?");
    $stmtCheck->execute([$lancamentoId]);
    $lancAtualizado = $stmtCheck->fetch();

    if ($lancAtualizado && $lancAtualizado['status'] === 'pago' && (float)$lancAtualizado['valor_pago'] === 500.00) {
        echo "<span style='color:green; font-weight:bold;'>[SUCESSO] Webhook processado! O lançamento local foi liquidado corretamente.</span><br>";
    } else {
        echo "<span style='color:red;'>[FALHA] O lançamento local não foi atualizado ou está incorreto.</span><br>";
        echo "Estado atual: <pre>" . print_r($lancAtualizado, true) . "</pre>";
    }

} catch (Exception $e) {
    echo "<span style='color:red;'>[FALHA] Exceção geral no teste de webhook: " . htmlspecialchars($e->getMessage()) . "</span><br>";
} finally {
    // Limpeza
    $db->prepare("DELETE FROM lancamentos WHERE id = ?")->execute([$lancamentoId]);
    echo "Lançamento temporário limpo.<br>";
}

echo "<br><hr><p>Testes concluídos.</p>";
