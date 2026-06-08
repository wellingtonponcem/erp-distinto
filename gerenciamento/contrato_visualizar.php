<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirDistinto();
$db = Database::get();

$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: ' . raizUrl('/gerenciamento/contratos.php'));
    exit;
}

$stmt = $db->prepare("SELECT * FROM contratos WHERE id = ?");
$stmt->execute([$id]);
$contrato = $stmt->fetch();

if (!$contrato) {
    header('Location: ' . raizUrl('/gerenciamento/contratos.php?erro=Contrato não encontrado.'));
    exit;
}

$dadosJson = json_decode($contrato['dados_json'], true) ?: [];
$sig1 = $dadosJson['signatario_1'] ?? ['nome' => '', 'cpf' => '', 'email' => '', 'telefone' => '', 'endereco' => ''];
$sig2 = $dadosJson['signatario_2'] ?? ['nome' => '', 'cpf' => '', 'email' => '', 'telefone' => '', 'endereco' => ''];
$locais = $dadosJson['locais'] ?? ['tem_prewedding' => '', 'local_prewedding' => '', 'tem_cartorio' => '', 'local_cartorio' => '', 'tem_cerimonia' => '', 'local_cerimonia' => ''];
$contratoTexto = $dadosJson['contrato_texto'] ?? '';
$anexoTexto = $dadosJson['anexo_texto'] ?? '';

