<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();

$tituloPagina = 'Propostas Web';
$db = Database::get();

// Buscar pastas
$stmtPastas = $db->query("SELECT * FROM pastas_propostas ORDER BY nome ASC");
$pastas = $stmtPastas->fetchAll();

// Buscar todas as propostas
$stmtPropostas = $db->query("SELECT * FROM propostas ORDER BY created_at DESC");
$propostas = $stmtPropostas->fetchAll();

include __DIR__ . '/../includes/layout/head.php';
?>

<style>
    .folder-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 24px;
    }

    /* Estilo de Pasta (Categoria) */
    .item-folder {
        position: relative;
        background: #121212;
        border-radius: 20px;
        padding: 16px;
        aspect-ratio: 1/0.9;
        display: flex;
        flex-direction: column;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        border: 1px solid rgba(255, 255, 255, 0.03);
    }

    .item-folder:hover {
        transform: translateY(-5px);
        background: #1a1a1a;
        border-color: rgba(255, 255, 255, 0.1);
    }

    .folder-icon-wrapper {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Estilo de Documento (Proposta) */
    .item-doc {
        position: relative;
        background: #181818;
        border-radius: 16px;
        padding: 12px;
        aspect-ratio: 1/1.2;
        display: flex;
        flex-direction: column;
        transition: all 0.2s ease;
        cursor: pointer;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .item-doc:hover {
        background: #222;
        border-color: rgba(255, 255, 255, 0.15);
    }

    .doc-visual {
        flex: 1;
        background: #fff;
        border-radius: 8px;
        margin-bottom: 10px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .doc-visual i {
        color: #ddd;
    }

    .context-menu {
        position: fixed;
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        z-index: 1000;
        padding: 5px;
        min-width: 160px;
    }

    .context-menu button {
        width: 100%;
        text-align: left;
        padding: 8px 12px;
        font-size: 12px;
        color: #eee;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .context-menu button:hover {
        background: #333;
    }

    .drag-over {
        background: rgba(255, 255, 255, 0.1) !important;
        border: 2px dashed #555 !important;
    }

    .breadcrumb-item:not(:last-child)::after {
        content: '/';
        margin: 0 8px;
        color: #444;
    }
</style>

<div id="app-wrapper" x-data="propostasApp()">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet !bg-[#050505] !text-white flex flex-col min-h-screen" 
          @contextmenu.prevent="showContextMenu($event, 'root')">
        
        <!-- Header & Breadcrumbs -->
        <div class="mb-10">
            <div class="flex items-center gap-2 mb-4 text-xs font-bold uppercase tracking-widest text-zinc-600">
                <span class="cursor-pointer hover:text-white transition-colors" @click="currentFolder = null">Raiz</span>
                <template x-if="currentFolder">
                    <span class="breadcrumb-item cursor-default text-zinc-400" x-text="getFolderName(currentFolder)"></span>
                </template>
            </div>
            
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-white" x-text="currentFolder ? getFolderName(currentFolder) : 'Propostas Web'"></h1>
                    <p class="text-zinc-500 text-sm mt-1" x-text="currentFolder ? 'Documentos nesta categoria' : 'Categorias e propostas avulsas'"></p>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="showSearch = !showSearch; if(showSearch) $nextTick(() => $refs.searchInput.focus())" 
                            class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <i data-lucide="search" class="w-5 h-5"></i>
                    </button>
                    
                    <a href="<?= raizUrl('/gerenciamento/proposta_nova.php') ?>" class="flex items-center gap-2 bg-white text-black px-4 py-2 rounded-full text-xs font-bold hover:bg-zinc-200 transition-all">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Nova Proposta
                    </a>
                </div>
            </div>

            <!-- Search Field -->
            <div x-show="showSearch" x-transition class="mt-4">
                <input type="text" x-model="searchQuery" x-ref="searchInput" placeholder="Buscar em tudo..." 
                       class="bg-zinc-900 border border-zinc-800 text-white text-sm rounded-xl px-4 py-3 w-full outline-none focus:border-zinc-600">
            </div>
        </div>

        <!-- Grid Layout -->
        <div class="folder-grid flex-1">
            
            <!-- PASTAS (Categorias) - Apenas na raiz ou se não estiver filtrando pasta específica -->
            <template x-if="!currentFolder">
                <template x-for="f in pastas" :key="f.id">
                    <div class="item-folder group" 
                         @click="currentFolder = f.id"
                         @contextmenu.stop.prevent="showContextMenu($event, 'folder', f)"
                         @dragover.prevent="dragOver($event)"
                         @dragleave="dragLeave($event)"
                         @drop="dropOnFolder($event, f.id)">
                        
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-zinc-300" x-text="f.nome"></span>
                            <span class="text-[10px] text-zinc-600" x-text="countItemsInFolder(f.id) + ' itens'"></span>
                        </div>
                        
                        <div class="folder-icon-wrapper">
                            <i data-lucide="folder" class="w-16 h-16 text-zinc-800 group-hover:text-zinc-700 transition-colors"></i>
                        </div>
                    </div>
                </template>
            </template>

            <!-- DOCUMENTOS (Propostas) -->
            <template x-for="p in filteredItems" :key="p.id">
                <div class="item-doc group" 
                     draggable="true" 
                     @dragstart="dragStart($event, p)"
                     @click="window.open(`<?= APP_URL ?>/p/${p.slug}`, '_blank')"
                     @contextmenu.stop.prevent="showContextMenu($event, 'proposal', p)">
                    
                    <div class="doc-visual">
                        <i :data-lucide="p.tipo === 'marketing' ? 'megaphone' : (p.tipo === 'filmmaker' ? 'video' : 'file-text')" 
                           class="w-10 h-10 opacity-30"></i>
                        
                        <!-- Status Dot -->
                        <div class="absolute top-2 right-2">
                             <div class="w-2 h-2 rounded-full" 
                                  :class="{
                                      'bg-blue-500': p.status === 'pendente',
                                      'bg-emerald-500': p.status === 'aceita',
                                      'bg-red-500': p.status === 'recusada'
                                  }"></div>
                        </div>
                    </div>

                    <div class="px-1">
                        <div class="text-[11px] font-bold text-white truncate" x-text="p.cliente_nome"></div>
                        <div class="text-[9px] text-zinc-500 truncate" x-text="p.titulo"></div>
                    </div>

                    <div class="mt-auto flex items-center justify-between pt-2">
                        <span class="text-[9px] text-zinc-600" x-text="new Date(p.created_at).toLocaleDateString('pt-BR')"></span>
                        <i data-lucide="more-horizontal" class="w-3 h-3 text-zinc-700"></i>
                    </div>
                </div>
            </template>

            <!-- Botão de Criação de Pasta na Raiz -->
            <template x-if="!currentFolder && pastas.length === 0">
                <div class="item-folder border-dashed border-zinc-800 bg-transparent flex items-center justify-center cursor-pointer hover:border-zinc-600 transition-colors"
                     @click="criarPasta()">
                    <div class="text-center">
                        <i data-lucide="folder-plus" class="w-8 h-8 mx-auto mb-2 text-zinc-800"></i>
                        <p class="text-[10px] font-bold text-zinc-700">Criar Primeira Pasta</p>
                    </div>
                </div>
            </template>

        </div>

        <!-- Empty States -->
        <template x-if="filteredItems.length === 0 && (currentFolder || searchQuery)">
            <div class="text-center py-20 flex-1">
                <i data-lucide="file-x" class="w-12 h-12 mx-auto mb-4 text-zinc-900"></i>
                <p class="text-zinc-600 font-medium">Nenhum item nesta localização.</p>
            </div>
        </template>

        <!-- FOOTER: Mantenha o ambiente organizado -->
        <div class="mt-auto pt-20 pb-10">
            <div class="text-center mb-6">
                <span class="text-zinc-800 text-[10px] font-bold uppercase tracking-[0.3em]">Gestão de Ativos</span>
            </div>
            <div class="max-w-xl mx-auto bg-zinc-900/30 border border-zinc-800/30 rounded-2xl p-6 flex items-center gap-6">
                <div class="w-14 h-14 rounded-xl bg-zinc-900 flex items-center justify-center text-zinc-500">
                    <i data-lucide="archive" class="w-7 h-7"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-white mb-1">Mantenha o ambiente organizado</h3>
                    <p class="text-xs text-zinc-500 leading-relaxed">
                        Coloque cada proposta em sua pasta correspondente. Arraste e solte arquivos para mover entre categorias.
                    </p>
                </div>
                <i data-lucide="check-circle-2" class="w-5 h-5 text-zinc-700"></i>
            </div>
        </div>

        <!-- Custom Context Menu -->
        <div x-show="contextMenu.show" 
             x-cloak
             class="context-menu"
             :style="`top: ${contextMenu.y}px; left: ${contextMenu.x}px;`"
             @click.away="contextMenu.show = false">
            
            <!-- Opções Root -->
            <template x-if="contextMenu.type === 'root'">
                <div>
                    <button @click="criarPasta()">
                        <i data-lucide="folder-plus" class="w-4 h-4"></i> Criar Nova Pasta
                    </button>
                    <button @click="location.href = 'proposta_nova.php'">
                        <i data-lucide="file-plus" class="w-4 h-4"></i> Nova Proposta
                    </button>
                </div>
            </template>

            <!-- Opções Folder -->
            <template x-if="contextMenu.type === 'folder'">
                <div>
                    <button @click="currentFolder = contextMenu.item.id; contextMenu.show = false">
                        <i data-lucide="folder-open" class="w-4 h-4"></i> Abrir Pasta
                    </button>
                    <button @click="renomearPasta(contextMenu.item)" class="text-zinc-400">
                        <i data-lucide="edit-3" class="w-4 h-4"></i> Renomear
                    </button>
                    <div class="h-px bg-zinc-800 my-1"></div>
                    <button @click="deletarPasta(contextMenu.item.id)" class="text-red-400 hover:bg-red-900/20">
                        <i data-lucide="trash-2" class="w-4 h-4"></i> Excluir Pasta
                    </button>
                </div>
            </template>

            <!-- Opções Proposal -->
            <template x-if="contextMenu.type === 'proposal'">
                <div>
                    <button @click="window.open(`<?= APP_URL ?>/p/${contextMenu.item.slug}`, '_blank'); contextMenu.show = false">
                        <i data-lucide="external-link" class="w-4 h-4"></i> Ver Proposta
                    </button>
                    <button @click="copiarLink(contextMenu.item.slug); contextMenu.show = false">
                        <i data-lucide="copy" class="w-4 h-4"></i> Copiar Link
                    </button>
                    <div class="relative group/submenu">
                        <button class="justify-between">
                            <span class="flex items-center gap-2"><i data-lucide="folder-input" class="w-4 h-4"></i> Mover para...</span>
                            <i data-lucide="chevron-right" class="w-3 h-3"></i>
                        </button>
                        <div class="absolute left-full top-0 ml-1 w-48 bg-zinc-900 border border-zinc-800 rounded-lg shadow-xl p-1 hidden group-hover/submenu:block">
                            <button @click="moverPara(contextMenu.item.id, null)">Raiz (Nenhuma)</button>
                            <template x-for="f in pastas">
                                <button @click="moverPara(contextMenu.item.id, f.id)" x-text="f.nome"></button>
                            </template>
                        </div>
                    </div>
                    <div class="h-px bg-zinc-800 my-1"></div>
                    <button @click="deletarProposta(contextMenu.item.id)" class="text-red-400 hover:bg-red-900/20">
                        <i data-lucide="trash-2" class="w-4 h-4"></i> Excluir
                    </button>
                </div>
            </template>
        </div>

        <!-- Modal de Confirmação de Exclusão -->
        <template x-if="deleteModal.show">
            <div class="fixed inset-0 z-[2000] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
                 @click.self="deleteModal.show = false">
                <div class="bg-[#121212] border border-zinc-800 rounded-2xl p-8 max-w-sm w-full shadow-2xl"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">
                    
                    <div class="w-16 h-16 bg-red-500/10 rounded-full flex items-center justify-center mb-6 mx-auto">
                        <svg class="text-red-500 w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    
                    <h3 class="text-xl font-bold text-white text-center mb-2">Confirmar Exclusão</h3>
                    <p class="text-zinc-400 text-center text-sm mb-8" x-text="deleteModal.message"></p>
                    
                    <div class="flex gap-3">
                        <button @click="deleteModal.show = false" 
                                class="flex-1 px-4 py-3 rounded-xl bg-zinc-800 text-white font-semibold hover:bg-zinc-700 transition-colors">
                            Cancelar
                        </button>
                        <button @click="confirmarExclusao()" 
                                class="flex-1 px-4 py-3 rounded-xl bg-red-600 text-white font-semibold hover:bg-red-500 transition-colors">
                            Excluir
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </main>
</div>

<script>
function propostasApp() {
    return {
        currentFolder: null,
        showSearch: false,
        searchQuery: '',
        pastas: <?= json_encode($pastas) ?>,
        propostas: <?= json_encode($propostas) ?>,
        contextMenu: { show: false, x: 0, y: 0, type: 'root', item: null },
        deleteModal: { show: false, id: null, type: '', message: '' },
        draggedItem: null,

        get filteredItems() {
            let list = this.propostas;
            
            // Se estiver buscando, ignora pastas e mostra tudo que combina
            if (this.searchQuery) {
                list = list.filter(p => 
                    p.cliente_nome.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                    p.titulo.toLowerCase().includes(this.searchQuery.toLowerCase())
                );
            } else {
                // Filtra pelo folder atual
                list = list.filter(p => p.pasta_id === this.currentFolder);
            }

            this.$nextTick(() => lucide.createIcons());
            return list;
        },

        getFolderName(id) {
            const folder = this.pastas.find(f => f.id === id);
            return folder ? folder.nome : 'Pasta';
        },

        countItemsInFolder(id) {
            return this.propostas.filter(p => p.pasta_id === id).length;
        },

        showContextMenu(e, type, item = null) {
            this.contextMenu = {
                show: true,
                x: e.clientX,
                y: e.clientY,
                type: type,
                item: item
            };
            this.$nextTick(() => lucide.createIcons());
        },

        // Drag & Drop
        dragStart(e, item) {
            this.draggedItem = item;
            e.dataTransfer.setData('text/plain', item.id);
        },
        dragOver(e) { e.currentTarget.classList.add('drag-over'); },
        dragLeave(e) { e.currentTarget.classList.remove('drag-over'); },
        dropOnFolder(e, folderId) {
            e.currentTarget.classList.remove('drag-over');
            if (this.draggedItem) {
                this.moverPara(this.draggedItem.id, folderId);
            }
        },

        // Ações de API (Mockadas por enquanto para resposta rápida na UI)
        moverPara(propostaId, pastaId) {
            // Atualiza localmente
            const p = this.propostas.find(x => x.id === propostaId);
            if (p) p.pasta_id = pastaId;
            this.contextMenu.show = false;

            // Envia para o servidor
            fetch('../api/propostas/organizar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'move', proposta_id: propostaId, pasta_id: pastaId })
            });
        },

        criarPasta() {
            const nome = prompt('Nome da nova pasta:');
            if (nome) {
                const id = crypto.randomUUID();
                const novaPasta = { id, nome, created_at: new Date().toISOString() };
                this.pastas.push(novaPasta);
                this.contextMenu.show = false;

                fetch('../api/propostas/organizar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'create_folder', id, nome })
                });
            }
        },

        renomearPasta(folder) {
            const novoNome = prompt('Novo nome da pasta:', folder.nome);
            if (novoNome && novoNome !== folder.nome) {
                folder.nome = novoNome;
                this.contextMenu.show = false;

                fetch('../api/propostas/organizar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'rename_folder', id: folder.id, nome: novoNome })
                });
            }
        },

        deletarPasta(id) {
            this.deleteModal = {
                show: true,
                id: id,
                type: 'pasta',
                message: 'Excluir esta pasta? As propostas dentro dela voltarão para a raiz.'
            };
            this.contextMenu.show = false;
        },

        deletarProposta(id) {
            this.deleteModal = {
                show: true,
                id: id,
                type: 'proposta',
                message: 'Deseja realmente excluir esta proposta permanentemente?'
            };
            this.contextMenu.show = false;
        },

        confirmarExclusao() {
            const { id, type } = this.deleteModal;
            if (type === 'proposta') {
                this.propostas = this.propostas.filter(p => p.id !== id);
                fetch('../api/propostas/organizar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_proposal', id: id })
                });
            } else if (type === 'pasta') {
                this.pastas = this.pastas.filter(f => f.id !== id);
                this.propostas.forEach(p => { if(p.pasta_id === id) p.pasta_id = null; });
                if(this.currentFolder === id) this.currentFolder = null;
                fetch('../api/propostas/organizar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_folder', id: id })
                });
            }
            this.deleteModal.show = false;
        },

        copiarLink(slug) {
            const link = `<?= APP_URL ?>/p/${slug}`;
            navigator.clipboard.writeText(link).then(() => alert('Link copiado!'));
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
});
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

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
        fetch('../api/propostas/organizar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_proposal', id: id })
        }).then(res => {
            if (res.ok) {
                location.reload();
            }
        });
    }
}

// Observar mudanças no Alpine para re-renderizar ícones
document.addEventListener('alpine:initialized', () => {
    // Sempre que o grid for filtrado, precisamos rodar o lucide.createIcons()
});
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
