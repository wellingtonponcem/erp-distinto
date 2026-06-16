<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirDistinto();
$db = Database::get();

$statusMessage = '';
$errorMessage = '';

if (isset($_GET['deletar'])) {
    $id = $_GET['deletar'];
    try {
        $stmt = $db->prepare("DELETE FROM contratos WHERE id = ?");
        $stmt->execute([$id]);
        $statusMessage = 'Contrato excluído com sucesso.';
    } catch (Exception $e) {
        $errorMessage = 'Erro ao excluir contrato: ' . $e->getMessage();
    }
}

// Fetch all contracts with their proposals
$contratos = $db->query("
    SELECT c.*, p.slug as proposta_slug
    FROM contratos c
    LEFT JOIN propostas p ON c.proposta_id = p.id
    ORDER BY c.created_at DESC
")->fetchAll();

$config = $db->query("SELECT assinafy_api_key, assinafy_account_id, assinafy_mode FROM configuracao_empresa WHERE id = 'principal' LIMIT 1")->fetch();

$tituloPagina = 'Contratos Comerciais';
require_once __DIR__ . '/../includes/layout/head.php';
?>
<div id="app-wrapper">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>
    <main id="main-content" class="content-sheet flex flex-col min-h-screen">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-zinc-900 dark:text-white flex items-center gap-3">
                    <i data-lucide="scroll" class="w-8 h-8 text-zinc-400"></i>
                    Contratos
                </h1>
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mt-1">Gerencie, envie para assinatura eletrônica e acompanhe o status de formalização das propostas.</p>
            </div>
            <div>
                <button onclick="abrirModalAssinafy()" class="px-5 py-2.5 bg-zinc-900 border border-white/5 hover:bg-zinc-800 text-zinc-300 rounded-xl text-xs font-bold uppercase tracking-widest transition-all flex items-center gap-1.5 shadow-xl cursor-pointer">
                    <i data-lucide="key" class="w-4 h-4"></i> API Assinafy
                </button>
            </div>
        </div>

        <?php if ($statusMessage): ?>
            <div class="mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-sm font-bold flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                <?= sanitizar($statusMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400 text-sm font-bold flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                <?= sanitizar($errorMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Lista de Contratos -->
        <div class="space-y-4">
            <div class="flex items-center justify-between ml-4 mb-4">
                <h2 class="text-[11px] font-black uppercase tracking-[0.2em] text-zinc-500">Histórico de Contratos (<?= count($contratos) ?>)</h2>
                <a href="<?= raizUrl('/gerenciamento/propostas.php') ?>" class="text-xs font-bold text-zinc-600 dark:text-zinc-400 hover:text-zinc-950 dark:hover:text-white transition-colors flex items-center gap-1">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Gerar a partir de Proposta
                </a>
            </div>
            
            <div class="grid grid-cols-1 gap-3">
                <?php foreach ($contratos as $contrato): ?>
                    <?php
                    $clienteNome = sanitizar(decodificarEntidades($contrato['cliente_nome'] ?? ''));
                    $tituloContrato = sanitizar(decodificarEntidades($contrato['titulo'] ?? ''));
                    $status = $contrato['status'] ?? 'rascunho';
                    $statusLabel = 'Rascunho';
                    $statusClass = 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400';
                    if ($status === 'pendente') {
                        $statusLabel = 'Pendente Assinatura';
                        $statusClass = 'bg-blue-500/10 text-blue-500';
                    } elseif ($status === 'assinado') {
                        $statusLabel = 'Assinado';
                        $statusClass = 'bg-emerald-500/10 text-emerald-500';
                        if ((int)($contrato['asaas_cobranca_gerada'] ?? 0) === 0) {
                            $statusLabel = 'Assinado • Cobrança Pendente';
                            $statusClass = 'bg-amber-500/10 text-amber-500 border border-amber-500/20';
                        }
                    } elseif ($status === 'cancelado') {
                        $statusLabel = 'Cancelado';
                        $statusClass = 'bg-red-500/10 text-red-500';
                    }
                    ?>
                    <div class="bg-white/50 dark:bg-zinc-900/30 border border-zinc-200/60 dark:border-zinc-800/60 rounded-3xl p-5 flex flex-wrap items-center justify-between gap-4 group hover:border-zinc-400 dark:hover:border-zinc-600 transition-all">
                        <div class="flex items-center gap-5">
                            <div class="w-12 h-12 rounded-2xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400 group-hover:text-zinc-900 dark:group-hover:text-white transition-colors">
                                <i data-lucide="scroll" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="font-bold text-zinc-900 dark:text-white"><?= $clienteNome ?></h3>
                                    <span class="px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider <?= $statusClass ?>">
                                        <?= $statusLabel ?>
                                    </span>
                                </div>
                                <p class="text-[11px] font-medium text-zinc-500 uppercase tracking-widest"><?= $tituloContrato ?></p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-8 items-center">
                            <div>
                                <p class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Valor Total</p>
                                <p class="text-xs font-bold text-zinc-600 dark:text-zinc-400 mt-0.5">R$ <?= number_format($contrato['valor_total'], 2, ',', '.') ?></p>
                            </div>
                            
                            <div class="hidden sm:block">
                                <p class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Data do Contrato</p>
                                <p class="text-xs font-bold text-zinc-600 dark:text-zinc-400 mt-0.5">
                                    <?= $contrato['data_contrato'] ? date('d/m/Y', strtotime($contrato['data_contrato'])) : '—' ?>
                                </p>
                            </div>

                            <?php if ($contrato['link_assinatura']): ?>
                                <div class="hidden md:block">
                                    <p class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Assinatura Eletrônica</p>
                                    <?php if ($status === 'assinado'): ?>
                                        <a href="<?= sanitizar($contrato['link_assinatura']) ?>" target="_blank" class="text-xs font-bold text-emerald-500 hover:underline flex items-center gap-1 mt-0.5">
                                            <i data-lucide="file-check" class="w-3.5 h-3.5"></i> Documento Assinado
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= sanitizar($contrato['link_assinatura']) ?>" target="_blank" class="text-xs font-bold text-blue-500 hover:underline flex items-center gap-1 mt-0.5">
                                            <i data-lucide="signature" class="w-3.5 h-3.5"></i> Link Assinafy
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="flex items-center gap-2 border-l border-zinc-100 dark:border-zinc-800/50 pl-6">
                                <?php if ($status === 'pendente' && !empty($contrato['documento_assinatura_id'])): ?>
                                    <button type="button"
                                            onclick="sincronizarStatusContrato('<?= sanitizar($contrato['id']) ?>', this)"
                                            class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500 hover:text-white transition-all"
                                            title="Sincronizar status com Assinafy">
                                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                    </button>
                                <?php endif; ?>

                                <?php if ($status === 'assinado' && (int)($contrato['asaas_cobranca_gerada'] ?? 0) === 0): ?>
                                    <button type="button"
                                            onclick="gerarCobrancaAsaas('<?= sanitizar($contrato['id']) ?>', this)"
                                            class="p-2.5 rounded-xl bg-purple-500/10 text-purple-500 hover:bg-purple-500 hover:text-white transition-all"
                                            title="Gerar Cobrança Asaas">
                                        <i data-lucide="wallet" class="w-4 h-4"></i>
                                    </button>
                                <?php endif; ?>

                                <a href="<?= raizUrl('/gerenciamento/contrato_visualizar.php?id=' . $contrato['id']) ?>" 
                                   class="p-2.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-900 dark:hover:bg-white hover:text-white dark:hover:text-black transition-all"
                                   title="Visualizar PDF / Enviar">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                
                                <?php if ($status === 'rascunho'): ?>
                                    <a href="<?= raizUrl('/gerenciamento/contrato_gerar.php?id=' . $contrato['id']) ?>" 
                                       class="p-2.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-900 dark:hover:bg-white hover:text-white dark:hover:text-black transition-all"
                                       title="Editar Conteúdo">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <a href="<?= raizUrl('/gerenciamento/contratos.php?deletar=' . $contrato['id']) ?>" 
                                   onclick="return confirm('Tem certeza que deseja excluir este contrato? Essa ação não pode ser desfeita.');"
                                   class="p-2.5 rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all"
                                   title="Excluir Contrato">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($contratos)): ?>
                    <div class="py-20 text-center bg-white/50 dark:bg-zinc-900/10 rounded-[32px] border border-dashed border-zinc-200 dark:border-zinc-800">
                        <i data-lucide="scroll" class="w-12 h-12 mx-auto text-zinc-800 mb-4 opacity-20"></i>
                        <p class="text-sm font-bold text-zinc-500 mb-2">Nenhum contrato gerado ainda.</p>
                        <p class="text-xs text-zinc-400 max-w-sm mx-auto">Vá para a seção de <a href="<?= raizUrl('/gerenciamento/propostas.php') ?>" class="text-zinc-600 dark:text-zinc-300 font-bold hover:underline">Propostas Web</a> e clique com o botão direito em uma proposta para gerar seu contrato.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Modal de Configuração Assinafy -->
<div id="modal-assinafy" class="fixed inset-0 bg-black/80 backdrop-blur-md z-[9999] hidden items-center justify-center p-4"
     style="display: none; z-index: 9999;">
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
    modal.style.display = 'flex';
    modal.style.zIndex = '9999';
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function fecharModalAssinafy() {
    const modal = document.getElementById('modal-assinafy');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
    modal.style.display = 'none';
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

function sincronizarStatusContrato(id, btn) {
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg>';

    fetch('<?= raizUrl("/api/contratos/sincronizar_status.php") ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.erro || 'Não foi possível sincronizar o status.');
        }
        alert(data.mensagem || 'Status sincronizado.');
        window.location.reload();
    })
    .catch(err => {
        alert('Erro ao sincronizar status: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = original;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
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

function gerarCobrancaAsaas(id, btn) {
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg>';

    fetch('<?= raizUrl("/api/contratos/gerar_asaas.php") ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id })
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
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
}
</script>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
