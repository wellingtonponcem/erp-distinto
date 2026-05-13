<?php
function formatarMoeda(float $valor): string {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function formatarData(string $data): string {
    if (!$data) return '—';
    $dt = DateTime::createFromFormat('Y-m-d', $data)
        ?: DateTime::createFromFormat('Y-m-d H:i:s', $data);
    return $dt ? $dt->format('d/m/Y') : $data;
}

function gerarId(): string {
    return bin2hex(random_bytes(16));
}

function responderJson(mixed $dados, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

function lerCorpo(): array {
    $corpo = file_get_contents('php://input');
    return json_decode($corpo, true) ?? [];
}

function sanitizar(string $valor): string {
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

function classeStatus(string $status): string {
    return match($status) {
        'pago'         => 'bg-green-500/20 text-green-400 border border-green-500/30',
        'pago_parcial' => 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30',
        'pendente'     => 'bg-blue-500/20 text-blue-400 border border-blue-500/30',
        'atrasado'     => 'bg-red-500/20 text-red-400 border border-red-500/30',
        'cancelado'    => 'bg-gray-500/20 text-gray-400 border border-gray-500/30',
        default        => 'bg-gray-500/20 text-gray-400 border border-gray-500/30',
    };
}

function contatoResponsavel(array $dados): string {
    $contatoTipo = $dados['contato_tipo'] ?? '';
    $nomeNoivo = trim($dados['nome_noivo'] ?? '');
    $nomeNoiva = trim($dados['nome_noiva'] ?? '');

    if ($contatoTipo === 'noivo' && $nomeNoivo !== '') {
        return $nomeNoivo;
    }

    if ($contatoTipo === 'noiva' && $nomeNoiva !== '') {
        return $nomeNoiva;
    }

    return trim($dados['responsavel'] ?? '');
}

function labelStatus(string $status): string {
    return match($status) {
        'pago'         => 'Pago',
        'pago_parcial' => 'Parcial',
        'pendente'     => 'Pendente',
        'atrasado'     => 'Atrasado',
        'cancelado'    => 'Cancelado',
        default        => ucfirst($status),
    };
}

function calcularStatusAtualizado(float $valor, float $valorPago, string $vencimento): string {
    if ($valorPago >= $valor) return 'pago';
    if ($valorPago > 0) return 'pago_parcial';
    if (strtotime($vencimento) < strtotime('today')) return 'atrasado';
    return 'pendente';
}

function raizUrl(string $caminho = ''): string {
    static $base = null;
    if ($base === null) {
        $path = parse_url(APP_URL, PHP_URL_PATH) ?: '';
        $base = rtrim($path, '/');
    }
    return $base . $caminho;
}
