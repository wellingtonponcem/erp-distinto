<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/ia_propostas.php';

exigirDistinto();
$db = Database::get();

$statusMessage = '';
$errorMessage = '';
$contrato = null;

// ---------------------------------------------------------
// CRIAÇÃO E REDIRECIONAMENTO DE CONTRATO A PARTIR DA PROPOSTA
// ---------------------------------------------------------
if (isset($_GET['proposta_id'])) {
    $propostaId = $_GET['proposta_id'];
    
    // Check if proposal exists
    $stmtP = $db->prepare("SELECT * FROM propostas WHERE id = ?");
    $stmtP->execute([$propostaId]);
    $proposta = $stmtP->fetch();
    
    if (!$proposta) {
        header('Location: ' . raizUrl('/gerenciamento/contratos.php?erro=Proposta não encontrada.'));
        exit;
    }
    
    // Check if contract already exists for this proposal
    $stmtExist = $db->prepare("SELECT * FROM contratos WHERE proposta_id = ? LIMIT 1");
    $stmtExist->execute([$propostaId]);
    $contratoExistente = $stmtExist->fetch();
    
    if ($contratoExistente) {
        if (($contratoExistente['status'] ?? 'rascunho') === 'rascunho') {
            header('Location: ' . raizUrl('/gerenciamento/contrato_gerar.php?id=' . $contratoExistente['id']));
            exit;
        } else {
            header('Location: ' . raizUrl('/gerenciamento/contrato_visualizar.php?id=' . $contratoExistente['id']));
            exit;
        }
    }
    
    // Fetch associated client if available to pre-fill CPF/CNPJ and email
    $cliente = null;
    if (!empty($proposta['cliente_id'])) {
        $stmtC = $db->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmtC->execute([$proposta['cliente_id']]);
        $cliente = $stmtC->fetch();
    }
    
    // Extract proposal dados_json
    $dadosProposta = json_decode($proposta['dados_json'], true) ?: [];
    
    // Create new contract record
    $contratoId = gerarId();
    $clienteNome = $proposta['cliente_nome'];
    $tituloContrato = "Contrato de Prestação de Serviços - " . $clienteNome;
    $valorTotal = (float)$proposta['valor_total'];
    $dataContrato = date('Y-m-d');
    $localContrato = 'Vitória/ES';
    
    // Build default Payment Conditions text
    $condicoesPagamento = 'À vista ou conforme parcelamento acordado.';
    if ($proposta['tipo'] === 'casamento') {
        $condicoesPagamento = $dadosProposta['condicoes_reserva'] ?? 'Conforme parcelamento em parcelas fixas.';
        if (!empty($dadosProposta['condicoes_heritage_cinematic'])) {
            $condicoesPagamento = $dadosProposta['condicoes_heritage_cinematic'];
        } elseif (!empty($dadosProposta['condicoes_essencial'])) {
            $condicoesPagamento = $dadosProposta['condicoes_essencial'];
        }
    }
    
    // Initialize Signatarios
    $sig1 = [
        'nome' => ($dadosProposta['nome_noiva'] ?? '') ?: ($cliente['nome'] ?? $clienteNome),
        'cpf' => $cliente['cpf_cnpj'] ?? '',
        'email' => $dadosProposta['email_contato'] ?? ($cliente['contato'] ?? ''),
        'telefone' => $dadosProposta['whatsapp'] ?? '',
        'endereco' => ''
    ];
    
    $sig2 = [
        'nome' => $dadosProposta['nome_noivo'] ?? '',
        'cpf' => '',
        'email' => '',
        'telefone' => '',
        'endereco' => ''
    ];
    
    $sigDistinto = [
        'nome' => 'Jeane Poncem',
        'email' => 'jeaneponcemsm@gmail.com',
        'telefone' => ''
    ];
    
    // Dynamic Anexo I generation via Gemini
    $anexoTexto = '';
    try {
        $dadosProposta['cliente_nome'] = $proposta['cliente_nome'];
        $dadosProposta['tipo'] = $proposta['tipo'];
        $dadosProposta['titulo'] = $proposta['titulo'];
        $dadosProposta['valor_total'] = $proposta['valor_total'];
        $anexoTexto = IAPropostas::gerarAnexoI($dadosProposta);
    } catch (Exception $e) {
        $anexoTexto = '<h4>Anexo I - Descrição dos Serviços</h4><p>Erro ao gerar descrição automática: ' . $e->getMessage() . '</p>';
    }
    
    // Build default Contract Body text
    $dataContratoPorExtenso = dataExtenso($dataContrato);
    $dataEvento = $dadosProposta['data_casamento'] ?? $dadosProposta['data_inicio'] ?? '';
    
    $temPreweddingInicial = false;
    if ($proposta['tipo'] === 'casamento') {
        $temPreweddingInicial = !empty($dadosProposta['include_prewedding'])
            || !empty($dadosProposta['include_prewedding_heritage'])
            || !empty($dadosProposta['include_prewedding_cinematic'])
            || !empty($dadosProposta['include_prewedding_essencial']);
    }
    
    $locais = [
        'tem_prewedding' => $temPreweddingInicial ? '1' : '',
        'local_prewedding' => '',
        'local_prewedding_a_definir' => '1',
        'data_prewedding' => '',
        'previsao_prewedding' => '10 dias úteis após a seleção das fotos pelo casal',
        'previsao_savethedate' => 'Até 15 dias úteis após a realização do ensaio',
        'tem_cartorio' => '',
        'local_cartorio' => '',
        'tem_cerimonia' => '1',
        'local_cerimonia' => '',
        'data_cerimonia' => $dataEvento
    ];
    
    if ($proposta['tipo'] === 'casamento') {
        // Build Clause 2 dynamically based on locais config
        $n = 1;
        $clausula2 = '<h4>CLÁUSULA SEGUNDA – PRAZO E LOCAL DE EXECUÇÃO DOS SERVIÇOS</h4>';

        if (!empty($locais['tem_prewedding'])) {
            $dataPw = !empty($locais['data_prewedding']) ? dataExtenso($locais['data_prewedding']) : 'data a ser definida em comum acordo';
            
            $localPw = 'local a ser definido em comum acordo entre as partes';
            if (empty($locais['local_prewedding_a_definir']) && !empty($locais['local_prewedding'])) {
                $localPw = htmlspecialchars($locais['local_prewedding']);
            }
            
            $clausula2 .= '<p>2.' . $n . '. Ensaio Pré-Wedding: Previsto para <strong>' . $dataPw . '</strong>, em ' . $localPw . '.</p>';
            $n++;
        }

        if (!empty($locais['tem_cartorio'])) {
            $dataCt = !empty($dataEvento) ? dataExtenso($dataEvento) : 'data a ser definida em comum acordo';
            $localCt = !empty($locais['local_cartorio']) ? htmlspecialchars($locais['local_cartorio']) : 'a definir em comum acordo entre as partes';
            $clausula2 .= '<p>2.' . $n . '. Cerimônia Civil: Prevista para <strong>' . $dataCt . '</strong>, em ' . $localCt . '.</p>';
            $n++;
        }

        if (!empty($locais['tem_cerimonia'])) {
            $dataCe = !empty($locais['data_cerimonia']) ? dataExtenso($locais['data_cerimonia']) : 'data a ser definida em comum acordo';
            $localCe = !empty($locais['local_cerimonia']) ? htmlspecialchars($locais['local_cerimonia']) : 'local a ser definido em comum acordo';
            $clausula2 .= '<p>2.' . $n . '. Cerimônia e Festa: Prevista para <strong>' . $dataCe . '</strong>, em ' . $localCe . '.</p>';
            $n++;
        }

        if ($n === 1) {
            $dataEv = !empty($dataEvento) ? dataExtenso($dataEvento) : 'data a ser definida em comum acordo';
            $clausula2 .= '<p>2.1. Os serviços serão executados na data de <strong>' . $dataEv . '</strong>, em local a definir em comum acordo entre as partes.</p>';
            $n++;
        }

        $clausula2 .= '<p>2.' . $n . '. A duração padrão da cobertura será aquela descrita e especificada no Anexo I, podendo ser ajustada mediante comum acordo entre as partes.</p>';
        $n++;
        $clausula2 .= '<p>2.' . $n . '. A CONTRATADA não se responsabiliza por atrasos ou impossibilidade de execução dos serviços decorrentes de condições climáticas adversas, falhas de energia elétrica no local do evento ou quaisquer outros fatores alheios à sua vontade, comprometendo-se, nestes casos, a remarcar a data mediante comum acordo com os CONTRATANTES.</p>';

        // Build Clause 4 dynamically based on prewedding config
        $clausula4 = '<h4>CLÁUSULA QUARTA – DAS ENTREGAS</h4>';
        $clausula4 .= '<p>4.1. A <strong>CONTRATADA</strong> entregará aos <strong>CONTRATANTES</strong> o material fotográfico e/ou audiovisual devidamente editado, conforme especificações técnicas e prazos estabelecidos no Anexo I, parte integrante deste instrumento.</p>';
        $clausula4 .= '<p>4.2. O prazo de entrega do material final será contado a partir da data de realização do evento, salvo disposição em contrário prevista no Anexo I.</p>';
        
        $c4_idx = 3;
        if (!empty($locais['tem_prewedding'])) {
            $previsaoPw = !empty($locais['previsao_prewedding']) ? htmlspecialchars($locais['previsao_prewedding']) : '10 dias úteis após a seleção das fotos pelo casal';
            $previsaoStd = !empty($locais['previsao_savethedate']) ? htmlspecialchars($locais['previsao_savethedate']) : 'Até 15 dias úteis após a realização do ensaio';
            
            $clausula4 .= '<p>4.' . $c4_idx . '. O prazo previsto para a entrega das fotos do ensaio Pré-Wedding é de <strong>' . $previsaoPw . '</strong>.</p>';
            $c4_idx++;
            $clausula4 .= '<p>4.' . $c4_idx . '. O prazo previsto para a entrega do Save the Date é de <strong>' . $previsaoStd . '</strong>.</p>';
            $c4_idx++;
        }
        
        $clausula4 .= '<p>4.' . $c4_idx . '. A <strong>CONTRATADA</strong> não se responsabiliza pela perda do material decorrente de caso fortuito ou força maior, obrigando-se, entretanto, a manter backup de segurança de todos os arquivos pelo prazo mínimo de 90 (noventa) dias após a entrega.</p>';

        $contratoTexto = "
        <h3>CONTRATO DE PRESTAÇÃO DE SERVIÇOS</h3>
        <p class=\"pdf-subtitle\">CASAMENTO</p>
        <p class=\"pdf-numero\">Nº " . date('Y') . "/" . substr($contratoId, 0, 4) . "</p>

        <p class=\"p0\">Pelo presente instrumento particular, de um lado:</p>

        <p class=\"p0\"><strong>CONTRATANTES:</strong><br>
        <strong>" . ($sig1['nome'] ?: '[Nome da Noiva]') . "</strong>, portadora do CPF nº " . ($sig1['cpf'] ?: '[CPF da Noiva]') . ", e <strong>" . ($sig2['nome'] ?: '[Nome do Noivo]') . "</strong>, portador do CPF nº " . ($sig2['cpf'] ?: '[CPF do Noivo]') . ", doravante denominados simplesmente <strong>CONTRATANTES</strong>.</p>

        <p class=\"p0\"><strong>CONTRATADA:</strong><br>
        <strong>Distinto | Poncem Studio (Poncem Studio LTDA)</strong>, CNPJ 50.168.732/0001-63, com sede na Rod. do Sol nº 2780, sala 1307, Praia de Itaparica, Vila Velha-ES, CEP 29102-020, e-mail contato@wedistinto.com, doravante denominada <strong>CONTRATADA</strong>.</p>

        <p class=\"p0\">Firmam o presente contrato de prestação de serviços, mediante cláusulas e condições a seguir:</p>

        <h4>CLÁUSULA PRIMEIRA – DO OBJETO</h4>
        <p>1.1. A <strong>CONTRATADA</strong> prestará serviços profissionais de cobertura fotográfica e/ou produção audiovisual para o casamento dos <strong>CONTRATANTES</strong>, em conformidade com o detalhamento contido no Anexo I, que integra este instrumento.</p>

        " . $clausula2 . "

        <h4>CLÁUSULA TERCEIRA – VALOR E CONDIÇÕES DE PAGAMENTO</h4>
        <p>3.1. Pela prestação dos serviços contratados, os <strong>CONTRATANTES</strong> pagarão à <strong>CONTRATADA</strong> a quantia total de <strong>R$ " . number_format($valorTotal, 2, ',', '.') . "</strong>, nas seguintes condições: " . htmlspecialchars($condicoesPagamento) . ".</p>
        <p>3.2. O pagamento será efetuado conforme cronograma acordado entre as partes, podendo ser dividido em parcelas mensais, conforme discriminado na proposta comercial aceita pelos CONTRATANTES.</p>
        <p>3.3. Em caso de atraso no pagamento de qualquer parcela, incidirá multa de 2% (dois por cento) sobre o valor da parcela em atraso, bem como juros de mora de 1% (um por cento) ao mês e correção monetária pelo IPCA.</p>

        " . $clausula4 . "


        <h4>CLÁUSULA QUINTA – DA AUTORIZAÇÃO DE IMAGEM</h4>
        <p>5.1. Os <strong>CONTRATANTES</strong> autorizam de forma expressa, irrevogável e gratuita a utilização de suas imagens capturadas durante os eventos e ensaios, para fins de divulgação de portfólio profissional da <strong>CONTRATADA</strong> em suas mídias digitais, redes sociais, site institucional e materiais promocionais, pelo período de 2 (dois) anos contados da data de realização do evento.</p>
        <p>5.2. A autorização prevista no item 5.1 abrange a reprodução, exibição, publicação e divulgação das imagens em qualquer mídia ou formato, desde que sem finalidade lucrativa direta e respeitando o decoro e a boa imagem dos CONTRATANTES.</p>
        <p>5.3. Caso os <strong>CONTRATANTES</strong> desejem restringir a divulgação de imagens específicas, deverão comunicar a <strong>CONTRATADA</strong> por escrito em até 15 (quinze) dias após a data do evento.</p>

        <h4>CLÁUSULA SEXTA – DAS OBRIGAÇÕES DA CONTRATADA</h4>
        <p>6.1. Prestar os serviços contratados com zelo profissional, utilizando equipamentos adequados e profissionais qualificados de sua inteira confiança.<br>
        6.2. Comparecer ao local do evento com antecedência mínima necessária para preparação e montagem dos equipamentos.<br>
        6.3. Disponibilizar aos CONTRATANTES os contatos telefônicos e de WhatsApp da equipe escalada para o dia do evento.<br>
        6.4. Manter sigilo absoluto sobre as informações pessoais e dados compartilhados pelos CONTRATANTES no âmbito da prestação dos serviços.</p>

        <h4>CLÁUSULA SÉTIMA – DAS OBRIGAÇÕES DOS CONTRATANTES</h4>
        <p>7.1. Fornecer alimentação adequada para a equipe de captação caso o tempo total do evento exceda 4 (quatro) horas.<br>
        7.2. Garantir o livre trânsito dos fotógrafos e cinegrafistas no local do evento.<br>
        7.3. Efetuar os pagamentos rigorosamente em dia, conforme cronograma acordado.<br>
        7.4. Disponibilizar os convites e credenciais necessários para acesso da equipe aos locais dos eventos.<br>
        7.5. Informar a <strong>CONTRATADA</strong> com antecedência mínima de 48 (quarenta e oito) horas sobre qualquer alteração de horário ou local dos eventos.</p>

        <h4>CLÁUSULA OITAVA – DA CESSÃO</h4>
        <p>8.1. A <strong>CONTRATADA</strong> poderá ceder ou subcontratar total ou parcialmente os serviços objeto deste contrato a terceiros de sua confiança, mantendo-se como única responsável perante os CONTRATANTES pela fiel execução do objeto contratado.</p>
        <p>8.2. Os <strong>CONTRATANTES</strong> não poderão ceder ou transferir a terceiros os direitos e obrigações decorrentes deste contrato sem a prévia e expressa autorização por escrito da CONTRATADA.</p>


        <h4>CLÁUSULA NONA – DA RESCISÃO CONTRATUAL E MULTAS</h4>
        <p>9.1. Em caso de cancelamento unilateral imotivado por parte dos <strong>CONTRATANTES</strong> com menos de 30 (trinta) dias da data do evento, nenhum valor pago a título de sinal ou reserva será reembolsado, configurando-se como cláusula penal de natureza compensatória.</p>
        <p>9.2. Em caso de cancelamento com antecedência superior a 30 (trinta) dias, os valores já pagos serão devolvidos deduzindo-se o percentual de 20% (vinte por cento) a título de multa compensatória pela reserva de data e custos administrativos já incorridos.</p>
        <p>9.3. Em descumprimento de quaisquer outras cláusulas deste contrato, incidirá multa penal de 10% (dez por cento) sobre o valor remanescente do instrumento, sem prejuízo de perdas e danos.</p>
        <p>9.4. A <strong>CONTRATADA</strong> poderá rescindir o contrato de pleno direito caso os <strong>CONTRATANTES</strong> descumpram com as obrigações pecuniárias aqui assumidas, ficando autorizada a reter os valores eventualmente já recebidos a título de indenização mínima.</p>

        <h4>CLÁUSULA DÉCIMA – DISPOSIÇÕES GERAIS</h4>
        <p>10.1. O presente instrumento não gera vínculo de natureza empregatícia entre as partes contratantes, nem solidariedade trabalhista ou previdenciária.</p>
        <p>10.2. As partes elegem o Anexo I como parte integrante e indissociável deste contrato para todos os fins de direito.</p>
        <p>10.3. Qualquer alteração neste instrumento deverá ser feita por escrito, mediante aditivo contratual assinado por ambas as partes.</p>
        <p>10.4. A tolerância ao descumprimento de qualquer cláusula ou condição deste contrato não constituirá novação ou precedente, nem afetará o exercício posterior do direito pela parte inocente.</p>
        <p>10.5. As partes se comprometem a buscar uma solução amigável, por meio de negociação direta, antes de recorrer a qualquer via judicial para resolução de eventuais controvérsias.</p>


        <h4>CLÁUSULA DÉCIMA PRIMEIRA – DO FORO</h4>
        <p>11.1. Fica eleito o foro da Comarca de Vitória/ES para dirimir quaisquer dúvidas ou controvérsias decorrentes do presente contrato, com expressa renúncia a qualquer outro, por mais privilegiado que seja.</p>



        <p class=\"p-closing\">Vitória/ES, " . $dataContratoPorExtenso . ".</p>
        ";
    } else {
        // Marketing / Filmmaker / Corporate template
        $contratoTexto = "
        <h3 style=\"text-align: center;\">CONTRATO DE PRESTAÇÃO DE SERVIÇOS PROFISSIONAIS</h3>
        <p style=\"text-align: center;\"><strong>Nº " . date('Y') . "/" . substr($contratoId, 0, 4) . "</strong></p>

        <p>Pelo presente instrumento particular, de um lado:</p>

        <p><strong>CONTRATANTE:</strong><br>
        <strong>" . ($sig1['nome'] ?: '[Nome da Empresa / Cliente]') . "</strong>, inscrita sob CPF/CNPJ nº " . ($sig1['cpf'] ?: '[Documento]') . ", sediada/residente em [Endereço], representada por " . ($sig1['nome'] ?: '[Responsável]') . ", doravante denominada <strong>CONTRATANTE</strong>.</p>

        <p><strong>CONTRATADA:</strong><br>
        <strong>Distinto | Poncem Studio (Poncem Studio LTDA)</strong>, CNPJ 50.168.732/0001-63, com sede na Rod. do Sol nº 2780, sala 1307, Praia de Itaparica, Vila Velha-ES, CEP 29102-020, e-mail contato@wedistinto.com, doravante denominada <strong>CONTRATADA</strong>.</p>

        <p>Firmam o presente contrato de prestação de serviços, mediante cláusulas e condições a seguir:</p>

        <h4>CLÁUSULA PRIMEIRA – DO OBJETO</h4>
        <p>1.1. O objeto deste contrato é a prestação de serviços de marketing digital, consultoria de posicionamento e/ou produção audiovisual para a <strong>CONTRATANTE</strong>, conforme especificações operacionais e prazos descritos no Anexo I.</p>

        <h4>CLÁUSULA SEGUNDA – VIGÊNCIA</h4>
        <p>2.1. O presente contrato terá vigência de <strong>" . ($dadosProposta['meses_contrato'] ?? 12) . " meses</strong>, com início em <strong>" . ($dadosProposta['data_inicio'] ? date('d/m/Y', strtotime($dadosProposta['data_inicio'])) : date('d/m/Y')) . "</strong>.</p>

        <h4>CLÁUSULA TERCEIRA – VALOR E CONDIÇÕES DE PAGAMENTO</h4>
        <p>3.1. Pela execução dos serviços, a <strong>CONTRATANTE</strong> pagará à <strong>CONTRATADA</strong> a quantia mensal/total de <strong>R$ " . number_format($valorTotal, 2, ',', '.') . "</strong>, nas seguintes condições: " . htmlspecialchars($condicoesPagamento) . ".</p>

        <h4>CLÁUSULA QUARTA – DIREITOS AUTORAIS E PORTFÓLIO</h4>
        <p>4.1. Fica expressamente reservado à <strong>CONTRATADA</strong> o direito de expor as peças criadas e campanhas veiculadas sob a marca da <strong>CONTRATANTE</strong> em seu próprio portfólio comercial, redes sociais e cases de marketing, respeitando a confidencialidade de dados econômicos internos.</p>

        <h4>CLÁUSULA QUINTA – OBRIGAÇÕES DAS PARTES</h4>
        <p>5.1. <strong>DA CONTRATADA:</strong> Executar as tarefas descritas no Anexo I com qualidade técnica, prestar contas mensais e manter sigilo absoluto sobre estratégias comerciais da Contratante.<br>
        5.2. <strong>DO CONTRATANTE:</strong> Fornecer feedbacks operacionais em até 48 horas, fornecer senhas e acessos a contas de publicidade necessários e honrar o calendário de pagamentos.</p>

        <h4>CLÁUSULA SEXTA – RESCISÃO ANTECIPADA</h4>
        <p>6.1. Qualquer das partes poderá rescindir o contrato antes da vigência plena, mediante aviso prévio por escrito de 30 (trinta) dias. No caso de rescisão antecipada imotivada por iniciativa do Contratante, incidirá multa contratual de 10% sobre o saldo devedor remanescente das parcelas futuras.</p>

        <h4>CLÁUSULA SÉTIMA – FORO</h4>
        <p>7.1. Fica eleito o foro da Comarca de Vitória/ES para solucionar qualquer divergência oriunda deste instrumento comercial.</p>

        <p>Vitória/ES, " . $dataContratoPorExtenso . ".</p>
        ";
    }
    
    // Save new draft contract
    $dadosJson = json_encode([
        'contrato_texto' => $contratoTexto,
        'anexo_texto' => $anexoTexto,
        'signatario_1' => $sig1,
        'signatario_2' => $sig2,
        'signatario_distinto' => $sigDistinto,
        'data_evento' => $dataEvento,
        'local_evento' => '',
        'locais' => $locais,
        'vigencia_meses' => $dadosProposta['meses_contrato'] ?? ''
    ], JSON_UNESCAPED_UNICODE);

    $stmtInsert = $db->prepare("
        INSERT INTO contratos (id, proposta_id, cliente_id, cliente_nome, titulo, valor_total, condicoes_pagamento, data_contrato, local_contrato, status, dados_json)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtInsert->execute([
        $contratoId,
        $propostaId,
        $proposta['cliente_id'],
        $clienteNome,
        $tituloContrato,
        $valorTotal,
        $condicoesPagamento,
        $dataContrato,
        $localContrato,
        'rascunho',
        $dadosJson
    ]);
    
    header('Location: ' . raizUrl('/gerenciamento/contrato_gerar.php?id=' . $contratoId));
    exit;
}

// ---------------------------------------------------------
// CARREGAR E ATUALIZAR DADOS DO CONTRATO
// ---------------------------------------------------------
$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: ' . raizUrl('/gerenciamento/contratos.php'));
    exit;
}

