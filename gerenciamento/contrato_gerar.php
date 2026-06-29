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

function contratoTextoTemMojibake(string $texto): bool
{
    $marcadores = [
        "\u{00C3}\u{0192}",
        "\u{00C3}\u{0082}",
        "\u{00C3}\u{00A1}",
        "\u{00C3}\u{00A0}",
        "\u{00C3}\u{00A2}",
        "\u{00C3}\u{00A3}",
        "\u{00C3}\u{00A7}",
        "\u{00C3}\u{00A9}",
        "\u{00C3}\u{00AA}",
        "\u{00C3}\u{00AD}",
        "\u{00C3}\u{00B3}",
        "\u{00C3}\u{00B4}",
        "\u{00C3}\u{00B5}",
        "\u{00C3}\u{00BA}",
        "\u{00C3}\u{2021}",
        "\u{00C2}",
        "\u{00E2}\u{20AC}",
    ];

    foreach ($marcadores as $marcador) {
        if (str_contains($texto, $marcador)) {
            return true;
        }
    }

    return false;
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
    $mapa = [
        "\u{00C3}\u{20AC}" => "\u{00C0}",
        "\u{00C3}\u{0081}" => "\u{00C1}",
        "\u{00C3}\u{201A}" => "\u{00C2}",
        "\u{00C3}\u{0192}" => "\u{00C3}",
        "\u{00C3}\u{2021}" => "\u{00C7}",
        "\u{00C3}\u{2030}" => "\u{00C9}",
        "\u{00C3}\u{0160}" => "\u{00CA}",
        "\u{00C3}\u{008D}" => "\u{00CD}",
        "\u{00C3}\u{201C}" => "\u{00D3}",
        "\u{00C3}\u{201D}" => "\u{00D4}",
        "\u{00C3}\u{2022}" => "\u{00D5}",
        "\u{00C3}\u{0161}" => "\u{00DA}",
        "\u{00C3}\u{00A0}" => "\u{00E0}",
        "\u{00C3}\u{00A1}" => "\u{00E1}",
        "\u{00C3}\u{00A2}" => "\u{00E2}",
        "\u{00C3}\u{00A3}" => "\u{00E3}",
        "\u{00C3}\u{00A7}" => "\u{00E7}",
        "\u{00C3}\u{00A9}" => "\u{00E9}",
        "\u{00C3}\u{00AA}" => "\u{00EA}",
        "\u{00C3}\u{00AD}" => "\u{00ED}",
        "\u{00C3}\u{00B3}" => "\u{00F3}",
        "\u{00C3}\u{00B4}" => "\u{00F4}",
        "\u{00C3}\u{00B5}" => "\u{00F5}",
        "\u{00C3}\u{00BA}" => "\u{00FA}",
        "\u{00E2}\u{20AC}\u{201C}" => '-',
        "\u{00E2}\u{20AC}\u{201D}" => '-',
        "\u{00E2}\u{20AC}\u{0153}" => '"',
        "\u{00E2}\u{20AC}\u{009D}" => '"',
        "\u{00E2}\u{20AC}\u{02DC}" => "'",
        "\u{00E2}\u{20AC}\u{2122}" => "'",
        "\u{00E2}\u{20AC}\u{00A6}" => '...',
        "\u{00C2}\u{00BA}" => "\u{00BA}",
        "\u{00C2}\u{00AA}" => "\u{00AA}",
        "\u{00C2}\u{00B0}" => "\u{00B0}",
        "\u{00C2}\u{00A7}" => "\u{00A7}",
        "\u{00C3}\u{0082} " => ' ',
        "\u{00C2}\u{00A0}" => ' ',
    ];

    for ($i = 0; $i < 3 && contratoTextoTemMojibake($texto); $i++) {
        $corrigido = strtr($texto, $mapa);

        if ($corrigido === $texto) {
            break;
        }

        $texto = $corrigido;
    }

    return $texto;
}
// ---------------------------------------------------------
// CRIAÇÃO DE CONTRATO EM BRANCO (SEM PROPOSTA)
// ---------------------------------------------------------
if (isset($_GET['novo'])) {
    $tipo = $_GET['tipo'] ?? '';

    // --- Step 1: Show type selection if no tipo specified ---
    if (!$tipo) {
        $tituloPagina = 'Novo Contrato';
        require_once __DIR__ . '/../includes/layout/head.php';
        ?>
        <div id="app-wrapper">
            <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>
            <main id="main-content" class="content-sheet flex flex-col min-h-screen">
                <div class="max-w-4xl mx-auto w-full py-12">
                    <div class="mb-12 text-center">
                        <div class="w-16 h-16 rounded-3xl bg-zinc-900 border border-white/5 flex items-center justify-center text-zinc-300 mx-auto mb-6">
                            <i data-lucide="scroll" class="w-8 h-8"></i>
                        </div>
                        <h1 class="text-3xl font-black tracking-tight text-white">Novo Contrato</h1>
                        <p class="text-sm font-medium text-zinc-400 mt-2">Selecione o tipo de contrato que deseja criar</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <a href="?novo=1&tipo=casamento"
                           class="group bg-zinc-900/50 border border-white/5 hover:border-white/20 rounded-[32px] p-8 transition-all hover:scale-[1.02] active:scale-95">
                            <div class="w-14 h-14 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-300 group-hover:bg-white group-hover:text-black transition-all mb-5">
                                <i data-lucide="heart" class="w-7 h-7"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Casamento</h3>
                            <p class="text-sm text-zinc-400 leading-relaxed">Contrato completo para casamentos com cláusulas de cobertura fotográfica, prazos de entrega, autorização de imagem e locais do evento.</p>
                        </a>

                        <a href="?novo=1&tipo=marketing"
                           class="group bg-zinc-900/50 border border-white/5 hover:border-white/20 rounded-[32px] p-8 transition-all hover:scale-[1.02] active:scale-95">
                            <div class="w-14 h-14 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-300 group-hover:bg-white group-hover:text-black transition-all mb-5">
                                <i data-lucide="megaphone" class="w-7 h-7"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Marketing Digital</h3>
                            <p class="text-sm text-zinc-400 leading-relaxed">Contrato de prestação de serviços de marketing digital, gestão de tráfego, social media e estratégia digital.</p>
                        </a>

                        <a href="?novo=1&tipo=filmmaker"
                           class="group bg-zinc-900/50 border border-white/5 hover:border-white/20 rounded-[32px] p-8 transition-all hover:scale-[1.02] active:scale-95">
                            <div class="w-14 h-14 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-300 group-hover:bg-white group-hover:text-black transition-all mb-5">
                                <i data-lucide="video" class="w-7 h-7"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Filmmaker</h3>
                            <p class="text-sm text-zinc-400 leading-relaxed">Contrato de produção audiovisual, vídeos, reels e conteúdo cinematic.</p>
                        </a>

                        <a href="?novo=1&tipo=15anos"
                           class="group bg-zinc-900/50 border border-white/5 hover:border-white/20 rounded-[32px] p-8 transition-all hover:scale-[1.02] active:scale-95">
                            <div class="w-14 h-14 rounded-2xl bg-white/5 flex items-center justify-center text-zinc-300 group-hover:bg-white group-hover:text-black transition-all mb-5">
                                <i data-lucide="star" class="w-7 h-7"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">15 Anos</h3>
                            <p class="text-sm text-zinc-400 leading-relaxed">Contrato de cobertura completa de festas de debutante.</p>
                        </a>
                    </div>

                    <div class="mt-10 text-center">
                        <a href="<?= raizUrl('/gerenciamento/contratos.php') ?>" class="text-sm font-bold text-zinc-500 hover:text-white transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            Voltar à Lista de Contratos
                        </a>
                    </div>
                </div>
            </main>
        </div>
        <script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
        <?php
        require_once __DIR__ . '/../includes/layout/footer.php';
        exit;
    }

    // --- Step 2: Create contract with selected type ---
    $contratoId = gerarId();
    $dataContrato = date('Y-m-d');
    $localContrato = 'Vitória/ES';
    $valorTotal = 0.00;
    $clienteNome = '';
    $tituloContrato = 'Novo Contrato';
    $condicoesPagamento = 'À vista ou conforme parcelamento acordado.';
    $dataContratoPorExtenso = dataExtenso($dataContrato);

    $sig1 = ['nome' => '', 'cpf' => '', 'email' => '', 'telefone' => '', 'endereco' => ''];
    $sig2 = ['nome' => '', 'cpf' => '', 'email' => '', 'telefone' => '', 'endereco' => ''];
    $sigDistinto = ['nome' => 'Jeane Poncem', 'email' => 'jeaneponcemsm@gmail.com', 'telefone' => ''];

    $locais = [
        'tem_prewedding' => '',
        'local_prewedding' => '',
        'local_prewedding_a_definir' => '1',
        'data_prewedding' => '',
        'previsao_prewedding' => '10 dias úteis após a seleção das fotos pelo casal',
        'previsao_savethedate' => 'Até 15 dias úteis após a realização do ensaio',
        'tem_cartorio' => '',
        'local_cartorio' => '',
        'tem_cerimonia' => '',
        'local_cerimonia' => '',
        'data_cerimonia' => ''
    ];

    if ($tipo === 'casamento') {
        $tituloContrato = 'Contrato de Prestação de Serviços - Casamento';
        $anexoTexto = '<h4>Anexo I - Descrição dos Serviços</h4><p>Descreva aqui os serviços contratados para o casamento, incluindo pacote, itens personalizados, prazos de entrega e condições específicas.</p>';

        $clausula2 = '<h4>CLÁUSULA SEGUNDA - PRAZO E LOCAL DE EXECUÇÃO DOS SERVIÇOS</h4>';
        $clausula2 .= '<p>2.1. Os serviços serão executados na data do evento, em local a definir em comum acordo entre as partes.</p>';
        $clausula2 .= '<p>2.2. A duração padrão da cobertura será aquela descrita e especificada no Anexo I, podendo ser ajustada mediante comum acordo entre as partes.</p>';
        $clausula2 .= '<p>2.3. A CONTRATADA não se responsabiliza por atrasos ou impossibilidade de execução dos serviços decorrentes de condições climáticas adversas, falhas de energia elétrica no local do evento ou quaisquer outros fatores alheios à sua vontade, comprometendo-se, nestes casos, a remarcar a data mediante comum acordo com os CONTRATANTES.</p>';

        $clausula4 = '<h4>CLÁUSULA QUARTA - DAS ENTREGAS</h4>';
        $clausula4 .= '<p>4.1. A <strong>CONTRATADA</strong> entregará aos <strong>CONTRATANTES</strong> o material fotográfico e/ou audiovisual devidamente editado, conforme especificações técnicas e prazos estabelecidos no Anexo I, parte integrante deste instrumento.</p>';
        $clausula4 .= '<p>4.2. O prazo de entrega do material final será contado a partir da data de realização do evento, salvo disposição em contrário prevista no Anexo I.</p>';
        $clausula4 .= '<p>4.3. A <strong>CONTRATADA</strong> não se responsabiliza pela perda do material decorrente de caso fortuito ou força maior, obrigando-se, entretanto, a manter backup de segurança de todos os arquivos pelo prazo mínimo de 90 (noventa) dias após a entrega.</p>';

        $contratoTexto = "
        <h3>CONTRATO DE PRESTAÇÃO DE SERVIÇOS</h3>
        <p class=\"pdf-subtitle\">CASAMENTO</p>
        <p class=\"pdf-numero\">Nº " . date('Y') . "/" . substr($contratoId, 0, 4) . "</p>

        <p class=\"p0\">Pelo presente instrumento particular, de um lado:</p>

        <p class=\"p0\"><strong>CONTRATANTES:</strong><br>
        <strong>[Nome da Noiva]</strong>, portadora do CPF nº [CPF da Noiva], e <strong>[Nome do Noivo]</strong>, portador do CPF nº [CPF do Noivo], doravante denominados simplesmente <strong>CONTRATANTES</strong>.</p>

        <p class=\"p0\"><strong>CONTRATADA:</strong><br>
        <strong>Distinto | Poncem Studio (Poncem Studio LTDA)</strong>, CNPJ 50.168.732/0001-63, com sede na Rod. do Sol nº 2780, sala 1307, Praia de Itaparica, Vila Velha-ES, CEP 29102-020, e-mail contato@wedistinto.com, doravante denominada <strong>CONTRATADA</strong>.</p>

        <p class=\"p0\">Firmam o presente contrato de prestação de serviços, mediante cláusulas e condições a seguir:</p>

        <h4>CLÁUSULA PRIMEIRA - DO OBJETO</h4>
        <p>1.1. A <strong>CONTRATADA</strong> prestará serviços profissionais de cobertura fotográfica e/ou produção audiovisual para o casamento dos <strong>CONTRATANTES</strong>, em conformidade com o detalhamento contido no Anexo I, que integra este instrumento.</p>

        " . $clausula2 . "

        <h4>CLÁUSULA TERCEIRA - VALOR E CONDIÇÕES DE PAGAMENTO</h4>
        <p>3.1. Pela prestação dos serviços contratados, os <strong>CONTRATANTES</strong> pagarão à <strong>CONTRATADA</strong> a quantia total de <strong>R$ 0,00</strong>, nas seguintes condições: " . htmlspecialchars($condicoesPagamento) . ".</p>
        <p>3.2. O pagamento será efetuado conforme cronograma acordado entre as partes, podendo ser dividido em parcelas mensais.</p>
        <p>3.3. Em caso de atraso no pagamento de qualquer parcela, incidirá multa de 2% (dois por cento) sobre o valor da parcela em atraso, bem como juros de mora de 1% (um por cento) ao mês e correção monetária pelo IPCA.</p>

        " . $clausula4 . "

        <h4>CLÁUSULA QUINTA - DA AUTORIZAÇÃO DE IMAGEM</h4>
        <p>5.1. Os <strong>CONTRATANTES</strong> autorizam de forma expressa, irrevogável e gratuita a utilização de suas imagens capturadas durante os eventos e ensaios, para fins de divulgação de portfólio profissional da <strong>CONTRATADA</strong> em suas mídias digitais, redes sociais, site institucional e materiais promocionais, pelo período de 2 (dois) anos contados da data de realização do evento.</p>
        <p>5.2. A autorização prevista no item 5.1 abrange a reprodução, exibição, publicação e divulgação das imagens em qualquer mídia ou formato, desde que sem finalidade lucrativa direta e respeitando o decoro e a boa imagem dos CONTRATANTES.</p>
        <p>5.3. Caso os <strong>CONTRATANTES</strong> desejem restringir a divulgação de imagens específicas, deverão comunicar a <strong>CONTRATADA</strong> por escrito em até 15 (quinze) dias após a data do evento.</p>

        <h4>CLÁUSULA SEXTA - DAS OBRIGAÇÕES DA CONTRATADA</h4>
        <p>6.1. Prestar os serviços contratados com zelo profissional, utilizando equipamentos adequados e profissionais qualificados.<br>
        6.2. Comparecer ao local do evento com antecedência mínima necessária para preparação e montagem dos equipamentos.<br>
        6.3. Disponibilizar aos CONTRATANTES os contatos telefônicos e de WhatsApp da equipe escalada para o dia do evento.<br>
        6.4. Manter sigilo absoluto sobre as informações pessoais e dados compartilhados pelos CONTRATANTES.</p>

        <h4>CLÁUSULA SÉTIMA - DAS OBRIGAÇÕES DOS CONTRATANTES</h4>
        <p>7.1. Fornecer alimentação adequada para a equipe de captação caso o tempo total do evento exceda 4 (quatro) horas.<br>
        7.2. Garantir o livre trânsito dos fotógrafos e cinegrafistas no local do evento.<br>
        7.3. Efetuar os pagamentos rigorosamente em dia, conforme cronograma acordado.<br>
        7.4. Disponibilizar os convites e credenciais necessários para acesso da equipe aos locais dos eventos.<br>
        7.5. Informar a <strong>CONTRATADA</strong> com antecedência mínima de 48 (quarenta e oito) horas sobre qualquer alteração de horário ou local.</p>

        <h4>CLÁUSULA OITAVA - DA CESSÃO</h4>
        <p>8.1. A <strong>CONTRATADA</strong> poderá ceder ou subcontratar total ou parcialmente os serviços objeto deste contrato a terceiros de sua confiança, mantendo-se como única responsável perante os CONTRATANTES.<br>
        8.2. Os <strong>CONTRATANTES</strong> não poderão ceder ou transferir a terceiros os direitos e obrigações deste contrato sem prévia autorização por escrito da CONTRATADA.</p>

        <h4>CLÁUSULA NONA - DA RESCISÃO CONTRATUAL E MULTAS</h4>
        <p>9.1. Em caso de cancelamento unilateral imotivado por parte dos <strong>CONTRATANTES</strong> com menos de 30 (trinta) dias da data do evento, nenhum valor pago a título de sinal ou reserva será reembolsado.<br>
        9.2. Em caso de cancelamento com antecedência superior a 30 (trinta) dias, os valores já pagos serão devolvidos deduzindo-se 20% (vinte por cento) a título de multa compensatória.<br>
        9.3. Em descumprimento de quaisquer outras cláusulas, incidirá multa penal de 10% (dez por cento) sobre o valor remanescente, sem prejuízo de perdas e danos.<br>
        9.4. A <strong>CONTRATADA</strong> poderá rescindir o contrato caso os <strong>CONTRATANTES</strong> descumpram obrigações pecuniárias, ficando autorizada a reter os valores já recebidos.</p>

        <h4>CLÁUSULA DÉCIMA - DISPOSIÇÕES GERAIS</h4>
        <p>10.1. Este instrumento não gera vínculo empregatício entre as partes.<br>
        10.2. As partes elegem o Anexo I como parte integrante deste contrato.<br>
        10.3. Qualquer alteração deverá ser feita por escrito, mediante aditivo contratual.<br>
        10.4. A tolerância ao descumprimento de cláusula não constituirá novação.<br>
        10.5. As partes buscarão solução amigável antes de recorrer à via judicial.</p>

        <h4>CLÁUSULA DÉCIMA PRIMEIRA - DO FORO</h4>
        <p>11.1. Fica eleito o foro da Comarca de Vitória/ES para dirimir quaisquer controvérsias, com expressa renúncia a qualquer outro.</p>

        <p class=\"p-closing\">" . $localContrato . ", " . $dataContratoPorExtenso . ".</p>
        ";
    } else {
        $tituloContrato = 'Contrato de Prestação de Serviços Profissionais';
        $anexoTexto = '<h4>Anexo I - Descrição dos Serviços</h4><p>Descreva aqui os serviços contratados, especificando prazos, entregáveis e demais condições operacionais.</p>';

        $contratoTexto = "
        <h3 style=\"text-align: center;\">CONTRATO DE PRESTAÇÃO DE SERVIÇOS PROFISSIONAIS</h3>
        <p style=\"text-align: center;\"><strong>Nº " . date('Y') . "/" . substr($contratoId, 0, 4) . "</strong></p>

        <p>Pelo presente instrumento particular, de um lado:</p>

        <p><strong>CONTRATANTE:</strong><br>
        <strong>[Nome do Contratante]</strong>, inscrito sob CPF/CNPJ nº [Documento], doravante denominado <strong>CONTRATANTE</strong>.</p>

        <p><strong>CONTRATADA:</strong><br>
        <strong>Distinto | Poncem Studio (Poncem Studio LTDA)</strong>, CNPJ 50.168.732/0001-63, com sede na Rod. do Sol nº 2780, sala 1307, Praia de Itaparica, Vila Velha-ES, CEP 29102-020, e-mail contato@wedistinto.com, doravante denominada <strong>CONTRATADA</strong>.</p>

        <p>Firmam o presente contrato de prestação de serviços, mediante cláusulas e condições a seguir:</p>

        <h4>CLÁUSULA PRIMEIRA - DO OBJETO</h4>
        <p>1.1. A <strong>CONTRATADA</strong> prestará serviços profissionais ao <strong>CONTRATANTE</strong>, conforme especificações operacionais e prazos descritos no Anexo I, parte integrante deste instrumento.</p>

        <h4>CLÁUSULA SEGUNDA - VIGÊNCIA</h4>
        <p>2.1. O presente contrato terá vigência a partir da data de assinatura, pelo período acordado entre as partes.</p>

        <h4>CLÁUSULA TERCEIRA - VALOR E CONDIÇÕES DE PAGAMENTO</h4>
        <p>3.1. Pela execução dos serviços, o <strong>CONTRATANTE</strong> pagará à <strong>CONTRATADA</strong> a quantia total de <strong>R$ 0,00</strong>, nas seguintes condições: " . htmlspecialchars($condicoesPagamento) . ".</p>

        <h4>CLÁUSULA QUARTA - DIREITOS AUTORAIS E PORTFÓLIO</h4>
        <p>4.1. Fica expressamente reservado à <strong>CONTRATADA</strong> o direito de expor as peças criadas e materiais produzidos em seu próprio portfólio comercial, redes sociais e cases, respeitando a confidencialidade dos dados do CONTRATANTE.</p>

        <h4>CLÁUSULA QUINTA - OBRIGAÇÕES DAS PARTES</h4>
        <p>5.1. <strong>DA CONTRATADA:</strong> Executar os serviços contratados com qualidade técnica, prestar contas e manter sigilo sobre as informações do Contratante.<br>
        5.2. <strong>DO CONTRATANTE:</strong> Fornecer as informações necessárias para a execução dos serviços e honrar o calendário de pagamentos.</p>

        <h4>CLÁUSULA SEXTA - DA RESCISÃO</h4>
        <p>6.1. Qualquer das partes poderá rescindir o contrato mediante aviso prévio por escrito de 30 (trinta) dias. Em caso de rescisão antecipada imotivada pelo Contratante, incidirá multa contratual de 10% sobre o saldo devedor remanescente.</p>

        <h4>CLÁUSULA SÉTIMA - DO FORO</h4>
        <p>7.1. Fica eleito o foro da Comarca de Vitória/ES para solucionar qualquer divergência oriunda deste instrumento.</p>

        <p>" . $localContrato . ", " . $dataContratoPorExtenso . ".</p>
        ";
    }

    $dadosJson = json_encode([
        'tipo_contrato' => $tipo,
        'contrato_texto' => $contratoTexto,
        'anexo_texto' => $anexoTexto,
        'signatario_1' => $sig1,
        'signatario_2' => $sig2,
        'signatario_distinto' => $sigDistinto,
        'data_evento' => '',
        'local_evento' => '',
        'locais' => $locais,
        'vigencia_meses' => '',
        'pagamento_modo' => 'parcelado',
        'permitir_parcela_pos_evento' => false,
        'asaas_billing_type' => 'UNDEFINED',
        'asaas_total_parcelas' => 1,
        'asaas_first_due_date' => '',
        'asaas_valor_sinal' => 0,
        'asaas_sinal_vencimento' => ''
    ], JSON_UNESCAPED_UNICODE);

    $stmtInsert = $db->prepare("
        INSERT INTO contratos (id, proposta_id, cliente_id, cliente_nome, titulo, valor_total, condicoes_pagamento, data_contrato, local_contrato, status, dados_json)
        VALUES (?, NULL, NULL, ?, ?, ?, ?, ?, ?, 'rascunho', ?)
    ");
    $stmtInsert->execute([
        $contratoId,
        $clienteNome,
        $tituloContrato,
        $valorTotal,
        $condicoesPagamento,
        $dataContrato,
        $localContrato,
        $dadosJson
    ]);

    header('Location: ' . raizUrl('/gerenciamento/contrato_gerar.php?id=' . $contratoId));
    exit;
}

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
    $dadosProposta = corrigirMojibakeContrato(json_decode($proposta['dados_json'], true) ?: []);

    if ($proposta['tipo'] === 'casamento') {
        $planoFechado = $dadosProposta['pacote_dado_andamento'] ?? $dadosProposta['cliente_escolha']['plano_id'] ?? '';
        $valorFechado = (float)($dadosProposta['valor_fechamento'] ?? $dadosProposta['cliente_escolha']['valor_total'] ?? $proposta['valor_total'] ?? 0);
        $condicoesFechamento = trim((string)($dadosProposta['escolha_condicoes'] ?? $dadosProposta['cliente_escolha']['condicoes'] ?? ''));
        $pendenciasContrato = [];

        if (!$planoFechado) {
            $pendenciasContrato[] = 'escolha o plano fechado pelo casal';
        }
        if ($valorFechado <= 0) {
            $pendenciasContrato[] = 'confira o valor final do fechamento';
        }
        if (empty($dadosProposta['data_casamento'])) {
            $pendenciasContrato[] = 'preencha a data do casamento';
        }
        if (empty($dadosProposta['whatsapp'])) {
            $pendenciasContrato[] = 'preencha o WhatsApp do cliente';
        }
        if ($condicoesFechamento === '') {
            $pendenciasContrato[] = 'preencha as condicoes de pagamento';
        }

        if (!empty($pendenciasContrato)) {
            $mensagem = 'Antes de gerar o contrato, revise: ' . implode('; ', $pendenciasContrato) . '.';
            header('Location: ' . raizUrl('/gerenciamento/proposta_editar.php?id=' . urlencode($propostaId) . '&contrato_precheck=1&erro=' . urlencode($mensagem)));
            exit;
        }
    }

    // Create new contract record
    $contratoId = gerarId();
    $clienteNome = corrigirMojibakeContrato($proposta['cliente_nome']);
    $tituloContrato = corrigirMojibakeContrato("Contrato de Prestação de Serviços - " . $clienteNome);
    $valorTotal = (float)$proposta['valor_total'];
    if ($proposta['tipo'] === 'casamento') {
        if (!empty($dadosProposta['valor_fechamento'])) {
            $valorTotal = (float)$dadosProposta['valor_fechamento'];
        } elseif (!empty($dadosProposta['cliente_escolha']['valor_total'])) {
            $valorTotal = (float)$dadosProposta['cliente_escolha']['valor_total'];
        } elseif ($valorTotal <= 0) {
            // Determinar plano padrão de acordo com a prioridade dos que estão marcados para exibir
            $planoId = '';
            if (($dadosProposta['show_heritage'] ?? false) !== false) {
                $planoId = 'heritage';
            } elseif (($dadosProposta['show_cinematic'] ?? false) !== false) {
                $planoId = 'cinematic';
            } elseif (($dadosProposta['show_essencial'] ?? false) !== false) {
                $planoId = 'essencial';
            }

            if ($planoId) {
                $valorBase = 0.00;
                if ($planoId === 'heritage') {
                    $valorBase = (float)($dadosProposta['valor_heritage'] ?? 7900);
                } elseif ($planoId === 'cinematic') {
                    $valorBase = (float)($dadosProposta['valor_cinematic'] ?? 4500);
                } elseif ($planoId === 'essencial') {
                    $valorBase = (float)($dadosProposta['valor_essencial'] ?? 2800);
                }

                $valorTotal = $valorBase;

                // Boudoir
                $incBoudoir = $dadosProposta["include_boudoir_{$planoId}"] ?? $dadosProposta['include_boudoir'] ?? false;
                if ($incBoudoir) {
                    $valorTotal += (float)($dadosProposta['valor_boudoir'] ?: 500);
                }

                // Prewedding
                $incPrewedding = $dadosProposta["include_prewedding_{$planoId}"] ?? $dadosProposta['include_prewedding'] ?? false;
                if ($incPrewedding) {
                    $valorTotal += (float)($dadosProposta['valor_prewedding'] ?: 1100);
                }

                // Upgrades dinâmicos
                $pkgUpgrades = $dadosProposta['upgrades'][$planoId] ?? [];
                if (!empty($pkgUpgrades)) {
                    $extrasDinamicos = array_keys(array_filter($pkgUpgrades));
                    if (!empty($extrasDinamicos)) {
                        $placeholders = implode(',', array_fill(0, count($extrasDinamicos), '?'));
                        $stmtExtras = $db->prepare("SELECT preco_venda FROM servicos WHERE id IN ($placeholders) AND categoria = 'wedding' AND ativo = 1");
                        $stmtExtras->execute($extrasDinamicos);
                        foreach ($stmtExtras->fetchAll(PDO::FETCH_COLUMN) as $precoVenda) {
                            $valorTotal += (float)$precoVenda;
                        }
                    }
                }
            }
        }
    }

    $dataContrato = date('Y-m-d');
    $localContrato = corrigirMojibakeContrato('Vitória/ES');

    // Build default Payment Conditions text
    $condicoesPagamento = corrigirMojibakeContrato('À vista ou conforme parcelamento acordado.');
    if ($proposta['tipo'] === 'casamento') {
        if (!empty($dadosProposta['escolha_condicoes'])) {
            $condicoesPagamento = $dadosProposta['escolha_condicoes'];
        } elseif (!empty($dadosProposta['cliente_escolha']['condicoes'])) {
            $condicoesPagamento = $dadosProposta['cliente_escolha']['condicoes'];
        } else {
            $condicoesPagamento = $dadosProposta['condicoes_reserva'] ?? 'Conforme parcelamento em parcelas fixas.';
            if (!empty($dadosProposta['condicoes_heritage_cinematic'])) {
                $condicoesPagamento = $dadosProposta['condicoes_heritage_cinematic'];
            } elseif (!empty($dadosProposta['condicoes_essencial'])) {
                $condicoesPagamento = $dadosProposta['condicoes_essencial'];
            }
        }
    }

    // Initialize Signatarios
    $contatoTipo = $dadosProposta['contato_tipo'] ?? 'noiva';
    $nomeNoivo = trim((string)($dadosProposta['nome_noivo'] ?? ''));
    $nomeNoiva = trim((string)($dadosProposta['nome_noiva'] ?? ''));
    $responsavelManual = trim((string)($dadosProposta['responsavel_manual'] ?? $dadosProposta['responsavel'] ?? ''));
    $nomeContratantePrincipal = contatoResponsavel([
        'contato_tipo' => $contatoTipo,
        'nome_noivo' => $nomeNoivo,
        'nome_noiva' => $nomeNoiva,
        'responsavel' => $responsavelManual,
    ]);
    if ($nomeContratantePrincipal === '') {
        $nomeContratantePrincipal = $cliente['nome'] ?? $clienteNome;
    }

    $nomeContratanteSecundario = '';
    if ($contatoTipo === 'noiva') {
        $nomeContratanteSecundario = $nomeNoivo;
    } elseif ($contatoTipo === 'noivo') {
        $nomeContratanteSecundario = $nomeNoiva;
    } elseif ($contatoTipo === 'casal') {
        $nomeContratantePrincipal = $nomeNoiva ?: ($nomeNoivo ?: $nomeContratantePrincipal);
        $nomeContratanteSecundario = $nomeNoivo;
    }

    $sig1 = [
        'nome' => $nomeContratantePrincipal,
        'cpf' => formatarCpfCnpj($cliente['cpf_cnpj'] ?? ''),
        'email' => $dadosProposta['email_contato'] ?? ($cliente['contato'] ?? ''),
        'telefone' => $dadosProposta['whatsapp'] ?? '',
        'endereco' => ''
    ];
    
    $sig2 = [
        'nome' => $nomeContratanteSecundario,
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
        $anexoTexto = corrigirMojibakeContrato(IAPropostas::gerarAnexoI($dadosProposta));
    } catch (Exception $e) {
        $anexoTexto = corrigirMojibakeContrato('<h4>Anexo I - Descrição dos Serviços</h4><p>Erro ao gerar descrição automática: ' . $e->getMessage() . '</p>');
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
        $clausula2 = '<h4>CLÁUSULA SEGUNDA - PRAZO E LOCAL DE EXECUÇÃO DOS SERVIÇOS</h4>';

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
        $clausula4 = '<h4>CLÁUSULA QUARTA - DAS ENTREGAS</h4>';
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

        <h4>CLÁUSULA PRIMEIRA - DO OBJETO</h4>
        <p>1.1. A <strong>CONTRATADA</strong> prestará serviços profissionais de cobertura fotográfica e/ou produção audiovisual para o casamento dos <strong>CONTRATANTES</strong>, em conformidade com o detalhamento contido no Anexo I, que integra este instrumento.</p>

        " . $clausula2 . "

        <h4>CLÁUSULA TERCEIRA - VALOR E CONDIÇÕES DE PAGAMENTO</h4>
        <p>3.1. Pela prestação dos serviços contratados, os <strong>CONTRATANTES</strong> pagarão à <strong>CONTRATADA</strong> a quantia total de <strong>R$ " . number_format($valorTotal, 2, ',', '.') . "</strong>, nas seguintes condições: " . htmlspecialchars($condicoesPagamento) . ".</p>
        <p>3.2. O pagamento será efetuado conforme cronograma acordado entre as partes, podendo ser dividido em parcelas mensais, conforme discriminado na proposta comercial aceita pelos CONTRATANTES.</p>
        <p>3.3. Em caso de atraso no pagamento de qualquer parcela, incidirá multa de 2% (dois por cento) sobre o valor da parcela em atraso, bem como juros de mora de 1% (um por cento) ao mês e correção monetária pelo IPCA.</p>

        " . $clausula4 . "


        <h4>CLÁUSULA QUINTA - DA AUTORIZAÇÃO DE IMAGEM</h4>
        <p>5.1. Os <strong>CONTRATANTES</strong> autorizam de forma expressa, irrevogável e gratuita a utilização de suas imagens capturadas durante os eventos e ensaios, para fins de divulgação de portfólio profissional da <strong>CONTRATADA</strong> em suas mídias digitais, redes sociais, site institucional e materiais promocionais, pelo período de 2 (dois) anos contados da data de realização do evento.</p>
        <p>5.2. A autorização prevista no item 5.1 abrange a reprodução, exibição, publicação e divulgação das imagens em qualquer mídia ou formato, desde que sem finalidade lucrativa direta e respeitando o decoro e a boa imagem dos CONTRATANTES.</p>
        <p>5.3. Caso os <strong>CONTRATANTES</strong> desejem restringir a divulgação de imagens específicas, deverão comunicar a <strong>CONTRATADA</strong> por escrito em até 15 (quinze) dias após a data do evento.</p>

        <h4>CLÁUSULA SEXTA - DAS OBRIGAÇÕES DA CONTRATADA</h4>
        <p>6.1. Prestar os serviços contratados com zelo profissional, utilizando equipamentos adequados e profissionais qualificados de sua inteira confiança.<br>
        6.2. Comparecer ao local do evento com antecedência mínima necessária para preparação e montagem dos equipamentos.<br>
        6.3. Disponibilizar aos CONTRATANTES os contatos telefônicos e de WhatsApp da equipe escalada para o dia do evento.<br>
        6.4. Manter sigilo absoluto sobre as informações pessoais e dados compartilhados pelos CONTRATANTES no âmbito da prestação dos serviços.</p>

        <h4>CLÁUSULA SÉTIMA - DAS OBRIGAÇÕES DOS CONTRATANTES</h4>
        <p>7.1. Fornecer alimentação adequada para a equipe de captação caso o tempo total do evento exceda 4 (quatro) horas.<br>
        7.2. Garantir o livre trânsito dos fotógrafos e cinegrafistas no local do evento.<br>
        7.3. Efetuar os pagamentos rigorosamente em dia, conforme cronograma acordado.<br>
        7.4. Disponibilizar os convites e credenciais necessários para acesso da equipe aos locais dos eventos.<br>
        7.5. Informar a <strong>CONTRATADA</strong> com antecedência mínima de 48 (quarenta e oito) horas sobre qualquer alteração de horário ou local dos eventos.</p>

        <h4>CLÁUSULA OITAVA - DA CESSÃO</h4>
        <p>8.1. A <strong>CONTRATADA</strong> poderá ceder ou subcontratar total ou parcialmente os serviços objeto deste contrato a terceiros de sua confiança, mantendo-se como única responsável perante os CONTRATANTES pela fiel execução do objeto contratado.</p>
        <p>8.2. Os <strong>CONTRATANTES</strong> não poderão ceder ou transferir a terceiros os direitos e obrigações decorrentes deste contrato sem a prévia e expressa autorização por escrito da CONTRATADA.</p>


        <h4>CLÁUSULA NONA - DA RESCISÃO CONTRATUAL E MULTAS</h4>
        <p>9.1. Em caso de cancelamento unilateral imotivado por parte dos <strong>CONTRATANTES</strong> com menos de 30 (trinta) dias da data do evento, nenhum valor pago a título de sinal ou reserva será reembolsado, configurando-se como cláusula penal de natureza compensatória.</p>
        <p>9.2. Em caso de cancelamento com antecedência superior a 30 (trinta) dias, os valores já pagos serão devolvidos deduzindo-se o percentual de 20% (vinte por cento) a título de multa compensatória pela reserva de data e custos administrativos já incorridos.</p>
        <p>9.3. Em descumprimento de quaisquer outras cláusulas deste contrato, incidirá multa penal de 10% (dez por cento) sobre o valor remanescente do instrumento, sem prejuízo de perdas e danos.</p>
        <p>9.4. A <strong>CONTRATADA</strong> poderá rescindir o contrato de pleno direito caso os <strong>CONTRATANTES</strong> descumpram com as obrigações pecuniárias aqui assumidas, ficando autorizada a reter os valores eventualmente já recebidos a título de indenização mínima.</p>

        <h4>CLÁUSULA DÉCIMA - DISPOSIÇÕES GERAIS</h4>
        <p>10.1. O presente instrumento não gera vínculo de natureza empregatícia entre as partes contratantes, nem solidariedade trabalhista ou previdenciária.</p>
        <p>10.2. As partes elegem o Anexo I como parte integrante e indissociável deste contrato para todos os fins de direito.</p>
        <p>10.3. Qualquer alteração neste instrumento deverá ser feita por escrito, mediante aditivo contratual assinado por ambas as partes.</p>
        <p>10.4. A tolerância ao descumprimento de qualquer cláusula ou condição deste contrato não constituirá novação ou precedente, nem afetará o exercício posterior do direito pela parte inocente.</p>
        <p>10.5. As partes se comprometem a buscar uma solução amigável, por meio de negociação direta, antes de recorrer a qualquer via judicial para resolução de eventuais controvérsias.</p>


        <h4>CLÁUSULA DÉCIMA PRIMEIRA - DO FORO</h4>
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

        <h4>CLÁUSULA PRIMEIRA - DO OBJETO</h4>
        <p>1.1. O objeto deste contrato é a prestação de serviços de marketing digital, consultoria de posicionamento e/ou produção audiovisual para a <strong>CONTRATANTE</strong>, conforme especificações operacionais e prazos descritos no Anexo I.</p>

        <h4>CLÁUSULA SEGUNDA - VIGÊNCIA</h4>
        <p>2.1. O presente contrato terá vigência de <strong>" . ($dadosProposta['meses_contrato'] ?? 12) . " meses</strong>, com início em <strong>" . (!empty($dadosProposta['data_inicio']) ? date('d/m/Y', strtotime($dadosProposta['data_inicio'])) : date('d/m/Y')) . "</strong>.</p>

        <h4>CLÁUSULA TERCEIRA - VALOR E CONDIÇÕES DE PAGAMENTO</h4>
        <p>3.1. Pela execução dos serviços, a <strong>CONTRATANTE</strong> pagará à <strong>CONTRATADA</strong> a quantia mensal/total de <strong>R$ " . number_format($valorTotal, 2, ',', '.') . "</strong>, nas seguintes condições: " . htmlspecialchars($condicoesPagamento) . ".</p>

        <h4>CLÁUSULA QUARTA - DIREITOS AUTORAIS E PORTFÓLIO</h4>
        <p>4.1. Fica expressamente reservado à <strong>CONTRATADA</strong> o direito de expor as peças criadas e campanhas veiculadas sob a marca da <strong>CONTRATANTE</strong> em seu próprio portfólio comercial, redes sociais e cases de marketing, respeitando a confidencialidade de dados econômicos internos.</p>

        <h4>CLÁUSULA QUINTA - OBRIGAÇÕES DAS PARTES</h4>
        <p>5.1. <strong>DA CONTRATADA:</strong> Executar as tarefas descritas no Anexo I com qualidade técnica, prestar contas mensais e manter sigilo absoluto sobre estratégias comerciais da Contratante.<br>
        5.2. <strong>DO CONTRATANTE:</strong> Fornecer feedbacks operacionais em até 48 horas, fornecer senhas e acessos a contas de publicidade necessários e honrar o calendário de pagamentos.</p>

        <h4>CLÁUSULA SEXTA - RESCISÃO ANTECIPADA</h4>
        <p>6.1. Qualquer das partes poderá rescindir o contrato antes da vigência plena, mediante aviso prévio por escrito de 30 (trinta) dias. No caso de rescisão antecipada imotivada por iniciativa do Contratante, incidirá multa contratual de 10% sobre o saldo devedor remanescente das parcelas futuras.</p>

        <h4>CLÁUSULA SÉTIMA - FORO</h4>
        <p>7.1. Fica eleito o foro da Comarca de Vitória/ES para solucionar qualquer divergência oriunda deste instrumento comercial.</p>

        <p>Vitória/ES, " . $dataContratoPorExtenso . ".</p>
        ";
    }

    // Save new draft contract
    $tituloContrato = corrigirMojibakeContrato($tituloContrato);
    $localContrato = corrigirMojibakeContrato($localContrato);
    $condicoesPagamento = corrigirMojibakeContrato($condicoesPagamento);
    $contratoTexto = corrigirMojibakeContrato($contratoTexto);
    $anexoTexto = corrigirMojibakeContrato($anexoTexto);

    $dadosJson = json_encode([
        'tipo_contrato' => $proposta['tipo'] ?? 'generico',
        'contrato_texto' => $contratoTexto,
        'anexo_texto' => $anexoTexto,
        'signatario_1' => $sig1,
        'signatario_2' => $sig2,
        'signatario_distinto' => $sigDistinto,
        'data_evento' => $dataEvento,
        'local_evento' => '',
        'locais' => $locais,
        'vigencia_meses' => $dadosProposta['meses_contrato'] ?? '',
        'prazo_contrato' => $dadosProposta['prazo_contrato'] ?? '',
        'pagamento_modo' => $dadosProposta['pagamento_modo'] ?? 'parcelado',
        'permitir_parcela_pos_evento' => !empty($dadosProposta['permitir_parcela_pos_evento']),
        'asaas_billing_type' => $dadosProposta['asaas_billing_type'] ?? 'UNDEFINED',
        'asaas_total_parcelas' => (int)($dadosProposta['asaas_total_parcelas'] ?? 1),
        'asaas_first_due_date' => $dadosProposta['asaas_first_due_date'] ?? '',
        'asaas_valor_sinal' => (float)($dadosProposta['asaas_valor_sinal'] ?? 0),
        'asaas_sinal_vencimento' => $dadosProposta['asaas_sinal_vencimento'] ?? ''
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
$proposta = $stmtP->fetch() ?: [];

if (($contrato['status'] ?? 'rascunho') !== 'rascunho') {
    header('Location: ' . raizUrl('/gerenciamento/contrato_visualizar.php?id=' . $contrato['id']));
    exit;
}

$contrato = corrigirMojibakeContrato($contrato);
$contrato['titulo'] = corrigirMojibakeContrato($contrato['titulo'] ?? '');
$contrato['cliente_nome'] = corrigirMojibakeContrato($contrato['cliente_nome'] ?? '');
$contrato['local_contrato'] = corrigirMojibakeContrato($contrato['local_contrato'] ?? '');

$dadosJson = corrigirMojibakeContrato(json_decode($contrato['dados_json'], true) ?: []);
$planoContrato = detectarPlanoCasamento($dadosJson);

// Carregar dados do pacote de casamento (se houver)
$tipoContrato = $dadosJson['tipo_contrato'] ?? $proposta['tipo'] ?? '';
$pacoteDadoAndamento = $dadosJson['pacote_dado_andamento'] ?? $planoContrato;
$valorHeritage = $dadosJson['valor_heritage'] ?? 7900;
$valorCinematic = $dadosJson['valor_cinematic'] ?? 4500;
$valorEssencial = $dadosJson['valor_essencial'] ?? 2800;
$includeBoudoir = !empty($dadosJson['include_boudoir_' . $pacoteDadoAndamento]) || !empty($dadosJson['include_boudoir']);
$includePrewedding = !empty($dadosJson['include_prewedding_' . $pacoteDadoAndamento]) || !empty($dadosJson['include_prewedding']);
$upgradesData = $dadosJson['upgrades'] ?? [];
$itensPersonalizados = $dadosJson['itens_personalizados'] ?? [];

// Wedding upgrades disponíveis para seleção
$weddingUpgrades = [];
if ($tipoContrato === 'casamento') {
    $stmtUpg = $db->prepare("SELECT * FROM servicos WHERE categoria = 'wedding' AND ativo = 1 ORDER BY tipo ASC, nome ASC");
    $stmtUpg->execute();
    $weddingUpgrades = $stmtUpg->fetchAll();
}

$sig1 = $dadosJson['signatario_1'] ?? ['nome' => '', 'cpf' => '', 'email' => '', 'telefone' => '', 'endereco' => ''];
$sig2 = $dadosJson['signatario_2'] ?? ['nome' => '', 'cpf' => '', 'email' => '', 'telefone' => '', 'endereco' => ''];
$sigDistinto = $dadosJson['signatario_distinto'] ?? ['nome' => 'Jeane Poncem', 'email' => 'jeaneponcemsm@gmail.com', 'telefone' => ''];

$sig1['nome'] = corrigirMojibakeContrato($sig1['nome'] ?? '');
$sig1['endereco'] = corrigirMojibakeContrato($sig1['endereco'] ?? '');
$sig1['cpf'] = corrigirMojibakeContrato($sig1['cpf'] ?? '');

$sig2['nome'] = corrigirMojibakeContrato($sig2['nome'] ?? '');
$sig2['endereco'] = corrigirMojibakeContrato($sig2['endereco'] ?? '');
$sig2['cpf'] = corrigirMojibakeContrato($sig2['cpf'] ?? '');

$sigDistinto['nome'] = corrigirMojibakeContrato($sigDistinto['nome'] ?? '');

$dataEvento = $dadosJson['data_evento'] ?? '';
$localEvento = corrigirMojibakeContrato($dadosJson['local_evento'] ?? '');
$vigenciaMeses = $dadosJson['vigencia_meses'] ?? '';
$prazoEntregaDias = (int)($dadosJson['prazo_entrega_dias'] ?? 30);
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

// Decodificar campos de string dentro de locais
foreach ($locais as $key => $val) {
    if (is_string($val)) {
        $locais[$key] = corrigirMojibakeContrato($val);
    }
}

$contratoTexto = corrigirMojibakeContrato($dadosJson['contrato_texto'] ?? '');
$anexoTexto = corrigirMojibakeContrato($dadosJson['anexo_texto'] ?? '');
$dataContratoPorExtenso = dataExtenso($contrato['data_contrato'] ?? date('Y-m-d'));

$asaasBillingType = $dadosJson['asaas_billing_type'] ?? 'UNDEFINED';
$asaasTotalParcelas = (int)($dadosJson['asaas_total_parcelas'] ?? 1);
if ($asaasBillingType !== 'CREDIT_CARD' && (empty($dadosJson['asaas_first_due_date']) || empty($dadosJson['asaas_sinal_vencimento']))) {
    $asaasTotalParcelas = calcularParcelasSaldoCasamento($dadosJson, limiteParcelasPorPlanoCasamento($planoContrato));
}
$asaasFirstDueDate = $dadosJson['asaas_first_due_date'] ?? '';
$asaasValorSinal = $asaasBillingType === 'CREDIT_CARD' ? 0 : round((float)$contrato['valor_total'] * 0.20, 2);
$asaasSinalVencimento = $dadosJson['asaas_sinal_vencimento'] ?? '';

// Save / POST Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = corrigirMojibakeContrato(trim($_POST['titulo'] ?? $contrato['titulo']));
    $valorTotal = (float)str_replace(['.', ','], ['', '.'], $_POST['valor_total'] ?? $contrato['valor_total']);
    $condicoesPagamento = corrigirMojibakeContrato(trim($_POST['condicoes_pagamento'] ?? ''));
    $dataContrato = $_POST['data_contrato'] ?? date('Y-m-d');
    $localContrato = corrigirMojibakeContrato(trim($_POST['local_contrato'] ?? 'Vitória/ES'));

    // Remover double-encoding do conteúdo do contrato (já vem do CKEditor com HTML)
    $contratoTexto = $_POST['contrato_texto'] ?? '';
    $contratoTexto = preg_replace('/&amp;amp;/', '&amp;', $contratoTexto);
    $contratoTexto = corrigirMojibakeContrato($contratoTexto);

    $sig1 = [
        'nome' => corrigirMojibakeContrato(trim($_POST['sig1_nome'] ?? '')),
        'cpf' => formatarCpfCnpj(corrigirMojibakeContrato(trim($_POST['sig1_cpf'] ?? ''))),
        'email' => trim($_POST['sig1_email'] ?? ''),
        'telefone' => trim($_POST['sig1_telefone'] ?? ''),
        'endereco' => corrigirMojibakeContrato(trim($_POST['sig1_endereco'] ?? ''))
    ];

    $sig2 = [
        'nome' => corrigirMojibakeContrato(trim($_POST['sig2_nome'] ?? '')),
        'cpf' => formatarCpfCnpj(corrigirMojibakeContrato(trim($_POST['sig2_cpf'] ?? ''))),
        'email' => trim($_POST['sig2_email'] ?? ''),
        'telefone' => trim($_POST['sig2_telefone'] ?? ''),
        'endereco' => corrigirMojibakeContrato(trim($_POST['sig2_endereco'] ?? ''))
    ];

    $sigDistinto = [
        'nome' => corrigirMojibakeContrato(trim($_POST['sig_distinto_nome'] ?? 'Jeane Poncem')),
        'email' => trim($_POST['sig_distinto_email'] ?? 'jeaneponcemsm@gmail.com'),
        'telefone' => trim($_POST['sig_distinto_telefone'] ?? '')
    ];

    $dataEvento = $_POST['data_evento'] ?? '';
    $localEvento = corrigirMojibakeContrato(trim($_POST['local_evento'] ?? ''));
    $vigenciaMeses = trim($_POST['vigencia_meses'] ?? '');

    // Sincronizar valor total e condições na Cláusula Terceira do HTML
    if (!empty($contratoTexto)) {
        $valorTotalFormated = 'R$ ' . number_format($valorTotal, 2, ',', '.');
        $condicoesHtml = nl2br(htmlspecialchars($condicoesPagamento));

        $tipoProposta = $proposta['tipo'] ?? $dadosJson['tipo_contrato'] ?? '';
        if ($tipoProposta === 'casamento') {
            $novoP = "<p>3.1. Pela prestação dos serviços contratados, os <strong>CONTRATANTES</strong> pagarão à <strong>CONTRATADA</strong> a quantia total de <strong>{$valorTotalFormated}</strong>, nas seguintes condições: {$condicoesHtml}.</p>";
        } else {
            $novoP = "<p>3.1. Pela execução dos serviços, a <strong>CONTRATANTE</strong> pagará à <strong>CONTRATADA</strong> a quantia mensal/total de <strong>{$valorTotalFormated}</strong>, nas seguintes condições: {$condicoesHtml}.</p>";
        }

        if (preg_match('/(<(?:h[1-6]|p)[^>]*>(?:<strong[^>]*>)?\s*CLÁUSULA TERCEIRA.*?<\/(?:h[1-6]|p)>)(.*?)(?=(?:<(?:h[1-6]|p)[^>]*>(?:<strong[^>]*>)?\s*CLÁUSULA QUARTA|<(?:h[1-6]|p)[^>]*>(?:<strong[^>]*>)?\s*CLÁUSULA QUINTA|<p class="p-closing"|$))/is', $contratoTexto, $matches)) {
            $header = $matches[1];
            $conteudo = $matches[2];

            if (preg_match('/(<p>.*?3\.1\..*?<\/p>|<p\s[^>]*>.*?3\.1\..*?<\/p>)/is', $conteudo, $pMatch)) {
                $conteudoAtualizado = str_replace($pMatch[1], $novoP, $conteudo);
            } else {
                $conteudoAtualizado = "\n        " . $novoP . "\n        " . preg_replace('/^\s+/', '', $conteudo);
            }

            $contratoTexto = str_replace($matches[0], $header . $conteudoAtualizado, $contratoTexto);
        }
    }

    // Substituir placeholders padrão pelos valores preenchidos no formulário
    if (!empty($contratoTexto)) {
        // Casamentos
        $contratoTexto = str_replace('[Nome da Noiva]', $sig1['nome'], $contratoTexto);
        $contratoTexto = str_replace('[CPF da Noiva]', $sig1['cpf'], $contratoTexto);
        $contratoTexto = str_replace('[Nome do Noivo]', $sig2['nome'], $contratoTexto);
        $contratoTexto = str_replace('[CPF do Noivo]', $sig2['cpf'], $contratoTexto);

        // Corporativo / Marketing
        $contratoTexto = str_replace('[Nome da Empresa / Cliente]', $sig1['nome'], $contratoTexto);
        $contratoTexto = str_replace('[Documento]', $sig1['cpf'], $contratoTexto);
        $contratoTexto = str_replace('[Responsável]', $sig1['nome'], $contratoTexto);
        $contratoTexto = str_replace('[Endereço]', $sig1['endereco'], $contratoTexto);
    }

    // Sincronizar CPFs e Nomes modificados ou crus diretamente no HTML do contrato
    if (!empty($contratoTexto)) {
        $dadosJsonAntigo = json_decode($contrato['dados_json'], true) ?: [];
        $sig1Antigo = $dadosJsonAntigo['signatario_1'] ?? null;
        $sig2Antigo = $dadosJsonAntigo['signatario_2'] ?? null;

        // Sincronização do Signatário 1
        if ($sig1Antigo) {
            $cpfAntigo = $sig1Antigo['cpf'] ?? '';
            $cpfAntigoCru = preg_replace('/\D/', '', $cpfAntigo);
            $nomeAntigo = $sig1Antigo['nome'] ?? '';

            if (!empty($cpfAntigoCru)) {
                $contratoTexto = str_replace($cpfAntigoCru, $sig1['cpf'], $contratoTexto);
                $contratoTexto = str_replace(formatarCpfCnpj($cpfAntigoCru), $sig1['cpf'], $contratoTexto);
            }
            if (!empty($nomeAntigo) && $nomeAntigo !== $sig1['nome']) {
                $contratoTexto = str_replace($nomeAntigo, $sig1['nome'], $contratoTexto);
            }
        }

        // Sincronização do Signatário 2
        if ($sig2Antigo) {
            $cpfAntigo = $sig2Antigo['cpf'] ?? '';
            $cpfAntigoCru = preg_replace('/\D/', '', $cpfAntigo);
            $nomeAntigo = $sig2Antigo['nome'] ?? '';

            if (!empty($cpfAntigoCru)) {
                $contratoTexto = str_replace($cpfAntigoCru, $sig2['cpf'], $contratoTexto);
                $contratoTexto = str_replace(formatarCpfCnpj($cpfAntigoCru), $sig2['cpf'], $contratoTexto);
            }
            if (!empty($nomeAntigo) && $nomeAntigo !== $sig2['nome']) {
                $contratoTexto = str_replace($nomeAntigo, $sig2['nome'], $contratoTexto);
            }
        }

        // Redundância caso os CPFs estejam crus no HTML mas corretos no formulário
        $sig1CpfCru = preg_replace('/\D/', '', $sig1['cpf']);
        if (strlen($sig1CpfCru) === 11 || strlen($sig1CpfCru) === 14) {
            $contratoTexto = str_replace($sig1CpfCru, $sig1['cpf'], $contratoTexto);
        }

        $sig2CpfCru = preg_replace('/\D/', '', $sig2['cpf']);
        if (strlen($sig2CpfCru) === 11 || strlen($sig2CpfCru) === 14) {
            $contratoTexto = str_replace($sig2CpfCru, $sig2['cpf'], $contratoTexto);
        }
    }

    $anexoTexto = corrigirMojibakeContrato($_POST['anexo_texto'] ?? '');

    $titulo = corrigirMojibakeContrato($titulo);
    $localContrato = corrigirMojibakeContrato($localContrato);
    $condicoesPagamento = corrigirMojibakeContrato($condicoesPagamento);
    $contratoTexto = corrigirMojibakeContrato($contratoTexto);
    $anexoTexto = corrigirMojibakeContrato($anexoTexto);
    $localEvento = corrigirMojibakeContrato($localEvento);
    $locais = [
        'tem_prewedding' => isset($_POST['tem_prewedding']) ? '1' : '',
        'local_prewedding' => corrigirMojibakeContrato(trim($_POST['local_prewedding'] ?? '')),
        'local_prewedding_a_definir' => isset($_POST['local_prewedding_a_definir']) ? '1' : '',
        'data_prewedding' => trim($_POST['data_prewedding'] ?? ''),
        'previsao_prewedding' => corrigirMojibakeContrato(trim($_POST['previsao_prewedding'] ?? '')),
        'previsao_savethedate' => corrigirMojibakeContrato(trim($_POST['previsao_savethedate'] ?? '')),
        'tem_cartorio' => isset($_POST['tem_cartorio']) ? '1' : '',
        'local_cartorio' => corrigirMojibakeContrato(trim($_POST['local_cartorio'] ?? '')),
        'tem_cerimonia' => isset($_POST['tem_cerimonia']) ? '1' : '',
        'local_cerimonia' => corrigirMojibakeContrato(trim($_POST['local_cerimonia'] ?? '')),
        'data_cerimonia' => trim($_POST['data_cerimonia'] ?? '')
    ];
    $locais = corrigirMojibakeContrato($locais);

    // Se for casamento, sincronizar dinamicamente os parágrafos de pré-wedding na Cláusula Quarta do HTML
    if ((($proposta['tipo'] ?? $dadosJson['tipo_contrato'] ?? '') === 'casamento') && !empty($contratoTexto)) {
        if (preg_match('/(<(?:h[1-6]|p)[^>]*>(?:<strong[^>]*>)?\s*CLÁUSULA QUARTA.*?<\/(?:h[1-6]|p)>)(.*?)(?=(?:<(?:h[1-6]|p)[^>]*>(?:<strong[^>]*>)?\s*CLÁUSULA QUINTA|<p class="p-closing"|$))/is', $contratoTexto, $matches)) {
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

    // Collect package data if casamento
    $tipoContratoPost = $dadosJson['tipo_contrato'] ?? ($proposta['tipo'] ?? '');
    $pacoteDadoAndamentoPost = $_POST['pacote_dado_andamento'] ?? $dadosJson['pacote_dado_andamento'] ?? '';
    $upgradesPost = [];
    if (isset($_POST['upgrades']) && is_array($_POST['upgrades'])) {
        // Use the plan key from the submitted data regardless of selected plan
        foreach ($_POST['upgrades'] as $planKey => $upgArr) {
            if (is_array($upgArr)) {
                foreach ($upgArr as $upgId => $val) {
                    $upgradesPost[$planKey][$upgId] = !empty($val);
                }
            }
        }
    }

    // Build dados_json array
    $dadosJsonArr = [
        'tipo_contrato' => $tipoContratoPost,
        'contrato_texto' => $contratoTexto,
        'anexo_texto' => $anexoTexto,
        'signatario_1' => $sig1,
        'signatario_2' => $sig2,
        'signatario_distinto' => $sigDistinto,
        'data_evento' => $dataEvento,
        'local_evento' => $localEvento,
        'vigencia_meses' => $vigenciaMeses,
        'locais' => $locais,
        'pacote_dado_andamento' => $pacoteDadoAndamentoPost,
        'valor_heritage' => (float)($_POST['valor_heritage'] ?? $dadosJson['valor_heritage'] ?? 7900),
        'valor_cinematic' => (float)($_POST['valor_cinematic'] ?? $dadosJson['valor_cinematic'] ?? 4500),
        'valor_essencial' => (float)($_POST['valor_essencial'] ?? $dadosJson['valor_essencial'] ?? 2800),
        'valor_boudoir' => (float)($_POST['valor_boudoir'] ?? $dadosJson['valor_boudoir'] ?? 500),
        'valor_prewedding' => (float)($_POST['valor_prewedding'] ?? $dadosJson['valor_prewedding'] ?? 1100),
        'include_boudoir' => !empty($_POST['include_boudoir']),
        'include_prewedding' => !empty($_POST['include_prewedding']),
        'upgrades' => $upgradesPost,
        'itens_personalizados' => $dadosJson['itens_personalizados'] ?? [],
        'prazo_entrega_dias' => (int)($_POST['prazo_entrega_dias'] ?? 30),
        'asaas_billing_type' => $_POST['asaas_billing_type'] ?? 'UNDEFINED',
        'asaas_total_parcelas' => (int)($_POST['asaas_total_parcelas'] ?? 1),
        'asaas_first_due_date' => $_POST['asaas_first_due_date'] ?? '',
        'asaas_valor_sinal' => (float)str_replace(['.', ','], ['', '.'], $_POST['asaas_valor_sinal'] ?? '0'),
        'asaas_sinal_vencimento' => $_POST['asaas_sinal_vencimento'] ?? ''
    ];

    $dadosJsonUpdated = json_encode($dadosJsonArr, JSON_UNESCAPED_UNICODE);

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

        // Auto-generate Anexo I via IA for contracts without proposal
        $gerouAnexo = false;
        if (empty($contrato['proposta_id']) && ($tipoContratoPost === 'casamento')) {
            try {
                $dadosIA = $dadosJsonArr;
                $dadosIA['cliente_nome'] = $clienteNomeForm;
                $dadosIA['tipo'] = 'casamento';
                $dadosIA['titulo'] = $titulo;
                $dadosIA['valor_total'] = $valorTotal;

                $anexoIa = corrigirMojibakeContrato(IAPropostas::gerarAnexoI($dadosIA));
                $dadosJsonArr['anexo_texto'] = $anexoIa;

                $db->prepare("UPDATE contratos SET dados_json = ? WHERE id = ?")
                   ->execute([json_encode($dadosJsonArr, JSON_UNESCAPED_UNICODE), $id]);
                $gerouAnexo = true;
            } catch (Exception $e) {
                // Silently fails - anexo will use the placeholder
            }
        }

        if (!empty($_POST['ajax'])) {
            responderJson([
                'success' => true,
                'contrato_id' => $id,
                'gerou_anexo' => $gerouAnexo,
                'redirect_url' => raizUrl('/gerenciamento/contrato_visualizar.php?id=' . $id)
            ]);
        }

        header('Location: ' . raizUrl('/gerenciamento/contrato_visualizar.php?id=' . $id));
        exit;
    } catch (Exception $e) {
        $errorMessage = 'Erro ao salvar contrato: ' . $e->getMessage();
    }
}

$tituloPagina = 'Editar Contrato';
require_once __DIR__ . '/../includes/layout/head.php';
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;700&display=swap');

#live-preview-panel { transition: opacity 0.2s ease; }
#live-preview-panel.hidden { display: none; }

#live-preview-content .pdf-body h3 {
    font-family: 'Sora', sans-serif;
    font-size: 15pt;
    font-weight: 700;
    text-transform: uppercase;
    color: #231f20;
    margin: 0;
    padding: 0;
    line-height: 1;
    text-align: center;
    page-break-after: avoid;
    break-after: avoid;
}
#live-preview-content .pdf-body h4 {
    font-family: 'Sora', sans-serif;
    font-size: 10pt;
    font-weight: 700;
    text-transform: uppercase;
    color: #231f20;
    margin: 0;
    padding: 12.3pt 0 0 0;
    line-height: 1;
    text-align: left;
    page-break-after: avoid;
    break-after: avoid;
}
#live-preview-content .pdf-body p {
    font-family: 'Sora', 'Arial', sans-serif;
    font-size: 10pt;
    font-weight: 400;
    color: #231f20;
    margin: 0;
    padding: 12.3pt 0 0 0;
    line-height: 1;
    text-align: justify;
    margin-left: 28.7pt;
}
#live-preview-content .pdf-body .p0 { margin-left: 0.3pt; }
#live-preview-content .pdf-body .p-closing { margin-left: 0.1pt; }
#live-preview-content .pdf-body strong { font-weight: 700; color: #231f20; }
#live-preview-content .pdf-body ul, #live-preview-content .pdf-body ol {
    margin: 0;
    padding-left: 28.7pt;
    font-family: 'Sora', 'Arial', sans-serif;
    font-size: 10pt;
    color: #231f20;
    line-height: 1;
}
@media (max-width: 1400px) {
    #live-preview-content { transform: scale(0.7); transform-origin: top center; }
}
@media (max-width: 1024px) {
    #live-preview-content { transform: scale(0.5); transform-origin: top center; }
}
</style>

