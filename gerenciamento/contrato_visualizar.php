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

// Dynamically render the header with current signatario data
if (!empty($contratoTexto)) {
    $headerHtml = '<p>Pelo presente instrumento particular, de um lado:</p>';
    $headerHtml .= '<p><strong>CONTRATANTES:</strong><br>';
    $headerHtml .= '<strong>' . ($sig1['nome'] ?: '[Nome da Noiva]') . '</strong>, portadora do CPF n&ordm; ' . ($sig1['cpf'] ?: '[CPF da Noiva]') . ', e <strong>' . ($sig2['nome'] ?: '[Nome do Noivo]') . '</strong>, portador do CPF n&ordm; ' . ($sig2['cpf'] ?: '[CPF do Noivo]') . ', doravante denominados simplesmente <strong>CONTRATANTES</strong>.</p>';
    $headerHtml .= '<p><strong>CONTRATADA:</strong><br>';
    $headerHtml .= '<strong>Distinto | Poncem Studio (Poncem Studio LTDA)</strong>, CNPJ 50.168.732/0001-63, com sede na Rod. do Sol n&ordm; 2780, sala 1307, Praia de Itaparica, Vila Velha-ES, CEP 29102-020, e-mail contato@wedistinto.com, doravante denominada <strong>CONTRATADA</strong>.</p>';
    $headerHtml .= '<p>Firmam o presente contrato de prestacao de servicos, mediante clausulas e condicoes a seguir:</p>';

    // Find the header boundary: replace everything between "<h3" (title) and first "<h4" (clausula primeira)
    $posAbertura = strpos($contratoTexto, '<h3');
    $posPrimeiraClausula = strpos($contratoTexto, '<h4');

    if ($posAbertura !== false && $posPrimeiraClausula !== false && $posPrimeiraClausula > $posAbertura) {
        $titulo = substr($contratoTexto, $posAbertura, $posPrimeiraClausula - $posAbertura);
        $fimTitulo = strpos($titulo, '</h3>');
        if ($fimTitulo !== false) {
            $tituloHtml = substr($titulo, 0, $fimTitulo + 6);
            $resto = substr($contratoTexto, $posPrimeiraClausula);
            $contratoTexto = substr($contratoTexto, 0, $posAbertura) . $tituloHtml . "\n\n" . $headerHtml . "\n\n" . $resto;
        }
    }

    // Dynamically render Clause 2 with current locais data
    $clausula2Html = '<h4>CLAUSULA SEGUNDA - PRAZO E LOCAL DE EXECUCAO DOS SERVICOS</h4>';
    $clausula2Html .= '<p>2.1. Os servicos objeto deste contrato serao executados na data de <strong>' . ($dadosJson['data_evento'] ? date('d/m/Y', strtotime($dadosJson['data_evento'])) : '[Data do Evento]') . '</strong>, conforme as especificacoes de local e horario a seguir:</p>';
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

    // Replace stored Clause 2 with dynamic version (support both accented and non-accented)
    $posClausula2 = strpos($contratoTexto, 'CLAUSULA SEGUNDA');
    if ($posClausula2 === false) {
        $posClausula2 = strpos($contratoTexto, 'CLÁUSULA SEGUNDA');
    }
    $posClausula3 = strpos($contratoTexto, 'CLAUSULA TERCEIRA');
    if ($posClausula3 === false) {
        $posClausula3 = strpos($contratoTexto, 'CLÁUSULA TERCEIRA');
    }
    if ($posClausula2 !== false && $posClausula3 !== false && $posClausula3 > $posClausula2) {
        $beforeCl2 = substr($contratoTexto, 0, $posClausula2);
        $afterCl3 = substr($contratoTexto, $posClausula3);
        $contratoTexto = $beforeCl2 . $clausula2Html . "\n\n" . $afterCl3;
    }
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