$stmtContrato = $db->prepare("SELECT * FROM contratos WHERE id = ?");
$stmtContrato->execute([$id]);
$contrato = $stmtContrato->fetch();

if (!$contrato) {
    header('Location: ' . raizUrl('/gerenciamento/contratos.php?erro=Contrato não encontrado.'));
    exit;
}

// Load proposta for tipo check
$stmtP = $db->prepare("SELECT * FROM propostas WHERE id = ?");
$stmtP->execute([$contrato['proposta_id']]);
$proposta = $stmtP->fetch();

if (($contrato['status'] ?? 'rascunho') !== 'rascunho') {
    header('Location: ' . raizUrl('/gerenciamento/contrato_visualizar.php?id=' . $contrato['id']));
    exit;
}

$dadosJson = json_decode($contrato['dados_json'], true) ?: [];
$sig1 = $dadosJson['signatario_1'] ?? ['nome' => '', 'cpf' => '', 'email' => '', 'telefone' => '', 'endereco' => ''];
$sig2 = $dadosJson['signatario_2'] ?? ['nome' => '', 'cpf' => '', 'email' => '', 'telefone' => '', 'endereco' => ''];
$sigDistinto = $dadosJson['signatario_distinto'] ?? ['nome' => 'Jeane Poncem', 'email' => 'jeaneponcemsm@gmail.com', 'telefone' => ''];
$dataEvento = $dadosJson['data_evento'] ?? '';
$localEvento = $dadosJson['local_evento'] ?? '';
$vigenciaMeses = $dadosJson['vigencia_meses'] ?? '';
$locais = $dadosJson['locais'] ?? [];
$locais = array_merge([
    'tem_prewedding' => '',
    'local_prewedding' => '',
    'local_prewedding_a_definir' => '',
    'data_prewedding' => '',
    'previsao_prewedding' => '10 dias úteis após a seleção das fotos pelo casal',
    'previsao_savethedate' => 'Até 15 dias úteis após a realização do ensaio',
    'tem_cartorio' => '',
    'local_cartorio' => '',
    'tem_cerimonia' => '',
    'local_cerimonia' => '',
    'data_cerimonia' => ''
], $locais);
$contratoTexto = $dadosJson['contrato_texto'] ?? '';
$anexoTexto = $dadosJson['anexo_texto'] ?? '';
$dataContratoPorExtenso = dataExtenso($contrato['data_contrato'] ?? date('Y-m-d'));

