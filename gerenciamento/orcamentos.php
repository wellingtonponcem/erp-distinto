<?php
/**
 * Painel Administrativo — Gestão de Orçamentos
 */
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();
$tituloPagina = 'Orçamentos';

$db = Database::get();

// Se a tabela ainda não tiver registros, tenta a migração
try {
    $countCheck = $db->query("SELECT COUNT(*) FROM orcamentos")->fetchColumn();
    if ($countCheck == 0 && file_exists(__DIR__ . '/../setup/migration_orcamentos.php')) {
        include_once __DIR__ . '/../setup/migration_orcamentos.php';
    }
} catch (Exception $e) {}

// Ações GET (Exclusão)
$msgSucesso = '';
$msgErro = '';
if (isset($_GET['deletar'])) {
    try {
        $stmtDel = $db->prepare("DELETE FROM orcamentos WHERE id = ?");
        $stmtDel->execute([$_GET['deletar']]);
        $msgSucesso = 'Orçamento excluído com sucesso.';
    } catch (Exception $e) {
        $msgErro = 'Erro ao excluir orçamento: ' . $e->getMessage();
    }
}

// Filtros de Busca e Status
$statusFiltro = $_GET['status'] ?? '';
$busca = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM orcamentos WHERE 1=1";
$params = [];

if ($statusFiltro) {
    $sql .= " AND status = ?";
    $params[] = $statusFiltro;
}

