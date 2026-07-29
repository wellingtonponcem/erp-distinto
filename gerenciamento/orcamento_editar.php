<?php
/**
 * Painel Administrativo — Editar Orçamento
 */
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();
$tituloPagina = 'Editar Orçamento';

$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: ' . raizUrl('/gerenciamento/orcamentos.php'));
    exit;
}

$db = Database::get();
$stmt = $db->prepare("SELECT * FROM orcamentos WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$orcamento = $stmt->fetch();

if (!$orcamento) {
    die("Orçamento não encontrado.");
}

include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="main-sidebar-fixed transition-all duration-300 min-h-screen flex flex-col flex-1">
        <?php include __DIR__ . '/../includes/layout/top_nav.php'; ?>

        <div class="flex-1 px-container-padding py-8 max-w-[1200px] mx-auto w-full space-y-6">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-display-lg font-display-lg text-on-surface mb-1">Editar Orçamento</h1>
                    <p class="text-body-md font-body-md text-on-surface-variant">Alteração de dados e tabela de coleções do orçamento</p>
                </div>

                <div class="flex items-center space-x-3">
                    <a href="<?= raizUrl('/o/' . $orcamento['slug']) ?>" target="_blank" class="px-4 py-2 rounded-xl bg-purple-600/20 text-purple-300 border border-purple-500/30 hover:bg-purple-600/30 font-bold text-xs flex items-center space-x-2">
                        <span class="material-symbols-outlined text-base">visibility</span>
                        <span>Ver Link Público</span>
                    </a>
                    <a href="<?= raizUrl('/gerenciamento/orcamentos.php') ?>" class="px-4 py-2 rounded-xl bg-surface-container-highest hover:bg-surface-variant text-on-surface font-semibold text-xs flex items-center space-x-2">
                        <span class="material-symbols-outlined text-base">arrow_back</span>
                        <span>Voltar</span>
                    </a>
                </div>
            </div>

            <div class="glass-card p-8 rounded-3xl space-y-6">
                <form id="form-editar-orcamento" onsubmit="atualizarOrcamento(event)" class="space-y-6">
                    <input type="hidden" id="orcamento_id" value="<?= htmlspecialchars($orcamento['id']) ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Nome do Cliente *</label>
                            <input type="text" id="cliente_nome" required value="<?= htmlspecialchars($orcamento['cliente_nome']) ?>" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Título do Orçamento *</label>
                            <input type="text" id="titulo" required value="<?= htmlspecialchars($orcamento['titulo']) ?>" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Subtítulo / Localidade</label>
                            <input type="text" id="subtitulo" value="<?= htmlspecialchars($orcamento['subtitulo'] ?? '') ?>" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Tipo de Orçamento</label>
                            <select id="tipo" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary">
                                <option value="albuns_15anos" <?= $orcamento['tipo'] === 'albuns_15anos' ? 'selected' : '' ?>>Álbuns 15 Anos</option>
                                <option value="albuns_casamento" <?= $orcamento['tipo'] === 'albuns_casamento' ? 'selected' : '' ?>>Álbuns Casamento</option>
                                <option value="simplificado" <?= $orcamento['tipo'] === 'simplificado' ? 'selected' : '' ?>>Orçamento Simplificado</option>
                                <option value="personalizado" <?= $orcamento['tipo'] === 'personalizado' ? 'selected' : '' ?>>Personalizado</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Data de Validade</label>
                            <input type="date" id="validade" value="<?= htmlspecialchars($orcamento['validade'] ?? '') ?>" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Status do Orçamento</label>
                            <select id="status" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary font-bold">
                                <option value="pendente" <?= $orcamento['status'] === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                                <option value="aprovado" <?= $orcamento['status'] === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                                <option value="recusado" <?= $orcamento['status'] === 'recusado' ? 'selected' : '' ?>>Recusado</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Valor Base (R$)</label>
                            <input type="number" step="0.01" id="valor_total" value="<?= htmlspecialchars($orcamento['valor_total'] ?? 0) ?>" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Slug Público</label>
                            <input type="text" disabled value="/o/<?= htmlspecialchars($orcamento['slug']) ?>" class="w-full bg-surface-container-low/50 border border-outline-variant/20 rounded-xl px-4 py-3 text-sm text-on-surface-variant font-mono">
                        </div>
                    </div>

                    <!-- JSON Editor -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Estrutura de Dados do Orçamento (JSON)</label>
                        <textarea id="dados_json" rows="16" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl p-4 text-xs font-mono text-on-surface focus:outline-none focus:border-primary leading-relaxed"><?= htmlspecialchars($orcamento['dados_json']) ?></textarea>
                    </div>

                    <div class="flex items-center justify-end space-x-4 pt-4 border-t border-outline-variant/20">
                        <a href="<?= raizUrl('/gerenciamento/orcamentos.php') ?>" class="px-6 py-3 rounded-xl bg-surface-container-highest hover:bg-surface-variant text-on-surface font-semibold text-xs">Cancelar</a>
                        <button type="submit" id="btn-atualizar" class="px-8 py-3 rounded-xl bg-primary hover:opacity-90 text-on-primary font-bold text-xs uppercase tracking-wider shadow-lg flex items-center space-x-2">
                            <span class="material-symbols-outlined text-base">save</span>
                            <span>Salvar Alterações</span>
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </main>
</div>

<script>
async function atualizarOrcamento(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-atualizar');
    btn.disabled = true;
    btn.textContent = 'Salvando...';

    const jsonStr = document.getElementById('dados_json').value;
    try {
        JSON.parse(jsonStr);
    } catch (err) {
        alert('Erro no formato do JSON: ' + err.message);
        btn.disabled = false;
        btn.textContent = 'Salvar Alterações';
        return;
    }

    const payload = {
        id: document.getElementById('orcamento_id').value,
        cliente_nome: document.getElementById('cliente_nome').value,
        titulo: document.getElementById('titulo').value,
        subtitulo: document.getElementById('subtitulo').value,
        tipo: document.getElementById('tipo').value,
        validade: document.getElementById('validade').value,
        status: document.getElementById('status').value,
        valor_total: parseFloat(document.getElementById('valor_total').value) || 0,
        dados_json: jsonStr
    };

    try {
        const resp = await fetch('<?= raizUrl('/api/orcamentos/atualizar.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await resp.json();

        if (data.success) {
            alert('Orçamento atualizado com sucesso!');
            window.location.href = '<?= raizUrl('/gerenciamento/orcamentos.php') ?>';
        } else {
            alert(data.erro || 'Falha ao atualizar orçamento.');
            btn.disabled = false;
            btn.textContent = 'Salvar Alterações';
        }
    } catch (err) {
        alert('Erro ao comunicar com o servidor.');
        btn.disabled = false;
        btn.textContent = 'Salvar Alterações';
    }
}
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