// Save / POST Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = sanitizar($_POST['titulo'] ?? $contrato['titulo']);
    $valorTotal = (float)str_replace(['.', ','], ['', '.'], $_POST['valor_total'] ?? $contrato['valor_total']);
    $condicoesPagamento = $_POST['condicoes_pagamento'] ?? '';
    $dataContrato = $_POST['data_contrato'] ?? date('Y-m-d');
    $localContrato = sanitizar($_POST['local_contrato'] ?? 'Vitória/ES');
    
    $sig1 = [
        'nome' => sanitizar($_POST['sig1_nome'] ?? ''),
        'cpf' => sanitizar($_POST['sig1_cpf'] ?? ''),
        'email' => sanitizar($_POST['sig1_email'] ?? ''),
        'telefone' => sanitizar($_POST['sig1_telefone'] ?? ''),
        'endereco' => sanitizar($_POST['sig1_endereco'] ?? '')
    ];
    
    $sig2 = [
        'nome' => sanitizar($_POST['sig2_nome'] ?? ''),
        'cpf' => sanitizar($_POST['sig2_cpf'] ?? ''),
        'email' => sanitizar($_POST['sig2_email'] ?? ''),
        'telefone' => sanitizar($_POST['sig2_telefone'] ?? ''),
        'endereco' => sanitizar($_POST['sig2_endereco'] ?? '')
    ];
    
    $sigDistinto = [
        'nome' => sanitizar($_POST['sig_distinto_nome'] ?? 'Jeane Poncem'),
        'email' => sanitizar($_POST['sig_distinto_email'] ?? 'jeaneponcemsm@gmail.com'),
        'telefone' => sanitizar($_POST['sig_distinto_telefone'] ?? '')
    ];
    
    $dataEvento = $_POST['data_evento'] ?? '';
    $localEvento = sanitizar($_POST['local_evento'] ?? '');
    $vigenciaMeses = sanitizar($_POST['vigencia_meses'] ?? '');
    $contratoTexto = $_POST['contrato_texto'] ?? '';
    $anexoTexto = $_POST['anexo_texto'] ?? '';
    $locais = [
        'tem_prewedding' => isset($_POST['tem_prewedding']) ? '1' : '',
        'local_prewedding' => sanitizar($_POST['local_prewedding'] ?? ''),
        'local_prewedding_a_definir' => isset($_POST['local_prewedding_a_definir']) ? '1' : '',
        'data_prewedding' => sanitizar($_POST['data_prewedding'] ?? ''),
        'previsao_prewedding' => sanitizar($_POST['previsao_prewedding'] ?? ''),
        'previsao_savethedate' => sanitizar($_POST['previsao_savethedate'] ?? ''),
        'tem_cartorio' => isset($_POST['tem_cartorio']) ? '1' : '',
        'local_cartorio' => sanitizar($_POST['local_cartorio'] ?? ''),
        'tem_cerimonia' => isset($_POST['tem_cerimonia']) ? '1' : '',
        'local_cerimonia' => sanitizar($_POST['local_cerimonia'] ?? ''),
        'data_cerimonia' => sanitizar($_POST['data_cerimonia'] ?? '')
    ];

    // Se for casamento, sincronizar dinamicamente os parágrafos de pré-wedding na Cláusula Quarta do HTML
    if (($proposta['tipo'] ?? '') === 'casamento' && !empty($contratoTexto)) {
        if (preg_match('/(<h4>CLÁUSULA QUARTA.*?<\/h4>)(.*?)(?=<h4>CLÁUSULA QUINTA|<p class="p-closing"|$)/is', $contratoTexto, $matches)) {
            $header = $matches[1];
            $conteudo = $matches[2];
            
            preg_match_all('/<p>.*?<\/p>|<p\s[^>]*>.*?<\/p>/is', $conteudo, $pMatches);
            $paragrafos = $pMatches[0] ?? [];
            
            $p1 = null;
            $p2 = null;
            $pBackup = null;
            
            foreach ($paragrafos as $p) {
                if (strpos($p, 'material fotográfico e/ou audiovisual') !== false) {
                    $p1 = $p;
                } elseif (strpos($p, 'prazo de entrega do material final') !== false) {
                    $p2 = $p;
                } elseif (strpos($p, 'perda do material decorrente de caso') !== false || strpos($p, 'backup de segurança') !== false) {
                    $pBackup = $p;
                }
            }
            
            if (!$p1 && isset($paragrafos[0])) $p1 = $paragrafos[0];
            if (!$p2 && isset($paragrafos[1])) $p2 = $paragrafos[1];
            if (!$pBackup) {
                $pBackup = end($paragrafos);
            }
            
            $novosParagrafos = [];
            $novosParagrafos[] = preg_replace('/^\s*(?:<p>|<p\s[^>]*>)\s*4\.1\.\s*/i', '<p>4.1. ', $p1 ?: '<p>4.1. A <strong>CONTRATADA</strong> entregará aos <strong>CONTRATANTES</strong> o material fotográfico e/ou audiovisual devidamente editado, conforme especificações técnicas e prazos estabelecidos no Anexo I, parte integrante deste instrumento.</p>');
            
            $novosParagrafos[] = preg_replace('/^\s*(?:<p>|<p\s[^>]*>)\s*4\.2\.\s*/i', '<p>4.2. ', $p2 ?: '<p>4.2. O prazo de entrega do material final será contado a partir da data de realização do evento, salvo disposição em contrário prevista no Anexo I.</p>');
            
            $idx = 3;
            if (!empty($locais['tem_prewedding'])) {
                $previsaoPw = !empty($locais['previsao_prewedding']) ? htmlspecialchars($locais['previsao_prewedding']) : '10 dias úteis após a seleção das fotos pelo casal';
                $previsaoStd = !empty($locais['previsao_savethedate']) ? htmlspecialchars($locais['previsao_savethedate']) : 'Até 15 dias úteis após a realização do ensaio';
                
                $novosParagrafos[] = "<p>4.{$idx}. O prazo previsto para a entrega das fotos do ensaio Pré-Wedding é de <strong>{$previsaoPw}</strong>.</p>";
                $idx++;
                $novosParagrafos[] = "<p>4.{$idx}. O prazo previsto para a entrega do Save the Date é de <strong>{$previsaoStd}</strong>.</p>";
                $idx++;
            }
            
            $pBackupLimpo = preg_replace('/^\s*(?:<p>|<p\s[^>]*>)\s*4\.[0-9]+\.\s*/i', '', $pBackup ?: '<p>A <strong>CONTRATADA</strong> não se responsabiliza pela perda do material decorrente de caso fortuito ou força maior, obrigando-se, entretanto, a manter backup de segurança de todos os arquivos pelo prazo mínimo de 90 (noventa) dias após a entrega.</p>');
            if (strpos($pBackupLimpo, '<p>') === false && strpos($pBackupLimpo, '<p ') === false) {
                $pBackupLimpo = '<p>' . $pBackupLimpo;
            }
            $novosParagrafos[] = preg_replace('/^\s*<p>/i', "<p>4.{$idx}. ", $pBackupLimpo);
            
            $novoConteudo = "\n        " . implode("\n        ", $novosParagrafos) . "\n        ";
            $contratoTexto = str_replace($matches[0], $header . $novoConteudo, $contratoTexto);
        }
    }

    // We save the contract text directly as edited by the user, without regenerating the template.
    
    // Re-pack dados_json
    $dadosJsonUpdated = json_encode([
        'contrato_texto' => $contratoTexto,
        'anexo_texto' => $anexoTexto,
        'signatario_1' => $sig1,
        'signatario_2' => $sig2,
        'signatario_distinto' => $sigDistinto,
        'data_evento' => $dataEvento,
        'local_evento' => $localEvento,
        'vigencia_meses' => $vigenciaMeses,
        'locais' => $locais
    ], JSON_UNESCAPED_UNICODE);
    
    // Save to Database
    $clienteNomeForm = $sig1['nome'];
    if (!empty($sig2['nome'])) {
        $clienteNomeForm .= ' & ' . $sig2['nome'];
    }
    
    try {
        $stmtUpdate = $db->prepare("
            UPDATE contratos
            SET cliente_nome = ?, titulo = ?, valor_total = ?, condicoes_pagamento = ?, data_contrato = ?, local_contrato = ?, dados_json = ?
            WHERE id = ?
        ");
        $stmtUpdate->execute([
            $clienteNomeForm,
            $titulo,
            $valorTotal,
            $condicoesPagamento,
            $dataContrato,
            $localContrato,
            $dadosJsonUpdated,
            $id
        ]);
        
        header('Location: ' . raizUrl('/gerenciamento/contrato_visualizar.php?id=' . $id));
        exit;
    } catch (Exception $e) {
        $errorMessage = 'Erro ao salvar contrato: ' . $e->getMessage();
    }
}