<div id="app-wrapper" x-data="contratoGerarApp()">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>
    <main id="main-content" class="content-sheet flex flex-col min-h-screen !bg-[#050505] !text-white">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between border-b border-white/5 pb-6">
            <div>
                <div class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-2">Comercial / Contratos</div>
                <h1 class="text-3xl font-black tracking-tight text-white flex items-center gap-3">
                    <i data-lucide="edit-3" class="w-8 h-8 text-zinc-400"></i>
                    Editar Contrato
                </h1>
                <p class="text-sm font-medium text-zinc-400 mt-1">Refine as cláusulas, preencha os dados dos signatários e use IA para otimizar os termos.</p>
            </div>

            <div class="flex items-center gap-4">
                <button type="button" id="btn-toggle-preview" onclick="togglePreview()" class="px-5 py-2.5 bg-zinc-800 text-zinc-300 hover:bg-zinc-700 active:scale-95 transition-all text-xs font-black uppercase tracking-widest rounded-xl flex items-center gap-2 cursor-pointer border border-white/5">
                    <i data-lucide="eye" class="w-4 h-4"></i> <span id="btn-preview-label">Pré-visualizar</span>
                </button>
                <button type="submit" form="contrato-form" class="px-5 py-2.5 bg-white text-black hover:bg-zinc-200 active:scale-95 transition-all text-xs font-black uppercase tracking-widest rounded-xl flex items-center gap-2 cursor-pointer">
                    <i data-lucide="check" class="w-4 h-4"></i> Salvar Contrato
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
            <input type="hidden" name="pacote_contrato" value="<?= sanitizar($planoContrato) ?>">

            <!-- Live Preview Panel (hidden by default) -->
            <div id="live-preview-panel" class="hidden mb-8">
                <div class="bg-zinc-900/50 border border-white/5 rounded-[32px] p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold text-white flex items-center gap-2">
                            <i data-lucide="eye" class="w-5 h-5 opacity-50"></i>
                            Pré-visualização ao Vivo
                        </h2>
                        <button type="button" onclick="togglePreview()" class="px-4 py-2 bg-zinc-800 text-zinc-300 hover:bg-zinc-700 rounded-xl text-xs font-bold uppercase tracking-widest transition-all flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="edit-3" class="w-4 h-4"></i> Voltar a Editar
                        </button>
                    </div>
                    <div class="flex justify-center">
                        <div id="live-preview-content" class="a4-page-content" style="background:#fff;color:#231f20;width:210mm;min-height:297mm;padding:10pt 50.5pt 15pt 47.3pt;box-shadow:0 20px 50px rgba(0,0,0,0.8);box-sizing:border-box;font-family:'Sora','Arial',sans-serif;font-size:10pt;line-height:1.15;">
                            <div class="pdf-logo-wrapper" style="margin-bottom:30pt;text-align:left;">
                                <img src="<?= raizUrl('/assets/logo-contrato.png') ?>" alt="Poncem Studio Logo" style="width:196px;height:auto;display:block;margin-top:35px;">
                            </div>
                            <div id="live-preview-body" class="pdf-body" style="font-family:'Sora','Arial',sans-serif;font-size:10pt;color:#231f20;line-height:1;"></div>
                            <div class="page-break" style="page-break-before:always;break-before:page;border-top:1px dashed #aaa;margin:20pt 28.7pt;height:0;"></div>
                            <div class="pdf-logo-wrapper pt-10" style="margin-bottom:30pt;text-align:left;">
                                <img src="<?= raizUrl('/assets/logo-contrato.png') ?>" alt="Poncem Studio Logo" style="width:196px;height:auto;display:block;margin-top:35px;">
                            </div>
                            <div id="live-preview-anexo" class="pdf-body" style="font-family:'Sora','Arial',sans-serif;font-size:10pt;color:#231f20;line-height:1;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="contrato-edit-grid" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
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
                             <textarea id="contrato_texto" name="contrato_texto"><?= htmlspecialchars($contratoTexto, ENT_NOQUOTES, 'UTF-8') ?></textarea>
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
                             <textarea id="anexo_texto" name="anexo_texto"><?= htmlspecialchars($anexoTexto, ENT_NOQUOTES, 'UTF-8') ?></textarea>
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
                                        <input type="number" id="calc_sinal_pct" value="20" min="20" max="20" readonly class="w-full bg-black/60 border border-white/5 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-white transition-colors">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[8px] font-bold uppercase tracking-wider text-zinc-500">Vencimento do Sinal</label>
                                        <input type="date" id="calc_sinal_data" value="<?= $asaasSinalVencimento ?: date('Y-m-d') ?>" class="w-full bg-black/60 border border-white/5 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-white transition-colors">
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

                    <?php if ($tipoContrato === 'casamento'): ?>
                    <!-- Pacote de Casamento -->
                    <div class="bg-zinc-900/50 border border-white/5 rounded-[32px] p-8" x-data="pacoteCasamentoApp()">
                        <h2 class="text-md font-bold text-white mb-6 flex items-center gap-2">
                            <i data-lucide="heart" class="w-4.5 h-4.5 opacity-50 text-rose-500"></i>
                            Pacote de Casamento
                        </h2>

                        <div class="space-y-5">
                            <!-- Seleção do Plano -->
                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Plano Escolhido</label>
                                <select name="pacote_dado_andamento" x-model="planoId" @change="recalcular()"
                                        class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-3 text-xs text-white focus:border-white transition-all outline-none cursor-pointer">
                                    <option value="heritage" <?= $pacoteDadoAndamento === 'heritage' ? 'selected' : '' ?>>Experiência Heritage</option>
                                    <option value="cinematic" <?= $pacoteDadoAndamento === 'cinematic' ? 'selected' : '' ?>>Experiência Cinematic</option>
                                    <option value="essencial" <?= $pacoteDadoAndamento === 'essencial' ? 'selected' : '' ?>>Registro Essencial</option>
                                </select>
                            </div>

                            <!-- Valores dos Planos (Exibe apenas o valor do plano selecionado) -->
                            <div>
                                <div class="space-y-1.5" x-show="planoId === 'heritage'">
                                    <label class="text-[8px] font-black uppercase tracking-wider text-zinc-500">Heritage (R$)</label>
                                    <input type="number" step="0.01" name="valor_heritage" x-model="vHeritage" @input="recalcular()"
                                           class="w-full bg-black/60 border border-white/5 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-white transition-all">
                                </div>
                                <div class="space-y-1.5" x-show="planoId === 'cinematic'">
                                    <label class="text-[8px] font-black uppercase tracking-wider text-zinc-500">Cinematic (R$)</label>
                                    <input type="number" step="0.01" name="valor_cinematic" x-model="vCinematic" @input="recalcular()"
                                           class="w-full bg-black/60 border border-white/5 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-white transition-all">
                                </div>
                                <div class="space-y-1.5" x-show="planoId === 'essencial'">
                                    <label class="text-[8px] font-black uppercase tracking-wider text-zinc-500">Essencial (R$)</label>
                                    <input type="number" step="0.01" name="valor_essencial" x-model="vEssencial" @input="recalcular()"
                                           class="w-full bg-black/60 border border-white/5 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-white transition-all">
                                </div>
                            </div>

                            <!-- Adicionais Fixos -->
                            <div class="p-4 bg-black/40 border border-white/5 rounded-2xl space-y-3">
                                <p class="text-[10px] font-black uppercase tracking-widest text-zinc-400">Adicionais</p>

                                <label class="flex items-center justify-between cursor-pointer">
                                    <span class="text-[11px] font-bold text-zinc-200">Boudoir da Noiva <span class="text-zinc-500 font-normal">(+R$ <span x-text="vBoudoir.toFixed(2).replace('.',',')"></span>)</span></span>
                                    <input type="checkbox" name="include_boudoir" x-model="inclBoudoir" @change="recalcular()"
                                           class="w-4 h-4 rounded accent-white" <?= $includeBoudoir ? 'checked' : '' ?>>
                                </label>

                                <div class="space-y-1.5" x-show="inclBoudoir" x-collapse>
                                    <label class="text-[8px] font-black uppercase tracking-wider text-zinc-500">Valor Boudoir (R$)</label>
                                    <input type="number" step="0.01" name="valor_boudoir" x-model="vBoudoir" @input="recalcular()"
                                           class="w-full bg-black/60 border border-white/5 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-white transition-all">
                                </div>

                                <label class="flex items-center justify-between cursor-pointer pt-2 border-t border-white/5">
                                    <span class="text-[11px] font-bold text-zinc-200">Ensaio Pré-Wedding <span class="text-zinc-500 font-normal">(+R$ <span x-text="vPrewedding.toFixed(2).replace('.',',')"></span>)</span></span>
                                    <input type="checkbox" name="include_prewedding" x-model="inclPrewedding" @change="recalcular()"
                                           class="w-4 h-4 rounded accent-white" <?= $includePrewedding ? 'checked' : '' ?>>
                                </label>

                                <div class="space-y-1.5" x-show="inclPrewedding" x-collapse>
                                    <label class="text-[8px] font-black uppercase tracking-wider text-zinc-500">Valor Pré-Wedding (R$)</label>
                                    <input type="number" step="0.01" name="valor_prewedding" x-model="vPrewedding" @input="recalcular()"
                                           class="w-full bg-black/60 border border-white/5 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-white transition-all">
                                </div>
                            </div>

                            <!-- Upgrades Dinâmicos -->
                            <?php if (!empty($weddingUpgrades)): ?>
                            <div class="p-4 bg-black/40 border border-white/5 rounded-2xl space-y-3">
                                <p class="text-[10px] font-black uppercase tracking-widest text-zinc-400">Adicionais Disponíveis</p>
                                <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                                    <?php foreach ($weddingUpgrades as $upg):
                                        $upgId = $upg['id'];
                                        $isBoudoir = stripos($upg['nome'], 'boudoir') !== false;
                                        $isPrewedding = stripos($upg['nome'], 'pre-wedding') !== false || stripos($upg['nome'], 'prewedding') !== false;
                                        if ($isBoudoir || $isPrewedding) continue;

                                        $upgradeChecked = !empty($upgradesData[$pacoteDadoAndamento][$upgId]);
                                        $upgPreco = (float)$upg['preco_venda'];
                                    ?>
                                    <label class="flex items-center justify-between p-3 rounded-xl upgrade-card cursor-pointer">
                                        <div class="flex flex-col">
                                            <span class="text-[11px] font-bold text-zinc-200"><?= sanitizar($upg['nome']) ?></span>
                                            <span class="text-[9px] text-zinc-500">R$ <?= number_format($upgPreco, 2, ',', '.') ?></span>
                                        </div>
                                        <input type="hidden" name="upgrades[<?= $pacoteDadoAndamento ?>][<?= $upgId ?>]" value="0">
                                        <input type="checkbox" name="upgrades[<?= $pacoteDadoAndamento ?>][<?= $upgId ?>]" value="1"
                                               class="w-4 h-4 rounded accent-white"
                                               <?= $upgradeChecked ? 'checked' : '' ?>
                                               @change="recalcular()">
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Total do Pacote -->
                            <div class="p-4 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase tracking-widest text-zinc-400">Valor Calculado do Pacote</span>
                                <span class="text-lg font-black text-white" x-text="'R$ ' + totalPacote.toFixed(2).replace('.',',')"></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Configuração do Faturamento Asaas -->
                    <div class="bg-zinc-900/50 border border-white/5 rounded-[32px] p-8">
                        <h2 class="text-lg font-bold text-white mb-6 flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <i data-lucide="wallet" class="w-5 h-5 opacity-50"></i>
                                Cobrança Asaas
                            </span>
                            <span class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Automático</span>
                        </h2>

                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Meio de Faturamento</label>
                                <select name="asaas_billing_type" class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-3 text-xs text-white focus:border-white transition-all outline-none cursor-pointer">
                                    <option value="UNDEFINED" <?= $asaasBillingType === 'UNDEFINED' ? 'selected' : '' ?>>Sem preferência (Cliente escolhe Boleto/Pix/Cartão)</option>
                                    <option value="BOLETO" <?= $asaasBillingType === 'BOLETO' ? 'selected' : '' ?>>Boleto Bancário</option>
                                    <option value="PIX" <?= $asaasBillingType === 'PIX' ? 'selected' : '' ?>>Apenas Pix</option>
                                    <option value="CREDIT_CARD" <?= $asaasBillingType === 'CREDIT_CARD' ? 'selected' : '' ?>>Apenas Cartão de Crédito</option>
                                </select>
                                <p class="text-[10px] text-zinc-500">Obs: Se o cliente escolher Cartão de Crédito, as taxas serão cobradas dele de acordo com a sua conta do Asaas.</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Qtd. Parcelas (Saldo/Cartão)</label>
                                    <input type="number" name="asaas_total_parcelas" value="<?= $asaasTotalParcelas ?>" min="1" max="60"
                                           class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-3 text-xs text-white focus:border-white transition-all outline-none">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Vencimento da 1ª Parcela</label>
                                    <input type="date" name="asaas_first_due_date" value="<?= $asaasFirstDueDate ?>"
                                           class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-3 text-xs text-white focus:border-white transition-all outline-none">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Valor do Sinal (Entrada)</label>
                                    <input type="text" name="asaas_valor_sinal" value="<?= $asaasValorSinal > 0 ? number_format($asaasValorSinal, 2, ',', '.') : '' ?>" placeholder="R$ 0,00"
                                           class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-3 text-xs text-white focus:border-white transition-all outline-none">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Vencimento do Sinal</label>
                                    <input type="date" name="asaas_sinal_vencimento" value="<?= $asaasSinalVencimento ?>"
                                           class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-3 text-xs text-white focus:border-white transition-all outline-none">
                                </div>
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

                        <!-- Prazo de Entrega -->
                        <div class="space-y-1.5 mt-6">
                            <label class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Prazo de Entrega do Material (dias úteis)</label>
                            <input type="number" name="prazo_entrega_dias" value="<?= $prazoEntregaDias ?>" min="1" max="365"
                                   class="w-full bg-black/60 border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white outline-none focus:border-white transition-colors">
                            <p class="text-[8px] text-zinc-500">Usado na cláusula 4.2: "O material fotográfico final será entregue em até X dias úteis a partir da data do evento"</p>
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

