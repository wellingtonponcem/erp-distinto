<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAdmin();
$db = Database::get();

// Buscar todas oportunidades com dados de proposta
$oportunidades = [];
try {
    $oportunidades = $db->query("
        SELECT o.*, c.nome AS cliente_nome, c.contato AS cliente_contato,
               p.id AS proposta_id, p.slug AS proposta_slug, p.status AS proposta_status, 
               p.tipo AS proposta_tipo, p.valor_total AS proposta_valor, p.dados_json
        FROM oportunidades o 
        LEFT JOIN clientes c ON c.id = o.cliente_id
        LEFT JOIN propostas p ON p.oportunidade_id = o.id
        ORDER BY o.atualizado_em DESC
    ")->fetchAll();
} catch (Exception $e) {
    // Fallback sem ordem ou com created_at
    try {
        $oportunidades = $db->query("
            SELECT o.*, c.nome AS cliente_nome, c.contato AS cliente_contato,
                   p.id AS proposta_id, p.slug AS proposta_slug, p.status AS proposta_status, 
                   p.tipo AS proposta_tipo, p.valor_total AS proposta_valor, p.dados_json
            FROM oportunidades o 
            LEFT JOIN clientes c ON c.id = o.cliente_id
            LEFT JOIN propostas p ON p.oportunidade_id = o.id
        ")->fetchAll();
    } catch (Exception $e2) {}
}

// Incorporar Propostas Órfãs (sem oportunidade vinculada)
try {
    $stmtOrfas = $db->query("
        SELECT p.id, p.cliente_id, p.cliente_nome AS nome, p.valor_total AS valor_estimado,
               CASE p.status WHEN 'rascunho' THEN 'novo' WHEN 'pendente' THEN 'proposta' WHEN 'aceita' THEN 'ganha' WHEN 'recusada' THEN 'perdida' ELSE 'proposta' END AS etapa,
               p.created_at AS previsao, 'Sistema' AS responsavel, 
               CONCAT('Proposta: ', p.titulo) AS descricao,
               p.id AS proposta_id, p.slug AS proposta_slug, p.status AS proposta_status,
               p.tipo AS proposta_tipo, p.valor_total AS proposta_valor, p.dados_json,
               c.nome AS cliente_nome, c.contato AS cliente_contato,
               TRUE AS is_proposta
        FROM propostas p
        LEFT JOIN clientes c ON c.id = p.cliente_id
        WHERE (p.oportunidade_id IS NULL OR p.oportunidade_id = '')
    ");
    $orfas = $stmtOrfas->fetchAll();
    $oportunidades = array_merge($oportunidades, $orfas);
} catch (Exception $e) {}

// Agrupar por etapa
$etapas = ['novo'=>'Novo','qualificado'=>'Qualificado','proposta'=>'Proposta','negociacao'=>'Negociação','ganha'=>'Ganha','perdida'=>'Perdida'];
$colunas = [];
foreach ($etapas as $k => $v) $colunas[$k] = [];
foreach ($oportunidades as $o) {
    $etapa = $o['etapa'] ?? 'novo';
    if (isset($colunas[$etapa])) $colunas[$etapa][] = $o;
}

// Contagens para métricas
$totalOps = count($oportunidades);
$totalValor = array_sum(array_map(function($o) { return (float)($o['proposta_valor'] ?: $o['valor_estimado'] ?? 0); }, $oportunidades));

$clientes = $db->query("SELECT id, nome FROM clientes ORDER BY nome ASC")->fetchAll();

$tituloPagina = 'CRM • Pipeline';
require_once __DIR__ . '/../includes/layout/head.php';
?>
<style>
.kanban-wrapper { display:flex; gap:12px; overflow-x:auto; padding-bottom:20px; min-height:calc(100vh - 240px); }
.kanban-col { min-width:280px; max-width:300px; flex:1; display:flex; flex-direction:column; }
.kanban-col-header { padding:14px 16px; border-radius:16px 16px 0 0; display:flex; align-items:center; justify-content:space-between; }
.kanban-col-body { flex:1; padding:8px; border-radius:0 0 16px 16px; min-height:120px; display:flex; flex-direction:column; gap:8px; transition:background 0.2s; }
.kanban-col-body.drag-over { background:rgba(197,168,128,0.12) !important; outline:2px dashed rgba(197,168,128,0.4); }
.kanban-card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:14px; padding:14px 16px; cursor:grab; transition:all 0.2s; position:relative; }
.kanban-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,0,0,0.15); border-color:rgba(197,168,128,0.4); }
.kanban-card:active { cursor:grabbing; opacity:0.7; }
.kanban-card.dragging { opacity:0.4; transform:scale(0.95); }

