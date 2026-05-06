<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();

$tituloPagina = 'Propostas Web';
$db = Database::get();

// Buscar propostas
$stmt = $db->query("SELECT * FROM propostas ORDER BY created_at DESC");
$propostas = $stmt->fetchAll();

include __DIR__ . '/../includes/layout/head.php';
?>

<style>
    .folder-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 24px;
    }

    .folder-card {
        position: relative;
        background: #121212;
        border-radius: 24px;
        padding: 16px;
        aspect-ratio: 1/1.1;
        display: flex;
        flex-direction: column;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        border: 1px solid rgba(255, 255, 255, 0.03);
    }

    .dark .folder-card {
        background: #0f0f0f;
    }

    .folder-card:hover {
        transform: translateY(-8px);
        background: #1a1a1a;
        border-color: rgba(255, 255, 255, 0.1);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    }

    .folder-title {
        font-size: 13px;
        font-weight: 700;
        color: #efefef;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .folder-subtitle {
        font-size: 11px;
        color: #777;
        margin-bottom: 16px;
    }

    .folder-visual {
        flex: 1;
        position: relative;
        background: #1a1a1a;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .folder-sheets {
        position: absolute;
        top: 12px;
        width: 80%;
        height: 80%;
        background: #fff;
        border-radius: 8px;
        transform: translateY(4px) rotate(-2deg);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 1;
    }

    .folder-front {
        position: relative;
        width: 70%;
        height: 60%;
        background: #252525;
        border-radius: 10px;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        border: 1px solid rgba(255,255,255,0.05);
    }

    .add-folder {
        border: 2px dashed rgba(255,255,255,0.1);
        background: transparent;
    }

    .add-folder:hover {
        border-color: rgba(255,255,255,0.3);
        background: rgba(255,255,255,0.02);
    }

    .search-container {
        transition: all 0.3s ease;
        max-width: 0;
        overflow: hidden;
        opacity: 0;
    }

    .search-container.active {
        max-width: 300px;
        opacity: 1;
        margin-right: 12px;
    }

    /* Cores por status */
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }
</style>

