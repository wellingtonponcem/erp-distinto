<?php
// Script de depuração temporário para rodar em produção
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

echo "=== DIAGNÓSTICO DE BANCO DE DADOS EM PRODUÇÃO ===\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_PORT: " . DB_PORT . "\n";
echo "DB_NAME: " . DB_NAME . "\n";

try {
    $db = Database::get();
    echo "Conexão de banco: OK!\n";
    
    $id = 'ff8a3128fc13b1e259db3dd5eaeda279';
    $stmt = $db->prepare("SELECT id, proposta_id, cliente_id, cliente_nome, valor_total, status, asaas_cobranca_gerada FROM contratos WHERE id = ?");
    $stmt->execute([$id]);
    $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contrato) {
        echo "\n--- Dados do Contrato ---\n";
        print_r($contrato);
        
        if (!empty($contrato['cliente_id'])) {
            $stmtCli = $db->prepare("SELECT id, nome, cpf_cnpj, contato, asaas_customer_id FROM clientes WHERE id = ?");
            $stmtCli->execute([$contrato['cliente_id']]);
            $cliente = $stmtCli->fetch(PDO::FETCH_ASSOC);
            echo "\n--- Dados do Cliente Cadastrado ---\n";
            print_r($cliente);
        } else {
            echo "\ncliente_id está VAZIO no contrato!\n";
        }
    } else {
        echo "\nContrato ff8a3128fc13b1e259db3dd5eaeda279 não encontrado no banco de produção!\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
