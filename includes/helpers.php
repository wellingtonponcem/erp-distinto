<?php
function dataExtenso(?string $data): string {
    if (empty($data) || $data === '0000-00-00' || $data === '0000-00-00 00:00:00') {
        return 'data a ser definida';
    }
    $d = strtotime($data);
    if ($d === false || $d === -1) {
        return $data;
    }
    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
    ];
    $m = (int)date('m', $d);
    $mesNome = $meses[$m] ?? 'janeiro';
    return date('d', $d) . ' de ' . $mesNome . ' de ' . date('Y', $d);
}

function formatarMoeda(float $valor): string {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function formatarData(string $data): string {
    if (!$data) return '—';
    $dt = DateTime::createFromFormat('Y-m-d', $data)
        ?: DateTime::createFromFormat('Y-m-d H:i:s', $data);
    return $dt ? $dt->format('d/m/Y') : $data;
}

function formatarCpfCnpj(string $valor): string {
    $numeros = preg_replace('/\D/', '', $valor);
    if (strlen($numeros) === 11) {
        return vsprintf('%s%s%s.%s%s%s.%s%s%s-%s%s', str_split($numeros));
    }
    if (strlen($numeros) === 14) {
        return vsprintf('%s%s.%s%s%s.%s%s%s/%s%s%s%s-%s%s', str_split($numeros));
    }
    return $valor;
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
    $responsavel = trim($dados['responsavel'] ?? '');

    if ($contatoTipo === 'noivo' && $nomeNoivo !== '') {
        return $nomeNoivo;
    }

    if ($contatoTipo === 'noiva' && $nomeNoiva !== '') {
        return $nomeNoiva;
    }

    if ($contatoTipo === 'casal') {
        if ($nomeNoivo !== '' && $nomeNoiva !== '') {
            return $nomeNoivo . ' & ' . $nomeNoiva;
        }

        return $nomeNoiva !== '' ? $nomeNoiva : $nomeNoivo;
    }

    return $responsavel !== '' ? $responsavel : ($nomeNoiva !== '' ? $nomeNoiva : $nomeNoivo);
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
    // Se o caminho já começa com uma das bases conhecidas do ecossistema, 
    // assumimos que é um link absoluto entre módulos e não mexemos.
    if (str_starts_with($caminho, '/roteiros/') || str_starts_with($caminho, '/sistema/')) {
        return '/' . ltrim($caminho, '/');
    }

    static $base = null;
    if ($base === null) {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        if (str_contains($script, '/roteiros/')) {
            $base = '/roteiros';
        } elseif (str_contains($script, '/sistema/')) {
            $base = '/sistema';
        } else {
            $path = parse_url(APP_URL, PHP_URL_PATH) ?: '';
            $base = rtrim($path, '/');
        }
    }
    
    // Se o caminho já começa com a base detectada, removemos para não duplicar no return final
    if ($base !== '' && str_starts_with($caminho, $base)) {
        $caminho = substr($caminho, strlen($base));
    }
    
    return $base . '/' . ltrim($caminho, '/');
}

function decodificarEntidades(?string $valor): string {
    if ($valor === null) return '';
    $prev = '';
    while ($valor !== $prev) {
        $prev = $valor;
        $valor = html_entity_decode($valor, ENT_QUOTES, 'UTF-8');
    }
    return $valor;
}

function detectarPlanoCasamento(array $dadosJson): string {
    foreach (['pacote_dado_andamento', 'plano_dado_andamento'] as $campoAdmin) {
        if (!empty($dadosJson[$campoAdmin]) && in_array($dadosJson[$campoAdmin], ['heritage', 'cinematic', 'essencial'], true)) {
            return $dadosJson[$campoAdmin];
        }
    }

    $escolha = $dadosJson['cliente_escolha'] ?? [];
    if (!empty($escolha['plano_id']) && in_array($escolha['plano_id'], ['heritage', 'cinematic', 'essencial'], true)) {
        return $escolha['plano_id'];
    }

    $planos = [
        'heritage' => 'show_heritage',
        'cinematic' => 'show_cinematic',
        'essencial' => 'show_essencial',
    ];

    foreach ($planos as $planoId => $campo) {
        if (($dadosJson[$campo] ?? false) !== false) {
            return $planoId;
        }
    }

    return 'cinematic';
}

function limiteParcelasPorPlanoCasamento(string $planoId): int {
    return $planoId === 'heritage' ? 9 : 7;
}

function adicionarMesesData(string $data, int $meses): string {
    if (!$data) return '';
    $dt = DateTime::createFromFormat('Y-m-d', $data);
    if (!$dt) return '';

    $dt->modify('+' . $meses . ' months');
    return $dt->format('Y-m-d');
}

function contarMesesInclusivo(string $inicio, string $fim): int {
    if (!$inicio || !$fim) return 1;

    $inicioDt = DateTime::createFromFormat('Y-m-d', $inicio);
    $fimDt = DateTime::createFromFormat('Y-m-d', $fim);
    if (!$inicioDt || !$fimDt) return 1;

    if ($fimDt <= $inicioDt) {
        return 1;
    }

    return (($fimDt->format('Y') - $inicioDt->format('Y')) * 12)
        + ((int)$fimDt->format('m') - (int)$inicioDt->format('m'))
        + 1;
}

function calcularParcelasSaldoCasamento(array $dadosJson, int $parcelasInformadas = 1): int {
    $planoId = detectarPlanoCasamento($dadosJson);
    $limitePacote = limiteParcelasPorPlanoCasamento($planoId);
    $sinalVencimento = $dadosJson['asaas_sinal_vencimento'] ?? '';
    if (!$sinalVencimento) {
        $sinalVencimento = date('Y-m-d');
    }

    $primeiraParcela = adicionarMesesData($sinalVencimento, 1);
    $parcelasAteEvento = contarMesesInclusivo($primeiraParcela, $dadosJson['data_evento'] ?? '');
    $parcelas = min($parcelasAteEvento, $limitePacote);

    if ($parcelasInformadas > 0) {
        $parcelas = min($parcelas, $parcelasInformadas);
    }

    return max(1, (int)$parcelas);
}

// Fallback do helper getallheaders para servidores Nginx/FPM
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