// For wedding contracts, regenerate full text from current data
$isCasamento = (stripos($contratoTexto, 'CASAMENTO') !== false);
if ($isCasamento && !empty($contratoTexto)) {
    $dataContratoPorExtenso = dataExtenso($contrato['data_contrato'] ?? date('Y-m-d'));
    $valorTotal = $contrato['valor_total'];
    $condicoesPagamento = $contrato['condicoes_pagamento'];
    $dataEvento = $dadosJson['data_evento'] ?? '';

    $clausula2Html = '<h4>CLAUSULA SEGUNDA - PRAZO E LOCAL DE EXECUCAO DOS SERVICOS</h4>';
    $clausula2Html .= '<p>2.1. Os servicos objeto deste contrato serao executados na data de <strong>' . ($dataEvento ? date('d/m/Y', strtotime($dataEvento)) : '[Data do Evento]') . '</strong>, conforme as especificacoes de local e horario a seguir:</p>';
    $clausula2Html .= '<ol style="margin-bottom: 12px;">';
    if (!empty($locais['tem_prewedding'])) {
        $localPw = !empty($locais['local_prewedding']) ? htmlspecialchars($locais['local_prewedding']) : 'a definir em comum acordo entre as partes';
        $clausula2Html .= '<li><strong>Ensaio Pre-Wedding:</strong> ' . $localPw . '.</li>';
    }
    if (!empty($locais['tem_cartorio'])) {
        $localCt = !empty($locais['local_cartorio']) ? htmlspecialchars($locais['local_cartorio']) : 'a definir em comum acordo entre as partes';
        $clausula2Html .= '<li><strong>Cartorio Civil:</strong> ' . $localCt . '.</li>';
    }
    if (!empty($locais['tem_cerimonia'])) {
        $localCe = !empty($locais['local_cerimonia']) ? htmlspecialchars($locais['local_cerimonia']) : 'a definir em comum acordo entre as partes';
        $clausula2Html .= '<li><strong>Cerimonia e Festa:</strong> ' . $localCe . '.</li>';
    }
    if (empty($locais['tem_prewedding']) && empty($locais['tem_cartorio']) && empty($locais['tem_cerimonia'])) {
        $clausula2Html .= '<li>Local a definir em comum acordo entre as partes.</li>';
    }
    $clausula2Html .= '</ol>';
    $clausula2Html .= '<p>2.2. A duracao padrao da cobertura sera aquela descrita e especificada no Anexo I, podendo ser ajustada mediante comum acordo entre as partes.<br>';
    $clausula2Html .= '2.3. A CONTRATADA nao se responsabiliza por atrasos ou impossibilidade de execucao dos servicos decorrentes de condicoes climaticas adversas, falhas de energia eletrica no local do evento ou quaisquer outros fatores alheios a sua vontade, comprometendo-se, nestes casos, a remarcar a data mediante comum acordo com os CONTRATANTES.</p>';

    $contratoTexto = '
    <h3 style="text-align: center;">CONTRATO DE PRESTACAO DE SERVICOS - CASAMENTO</h3>
    <p style="text-align: center;"><strong>N&ordm; ' . date('Y') . '/' . substr($contrato['id'], 0, 4) . '</strong></p>

    <p>Pelo presente instrumento particular, de um lado:</p>

    <p><strong>CONTRATANTES:</strong><br>
    <strong>' . ($sig1['nome'] ?: '[Nome da Noiva]') . '</strong>, portadora do CPF n&ordm; ' . ($sig1['cpf'] ?: '[CPF da Noiva]') . ', e <strong>' . ($sig2['nome'] ?: '[Nome do Noivo]') . '</strong>, portador do CPF n&ordm; ' . ($sig2['cpf'] ?: '[CPF do Noivo]') . ', doravante denominados simplesmente <strong>CONTRATANTES</strong>.</p>

    <p><strong>CONTRATADA:</strong><br>
    <strong>Distinto | Poncem Studio (Poncem Studio LTDA)</strong>, CNPJ 50.168.732/0001-63, com sede na Rod. do Sol n&ordm; 2780, sala 1307, Praia de Itaparica, Vila Velha-ES, CEP 29102-020, e-mail contato@wedistinto.com, doravante denominada <strong>CONTRATADA</strong>.</p>

    <p>Firmam o presente contrato de prestacao de servicos, mediante clausulas e condicoes a seguir:</p>

    <h4>CLAUSULA PRIMEIRA - DO OBJETO</h4>
    <p>1.1. A <strong>CONTRATADA</strong> prestara servicos profissionais de cobertura fotografica e/ou producao audiovisual para o casamento dos <strong>CONTRATANTES</strong>, em conformidade com o detalhamento contido no Anexo I, que integra este instrumento.</p>

    ' . $clausula2Html . '

    <h4>CLAUSULA TERCEIRA - VALOR E CONDICOES DE PAGAMENTO</h4>
    <p>3.1. Pela prestacao dos servicos contratados, os <strong>CONTRATANTES</strong> pagarao a <strong>CONTRATADA</strong> a quantia total de <strong>R$ ' . number_format($valorTotal, 2, ',', '.') . '</strong>, nas seguintes condicoes: ' . htmlspecialchars($condicoesPagamento) . '.</p>
    <p>3.2. O pagamento sera efetuado conforme cronograma acordado entre as partes, podendo ser dividido em parcelas mensais, conforme discriminado na proposta comercial aceita pelos CONTRATANTES.</p>
    <p>3.3. Em caso de atraso no pagamento de qualquer parcela, incidira multa de 2% (dois por cento) sobre o valor da parcela em atraso, bem como juros de mora de 1% (um por cento) ao mes e correcao monetaria pelo IPCA.</p>

    <h4>CLAUSULA QUARTA - DAS ENTREGAS</h4>
    <p>4.1. A <strong>CONTRATADA</strong> entregara aos <strong>CONTRATANTES</strong> o material fotografico e/ou audiovisual devidamente editado, conforme especificacoes tecnicas e prazos estabelecidos no Anexo I, parte integrante deste instrumento.</p>
    <p>4.2. O prazo de entrega do material final sera contado a partir da data de realizacao do evento, salvo disposicao em contrario prevista no Anexo I.</p>
    <p>4.3. A <strong>CONTRATADA</strong> nao se responsabiliza pela perda do material decorrente de caso fortuito ou forca maior, obrigando-se, entretanto, a manter backup de seguranca de todos os arquivos pelo prazo minimo de 90 (noventa) dias apos a entrega.</p>

    <h4>CLAUSULA QUINTA - DA AUTORIZACAO DE IMAGEM</h4>
    <p>5.1. Os <strong>CONTRATANTES</strong> autorizam de forma expressa, irrevogavel e gratuita a utilizacao de suas imagens capturadas durante os eventos e ensaios, para fins de divulgacao de portfolio profissional da <strong>CONTRATADA</strong> em suas midias digitais, redes sociais, site institucional e materiais promocionais, pelo periodo de 2 (dois) anos contados da data de realizacao do evento.</p>
    <p>5.2. A autorizacao prevista no item 5.1 abrange a reproducao, exibicao, publicacao e divulgacao das imagens em qualquer midia ou formato, desde que sem finalidade lucrativa direta e respeitando o decoro e a boa imagem dos CONTRATANTES.</p>
    <p>5.3. Caso os <strong>CONTRATANTES</strong> desejem restringir a divulgacao de imagens especificas, deverao comunicar a <strong>CONTRATADA</strong> por escrito em ate 15 (quinze) dias apos a data do evento.</p>

    <h4>CLAUSULA SEXTA - DAS OBRIGACOES DA CONTRATADA</h4>
    <p>6.1. Prestar os servicos contratados com zelo profissional, utilizando equipamentos adequados e profissionais qualificados de sua inteira confianca.<br>
    6.2. Comparecer ao local do evento com antecedencia minima necessaria para preparacao e montagem dos equipamentos.<br>
    6.3. Disponibilizar aos CONTRATANTES os contatos telefonicos e de WhatsApp da equipe escalada para o dia do evento.<br>
    6.4. Manter sigilo absoluto sobre as informacoes pessoais e dados compartilhados pelos CONTRATANTES no âmbito da prestacao dos servicos.</p>

    <h4>CLAUSULA SETIMA - DAS OBRIGACOES DOS CONTRATANTES</h4>
    <p>7.1. Fornecer alimentacao adequada para a equipe de captacao caso o tempo total do evento exceda 4 (quatro) horas.<br>
    7.2. Garantir o livre transito dos fotografos e cinegrafistas no local do evento.<br>
    7.3. Efetuar os pagamentos rigorosamente em dia, conforme cronograma acordado.<br>
    7.4. Disponibilizar os convites e credenciais necessarios para acesso da equipe aos locais dos eventos.<br>
    7.5. Informar a <strong>CONTRATADA</strong> com antecedencia minima de 48 (quarenta e oito) horas sobre qualquer alteracao de horario ou local dos eventos.</p>

    <h4>CLAUSULA OITAVA - DA CESSAO</h4>
    <p>8.1. A <strong>CONTRATADA</strong> podera ceder ou subcontratar total ou parcialmente os servicos objeto deste contrato a terceiros de sua confianca, mantendo-se como unica responsavel perante os CONTRATANTES pela fiel execucao do objeto contratado.</p>
    <p>8.2. Os <strong>CONTRATANTES</strong> nao poderao ceder ou transferir a terceiros os direitos e obrigacoes decorrentes deste contrato sem a previa e expressa autorizacao por escrito da CONTRATADA.</p>

    <h4>CLAUSULA NONA - DA RESCISAO CONTRATUAL E MULTAS</h4>
    <p>9.1. Em caso de cancelamento unilateral imotivado por parte dos <strong>CONTRATANTES</strong> com menos de 30 (trinta) dias da data do evento, nenhum valor pago a titulo de sinal ou reserva sera reembolsado, configurando-se como clausula penal de natureza compensatoria.</p>
    <p>9.2. Em caso de cancelamento com antecedencia superior a 30 (trinta) dias, os valores ja pagos serao devolvidos deduzindo-se o percentual de 20% (vinte por cento) a titulo de multa compensatoria pela reserva de data e custos administrativos ja incorridos.</p>
    <p>9.3. Em descumprimento de quaisquer outras clausulas deste contrato, incidira multa penal de 10% (dez por cento) sobre o valor remanescente do instrumento, sem prejuizo de perdas e danos.</p>
    <p>9.4. A <strong>CONTRATADA</strong> podera rescindir o contrato de pleno direito caso os <strong>CONTRATANTES</strong> descumpram com as obrigacoes pecuniarias aqui assumidas, ficando autorizada a reter os valores eventualmente ja recebidos a titulo de indenizacao minima.</p>

    <h4>CLAUSULA DECIMA - DISPOSICOES GERAIS</h4>
    <p>10.1. O presente instrumento nao gera vinculo de natureza empregaticia entre as partes contratantes, nem solidariedade trabalhista ou previdenciaria.</p>
    <p>10.2. As partes elegem o Anexo I como parte integrante e indissociavel deste contrato para todos os fins de direito.</p>
    <p>10.3. Qualquer alteracao neste instrumento devera ser feita por escrito, mediante aditivo contratual assinado por ambas as partes.</p>
    <p>10.4. A tolerancia ao descumprimento de qualquer clausula ou condicao deste contrato nao constituira novacao ou precedente, nem afetara o exercicio posterior do direito pela parte inocente.</p>
    <p>10.5. As partes se comprometem a buscar uma solucao amigavel, por meio de negociacao direta, antes de recorrer a qualquer via judicial para resolucao de eventuais controversias.</p>

    <h4>CLAUSULA DECIMA PRIMEIRA - DO FORO</h4>
    <p>11.1. Fica eleito o foro da Comarca de Vitoria/ES para dirimir quaisquer duvidas ou controversias decorrentes do presente contrato, com expressa renuncia a qualquer outro, por mais privilegiado que seja.</p>

    <p>Vitoria/ES, ' . $dataContratoPorExtenso . '.</p>
    ';
}

