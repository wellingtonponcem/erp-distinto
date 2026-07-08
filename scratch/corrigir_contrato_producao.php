<?php
// Script de correção de contrato e sincronização de faturamento do Asaas
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/asaas.php';
require_once __DIR__ . '/../includes/financeiro_custos.php';
require_once __DIR__ . '/../includes/contratos.php';

try {
    $db = Database::get();
    
    $contratoId = 'ff8a3128fc13b1e259db3dd5eaeda279';
    $asaasCustomerId = 'cus_000186079450'; // ID retornado na nossa criacao com sucesso
    
    echo "1. Buscando o contrato...\n";
    $stmtC = $db->prepare("SELECT * FROM contratos WHERE id = ?");
    $stmtC->execute([$contratoId]);
    $contrato = $stmtC->fetch(PDO::FETCH_ASSOC);
    
    if (!$contrato) {
        die("ERRO: Contrato não encontrado.\n");
    }
    
    $dadosJson = json_decode($contrato['dados_json'], true) ?: [];
    $sig2 = $dadosJson['signatario_2'] ?? null;
    if (!$sig2) {
        die("ERRO: Dados do signatário 2 não encontrados no contrato.\n");
    }
    
    echo "2. Criando/Buscando cliente local correspondente ao Igor...\n";
    $stmtCliExiste = $db->prepare("SELECT id FROM clientes WHERE cpf_cnpj = ? OR nome = ? LIMIT 1");
    $stmtCliExiste->execute([$sig2['cpf'], $sig2['nome']]);
    $clienteId = $stmtCliExiste->fetchColumn();
    
    if (!$clienteId) {
        $clienteId = gerarId();
        $stmtInsertCli = $db->prepare("INSERT INTO clientes (id, nome, cpf_cnpj, contato, asaas_customer_id, segmento) VALUES (?, ?, ?, ?, ?, 'Casamento')");
        $stmtInsertCli->execute([
            $clienteId,
            $sig2['nome'],
            $sig2['cpf'],
            $sig2['email'] ?? '',
            $asaasCustomerId
        ]);
        echo "Cliente local criado com ID: $clienteId\n";
    } else {
        // Atualiza asaas_customer_id do cliente local
        $stmtUpCli = $db->prepare("UPDATE clientes SET asaas_customer_id = ? WHERE id = ?");
        $stmtUpCli->execute([$asaasCustomerId, $clienteId]);
        echo "Cliente local encontrado com ID: $clienteId. Vinculado ao Asaas Customer ID.\n";
    }
    
    echo "3. Associando cliente_id ao contrato e salvando...\n";
    $stmtUpContrato = $db->prepare("UPDATE contratos SET cliente_id = ?, asaas_cobranca_gerada = 1 WHERE id = ?");
    $stmtUpContrato->execute([$clienteId, $contratoId]);
    
    // Atualiza objeto na memória para usar na sincronização
    $contrato['cliente_id'] = $clienteId;
    $contrato['asaas_cobranca_gerada'] = 1;
    
    echo "4. Sincronizando as cobranças do Asaas diretamente para a tabela local...\n";
    $asaas = new AsaasService();
    
    $pagamentosPorId = [];
    $erros = [];
    
    // Lista todas as cobranças pela referência do contrato
    foreach ($asaas->listarCobrancasPorReferencia($contratoId) as $payment) {
        if (empty($payment['id'])) {
            continue;
        }
        $pagamentosPorId[$payment['id']] = $payment;
        
        foreach (obterParcelasAsaas($asaas, $payment) as $parcela) {
            if (!empty($parcela['id'])) {
                $pagamentosPorId[$parcela['id']] = $parcela;
            }
        }
    }
    
    if (empty($pagamentosPorId)) {
        die("Nenhuma cobrança encontrada no Asaas para este contrato.\n");
    }
    
    echo "Cobranças encontradas no Asaas: " . count($pagamentosPorId) . "\n";
    
    $pagamentosOrdenados = array_values($pagamentosPorId);
    usort($pagamentosOrdenados, function ($a, $b) {
        return strcmp((string)($a['dueDate'] ?? ''), (string)($b['dueDate'] ?? ''));
    });
    
    $stmtLocal = $db->prepare("SELECT id FROM lancamentos WHERE asaas_id = ? LIMIT 1");
    $criados = 0;
    $atualizados = 0;
    
    foreach ($pagamentosOrdenados as $payment) {
        if (empty($payment['id'])) {
            continue;
        }
        
        $stmtLocal->execute([$payment['id']]);
        $localId = $stmtLocal->fetchColumn();
        
        $descricao = "[Parcela] " . $contrato['titulo'];
        $valor = (float)($payment['value'] ?? 0);
        $vencimento = $payment['dueDate'] ?? '';
        
        if ($localId) {
            // Atualiza
            atualizarLancamentoAsaas($db, (string)$localId, $payment, $descricao, $valor, $vencimento);
            $atualizados++;
        } else {
            // Cria
            gravarLancamentoAsaas($db, $contrato, $payment, $descricao, $valor, $vencimento, null, $sig2['nome']);
            $criados++;
        }
    }
    
    echo "Sincronização realizada: $criados criados, $atualizados atualizados locais.\n";
    echo "SUCESSO TOTAL! Contrato regularizado.\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

function atualizarLancamentoAsaas(PDO $db, string $id, array $payment, string $descricao, float $valor, string $vencimento): void {
    $status = 'pendente';
    $statusAsaas = strtolower((string)($payment['status'] ?? ''));
    if (in_array($statusAsaas, ['received', 'confirmed', 'received_in_cash'], true)) {
        $status = 'pago';
    } elseif ($statusAsaas === 'overdue') {
        $status = 'atrasado';
    }
    
    $stmt = $db->prepare("UPDATE lancamentos SET valor = ?, vencimento = ?, status = ?, asaas_boleto_url = ?, asaas_invoice_url = ? WHERE id = ?");
    $stmt->execute([
        $valor,
        $vencimento,
        $status,
        $payment['bankSlipUrl'] ?? $payment['nossoNumero'] ?? null,
        $payment['invoiceUrl'] ?? null,
        $id
    ]);
}
