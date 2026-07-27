<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();
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
.kanban-wrapper { display:flex; gap:16px; overflow-x:auto; padding-bottom:20px; min-height:calc(100vh - 240px); }
.kanban-col { min-width:290px; max-width:320px; flex:1; display:flex; flex-direction:column; background:rgba(255,255,255,0.01) !important; border:1px solid rgba(255,255,255,0.04); border-radius:16px; overflow:hidden; }
.kanban-col-header { padding:14px 16px; border-bottom:1px solid rgba(255,255,255,0.04); display:flex; align-items:center; justify-content:space-between; background:rgba(255,255,255,0.02) !important; }
.kanban-col-body { flex:1; padding:12px; min-height:400px; display:flex; flex-direction:column; gap:12px; transition:all 0.2s; background:transparent !important; }
.kanban-col-body.drag-over { background:rgba(167,139,250,0.05) !important; outline:2px dashed rgba(167,139,250,0.3); }
.kanban-card { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); border-radius:12px; padding:14px 16px; cursor:grab; transition:all 0.2s; position:relative; }
.kanban-card:hover { transform:translateY(-2px); box-shadow:0 12px 24px rgba(0,0,0,0.3); border-color:rgba(167,139,250,0.3); background:rgba(255,255,255,0.05); }
.kanban-card:active { cursor:grabbing; opacity:0.7; }
.kanban-card.dragging { opacity:0.4; transform:scale(0.95); }