:root { --card-bg:#fff; --card-border:rgba(0,0,0,0.08); --col-bg:rgba(0,0,0,0.03); --col-header-bg:rgba(0,0,0,0.06); }
@media(prefers-color-scheme:dark) {
    :root { --card-bg:rgba(255,255,255,0.04); --card-border:rgba(255,255,255,0.08); --col-bg:rgba(255,255,255,0.02); --col-header-bg:rgba(255,255,255,0.05); }
}
.dark .kanban-card { --card-bg:rgba(255,255,255,0.04); --card-border:rgba(255,255,255,0.08); }
.dark .kanban-col-body { background:rgba(255,255,255,0.02); }
.dark .kanban-col-header { background:rgba(255,255,255,0.05); }

/* Modal */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(8px); z-index:9999; display:none; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-panel { background:#fff; border-radius:24px; width:95%; max-width:680px; max-height:90vh; overflow-y:auto; box-shadow:0 32px 64px rgba(0,0,0,0.3); animation:modalIn 0.25s ease; }
.dark .modal-panel { background:#1a1a1a; }
@keyframes modalIn { from{opacity:0;transform:translateY(20px)scale(0.97)} to{opacity:1;transform:none} }

.pipeline-stats { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
.stat-chip { padding:8px 16px; border-radius:12px; font-size:11px; font-weight:800; letter-spacing:0.05em; text-transform:uppercase; display:flex; align-items:center; gap:6px; }
</style>

<div id="app-wrapper">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>
    <main id="main-content" style="overflow-x:hidden;">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-zinc-900 dark:text-white flex items-center gap-3">
                    <i data-lucide="kanban" class="w-7 h-7 text-zinc-400"></i> Pipeline de Oportunidades
                </h1>
                <p class="text-xs font-medium text-zinc-500 mt-1">Arraste os cards para mover entre etapas</p>
            </div>
            <button onclick="document.getElementById('modal-novo').classList.add('active')" 
                    class="bg-zinc-900 dark:bg-white text-white dark:text-black px-5 py-2.5 rounded-xl text-xs font-black hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i> Nova Oportunidade
            </button>
        </div>

        <!-- Pipeline Stats -->
        <div class="pipeline-stats">
            <?php 
            $cores = ['novo'=>'bg-zinc-500','qualificado'=>'bg-blue-500','proposta'=>'bg-purple-500','negociacao'=>'bg-amber-500','ganha'=>'bg-emerald-500','perdida'=>'bg-red-500'];
            foreach ($etapas as $k => $label): ?>
            <div class="stat-chip bg-zinc-100 dark:bg-zinc-800/50 text-zinc-600 dark:text-zinc-400">
                <span class="w-2 h-2 rounded-full <?= $cores[$k] ?>"></span>
                <?= $label ?>: <span class="text-zinc-900 dark:text-white"><?= count($colunas[$k]) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="stat-chip bg-zinc-900 dark:bg-white text-white dark:text-black ml-auto">
                Total: <?= formatarMoeda($totalValor) ?>
            </div>
        </div>

        <!-- Kanban Board -->
        <div class="kanban-wrapper">
            <?php foreach ($etapas as $etapaKey => $etapaLabel): ?>
            <div class="kanban-col">
                <div class="kanban-col-header" style="background:var(--col-header-bg);">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full <?= $cores[$etapaKey] ?>"></span>
                        <span class="text-[11px] font-black uppercase tracking-widest text-zinc-600 dark:text-zinc-400"><?= $etapaLabel ?></span>
                    </div>
                    <span class="text-[10px] font-black text-zinc-400 bg-zinc-200 dark:bg-zinc-700 px-2 py-0.5 rounded-full"><?= count($colunas[$etapaKey]) ?></span>
                </div>
                <div class="kanban-col-body" style="background:var(--col-bg);" 
                     data-etapa="<?= $etapaKey ?>"
                     ondragover="event.preventDefault();this.classList.add('drag-over')" 
                     ondragleave="this.classList.remove('drag-over')"
                     ondrop="dropCard(event, '<?= $etapaKey ?>')">
                    
                    <?php foreach ($colunas[$etapaKey] as $o): 
                        $dados = $o['dados_json'] ? json_decode($o['dados_json'], true) : [];
                        $whatsapp = $dados['whatsapp'] ?? $o['cliente_contato'] ?? '';
                        $waClean = preg_replace('/[^0-9]/', '', $whatsapp);
                    ?>
                    <div class="kanban-card" draggable="true" data-id="<?= $o['id'] ?>"
                         ondragstart="startDrag(event)" ondragend="endDrag(event)"
                         onclick="openDetail('<?= $o['id'] ?>')">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h4 class="text-sm font-bold text-zinc-900 dark:text-white leading-tight"><?= sanitizar($o['nome']) ?></h4>
                            <?php if ($o['proposta_slug']): ?>
                            <span class="shrink-0 text-[8px] font-black uppercase px-1.5 py-0.5 rounded bg-purple-500/15 text-purple-500 border border-purple-500/20">Proposta</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-500 mb-2"><?= sanitizar($o['cliente_nome'] ?: 'Sem cliente') ?></p>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black text-zinc-700 dark:text-zinc-300"><?= formatarMoeda((float)($o['proposta_valor'] ?: $o['valor_estimado'])) ?></span>
                            <?php if ($waClean): ?>
                            <a href="https://wa.me/<?= $waClean ?>" target="_blank" onclick="event.stopPropagation()" 
                               class="w-6 h-6 rounded-full bg-emerald-500/15 text-emerald-500 flex items-center justify-center hover:bg-emerald-500 hover:text-white transition-all">
                                <i data-lucide="message-circle" class="w-3 h-3"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php if ($o['previsao']): ?>
                        <p class="text-[10px] text-zinc-400 mt-2 flex items-center gap-1">
                            <i data-lucide="calendar" class="w-3 h-3"></i> <?= date('d/m', strtotime($o['previsao'])) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<!-- Modal Nova Oportunidade -->
<div id="modal-novo" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('active')">
    <div class="modal-panel p-8">
        <h2 class="text-lg font-black text-zinc-900 dark:text-white mb-6 flex items-center gap-2">
            <i data-lucide="plus-circle" class="w-5 h-5 text-zinc-400"></i> Nova Oportunidade
        </h2>
        <form method="post" action="<?= raizUrl('/gerenciamento/oportunidades.php') ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="md:col-span-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-1 block">Nome *</label>
                    <input class="w-full bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 ring-zinc-400/30" 
                           type="text" name="nome" required placeholder="Ex: Casamento Igor & Gabriela">
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-1 block">Cliente</label>
                    <select class="w-full bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 text-sm outline-none" name="cliente_id">
                        <option value="">— Nenhum —</option>
                        <?php foreach ($clientes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= sanitizar($c['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-1 block">Valor Estimado</label>
                    <input class="w-full bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 text-sm outline-none" 
                           type="number" step="0.01" name="valor_estimado" value="0">
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-1 block">Etapa</label>
                    <select class="w-full bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 text-sm outline-none" name="etapa">
                        <?php foreach ($etapas as $k => $v): ?>
                        <option value="<?= $k ?>"><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-1 block">Previsão</label>
                    <input class="w-full bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 text-sm outline-none" type="date" name="previsao">
                </div>
                <div class="md:col-span-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-1 block">Descrição</label>
                    <textarea class="w-full bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-3 text-sm outline-none" name="descricao" rows="2"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-novo').classList.remove('active')" class="px-5 py-2.5 rounded-xl text-sm font-bold text-zinc-500 hover:text-zinc-900 dark:hover:text-white">Cancelar</button>
                <button type="submit" class="bg-zinc-900 dark:bg-white text-white dark:text-black px-6 py-2.5 rounded-xl text-sm font-black hover:scale-105 active:scale-95 transition-all">Criar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detalhe da Oportunidade -->
<div id="modal-detalhe" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('active')">
    <div class="modal-panel">
        <div id="detalhe-content" class="p-8">
            <p class="text-zinc-500 text-center py-12">Carregando...</p>
        </div>
    </div>
</div>

<script>
// Dados das oportunidades para o modal de detalhe
const oportunidadesData = <?= json_encode($oportunidades) ?>;
const raizUrl = '<?= rtrim(APP_URL, '/') ?>';

// Drag & Drop
function startDrag(e) {
    e.target.classList.add('dragging');
    e.dataTransfer.setData('text/plain', e.target.dataset.id);
    e.dataTransfer.effectAllowed = 'move';
}
function endDrag(e) { e.target.classList.remove('dragging'); }
function dropCard(e, novaEtapa) {
    e.preventDefault();
    e.currentTarget.classList.remove('drag-over');
    const id = e.dataTransfer.getData('text/plain');
    const card = document.querySelector(`[data-id="${id}"]`);
    if (!card) return;
    
    // Mover visualmente
    e.currentTarget.appendChild(card);
    
    // Atualizar contadores
    document.querySelectorAll('.kanban-col-body').forEach(col => {
        const count = col.querySelectorAll('.kanban-card').length;
        col.parentElement.querySelector('.kanban-col-header span:last-child').textContent = count;
    });
    
    // Salvar no servidor
    fetch(raizUrl + '/api/gerenciamento/oportunidade_atualizar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id, etapa: novaEtapa })
    }).then(r => r.json()).then(data => {
        if (!data.sucesso) alert('Erro ao atualizar: ' + (data.erro || 'Desconhecido'));
    }).catch(() => alert('Erro de conexão'));
}

// Modal de Detalhe
function openDetail(id) {
    const o = oportunidadesData.find(x => x.id === id);
    if (!o) return;
    
    const dados = o.dados_json ? JSON.parse(o.dados_json) : {};
    const wa = (dados.whatsapp || o.cliente_contato || '').replace(/\D/g, '');
    const propostaUrl = o.proposta_slug ? `${raizUrl}/p/${o.proposta_slug}` : null;
    const editarUrl = o.proposta_id ? `${raizUrl}/gerenciamento/proposta_editar.php?id=${o.proposta_id}` : null;
    
    const etapaCores = {novo:'bg-zinc-500',qualificado:'bg-blue-500',proposta:'bg-purple-500',negociacao:'bg-amber-500',ganha:'bg-emerald-500',perdida:'bg-red-500'};
    const etapaNomes = {novo:'Novo',qualificado:'Qualificado',proposta:'Proposta',negociacao:'Negociação',ganha:'Ganha',perdida:'Perdida'};
    
    document.getElementById('detalhe-content').innerHTML = `
        <div class="flex items-start justify-between mb-6">
            <div>
                <h2 class="text-xl font-black text-zinc-900 dark:text-white">${o.nome || 'Sem nome'}</h2>
                <p class="text-sm text-zinc-500 mt-1">${o.cliente_nome || 'Sem cliente vinculado'}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-black uppercase px-3 py-1 rounded-lg text-white ${etapaCores[o.etapa] || 'bg-zinc-500'}">${etapaNomes[o.etapa] || o.etapa}</span>
                <button onclick="document.getElementById('modal-detalhe').classList.remove('active')" class="w-8 h-8 rounded-xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-4">
                <p class="text-[10px] font-black uppercase text-zinc-400 mb-1">Valor</p>
                <p class="text-base font-black text-zinc-900 dark:text-white">${new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(o.proposta_valor || o.valor_estimado || 0)}</p>
            </div>
            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-4">
                <p class="text-[10px] font-black uppercase text-zinc-400 mb-1">Previsão</p>
                <p class="text-sm font-bold text-zinc-700 dark:text-zinc-300">${o.previsao ? new Date(o.previsao+'T12:00:00').toLocaleDateString('pt-BR') : '—'}</p>
            </div>
            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-4">
                <p class="text-[10px] font-black uppercase text-zinc-400 mb-1">Tipo</p>
                <p class="text-sm font-bold text-zinc-700 dark:text-zinc-300">${o.proposta_tipo || 'CRM'}</p>
            </div>
        </div>
        
        ${o.descricao ? `<div class="mb-6"><p class="text-[10px] font-black uppercase text-zinc-400 mb-2">Notas</p><p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">${o.descricao}</p></div>` : ''}
        
        <div class="border-t border-zinc-100 dark:border-zinc-800 pt-5 flex flex-wrap gap-2">
            ${wa ? `<a href="https://wa.me/${wa}" target="_blank" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500/10 text-emerald-600 text-xs font-bold hover:bg-emerald-500 hover:text-white transition-all"><i data-lucide="message-circle" class="w-4 h-4"></i> WhatsApp</a>` : ''}
            ${propostaUrl ? `<a href="${propostaUrl}" target="_blank" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-purple-500/10 text-purple-600 text-xs font-bold hover:bg-purple-500 hover:text-white transition-all"><i data-lucide="external-link" class="w-4 h-4"></i> Ver Proposta</a>` : ''}
            ${editarUrl ? `<a href="${editarUrl}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-500/10 text-blue-600 text-xs font-bold hover:bg-blue-500 hover:text-white transition-all"><i data-lucide="edit-2" class="w-4 h-4"></i> Editar Proposta</a>` : ''}
            <a href="${raizUrl}/gerenciamento/oportunidades.php?deletar=${o.id}" onclick="return confirm('Excluir esta oportunidade?')" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-500/10 text-red-500 text-xs font-bold hover:bg-red-500 hover:text-white transition-all ml-auto"><i data-lucide="trash-2" class="w-4 h-4"></i> Excluir</a>
        </div>
    `;
    
    document.getElementById('modal-detalhe').classList.add('active');
    if (window.lucide) lucide.createIcons();
}
</script>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