<!-- Loading Modal -->
<div id="loading-modal" class="fixed inset-0 bg-black/90 backdrop-blur-xl z-[9999] hidden items-center justify-center" style="display: none;">
    <div class="bg-zinc-900 border border-white/10 rounded-[2rem] p-10 w-full max-w-md mx-4 shadow-2xl text-center">
        <div class="w-16 h-16 rounded-full border-4 border-zinc-700 border-t-white animate-spin mx-auto mb-8"></div>
        <h3 class="text-lg font-black text-white mb-2" id="loading-modal-title">Salvando contrato...</h3>
        <p class="text-sm text-zinc-400" id="loading-modal-step">Aguarde enquanto processamos suas informações.</p>
        <div class="mt-6 space-y-3 text-left">
            <div class="flex items-center gap-3" id="loading-step-1">
                <div class="w-6 h-6 rounded-full bg-zinc-800 flex items-center justify-center flex-shrink-0" id="loading-icon-1">
                    <svg class="w-3.5 h-3.5 text-zinc-500 animate-spin" id="loading-spinner-1" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg>
                    <svg class="w-3.5 h-3.5 text-emerald-500 hidden" id="loading-check-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span class="text-sm font-medium text-zinc-300">Salvando dados do contrato...</span>
            </div>
            <div class="flex items-center gap-3 hidden" id="loading-step-2">
                <div class="w-6 h-6 rounded-full bg-zinc-800 flex items-center justify-center flex-shrink-0" id="loading-icon-2">
                    <svg class="w-3.5 h-3.5 text-zinc-500" id="loading-spinner-2" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg>
                    <svg class="w-3.5 h-3.5 text-emerald-500 hidden" id="loading-check-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span class="text-sm font-medium text-zinc-300">Gerando Anexo I via inteligência artificial...</span>
            </div>
            <div class="flex items-center gap-3 hidden" id="loading-step-3">
                <div class="w-6 h-6 rounded-full bg-zinc-800 flex items-center justify-center flex-shrink-0" id="loading-icon-3">
                    <svg class="w-3.5 h-3.5 text-zinc-500" id="loading-spinner-3" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg>
                    <svg class="w-3.5 h-3.5 text-emerald-500 hidden" id="loading-check-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span class="text-sm font-medium text-zinc-300">Redirecionando para visualização...</span>
            </div>
        </div>
    </div>
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