if ($busca) {
    $sql .= " AND (cliente_nome LIKE ? OR titulo LIKE ? OR slug LIKE ?)";
    $term = "%{$busca}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orcamentos = $stmt->fetchAll();

include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="main-sidebar-fixed transition-all duration-300 min-h-screen flex flex-col flex-1">
        <?php include __DIR__ . '/../includes/layout/top_nav.php'; ?>

        <div class="flex-1 px-container-padding py-8 max-w-[1600px] mx-auto w-full space-y-6">

            <!-- Mensagens de Alerta -->
            <?php if ($msgSucesso): ?>
                <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-bold">
                    <?= htmlspecialchars($msgSucesso) ?>
                </div>
            <?php endif; ?>
            <?php if ($msgErro): ?>
                <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm font-bold">
                    <?= htmlspecialchars($msgErro) ?>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-display-lg font-display-lg text-on-surface mb-1">Orçamentos Comercial</h1>
                    <p class="text-body-md font-body-md text-on-surface-variant">Gerencie e envie orçamentos simplificados e interativos para seus clientes</p>
                </div>

                <div class="flex items-center space-x-3">
                    <a href="<?= raizUrl('/gerenciamento/orcamento_novo.php') ?>" 
                       class="px-5 py-2.5 rounded-xl bg-primary text-on-primary font-bold text-xs uppercase tracking-wider hover:opacity-90 transition-opacity flex items-center space-x-2 shadow-lg">
                        <span class="material-symbols-outlined text-lg">add</span>
                        <span>Novo Orçamento</span>
                    </a>
                </div>
            </div>

            <!-- Filtros & Busca -->
            <div class="glass-card p-4 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
                <form method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                    <div class="relative min-w-[260px] flex-1">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
                        <input type="text" name="q" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar por cliente, título..." 
                               class="w-full pl-9 pr-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl text-sm text-on-surface focus:outline-none focus:border-primary">
                    </div>

                    <select name="status" onchange="this.form.submit()" class="bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-3 py-2 focus:outline-none focus:border-primary">
                        <option value="">Todos os Status</option>
                        <option value="pendente" <?= $statusFiltro === 'pendente' ? 'selected' : '' ?>>Pendentes</option>
                        <option value="aprovado" <?= $statusFiltro === 'aprovado' ? 'selected' : '' ?>>Aprovados</option>
                        <option value="recusado" <?= $statusFiltro === 'recusado' ? 'selected' : '' ?>>Recusados</option>
                    </select>

                    <button type="submit" class="px-4 py-2 bg-surface-container-highest hover:bg-surface-variant text-on-surface font-semibold text-xs rounded-xl">Filtrar</button>
                    <?php if ($busca || $statusFiltro): ?>
                        <a href="<?= raizUrl('/gerenciamento/orcamentos.php') ?>" class="text-xs text-outline hover:text-on-surface">Limpar</a>
                    <?php endif; ?>
                </form>

                <div class="text-xs text-on-surface-variant">
                    Total: <strong class="text-on-surface"><?= count($orcamentos) ?></strong> orçamentos encontrados
                </div>
            </div>

            <!-- Tabela / Lista de Orçamentos -->
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-outline-variant/20 bg-surface-container-low/50 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                                <th class="p-4">Orçamento / Título</th>
                                <th class="p-4">Cliente</th>
                                <th class="p-4">Tipo</th>
                                <th class="p-4">Validade</th>
                                <th class="p-4">Investimento Total</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 text-sm">
                            <?php if (empty($orcamentos)): ?>
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-on-surface-variant font-semibold">
                                        Nenhum orçamento encontrado. <a href="<?= raizUrl('/gerenciamento/orcamento_novo.php') ?>" class="text-primary font-bold hover:underline">Criar o primeiro orçamento</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orcamentos as $o): 
                                    $linkPublico = raizUrl('/o/' . $o['slug']);
                                    $vencido = (!empty($o['validade']) && $o['validade'] < date('Y-m-d'));
                                ?>
                                <tr class="hover:bg-surface-container/30 transition-colors">
                                    <td class="p-4">
                                        <div class="font-bold text-on-surface"><?= htmlspecialchars($o['titulo']) ?></div>
                                        <a href="<?= $linkPublico ?>" target="_blank" class="text-xs text-primary/80 hover:text-primary flex items-center gap-1 mt-0.5 font-mono">
                                            <span>/o/<?= htmlspecialchars($o['slug']) ?></span>
                                            <span class="material-symbols-outlined text-xs">open_in_new</span>
                                        </a>
                                    </td>

                                    <td class="p-4 font-semibold text-on-surface">
                                        <?= htmlspecialchars($o['cliente_nome']) ?>
                                    </td>

                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-lg bg-surface-container text-xs font-medium text-on-surface-variant capitalize">
                                            <?= htmlspecialchars(str_replace('_', ' ', $o['tipo'])) ?>
                                        </span>
                                    </td>

                                    <td class="p-4 text-xs font-medium text-on-surface-variant">
                                        <?php if (!empty($o['validade'])): ?>
                                            <span class="<?= $vencido ? 'text-error font-bold' : '' ?>">
                                                <?= date('d/m/Y', strtotime($o['validade'])) ?>
                                                <?= $vencido ? ' (Expirado)' : '' ?>
                                            </span>
                                        <?php else: ?>
                                            --
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-4 font-bold text-on-surface">
                                        R$ <?= number_format($o['valor_total'] ?? 0, 2, ',', '.') ?>
                                    </td>

                                    <td class="p-4">
                                        <?php if ($o['status'] === 'aprovado'): ?>
                                            <span class="px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-bold uppercase border border-emerald-500/30">Aprovado</span>
                                        <?php elseif ($o['status'] === 'recusado'): ?>
                                            <span class="px-2.5 py-1 rounded-full bg-rose-500/20 text-rose-400 text-xs font-bold uppercase border border-rose-500/30">Recusado</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-full bg-amber-500/20 text-amber-400 text-xs font-bold uppercase border border-amber-500/30">Pendente</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <!-- Ver Link Público -->
                                            <a href="<?= $linkPublico ?>" target="_blank" title="Visualizar Link Público" 
                                               class="p-2 rounded-lg bg-surface-container hover:bg-surface-container-highest text-on-surface transition-colors">
                                                <span class="material-symbols-outlined text-base">visibility</span>
                                            </a>

                                            <!-- Copiar Link -->
                                            <button onclick="copiarLinkPublico('<?= $linkPublico ?>', this)" title="Copiar Link"
                                                    class="p-2 rounded-lg bg-surface-container hover:bg-surface-container-highest text-on-surface transition-colors">
                                                <span class="material-symbols-outlined text-base">content_copy</span>
                                            </button>

                                            <!-- Gerar WhatsApp -->
                                            <button onclick="gerarMensagemWhats('<?= $o['id'] ?>')" title="Gerar Mensagem WhatsApp"
                                                    class="p-2 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-400 transition-colors">
                                                <span class="material-symbols-outlined text-base">chat</span>
                                            </button>

                                            <!-- Editar -->
                                            <a href="<?= raizUrl('/gerenciamento/orcamento_editar.php?id=' . $o['id']) ?>" title="Editar Orçamento"
                                               class="p-2 rounded-lg bg-surface-container hover:bg-surface-container-highest text-on-surface transition-colors">
                                                <span class="material-symbols-outlined text-base">edit</span>
                                            </a>

                                            <!-- Excluir -->
                                            <a href="<?= raizUrl('/gerenciamento/orcamentos.php?deletar=' . $o['id']) ?>" 
                                               onclick="return confirm('Tem certeza que deseja excluir este orçamento?')" title="Excluir"
                                               class="p-2 rounded-lg bg-error-container/20 hover:bg-error-container/40 text-error transition-colors">
                                                <span class="material-symbols-outlined text-base">delete</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Modal Mensagem WhatsApp -->
