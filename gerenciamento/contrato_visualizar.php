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
$locais = $dadosJson['locais'] ?? ['tem_prewedding' => '', 'local_prewedding' => '', 'data_prewedding' => '', 'tem_cartorio' => '', 'local_cartorio' => '', 'tem_cerimonia' => '', 'local_cerimonia' => '', 'data_cerimonia' => ''];
$contratoTexto = $dadosJson['contrato_texto'] ?? '';
$anexoTexto = $dadosJson['anexo_texto'] ?? '';
// For wedding contracts, we display the saved text directly to respect user edits.

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

                <button @click="atualizarAnexo()" :disabled="loadingAnexo" class="px-5 py-2.5 bg-zinc-800 hover:bg-zinc-700 border border-white/10 text-zinc-200 rounded-xl text-xs font-bold uppercase tracking-widest transition-all flex items-center gap-1.5 disabled:opacity-50">
                    <template x-if="!loadingAnexo">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                    </template>
                    <template x-if="loadingAnexo">
                        <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg>
                    </template>
                    <span x-text="loadingAnexo ? 'Gerando...' : 'Anexo IA'">Anexo IA</span>
                </button>

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

                <!-- Page Break For Anexo I -->
                <div class="pdf-logo-wrapper pt-10">
                    <img src="<?= raizUrl('/assets/logo-contrato.png') ?>" alt="Poncem Studio Logo" class="pdf-logo">
                </div>
                <div class="pdf-body text-justify">
                    <?= !empty($anexoTexto) ? $anexoTexto : '<h4>ANEXO I – DESCRIÇÃO DOS SERVIÇOS</h4><p class="p0">A descrição detalhada dos serviços será incluída após a definição do escopo do evento.</p>' ?>
            </div>
        </div>

    </main>
</div>

<!-- Styles specifically for A4 preview and print generation -->
<style>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;700&display=swap');

.a4-page-content {
    background: #ffffff;
    color: #231f20;
    width: 210mm;
    min-height: 297mm;
    padding: 10pt 51.5pt 15pt 92.3pt;
    box-shadow: 0 20px 50px rgba(0,0,0,0.8);
    box-sizing: border-box;
    font-family: 'Sora', 'Arial', sans-serif;
    font-size: 10pt;
    line-height: 1.15;
}

.pdf-logo-wrapper {
    margin-bottom: 30pt;
    text-align: left;
}

.pdf-logo {
    width: 196px;
    height: auto;
    display: block;
}

.pdf-body {
    font-family: 'Sora', 'Arial', sans-serif;
    font-size: 10pt;
    color: #231f20;
    line-height: 1;
}

.pdf-body h3 {
    font-family: 'Sora', sans-serif;
    font-size: 15pt;
    font-weight: 700;
    text-transform: uppercase;
    color: #231f20;
    margin: 0;
    padding: 0;
    line-height: 1;
    text-align: center;
}

.pdf-body .pdf-subtitle {
    font-family: 'Sora', sans-serif;
    font-size: 10pt;
    font-weight: 400;
    color: #231f20;
    margin: 0;
    padding-top: 33.1pt;
    line-height: 1;
    text-align: left;
}

.pdf-body .pdf-numero {
    font-family: 'Sora', sans-serif;
    font-size: 10pt;
    font-weight: 400;
    color: #231f20;
    margin: 0;
    padding-top: 0.3pt;
    line-height: 1;
    text-align: left;
}

.pdf-body h4 {
    font-family: 'Sora', sans-serif;
    font-size: 10pt;
    font-weight: 700;
    text-transform: uppercase;
    color: #231f20;
    margin: 0;
    padding: 12.3pt 0 0 0;
    line-height: 1;
    text-align: left;
}

.pdf-body p {
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

.pdf-body .p0 {
    margin-left: 0.3pt;
}

.pdf-body .p-closing {
    margin-left: 0.1pt;
}

.pdf-body .p2 {
    margin-left: 57pt;
}

.pdf-body strong {
    font-weight: 700;
    color: #231f20;
}

.pdf-body ul, .pdf-body ol {
    margin: 0;
    padding-left: 28.7pt;
    font-family: 'Sora', 'Arial', sans-serif;
    font-size: 10pt;
    color: #231f20;
    line-height: 1;
}

.pdf-body li {
    margin: 0;
    padding-top: 12.3pt;
    line-height: 1;
}

.page-break {
    page-break-before: always;
    break-before: page;
    border-top: 1px dashed #aaa;
    margin: 20pt 28.7pt;
    height: 0;
}

.pdf-body h3, .pdf-body h4, .pdf-body p, .pdf-body li, .pdf-signatures-wrapper, table, table tr {
    page-break-inside: avoid;
    break-inside: avoid;
}

.pdf-signatures-wrapper {
    margin-top: 60px;
}

@media print {
    body {
        background: white;
    }
    .a4-page-content {
        box-shadow: none;
        padding: 0;
        margin: 0;
    }
    .page-break {
        border-top: none;
        margin: 0;
        height: 0;
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
        loadingAnexo: false,
        loadingMessage: '',
        
        exportarPDFLocal() {
            this.loading = true;
            this.loadingMessage = 'Gerando arquivo PDF...';
            
            const element = document.getElementById('pdf-content');
            const opt = {
                margin: [15, 0, 18, 0],
                filename: 'Contrato_' + this.id + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, logging: false },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { 
                    mode: ['css', 'legacy'], 
                    avoid: ['p', 'h3', 'h4', 'li', 'tr', '.pdf-signatures-wrapper', 'table'] 
                }
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
        
        atualizarAnexo() {
            this.loadingAnexo = true;

            fetch('<?= raizUrl("/api/contratos/atualizar_anexo.php") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ contrato_id: this.id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.erro || 'Falha ao gerar anexo.');
                    this.loadingAnexo = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Erro de conexão ao gerar anexo.');
                this.loadingAnexo = false;
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
                margin: [15, 0, 18, 0],
                filename: 'Contrato_' + this.id + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, logging: false },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { 
                    mode: ['css', 'legacy'], 
                    avoid: ['p', 'h3', 'h4', 'li', 'tr', '.pdf-signatures-wrapper', 'table'] 
                }
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