$tituloPagina = 'Editar Contrato';
require_once __DIR__ . '/../includes/layout/head.php';
?>
<div id="app-wrapper" x-data="contratoGerarApp()">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>
    <main id="main-content" class="content-sheet flex flex-col min-h-screen !bg-[#050505] !text-white">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between border-b border-white/5 pb-6">
            <div>
                <div class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-2">Comercial / Contratos</div>
                <h1 class="text-3xl font-black tracking-tight text-white flex items-center gap-3">
                    <i data-lucide="edit-3" class="w-8 h-8 text-zinc-400"></i>
                    Editar Minuta de Contrato
                </h1>
                <p class="text-sm font-medium text-zinc-400 mt-1">Refine as cláusulas, preencha os dados dos signatários e use IA para otimizar os termos.</p>
            </div>
            
            <div class="flex items-center gap-4">
                <button type="submit" form="contrato-form" class="px-5 py-2.5 bg-white text-black hover:bg-zinc-200 active:scale-95 transition-all text-xs font-black uppercase tracking-widest rounded-xl flex items-center gap-2 cursor-pointer">
                    <i data-lucide="check" class="w-4 h-4"></i> Salvar Minuta
                </button>
                <a href="<?= raizUrl('/gerenciamento/contratos.php') ?>" class="flex items-center gap-2 text-zinc-400 hover:text-white transition-colors text-xs font-bold uppercase tracking-wider">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Voltar à Lista
                </a>
            </div>
        </div>

        <?php if ($errorMessage): ?>
            <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm font-bold flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                <?= sanitizar($errorMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Form Layout -->
        <form method="post" action="<?= raizUrl('/gerenciamento/contrato_gerar.php?id=' . $id) ?>" id="contrato-form">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Coluna 1 e 2: Editores de Texto e Cláusulas -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Editor do Contrato Principal -->
                    <div class="bg-zinc-900/50 border border-white/5 rounded-[32px] p-8">
                        <h2 class="text-lg font-bold text-white mb-6 flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <i data-lucide="file-text" class="w-5 h-5 opacity-50"></i>
                                Corpo do Contrato
                            </span>
                            <span class="text-[10px] font-black uppercase tracking-widest text-zinc-500">Editável em HTML</span>
                        </h2>
                        
                        <div class="space-y-2 prose prose-invert max-w-none text-black">
                            <textarea id="contrato_texto" name="contrato_texto"><?= $contratoTexto ?></textarea>
                        </div>
                    </div>

                    <!-- Editor do Anexo I -->
                    <div class="bg-zinc-900/50 border border-white/5 rounded-[32px] p-8">
                        <h2 class="text-lg font-bold text-white mb-6 flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <i data-lucide="paperclip" class="w-5 h-5 opacity-50"></i>
                                Anexo I - Descrição dos Serviços
                            </span>
                            
                            <?php if ($contrato['proposta_id']): ?>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="gerarAnexoIA()" :disabled="iaAnexoLoading" 
                                            class="px-4 py-1.5 bg-zinc-800 text-zinc-200 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-white hover:text-black active:scale-95 transition-all flex items-center gap-1.5 disabled:opacity-50">
                                        <template x-if="!iaAnexoLoading">
                                            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                        </template>
                                        <template x-if="iaAnexoLoading">
                                            <svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg>
                                        </template>
                                        <span x-text="iaAnexoLoading ? 'Gerando...' : 'Reescrever via IA'"></span>
                                    </button>

                                    <button type="button" @click="atualizarAnexo()" :disabled="iaSaveLoading"
                                            class="px-4 py-1.5 bg-zinc-800 text-zinc-200 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-white hover:text-black active:scale-95 transition-all flex items-center gap-1.5 disabled:opacity-50">
                                        <template x-if="!iaSaveLoading">
                                            <i data-lucide="save" class="w-3.5 h-3.5"></i>
                                        </template>
                                        <template x-if="iaSaveLoading">
                                            <svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg>
                                        </template>
                                        <span x-text="iaSaveLoading ? 'Salvando...' : 'Atualizar Anexo'"></span>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </h2>
                        
                        <div class="space-y-2 text-black">
                            <textarea id="anexo_texto" name="anexo_texto"><?= $anexoTexto ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Coluna 3: Metadados, Signatários e Copilot -->
                <div class="space-y-8">
                    <!-- Informações Gerais do Contrato -->
                    <div class="bg-zinc-900/50 border border-white/5 rounded-[32px] p-8">
                        <h2 class="text-md font-bold text-white mb-6 flex items-center gap-2">
                            <i data-lucide="settings" class="w-4.5 h-4.5 opacity-50"></i>
                            Geral e Financeiro
                        </h2>
                        
                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Título Interno do Contrato</label>
                                <input type="text" name="titulo" value="<?= sanitizar($contrato['titulo']) ?>" required
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-3 text-xs text-white focus:border-white transition-all outline-none">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Valor Total (R$)</label>
                                    <input type="text" name="valor_total" value="<?= number_format($contrato['valor_total'], 2, ',', '.') ?>" required
                                           class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-3 text-xs text-white focus:border-white transition-all outline-none">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Data do Contrato</label>
                                    <input type="date" name="data_contrato" value="<?= $contrato['data_contrato'] ?: date('Y-m-d') ?>" required
                                           class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-3 text-xs text-white focus:border-white transition-all outline-none">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Data do Evento/Início</label>
                                    <input type="date" name="data_evento" value="<?= $dataEvento ?>"
                                           class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-3 text-xs text-white focus:border-white transition-all outline-none">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Cidade/UF Emissão</label>
                                    <input type="text" name="local_contrato" value="<?= sanitizar($contrato['local_contrato'] ?: 'Vitória/ES') ?>" required
                                           class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-3 text-xs text-white focus:border-white transition-all outline-none">
                                </div>
                            </div>

                            <!-- Calculadora de Condições de Pagamento -->
                            <div class="p-5 bg-black/40 border border-white/5 rounded-2xl space-y-4 mb-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-[10px] font-black uppercase tracking-widest text-zinc-400">Calculadora de Condições de Pagamento</div>
                                    <span class="text-[8px] font-bold text-zinc-600">Cálculo Automático</span>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-1">
                                        <label class="text-[8px] font-bold uppercase tracking-wider text-zinc-500">Sinal / Entrada (%)</label>
                                        <input type="number" id="calc_sinal_pct" value="20" min="0" max="100" class="w-full bg-black/60 border border-white/5 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-white transition-colors">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[8px] font-bold uppercase tracking-wider text-zinc-500">Vencimento do Sinal</label>
                                        <input type="date" id="calc_sinal_data" value="<?= date('Y-m-d') ?>" class="w-full bg-black/60 border border-white/5 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-white transition-colors">
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label class="text-[8px] font-bold uppercase tracking-wider text-zinc-500">Meio de Pagamento</label>
                                    <select id="calc_meio" onchange="toggleMeioPagamento()" class="w-full bg-black/60 border border-white/5 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-white transition-colors">
                                        <option value="Pix ou Boleto Bancário">Pix ou Boleto Bancário</option>
                                        <option value="Pix">Pix</option>
                                        <option value="Boleto Bancário">Boleto Bancário</option>
                                        <option value="Cartão de Crédito">Cartão de Crédito</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <!-- Bloco do último pagamento (Pix/Boleto) -->
                                    <div class="space-y-1 col-span-2" id="calc_ultimo_data_wrap">
                                        <label class="text-[8px] font-bold uppercase tracking-wider text-zinc-500">Data do Último Pagamento</label>
                                        <input type="date" id="calc_ultimo_data" value="<?= date('Y-m-d', strtotime('+5 months')) ?>" class="w-full bg-black/60 border border-white/5 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-white transition-colors">
                                    </div>
                                    
                                    <!-- Bloco de parcelas (Cartão de Crédito) -->
                                    <div class="space-y-1 col-span-2 hidden" id="calc_parcelas_wrap">
                                        <label class="text-[8px] font-bold uppercase tracking-wider text-zinc-500">Qtd. de Parcelas (Cartão)</label>
                                        <select id="calc_parcelas" class="w-full bg-black/60 border border-white/5 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-white transition-colors">
                                            <?php for ($i = 1; $i <= 24; $i++): ?>
                                                <option value="<?= $i ?>" <?= $i === 5 ? 'selected' : '' ?>><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?> parcelas</option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>

                                <button type="button" onclick="calcularCondicoes()" class="w-full py-2.5 bg-zinc-800 hover:bg-white text-zinc-300 hover:text-black active:scale-95 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all cursor-pointer">
                                    Calcular e Preencher Condições
                                </button>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Condições de Pagamento (Texto do Contrato)</label>
                                <textarea name="condicoes_pagamento" id="condicoes_pagamento" rows="4"
                                          class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-3 text-xs text-white focus:border-white transition-all outline-none resize-none"><?= sanitizar($contrato['condicoes_pagamento']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Signatários -->
                    <div class="bg-zinc-900/50 border border-white/5 rounded-[32px] p-8">
                        <h2 class="text-md font-bold text-white mb-6 flex items-center gap-2">
                            <i data-lucide="users" class="w-4.5 h-4.5 opacity-50"></i>
                            Dados de Assinatura (Signatários)
                        </h2>
                        
                        <!-- Signatário 1 -->
                        <div class="space-y-4 mb-6 pb-6 border-b border-white/5">
                            <div class="text-[9px] font-black text-white uppercase tracking-widest">Signatário 1 (Noiva / Contratante)</div>
                            
                            <div class="space-y-2">
                                <input type="text" name="sig1_nome" value="<?= sanitizar($sig1['nome']) ?>" placeholder="Nome Completo *" required
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                                <input type="text" name="sig1_cpf" value="<?= sanitizar($sig1['cpf']) ?>" placeholder="CPF / CNPJ *" required
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                                <input type="email" name="sig1_email" value="<?= sanitizar($sig1['email']) ?>" placeholder="E-mail de Assinatura *" required
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                                <input type="text" name="sig1_telefone" value="<?= sanitizar($sig1['telefone']) ?>" placeholder="WhatsApp / Telefone"
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                                <input type="text" name="sig1_endereco" value="<?= sanitizar($sig1['endereco']) ?>" placeholder="Endereço Residencial"
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                            </div>
                        </div>

                        <!-- Signatário 2 (Casamentos) -->
                        <div class="space-y-4 pb-6 border-b border-white/5">
                            <div class="text-[9px] font-black text-white uppercase tracking-widest">Signatário 2 (Noivo / Opcional)</div>
                            
                            <div class="space-y-2">
                                <input type="text" name="sig2_nome" value="<?= sanitizar($sig2['nome'] ?? '') ?>" placeholder="Nome Completo"
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                                <input type="text" name="sig2_cpf" value="<?= sanitizar($sig2['cpf'] ?? '') ?>" placeholder="CPF"
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                                <input type="email" name="sig2_email" value="<?= sanitizar($sig2['email'] ?? '') ?>" placeholder="E-mail de Assinatura"
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                                <input type="text" name="sig2_telefone" value="<?= sanitizar($sig2['telefone'] ?? '') ?>" placeholder="WhatsApp"
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                                <input type="text" name="sig2_endereco" value="<?= sanitizar($sig2['endereco'] ?? '') ?>" placeholder="Endereço Residencial"
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                            </div>
                        </div>

                        <!-- Signatário Distinto (Contratada) -->
                        <div class="space-y-4 mt-6">
                            <div class="text-[9px] font-black text-white uppercase tracking-widest">Signatário Distinto (Contratada / Padrão)</div>
                            
                            <div class="space-y-2">
                                <input type="text" name="sig_distinto_nome" value="<?= sanitizar($sigDistinto['nome'] ?? 'Jeane Poncem') ?>" placeholder="Nome Completo *" required
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                                <input type="email" name="sig_distinto_email" value="<?= sanitizar($sigDistinto['email'] ?? 'jeaneponcemsm@gmail.com') ?>" placeholder="E-mail de Assinatura *" required
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                                <input type="text" name="sig_distinto_telefone" value="<?= sanitizar($sigDistinto['telefone'] ?? '') ?>" placeholder="WhatsApp"
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- Locais do Casamento -->
                    <div class="bg-zinc-900/50 border border-white/5 rounded-[32px] p-8">
                        <h2 class="text-md font-bold text-white mb-6 flex items-center gap-2">
                            <i data-lucide="map-pin" class="w-4.5 h-4.5 opacity-50"></i>
                            Locais do Casamento
                        </h2>

                        <div class="space-y-5">
                            <!-- Pre-Wedding -->
                            <div class="flex items-center gap-3">
                                <input type="checkbox" name="tem_prewedding" value="1" <?= !empty($locais['tem_prewedding']) ? 'checked' : '' ?>
                                       class="w-4 h-4 rounded accent-white">
                                <label class="text-[9px] font-black uppercase tracking-widest text-zinc-400 flex-1">Ensaio Pré-Wedding</label>
                            </div>
                            <div class="space-y-1.5" id="local_prewedding_wrap" style="<?= empty($locais['tem_prewedding']) ? 'display:none' : '' ?>">
                                <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Data do Pré-Wedding</label>
                                <input type="date" name="data_prewedding" value="<?= sanitizar($locais['data_prewedding'] ?? '') ?>"
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                                <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Local do Pré-Wedding</label>
                                <input type="text" name="local_prewedding" value="<?= sanitizar($locais['local_prewedding'] ?? '') ?>" placeholder="Endereço ou 'a definir'"
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                                <div class="flex items-center gap-2 mt-1 mb-2">
                                    <input type="checkbox" name="local_prewedding_a_definir" id="local_prewedding_a_definir" value="1" <?= !empty($locais['local_prewedding_a_definir']) ? 'checked' : '' ?>
                                           class="w-3.5 h-3.5 rounded accent-white">
                                    <label for="local_prewedding_a_definir" class="text-[9px] font-bold text-zinc-400 cursor-pointer">A definir em comum acordo entre as partes</label>
                                </div>
                                <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Previsão de Entrega das Fotos</label>
                                <input type="text" name="previsao_prewedding" value="<?= sanitizar($locais['previsao_prewedding'] ?? '') ?>" placeholder="Ex: 10 dias úteis após a seleção das fotos pelo casal"
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                                <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Previsão de Entrega do Save the Date</label>
                                <input type="text" name="previsao_savethedate" value="<?= sanitizar($locais['previsao_savethedate'] ?? '') ?>" placeholder="Ex: Até 15 dias úteis após a realização do ensaio"
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                            </div>

                            <!-- Cartório -->
                            <div class="flex items-center gap-3 mt-4">
                                <input type="checkbox" name="tem_cartorio" value="1" <?= !empty($locais['tem_cartorio']) ? 'checked' : '' ?>
                                       class="w-4 h-4 rounded accent-white">
                                <label class="text-[9px] font-black uppercase tracking-widest text-zinc-400 flex-1">Cartório Civil</label>
                            </div>
                            <div class="space-y-1.5" id="local_cartorio_wrap" style="<?= empty($locais['tem_cartorio']) ? 'display:none' : '' ?>">
                                <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Local do Cartório</label>
                                <input type="text" name="local_cartorio" value="<?= sanitizar($locais['local_cartorio'] ?? '') ?>" placeholder="Endereço ou 'a definir'"
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                            </div>

                            <!-- Cerimônia -->
                            <div class="flex items-center gap-3 mt-4">
                                <input type="checkbox" name="tem_cerimonia" value="1" <?= !empty($locais['tem_cerimonia']) ? 'checked' : '' ?>
                                       class="w-4 h-4 rounded accent-white">
                                <label class="text-[9px] font-black uppercase tracking-widest text-zinc-400 flex-1">Cerimônia e Festa</label>
                            </div>
                            <div class="space-y-1.5" id="local_cerimonia_wrap" style="<?= empty($locais['tem_cerimonia']) ? 'display:none' : '' ?>">
                                <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Data da Cerimônia</label>
                                <input type="date" name="data_cerimonia" value="<?= sanitizar($locais['data_cerimonia'] ?? '') ?>"
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                                <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Local da Cerimônia</label>
                                <input type="text" name="local_cerimonia" value="<?= sanitizar($locais['local_cerimonia'] ?? '') ?>" placeholder="Endereço ou 'a definir'"
                                       class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- AI Copilot Panel -->
                    <div class="bg-white/5 border border-white/10 rounded-[32px] p-8 shadow-[0_0_50px_rgba(255,255,255,0.02)]">
                        <h2 class="text-md font-bold text-white mb-4 flex items-center gap-2">
                            <i data-lucide="sparkles" class="w-4.5 h-4.5 text-zinc-300"></i>
                            IA Copilot de Contratos
                        </h2>
                        <p class="text-zinc-400 text-[11px] leading-relaxed mb-6">Selecione e copie um trecho do contrato, ou descreva abaixo a alteração contratual que deseja fazer. O Gemini otimizará as cláusulas para você.</p>
                        
                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black uppercase tracking-widest text-zinc-400">Texto Original da Cláusula</label>
                                <textarea x-model="copilotTexto" rows="4" placeholder="Cole aqui o texto da cláusula que deseja ajustar..."
                                          class="w-full bg-black/80 border border-white/10 rounded-xl px-4 py-3 text-xs text-white focus:border-white transition-all outline-none resize-none"></textarea>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black uppercase tracking-widest text-zinc-400">Instrução para a IA (Opcional)</label>
                                <input type="text" x-model="copilotPrompt" placeholder="Ex: Adicionar 20% de juros moratórios..."
                                       class="w-full bg-black/80 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:border-white transition-all outline-none">
                            </div>

                            <div class="flex gap-2">
                                <button type="button" @click="copilotOtimizar()" :disabled="copilotLoading || !copilotTexto.trim()"
                                        class="flex-1 bg-white text-black py-2.5 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-zinc-200 active:scale-95 transition-all flex items-center justify-center gap-1 disabled:opacity-50">
                                    <template x-if="!copilotLoading">
                                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                    </template>
                                    <span x-text="copilotLoading ? 'Refinando...' : 'Refinar Legal'"></span>
                                </button>
                            </div>

                            <template x-if="copilotResultado">
                                <div class="bg-black/40 border border-white/5 rounded-2xl p-4 mt-4 relative">
                                    <div class="text-[8px] font-black uppercase tracking-widest text-emerald-500 mb-2">Resultado da IA</div>
                                    <p class="text-[11px] text-zinc-300 leading-relaxed font-mono select-all" x-text="copilotResultado"></p>
                                    <div class="text-[9px] text-zinc-500 mt-3 italic">Dica: copie e cole de volta no editor principal.</div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full bg-white text-black h-14 rounded-2xl font-black text-sm hover:scale-[1.02] active:scale-95 transition-all shadow-xl flex items-center justify-center gap-2">
                        <i data-lucide="check" class="w-5 h-5"></i>
                        Salvar e Visualizar Contrato
                    </button>
                </div>
            </div>
        </form>
    </main>
</div>

<!-- Load CKEditor 5 from CDN -->
<script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>

<script>
function contratoGerarApp() {
    return {
        propostaId: <?= json_encode($contrato['proposta_id'] ?? null) ?>,
        contratoId: <?= json_encode($contrato['id'] ?? null) ?>,
        iaAnexoLoading: false,
        iaSaveLoading: false,
        copilotTexto: '',
        copilotPrompt: '',
        copilotResultado: '',
        copilotLoading: false,
        
        gerarAnexoIA() {
            if (!this.propostaId) return;
            this.iaAnexoLoading = true;
            
            fetch(<?= json_encode(raizUrl("/api/contratos/gerar_anexo.php")) ?>, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ proposta_id: this.propostaId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.html) {
                    if (window.anexoEditor) {
                        window.anexoEditor.setData(data.html);
                    } else {
                        document.querySelector('#anexo_texto').value = data.html;
                    }
                } else {
                    alert(data.erro || 'Falha ao gerar anexo.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Erro de conexão ao gerar anexo.');
            })
            .finally(() => {
                this.iaAnexoLoading = false;
            });
        },
        
        atualizarAnexo() {
            if (!this.contratoId) return;
            this.iaSaveLoading = true;

            fetch(<?= json_encode(raizUrl("/api/contratos/atualizar_anexo.php")) ?>, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ contrato_id: this.contratoId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.erro || 'Falha ao gerar anexo.');
                    this.iaSaveLoading = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Erro de conexão ao gerar anexo.');
                this.iaSaveLoading = false;
            });
        },

        copilotOtimizar() {
            if (!this.copilotTexto.trim()) return;
            this.copilotLoading = true;
            this.copilotResultado = '';
            
            const payload = {
                texto: this.copilotTexto,
                acao: this.copilotPrompt.trim() ? 'custom' : 'otimizar',
                prompt: this.copilotPrompt
            };
            
            fetch(<?= json_encode(raizUrl("/api/contratos/copilot_ia.php")) ?>, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.resultado) {
                    this.copilotResultado = data.resultado;
                } else {
                    alert(data.erro || 'Falha no Copilot.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Erro de conexão com o Copilot.');
            })
            .finally(() => {
                this.copilotLoading = false;
            });
        }
    }
}
</script>
<script>
// Initialize CKEditor
document.addEventListener('DOMContentLoaded', () => {
    ClassicEditor
        .create(document.querySelector('#contrato_texto'), {
            toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|', 'undo', 'redo' ]
        })
        .then(editor => {
            window.contratoEditor = editor;
        })
        .catch(err => {
            console.warn('Falha ao inicializar CKEditor no contrato_texto', err);
        });

    ClassicEditor
        .create(document.querySelector('#anexo_texto'), {
            toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|', 'undo', 'redo' ]
        })
        .then(editor => {
            window.anexoEditor = editor;
        })
        .catch(err => {
            console.warn('Falha ao inicializar CKEditor no anexo_texto', err);
        });

    // Toggle location fields when checkboxes change
    document.querySelectorAll('[name="tem_prewedding"], [name="tem_cartorio"], [name="tem_cerimonia"]').forEach(cb => {
        cb.addEventListener('change', function () {
            const wrap = document.getElementById('local_' + this.name.replace('tem_', '') + '_wrap');
            if (wrap) {
                wrap.style.display = this.checked ? '' : 'none';
            }
        });
    });

    const togglePreweddingAdefinir = () => {
        const cbDefinir = document.querySelector('[name="local_prewedding_a_definir"]');
        const inputLocal = document.querySelector('[name="local_prewedding"]');
        if (cbDefinir && inputLocal) {
            inputLocal.disabled = cbDefinir.checked;
            if (cbDefinir.checked) {
                inputLocal.classList.add('opacity-50');
            } else {
                inputLocal.classList.remove('opacity-50');
            }
        }
    };

    const cbDefinir = document.querySelector('[name="local_prewedding_a_definir"]');
    if (cbDefinir) {
        cbDefinir.addEventListener('change', togglePreweddingAdefinir);
    }
    togglePreweddingAdefinir();
    
    // Executar toggle do meio de pagamento da calculadora
    if (typeof toggleMeioPagamento === 'function') {
        toggleMeioPagamento();
    }
});