function pacoteCasamentoApp() {
    return {
        planoId: '<?= $pacoteDadoAndamento ?>',
        vHeritage: <?= (float)$valorHeritage ?>,
        vCinematic: <?= (float)$valorCinematic ?>,
        vEssencial: <?= (float)$valorEssencial ?>,
        vBoudoir: <?= (float)($dadosJson['valor_boudoir'] ?? 500) ?>,
        vPrewedding: <?= (float)($dadosJson['valor_prewedding'] ?? 1100) ?>,
        inclBoudoir: <?= $includeBoudoir ? 'true' : 'false' ?>,
        inclPrewedding: <?= $includePrewedding ? 'true' : 'false' ?>,
        init() {
            this.$nextTick(() => this.recalcular());
        },
        get totalPacote() {
            return this.calcularTotal();
        },
        recalcular() {
            this.$nextTick(() => {
                let totalInput = document.querySelector('input[name="valor_total"]');
                if (totalInput) {
                    let val = this.totalPacote.toFixed(2).replace('.', ',');
                    totalInput.value = val;
                }
                // Sync CKEditor immediately so CLÁUSULA TERCEIRA reflects the new total
                if (window.contratoEditor && typeof sincronizarTudoNoEditor === 'function') {
                    try { sincronizarTudoNoEditor(); } catch (e) {}
                }
            });
        },
        calcularTotal() {
            let base = 0;
            if (this.planoId === 'heritage') base = parseFloat(this.vHeritage) || 0;
            else if (this.planoId === 'cinematic') base = parseFloat(this.vCinematic) || 0;
            else if (this.planoId === 'essencial') base = parseFloat(this.vEssencial) || 0;

            if (this.inclBoudoir) base += parseFloat(this.vBoudoir) || 0;
            if (this.inclPrewedding) base += parseFloat(this.vPrewedding) || 0;

            return base;
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

    // ============================================================
    // SINCRONIZAÇÃO EM TEMPO REAL — TUDO NUM ÚNICO setData()
    // ============================================================

    const TIPO_CONTRATO = <?= json_encode($proposta['tipo'] ?? $dadosJson['tipo_contrato'] ?? '') ?>;

    function escreverDataExtenso(dataStr) {
        if (!dataStr) return '';
        const meses = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
        const d = new Date(dataStr + 'T12:00:00');
        if (isNaN(d.getTime())) return dataStr;
        return d.getDate() + ' de ' + meses[d.getMonth()] + ' de ' + d.getFullYear();
    }

    function sincronizarTudoNoEditor() {
        if (!window.contratoEditor) return;
        let html = window.contratoEditor.getData();
        const hadChanges = { val: false };

        html = sincronizarPlaceholders(html);
        html = sincronizarSignatariosHTML(html);
        html = sincronizarCondicoes(html);
        html = sincronizarFechamento(html);
        html = sincronizarLocais(html);

        window.contratoEditor.setData(html);
    }
    window.sincronizarTudoNoEditor = sincronizarTudoNoEditor;

    // ---------- placeholders (sempre funciona) ----------
    function sincronizarPlaceholders(html) {
        const sig1Nome = document.querySelector('[name="sig1_nome"]')?.value || '';
        const sig1Cpf  = document.querySelector('[name="sig1_cpf"]')?.value || '';
        const sig2Nome = document.querySelector('[name="sig2_nome"]')?.value || '';
        const sig2Cpf  = document.querySelector('[name="sig2_cpf"]')?.value || '';
        const sig1End  = document.querySelector('[name="sig1_endereco"]')?.value || '';

        return html
            .replace(/\[Nome da Noiva\]/g, sig1Nome || '[Nome da Noiva]')
            .replace(/\[CPF da Noiva\]/g, sig1Cpf || '[CPF da Noiva]')
            .replace(/\[Nome do Noivo\]/g, sig2Nome || '[Nome do Noivo]')
            .replace(/\[CPF do Noivo\]/g, sig2Cpf || '[CPF do Noivo]')
            .replace(/\[Nome da Empresa \/ Cliente\]/g, sig1Nome || '[Nome da Empresa / Cliente]')
            .replace(/\[Documento\]/g, sig1Cpf || '[Documento]')
            .replace(/\[Responsável\]/g, sig1Nome || '[Responsável]')
            .replace(/\[Endereço\]/g, sig1End || '[Endereço]');
    }

    // ---------- signatários: nomes + cpfs + endereço direto no HTML ----------
    function sincronizarSignatariosHTML(html) {
        const sig1Nome = document.querySelector('[name="sig1_nome"]')?.value || '';
        const sig1Cpf  = document.querySelector('[name="sig1_cpf"]')?.value || '';
        const sig2Nome = document.querySelector('[name="sig2_nome"]')?.value || '';
        const sig2Cpf  = document.querySelector('[name="sig2_cpf"]')?.value || '';
        const sig1End  = document.querySelector('[name="sig1_endereco"]')?.value || '';

        // Names in CONTRATANTES paragraph
        const contratantesRegex = /(<p[^>]*><strong>CONTRATANTES:<\/strong><br>\s*<strong>)([^<]+)(<\/strong>,[^<]*<strong>)([^<]+)(<\/strong>)/;
        const contratantesMatch = html.match(contratantesRegex);
        if (contratantesMatch && (sig1Nome || sig2Nome)) {
            const newSig1 = sig1Nome || contratantesMatch[2];
            const newSig2 = sig2Nome || contratantesMatch[4];
            html = html.replace(contratantesRegex, `$1${newSig1}$3${newSig2}$5`);
        }

        // CPFs in order (1st = sig1, 2nd = sig2)
        if (sig1Cpf || sig2Cpf) {
            const cpfRegex = /(?:CPF\s*n[º°]\s*|CPF\/CNPJ\s*n[º°]\s*|Documento\s*)[\d.\/-]+/gi;
            const matches = html.match(cpfRegex);
            if (matches) {
                let idx = 0;
                html = html.replace(cpfRegex, (m) => {
                    idx++;
                    if (idx === 1 && sig1Cpf) return `CPF nº ${sig1Cpf}`;
                    if (idx === 2 && sig2Cpf) return `CPF nº ${sig2Cpf}`;
                    return m;
                });
            }
        }

        // Endereço directly in HTML (look for "Endereço:" or "Endereco:" pattern)
        if (sig1End) {
            const endRegex = /(Endereço:\s*|Endereco:\s*)[^<]+(?=<|$)/i;
            html = html.replace(endRegex, `$1${sig1End}`);
        }

        // CONTRATANTE (singular, marketing)
        const contratanteRegex = /(<p><strong>CONTRATANTE:<\/strong><br>\s*<strong>)[^<]+(<\/strong>)/;
        if (sig1Nome) {
            html = html.replace(contratanteRegex, `$1${sig1Nome}$2`);
        }

        return html;
    }

    // ---------- Cláusula Terceira — valor + condições ----------
    function sincronizarCondicoes(html) {
        const totalInput = document.querySelector('[name="valor_total"]')?.value || '0,00';
        const condicoes = document.getElementById('condicoes_pagamento')?.value || '';
        const condicoesHtml = condicoes.replace(/\n/g, '<br>');

        const regexClausula3 = /(<(?:h[1-6]|p)[^>]*>(?:<strong[^>]*>)?\s*CLÁUSULA TERCEIRA[\s\S]*?<\/(?:h[1-6]|p)>)([\s\S]*?)(?=(?:<h[1-6]|<p)[^>]*>(?:<strong[^>]*>)?\s*CLÁUSULA QUARTA|(?:<h[1-6]|<p)[^>]*>(?:<strong[^>]*>)?\s*CLÁUSULA QUINTA|<p class="p-closing"|$)/i;
        const match = html.match(regexClausula3);

        const tipo = TIPO_CONTRATO;
        const novoP = tipo === 'casamento'
            ? `<p>3.1. Pela prestação dos serviços contratados, os <strong>CONTRATANTES</strong> pagarão à <strong>CONTRATADA</strong> a quantia total de <strong>R$ ${totalInput}</strong>, nas seguintes condições: ${condicoesHtml}.</p>`
            : `<p>3.1. Pela execução dos serviços, a <strong>CONTRATANTE</strong> pagará à <strong>CONTRATADA</strong> a quantia mensal/total de <strong>R$ ${totalInput}</strong>, nas seguintes condições: ${condicoesHtml}.</p>`;

        if (match) {
            const header = match[1];
            let conteudo = match[2];

            const regexP31 = /(<p>[\s\S]*?3\.1\.[\s\S]*?<\/p>|<p\s[^>]*>[\s\S]*?3\.1\.[\s\S]*?<\/p>)/i;
            const pMatch = conteudo.match(regexP31);
            if (pMatch) {
                conteudo = conteudo.replace(pMatch[0], novoP);
            } else {
                conteudo = "\n        " + novoP + "\n        " + conteudo.trim();
            }

            return html.replace(match[0], header + conteudo);
        }

        // Fallback: regex falhou → substituir diretamente no HTML qualquer <p>3.1.
        console.log('[sincronizarCondicoes] regex CLÁUSULA TERCEIRA falhou — usando fallback');
        const regexP31Global = /(<p[\s\S]*?>)\s*3\.1\.\s*Pela\s+(prestação|execução)[\s\S]*?<\/p>/i;
        const p31match = html.match(regexP31Global);
        if (p31match) {
            return html.replace(p31match[0], novoP);
        }
        return html;
    }

    // ---------- linha de fechamento (cidade, data) ----------
    function sincronizarFechamento(html) {
        const local = document.querySelector('[name="local_contrato"]')?.value || '';
        const dataContrato = document.querySelector('[name="data_contrato"]')?.value || '';
        if (!local && !dataContrato) return html;

        const dataExt = escreverDataExtenso(dataContrato);
        const textoFechamento = dataExt ? `${local}, ${dataExt}.` : `${local}.`;

        const regexFechamento = /(<p\s[^>]*class="p-closing"[^>]*>)[^<]+(<\/p>)/i;
        if (html.match(regexFechamento)) {
            html = html.replace(regexFechamento, `$1${textoFechamento}$2`);
        }

        return html;
    }

    // ---------- locais (Cláusulas 2 e 4, apenas casamento) ----------
    function sincronizarLocais(html) {
        if (TIPO_CONTRATO !== 'casamento') return html;

        const temPrewedding = document.querySelector('[name="tem_prewedding"]')?.checked || false;
        const temCartorio   = document.querySelector('[name="tem_cartorio"]')?.checked || false;
        const temCerimonia  = document.querySelector('[name="tem_cerimonia"]')?.checked || false;
        const dataCerimoniaRaw = document.querySelector('[name="data_cerimonia"]')?.value || '';
        const localCerimonia = document.querySelector('[name="local_cerimonia"]')?.value || '';
        const dataCerimonia = dataCerimoniaRaw ? dataCerimoniaRaw.split('-').reverse().join('/') : '';

        // --- Clause 2 ---
        let n = 1;
        let novaClausula2 = '<h4>CLÁUSULA SEGUNDA - PRAZO E LOCAL DE EXECUÇÃO DOS SERVIÇOS</h4>';

        if (temPrewedding) {
            novaClausula2 += `<p>2.${n}. Ensaio Pré-Wedding: Previsto para <strong>data a ser definida em comum acordo</strong>, em local a ser definido em comum acordo entre as partes.</p>`;
            n++;
        }
        if (temCartorio) {
            novaClausula2 += `<p>2.${n}. Cerimônia Civil: Prevista para <strong>${dataCerimonia || 'data a ser definida em comum acordo'}</strong>, em ${localCerimonia || 'a definir em comum acordo entre as partes'}.</p>`;
            n++;
        }
        if (temCerimonia) {
            novaClausula2 += `<p>2.${n}. Cerimônia e Festa: Prevista para <strong>${dataCerimonia || 'data a ser definida em comum acordo'}</strong>, em ${localCerimonia || 'local a ser definido em comum acordo'}.</p>`;
            n++;
        }
        if (n === 1) {
            novaClausula2 += '<p>2.1. Os serviços serão executados na data do evento, em local a definir em comum acordo entre as partes.</p>';
            n++;
        }
        novaClausula2 += `<p>2.${n}. A duração padrão da cobertura será aquela descrita e especificada no Anexo I, podendo ser ajustada mediante comum acordo entre as partes.</p>`;
        n++;
        novaClausula2 += `<p>2.${n}. A CONTRATADA não se responsabiliza por atrasos ou impossibilidade de execução dos serviços decorrentes de condições climáticas adversas, falhas de energia elétrica no local do evento ou quaisquer outros fatores alheios à sua vontade, comprometendo-se, nestes casos, a remarcar a data mediante comum acordo com os CONTRATANTES.</p>`;

        const regexClausula2 = /<(?:h[1-6]|p)[^>]*>(?:<strong[^>]*>)?\s*CLÁUSULA SEGUNDA[\s\S]*?<\/(?:h[1-6]|p)>[\s\S]*?(?=(?:<h[1-6]|<p)[^>]*>(?:<strong[^>]*>)?\s*CLÁUSULA TERCEIRA)/i;
        html = html.replace(regexClausula2, novaClausula2);

        // --- Clause 4 ---
        const regexClausula4 = /(<(?:h[1-6]|p)[^>]*>(?:<strong[^>]*>)?\s*CLÁUSULA QUARTA[\s\S]*?<\/(?:h[1-6]|p)>)([\s\S]*?)(?=(?:<h[1-6]|<p)[^>]*>(?:<strong[^>]*>)?\s*CLÁUSULA QUINTA|<p class="p-closing"|$)/i;
        const match4 = html.match(regexClausula4);
        if (match4) {
            const header4 = match4[1];
            let conteudo4 = match4[2];

            const p1 = conteudo4.match(/<p>[\s\S]*?material fotográfico[\s\S]*?<\/p>/i);
            const pBackup = conteudo4.match(/<p>[\s\S]*?backup de segurança[\s\S]*?<\/p>/i);

            const prazoDias = parseInt(document.querySelector('[name="prazo_entrega_dias"]')?.value || '30') || 30;
            const prazoExtenso = typeof escreverNumero === 'function' ? escreverNumero(prazoDias) : prazoDias.toString();
            const novoP42 = `<p>4.2. O material fotográfico final será entregue em até <strong>${prazoDias} (${prazoExtenso}) dias úteis</strong> a partir da data de realização do evento, salvo disposição em contrário prevista no Anexo I.</p>`;

            const novosP4 = [];
            novosP4.push(p1 ? p1[0] : '<p>4.1. A <strong>CONTRATADA</strong> entregará aos <strong>CONTRATANTES</strong> o material fotográfico e/ou audiovisual devidamente editado, conforme especificações técnicas e prazos estabelecidos no Anexo I, parte integrante deste instrumento.</p>');
            novosP4.push(novoP42);

            let idx = 3;
            if (temPrewedding) {
                novosP4.push(`<p>4.${idx}. O prazo previsto para a entrega das fotos do ensaio Pré-Wedding é de <strong>10 dias úteis após a seleção das fotos pelo casal</strong>.</p>`);
                idx++;
                novosP4.push(`<p>4.${idx}. O prazo previsto para a entrega do Save the Date é de <strong>Até 15 dias úteis após a realização do ensaio</strong>.</p>`);
                idx++;
            }

            novosP4.push(pBackup ? pBackup[0] : `<p>4.${idx}. A <strong>CONTRATADA</strong> não se responsabiliza pela perda do material decorrente de caso fortuito ou força maior, obrigando-se, entretanto, a manter backup de segurança de todos os arquivos pelo prazo mínimo de 90 (noventa) dias após a entrega.</p>`);

            html = html.replace(match4[0], header4 + '\n        ' + novosP4.join('\n        ') + '\n        ');
        }

        return html;
    }

    // ---------- debounce ----------
    function debounce(fn, ms) {
        let timer;
        return function(...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), ms);
        };
    }

    const debouncedSyncTudo = debounce(sincronizarTudoNoEditor, 350);
    const debouncedSyncRapido = debounce(sincronizarTudoNoEditor, 150);
    const debouncedSyncPesado = debounce(sincronizarTudoNoEditor, 400);

    // ---------- event listeners ----------
    // Signatários (rápido)
    document.querySelectorAll('[name="sig1_nome"], [name="sig1_cpf"], [name="sig2_nome"], [name="sig2_cpf"], [name="sig1_endereco"]').forEach(el => {
        if (el) el.addEventListener('input', debouncedSyncRapido);
    });

    // Valor e condições (médio)
    document.querySelectorAll('[name="valor_total"], [name="condicoes_pagamento"]').forEach(el => {
        if (el) el.addEventListener('input', debouncedSyncTudo);
    });

    // Data e local do contrato (fechamento)
    document.querySelectorAll('[name="data_contrato"], [name="local_contrato"]').forEach(el => {
        if (el) el.addEventListener('change', debouncedSyncTudo);
        if (el && el.name === 'local_contrato') el.addEventListener('input', debouncedSyncTudo);
    });

    // Locais de casamento (pesado — reconstrói cláusulas)
    document.querySelectorAll('[name="tem_prewedding"], [name="tem_cartorio"], [name="tem_cerimonia"], [name="local_prewedding"], [name="data_prewedding"], [name="previsao_prewedding"], [name="previsao_savethedate"], [name="local_cartorio"], [name="local_cerimonia"], [name="data_cerimonia"], [name="local_prewedding_a_definir"], [name="prazo_entrega_dias"]').forEach(el => {
        if (el) {
            el.addEventListener('change', debouncedSyncPesado);
            if (el.type === 'text' || el.tagName === 'TEXTAREA') {
                el.addEventListener('input', debouncedSyncPesado);
            }
        }
    });

    // Outros campos gerais
    document.querySelectorAll('[name="data_evento"], [name="local_evento"], [name="vigencia_meses"], [name="pacote_dado_andamento"], [name="asaas_sinal_vencimento"]').forEach(el => {
        if (el) el.addEventListener('change', debouncedSyncTudo);
    });

    // Calculadora de condições — recalcula automaticamente ao mudar (silencioso, sem alert)
    const debouncedCalcular = debounce(() => {
        // Sync Asaas billing fields → calculator fields before calculating
        const asaasSinalVenc = document.querySelector('[name="asaas_sinal_vencimento"]');
        if (asaasSinalVenc?.value) {
            const calcData = document.getElementById('calc_sinal_data');
            if (calcData) calcData.value = asaasSinalVenc.value;
        }
        const asaasBillingType = document.querySelector('[name="asaas_billing_type"]');
        if (asaasBillingType?.value) {
            const calcMeio = document.getElementById('calc_meio');
            if (calcMeio) {
                if (asaasBillingType.value === 'CREDIT_CARD') calcMeio.value = 'Cartão de Crédito';
                else if (asaasBillingType.value === 'PIX') calcMeio.value = 'Pix';
                else if (asaasBillingType.value === 'BOLETO') calcMeio.value = 'Boleto Bancário';
                else calcMeio.value = 'Pix ou Boleto Bancário';
                if (typeof toggleMeioPagamento === 'function') toggleMeioPagamento();
            }
        }
        const totalInput = document.querySelector('[name="valor_total"]')?.value || '0';
        const total = parseFloat(totalInput.replace(/[^\d,]/g, '').replace(',', '.'));
        const sinalDataStr = document.getElementById('calc_sinal_data')?.value;
        if (isNaN(total) || total <= 0 || !sinalDataStr) return;
        const meio = document.getElementById('calc_meio')?.value;
        if (meio !== 'Cartão de Crédito') {
            const dataEvento = document.querySelector('[name="data_evento"]')?.value;
            if (!dataEvento) return;
        }
        console.log('[AutoCalc] chamando calcularCondicoes()');
        calcularCondicoes();
    }, 400);
    document.querySelectorAll('#calc_sinal_data, #calc_meio, #calc_parcelas, #calc_ultimo_data, [name="asaas_total_parcelas"], [name="asaas_sinal_vencimento"], [name="asaas_billing_type"], [name="asaas_first_due_date"]').forEach(el => {
        if (el) el.addEventListener('change', debouncedCalcular);
    });

    // ============================================================
    // LOADING MODAL + AJAX SAVE
    // ============================================================

    function mostrarModalLoading() {
        const modal = document.getElementById('loading-modal');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.style.display = 'flex';

        // Reset all steps
        for (let i = 1; i <= 3; i++) {
            const step = document.getElementById('loading-step-' + i);
            const spinner = document.getElementById('loading-spinner-' + i);
            const check = document.getElementById('loading-check-' + i);
            if (step) step.classList.remove('hidden');
            if (spinner) { spinner.classList.remove('hidden'); spinner.classList.add('animate-spin'); }
            if (check) check.classList.add('hidden');
        }
        document.getElementById('loading-step-2').classList.add('hidden');
        document.getElementById('loading-step-3').classList.add('hidden');
        document.getElementById('loading-modal-title').textContent = 'Salvando contrato...';
        document.getElementById('loading-modal-step').textContent = 'Aguarde enquanto processamos suas informações.';
    }

    function concluirStep(num) {
        const spinner = document.getElementById('loading-spinner-' + num);
        const check = document.getElementById('loading-check-' + num);
        if (spinner) { spinner.classList.add('hidden'); spinner.classList.remove('animate-spin'); }
        if (check) check.classList.remove('hidden');
    }

    function mostrarStep(num) {
        const step = document.getElementById('loading-step-' + num);
        if (step) step.classList.remove('hidden');
    }

    // Intercept form submit
    const contratoForm = document.getElementById('contrato-form');
    if (contratoForm) {
        contratoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;

            // Show loading modal immediately so user sees feedback
            mostrarModalLoading();

            // Yield to browser paint, then do sync + fetch
            requestAnimationFrame(() => {
            setTimeout(() => {
                try { sincronizarTudoNoEditor(); } catch (e) {}
                if (window.contratoEditor) {
                    document.getElementById('contrato_texto').value = window.contratoEditor.getData();
                }
                if (window.anexoEditor) {
                    document.getElementById('anexo_texto').value = window.anexoEditor.getData();
                }

                // Check if this is a casamento without proposta (needs AI anexo)
                const precisaAnexoIA = <?= json_encode(empty($contrato['proposta_id']) && ($tipoContrato === 'casamento')) ?>;

                const formData = new FormData(form);
                formData.set('ajax', '1');

                fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) throw new Error(data.erro || 'Erro ao salvar');

                    concluirStep(1);

                    if (precisaAnexoIA && !data.gerou_anexo) {
                        mostrarStep(2);
                        document.getElementById('loading-modal-title').textContent = 'Gerando Anexo I...';
                        document.getElementById('loading-modal-step').textContent = 'Inteligência artificial está redigindo a descrição dos serviços.';

                        return fetch('<?= raizUrl("/api/contratos/atualizar_anexo.php") ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ contrato_id: data.contrato_id })
                        })
                        .then(r => r.json())
                        .then(anexoData => {
                            concluirStep(2);
                            return data.redirect_url;
                        });
                    }

                    if (precisaAnexoIA) {
                        concluirStep(2);
                    }
                    return data.redirect_url;
                })
                .then(url => {
                    mostrarStep(3);
                    document.getElementById('loading-modal-title').textContent = 'Redirecionando...';
                    document.getElementById('loading-modal-step').textContent = 'Você será redirecionado para visualizar o contrato.';
                    concluirStep(3);
                    window.location.href = url;
                })
                .catch(err => {
                    console.error('Erro no save AJAX:', err);
                    document.getElementById('loading-modal').style.display = 'none';
                    const ajaxInput = document.createElement('input');
                    ajaxInput.type = 'hidden';
                    ajaxInput.name = 'ajax';
                    ajaxInput.value = '0';
                    contratoForm.appendChild(ajaxInput);
                    HTMLElement.prototype.submit.call(contratoForm);
                });
            }, 30);
        });
    });
    }

    // ============================================================
    // LIVE PREVIEW — lê direto do CKEditor (já sincronizado)
    // ============================================================

    function atualizarPreview() {
        const bodyEl = document.getElementById('live-preview-body');
        const anexoEl = document.getElementById('live-preview-anexo');
        if (!bodyEl) return;

        const contratoHtml = window.contratoEditor
            ? window.contratoEditor.getData()
            : (document.getElementById('contrato_texto')?.value || '');
        const anexoHtml = window.anexoEditor
            ? window.anexoEditor.getData()
            : (document.getElementById('anexo_texto')?.value || '');

        bodyEl.innerHTML = contratoHtml || '<p style="padding:20pt;text-align:center;color:#999;">Nenhum conteúdo de contrato definido ainda.</p>';
        anexoEl.innerHTML = anexoHtml || '<p style="padding:20pt;text-align:center;color:#999;">Nenhum anexo definido ainda.</p>';
    }

    window.togglePreview = function() {
        const previewPanel = document.getElementById('live-preview-panel');
        const editGrid = document.getElementById('contrato-edit-grid');
        const btnLabel = document.getElementById('btn-preview-label');
        if (!previewPanel) return;

        const isHidden = previewPanel.classList.contains('hidden');

        if (isHidden) {
            try { sincronizarTudoNoEditor(); } catch (e) {}
            atualizarPreview();
            previewPanel.classList.remove('hidden');
            editGrid.classList.add('hidden');
            btnLabel.textContent = 'Editar';
        } else {
            previewPanel.classList.add('hidden');
            editGrid.classList.remove('hidden');
            btnLabel.textContent = 'Pré-visualizar';
        }

        if (typeof lucide !== 'undefined') {
            setTimeout(() => lucide.createIcons(), 50);
        }
    };

    // Live preview: sync editor first, then read from CKEditor
    function atualizarPreviewIfVisible() {
        const panel = document.getElementById('live-preview-panel');
        if (panel && !panel.classList.contains('hidden')) {
            // Sync form fields into CKEditor first so preview shows latest
            try { sincronizarTudoNoEditor(); } catch (e) {}
            atualizarPreview();
        }
    }

    // CKEditor change events also update preview
    let previewSetupDone = false;
    function setupPreviewListeners() {
        if (previewSetupDone) return;

        if (window.contratoEditor) {
            window.contratoEditor.model.document.on('change:data', () => {
                const panel = document.getElementById('live-preview-panel');
                if (panel && !panel.classList.contains('hidden')) {
                    atualizarPreview();
                }
            });
        }
        if (window.anexoEditor) {
            window.anexoEditor.model.document.on('change:data', () => {
                const panel = document.getElementById('live-preview-panel');
                if (panel && !panel.classList.contains('hidden')) {
                    atualizarPreview();
                }
            });
        }

        // Form field changes — sync then update preview
        const form = document.getElementById('contrato-form');
        if (form) {
            form.addEventListener('input', atualizarPreviewIfVisible);
            form.addEventListener('change', atualizarPreviewIfVisible);
        }

        previewSetupDone = true;
    }

    // Set up CKEditor listeners once editors are initialized
    let previewListenersReady = false;
    const ckTimer = setInterval(() => {
        if (previewListenersReady) { clearInterval(ckTimer); return; }
        if (window.contratoEditor || window.anexoEditor) {
            setupPreviewListeners();
            previewListenersReady = true;
            clearInterval(ckTimer);
        }
    }, 500);

    // Mascaras de input
    const mascararCPF = (v) => {
        v = v.replace(/\D/g, '').slice(0, 11);
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        return v;
    };

    const mascararCNPJ = (v) => {
        v = v.replace(/\D/g, '').slice(0, 14);
        v = v.replace(/(\d{2})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1/$2');
        v = v.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
        return v;
    };

    const mascararCPFCNPJ = (v) => {
        const n = v.replace(/\D/g, '');
        if (n.length <= 11) return mascararCPF(v);
        return mascararCNPJ(v);
    };

    const mascararTelefone = (v) => {
        v = v.replace(/\D/g, '').slice(0, 11);
        v = v.replace(/(\d{2})(\d)/, '($1) $2');
        v = v.replace(/(\d{4,5})(\d{4})$/, '$1-$2');
        return v;
    };

    const mascararMoeda = (v) => {
        v = v.replace(/\D/g, '');
        if (!v) return '';
        v = (parseInt(v, 10) / 100).toFixed(2);
        const parts = v.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return parts.join(',');
    };

    document.querySelectorAll('input[name="sig1_cpf"], input[name="sig2_cpf"]').forEach(el => {
        el.addEventListener('input', e => e.target.value = mascararCPFCNPJ(e.target.value));
    });

    document.querySelectorAll('input[name="sig1_telefone"], input[name="sig2_telefone"], input[name="sig_distinto_telefone"]').forEach(el => {
        el.addEventListener('input', e => e.target.value = mascararTelefone(e.target.value));
    });

    const valorInput = document.querySelector('input[name="valor_total"]');
    if (valorInput) {
        valorInput.addEventListener('blur', e => {
            e.target.value = mascararMoeda(e.target.value);
        });
        valorInput.addEventListener('input', e => {
            const cursor = e.target.selectionStart;
            const val = e.target.value;
            if (val.includes(',')) {
                e.target.value = mascararMoeda(val);
                e.target.setSelectionRange(cursor, cursor);
            }
        });
    }

    // Sincronização automática entre o Vencimento do Sinal da Calculadora e da Cobrança Asaas
    const calcSinalData = document.getElementById('calc_sinal_data');
    const asaasSinalVenc = document.querySelector('input[name="asaas_sinal_vencimento"]');
    if (calcSinalData && asaasSinalVenc) {
        calcSinalData.addEventListener('change', e => {
            asaasSinalVenc.value = e.target.value;
        });
        asaasSinalVenc.addEventListener('change', e => {
            calcSinalData.value = e.target.value;
        });
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
    console.log('[calcularCondicoes] iniciou');
    let totalInput = document.querySelector('[name="valor_total"]').value;
    let total = parseFloat(totalInput.replace(/[^\d,]/g, '').replace(',', '.'));

    if (isNaN(total) || total <= 0) {
        alert('Por favor, preencha o Valor Total do contrato.');
        return;
    }

    let sinalDataStr = document.getElementById('calc_sinal_data').value;
    let meioPagamento = document.getElementById('calc_meio').value;

    if (!sinalDataStr) {
        alert('Por favor, preencha a data do Sinal ou da 1ª parcela.');
        return;
    }

    let sinalSplit = sinalDataStr.split('-');
    let sinalAno = parseInt(sinalSplit[0]);
    let sinalMes = parseInt(sinalSplit[1]) - 1;
    let sinalDia = parseInt(sinalSplit[2]);

    let d1 = new Date(sinalAno, sinalMes + 1, sinalDia);
    if (d1.getMonth() !== (sinalMes + 1) % 12) {
        d1.setDate(0);
    }
    let p1Ano = d1.getFullYear();
    let p1Mes = (d1.getMonth() + 1).toString().padStart(2, '0');
    let p1Dia = d1.getDate().toString().padStart(2, '0');
    let primeiraParcelaDataStr = `${p1Ano}-${p1Mes}-${p1Dia}`;

    let qtdParcelas = 1;
    let sinalVal = 0;
    let saldoVal = total;
    let primeiraParcelaTexto = primeiraParcelaDataStr;

    if (meioPagamento === 'Cartão de Crédito') {
        qtdParcelas = parseInt(document.getElementById('calc_parcelas').value) || 1;
        sinalVal = 0;
        saldoVal = total;
        primeiraParcelaTexto = sinalDataStr;
    } else {
        sinalVal = total * 0.20;
        saldoVal = total - sinalVal;

        let dataEvento = document.querySelector('[name="data_evento"]').value;
        if (!dataEvento) {
            alert('Por favor, preencha a Data do Evento/Início para calcular as parcelas até o casamento.');
            return;
        }

        let pacote = document.querySelector('[name="pacote_contrato"]')?.value || '';
        let limitePacote = pacote === 'heritage' ? 9 : 7;
        let parcelasInformadas = parseInt(document.querySelector('[name="asaas_total_parcelas"]')?.value || '0') || 0;

        if (parcelasInformadas > 0) {
            qtdParcelas = Math.min(limitePacote, parcelasInformadas);
        } else {
            let dataSinal = new Date(sinalDataStr + 'T00:00:00');
            let dataEventoDt = new Date(dataEvento + 'T00:00:00');
            let diffMeses = (dataEventoDt.getFullYear() - dataSinal.getFullYear()) * 12 + (dataEventoDt.getMonth() - dataSinal.getMonth());
            let parcelasPelaData = Math.max(1, diffMeses);
            qtdParcelas = Math.min(parcelasPelaData, limitePacote);
        }
        qtdParcelas = Math.max(1, qtdParcelas);
    }

    let parcelaVal = saldoVal / qtdParcelas;

    let totalParcelasInput = document.querySelector('[name="asaas_total_parcelas"]');
    let firstDueDateInput = document.querySelector('[name="asaas_first_due_date"]');
    let valorSinalInput = document.querySelector('[name="asaas_valor_sinal"]');
    let sinalVencimentoInput = document.querySelector('[name="asaas_sinal_vencimento"]');

    if (totalParcelasInput) totalParcelasInput.value = qtdParcelas;
    if (firstDueDateInput) firstDueDateInput.value = primeiraParcelaTexto;
    if (valorSinalInput) {
        valorSinalInput.value = sinalVal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    if (sinalVencimentoInput) sinalVencimentoInput.value = sinalDataStr;

    let dataSinalFormated = `${sinalSplit[2]}/${sinalSplit[1]}/${sinalSplit[0]}`;

    let texto = ``;

    if (meioPagamento === 'Cartão de Crédito') {
        texto += `Pagamento via Cartão de Crédito em ${qtdParcelas.toString().padStart(2, '0')} (${escreverCardinal(qtdParcelas)}) parcelas mensais e consecutivas no valor de ${formatarMoeda(parcelaVal)} (${escreverValorPorExtenso(parcelaVal)}) cada, com vencimento inicial em ${dataSinalFormated}.\n`;
        for (let i = 0; i < qtdParcelas; i++) {
            let venc = adicionarMeses(sinalDataStr, i);
            texto += `- ${i + 1}ª Parcela: ${venc} — ${formatarMoeda(parcelaVal)}\n`;
        }
    } else {
        if (sinalVal > 0) {
            texto += `Sinal/Entrada (20%): ${formatarMoeda(sinalVal)} (${escreverValorPorExtenso(sinalVal)}) com vencimento em ${dataSinalFormated}.\n\n`;
        }

        texto += `Saldo Restante: ${formatarMoeda(saldoVal)} (${escreverValorPorExtenso(saldoVal)}) dividido em ${qtdParcelas.toString().padStart(2, '0')} (${escreverCardinal(qtdParcelas)}) parcelas mensais e consecutivas no valor de ${formatarMoeda(parcelaVal)} (${escreverValorPorExtenso(parcelaVal)}) cada, via ${meioPagamento}, com vencimentos programados até a data do evento:\n`;

        for (let i = 0; i < qtdParcelas; i++) {
            let venc = adicionarMeses(primeiraParcelaDataStr, i);
            texto += `- ${i + 1}ª Parcela: ${venc} — ${formatarMoeda(parcelaVal)}\n`;
        }
    }

    document.getElementById('condicoes_pagamento').value = texto.trim();
    if (typeof window.sincronizarTudoNoEditor === 'function') {
        window.sincronizarTudoNoEditor();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
