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

function formatarItemRico($linha) {
    $linha = trim($linha);
    if ($linha === '') return '';
    
    // Se a linha já começa com bolinha, traço ou asterisco, limpa para não duplicar
    $prefixos = ['•', '-', '*'];
    foreach ($prefixos as $p) {
        if (str_starts_with($linha, $p)) {
            $linha = trim(substr($linha, strlen($p)));
        }
    }
    
    $pos = strpos($linha, ':');
    if ($pos !== false) {
        $titulo = trim(substr($linha, 0, $pos));
        $desc = trim(substr($linha, $pos + 1));
        return "• <b>{$titulo}:</b> {$desc}";
    }
    
    return "• {$linha}";
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

    $itensHeritageDefault = "Cobertura Documental Completa: Presença ilimitada no evento. Do making of à última música, sem limite de horas.\nO Álbum Heritage: Álbum luxo panorâmico no tamanho 25x30cm (aberto 25x60cm), com papel fotográfico de alta gramatura e laminação especial.\nRéplicas para a Família (Presente): Inclusão de 02 Mini Álbuns réplicas, ideais para presentear os pais com a mesma qualidade do álbum principal.\nProdução Cinematográfica 4K: Filme completo (8 a 12 min) com áudio dos votos e trilha sonora licenciada.\nImagens Aéreas (Drone): Perspectivas cinematográficas para contextualizar o local do seu \"sim\".\nEcossistema Digital e Físico: Galeria online vitalícia, e pen drive personalizado.";
    $itensCinematicDefault = "Fotografia de Evento (8h): Cobertura focada na essência e na espontaneidade dos convidados.\nSessão Engagement (Pré-Wedding): Ensaio de até 3h para conexão do casal com a lente antes do grande dia.\nShort Film de Cinema: Filme dinâmico (5 a 7 min) com os melhores momentos da cerimônia e recepção.\nSocial Content (Story Maker): Entrega de conteúdo vertical pronto para redes sociais. Seus convidados acompanham os bastidores em tempo real.\nMaking Of Completo: Registro da preparação da noiva e do noivo, capturando a expectativa e os detalhes.\nBônus: Vídeo Save-the-Date incluso para o anúncio oficial.";
    $itensEssencialDefault = "Fotografia de Cerimônia (4h): Cobertura pontual focada no protocolo religioso e fotos protocolares de família.\nEscopo Limitado: Plano focado em registros estáticos. Não inclui vídeo, drone, cobertura de preparativos ou ensaio externo.\nEntrega Digital: Acesso à galeria online exclusiva para download das fotos editadas.";

    $itens = [];
    $itensTextoVal = '';
    if ($planoId === 'heritage') {
        $itensTextoVal = trim($dados['itens_heritage'] ?? '');
        if ($itensTextoVal === '') {
            $itensTextoVal = $itensHeritageDefault;
        }
    } elseif ($planoId === 'cinematic') {
        $itensTextoVal = trim($dados['itens_cinematic'] ?? '');
        if ($itensTextoVal === '') {
            $itensTextoVal = $itensCinematicDefault;
        }
    } elseif ($planoId === 'essencial') {
        $itensTextoVal = trim($dados['itens_essencial'] ?? '');
        if ($itensTextoVal === '') {
            $itensTextoVal = $itensEssencialDefault;
        }
    }

    foreach (preg_split('/\r\n|\r|\n/', $itensTextoVal) as $linha) {
        $linha = trim($linha);
        if ($linha !== '') $itens[] = formatarItemRico($linha);
    }
    foreach (($dados['itens_personalizados'][$planoId] ?? []) as $item) {
        if (!empty($item['nome']) && (($item['incluido'] ?? '1') !== '0')) {
            $itens[] = formatarItemRico($item['nome'] . (!empty($item['descricao']) ? ': ' . $item['descricao'] : ''));
        }
    }
    foreach (($dados['cliente_escolha']['itens_selecionados'] ?? []) as $item) {
        if ($item) $itens[] = '+ ' . $item;
    }

    $planosAtivos = [];
    
    // Heritage
    if (($dados['show_heritage'] ?? true) !== false) {
        $itensH = [];
        $itensTextoH = trim($dados['itens_heritage'] ?? '');
        if ($itensTextoH === '') {
            $itensTextoH = $itensHeritageDefault;
        }
        foreach (preg_split('/\r\n|\r|\n/', $itensTextoH) as $linha) {
            $linha = trim($linha);
            if ($linha !== '') $itensH[] = formatarItemRico($linha);
        }
        foreach (($dados['itens_personalizados']['heritage'] ?? []) as $item) {
            if (!empty($item['nome']) && (($item['incluido'] ?? '1') !== '0')) {
                $itensH[] = formatarItemRico($item['nome'] . (!empty($item['descricao']) ? ': ' . $item['descricao'] : ''));
            }
        }
        $planosAtivos[] = [
            'id' => 'heritage',
            'pacote_nome' => 'Experiencia Heritage',
            'pacote_valor' => formatarValorPdf($dados['valor_heritage'] ?? 7900),
            'pacote_itens' => implode("\n", array_unique($itensH)),
            'pacote_condicoes' => $dados['condicoes_heritage_cinematic'] ?? 'Entrada de 20% + Saldo parcelado em até 6x',
            'pacote_foto' => raizUrl('/imagens-proposta-casamento/foto-section-07.png')
        ];
    }
    
    // Cinematic
    if (($dados['show_cinematic'] ?? true) !== false) {
        $itensC = [];
        $itensTextoC = trim($dados['itens_cinematic'] ?? '');
        if ($itensTextoC === '') {
            $itensTextoC = $itensCinematicDefault;
        }
        foreach (preg_split('/\r\n|\r|\n/', $itensTextoC) as $linha) {
            $linha = trim($linha);
            if ($linha !== '') $itensC[] = formatarItemRico($linha);
        }
        foreach (($dados['itens_personalizados']['cinematic'] ?? []) as $item) {
            if (!empty($item['nome']) && (($item['incluido'] ?? '1') !== '0')) {
                $itensC[] = formatarItemRico($item['nome'] . (!empty($item['descricao']) ? ': ' . $item['descricao'] : ''));
            }
        }
        $planosAtivos[] = [
            'id' => 'cinematic',
            'pacote_nome' => 'Experiencia Cinematic',
            'pacote_valor' => formatarValorPdf($dados['valor_cinematic'] ?? 4500),
            'pacote_itens' => implode("\n", array_unique($itensC)),
            'pacote_condicoes' => $dados['condicoes_heritage_cinematic'] ?? 'Entrada de 20% + Saldo parcelado em até 6x',
            'pacote_foto' => raizUrl('/imagens-proposta-casamento/foto-section-08.png')
        ];
    }
    
    // Essencial
    if (($dados['show_essencial'] ?? true) !== false) {
        $itensE = [];
        $itensTextoE = trim($dados['itens_essencial'] ?? '');
        if ($itensTextoE === '') {
            $itensTextoE = $itensEssencialDefault;
        }
        foreach (preg_split('/\r\n|\r|\n/', $itensTextoE) as $linha) {
            $linha = trim($linha);
            if ($linha !== '') $itensE[] = formatarItemRico($linha);
        }
        foreach (($dados['itens_personalizados']['essencial'] ?? []) as $item) {
            if (!empty($item['nome']) && (($item['incluido'] ?? '1') !== '0')) {
                $itensE[] = formatarItemRico($item['nome'] . (!empty($item['descricao']) ? ': ' . $item['descricao'] : ''));
            }
        }
        $planosAtivos[] = [
            'id' => 'essencial',
            'pacote_nome' => 'Registro Essencial',
            'pacote_valor' => formatarValorPdf($dados['valor_essencial'] ?? 2800),
            'pacote_itens' => implode("\n", array_unique($itensE)),
            'pacote_condicoes' => $dados['condicoes_essencial'] ?? 'Entrada de 25% + Saldo parcelado em até 5x',
            'pacote_foto' => raizUrl('/imagens-proposta-casamento/foto-section-09.png')
        ];
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
        'planos' => $planosAtivos,
    ];
}