$tituloPagina = 'Visualizar Contrato';
require_once __DIR__ . '/../includes/layout/head.php';
?>
<div id="app-wrapper" x-data="contratoVisualizarApp()">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>
    <main id="main-content" class="content-sheet flex flex-col min-h-screen !bg-[#0c0c0c] !text-white relative">
        
        <!-- Loading Overlay -->
        <div x-show="loading" class="fixed inset-0 bg-black/80 backdrop-blur-md z-[9999] flex flex-col items-center justify-center gap-4" x-cloak>
            <div class="w-12 h-12 border-4 border-white/10 border-t-white rounded-full animate-spin"></div>
            <p class="text-sm font-bold text-white uppercase tracking-widest" x-text="loadingMessage"></p>
        </div>

        <!-- Header -->
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4 border-b border-white/5 pb-6">
            <div>
                <div class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-2">Comercial / Contrato</div>
                <h1 class="text-3xl font-black tracking-tight text-white flex items-center gap-3">
                    <i data-lucide="eye" class="w-8 h-8 text-zinc-400"></i>
                    Pré-visualização do Contrato
                </h1>
                <p class="text-sm font-medium text-zinc-400 mt-1">Veja como o contrato ficará impresso ou enviado para assinatura digital.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="<?= raizUrl('/gerenciamento/contratos.php') ?>" class="px-5 py-2.5 bg-zinc-900 border border-white/5 hover:bg-zinc-800 text-zinc-300 rounded-xl text-xs font-bold uppercase tracking-widest transition-all">
                    Voltar
                </a>
                
                <?php if (($contrato['status'] ?? 'rascunho') === 'rascunho'): ?>
                    <a href="<?= raizUrl('/gerenciamento/contrato_gerar.php?id=' . $id) ?>" class="px-5 py-2.5 bg-zinc-900 border border-white/5 hover:bg-zinc-800 text-zinc-300 rounded-xl text-xs font-bold uppercase tracking-widest transition-all flex items-center gap-1.5">
                        <i data-lucide="edit-3" class="w-4 h-4"></i> Editar
                    </a>
                    
                    <button @click="enviarParaAssinatura()" class="px-6 py-2.5 bg-white text-black hover:bg-zinc-200 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center gap-1.5 shadow-xl">
                        <i data-lucide="signature" class="w-4 h-4"></i> Enviar Assinatura
                    </button>
                <?php endif; ?>

                <button @click="exportarPDFLocal()" class="px-5 py-2.5 bg-zinc-850 hover:bg-zinc-700 border border-white/10 text-zinc-200 rounded-xl text-xs font-bold uppercase tracking-widest transition-all flex items-center gap-1.5">
                    <i data-lucide="download" class="w-4 h-4"></i> PDF
                </button>
            </div>
        </div>

        <!-- Status Box if already sent/signed -->
        <?php if (($contrato['status'] ?? 'rascunho') !== 'rascunho'): ?>
            <div class="mb-8 p-6 rounded-[2rem] bg-zinc-900/50 border border-white/5 flex flex-wrap items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-zinc-800 flex items-center justify-center text-zinc-400">
                        <i data-lucide="shield-check" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-white">Contrato em Processamento de Assinatura</h4>
                        <p class="text-xs text-zinc-400 mt-1">Este contrato foi enviado eletronicamente e não aceita mais edições diretas.</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    <?php if ($contrato['link_assinatura']): ?>
                        <a href="<?= sanitizar($contrato['link_assinatura']) ?>" target="_blank" class="px-5 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-all flex items-center gap-1.5">
                            <i data-lucide="external-link" class="w-4 h-4"></i> Acompanhar no Assinafy
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- A4 Paper Preview Container -->
        <div class="overflow-x-auto py-10 flex justify-center bg-[#111] border border-white/5 rounded-[32px] mb-12 shadow-inner">
            <!-- PDF Container Content -->
            <div id="pdf-content" class="a4-page-content">
                <!-- Header Logo -->
                <div class="pdf-logo-wrapper">
                    <img src="<?= raizUrl('/assets/logo-contrato.png') ?>" alt="Poncem Studio Logo" class="pdf-logo">
                </div>

                <!-- Contract Body Text -->
                <div class="pdf-body text-justify">
                    <?= $contratoTexto ?>
                </div>

                <!-- Page Break For Anexo I -->
                <?php if (!empty($anexoTexto)): ?>
                    <div class="page-break"></div>
                    <div class="pdf-logo-wrapper pt-10">
                        <img src="<?= raizUrl('/assets/logo-contrato.png') ?>" alt="Poncem Studio Logo" class="pdf-logo">
                    </div>
                    <div class="pdf-body text-justify">
                        <?= $anexoTexto ?>
                    </div>
                <?php endif; ?>
                
                <!-- Signatures Space -->
                <div class="pdf-signatures-wrapper">
                    <table style="width: 100%; border: 0; margin-top: 50px;">
                        <tr>
                            <td style="width: 46%; text-align: center; border: 0; padding: 0;">
                                <div style="border-top: 1px solid #111; padding-top: 8px; font-size: 10px; color: #333; font-family: 'Arial', sans-serif;">
                                    <strong>CONTRATANTE(S)</strong>
                                </div>
                            </td>
                            <td style="width: 8%; border: 0;"></td>
                            <td style="width: 46%; text-align: center; border: 0; padding: 0;">
                                <div style="border-top: 1px solid #111; padding-top: 8px; font-size: 10px; color: #333; font-family: 'Arial', sans-serif;">
                                    <strong>CONTRATADA</strong><br>
                                    Poncem Studio LTDA
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Styles specifically for A4 preview and print generation -->
<style>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;700;900&display=swap');

