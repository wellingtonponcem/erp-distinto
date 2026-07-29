<?php
/**
 * API: Aprovação Pública de Orçamento
 */
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $d = lerCorpo();
    $slug = $d['slug'] ?? '';
    
    if (empty($slug)) {
        responderJson(['erro' => 'Slug do orçamento não informado.'], 422);
    }

    $db = Database::get();
    $stmt = $db->prepare("SELECT * FROM orcamentos WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $orcamento = $stmt->fetch();

    if (!$orcamento) {
        responderJson(['erro' => 'Orçamento não encontrado.'], 404);
    }

    $dados = json_decode($orcamento['dados_json'], true) ?? [];
    
    // Registrar escolha do cliente no JSON
    $dados['aprovacao'] = [
        'data' => date('Y-m-d H:i:s'),
        'cliente_nome' => $d['cliente_nome'] ?? $orcamento['cliente_nome'],
        'telefone' => $d['telefone'] ?? '',
        'colecao_id' => $d['colecao_id'] ?? '',
        'colecao_nome' => $d['colecao_nome'] ?? '',
        'laminas_extras' => $d['laminas_extras'] ?? 0,
        'valor_total' => $d['valor_total'] ?? $orcamento['valor_total'],
        'observacoes' => $d['observacoes'] ?? ''
    ];

    $valorTotalFinal = (float) ($d['valor_total'] ?? $orcamento['valor_total']);

    // Atualizar registro
    $stmtUp = $db->prepare("UPDATE orcamentos SET status = 'aprovado', valor_total = ?, dados_json = ? WHERE id = ?");
    $stmtUp->execute([
        $valorTotalFinal,
        json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        $orcamento['id']
    ]);

    // Buscar telefone da empresa para WhatsApp
    $configEmpresa = [];
    try {
        $configEmpresa = $db->query("SELECT * FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch() ?: [];
    } catch (Exception $e) {}

    $telEmpresa = preg_replace('/\D/', '', $configEmpresa['telefone'] ?? '5527999999999');
    if (!str_starts_with($telEmpresa, '55') && strlen($telEmpresa) >= 10) {
        $telEmpresa = '55' . $telEmpresa;
    }

    $msgWhats = "🎉 *Aprovação de Orçamento!*\n\n"
              . "Cliente: *" . ($d['cliente_nome'] ?? $orcamento['cliente_nome']) . "*\n"
              . "Orçamento: *" . $orcamento['titulo'] . "*\n"
              . "Coleção Escolhida: *" . ($d['colecao_nome'] ?? 'Coleção Base') . "*\n"
              . "Lâminas Extras: +" . ($d['laminas_extras'] ?? 0) . "\n"
              . "Investimento Total: *R$ " . number_format($valorTotalFinal, 2, ',', '.') . "*\n\n"
              . (!empty($d['observacoes']) ? "Observações: " . $d['observacoes'] . "\n\n" : "")
              . "Link: " . rtrim(APP_URL, '/') . "/o/" . $slug;

    $urlWhats = "https://wa.me/" . $telEmpresa . "?text=" . urlencode($msgWhats);

    responderJson([
        'success' => true,
        'mensagem' => 'Orçamento aprovado com sucesso!',
        'whatsapp_url' => $urlWhats
    ]);

} catch (Exception $e) {
    responderJson(['erro' => 'Erro ao processar aprovação: ' . $e->getMessage()], 500);
}