<div id="modal-whats" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md hidden p-4">
    <div class="glass-card w-full max-w-lg rounded-3xl p-6 space-y-4 border border-outline-variant/30 relative">
        <button onclick="fecharModalWhats()" class="absolute top-5 right-5 text-on-surface-variant hover:text-on-surface">
            <span class="material-symbols-outlined">close</span>
        </button>

        <h3 class="text-xl font-bold text-on-surface flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-400">chat</span>
            <span>Mensagem de Envio do Orçamento</span>
        </h3>

        <div id="loading-whats" class="py-8 text-center text-on-surface-variant font-semibold">
            Gerando mensagem com IA...
        </div>

        <div id="conteudo-whats" class="space-y-4 hidden">
            <textarea id="txt-msg-whats" rows="7" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl p-3 text-sm text-on-surface focus:outline-none focus:border-primary"></textarea>
            
            <div class="flex items-center justify-end space-x-3">
                <button onclick="copiarTextoWhats()" class="px-4 py-2 rounded-xl bg-surface-container-highest hover:bg-surface-variant text-on-surface font-semibold text-xs">
                    Copiar Texto
                </button>
                <a id="btn-abrir-whats" href="#" target="_blank" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs uppercase flex items-center space-x-2">
                    <span class="material-symbols-outlined text-base">send</span>
                    <span>Abrir no WhatsApp</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function copiarLinkPublico(url, btn) {
    navigator.clipboard.writeText(url).then(() => {
        const icon = btn.querySelector('.material-symbols-outlined');
        const orig = icon.textContent;
        icon.textContent = 'check';
        setTimeout(() => icon.textContent = orig, 2500);
    });
}

async function gerarMensagemWhats(id) {
    document.getElementById('modal-whats').classList.remove('hidden');
    document.getElementById('loading-whats').classList.remove('hidden');
    document.getElementById('conteudo-whats').classList.add('hidden');

    try {
        const resp = await fetch('<?= raizUrl('/api/orcamentos/mensagem-whatsapp.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const data = await resp.json();

        if (data.mensagem) {
            document.getElementById('txt-msg-whats').value = data.mensagem;
            document.getElementById('btn-abrir-whats').href = 'https://wa.me/?text=' + encodeURIComponent(data.mensagem);
            document.getElementById('loading-whats').classList.add('hidden');
            document.getElementById('conteudo-whats').classList.remove('hidden');
        } else {
            alert(data.erro || 'Falha ao gerar mensagem.');
            fecharModalWhats();
        }
    } catch (err) {
        alert('Erro ao conectar à API.');
        fecharModalWhats();
    }
}

function fecharModalWhats() {
    document.getElementById('modal-whats').classList.add('hidden');
}

function copiarTextoWhats() {
    const txt = document.getElementById('txt-msg-whats').value;
    navigator.clipboard.writeText(txt).then(() => alert('Mensagem copiada!'));
}
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