.a4-page-content {
    background: #ffffff;
    color: #1a1a1a;
    width: 210mm;
    min-height: 297mm;
    padding: 25mm 20mm 20mm 20mm;
    box-shadow: 0 20px 50px rgba(0,0,0,0.8);
    box-sizing: border-box;
    font-family: 'Arial', sans-serif;
    font-size: 11pt;
    line-height: 1.5;
}

.pdf-logo-wrapper {
    margin-bottom: 25px;
    text-align: left;
}

.pdf-logo {
    width: 220px;
    height: auto;
    display: block;
}

.pdf-body h3 {
    font-family: 'Sora', sans-serif;
    font-size: 14pt;
    font-weight: 900;
    text-transform: uppercase;
    color: #231f20;
    margin-top: 25px;
    margin-bottom: 15px;
    letter-spacing: -0.02em;
}

.pdf-body h4 {
    font-family: 'Sora', sans-serif;
    font-size: 11pt;
    font-weight: 700;
    text-transform: uppercase;
    color: #231f20;
    margin-top: 20px;
    margin-bottom: 8px;
}

.pdf-body p {
    margin-bottom: 12px;
    font-family: 'Sora', 'Arial', sans-serif;
    font-size: 10pt;
    color: #231f20;
}

.pdf-body strong {
    font-weight: 700;
}

