<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/asaas.php';
require_once __DIR__ . '/../includes/contratos.php';

try {
    $db = Database::get();
    $id = 'ff8a3128fc13b1e259db3dd5eaeda279';
    
    $stmtC = $db->prepare("SELECT * FROM contratos WHERE id = ?");
    $stmtC->execute([$id]);
    $contrato = $stmtC->fetch(PDO::FETCH_ASSOC);
    
    if (!$contrato) {
        die("Contrato não encontrado.\n");
    }
    
    $dadosJson = json_decode($contrato['dados_json'], true) ?: [];
    
    // Simula a escolha do signatário 1 (Gabriela Vianna Soares)
    $sig1 = $dadosJson['signatario_1'] ?? null;
    $sigEscolhido = $sig1;
    
    $cpfOriginal = $sigEscolhido['cpf'] ?? '';
    $cpfLimpo = preg_replace('/\D/', '', $cpfOriginal);
    
    echo "Nome: " . $sigEscolhido['nome'] . "\n";
    echo "CPF Original: '$cpfOriginal'\n";
    echo "CPF Limpo: '$cpfLimpo'\n";
    echo "Email: " . ($sigEscolhido['email'] ?? '') . "\n";
    echo "Telefone: " . ($sigEscolhido['telefone'] ?? '') . "\n";
    
    $asaas = new AsaasService();
    echo "Asaas Configurado: " . ($asaas->estaConfigurado() ? 'SIM' : 'NÃO') . "\n";
    
    // Simula as opções do formulário do usuário no print
    $firstDueDate = '2026-08-25';
    $sinalVencimento = '2026-06-20';
    $valorSinal = 700.00;
    $totalParcelas = 8;
    $billingType = 'CREDIT_CARD';
    
    $valorTotalCobranca = (float)$contrato['valor_total']; // 4500.00
    // Como entrada_status = 'pago', gerar_apenas_saldo = 1
    $valorTotalCobranca = max(0.0, $valorTotalCobranca - $valorSinal); // 3800.00
    $valorSinalAsaas = 0.0;
    
    $dadosCobranca = [
        'cliente_id' => $contrato['cliente_id'], // Vazio
        'cliente_nome' => $sigEscolhido['nome'] ?? $contrato['cliente_nome'],
        'cliente_cpf_cnpj' => $cpfLimpo,
        'cliente_email' => $sigEscolhido['email'] ?? '',
        'cliente_telefone' => preg_replace('/\D/', '', $sigEscolhido['telefone'] ?? ''),
        'valor_total' => $valorTotalCobranca,
        'vencimento' => $firstDueDate,
        'billing_type' => $billingType,
        'descricao' => "Contrato: " . $contrato['titulo'],
        'external_reference' => $contrato['id'],
        'total_parcelas' => $totalParcelas,
        'valor_sinal' => $valorSinalAsaas,
        'sinal_vencimento' => $sinalVencimento
    ];
    
    echo "\nDados da Cobrança a serem enviados para criarCobranca:\n";
    print_r($dadosCobranca);
    
    // Tenta criar
    $cobrancaRes = $asaas->criarCobranca($dadosCobranca);
    echo "Sucesso total! Cobrança criada:\n";
    print_r($cobrancaRes);
    
} catch (Exception $e) {
    echo "Erro capturado: " . $e->getMessage() . "\n";
}
