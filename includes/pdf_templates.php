<?php

function garantirTabelasPdfTemplates(PDO $db): void
{
    $isMysql = defined('DB_PORT') && DB_PORT == 3306;
    $checkSql = $isMysql
        ? "SHOW TABLES LIKE 'pdf_templates'"
        : "SELECT 1 FROM information_schema.tables WHERE table_name='pdf_templates'";

    $stmt = $db->query($checkSql);
    if ($stmt->fetch()) {
        return;
    }

    $sql = $isMysql
        ? "CREATE TABLE pdf_templates (
            id VARCHAR(64) PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            tipo VARCHAR(50) NOT NULL,
            ativo TINYINT DEFAULT 0,
            config_json LONGTEXT NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
          )"
        : "CREATE TABLE pdf_templates (
            id TEXT PRIMARY KEY,
            nome TEXT NOT NULL,
            tipo TEXT NOT NULL,
            ativo INTEGER DEFAULT 0,
            config_json TEXT NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
          )";

    $db->exec($sql);
}

function templatePdfAtivo(PDO $db, string $tipo): ?array
{
    garantirTabelasPdfTemplates($db);
    $stmt = $db->prepare("SELECT * FROM pdf_templates WHERE tipo = ? AND ativo = 1 ORDER BY atualizado_em DESC LIMIT 1");
    $stmt->execute([$tipo]);
    $template = $stmt->fetch();
    if (!$template) {
        return null;
    }

    $template['config'] = json_decode($template['config_json'] ?? '{}', true) ?: [];
    return $template;
}

function formatarValorPdf($valor): string
{
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

function dadosPdfProposta(array $proposta): array
{
    $dados = json_decode($proposta['dados_json'] ?? '{}', true) ?: [];
    $nomeNoivo = trim($dados['nome_noivo'] ?? '');
    $nomeNoiva = trim($dados['nome_noiva'] ?? '');
    $nomeCasal = ($nomeNoivo && $nomeNoiva) ? "{$nomeNoivo} & {$nomeNoiva}" : ($proposta['cliente_nome'] ?? '');
    $primeiroNoivo = $nomeNoivo ? explode(' ', $nomeNoivo)[0] : '';
    $primeiraNoiva = $nomeNoiva ? explode(' ', $nomeNoiva)[0] : '';
    $saudacaoCasal = 'Ola, ' . (($primeiroNoivo && $primeiraNoiva) ? "{$primeiroNoivo} & {$primeiraNoiva}" : $nomeCasal) . '!';
    $mensagemPadrao = 'A gente sabe que fotografia e muito mais do que so apertar um botao. Nosso trabalho e capturar o que voces sentem um pelo outro, de um jeito que pareca real e sem poses forcadas.';
    $mensagemPessoal = trim($dados['mensagem_pessoal'] ?? '');
    if ($mensagemPessoal === '') {
        $mensagemPessoal = $mensagemPadrao;
    }
    $experienciasDistintasTexto = "Na Distinto, nao comecamos com ideias soltas. Comecamos com clareza.\n\n"
        . "Desenhamos tres caminhos estrategicos para que a historia de <b>{$nomeCasal}</b> seja preservada com a forca e a verdade que merecem.\n\n"
        . "Apresentamos nossas propostas de investimento. Cada uma delas foi pensada para transformar o seu casamento em uma experiencia totalmente nova, onde a nossa perspectiva artistica garante que todas as variaveis do dia ganhem o mais bonito sentido.\n\n"
        . "Escolham o caminho que melhor se conecta com o sonho de voces.\n\n"
        . "<b>Nossa meta e uma so: arrepiar.</b>";

    $planoId = $dados['cliente_escolha']['plano_id'] ?? '';
    if (!$planoId) {
        if (($dados['show_heritage'] ?? false) !== false) $planoId = 'heritage';
        elseif (($dados['show_cinematic'] ?? false) !== false) $planoId = 'cinematic';
        elseif (($dados['show_essencial'] ?? false) !== false) $planoId = 'essencial';
    }

    $nomesPlano = [
        'heritage' => 'Experiencia Heritage',
        'cinematic' => 'Experiencia Cinematic',
        'essencial' => 'Registro Essencial',
    ];

    $itens = [];
    $itensTexto = [
        'heritage' => $dados['itens_heritage'] ?? '',
        'cinematic' => $dados['itens_cinematic'] ?? '',
        'essencial' => $dados['itens_essencial'] ?? '',
    ];
    foreach (preg_split('/\r\n|\r|\n/', $itensTexto[$planoId] ?? '') as $linha) {
        $linha = trim($linha);
        if ($linha !== '') $itens[] = $linha;
    }
    foreach (($dados['itens_personalizados'][$planoId] ?? []) as $item) {
        if (!empty($item['nome']) && (($item['incluido'] ?? '1') !== '0')) {
            $itens[] = $item['nome'] . (!empty($item['descricao']) ? ': ' . $item['descricao'] : '');
        }
    }
    foreach (($dados['cliente_escolha']['itens_selecionados'] ?? []) as $item) {
        if ($item) $itens[] = '+ ' . $item;
    }

    return [
        'cliente_nome' => $proposta['cliente_nome'] ?? '',
        'titulo_proposta' => $proposta['titulo'] ?? '',
        'nome_casal' => $nomeCasal,
        'nome_noivo' => $nomeNoivo,
        'nome_noiva' => $nomeNoiva,
        'saudacao_casal' => $saudacaoCasal,
        'data_casamento' => !empty($dados['data_casamento']) ? formatarData($dados['data_casamento']) : '',
        'visao_ia' => $dados['secoes']['visao'] ?? '',
        'experiencias_distintas_texto' => $experienciasDistintasTexto,
        'pacote_escolhido' => $nomesPlano[$planoId] ?? '',
        'valor_total' => formatarValorPdf($dados['cliente_escolha']['valor_total'] ?? $proposta['valor_total'] ?? 0),
        'itens_inclusos' => implode("\n", array_unique($itens)),
        'condicoes_pagamento' => $dados['cliente_escolha']['condicoes'] ?? ($planoId === 'essencial' ? ($dados['condicoes_essencial'] ?? '') : ($dados['condicoes_heritage_cinematic'] ?? '')),
        'validade_proposta' => $dados['validade_proposta'] ?? '',
        'andamento_proposta' => $dados['andamento_proposta'] ?? '',
        'mensagem_pessoal' => $mensagemPessoal,
        'prazo_previas' => $dados['prazo_previas'] ?? '',
        'prazo_final' => $dados['prazo_final'] ?? '',
    ];
}