.pdf-body ul, .pdf-body ol {
    margin-left: 20px;
    margin-bottom: 15px;
    font-family: 'Sora', 'Arial', sans-serif;
    font-size: 10pt;
    color: #231f20;
}

.pdf-body li {
    margin-bottom: 6px;
}

.page-break {
    page-break-before: always;
    height: 1px;
}

.pdf-signatures-wrapper {
    margin-top: 60px;
}

/* Print configuration to bypass html2pdf margins issue */
@media print {
    body {
        background: white;
    }
    .a4-page-content {
        box-shadow: none;
        padding: 0;
        margin: 0;
    }
}
</style>

<!-- html2pdf.js library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
function contratoVisualizarApp() {
    return {
        id: <?= json_encode($id) ?>,
        loading: false,
        loadingMessage: '',
        
        exportarPDFLocal() {
            this.loading = true;
            this.loadingMessage = 'Gerando arquivo PDF...';
            
            const element = document.getElementById('pdf-content');
            const opt = {
                margin: [10, 10, 10, 10], // standard padding for printable
                filename: 'Contrato_' + this.id + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            html2pdf().set(opt).from(element).save()
            .then(() => {
                this.loading = false;
            })
            .catch(err => {
                console.error(err);
                alert('Erro ao exportar PDF.');
                this.loading = false;
            });
        },
        
        enviarParaAssinatura() {
            if (!confirm('Deseja gerar o PDF final e enviar este contrato para assinatura eletrônica via Assinafy? Esta ação não poderá ser desfeita.')) {
                return;
            }
            
            this.loading = true;
            this.loadingMessage = 'Gerando PDF de alta definição...';
            
            const element = document.getElementById('pdf-content');
            const opt = {
                margin: [10, 10, 10, 10],
                filename: 'Contrato_' + this.id + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            // Generate PDF Blob
            html2pdf().set(opt).from(element).outputPdf('blob')
            .then(blob => {
                this.loadingMessage = 'Enviando documento para o Assinafy...';
                
                const formData = new FormData();
                formData.append('pdf', blob, 'Contrato_' + this.id + '.pdf');
                formData.append('id', this.id);
                
                return fetch('<?= raizUrl("/api/contratos/enviar_assinatura.php") ?>', {
                    method: 'POST',
                    body: formData
                });
            })
            .then(res => res.json())
            .then(data => {
                this.loading = false;
                if (data.success) {
                    alert('Contrato enviado com sucesso para assinatura eletrônica!');
                    window.location.reload();
                } else {
                    alert('Erro ao enviar assinatura: ' + (data.erro || 'Erro interno. Verifique as credenciais do Assinafy nas configurações.'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Erro ao enviar documento para o servidor.');
                this.loading = false;
            });
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
