<?php
/**
 * Painel Administrativo — Criar Novo Orçamento (Construtor Visual sem JSON)
 */
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();
$tituloPagina = 'Novo Orçamento';

$db = Database::get();

// Auto-seed de serviços caso a tabela esteja vazia
try {
    $checkSeed = $db->query("SELECT COUNT(*) FROM servicos WHERE categoria = '15anos'")->fetchColumn();
    if ($checkSeed == 0 && file_exists(__DIR__ . '/../setup/seed_servicos_albuns.php')) {
        include_once __DIR__ . '/../setup/seed_servicos_albuns.php';
    }
} catch (Exception $e) {}

// Buscar itens cadastrados na Tabela de Preços
$servicosTabela = [];
try {
    $servicosTabela = $db->query("SELECT * FROM servicos WHERE ativo=1 ORDER BY categoria ASC, nome ASC")->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="main-sidebar-fixed transition-all duration-300 min-h-screen flex flex-col flex-1">
        <?php include __DIR__ . '/../includes/layout/top_nav.php'; ?>

        <div class="flex-1 px-container-padding py-8 max-w-[1400px] mx-auto w-full space-y-6">

            <!-- Top bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-display-lg font-display-lg text-on-surface mb-1">Construtor de Orçamento</h1>
                    <p class="text-body-md font-body-md text-on-surface-variant">Selecione as coleções e acabamentos da Tabela de Preços com apenas alguns cliques</p>
                </div>

                <div class="flex items-center space-x-3">
                    <a href="<?= raizUrl('/gerenciamento/orcamentos.php') ?>" class="px-4 py-2.5 rounded-xl bg-surface-container-highest hover:bg-surface-variant text-on-surface font-semibold text-xs flex items-center space-x-2">
                        <span class="material-symbols-outlined text-base">arrow_back</span>
                        <span>Voltar</span>
                    </a>
                </div>
            </div>

            <!-- Formulário Construtor Visual -->
            <form id="form-novo-orcamento" onsubmit="salvarOrcamentoVisual(event)" class="space-y-8">
                
                <!-- 1. Dados Básicos do Orçamento -->
                <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-6">
                    <div class="flex items-center space-x-3 border-b border-outline-variant/20 pb-4">
                        <div class="w-9 h-9 rounded-xl bg-primary/20 text-primary flex items-center justify-center font-bold">
                            <span class="material-symbols-outlined text-lg">person</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-on-surface">1. Informações Básicas do Cliente e Projeto</h2>
                            <p class="text-xs text-on-surface-variant">Identificação do cliente e prazos de validade</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Nome do Cliente / Projeto *</label>
                            <input type="text" id="cliente_nome" required placeholder="Ex: Debutante Premium / Maria Silva" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Título do Orçamento *</label>
                            <input type="text" id="titulo" required value="Orçamento de Álbuns Premium — 15 Anos" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Subtítulo / Localidade</label>
                            <input type="text" id="subtitulo" value="15 Anos - Debutante Premium (Vitória/ES)" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Categoria da Tabela de Preços</label>
                            <select id="categoria_filtro" onchange="filtrarColecoesPorCategoria()" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary font-bold">
                                <option value="15anos" selected>Álbuns 15 Anos</option>
                                <option value="wedding">Álbuns Casamento</option>
                                <option value="marketing">Marketing & Design</option>
                                <option value="todos">Todas as Categorias</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Data de Validade</label>
                            <input type="date" id="validade" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Valor Total do Orçamento (R$)</label>
                            <input type="number" step="0.01" id="valor_total" value="1250.00" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary font-bold">
                        </div>
                    </div>
                </div>

                <!-- 2. Especificações Técnicas da Linha -->
                <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-6">
                    <div class="flex items-center space-x-3 border-b border-outline-variant/20 pb-4">
                        <div class="w-9 h-9 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center font-bold">
                            <span class="material-symbols-outlined text-lg">layers</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-on-surface">2. Especificações Técnicas da Linha</h2>
                            <p class="text-xs text-on-surface-variant">Configuração geral de formato, gramatura e serviços inclusos</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1">Tamanho Fechado</label>
                            <input type="text" id="spec_tamanho_fechado" value="30x30 cm" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-3 py-2 text-xs text-on-surface focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1">Tamanho Aberto</label>
                            <input type="text" id="spec_tamanho_aberto" value="30x60 cm" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-3 py-2 text-xs text-on-surface focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1">Encadernação / Abertura</label>
                            <input type="text" id="spec_abertura" value="Panorâmica 180° (Lâminas Rígidas de 800g)" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-3 py-2 text-xs text-on-surface focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1">Páginas Base / Retirada</label>
                            <input type="text" id="spec_retirada" value="Presencial (Vitória/ES)" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-3 py-2 text-xs text-on-surface focus:outline-none focus:border-primary">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Serviços Premium Inclusos (Separados por vírgula)</label>
                        <input type="text" id="spec_servicos_inclusos" value="Diagramação Profissional Personalizada, Curadoria de Acabamentos Temáticos, Tratamento de Imagem para Impressão Premium, Garantia de Fidelidade de Cor e Durabilidade" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-xs text-on-surface focus:outline-none focus:border-primary">
                    </div>
                </div>

                <!-- 3. Seleção de Coleções da Tabela de Preços -->
                <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-6">
                    <div class="flex items-center justify-between border-b border-outline-variant/20 pb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold">
                                <span class="material-symbols-outlined text-lg">crown</span>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-on-surface">3. Coleções do Orçamento (Tabela de Preços)</h2>
                                <p class="text-xs text-on-surface-variant">Marque as coleções que estarão disponíveis para o cliente escolher na página pública</p>
                            </div>
                        </div>

                        <a href="<?= raizUrl('/precificacao/servicos.php') ?>" target="_blank" class="text-xs text-primary font-bold hover:underline flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">open_in_new</span>
                            <span>Gerenciar Tabela de Preços</span>
                        </a>
                    </div>

                    <!-- Cards de Coleções Selecionáveis -->
                    <div id="container-colecoes" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Preenchido via JavaScript dinamicamente -->
                    </div>
                </div>

                <!-- 4. Galeria de Acabamentos em Destaque -->
                <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-6">
                    <div class="flex items-center space-x-3 border-b border-outline-variant/20 pb-4">
                        <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">
                            <span class="material-symbols-outlined text-lg">photo_library</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-on-surface">4. Galeria de Acabamentos em Destaque</h2>
                            <p class="text-xs text-on-surface-variant">Selecione quais acabamentos com foto serão exibidos na apresentação</p>
                        </div>
                    </div>

                    <div id="container-acabamentos" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Preenchido via JavaScript dinamicamente -->
                    </div>
                </div>

                <!-- Aba Avançada (Visualizar JSON Gerado) -->
                <details class="glass-card p-6 rounded-3xl border border-outline-variant/20">
                    <summary class="text-xs font-bold uppercase tracking-wider text-on-surface-variant cursor-pointer flex items-center justify-between">
                        <span>Modo Avançado (Visualizar / Ajustar JSON Compilado)</span>
                        <span class="material-symbols-outlined text-sm">expand_more</span>
                    </summary>
                    <div class="mt-4 space-y-2">
                        <textarea id="dados_json_preview" rows="12" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl p-4 text-xs font-mono text-on-surface focus:outline-none focus:border-primary leading-relaxed" readonly></textarea>
                    </div>
                </details>

                <!-- Botões de Ação -->
                <div class="flex items-center justify-end space-x-4 pt-4">
                    <a href="<?= raizUrl('/gerenciamento/orcamentos.php') ?>" class="px-6 py-3.5 rounded-xl bg-surface-container-highest hover:bg-surface-variant text-on-surface font-semibold text-xs">Cancelar</a>
                    <button type="submit" id="btn-salvar" class="px-8 py-3.5 rounded-xl bg-primary hover:opacity-90 text-on-primary font-extrabold text-xs uppercase tracking-wider shadow-xl flex items-center space-x-2">
                        <span class="material-symbols-outlined text-base">save</span>
                        <span>Gerar & Salvar Orçamento</span>
                    </button>
                </div>

            </form>

        </div>
    </main>
</div>

<script>
// Dados de serviços vindos do banco de dados
const todosServicosTabela = <?= json_encode($servicosTabela) ?>;

// Estado das seleções
let colecoesSelecionadas = [];
let acabamentosSelecionados = [];

function inicializarSeletores() {
    filtrarColecoesPorCategoria();
}

function filtrarColecoesPorCategoria() {
    const cat = document.getElementById('categoria_filtro').value;
    const containerCol = document.getElementById('container-colecoes');
    const containerAcab = document.getElementById('container-acabamentos');

    containerCol.innerHTML = '';
    containerAcab.innerHTML = '';

    const filtrados = todosServicosTabela.filter(s => cat === 'todos' || s.categoria === cat || s.categoria === '15anos');

    // Separar por coleções e acabamentos
    const colecoes = filtrados.filter(s => (s.tipo || 'colecao') === 'colecao' || s.tipo === 'plano');
    const acabamentos = filtrados.filter(s => (s.tipo || '') === 'acabamento' || s.tipo === 'servico');

    // Renderizar Coleções
    if (colecoes.length === 0) {
        containerCol.innerHTML = '<div class="col-span-3 p-6 text-center text-xs text-on-surface-variant">Nenhuma coleção cadastrada para esta categoria na Tabela de Preços.</div>';
    } else {
        colecoes.forEach((c, idx) => {
            const jaSel = true; // Selecionado por padrão
            const acabObj = parseJsonSeguro(c.acabamento_json) || { capa: 'Foto Total Nobre', fechamento: 'Ímã Invisível', papel: 'Fotográfico Silk', laminação: 'UV Proteção' };
            const estojoObj = parseJsonSeguro(c.estojo_json) || { tipo: 'Estojo Slim', descricao: 'Proteção Aveludada', imagem_referencia: '' };
            const imgObj = parseJsonSeguro(c.imagens_json) || {};

            const html = `
                <div class="glass-card p-5 rounded-2xl border-2 border-primary/30 relative flex flex-col justify-between space-y-4">
                    <div class="flex items-start justify-between">
                        <label class="flex items-center space-x-2.5 cursor-pointer">
                            <input type="checkbox" class="chk-colecao w-4 h-4 rounded text-primary" value="${c.id}" checked onchange="compilarJsonPreview()">
                            <span class="font-bold text-sm text-on-surface">${c.nome}</span>
                        </label>
                        <span class="px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 font-bold text-[10px] uppercase">${c.categoria_original || 'Coleção'}</span>
                    </div>

                    <p class="text-xs text-on-surface-variant leading-relaxed">${c.descricao || ''}</p>

                    <div class="space-y-1 text-xs bg-surface-container-low p-3 rounded-xl border border-outline-variant/10">
                        <div class="flex justify-between font-bold text-on-surface">
                            <span>Investimento:</span>
                            <span class="text-primary">R$ ${parseFloat(c.preco_venda || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                        </div>
                        <div class="flex justify-between text-[11px] text-on-surface-variant">
                            <span>Lâmina Extra:</span>
                            <span>R$ ${parseFloat(c.valor_lamina_extra || 35).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                        </div>
                    </div>
                </div>
            `;
            containerCol.innerHTML += html;
        });
    }

    // Renderizar Acabamentos
    if (acabamentos.length === 0) {
        // FallbackAcabamentos de 15 Anos
        const acabamentosDefault = [
            { id: 'acab_1', nome: 'Papel Linho Silk', descricao: 'Textura que imita o toque do linho, anti-digital.', imagem: 'https://m.media-amazon.com/images/I/71YvE9-9VFL._AC_SL1500_.jpg' },
            { id: 'acab_2', nome: 'Abertura Panorâmica 180°', descricao: 'Fotos de página dupla sem cortes.', imagem: 'https://www.ipsispro.com.br/fotolivro-panoramico-180' },
            { id: 'acab_3', nome: 'Corte Lateral Ouro', descricao: 'Acabamento metálico dourado nas bordas.', imagem: 'https://www.instagram.com/p/DZiX91-sEon/' }
        ];
        acabamentosDefault.forEach(a => {
            containerAcab.innerHTML += `
                <div class="glass-card p-4 rounded-2xl border border-outline-variant/20 flex items-start space-x-3">
                    <input type="checkbox" class="chk-acabamento w-4 h-4 rounded text-primary mt-1" value="${a.nome}" data-desc="${a.descricao}" data-img="${a.imagem}" checked onchange="compilarJsonPreview()">
                    <div class="text-xs">
                        <strong class="text-on-surface block mb-0.5">${a.nome}</strong>
                        <p class="text-on-surface-variant text-[11px]">${a.descricao}</p>
                    </div>
                </div>
            `;
        });
    } else {
        acabamentos.forEach(a => {
            const imgObj = parseJsonSeguro(a.imagens_json) || {};
            const img = imgObj.imagem_exemplo || imgObj.capa || '';
            containerAcab.innerHTML += `
                <div class="glass-card p-4 rounded-2xl border border-outline-variant/20 flex items-start space-x-3">
                    <input type="checkbox" class="chk-acabamento w-4 h-4 rounded text-primary mt-1" value="${a.nome}" data-desc="${a.descricao}" data-img="${img}" checked onchange="compilarJsonPreview()">
                    <div class="text-xs">
                        <strong class="text-on-surface block mb-0.5">${a.nome}</strong>
                        <p class="text-on-surface-variant text-[11px]">${a.descricao || ''}</p>
                    </div>
                </div>
            `;
        });
    }

    compilarJsonPreview();
}

function parseJsonSeguro(str) {
    if (!str) return null;
    try {
        return typeof str === 'object' ? str : JSON.parse(str);
    } catch(e) { return null; }
}

function compilarJsonPreview() {
    const titulo = document.getElementById('titulo').value;
    const subtitulo = document.getElementById('subtitulo').value;
    const cliente = document.getElementById('cliente_nome').value;

    const specTamanhoFechado = document.getElementById('spec_tamanho_fechado').value;
    const specTamanhoAberto = document.getElementById('spec_tamanho_aberto').value;
    const specAbertura = document.getElementById('spec_abertura').value;
    const specRetirada = document.getElementById('spec_retirada').value;
    const servicosInclusosStr = document.getElementById('spec_servicos_inclusos').value;

    const servicosInclusosArr = servicosInclusosStr.split(',').map(s => s.trim()).filter(s => s !== '');

    // Coletar coleções selecionadas
    const chksColecao = document.querySelectorAll('.chk-colecao:checked');
    const colecoesCompiladas = [];

    chksColecao.forEach(chk => {
        const itemSrv = todosServicosTabela.find(s => s.id === chk.value);
        if (itemSrv) {
            const acabObj = parseJsonSeguro(itemSrv.acabamento_json) || {
                capa: "Foto Total com Revestimento em Courvin Nobre",
                fechamento: "Sistema de Ímã Invisível",
                papel: "Fotográfico Fosco Silk (Anti-Digital e Antirreflexo)",
                laminação: "UV Proteção contra Luz e Umidade"
            };
            const estojoObj = parseJsonSeguro(itemSrv.estojo_json) || {
                tipo: "Case Slim Personalizado",
                descricao: "Estojo tipo luva em courvin aveludado.",
                imagem_referencia: ""
            };
            const imgObj = parseJsonSeguro(itemSrv.imagens_json) || {};

            colecoesCompiladas.push({
                id: itemSrv.id.replace('srv_', ''),
                nome_comercial: itemSrv.nome,
                categoria_original: itemSrv.categoria_original || "Coleção Premium",
                descricao: itemSrv.descricao || "",
                acabamento_detalhado: acabObj,
                estojo: estojoObj,
                custo_base_fullcolor: parseFloat(itemSrv.custo_producao || 445),
                investimento_cliente: parseFloat(itemSrv.preco_venda || 1250),
                valor_lamina_extra: parseFloat(itemSrv.valor_lamina_extra || 35),
                imagens: imgObj
            });
        }
    });

    // Se nenhuma coleção foi marcada no DOM, adiciona fallback
    if (colecoesCompiladas.length === 0) {
        todosServicosTabela.slice(0, 3).forEach(s => {
            colecoesCompiladas.push({
                id: s.id.replace('srv_', ''),
                nome_comercial: s.nome,
                categoria_original: s.categoria_original || 'Coleção',
                descricao: s.descricao || '',
                acabamento_detalhado: parseJsonSeguro(s.acabamento_json) || {},
                estojo: parseJsonSeguro(s.estojo_json) || {},
                custo_base_fullcolor: parseFloat(s.custo_producao || 445),
                investimento_cliente: parseFloat(s.preco_venda || 1250),
                valor_lamina_extra: parseFloat(s.valor_lamina_extra || 35),
                imagens: parseJsonSeguro(s.imagens_json) || {}
            });
        });
    }

    // Coletar acabamentos selecionados
    const chksAcab = document.querySelectorAll('.chk-acabamento:checked');
    const acabamentosCompilados = [];
    chksAcab.forEach(chk => {
        acabamentosCompilados.push({
            item: chk.value,
            descricao: chk.getAttribute('data-desc') || '',
            imagem_exemplo: chk.getAttribute('data-img') || ''
        });
    });

    const jsonFinal = {
        evento: subtitulo || titulo,
        localidade: "Vitória/ES",
        data_geracao: new Date().toISOString().split('T')[0],
        configuracao_geral: {
            tamanho_fechado: specTamanhoFechado,
            tamanho_aberto: specTamanhoAberto,
            abertura: specAbertura,
            paginas_base: 20,
            retirada: specRetirada,
            servicos_inclusos: servicosInclusosArr
        },
        colecao_albuns: colecoesCompiladas,
        galeria_acabamentos: acabamentosCompilados
    };

    document.getElementById('dados_json_preview').value = JSON.stringify(jsonFinal, null, 2);

    // Ajustar valor total do orçamento para o valor da primeira coleção
    if (colecoesCompiladas.length > 0) {
        document.getElementById('valor_total').value = colecoesCompiladas[0].investimento_cliente;
    }
}

async function salvarOrcamentoVisual(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-salvar');
    btn.disabled = true;
    btn.textContent = 'Gerando Orçamento...';

    compilarJsonPreview();
    const jsonStr = document.getElementById('dados_json_preview').value;

    const payload = {
        cliente_nome: document.getElementById('cliente_nome').value,
        titulo: document.getElementById('titulo').value,
        subtitulo: document.getElementById('subtitulo').value,
        tipo: document.getElementById('categoria_filtro').value,
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
            alert('Orçamento gerado e salvo com sucesso!');
            window.location.href = '<?= raizUrl('/gerenciamento/orcamentos.php') ?>';
        } else {
            alert(data.erro || 'Falha ao salvar orçamento.');
            btn.disabled = false;
            btn.textContent = 'Gerar & Salvar Orçamento';
        }
    } catch (err) {
        alert('Erro ao conectar ao servidor.');
        btn.disabled = false;
        btn.textContent = 'Gerar & Salvar Orçamento';
    }
}

document.addEventListener('DOMContentLoaded', inicializarSeletores);
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
