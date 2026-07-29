<?php
/**
 * Painel Administrativo — Criar Novo Orçamento
 */
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();
$tituloPagina = 'Novo Orçamento';

$db = Database::get();

// Template base de 15 Anos por padrão
$modeloDefault = file_exists(__DIR__ . '/../orcamento_albuns_15anos_v3.json') 
    ? file_get_contents(__DIR__ . '/../orcamento_albuns_15anos_v3.json') 
    : '{}';

include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="main-sidebar-fixed transition-all duration-300 min-h-screen flex flex-col flex-1">
        <?php include __DIR__ . '/../includes/layout/top_nav.php'; ?>

        <div class="flex-1 px-container-padding py-8 max-w-[1200px] mx-auto w-full space-y-6">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-display-lg font-display-lg text-on-surface mb-1">Novo Orçamento Commercial</h1>
                    <p class="text-body-md font-body-md text-on-surface-variant">Crie um orçamento e gere um link amigável para o seu cliente</p>
                </div>

                <a href="<?= raizUrl('/gerenciamento/orcamentos.php') ?>" class="px-4 py-2 rounded-xl bg-surface-container-highest hover:bg-surface-variant text-on-surface font-semibold text-xs flex items-center space-x-2">
                    <span class="material-symbols-outlined text-base">arrow_back</span>
                    <span>Voltar para Orçamentos</span>
                </a>
            </div>

            <div class="glass-card p-8 rounded-3xl space-y-6">
                <form id="form-novo-orcamento" onsubmit="salvarOrcamento(event)" class="space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Nome do Cliente *</label>
                            <input type="text" id="cliente_nome" required placeholder="Ex: Debutante Premium / Maria Silva" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Título do Orçamento *</label>
                            <input type="text" id="titulo" required placeholder="Ex: Orçamento de Álbuns Premium — 15 Anos" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Subtítulo / Localidade</label>
                            <input type="text" id="subtitulo" placeholder="Ex: 15 Anos - Debutante Premium (Vitória/ES)" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Tipo de Orçamento</label>
                            <select id="tipo" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary">
                                <option value="albuns_15anos">Álbuns 15 Anos</option>
                                <option value="albuns_casamento">Álbuns Casamento</option>
                                <option value="simplificado">Orçamento Simplificado</option>
                                <option value="personalizado">Personalizado</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Data de Validade</label>
                            <input type="date" id="validade" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Valor Inicial (R$)</label>
                            <input type="number" step="0.01" id="valor_total" value="1250.00" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary">
                        </div>
                    </div>

                    <!-- JSON Editor com Opção de Carregar Modelo -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Estrutura de Dados do Orçamento (JSON)</label>
                            <button type="button" onclick="restaurarModeloDefault()" class="text-xs text-primary font-bold hover:underline flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">refresh</span>
                                <span>Carregar Modelo de Álbuns 15 Anos</span>
                            </button>
                        </div>
                        <textarea id="dados_json" rows="16" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl p-4 text-xs font-mono text-on-surface focus:outline-none focus:border-primary leading-relaxed"><?= htmlspecialchars($modeloDefault) ?></textarea>
                        <p class="text-[11px] text-on-surface-variant">Este JSON contém as coleções, especificações técnicas e galeria de acabamentos exibidos ao cliente.</p>
                    </div>

                    <div class="flex items-center justify-end space-x-4 pt-4 border-t border-outline-variant/20">
                        <a href="<?= raizUrl('/gerenciamento/orcamentos.php') ?>" class="px-6 py-3 rounded-xl bg-surface-container-highest hover:bg-surface-variant text-on-surface font-semibold text-xs">Cancelar</a>
                        <button type="submit" id="btn-salvar" class="px-8 py-3 rounded-xl bg-primary hover:opacity-90 text-on-primary font-bold text-xs uppercase tracking-wider shadow-lg flex items-center space-x-2">
                            <span class="material-symbols-outlined text-base">save</span>
                            <span>Criar Orçamento</span>
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </main>
</div>

<script>
const modeloDefaultText = <?= json_encode($modeloDefault) ?>;

function restaurarModeloDefault() {
    document.getElementById('dados_json').value = modeloDefaultText;
}

async function salvarOrcamento(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-salvar');
    btn.disabled = true;
    btn.textContent = 'Gravando...';

    const jsonStr = document.getElementById('dados_json').value;
    try {
        JSON.parse(jsonStr); // Validação de sintaxe JSON
    } catch (err) {
        alert('Erro no formato do JSON: ' + err.message);
        btn.disabled = false;
        btn.textContent = 'Criar Orçamento';
        return;
    }

    const payload = {
        cliente_nome: document.getElementById('cliente_nome').value,
        titulo: document.getElementById('titulo').value,
        subtitulo: document.getElementById('subtitulo').value,
        tipo: document.getElementById('tipo').value,
        validade: document.getElementById('validade').value,
        valor_total: parseFloat(document.getElementById('valor_total').value) || 0,
        dados_json: jsonStr
    };

    try {
        const resp = await fetch('<?= raizUrl('/api/orcamentos/gerar.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await resp.json();

        if (data.success) {
            alert('Orçamento criado com sucesso!');
            window.location.href = '<?= raizUrl('/gerenciamento/orcamentos.php') ?>';
        } else {
            alert(data.erro || 'Falha ao criar orçamento.');
            btn.disabled = false;
            btn.textContent = 'Criar Orçamento';
        }
    } catch (err) {
        alert('Erro ao comunicar com o servidor.');
        btn.disabled = false;
        btn.textContent = 'Criar Orçamento';
    }
}
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