.pipeline-stats { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:24px; }
.stat-chip { padding:8px 14px; border-radius:10px; font-size:10px; font-weight:700; letter-spacing:0.05em; text-transform:uppercase; display:flex; align-items:center; gap:8px; border:1px solid rgba(255,255,255,0.04); background:rgba(255,255,255,0.02); }
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
        <div class="pipeline-stats mb-6 mt-4">
            <?php 
            $cores = ['novo'=>'bg-zinc-500','qualificado'=>'bg-blue-500','proposta'=>'bg-purple-500','negociacao'=>'bg-amber-500','ganha'=>'bg-emerald-500','perdida'=>'bg-red-500'];
            foreach ($etapas as $k => $label): ?>
            <div class="stat-chip">
                <span class="w-2 h-2 rounded-full <?= $cores[$k] ?>"></span>
                <?= $label ?>: <span class="text-on-surface ml-1"><?= count($colunas[$k]) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="stat-chip bg-primary text-on-primary border-primary/20 ml-auto font-data-tabular">
                Total: <?= formatarMoeda($totalValor) ?>
            </div>
        </div>

        <!-- Kanban Board -->
        <div class="kanban-wrapper">
            <?php foreach ($etapas as $etapaKey => $etapaLabel): ?>
            <div class="kanban-col">
                <div class="kanban-col-header">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full <?= $cores[$etapaKey] ?>"></span>
                        <span class="text-[10px] font-label-caps tracking-wider text-on-surface"><?= $etapaLabel ?></span>
                    </div>
                    <span class="text-[9px] font-label-caps px-2 py-0.5 rounded-full bg-surface-container-high border border-outline-variant/10 text-on-surface-variant font-data-tabular"><?= count($colunas[$etapaKey]) ?></span>
                </div>
                <div class="kanban-col-body" 
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
                        <div class="flex items-start justify-between gap-2 mb-1.5">
                            <h4 class="text-xs font-bold text-on-surface leading-tight truncate" title="<?= sanitizar($o['nome']) ?>"><?= sanitizar($o['nome']) ?></h4>
                            <?php if ($o['proposta_slug']): ?>
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-label-caps border bg-primary/10 text-primary border-primary/20 shrink-0">Proposta</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-[10px] text-on-surface-variant truncate"><?= sanitizar($o['cliente_nome'] ?: 'Sem cliente') ?></p>
                        
                        <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-outline-variant/10">
                            <span class="text-xs font-bold text-on-surface font-data-tabular"><?= formatarMoeda((float)($o['proposta_valor'] ?: $o['valor_estimado'])) ?></span>
                            <?php if ($waClean): ?>
                                <a href="https://wa.me/<?= $waClean ?>" target="_blank" onclick="event.stopPropagation()" 
                                   class="w-6 h-6 rounded-lg bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-all border border-emerald-500/20">
                                    <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php if ($o['previsao']): ?>
                            <p class="text-[9px] font-label-caps text-on-surface-variant mt-2 flex items-center gap-1">
                                <i data-lucide="calendar" class="w-3 h-3 text-on-surface-variant"></i> <?= date('d/m', strtotime($o['previsao'])) ?>
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
    <div class="modal w-full max-w-lg p-6 bg-surface-container-low border border-outline-variant/30 rounded-2xl shadow-2xl relative animate-in fade-in zoom-in-95 duration-200">
        <button onclick="document.getElementById('modal-novo').classList.remove('active')" class="absolute top-6 right-6 text-on-surface-variant hover:text-on-surface transition-colors cursor-pointer">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
        
        <h2 class="text-title-sm font-headline-md font-bold text-on-surface mb-6 flex items-center gap-2">
            <i data-lucide="plus-circle" class="w-5 h-5 text-on-surface-variant"></i> Nova Oportunidade
        </h2>
        
        <form method="post" action="<?= raizUrl('/gerenciamento/oportunidades.php') ?>">
            <div class="space-y-4 mb-6">
                <div>
                    <label class="label">Nome da Oportunidade *</label>
                    <input class="input w-full" type="text" name="nome" required placeholder="Ex: Casamento Igor & Gabriela">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Cliente</label>
                        <select class="select w-full" name="cliente_id">
                            <option value="">— Nenhum —</option>
                            <?php foreach ($clientes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= sanitizar($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Valor Estimado (R$)</label>
                        <input class="input w-full" type="number" step="0.01" name="valor_estimado" value="0">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Etapa Inicial</label>
                        <select class="select w-full" name="etapa">
                            <?php foreach ($etapas as $k => $v): ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Previsão de Fechamento</label>
                        <input class="input w-full" type="date" name="previsao">
                    </div>
                </div>

                <div>
                    <label class="label">Descrição / Observações</label>
                    <textarea class="textarea w-full" name="descricao" rows="2" placeholder="Notas sobre a negociação..."></textarea>
                </div>
            </div>
            
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('modal-novo').classList.remove('active')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary">Criar Oportunidade</button>
            </div>
        </form>
    <<!-- Modal Detalhe da Oportunidade -->
<div id="modal-detalhe" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('active')">
    <div class="modal w-full max-w-xl p-6 bg-surface-container-low border border-outline-variant/30 rounded-2xl shadow-2xl relative animate-in fade-in zoom-in-95 duration-200">
        <button onclick="document.getElementById('modal-detalhe').classList.remove('active')" class="absolute top-6 right-6 text-on-surface-variant hover:text-on-surface transition-colors cursor-pointer">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
        <div id="detalhe-content" class="space-y-6">
            <p class="text-on-surface-variant text-center py-12 italic">Carregando...</p>
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
    
    const etapaCores = {
        novo: 'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
        qualificado: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        proposta: 'bg-purple-500/10 text-purple-400 border-purple-500/20',
        negociacao: 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        ganha: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        perdida: 'bg-red-500/10 text-red-400 border-red-500/20'
    };
    const etapaNomes = {novo:'Novo',qualificado:'Qualificado',proposta:'Proposta',negociacao:'Negociação',ganha:'Ganha',perdida:'Perdida'};
    
    document.getElementById('detalhe-content').innerHTML = `
        <div class="flex items-start justify-between gap-6 mr-6">
            <div>
                <h2 class="text-base font-bold text-on-surface leading-tight">${o.nome || 'Sem nome'}</h2>
                <p class="text-xs text-on-surface-variant mt-1">${o.cliente_nome || 'Sem cliente vinculado'}</p>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[9px] font-label-caps border ${etapaCores[o.etapa] || 'bg-zinc-500/10 text-zinc-400 border-zinc-500/20'} shrink-0">${etapaNomes[o.etapa] || o.etapa}</span>
        </div>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <div class="glass-card p-4 rounded-xl border border-outline-variant/10">
                <p class="text-[9px] font-label-caps text-on-surface-variant mb-1">Valor Estimado</p>
                <p class="text-sm font-bold text-on-surface font-data-tabular">${new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(o.proposta_valor || o.valor_estimado || 0)}</p>
            </div>
            <div class="glass-card p-4 rounded-xl border border-outline-variant/10">
                <p class="text-[9px] font-label-caps text-on-surface-variant mb-1">Previsão Fechamento</p>
                <p class="text-sm font-bold text-on-surface">${o.previsao ? new Date(o.previsao+'T12:00:00').toLocaleDateString('pt-BR') : '—'}</p>
            </div>
            <div class="glass-card p-4 rounded-xl border border-outline-variant/10">
                <p class="text-[9px] font-label-caps text-on-surface-variant mb-1">Origem / Canal</p>
                <p class="text-sm font-bold text-on-surface">${o.proposta_tipo || 'CRM'}</p>
            </div>
        </div>
        
        ${o.descricao ? `
        <div>
            <p class="text-[9px] font-label-caps text-on-surface-variant mb-1.5">Descrição / Anotações</p>
            <p class="text-xs text-on-surface leading-relaxed whitespace-pre-wrap">${o.descricao}</p>
        </div>` : ''}
        
        <div class="border-t border-outline-variant/10 pt-4 flex flex-wrap gap-2 items-center">
            ${wa ? `
            <a href="https://wa.me/${wa}" target="_blank" class="bg-emerald-500/10 hover:bg-emerald-500 text-emerald-400 hover:text-white border border-emerald-500/20 font-bold px-4 py-2 rounded-lg text-xs transition-all active:scale-95 duration-150 flex items-center gap-1.5">
                <i data-lucide="message-circle" class="w-4 h-4"></i> WhatsApp
            </a>` : ''}
            ${propostaUrl ? `
            <a href="${propostaUrl}" target="_blank" class="bg-primary/10 hover:bg-primary text-primary hover:text-on-primary border border-primary/20 font-bold px-4 py-2 rounded-lg text-xs transition-all active:scale-95 duration-150 flex items-center gap-1.5">
                <i data-lucide="external-link" class="w-4 h-4"></i> Ver Proposta
            </a>` : ''}
            ${editarUrl ? `
            <a href="${editarUrl}" class="bg-blue-500/10 hover:bg-blue-500 text-blue-400 hover:text-white border border-blue-500/20 font-bold px-4 py-2 rounded-lg text-xs transition-all active:scale-95 duration-150 flex items-center gap-1.5">
                <i data-lucide="pencil" class="w-4 h-4"></i> Editar Proposta
            </a>` : ''}
            
            <a href="${raizUrl}/gerenciamento/oportunidades.php?deletar=${o.id}" onclick="return confirm('Excluir esta oportunidade?')" class="bg-error/10 hover:bg-error text-error hover:text-on-error border border-error/20 font-bold px-4 py-2 rounded-lg text-xs transition-all active:scale-95 duration-150 flex items-center gap-1.5 ml-auto">
                <i data-lucide="trash-2" class="w-4 h-4"></i> Excluir
            </a>
        </div>
    `;
    
    document.getElementById('modal-detalhe').classList.add('active');
    if (window.lucide) lucide.createIcons();
}
</script>
 
<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