function escreverValorPorExtenso(valor) {
    if (valor === 0) return 'zero reais';
    
    let inteiro = Math.floor(valor);
    let centavos = Math.round((valor - inteiro) * 100);
    
    let extensoInteiro = escreverNumero(inteiro) + (inteiro === 1 ? ' real' : ' reais');
    let extensoCentavos = '';
    
    if (centavos > 0) {
        extensoCentavos = ' e ' + escreverNumero(centavos) + (centavos === 1 ? ' centavo' : ' centavos');
    }
    
    return extensoInteiro + extensoCentavos;
}

function escreverNumero(n) {
    const unidades = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];
    const dezenas10 = ['dez', 'onze', 'doze', 'treze', 'catorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
    const dezenas = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
    const centenas = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];
    
    if (n === 100) return 'cem';
    if (n < 10) return unidades[n];
    if (n >= 10 && n < 20) return dezenas10[n - 10];
    if (n >= 20 && n < 100) {
        let u = n % 10;
        let d = Math.floor(n / 10);
        return dezenas[d] + (u > 0 ? ' e ' + unidades[u] : '');
    }
    if (n >= 100 && n < 1000) {
        let resto = n % 100;
        let c = Math.floor(n / 100);
        return centenas[c] + (resto > 0 ? ' e ' + escreverNumero(resto) : '');
    }
    if (n >= 1000 && n < 1000000) {
        let mil = Math.floor(n / 1000);
        let resto = n % 1000;
        let extensoMil = (mil === 1 ? '' : escreverNumero(mil) + ' ') + 'mil';
        return extensoMil + (resto > 0 ? (resto < 100 || resto % 100 === 0 ? ' e ' : ', ') + escreverNumero(resto) : '');
    }
    
    return n.toString();
}

