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

$lancamentosAsaas = [];
if ((int)($contrato['asaas_cobranca_gerada'] ?? 0) === 1) {
    $stmtL = $db->prepare("SELECT * FROM lancamentos WHERE cliente_id = ? AND (descricao LIKE ? OR observacao LIKE ?) ORDER BY vencimento ASC");
    $stmtL->execute([$contrato['cliente_id'], '%' . $contrato['titulo'] . '%', '%Contrato: ' . $contrato['id'] . '%']);
    $lancamentosAsaas = $stmtL->fetchAll();
}

$config = $db->query("SELECT assinafy_api_key, assinafy_account_id, assinafy_mode FROM configuracao_empresa WHERE id = 'principal' LIMIT 1")->fetch();

$dadosJson = json_decode($contrato['dados_json'], true) ?: [];
$sig1 = $dadosJson['signatario_1'] ?? ['nome' => '', 'cpf' => '', 'email' => '', 'telefone' => '', 'endereco' => ''];
$sig2 = $dadosJson['signatario_2'] ?? ['nome' => '', 'cpf' => '', 'email' => '', 'telefone' => '', 'endereco' => ''];
$contasBancarias = [];
try {
    $contasBancarias = $db->query("SELECT id, nome FROM contas_bancarias WHERE ativo = 1 ORDER BY nome ASC")->fetchAll();
} catch (Throwable $e) {
    $contasBancarias = [];
}
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
$planoContrato = detectarPlanoCasamento($dadosJson);
$percentualEntrada = $planoContrato === 'heritage' ? 25 : 20;
$valorEntradaPadrao = (float)($dadosJson['asaas_valor_sinal'] ?? 0);
if ($valorEntradaPadrao <= 0 && (float)$contrato['valor_total'] > 0) {
    $valorEntradaPadrao = round((float)$contrato['valor_total'] * ($percentualEntrada / 100), 2);
}
$sinalVencimentoPadrao = $dadosJson['asaas_sinal_vencimento'] ?? date('Y-m-d');
$primeiraParcelaPadrao = $dadosJson['asaas_first_due_date'] ?? adicionarMesesData($sinalVencimentoPadrao, 1) ?? date('Y-m-d', strtotime('+30 days'));
$parcelasPadrao = max(1, (int)($dadosJson['asaas_total_parcelas'] ?? 1));
$billingTypePadrao = $dadosJson['asaas_billing_type'] ?? 'UNDEFINED';
$entradaStatusPadrao = $dadosJson['entrada_status'] ?? 'pendente';
$temLancamentosFinanceiros = !empty($lancamentosAsaas);
$podeGerarFinanceiro = (($contrato['status'] ?? 'rascunho') === 'assinado')
    && ((int)($contrato['asaas_cobranca_gerada'] ?? 0) === 0 || !$temLancamentosFinanceiros);
// For wedding contracts, we display the saved text directly to respect user edits.