<div id="app-wrapper" x-data="{ 
    showSearch: false, 
    searchQuery: '', 
    statusFilter: 'todos',
    propostas: <?= htmlspecialchars(json_encode($propostas)) ?>,
    get filteredPropostas() {
        let list = this.propostas.filter(p => {
            const matchesSearch = p.cliente_nome.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                                 p.titulo.toLowerCase().includes(this.searchQuery.toLowerCase());
            const matchesStatus = this.statusFilter === 'todos' || p.status === this.statusFilter;
            return matchesSearch && matchesStatus;
        });
        this.$nextTick(() => { lucide.createIcons(); });
        return list;
    }
}">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet !bg-[#0a0a0a] !text-white flex flex-col min-h-screen">
        <!-- Header -->
        <div class="flex items-center justify-between mb-10">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white">Propostas Web</h1>
                <p class="text-zinc-500 text-sm mt-1">Gerencie seus modelos e propostas em um só lugar.</p>
            </div>

            <div class="flex items-center gap-3">
                <!-- Busca -->
                <div class="flex items-center">
                    <div class="search-container" :class="{ 'active': showSearch }">
                        <input type="text" x-model="searchQuery" x-ref="searchInput" placeholder="Buscar proposta..." 
                               class="bg-zinc-900 border border-zinc-800 text-white text-xs rounded-full px-4 py-2 w-full outline-none focus:border-zinc-600">
                    </div>
                    <button @click="showSearch = !showSearch; if(showSearch) $nextTick(() => $refs.searchInput.focus())" 
                            class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <i data-lucide="search" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Filtro -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <i data-lucide="filter" class="w-5 h-5"></i>
                    </button>
                    <div x-show="open" @click.away="open = false" x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         class="absolute right-0 mt-2 w-48 bg-zinc-900 border border-zinc-800 rounded-xl shadow-2xl z-50 p-2">
                        <button @click="statusFilter = 'todos'; open = false" class="w-full text-left px-4 py-2 text-xs rounded-lg hover:bg-zinc-800" :class="statusFilter === 'todos' ? 'text-white font-bold' : 'text-zinc-400'">Todos</button>
                        <button @click="statusFilter = 'pendente'; open = false" class="w-full text-left px-4 py-2 text-xs rounded-lg hover:bg-zinc-800" :class="statusFilter === 'pendente' ? 'text-white font-bold' : 'text-zinc-400'">Pendentes</button>
                        <button @click="statusFilter = 'aceita'; open = false" class="w-full text-left px-4 py-2 text-xs rounded-lg hover:bg-zinc-800" :class="statusFilter === 'aceita' ? 'text-white font-bold' : 'text-zinc-400'">Aceitas</button>
                        <button @click="statusFilter = 'recusada'; open = false" class="w-full text-left px-4 py-2 text-xs rounded-lg hover:bg-zinc-800" :class="statusFilter === 'recusada' ? 'text-white font-bold' : 'text-zinc-400'">Recusadas</button>
                    </div>
                </div>

                <!-- Novo -->
                <a href="<?= raizUrl('/gerenciamento/proposta_nova.php') ?>" class="flex items-center gap-2 bg-white text-black px-4 py-2 rounded-full text-xs font-bold hover:bg-zinc-200 transition-all">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Nova Proposta
                </a>
            </div>
        </div>

        <!-- Grid -->
        <div class="folder-grid flex-1">
            <!-- Botão Adicionar (Card Estilizado) -->
            <a href="<?= raizUrl('/gerenciamento/proposta_nova.php') ?>" class="folder-card add-folder group">
                <div class="folder-title text-zinc-500 group-hover:text-zinc-300">Nova Proposta</div>
                <div class="folder-subtitle">Criar do zero</div>
                <div class="folder-visual !bg-transparent border-2 border-dashed border-zinc-800 group-hover:border-zinc-700">
                    <div class="w-12 h-12 rounded-full bg-zinc-900 flex items-center justify-center text-zinc-500 group-hover:text-white transition-all">
                        <i data-lucide="plus" class="w-6 h-6"></i>
                    </div>
                </div>
            </a>

            <!-- Propostas -->
            <template x-for="p in filteredPropostas" :key="p.id">
                <div class="folder-card" @click="window.open(`<?= APP_URL ?>/p/${p.slug}`, '_blank')">
                    <div class="folder-title" x-text="p.cliente_nome"></div>
                    <div class="folder-subtitle" x-text="p.titulo"></div>
                    
                    <div class="folder-visual">
                        <div class="folder-sheets"></div>
                        <div class="folder-front">
                            <i :data-lucide="p.tipo === 'Marketing Digital' ? 'megaphone' : (p.tipo === 'Filmmaker' ? 'video' : 'briefcase')" 
                               class="w-8 h-8 text-zinc-500"></i>
                        </div>
                        
                        <!-- Status Badge Overlay -->
                        <div class="absolute top-3 right-3 z-30">
                            <span class="w-3 h-3 rounded-full border-2 border-zinc-900" 
                                  :class="{
                                      'bg-blue-500': p.status === 'pendente',
                                      'bg-emerald-500': p.status === 'aceita',
                                      'bg-red-500': p.status === 'recusada',
                                      'bg-amber-500': p.status === 'rascunho'
                                  }"></span>
                        </div>
                    </div>

                    <!-- Footer Info -->
                    <div class="flex items-center justify-between mt-4">
                        <span class="text-[10px] font-bold text-zinc-600" x-text="new Date(p.created_at).toLocaleDateString('pt-BR')"></span>
                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                             <button @click.stop="copiarLink(p.slug)" class="p-1.5 hover:bg-zinc-800 rounded-md transition-colors text-zinc-500 hover:text-white">
                                <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                             </button>
                             <button @click.stop="deletarProposta(p.id)" class="p-1.5 hover:bg-zinc-800 rounded-md transition-colors text-zinc-500 hover:text-red-400">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                             </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State Search -->
        <template x-if="filteredPropostas.length === 0">
            <div class="text-center py-20 flex-1">
                <i data-lucide="search-x" class="w-12 h-12 mx-auto mb-4 text-zinc-800"></i>
                <p class="text-zinc-500 font-medium">Nenhuma proposta encontrada para sua busca.</p>
            </div>
        </template>

        <!-- Learn More Section (Footer of main content) -->
        <div class="mt-auto pt-20 pb-10">
            <div class="text-center mb-6">
                <span class="text-zinc-600 text-[10px] font-bold uppercase tracking-[0.2em]">Dicas & Atalhos</span>
            </div>
            <div class="max-w-xl mx-auto bg-zinc-900/50 border border-zinc-800/50 rounded-2xl p-6 flex items-center gap-6">
                <div class="w-14 h-14 rounded-xl bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-400">
                    <i data-lucide="graduation-cap" class="w-7 h-7"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-white mb-1">Potencialize suas vendas</h3>
                    <p class="text-xs text-zinc-500 leading-relaxed">
                        Use nossa IA para gerar textos persuasivos. Propostas personalizadas convertem até 40% mais.
                    </p>
                </div>
                <button class="text-zinc-400 hover:text-white transition-colors">
                    <i data-lucide="chevron-right" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Re-inicializar Lucide após o Alpine renderizar o template
    // Usamos um MutationObserver ou um simples timeout para o template
    setTimeout(() => { lucide.createIcons(); }, 100);
});

function copiarLink(slug) {
    const link = `<?= APP_URL ?>/p/${slug}`;
    navigator.clipboard.writeText(link).then(() => {
        alert('Link da proposta copiado!');
    });
}

function deletarProposta(id) {
    if (confirm('Deseja realmente excluir esta proposta?')) {
        // Implementar via fetch se necessário
        alert('Funcionalidade de exclusão em desenvolvimento.');
    }
}

// Observar mudanças no Alpine para re-renderizar ícones
document.addEventListener('alpine:initialized', () => {
    // Sempre que o grid for filtrado, precisamos rodar o lucide.createIcons()
});
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
