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

// Testando strings estáticas contendo mojibake retiradas de contrato_gerar.php
$testes = [
    'Anexo I - DescriÃ§Ã£o dos ServiÃ§os' => 'Anexo I - Descrição dos Serviços',
    'Gerais e Financeiro' => 'Gerais e Financeiro',
    'TÃ­tulo Interno do Contrato' => 'Título Interno do Contrato',
    'Data do Evento/InÃ­cio' => 'Data do Evento/Início',
    'Cidade/UF EmissÃ£o' => 'Cidade/UF Emissão',
    'Calculadora de CondiÃ§Ãµes de Pagamento' => 'Calculadora de Condições de Pagamento',
    'Pix ou Boleto BancÃ¡rio' => 'Pix ou Boleto Bancário',
    'CartÃ£o de CrÃ©dito' => 'Cartão de Crédito',
    'Dados de Assinatura (SignatÃ¡rios)' => 'Dados de Assinatura (Signatários)',
    'CLÃ USULA SEGUNDA â€“ PRAZO E LOCAL DE EXECUÃ‡ÃƒO DOS SERVIÃ‡OS' => 'CLÁUSULA SEGUNDA – PRAZO E LOCAL DE EXECUÇÃO DOS SERVIÇOS',
    'CLÃ USULA QUARTA â€“ DAS ENTREGAS' => 'CLÁUSULA QUARTA – DAS ENTREGAS',
];

$erros = 0;
foreach ($testes as $mojibake => $esperado) {
    $resultado = corrigirMojibakeContrato($mojibake);
    if ($resultado === $esperado) {
        echo "[OK] '$mojibake' -> '$resultado'\n";
    } else {
        echo "[ERRO] '$mojibake' -> '$resultado' (Esperado: '$esperado')\n";
        $erros++;
    }
}

if ($erros === 0) {
    echo "\nTodos os testes passaram com sucesso! A codificação está 100% correta.\n";
} else {
    echo "\nOcorreram $erros erro(s) de codificação.\n";
}
