<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

$db = Database::get();

// Buscar propostas sem oportunidade
$stmt = $db->query("SELECT id, cliente_id, cliente_nome, tipo, titulo, valor_total, responsavel_json FROM (
    SELECT p.*, p.dados_json->>'responsavel' as responsavel_json 
    FROM propostas p 
    WHERE oportunidade_id IS NULL OR oportunidade_id = ''
) x");
$propostas = $stmt->fetchAll();

echo "Encontradas " . count($propostas) . " propostas sem oportunidade.\n";

foreach ($propostas as $p) {
    $idOportunidade = gerarId();
    $previsao = date('Y-m-d', strtotime($p['tipo'] === 'casamento' ? '+30 days' : '+15 days'));
    
    try {
        $stmtIns = $db->prepare("INSERT INTO oportunidades (id, cliente_id, nome, valor_estimado, etapa, previsao, responsavel, descricao, criado_em, atualizado_em) 
                                 VALUES (?, ?, ?, ?, 'proposta', ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        
        $stmtIns->execute([
            $idOportunidade,
            $p['cliente_id'],
            $p['titulo'],
            $p['valor_total'],
            $previsao,
            $p['responsavel_json'] ?: $p['cliente_nome'],
            "Migrado automaticamente da proposta " . $p['id']
        ]);
        
        $db->prepare("UPDATE propostas SET oportunidade_id = ? WHERE id = ?")->execute([$idOportunidade, $p['id']]);
        echo "Vinculado: Proposta {$p['id']} -> Oportunidade {$idOportunidade}\n";
    } catch (Exception $e) {
        echo "Erro na proposta {$p['id']}: " . $e->getMessage() . "\n";
    }
}