$tituloPagina = 'Visualizar Contrato';
require_once __DIR__ . '/../includes/layout/head.php';
?>
<div id="app-wrapper" x-data="contratoVisualizarApp">
    <!-- Modal de Confirmação de Assinatura -->
    <div id="modal-confirm-assinatura"
         class="fixed inset-0 bg-black/80 backdrop-blur-md z-[9999] hidden items-center justify-center p-4"
         style="display: none; z-index: 9999;"
         onclick="if (event.target === this) fecharModalAssinatura()">
        <div class="bg-zinc-950 border border-white/10 rounded-[2rem] p-8 w-full max-w-md shadow-2xl relative text-center">
            <div class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500 mx-auto mb-6">
                <i data-lucide="alert-triangle" class="w-7 h-7"></i>
            </div>
            
            <h3 class="text-xl font-black text-white mb-3">Enviar para Assinatura?</h3>
            <p class="text-sm text-zinc-400 leading-relaxed mb-8">
                Deseja gerar o PDF final e enviar este contrato para assinatura eletrônica via Assinafy? Esta ação não poderá ser desfeita e bloqueará edições futuras.
            </p>
            
            <div class="flex gap-3">
                <button type="button" onclick="fecharModalAssinatura()"
                        class="flex-1 py-3.5 bg-zinc-900 hover:bg-zinc-850 text-zinc-400 hover:text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-all cursor-pointer">
                    Cancelar
                </button>
                <button type="button" onclick="confirmarEnvioAssinatura()"
                        class="flex-1 py-3.5 bg-white hover:bg-zinc-200 text-black rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-lg shadow-white/5">
                    Confirmar Envio
                </button>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>
    <main id="main-content" class="content-sheet flex flex-col min-h-screen !bg-[#0c0c0c] !text-white relative">
        
        <!-- Loading Overlay -->
        <div x-show="loading" class="fixed inset-0 bg-black/80 backdrop-blur-md flex flex-col items-center justify-center gap-4" style="z-index: 9999;" x-cloak>
            <div class="w-12 h-12 border-4 border-white/10 border-t-white rounded-full animate-spin"></div>
            <p class="text-sm font-bold text-white uppercase tracking-widest" x-text="loadingMessage"></p>
        </div>

        <div x-show="modalContratoAberto"
             x-transition.opacity
             class="fixed inset-0 bg-black/85 backdrop-blur-md p-6 flex items-center justify-center"
             style="z-index: 9998;"
             x-cloak
             @keydown.escape.window="modalContratoAberto = false"
             @click.self="modalContratoAberto = false">
            <div class="w-[80vw] max-w-[1280px] h-[90vh] bg-[#0f0f10] border border-white/10 rounded-[28px] shadow-2xl overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-black text-white">Visualizacao completa do contrato</h2>
                        <p class="text-xs text-zinc-500 mt-1">Use esta janela para conferir o documento completo.</p>
                    </div>
                    <button type="button"
                            @click="modalContratoAberto = false"
                            class="w-10 h-10 rounded-xl bg-zinc-900 hover:bg-zinc-800 border border-white/10 text-zinc-300 flex items-center justify-center transition-all">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="flex-1 min-h-0 overflow-y-auto py-10 px-6">
                    <div class="a4-page-content contrato-modal-paper mx-auto">
                        <div class="pdf-logo-wrapper">
                            <img src="<?= raizUrl('/assets/logo-contrato.png') ?>" alt="Poncem Studio Logo" class="pdf-logo">
                        </div>
                        <div class="pdf-body text-justify">
                            <?= $contratoTexto ?>
                        </div>
                        <div class="page-break"></div>
                        <div class="pdf-logo-wrapper pt-10">
                            <img src="<?= raizUrl('/assets/logo-contrato.png') ?>" alt="Poncem Studio Logo" class="pdf-logo">
                        </div>
                        <div class="pdf-body text-justify">
                            <?= !empty($anexoTexto) ? $anexoTexto : '<h4>ANEXO I - DESCRICAO DOS SERVICOS</h4><p class="p0">A descricao detalhada dos servicos sera incluida apos a definicao do escopo do evento.</p>' ?>
                        </div>
                    </div>
                </div>
            </div>
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
                    
                    <button type="button" onclick="abrirModalAssinatura()" class="px-6 py-2.5 bg-white text-black hover:bg-zinc-200 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center gap-1.5 shadow-xl">
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
        <?php if (($contrato['status'] ?? 'rascunho') !== 'rascunho'): 
            $ehAssinado = ($contrato['status'] ?? '') === 'assinado';
        ?>
            <div class="mb-8 p-6 rounded-[2rem] bg-zinc-900/50 border border-white/5 flex flex-wrap items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-zinc-800 flex items-center justify-center <?= $ehAssinado ? 'text-emerald-400' : 'text-zinc-400' ?>">
                        <i data-lucide="<?= $ehAssinado ? 'file-check' : 'shield-check' ?>" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-white"><?= $ehAssinado ? 'Contrato Assinado com Sucesso!' : 'Contrato em Processamento de Assinatura' ?></h4>
                        <p class="text-xs text-zinc-400 mt-1"><?= $ehAssinado ? 'Este contrato foi assinado eletronicamente por todas as partes e está formalizado.' : 'Este contrato foi enviado eletronicamente e não aceita mais edições diretas.' ?></p>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    <?php if ($contrato['link_assinatura']): 
                        $linkLimpo = str_replace('painel.assinafy.com.br', 'app.assinafy.com.br', $contrato['link_assinatura']);
                    ?>
                        <a href="<?= sanitizar($linkLimpo) ?>" target="_blank" class="px-5 py-2.5 <?= $ehAssinado ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-blue-500 hover:bg-blue-600' ?> text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-all flex items-center gap-1.5 shadow-lg">
                            <i data-lucide="<?= $ehAssinado ? 'download' : 'external-link' ?>" class="w-4 h-4"></i> <?= $ehAssinado ? 'Visualizar / Baixar Assinado' : 'Acompanhar no Assinafy' ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (($contrato['status'] ?? 'rascunho') === 'pendente'): ?>
                        <button type="button" @click="sincronizarStatus()" :disabled="loading"
                                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-all flex items-center gap-1.5 cursor-pointer shadow-lg disabled:opacity-50">
                            <i data-lucide="refresh-cw" class="w-4 h-4" :class="loading ? 'animate-spin' : ''"></i> Sincronizar Status
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Layout com Sidebar de Faturamento se o contrato for assinado -->
        <div class="contrato-workspace mb-12 w-full">
            <!-- A4 Paper Preview Container -->
            <div class="contrato-preview-card bg-[#111] border border-white/5 rounded-[32px] shadow-inner">
                <!-- PDF Container Content -->
                <div class="contrato-preview-paper-wrap pointer-events-none" x-ignore>
                <div id="pdf-preview-content" class="a4-page-content">
                    <!-- Header Logo -->
                    <div class="pdf-logo-wrapper">
                        <img src="<?= raizUrl('/assets/logo-contrato.png') ?>" alt="Poncem Studio Logo" class="pdf-logo">
                    </div>

                    <!-- Contract Body Text -->
                    <div class="pdf-body text-justify">
                        <?= $contratoTexto ?>
                    </div>

                    <!-- Page Break For Anexo I -->
                    <div class="page-break"></div>
                    <div class="pdf-logo-wrapper pt-10">
                        <img src="<?= raizUrl('/assets/logo-contrato.png') ?>" alt="Poncem Studio Logo" class="pdf-logo">
                    </div>
                    <div class="pdf-body text-justify">
                        <?= !empty($anexoTexto) ? $anexoTexto : '<h4>ANEXO I - DESCRICAO DOS SERVICOS</h4><p class="p0">A descricao detalhada dos servicos sera incluida apos a definicao do escopo do evento.</p>' ?>
                    </div>
                </div>
                </div>
                <div class="contrato-preview-fade pointer-events-none"></div>
                <button type="button"
                        @click="modalContratoAberto = true; $nextTick(() => window.lucide?.createIcons?.())"
                        class="contrato-preview-button px-5 py-3 bg-white hover:bg-zinc-200 text-black rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center gap-2 shadow-xl pointer-events-auto">
                    <i data-lucide="maximize-2" class="w-4 h-4"></i>
                    Visualizar contrato completo
                </button>
                <div id="pdf-content" class="a4-page-content pdf-export-source" aria-hidden="true">
                    <div class="pdf-logo-wrapper">
                        <img src="<?= raizUrl('/assets/logo-contrato.png') ?>" alt="Poncem Studio Logo" class="pdf-logo">
                    </div>
                    <div class="pdf-body text-justify">
                        <?= $contratoTexto ?>
                    </div>
                    <div class="page-break"></div>
                    <div class="pdf-logo-wrapper pt-10">
                        <img src="<?= raizUrl('/assets/logo-contrato.png') ?>" alt="Poncem Studio Logo" class="pdf-logo">
                    </div>
                    <div class="pdf-body text-justify">
                        <?= !empty($anexoTexto) ? $anexoTexto : '<h4>ANEXO I - DESCRICAO DOS SERVICOS</h4><p class="p0">A descricao detalhada dos servicos sera incluida apos a definicao do escopo do evento.</p>' ?>
                    </div>
                </div>
            </div>

            <?php if (($contrato['status'] ?? 'rascunho') === 'assinado'): ?>
                <!-- Sidebar de Faturamento Asaas -->
                <div class="contrato-financeiro-panel bg-zinc-900/60 border border-white/10 rounded-[2rem] p-6 space-y-6">
                    <div class="flex items-center justify-between border-b border-white/5 pb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center">
                                <i data-lucide="wallet" class="w-5 h-5 text-[#0038e5]"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-white text-sm ml-[10px]">Faturamento Asaas</h4>
                                <p class="text-[10px] text-zinc-500 uppercase tracking-wider font-semibold">Integração Ativa</p>
                            </div>
                        </div>

                        <?php if ($podeGerarFinanceiro): ?>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-amber-500/10 text-amber-500 border border-amber-500/20"><?= (int)($contrato['asaas_cobranca_gerada'] ?? 0) === 1 ? 'Regularizar' : 'Pendente' ?></span>
                        <?php else: ?>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">Gerado</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($podeGerarFinanceiro): ?>
                        <div class="space-y-4">
                            <?php if ((int)($contrato['asaas_cobranca_gerada'] ?? 0) === 1 && !$temLancamentosFinanceiros): ?>
                                <div class="rounded-2xl bg-amber-500/10 border border-amber-500/20 p-4 text-xs text-amber-200 leading-relaxed">
                                    Este contrato estava marcado como faturado, mas não há lançamentos financeiros locais. Confira os dados abaixo para regularizar o financeiro.
                                </div>
                            <?php endif; ?>

                            <p class="text-xs text-zinc-400 leading-relaxed">
                                Confirme a entrada e gere o financeiro do saldo. Se a entrada já foi paga fora do Asaas, ela será lançada no financeiro sem conciliação; a conciliação acontecerá depois pelo OFX/API.
                            </p>

                            <div>
                                <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Entrada / Sinal</label>
                                <select id="asaas-entrada-status" onchange="atualizarResumoCobrancaContrato()" class="w-full bg-black/50 border border-white/10 rounded-xl px-3 py-3 text-xs text-white outline-none">
                                    <option value="pendente" <?= $entradaStatusPadrao === 'pendente' ? 'selected' : '' ?>>Cobrar entrada no Asaas</option>
                                    <option value="pago" <?= $entradaStatusPadrao === 'pago' ? 'selected' : '' ?>>Entrada já paga fora do Asaas</option>
                                    <option value="nao_aplica" <?= $entradaStatusPadrao === 'nao_aplica' ? 'selected' : '' ?>>Não usar entrada</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Valor da entrada</label>
                                    <input id="asaas-valor-sinal" oninput="atualizarResumoCobrancaContrato()" type="text" value="<?= sanitizar(number_format($valorEntradaPadrao, 2, ',', '.')) ?>" class="w-full bg-black/50 border border-white/10 rounded-xl px-3 py-3 text-xs text-white outline-none">
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Data da entrada</label>
                                    <input id="asaas-sinal-vencimento" type="date" value="<?= sanitizar($sinalVencimentoPadrao) ?>" class="w-full bg-black/50 border border-white/10 rounded-xl px-3 py-3 text-xs text-white outline-none">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Parcelas do saldo</label>
                                    <input id="asaas-total-parcelas" oninput="atualizarResumoCobrancaContrato()" type="number" min="1" max="60" value="<?= sanitizar((string)$parcelasPadrao) ?>" class="w-full bg-black/50 border border-white/10 rounded-xl px-3 py-3 text-xs text-white outline-none">
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">1ª parcela</label>
                                    <input id="asaas-first-due-date" type="date" value="<?= sanitizar($primeiraParcelaPadrao) ?>" class="w-full bg-black/50 border border-white/10 rounded-xl px-3 py-3 text-xs text-white outline-none">
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Cobrança Asaas</label>
                                <select id="asaas-billing-type" class="w-full bg-black/50 border border-white/10 rounded-xl px-3 py-3 text-xs text-white outline-none">
                                    <option value="UNDEFINED" <?= $billingTypePadrao === 'UNDEFINED' ? 'selected' : '' ?>>Cliente escolhe boleto/pix/cartão</option>
                                    <option value="PIX" <?= $billingTypePadrao === 'PIX' ? 'selected' : '' ?>>Pix</option>
                                    <option value="BOLETO" <?= $billingTypePadrao === 'BOLETO' ? 'selected' : '' ?>>Boleto</option>
                                    <option value="CREDIT_CARD" <?= $billingTypePadrao === 'CREDIT_CARD' ? 'selected' : '' ?>>Cartão de crédito</option>
                                </select>
                            </div>

                            <div id="entrada-paga-campos" class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Conta recebida</label>
                                    <select id="entrada-conta" class="w-full bg-black/50 border border-white/10 rounded-xl px-3 py-3 text-xs text-white outline-none">
                                        <option value="c6">C6 Bank</option>
                                        <?php foreach ($contasBancarias as $conta): ?>
                                            <option value="<?= sanitizar($conta['id']) ?>"><?= sanitizar($conta['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Forma</label>
                                    <select id="entrada-forma-pagamento" class="w-full bg-black/50 border border-white/10 rounded-xl px-3 py-3 text-xs text-white outline-none">
                                        <option value="pix">Pix</option>
                                        <option value="transferencia">Transferência</option>
                                        <option value="boleto">Boleto</option>
                                        <option value="cartao">Cartão</option>
                                        <option value="dinheiro">Dinheiro</option>
                                    </select>
                                </div>
                            </div>

                            <textarea id="entrada-observacao" rows="2" class="w-full bg-black/50 border border-white/10 rounded-xl px-3 py-3 text-xs text-white outline-none resize-none" placeholder="Observação interna">Entrada recebida via C6 Bank. Aguardando conciliação por OFX.</textarea>

                            <div class="rounded-2xl bg-black/40 border border-white/10 p-4">
                                <p id="resumo-cobranca-contrato" class="text-xs text-zinc-300 leading-relaxed"></p>
                            </div>

                            <button type="button" onclick="gerarCobrancaVisualizar('<?= sanitizar($contrato['id']) ?>', this)"
                                    class="w-full py-3 bg-white hover:bg-zinc-200 text-black rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-lg">
                                <i data-lucide="wallet" class="w-4 h-4"></i> Confirmar e Gerar Financeiro
                            </button>
                        </div>
                    <?php else: ?>
                        <!-- Detalhes do Faturamento -->
                        <div class="space-y-4">
                            <button type="button" onclick="sincronizarCobrancasAsaas('<?= sanitizar($contrato['id']) ?>', this)"
                                    class="w-full py-3 bg-purple-500/10 hover:bg-purple-500/20 border border-purple-500/20 text-purple-200 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                                <i data-lucide="refresh-cw" class="w-4 h-4"></i> Sincronizar Asaas
                            </button>

                            <?php if (empty($lancamentosAsaas)): ?>
                                <p class="text-xs text-zinc-500 italic text-center">Nenhum lançamento local encontrado para esta cobrança.</p>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($lancamentosAsaas as $idx => $lanc): 
                                        $statusLanc = $lanc['status'];
                                        $badgeClass = 'bg-zinc-800 text-zinc-400';
                                        $statusTxt = 'Pendente';
                                        if ($statusLanc === 'pago') {
                                            $badgeClass = 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
                                            $statusTxt = 'Pago';
                                        } elseif ($statusLanc === 'atrasado') {
                                            $badgeClass = 'bg-red-500/10 text-red-400 border border-red-500/20';
                                            $statusTxt = 'Atrasado';
                                        } elseif ($statusLanc === 'cancelado') {
                                            $badgeClass = 'bg-zinc-800 text-zinc-500';
                                            $statusTxt = 'Cancelado';
                                        }
                                    ?>
                                        <div class="bg-zinc-950/45 border border-white/5 rounded-2xl p-4 space-y-3 hover:border-white/10 transition-all">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-bold text-white"><?= sanitizar($lanc['descricao']) ?></span>
                                                <span class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase <?= $badgeClass ?>">
                                                    <?= $statusTxt ?>
                                                </span>
                                            </div>
                                            
                                            <div class="grid grid-cols-2 gap-2 text-[11px] text-zinc-400">
                                                <div>
                                                    <span class="block text-[9px] text-zinc-500 uppercase tracking-wider font-semibold">Valor</span>
                                                    <span class="font-bold text-white">R$ <?= number_format($lanc['valor'], 2, ',', '.') ?></span>
                                                </div>
                                                <div>
                                                    <span class="block text-[9px] text-zinc-500 uppercase tracking-wider font-semibold">Vencimento</span>
                                                    <span class="font-bold text-white"><?= date('d/m/Y', strtotime($lanc['vencimento'])) ?></span>
                                                </div>
                                            </div>
                                            
                                            <div class="flex gap-2 pt-1 border-t border-white/5">
                                                <?php if ($lanc['asaas_boleto_url']): ?>
                                                    <a href="<?= sanitizar($lanc['asaas_boleto_url']) ?>" target="_blank" 
                                                       class="flex-1 py-2 bg-zinc-900 hover:bg-zinc-800 border border-white/5 text-zinc-300 hover:text-white rounded-xl text-[10px] font-bold uppercase tracking-wider text-center transition-all flex items-center justify-center gap-1">
                                                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Boleto / Pix
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($lanc['asaas_invoice_url']): ?>
                                                    <a href="<?= sanitizar($lanc['asaas_invoice_url']) ?>" target="_blank"
                                                       class="flex-1 py-2 bg-zinc-900 hover:bg-zinc-800 border border-white/5 text-zinc-300 hover:text-white rounded-xl text-[10px] font-bold uppercase tracking-wider text-center transition-all flex items-center justify-center gap-1">
                                                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i> Fatura
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- Styles specifically for A4 preview and print generation -->
<style>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;700&display=swap');

.contrato-workspace {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 2rem;
    align-items: start;
}

.contrato-preview-card,
.contrato-financeiro-panel {
    height: 70vh;
    min-height: 460px;
}

.contrato-preview-card {
    position: relative;
    overflow: hidden;
    isolation: isolate;
}

.contrato-preview-paper-wrap {
    position: absolute;
    top: 32px;
    left: 50%;
    width: 210mm;
    transform: translateX(-50%) scale(var(--preview-scale, 1));
    transform-origin: top center;
}

.contrato-preview-fade {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 10%;
    z-index: 5;
    background: linear-gradient(
        to bottom,
        rgba(17, 17, 17, 0) 0%,
        rgba(17, 17, 17, 0.55) 45%,
        rgba(17, 17, 17, 1) 100%
    );
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.contrato-preview-button {
    position: absolute;
    z-index: 10;
    left: 50%;
    bottom: 24px;
    transform: translateX(-50%);
}

.contrato-financeiro-panel {
    overflow-y: auto;
}

.contrato-modal-paper {
    flex: 0 0 auto;
}

.pdf-export-source {
    position: fixed;
    left: -12000px;
    top: 0;
    z-index: -1;
    pointer-events: none;
}

@media (min-width: 1800px) {
    .contrato-preview-card {
        --preview-scale: 1.7;
    }
}

@media (min-width: 1400px) and (max-width: 1799px) {
    .contrato-preview-card {
        --preview-scale: 0.94;
    }
}

@media (min-width: 1024px) and (max-width: 1399px) {
    .contrato-preview-card {
        --preview-scale: 0.76;
    }
}

@media (max-width: 1023px) {
    .contrato-workspace {
        grid-template-columns: 1fr;
    }

    .contrato-preview-card,
    .contrato-financeiro-panel {
        height: auto;
        min-height: 520px;
    }

    .contrato-preview-card {
        --preview-scale: 0.56;
    }
}

.a4-page-content {
    background: #ffffff;
    color: #231f20;
    width: 210mm;
    min-height: 297mm;
    padding: 10pt 50.5pt 15pt 47.3pt;
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
    margin-top: 35px;
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
    page-break-after: avoid;
    break-after: avoid;
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
    page-break-after: avoid;
    break-after: avoid;
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
    line-height: 1.4;
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
document.addEventListener('alpine:init', () => {
    Alpine.data('contratoVisualizarApp', () => ({
        id: <?= json_encode($id) ?>,
        loading: false,
        loadingAnexo: false,
        loadingMessage: '',
        modalContratoAberto: false,
        showConfirmModal: false,
        
        exportarPDFLocal() {
            this.loading = true;
            if (typeof html2pdf === 'undefined') {
                alert('Biblioteca de PDF não carregada. Recarregue a página e tente novamente.');
                this.loading = false;
                return;
            }
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
            this.showConfirmModal = true;
        },

        confirmarEnvio() {
            const pdfLibReady = typeof html2pdf !== 'undefined';
            if (!pdfLibReady) {
                alert('Biblioteca de PDF não carregada. Recarregue a página e tente novamente.');
                return;
            }
            this.showConfirmModal = false;
            this.loading = true;
            this.loadingMessage = 'Gerando PDF de alta definição...';
            
            const original = document.getElementById('pdf-content');
            const element = original.cloneNode(true);
            element.classList.remove('pdf-export-source');
            element.style.position = 'fixed';
            element.style.left = '0';
            element.style.top = '0';
            element.style.zIndex = '99999';
            element.style.width = '210mm';
            element.style.backgroundColor = '#ffffff';
            element.style.color = '#231f20';
            element.style.pointerEvents = 'none';

            // Força a cor do texto para escuro em todas as tags filhas (evita que o modo escuro a herde como branca)
            element.querySelectorAll('*').forEach(child => {
                child.style.color = '#231f20';
                child.style.backgroundColor = 'transparent';
            });

            document.body.appendChild(element);

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
            
            // Aguarda 150ms para garantir a renderização e reflow do clone no DOM pelo navegador antes do canvas capturar
            setTimeout(() => {
                html2pdf().set(opt).from(element).outputPdf('blob')
                .then(blob => {
                    element.remove();
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
                element.remove();
                alert('Erro ao enviar documento para o servidor.');
                this.loading = false;
            });
        },
        
        sincronizarStatus() {
            this.loading = true;
            this.loadingMessage = 'Consultando status no Assinafy...';

            const formData = new FormData();
            formData.append('id', this.id);

            fetch('<?= raizUrl("/api/contratos/sincronizar_status.php") ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                this.loading = false;
                if (data.success) {
                    alert(data.mensagem);
                    window.location.reload();
                } else {
                    alert('Erro ao sincronizar status: ' + (data.erro || data.error || 'Erro interno.'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Erro de conexão ao sincronizar status.');
                this.loading = false;
            });
        })
    })
})
</script>

<!-- Modal de Configuração Assinafy -->
<script>
function abrirModalAssinatura() {
    const modal = document.getElementById('modal-confirm-assinatura');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.style.display = 'flex';
    modal.style.zIndex = '9999';
}

function fecharModalAssinatura() {
    const modal = document.getElementById('modal-confirm-assinatura');
    if (!modal) return;
    modal.classList.remove('flex');
    modal.classList.add('hidden');
    modal.style.display = 'none';
}

function setContratoAssinaturaLoading(active, message = '') {
    let overlay = document.getElementById('contrato-assinatura-loading');

    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'contrato-assinatura-loading';
        overlay.className = 'fixed inset-0 bg-black/80 backdrop-blur-md hidden flex-col items-center justify-center gap-4';
        overlay.style.zIndex = '9999';
        overlay.innerHTML = `
            <div class="w-12 h-12 border-4 border-white/10 border-t-white rounded-full animate-spin"></div>
            <p class="text-sm font-bold text-white uppercase tracking-widest"></p>
        `;
        document.body.appendChild(overlay);
    }

    overlay.querySelector('p').textContent = message;
    overlay.classList.toggle('hidden', !active);
    overlay.classList.toggle('flex', active);
}

function confirmarEnvioAssinatura() {
    fecharModalAssinatura();

    if (typeof html2pdf === 'undefined') {
        alert('Biblioteca de PDF nao carregada. Recarregue a pagina e tente novamente.');
        return;
    }

    const original = document.getElementById('pdf-content');
    if (!original) {
        alert('Conteudo do contrato nao encontrado para gerar o PDF.');
        return;
    }

    const element = original.cloneNode(true);
    element.classList.remove('pdf-export-source');
    element.style.position = 'fixed';
    element.style.left = '0';
    element.style.top = '0';
    element.style.zIndex = '99999';
    element.style.width = '210mm';
    element.style.backgroundColor = '#ffffff';
    element.style.color = '#231f20';
    element.style.pointerEvents = 'none';

    // Força a cor do texto para escuro em todas as tags filhas (evita que o modo escuro a herde como branca)
    element.querySelectorAll('*').forEach(child => {
        child.style.color = '#231f20';
        child.style.backgroundColor = 'transparent';
    });

    document.body.appendChild(element);

    setContratoAssinaturaLoading(true, 'Gerando PDF de alta definicao...');

    const contratoId = <?= json_encode($id) ?>;
    const opt = {
        margin: [15, 0, 18, 0],
        filename: 'Contrato_' + contratoId + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, logging: false },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak: {
            mode: ['css', 'legacy'],
            avoid: ['p', 'h3', 'h4', 'li', 'tr', '.pdf-signatures-wrapper', 'table']
        }
    };

    // Aguarda 150ms para garantir a renderização e reflow do clone no DOM pelo navegador antes do canvas capturar
    setTimeout(() => {
        html2pdf().set(opt).from(element).outputPdf('blob')
            .then(blob => {
                element.remove();
                setContratoAssinaturaLoading(true, 'Enviando documento para o Assinafy...');

            const formData = new FormData();
            formData.append('pdf', blob, 'Contrato_' + contratoId + '.pdf');
            formData.append('id', contratoId);

            return fetch('<?= raizUrl("/api/contratos/enviar_assinatura.php") ?>', {
                method: 'POST',
                body: formData
            });
        })
        .then(res => res.json())
        .then(data => {
            setContratoAssinaturaLoading(false);

            if (data.success) {
                alert('Contrato enviado com sucesso para assinatura eletronica!');
                window.location.reload();
                return;
            }

            alert('Erro ao enviar assinatura: ' + (data.erro || data.error || 'Erro interno. Verifique as credenciais do Assinafy nas configuracoes.'));
        })
        .catch(err => {
            console.error(err);
            element.remove();
            setContratoAssinaturaLoading(false);
            alert('Erro ao enviar documento para o servidor.');
        });
}
</script>

<div id="modal-assinafy" class="fixed inset-0 bg-black/80 backdrop-blur-md hidden items-center justify-center p-4" style="z-index: 9999;">
    <div class="bg-zinc-950 border border-white/10 rounded-[2rem] p-8 w-full max-w-md shadow-2xl relative">
        <button onclick="fecharModalAssinafy()" class="absolute top-6 right-6 text-zinc-400 hover:text-white transition-colors cursor-pointer">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
        
        <div class="mb-6">
            <div class="w-12 h-12 rounded-2xl bg-zinc-900 flex items-center justify-center text-zinc-300 mb-4 border border-white/5">
                <i data-lucide="key" class="w-6 h-6"></i>
            </div>
            <h3 class="text-xl font-black text-white">Configurar API Assinafy</h3>
            <p class="text-xs text-zinc-400 mt-1">Insira suas credenciais da Assinafy para enviar contratos eletrônicos.</p>
        </div>
        
        <form id="form-config-assinafy" onsubmit="salvarConfigAssinafy(event)">
            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-[11px] font-black uppercase tracking-wider text-zinc-400 mb-2">API Key (Token)</label>
                    <input type="password" id="assinafy-api-key" name="assinafy_api_key" 
                           class="w-full bg-zinc-900 border border-white/5 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-white/20 transition-all placeholder-zinc-600"
                           placeholder="<?= !empty($config['assinafy_api_key']) ? '••••••••••••••••••••••••••••••••' : 'Cole a chave da API' ?>">
                    <?php if (!empty($config['assinafy_api_key'])): ?>
                        <span class="text-[10px] text-emerald-500 flex items-center gap-1 mt-1.5"><i data-lucide="check" class="w-3.5 h-3.5"></i> Chave atualmente salva</span>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label class="block text-[11px] font-black uppercase tracking-wider text-zinc-400 mb-2">ID da Conta (Account ID) *</label>
                    <input type="text" id="assinafy-account-id" name="assinafy_account_id" required
                           value="<?= sanitizar($config['assinafy_account_id'] ?? '') ?>"
                           class="w-full bg-zinc-900 border border-white/5 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-white/20 transition-all placeholder-zinc-600"
                           placeholder="ID da Conta no painel">
                </div>
                
                <div>
                    <label class="block text-[11px] font-black uppercase tracking-wider text-zinc-400 mb-2">Ambiente Ativo</label>
                    <select id="assinafy-mode" name="assinafy_mode" 
                            class="w-full bg-zinc-900 border border-white/5 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-white/20 transition-all cursor-pointer">
                        <option value="test" <?= ($config['assinafy_mode'] ?? 'test') === 'test' ? 'selected' : '' ?>>🧪 Sandbox (Testes)</option>
                        <option value="prod" <?= ($config['assinafy_mode'] ?? 'test') === 'prod' ? 'selected' : '' ?>>🟢 Produção (Real)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-black uppercase tracking-wider text-zinc-400 mb-2">URL do Webhook</label>
                    <div class="flex gap-2">
                        <input type="text" id="assinafy-webhook-url" readonly
                               value="<?= sanitizar(preg_replace('#/sistema/?$#', '', rtrim(APP_URL, '/')) . raizUrl('/api/contratos/webhook_assinafy.php')) ?>"
                               class="flex-1 bg-zinc-900 border border-white/5 rounded-xl px-4 py-3 text-xs text-zinc-300 focus:outline-none focus:border-white/20 transition-all">
                        <button type="button" onclick="copiarWebhookAssinafy()"
                                class="px-4 py-3 bg-zinc-900 hover:bg-zinc-800 border border-white/5 text-zinc-200 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                            Copiar
                        </button>
                    </div>
                    <p class="text-[10px] text-zinc-500 mt-2">Use esta URL no painel da Assinafy e ative eventos de assinatura, rejeição e documento pronto.</p>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button type="button" onclick="fecharModalAssinafy()" 
                        class="flex-1 py-3 bg-zinc-900 hover:bg-zinc-850 text-zinc-400 hover:text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-all cursor-pointer">
                    Cancelar
                </button>
                <button type="submit" id="btn-salvar-assinafy"
                        class="flex-1 py-3 bg-white hover:bg-zinc-200 text-black rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalAssinafy() {
    const modal = document.getElementById('modal-assinafy');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function fecharModalAssinafy() {
    const modal = document.getElementById('modal-assinafy');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}

function copiarWebhookAssinafy() {
    const input = document.getElementById('assinafy-webhook-url');
    if (!input) return;

    input.select();
    input.setSelectionRange(0, input.value.length);

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.value)
            .then(() => alert('URL do webhook copiada.'))
            .catch(() => document.execCommand('copy'));
        return;
    }

    document.execCommand('copy');
    alert('URL do webhook copiada.');
}

function salvarConfigAssinafy(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-salvar-assinafy');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg> Salvando...`;
    
    const apiKey = document.getElementById('assinafy-api-key').value;
    const accountId = document.getElementById('assinafy-account-id').value;
    const mode = document.getElementById('assinafy-mode').value;
    
    fetch('<?= raizUrl("/api/contratos/salvar_config_assinafy.php") ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            assinafy_api_key: apiKey,
            assinafy_account_id: accountId,
            assinafy_mode: mode
        })
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (data.success) {
            alert('Configurações salvas com sucesso!');
            fecharModalAssinafy();
            window.location.reload();
        } else {
            alert(data.erro || 'Falha ao salvar configurações.');
        }
    })
    .catch(err => {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('Erro de conexão ao salvar configurações.');
    });
}

function numeroContratoMoeda(valor) {
    valor = String(valor || '').replace(/[^\d,.-]/g, '');
    if (valor.includes(',') && valor.includes('.')) {
        valor = valor.replace(/\./g, '').replace(',', '.');
    } else {
        valor = valor.replace(',', '.');
    }
    return Number(valor) || 0;
}

function moedaContrato(valor) {
    return (Number(valor) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function atualizarResumoCobrancaContrato() {
    const resumo = document.getElementById('resumo-cobranca-contrato');
    if (!resumo) return;

    const total = <?= json_encode((float)$contrato['valor_total']) ?>;
    const statusEntrada = document.getElementById('asaas-entrada-status')?.value || 'pendente';
    const entrada = statusEntrada === 'nao_aplica' ? 0 : numeroContratoMoeda(document.getElementById('asaas-valor-sinal')?.value);
    const parcelas = Math.max(1, parseInt(document.getElementById('asaas-total-parcelas')?.value || '1', 10));
    const saldo = Math.max(0, total - entrada);
    const camposEntrada = document.getElementById('entrada-paga-campos');
    const obsEntrada = document.getElementById('entrada-observacao');

    if (camposEntrada) camposEntrada.style.display = statusEntrada === 'pago' ? 'grid' : 'none';
    if (obsEntrada) obsEntrada.style.display = statusEntrada === 'pago' ? '' : 'none';

    if (statusEntrada === 'pago') {
        resumo.textContent = `Será lançado ${moedaContrato(entrada)} como entrada paga fora do Asaas, sem conciliar. O Asaas vai gerar ${parcelas} parcela(s) sobre o saldo de ${moedaContrato(saldo)}.`;
    } else if (statusEntrada === 'nao_aplica') {
        resumo.textContent = `Sem entrada. O Asaas vai gerar ${parcelas} parcela(s) sobre o total de ${moedaContrato(total)}.`;
    } else {
        resumo.textContent = `O Asaas vai cobrar a entrada de ${moedaContrato(entrada)} e gerar ${parcelas} parcela(s) sobre o saldo de ${moedaContrato(saldo)}.`;
    }
}

function gerarCobrancaVisualizar(id, btn) {
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg> Gerando...';
    const entradaStatus = document.getElementById('asaas-entrada-status')?.value || 'pendente';
    const params = new URLSearchParams({
        id,
        entrada_status: entradaStatus,
        gerar_apenas_saldo: entradaStatus === 'pago' ? '1' : '0',
        asaas_valor_sinal: document.getElementById('asaas-valor-sinal')?.value || '0',
        asaas_sinal_vencimento: document.getElementById('asaas-sinal-vencimento')?.value || '',
        asaas_total_parcelas: document.getElementById('asaas-total-parcelas')?.value || '1',
        asaas_first_due_date: document.getElementById('asaas-first-due-date')?.value || '',
        asaas_billing_type: document.getElementById('asaas-billing-type')?.value || 'UNDEFINED',
        entrada_conta: document.getElementById('entrada-conta')?.value || 'c6',
        entrada_forma_pagamento: document.getElementById('entrada-forma-pagamento')?.value || 'pix',
        entrada_observacao: document.getElementById('entrada-observacao')?.value || ''
    });

    fetch('<?= raizUrl("/api/contratos/gerar_asaas.php") ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.erro || 'Não foi possível gerar a cobrança.');
        }
        alert(data.mensagem || 'Cobrança gerada com sucesso.');
        window.location.reload();
    })
    .catch(err => {
        alert('Erro ao gerar cobrança: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = original;
    });
}

function sincronizarCobrancasAsaas(id, btn) {
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg> Sincronizando...';

    fetch('<?= raizUrl("/api/contratos/sincronizar_asaas.php") ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.erro || 'Não foi possível sincronizar com o Asaas.');
        }
        alert(data.mensagem || 'Sincronização concluída.');
        window.location.reload();
    })
    .catch(err => {
        alert('Erro ao sincronizar Asaas: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = original;
    });
}

document.addEventListener('DOMContentLoaded', atualizarResumoCobrancaContrato);
</script>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
