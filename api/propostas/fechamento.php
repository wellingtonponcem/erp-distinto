<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAdmin();

header('Content-Type: application/json; charset=utf-8');

function responderFechamento(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function decimalFormulario($valor): float {
    $texto = trim((string)$valor);
    if ($texto === '') return 0.0;
    $texto = str_replace(['R$', ' '], '', $texto);
    if (strpos($texto, ',') !== false) {
        $texto = str_replace('.', '', $texto);
        $texto = str_replace(',', '.', $texto);
    }
    return (float)$texto;
}

function adicionarMesesIso(string $dataIso, int $meses): string {
    if ($dataIso === '') return '';
    $dt = DateTime::createFromFormat('Y-m-d', $dataIso);
    if (!$dt) return '';
    $dt->modify('+' . $meses . ' months');
    return $dt->format('Y-m-d');
}

function mesesAteEvento(string $primeiraParcela, string $dataEvento): int {
    $inicio = DateTime::createFromFormat('Y-m-d', $primeiraParcela);
    $fim = DateTime::createFromFormat('Y-m-d', $dataEvento);
    if (!$inicio || !$fim || $fim < $inicio) return 1;
    return max(1, (((int)$fim->format('Y') - (int)$inicio->format('Y')) * 12) + ((int)$fim->format('m') - (int)$inicio->format('m')) + 1);
}

try {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $id = $payload['id'] ?? '';
    if ($id === '') {
        responderFechamento(['success' => false, 'erro' => 'ID da proposta não informado.'], 422);
    }

    $db = Database::get();
    $stmt = $db->prepare("SELECT * FROM propostas WHERE id = ?");
    $stmt->execute([$id]);
    $proposta = $stmt->fetch();
    if (!$proposta) {
        responderFechamento(['success' => false, 'erro' => 'Proposta não encontrada.'], 404);
    }

    $dados = json_decode($proposta['dados_json'] ?? '{}', true);
    if (!is_array($dados)) {
        $dados = [];
    }

    $plano = trim((string)($payload['pacote_dado_andamento'] ?? ''));
    $valorTotal = decimalFormulario($payload['valor_total'] ?? ($proposta['valor_total'] ?? 0));
    $condicoes = trim((string)($payload['escolha_condicoes'] ?? ''));
    $pagamentoModo = ($payload['pagamento_modo'] ?? 'parcelado') === 'avista' ? 'avista' : 'parcelado';
    $permitirPosEvento = !empty($payload['permitir_parcela_pos_evento']);

    if (!in_array($plano, ['', 'heritage', 'cinematic', 'essencial'], true)) {
        responderFechamento(['success' => false, 'erro' => 'Plano escolhido inválido.'], 422);
    }

    $dados['pacote_dado_andamento'] = $plano;
    $dados['valor_fechamento'] = $valorTotal;
    $dados['escolha_condicoes'] = $condicoes;
    $dados['pagamento_modo'] = $pagamentoModo;
    $dados['permitir_parcela_pos_evento'] = $permitirPosEvento;
    $dados['asaas_billing_type'] = $payload['asaas_billing_type'] ?? 'UNDEFINED';
    $percentualEntrada = $plano === 'heritage' ? 25 : 20;
    $maxParcelasPlano = $plano === 'heritage' ? 6 : 5;
    $parcelas = $pagamentoModo === 'avista' ? 1 : max(1, (int)($payload['asaas_total_parcelas'] ?? 1));
    $dados['asaas_first_due_date'] = $payload['asaas_first_due_date'] ?? '';
    $dados['asaas_valor_sinal'] = decimalFormulario($payload['asaas_valor_sinal'] ?? 0);
    $dados['asaas_sinal_vencimento'] = $payload['asaas_sinal_vencimento'] ?? '';
    $dados['prazo_contrato'] = trim((string)($payload['prazo_contrato'] ?? ''));

    if ($valorTotal > 0 && $dados['asaas_valor_sinal'] <= 0) {
        $dados['asaas_valor_sinal'] = round($valorTotal * ($percentualEntrada / 100), 2);
    }

    if ($pagamentoModo === 'parcelado') {
        if ($dados['asaas_first_due_date'] === '' && $dados['asaas_sinal_vencimento'] !== '') {
            $dados['asaas_first_due_date'] = adicionarMesesIso($dados['asaas_sinal_vencimento'], 1);
        }
        $limiteEvento = $maxParcelasPlano;
        $dataEvento = $dados['data_casamento'] ?? $dados['data_evento'] ?? '';
        if (!$permitirPosEvento && $dados['asaas_first_due_date'] !== '' && $dataEvento !== '') {
            $limiteEvento = min($maxParcelasPlano, mesesAteEvento($dados['asaas_first_due_date'], $dataEvento));
        }
        $dados['asaas_total_parcelas'] = max(1, min($parcelas, $limiteEvento));
    } else {
        $dados['asaas_total_parcelas'] = 1;
        $dados['asaas_first_due_date'] = '';
    }

    $clienteEscolha = $dados['cliente_escolha'] ?? [];
    if (!is_array($clienteEscolha)) {
        $clienteEscolha = [];
    }
    if ($plano !== '') {
        $clienteEscolha['plano_id'] = $plano;
    }
    if ($valorTotal > 0) {
        $clienteEscolha['valor_total'] = $valorTotal;
    }
    if ($condicoes !== '') {
        $clienteEscolha['condicoes'] = $condicoes;
    }
    $clienteEscolha['ajustado_admin_em'] = date('Y-m-d H:i:s');
    $dados['cliente_escolha'] = $clienteEscolha;

    $dadosJson = json_encode($dados, JSON_UNESCAPED_UNICODE);
    $stmtUp = $db->prepare("UPDATE propostas SET dados_json = ?, valor_total = ? WHERE id = ?");
    $stmtUp->execute([$dadosJson, $valorTotal > 0 ? $valorTotal : $proposta['valor_total'], $id]);

    try {
        $db->exec("CREATE TABLE IF NOT EXISTS propostas_historico (
            id SERIAL PRIMARY KEY,
            proposta_id TEXT NOT NULL,
            user_id TEXT NOT NULL,
            tipo TEXT DEFAULT 'nota',
            conteudo TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $resumoPlano = $plano !== '' ? $plano : 'não definido';
        $conteudo = 'Dados de fechamento atualizados. Plano: ' . $resumoPlano . '. Valor: R$ ' . number_format($valorTotal, 2, ',', '.') . '.';
        $stmtHist = $db->prepare("INSERT INTO propostas_historico (proposta_id, user_id, tipo, conteudo) VALUES (?, ?, ?, ?)");
        $stmtHist->execute([$id, $_SESSION['usuario_id'] ?? 'admin', 'fechamento', $conteudo]);
    } catch (Throwable $e) {
        // Historico não deve impedir o salvamento.
    }

    responderFechamento([
        'success' => true,
        'dados_json' => $dadosJson,
        'valor_total' => $valorTotal > 0 ? $valorTotal : (float)$proposta['valor_total'],
    ]);
} catch (Throwable $e) {
    responderFechamento(['success' => false, 'erro' => $e->getMessage()], 500);
}
