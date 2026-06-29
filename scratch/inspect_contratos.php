<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

function contratoTextoTemMojibake(string $texto): bool
{
    return preg_match('/(Ãƒ|Ã‚|Ã§|Ã£|Ã¡|Ã©|Ãª|Ã­|Ã³|Ã´|Ãµ|Ãº|Ã‡|Âº|Âª|Â°|â€)/u', $texto) === 1;
}

function corrigirMojibakeContrato($valor)
{
    if (is_array($valor)) {
        foreach ($valor as $chave => $item) {
            $valor[$chave] = corrigirMojibakeContrato($item);
        }
        return $valor;
    }
    if (!is_string($valor) || $valor === '') {
        return $valor;
    }
    $texto = decodificarEntidades($valor);
    for ($i = 0; $i < 3 && contratoTextoTemMojibake($texto); $i++) {
        // Tenta converter de UTF-8 para Windows-1252 para corrigir o double-encoding
        $convertido = @iconv('UTF-8', 'Windows-1252//IGNORE', $texto);
        if (!is_string($convertido) || $convertido === '' || $convertido === $texto) {
            break;
        }
        $texto = $convertido;
    }
    return strtr($texto, [
        'â€“' => '-',
        'â€”' => '-',
        'â€œ' => '"',
        'â€ ' => '"',
        'â€˜' => "'",
        'â€™' => "'",
        'â€¦' => '...',
        'Âº' => 'º',
        'Âª' => 'ª',
        'Â°' => '°',
        'Â§' => '§',
        'Â ' => ' ',
    ]);
}

$db = Database::get();
$c = $db->query('SELECT id, cliente_nome, dados_json FROM contratos LIMIT 1')->fetch();

$decoded = json_decode($c['dados_json'], true);
$contratoTextoRaw = $decoded['contrato_texto'] ?? '';
echo "RAW SIZE: " . strlen($contratoTextoRaw) . " bytes\n";
echo "RAW FIRST 200 CHARS:\n" . substr($contratoTextoRaw, 0, 200) . "\n\n";

$corrigido = corrigirMojibakeContrato($contratoTextoRaw);
echo "CORRIGIDO SIZE: " . strlen($corrigido) . " bytes\n";
echo "CORRIGIDO FIRST 200 CHARS:\n" . substr($corrigido, 0, 200) . "\n";
