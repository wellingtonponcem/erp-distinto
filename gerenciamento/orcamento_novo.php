<?php
/**
 * Painel Administrativo — Criar Novo Orçamento (Produtos & Álbuns Fotográficos)
 */
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();
$tituloPagina = 'Novo Orçamento';

$db = Database::get();

// Auto-seed de produtos de álbuns caso a tabela esteja vazia
try {
    $checkSeed = $db->query("SELECT COUNT(*) FROM produtos_albuns")->fetchColumn();
    if ($checkSeed == 0 && file_exists(__DIR__ . '/../setup/seed_produtos_albuns.php')) {
        include_once __DIR__ . '/../setup/seed_produtos_albuns.php';
    }
} catch (Exception $e) {}

// Buscar Produtos de Álbuns Cadastrados
$produtosTabela = [];
try {
    $produtosTabela = $db->query("SELECT * FROM produtos_albuns WHERE ativo=1 ORDER BY categoria ASC, investimento_cliente ASC")->fetchAll();
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
                    <h1 class="text-display-lg font-display-lg text-on-surface mb-1">Construtor de Orçamento de Álbuns</h1>
                    <p class="text-body-md font-body-md text-on-surface-variant">Selecione os produtos de álbuns e acabamentos fotografados cadastrados no catálogo</p>
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
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Categoria de Produtos</label>
                            <select id="categoria_filtro" onchange="renderizarProdutosPorCategoria()" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary font-bold">
                                <option value="15anos" selected>Álbuns 15 Anos</option>
                                <option value="wedding">Álbuns Casamento</option>
                                <option value="todos">Todos os Produtos</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Data de Validade</label>
                            <input type="date" id="validade" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Valor Base do Orçamento (R$)</label>
                            <input type="number" step="0.01" id="valor_total" value="1250.00" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-sm text-on-surface focus:outline-none focus:border-primary font-bold">
                        </div>
                    </div>
                </div>

                <!-- 2. Especificações Técnicas Gerais -->
                <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-6">
                    <div class="flex items-center space-x-3 border-b border-outline-variant/20 pb-4">
                        <div class="w-9 h-9 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center font-bold">
                            <span class="material-symbols-outlined text-lg">layers</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-on-surface">2. Especificações Técnicas Gerais da Linha</h2>
                            <p class="text-xs text-on-surface-variant">Formatos, encadernação e serviços inclusos no projeto</p>
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
                            <label class="block text-xs font-bold text-on-surface-variant mb-1">Retirada / Entrega</label>
                            <input type="text" id="spec_retirada" value="Presencial (Vitória/ES)" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-3 py-2 text-xs text-on-surface focus:outline-none focus:border-primary">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Serviços Premium Inclusos (Separados por vírgula)</label>
                        <input type="text" id="spec_servicos_inclusos" value="Diagramação Profissional Personalizada, Curadoria de Acabamentos Temáticos, Tratamento de Imagem para Impressão Premium, Garantia de Fidelidade de Cor e Durabilidade" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-4 py-3 text-xs text-on-surface focus:outline-none focus:border-primary">
                    </div>
                </div>

                <!-- 3. Seleção dos Produtos de Álbuns Fotográficos -->
                <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-6">
                    <div class="flex items-center justify-between border-b border-outline-variant/20 pb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold">
                                <span class="material-symbols-outlined text-lg">book</span>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-on-surface">3. Produtos & Coleções de Álbuns</h2>
                                <p class="text-xs text-on-surface-variant">Selecione quais produtos de álbuns estarão disponíveis para escolha no orçamento</p>
                            </div>
                        </div>
                    </div>

                    <div id="container-produtos" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Preenchido via JS com os produtos de álbuns -->
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
const produtosTabela = <?= json_encode($produtosTabela) ?>;

function renderizarProdutosPorCategoria() {
    const cat = document.getElementById('categoria_filtro').value;
    const container = document.getElementById('container-produtos');
    container.innerHTML = '';

    const filtrados = produtosTabela.filter(p => cat === 'todos' || p.categoria === cat);

    if (filtrados.length === 0) {
        container.innerHTML = '<div class="col-span-3 p-6 text-center text-xs text-on-surface-variant">Nenhum produto cadastrado nesta categoria.</div>';
        return;
    }

    filtrados.forEach(p => {
        const estojo = parseJsonSeguro(p.estojo_json) || {};
        const imagens = parseJsonSeguro(p.imagens_galeria_json) || {};
        const acabamentos = parseJsonSeguro(p.acabamentos_detalhados_json) || [];

        const fotoCapa = imagens.capa || estojo.imagem_referencia || 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=800&q=80';

        let acabamentosHtml = '';
        acabamentos.forEach(a => {
            acabamentosHtml += `
                <div class="flex items-center space-x-2 text-[11px] text-zinc-300">
                    ${a.imagem ? `<img src="${a.imagem}" class="w-5 h-5 rounded object-cover border border-white/20">` : `<span class="material-symbols-outlined text-xs text-primary">check_circle</span>`}
                    <span class="truncate"><strong>${a.item || ''}:</strong> ${a.texto || ''}</span>
                </div>
            `;
        });

        const html = `
            <div class="glass-card p-5 rounded-2xl border-2 border-primary/40 relative flex flex-col justify-between space-y-4">
                <div class="flex items-start justify-between">
                    <label class="flex items-center space-x-2.5 cursor-pointer">
                        <input type="checkbox" class="chk-produto w-4 h-4 rounded text-primary" value="${p.id}" checked onchange="compilarJsonPreview()">
                        <span class="font-bold text-sm text-on-surface">${p.nome}</span>
                    </label>
                    <span class="px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 font-bold text-[10px] uppercase">${p.categoria_original || 'Produto'}</span>
                </div>

                <div class="w-full h-36 rounded-xl overflow-hidden bg-zinc-900 relative">
                    <img src="${fotoCapa}" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=800&q=80'">
                </div>

                <p class="text-xs text-on-surface-variant leading-relaxed line-clamp-2">${p.descricao || ''}</p>

                <div class="space-y-1.5 bg-surface-container-low p-3 rounded-xl border border-outline-variant/10">
                    <span class="text-[10px] font-bold uppercase text-zinc-400 block mb-1">Acabamentos com Foto:</span>
                    ${acabamentosHtml || '<span class="text-xs text-zinc-500">Ficha técnica inclusa</span>'}
                </div>

                <div class="space-y-1 text-xs bg-surface-container-low p-3 rounded-xl border border-outline-variant/10">
                    <div class="flex justify-between font-bold text-on-surface">
                        <span>Investimento:</span>
                        <span class="text-primary">R$ ${parseFloat(p.investimento_cliente || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                    </div>
                    <div class="flex justify-between text-[11px] text-on-surface-variant">
                        <span>Lâmina Extra:</span>
                        <span>R$ ${parseFloat(p.valor_lamina_extra || 35).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                    </div>
                </div>
            </div>
        `;
        container.innerHTML += html;
    });

    compilarJsonPreview();
}

function parseJsonSeguro(str) {
    if (!str) return null;
    try { return typeof str === 'object' ? str : JSON.parse(str); } catch(e) { return null; }
}

function compilarJsonPreview() {
    const titulo = document.getElementById('titulo').value;
    const subtitulo = document.getElementById('subtitulo').value;

    const specTamanhoFechado = document.getElementById('spec_tamanho_fechado').value;
    const specTamanhoAberto = document.getElementById('spec_tamanho_aberto').value;
    const specAbertura = document.getElementById('spec_abertura').value;
    const specRetirada = document.getElementById('spec_retirada').value;
    const servicosInclusosStr = document.getElementById('spec_servicos_inclusos').value;

    const servicosInclusosArr = servicosInclusosStr.split(',').map(s => s.trim()).filter(s => s !== '');

    const chks = document.querySelectorAll('.chk-produto:checked');
    const colecoesCompiladas = [];

    chks.forEach(chk => {
        const prod = produtosTabela.find(p => p.id === chk.value);
        if (prod) {
            const acabArray = parseJsonSeguro(prod.acabamentos_detalhados_json) || [];
            const acabObj = {};
            acabArray.forEach(a => {
                const chave = a.chave || (a.item ? a.item.toLowerCase().replace(/\s+/g, '_') : 'detalhe');
                acabObj[chave] = a.texto;
            });

            colecoesCompiladas.push({
                id: prod.id,
                nome_comercial: prod.nome,
                categoria_original: prod.categoria_original || 'Coleção Premium',
                descricao: prod.descricao || '',
                acabamento_detalhado: acabObj,
                acabamentos_lista_fotos: acabArray,
                estojo: parseJsonSeguro(prod.estojo_json) || {},
                custo_base_fullcolor: parseFloat(prod.custo_base || 445),
                investimento_cliente: parseFloat(prod.investimento_cliente || 1250),
                valor_lamina_extra: parseFloat(prod.valor_lamina_extra || 35),
                imagens: parseJsonSeguro(prod.imagens_galeria_json) || {}
            });
        }
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
        galeria_acabamentos: [
            { item: 'Papel Linho Silk', descricao: 'Textura acetinada anti-digital.', imagem_exemplo: 'https://m.media-amazon.com/images/I/71YvE9-9VFL._AC_SL1500_.jpg' },
            { item: 'Abertura Panorâmica 180°', descricao: 'Abertura total 180° sem vinco central.', imagem_exemplo: 'https://www.ipsispro.com.br/fotolivro-panoramico-180' },
            { item: 'Corte Lateral Ouro', descricao: 'Bordas douradas metálicas reluzentes.', imagem_exemplo: 'https://www.instagram.com/p/DZiX91-sEon/' }
        ]
    };

    document.getElementById('dados_json_preview').value = JSON.stringify(jsonFinal, null, 2);

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
            alert('Orçamento de Álbuns gerado com sucesso!');
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

document.addEventListener('DOMContentLoaded', renderizarProdutosPorCategoria);
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