function escreverCardinal(n) {
    const cardinais = ['', 'uma', 'duas', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove', 'dez', 'onze', 'doze', 'treze', 'catorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove', 'vinte', 'vinte e uma', 'vinte e duas', 'vinte e três', 'vinte e quatro'];
    return cardinais[n] || n.toString();
}

function adicionarMeses(dataStr, meses) {
    let partes = dataStr.split('-');
    let ano = parseInt(partes[0]);
    let mes = parseInt(partes[1]) - 1;
    let dia = parseInt(partes[2]);
    
    let d = new Date(ano, mes + meses, dia);
    let mesAlvo = (mes + meses) % 12;
    if (mesAlvo < 0) mesAlvo += 12;
    if (d.getMonth() !== mesAlvo) {
        d.setDate(0);
    }
    
    let anoResult = d.getFullYear();
    let mesResult = (d.getMonth() + 1).toString().padStart(2, '0');
    let diaResult = d.getDate().toString().padStart(2, '0');
    
    return `${diaResult}/${mesResult}/${anoResult}`;
}

function formatarMoeda(valor) {
    return valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function toggleMeioPagamento() {
    let meio = document.getElementById('calc_meio').value;
    let ultimoWrap = document.getElementById('calc_ultimo_data_wrap');
    let parcelasWrap = document.getElementById('calc_parcelas_wrap');
    
    if (meio === 'Cartão de Crédito') {
        if (ultimoWrap) ultimoWrap.classList.add('hidden');
        if (parcelasWrap) parcelasWrap.classList.remove('hidden');
    } else {
        if (ultimoWrap) ultimoWrap.classList.remove('hidden');
        if (parcelasWrap) parcelasWrap.classList.add('hidden');
    }
}

function calcularCondicoes() {
    let totalInput = document.querySelector('[name="valor_total"]').value;
    let total = parseFloat(totalInput.replace(/[^\d,]/g, '').replace(',', '.'));
    
    if (isNaN(total) || total <= 0) {
        alert('Por favor, preencha o Valor Total do contrato.');
        return;
    }
    
    let sinalPct = parseInt(document.getElementById('calc_sinal_pct').value) || 0;
    let sinalDataStr = document.getElementById('calc_sinal_data').value;
    let meioPagamento = document.getElementById('calc_meio').value;
    
    if (!sinalDataStr) {
        alert('Por favor, preencha a data do Sinal.');
        return;
    }
    
    let qtdParcelas = 1;
    let primeiraParcelaDataStr = '';
    
    // Primeira parcela vence 1 mês após o sinal
    let sinalSplit = sinalDataStr.split('-');
    let sinalAno = parseInt(sinalSplit[0]);
    let sinalMes = parseInt(sinalSplit[1]) - 1; // 0-indexed
    let sinalDia = parseInt(sinalSplit[2]);
    
    let d1 = new Date(sinalAno, sinalMes + 1, sinalDia);
    if (d1.getMonth() !== (sinalMes + 1) % 12) {
        d1.setDate(0);
    }
    let p1Ano = d1.getFullYear();
    let p1Mes = (d1.getMonth() + 1).toString().padStart(2, '0');
    let p1Dia = d1.getDate().toString().padStart(2, '0');
    primeiraParcelaDataStr = `${p1Ano}-${p1Mes}-${p1Dia}`;

    if (meioPagamento === 'Cartão de Crédito') {
        qtdParcelas = parseInt(document.getElementById('calc_parcelas').value) || 1;
    } else {
        // Pix ou Boleto: calcular quantidade de parcelas pela diferença de meses entre último pagamento e data do sinal
        let ultimoDataStr = document.getElementById('calc_ultimo_data').value;
        if (!ultimoDataStr) {
            alert('Por favor, preencha a data do Último Pagamento.');
            return;
        }
        
        let dataSinal = new Date(sinalDataStr + 'T00:00:00');
        let dataUltimo = new Date(ultimoDataStr + 'T00:00:00');
        
        let diffMeses = (dataUltimo.getFullYear() - dataSinal.getFullYear()) * 12 + (dataUltimo.getMonth() - dataSinal.getMonth());
        qtdParcelas = Math.max(1, diffMeses);
    }
    
    let sinalVal = total * (sinalPct / 100);
    let saldoVal = total - sinalVal;
    let parcelaVal = saldoVal / qtdParcelas;
    
    // Formatar data do sinal para exibição
    let dataSinalFormated = `${sinalSplit[2]}/${sinalSplit[1]}/${sinalSplit[0]}`;
    
    let texto = `O valor total do presente contrato é de ${formatarMoeda(total)} (${escreverValorPorExtenso(total)}), que será adimplido pelo CONTRATANTE através de parcelamento direto, conforme as datas e valores especificados abaixo:\n\n`;
    
    if (sinalVal > 0) {
        texto += `Sinal/Entrada (${sinalPct}%): ${formatarMoeda(sinalVal)} (${escreverValorPorExtenso(sinalVal)}) com vencimento em ${dataSinalFormated}.\n\n`;
    }
    
    texto += `Saldo Restante: ${formatarMoeda(saldoVal)} (${escreverValorPorExtenso(saldoVal)}) dividido em ${qtdParcelas.toString().padStart(2, '0')} (${escreverCardinal(qtdParcelas)}) parcelas mensais e consecutivas no valor de ${formatarMoeda(parcelaVal)} (${escreverValorPorExtenso(parcelaVal)}) cada, via ${meioPagamento}, com vencimentos programados para:\n`;
    
    for (let i = 0; i < qtdParcelas; i++) {
        let venc = adicionarMeses(primeiraParcelaDataStr, i);
        texto += `- ${i + 1}ª Parcela: ${venc} — ${formatarMoeda(parcelaVal)}\n`;
    }
    
    document.getElementById('condicoes_pagamento').value = texto.trim();
}
</script>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
