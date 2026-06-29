<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirDistinto();

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

                    <a :href="'<?= raizUrl('/gerenciamento/proposta_nova.php') ?>' + (currentFolder ? '?folder=' + currentFolder : '')"
                       @click.prevent="if(window.innerWidth > 1024) { showModalNova = true; novaUrl = 'proposta_nova.php?layout=modal' + (currentFolder ? '&folder=' + currentFolder : '') } else { window.location.href = $el.href }"
                       class="flex items-center gap-2 bg-white text-black px-4 py-2 rounded-full text-xs font-bold hover:bg-zinc-200 transition-all">
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

            <!-- PASTAS (Categorias) - Apenas na raiz ou se nÃ£o estiver filtrando pasta especÃ­fica -->
            <template x-if="!currentFolder">
                <template x-for="f in pastas" :key="f.id">
                    <div class="item-folder group transition-all duration-500"
                         :class="{
                            'opacity-40 grayscale': countItemsInFolder(f.id) === 0,
                            'border-white/10 bg-zinc-900/50': countItemsInFolder(f.id) > 5
                         }"
                         @click="currentFolder = f.id"
                         @contextmenu.stop.prevent="showContextMenu($event, 'folder', f)"
                         @dragover.prevent="dragOver($event)"
                         @dragleave="dragLeave($event)"
                         @drop="dropOnFolder($event, f.id)">

                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold transition-colors"
                                  :class="countItemsInFolder(f.id) === 0 ? 'text-zinc-600' : 'text-zinc-300'"
                                  x-text="f.nome"></span>
                            <span class="text-[9px] font-black tracking-widest uppercase"
                                  :class="countItemsInFolder(f.id) === 0 ? 'text-zinc-700' : 'text-zinc-500'"
                                  x-text="countItemsInFolder(f.id) + ' ITENS'"></span>
                        </div>

                        <div class="folder-icon-wrapper relative">
                            <!-- Ãcone DinÃ¢mico -->
                            <i :data-lucide="countItemsInFolder(f.id) === 0 ? 'folder' : (countItemsInFolder(f.id) > 5 ? 'folders' : 'folder-open')"
                               class="w-16 h-16 transition-all duration-500"
                               :class="{
                                   'text-zinc-900': countItemsInFolder(f.id) === 0,
                                   'text-zinc-700 group-hover:text-zinc-500': countItemsInFolder(f.id) > 0 && countItemsInFolder(f.id) <= 5,
                                   'text-white scale-110 drop-shadow-[0_0_15px_rgba(255,255,255,0.1)]': countItemsInFolder(f.id) > 5
                               }"></i>

                            <!-- Indicador de Volume p/ pastas cheias -->
                            <template x-if="countItemsInFolder(f.id) > 5">
                                <div class="absolute -top-2 -right-2 w-5 h-5 bg-white text-black text-[10px] font-black rounded-full flex items-center justify-center shadow-lg border-2 border-[#050505] animate-bounce">
                                    <i data-lucide="zap" class="w-2.5 h-2.5"></i>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </template>

            <!-- DOCUMENTOS (Propostas) -->
            <template x-for="p in filteredItems" :key="p.id">
                <div class="item-doc group"
                     draggable="true"
                     @dragstart="dragStart($event, p)"
                     @click.stop="abrirResumo(p)"
                     @contextmenu.stop.prevent="showContextMenu($event, 'proposal', p)">
                    <div class="doc-visual">
                        <i :data-lucide="p.tipo === 'marketing' ? 'megaphone' : (p.tipo === 'filmmaker' ? 'video' : 'file-text')"
                           class="w-10 h-10 opacity-30"></i>

                        <!-- Pipeline Progress Bar -->
                        <div class="absolute bottom-2 left-2 right-2 flex flex-col gap-1.5">
                            <div class="flex items-center justify-between px-1">
                                <span class="text-[8px] font-black uppercase tracking-widest text-zinc-500"
                                      x-text="p.status === 'rascunho' ? 'Rascunho' : (p.status === 'pendente' ? 'Aguardando' : (p.status === 'aceita' ? 'Fechado' : 'Recusado'))"></span>
                                <span class="text-[8px] font-bold text-zinc-600"
                                      x-text="p.status === 'rascunho' ? '10%' : (p.status === 'pendente' ? '50%' : '100%')"></span>
                            </div>
                            <div class="h-1 w-full bg-zinc-800 rounded-full overflow-hidden flex">
                                <div class="h-full transition-all duration-1000 ease-out"
                                     :style="`width: ${p.status === 'rascunho' ? '10%' : (p.status === 'pendente' ? '50%' : '100%')};`"
                                     :class="{
                                         'bg-zinc-600': p.status === 'rascunho',
                                         'bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.5)]': p.status === 'pendente',
                                         'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]': p.status === 'aceita',
                                         'bg-red-500': p.status === 'recusada'
                                     }"></div>
                            </div>
                        </div>
                    </div>

                    <div class="px-1 mt-2">
                        <div class="text-[11px] font-bold text-white truncate" x-text="p.cliente_nome"></div>
                        <div class="text-[9px] text-zinc-500 truncate" x-text="p.titulo"></div>
                    </div>

                    <div class="mt-auto flex items-center justify-between pt-2">
                        <span class="text-[9px] text-zinc-600" x-text="new Date(p.created_at).toLocaleDateString('pt-BR')"></span>
                        <i data-lucide="more-horizontal" class="w-3 h-3 text-zinc-700"></i>
                    </div>
                </div>
            </template>

            <!-- BotÃ£o de CriaÃ§Ã£o de Pasta na Raiz -->
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
                <p class="text-zinc-600 font-medium">Nenhum item nesta localizaÃ§Ã£o.</p>
            </div>
        </template>

        <!-- FOOTER: Mantenha o ambiente organizado -->
        <div class="mt-auto pt-20 pb-10">
            <div class="text-center mb-6">
                <span class="text-zinc-800 text-[10px] font-bold uppercase tracking-[0.3em]">GestÃ£o de Ativos</span>
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

            <!-- OpÃ§Ãµes Root -->
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

            <!-- OpÃ§Ãµes Folder -->
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

            <!-- OpÃ§Ãµes Proposal -->
            <template x-if="contextMenu.type === 'proposal'">
                <div>
                    <button @click="window.open(`<?= APP_URL ?>/p/${contextMenu.item.slug}`, '_blank'); contextMenu.show = false">
                        <i data-lucide="external-link" class="w-4 h-4"></i> Ver Proposta
                    </button>
                    <button @click="copiarLink(contextMenu.item.slug); contextMenu.show = false">
                        <i data-lucide="copy" class="w-4 h-4"></i> Copiar Link
                    </button>
                    <button @click="enviarWhatsApp(contextMenu.item)" :disabled="whatsappLoading" class="text-green-400 hover:bg-green-900/20 disabled:opacity-50">
                        <template x-if="!whatsappLoading"><i data-lucide="message-circle" class="w-4 h-4"></i></template>
                        <template x-if="whatsappLoading"><svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg></template>
                        <span x-text="whatsappLoading ? 'Gerando mensagem...' : 'Enviar via WhatsApp'"></span>
                    </button>
                    <button @click="if(window.innerWidth > 1024) { showModalEditar = true; editUrl = 'proposta_editar.php?id=' + contextMenu.item.id + '&layout=modal&t=' + Date.now(); contextMenu.show = false } else { location.href = 'proposta_editar.php?id=' + contextMenu.item.id }">
                        <i data-lucide="edit-2" class="w-4 h-4"></i> Editar Dados
                    </button>
                    <button @click="gerarContrato(contextMenu.item); contextMenu.show = false">
                        <i data-lucide="scroll" class="w-4 h-4"></i> Gerar Contrato
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


        <!-- Modal Resumo da Proposta -->
        <template x-teleport="body">
            <div x-show="modalResumoAberto"
                 class="fixed inset-0 z-[2000] flex items-center justify-center p-6 bg-black/80 backdrop-blur-md"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-cloak
                 x-init="$watch('modalResumoAberto', v => { if(v) $nextTick(() => lucide.createIcons()) })">

                <template x-if="selectedProposta">
                    <div class="bg-[#0c0c0c] border border-white/10 rounded-[2.5rem] w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col"
                        @click.away="modalResumoAberto = false"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0">

                        <!-- Header -->
                        <div class="p-8 border-b border-white/5 flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                                        :class="{
                                            'bg-zinc-800 text-zinc-400': selectedProposta.status === 'rascunho',
                                            'bg-blue-500/10 text-blue-500': selectedProposta.status === 'pendente',
                                            'bg-emerald-500/10 text-emerald-500': selectedProposta.status === 'aceita',
                                            'bg-red-500/10 text-red-500': selectedProposta.status === 'recusada'
                                        }" x-text="selectedProposta.status.toUpperCase()"></span>
                                    <span class="text-zinc-600 text-[10px] font-bold uppercase tracking-widest" x-text="'Criada em ' + new Date(selectedProposta.created_at).toLocaleDateString()"></span>
                                </div>
                                <h2 class="text-3xl font-black text-white tracking-tight" x-text="selectedProposta.cliente_nome"></h2>
                                <p class="text-zinc-500 font-medium" x-text="selectedProposta.titulo"></p>
                            </div>
                            <button @click="modalResumoAberto = false" class="text-zinc-500 hover:text-white transition-colors">
                                <i data-lucide="x" class="w-6 h-6"></i>
                            </button>
                        </div>

                        <!-- Content -->
                        <div class="p-8 overflow-y-auto max-h-[60vh] custom-scrollbar">
                            <div class="grid grid-cols-2 gap-8 mb-8">
                                <div>
                                    <h3 class="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-4">Dados do Cliente</h3>
                                    <div class="space-y-3">
                                         <div class="flex items-center gap-3">
                                             <div class="w-8 h-8 rounded-lg bg-zinc-900 flex items-center justify-center text-zinc-500">
                                                 <i data-lucide="user" class="w-4 h-4"></i>
                                             </div>
                                             <div class="flex flex-col">
                                                 <span class="text-sm font-bold text-zinc-300" x-text="getResponsavel()"></span>
                                                 <template x-if="getResumoDados().contato_tipo">
                                                     <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full mt-1 inline-block w-fit"
                                                         :class="{
                                                             'bg-rose-500/10 text-rose-500': getResumoDados().contato_tipo === 'noiva',
                                                             'bg-blue-500/10 text-blue-500': getResumoDados().contato_tipo === 'noivo',
                                                             'bg-zinc-800 text-zinc-500': getResumoDados().contato_tipo === 'outro'
                                                         }" x-text="getResumoDados().contato_tipo"></span>
                                                 </template>
                                             </div>
                                         </div>
                                         <div class="flex items-center gap-3">
                                             <div class="w-8 h-8 rounded-lg bg-zinc-900 flex items-center justify-center text-zinc-500">
                                                 <i data-lucide="phone" class="w-4 h-4"></i>
                                             </div>
                                             <span class="text-sm font-bold text-zinc-300" x-text="getResumoDados().whatsapp || 'NÃ£o informado'"></span>
                                         </div>
                                     </div>
                                 </div>
                                 <div>
                                     <h3 class="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-4">Andamento</h3>
                                     <div class="bg-zinc-900/50 rounded-2xl p-4 border border-white/5">
                                         <div class="flex items-center justify-between mb-3">
                                             <span class="text-xs font-bold text-zinc-400">Progresso</span>
                                             <span class="text-xs font-black text-white" x-text="selectedProposta.status === 'rascunho' ? '10%' : (selectedProposta.status === 'pendente' ? '50%' : '100%')"></span>
                                         </div>
                                         <div class="h-1.5 w-full bg-zinc-800 rounded-full overflow-hidden">
                                             <div class="h-full bg-emerald-500 transition-all duration-1000"
                                                 :style="`width: ${selectedProposta.status === 'rascunho' ? '10%' : (selectedProposta.status === 'pendente' ? '50%' : '100%')}`"></div>
                                         </div>
                                         <p class="text-[10px] text-zinc-500 mt-3 italic" x-text="selectedProposta.status === 'aceita' ? 'Proposta fechada e aprovada pelo cliente.' : 'Aguardando interaÃ§Ã£o do cliente.'"></p>
                                     </div>
                                 </div>
                                 <template x-if="recomendacao">
                                     <div>
                                         <h3 class="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-4">PrÃ³ximo passo recomendado</h3>
                                         <div class="bg-emerald-500/10 border border-emerald-500/15 rounded-2xl p-4 text-sm text-zinc-100">
                                             <p x-text="recomendacao"></p>
                                         </div>
                                     </div>
                                 </template>
                             </div>

                             <!-- HistÃ³rico / Timeline -->
                             <div class="mb-8">
                                 <h3 class="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-4">HistÃ³rico de InteraÃ§Ãµes</h3>

                                 <!-- Input de Nota -->
                                 <div class="bg-zinc-900/30 border border-white/5 rounded-2xl p-4 mb-4">
                                     <textarea x-model="novaNota"
                                               placeholder="O que aconteceu nesta conversa? (ex: cliente vai pensar, objeÃ§Ã£o de preÃ§o...)"
                                               class="w-full bg-transparent border-0 focus:ring-0 text-sm text-zinc-300 placeholder:text-zinc-600 resize-none"
                                               rows="2"></textarea>
                                     <div class="flex justify-end mt-2">
                                         <button @click="adicionarHistorico()"
                                                 :disabled="!novaNota.trim()"
                                                 class="px-4 py-1.5 bg-white text-black rounded-lg text-[10px] font-black uppercase tracking-widest disabled:opacity-50 hover:scale-105 transition-all">
                                             Salvar Registro
                                         </button>
                                     </div>
                                 </div>

                                 <!-- Lista de Eventos -->
                                 <div class="space-y-4 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                                     <template x-if="carregandoHistorico">
                                         <div class="flex justify-center py-4">
                                             <div class="w-6 h-6 border-2 border-white/10 border-t-white rounded-full animate-spin"></div>
                                         </div>
                                     </template>

                                     <template x-for="event in historico" :key="event.id">
                                         <div class="flex gap-4 group">
                                             <div class="flex flex-col items-center">
                                                 <div class="w-8 h-8 rounded-full bg-zinc-900 border border-white/5 flex items-center justify-center text-zinc-500 group-first:bg-white group-first:text-black transition-colors">
                                                     <i :data-lucide="event.tipo === 'nota' ? 'file-text' : (event.tipo === 'ligacao' ? 'phone' : 'message-circle')" class="w-3.5 h-3.5"></i>
                                                 </div>
                                                 <div class="w-px flex-1 bg-white/5 mt-2 group-last:hidden"></div>
                                             </div>
                                             <div class="flex-1 pb-6">
                                                 <div class="flex items-center justify-between mb-1">
                                                     <span class="text-[10px] font-bold text-zinc-400" x-text="event.usuario_nome"></span>
                                                     <span class="text-[9px] text-zinc-600" x-text="new Date(event.created_at).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })"></span>
                                                 </div>
                                                 <p class="text-xs text-zinc-300 leading-relaxed" x-text="event.conteudo"></p>
                                             </div>
                                         </div>
                                     </template>

                                     <template x-if="!carregandoHistorico && historico.length === 0">
                                         <div class="text-center py-6 border border-dashed border-white/5 rounded-3xl">
                                             <p class="text-[10px] font-bold text-zinc-600 uppercase tracking-widest">Nenhuma interaÃ§Ã£o registrada ainda.</p>
                                         </div>
                                     </template>
                                 </div>
                             </div>

                             <!-- Fechamento / Contrato / Asaas -->
                             <div class="bg-white/5 rounded-3xl p-6 border border-white/5 mb-8">
                                 <div class="flex items-start justify-between gap-4 mb-6">
                                     <div>
                                         <h3 class="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-2">Fechamento, contrato e Asaas</h3>
                                         <p class="text-xs text-zinc-500">Dados usados para gerar contrato, assinatura e cobrança.</p>
                                     </div>
                                     <span class="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-[10px] font-black uppercase tracking-widest" x-text="getPlanoEscolhidoLabel()"></span>
                                 </div>

                                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                     <div>
                                         <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Plano escolhido</label>
                                         <select x-model="fechamentoForm.pacote_dado_andamento" @change="aplicarRegrasPagamento(true)" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none">
                                             <option value="">Ainda não definido</option>
                                             <option value="heritage">Experiência Heritage</option>
                                             <option value="cinematic">Experiência Cinematic</option>
                                             <option value="essencial">Registro Essencial</option>
                                         </select>
                                     </div>
                                     <div>
                                         <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Valor final</label>
                                         <input x-model="fechamentoForm.valor_total" @input="aplicarRegrasPagamento(false)" type="text" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none" placeholder="R$ 0,00">
                                     </div>
                                     <div>
                                         <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Cobrança Asaas</label>
                                         <select x-model="fechamentoForm.asaas_billing_type" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none">
                                             <option value="UNDEFINED">Cliente escolhe no Asaas</option>
                                             <option value="PIX">Pix</option>
                                             <option value="BOLETO">Boleto</option>
                                             <option value="CREDIT_CARD">Cartão de crédito</option>
                                         </select>
                                     </div>
                                     <div>
                                         <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Forma de pagamento</label>
                                         <select x-model="fechamentoForm.pagamento_modo" @change="aplicarRegrasPagamento(true)" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none">
                                             <option value="avista">À vista</option>
                                             <option value="parcelado">Parcelado</option>
                                         </select>
                                     </div>
                                     <div x-show="fechamentoForm.pagamento_modo === 'parcelado'">
                                         <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Parcelas do saldo</label>
                                         <input x-model="fechamentoForm.asaas_total_parcelas" @input="validarParcelasFechamento()" type="number" min="1" max="60" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none">
                                         <p class="text-[10px] text-zinc-500 mt-2" x-text="textoLimiteParcelas()"></p>
                                     </div>
                                     <div>
                                         <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Vencimento do sinal</label>
                                         <input x-model="fechamentoForm.asaas_sinal_vencimento" @change="aplicarRegrasPagamento(false)" type="date" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none">
                                     </div>
                                     <div>
                                         <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Valor do sinal</label>
                                         <input x-model="fechamentoForm.asaas_valor_sinal" type="text" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none" placeholder="R$ 0,00">
                                         <p class="text-[10px] text-zinc-500 mt-2" x-text="textoPercentualEntrada()"></p>
                                     </div>
                                     <div x-show="fechamentoForm.pagamento_modo === 'parcelado'">
                                         <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Primeira parcela</label>
                                         <input x-model="fechamentoForm.asaas_first_due_date" @change="validarParcelasFechamento()" type="date" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none">
                                     </div>
                                     <div>
                                         <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Prazo / observação</label>
                                         <input x-model="fechamentoForm.prazo_contrato" type="text" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none" placeholder="Ex: quitar até 15 dias antes do evento">
                                     </div>
                                     <div class="md:col-span-2" x-show="fechamentoForm.pagamento_modo === 'parcelado'">
                                         <label class="flex items-center justify-between gap-4 p-4 rounded-2xl bg-black/30 border border-white/10 cursor-pointer">
                                             <div>
                                                 <span class="block text-xs font-bold text-white">Permitir parcela depois da data do evento</span>
                                                 <span class="block text-[10px] text-zinc-500 mt-1">Use somente quando isso fizer parte da negociação com o casal.</span>
                                             </div>
                                             <input type="checkbox" x-model="fechamentoForm.permitir_parcela_pos_evento" @change="validarParcelasFechamento()" class="w-5 h-5">
                                         </label>
                                         <p class="text-[10px] mt-2" :class="alertaParcelas ? 'text-amber-400' : 'text-zinc-500'" x-text="alertaParcelas || resumoParcelasFechamento()"></p>
                                     </div>
                                     <div class="md:col-span-2">
                                         <label class="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Condições de pagamento para o contrato</label>
                                         <textarea x-model="fechamentoForm.escolha_condicoes" rows="3" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none resize-none" placeholder="Ex: Entrada de 20% via Pix e saldo parcelado..."></textarea>
                                     </div>
                                 </div>

                                 <div class="flex justify-end mt-5">
                                     <button @click="salvarFechamento()"
                                             :disabled="fechamentoSalvando"
                                             class="px-5 py-2.5 rounded-xl bg-white text-black text-[10px] font-black uppercase tracking-widest disabled:opacity-50 hover:scale-105 transition-all">
                                         <span x-text="fechamentoSalvando ? 'Salvando...' : 'Salvar dados do fechamento'"></span>
                                     </button>
                                 </div>
                             </div>

                             <!-- Resumo do que foi proposto -->
                             <div class="bg-white/5 rounded-3xl p-6 border border-white/5">
                                 <h3 class="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-6">O que foi proposto</h3>

                                 <div class="space-y-4">
                                     <template x-if="getResumoDados().pacotes && getResumoDados().pacotes.length > 0">
                                         <template x-for="pacote in getResumoDados().pacotes">
                                             <div class="flex items-center justify-between py-3 border-b border-white/5 last:border-0">
                                                 <div class="flex items-center gap-4">
                                                     <div class="w-10 h-10 rounded-xl bg-white text-black flex items-center justify-center font-black text-xs" x-text="pacote.nome.charAt(0)"></div>
                                                     <div>
                                                         <p class="text-sm font-bold text-white" x-text="pacote.nome"></p>
                                                         <p class="text-[10px] text-zinc-500" x-text="pacote.itens ? pacote.itens.length + ' itens inclusos' : ''"></p>
                                                     </div>
                                                 </div>
                                                 <p class="text-sm font-black text-white" x-text="'R$ ' + (parseFloat(pacote.valor) || 0).toLocaleString('pt-BR')"></p>
                                             </div>
                                         </template>
                                     </template>
                                     <template x-if="!getResumoDados().pacotes || getResumoDados().pacotes.length === 0">
                                         <p class="text-sm text-zinc-500 italic py-4 text-center">Nenhum pacote detalhado no resumo.</p>
                                     </template>
                                 </div>
                             </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="p-8 bg-zinc-900/50 border-t border-white/5 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <button @click="deletarProposta(selectedProposta.id); modalResumoAberto = false" class="w-12 h-12 rounded-2xl bg-red-500/10 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                                <button @click="enviarWhatsApp(selectedProposta)" class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center hover:bg-emerald-500 hover:text-white transition-all">
                                    <i data-lucide="message-circle" class="w-5 h-5"></i>
                                </button>
                                <button @click="if(window.innerWidth > 1024) { showModalEditar = true; editUrl = 'proposta_editar.php?id=' + selectedProposta.id + '&layout=modal&t=' + Date.now(); modalResumoAberto = false } else { location.href = 'proposta_editar.php?id=' + selectedProposta.id }"
                                        class="w-12 h-12 rounded-2xl bg-zinc-800 text-zinc-400 flex items-center justify-center hover:bg-white hover:text-black transition-all">
                                    <i data-lucide="edit-3" class="w-5 h-5"></i>
                                </button>
                                <button @click="gerarContrato(selectedProposta)"
                                        title="Gerar Contrato"
                                        class="w-12 h-12 rounded-2xl bg-zinc-800 text-zinc-400 flex items-center justify-center hover:bg-white hover:text-black transition-all">
                                    <i data-lucide="scroll" class="w-5 h-5"></i>
                                </button>
                            </div>

                            <button @click="window.open(`<?= APP_URL ?>/p/${selectedProposta.slug}`, '_blank')"
                                    class="flex-1 bg-white text-black h-12 rounded-2xl font-black text-sm hover:scale-[1.02] active:scale-95 transition-all shadow-xl flex items-center justify-center gap-2">
                                <i data-lucide="external-link" class="w-4 h-4"></i>
                                Abrir Proposta Completa
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </template>
        <template x-if="showModalNova">
            <div class="fixed inset-0 z-[3000] flex items-center justify-center p-6 bg-black/90 backdrop-blur-md"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100">

                <div class="bg-[#0c0c0c]/90 backdrop-blur-3xl rounded-[2.5rem] w-[85%] h-[90vh] flex flex-col overflow-hidden relative shadow-[0_0_100px_rgba(0,0,0,0.8)] border border-white/5"
                     x-init="$watch('showModalNova', v => { if(v) $nextTick(() => lucide.createIcons()) })"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0">

                    <!-- Header Modal -->
                    <div class="px-8 py-6 bg-transparent border-b border-white/5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h2 class="text-[10px] font-black text-zinc-500 uppercase tracking-[0.3em]">Registrar Nova Proposta</h2>
                        </div>
                        <button @click="showModalNova = false; window.location.reload()"
                                class="p-2 hover:bg-white/5 rounded-full transition-colors text-zinc-500 hover:text-white group flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="group-hover:rotate-90 transition-transform duration-300">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>

                    <!-- Content Iframe -->
                    <div class="flex-1 bg-[#fcfcfc] overflow-hidden">
                        <iframe :src="currentFolder ? novaUrl + '&folder=' + currentFolder : novaUrl"
                                class="w-full h-full border-0"></iframe>
                    </div>
                </div>
            </div>
        </template>

        <!-- Modal Editar Proposta (Desktop Only) -->
        <template x-if="showModalEditar">
            <div class="fixed inset-0 z-[3000] flex items-center justify-center p-6 bg-black/90 backdrop-blur-md"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100">

                <div class="bg-[#0c0c0c]/90 backdrop-blur-3xl rounded-[2.5rem] w-[85%] h-[90vh] flex flex-col overflow-hidden relative shadow-[0_0_100px_rgba(0,0,0,0.8)] border border-white/5"
                     x-init="$watch('showModalEditar', v => { if(v) $nextTick(() => lucide.createIcons()) })"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0">

                    <!-- Header Modal -->
                    <div class="px-8 py-4 bg-zinc-900 border-b border-zinc-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Editar Proposta</h2>
                        </div>
                        <button @click="showModalEditar = false; window.location.reload()"
                                class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white group flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="group-hover:rotate-90 transition-transform duration-300">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>

                    <!-- Content Iframe -->
                    <div class="flex-1 bg-[#fcfcfc] overflow-hidden">
                        <iframe :src="editUrl" class="w-full h-full border-0"></iframe>
                    </div>
                </div>
            </div>
        </template>

        <!-- Modal Gerenciar Pasta -->
        <template x-teleport="body">
            <div x-show="folderModal.show"
                 class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 style="display: none;">

                <div @click.away="folderModal.show = false"
                     class="bg-zinc-900 border border-zinc-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0">

                    <div class="px-6 py-6">
                        <h3 class="text-xl font-bold text-white mb-2" x-text="folderModal.mode === 'create' ? 'Nova Pasta' : 'Renomear Pasta'"></h3>
                        <p class="text-zinc-400 text-sm mb-6">Digite um nome para organizar suas propostas.</p>

                        <div class="form-group">
                            <label class="label text-zinc-400">Nome da Pasta</label>
                            <input type="text"
                                   id="folderNameInput"
                                   x-model="folderModal.nome"
                                   class="input bg-zinc-800 border-zinc-700 text-white focus:border-white transition-all w-full"
                                   placeholder="Ex: Campanhas de Maio"
                                   @keydown.enter="confirmarPasta()"
                                   @keydown.escape="folderModal.show = false">
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-zinc-800/50 border-t border-zinc-800 flex justify-end gap-3">
                        <button @click="folderModal.show = false"
                                class="px-4 py-2 text-zinc-400 hover:text-white font-medium transition-colors">
                            Cancelar
                        </button>
                        <button @click="confirmarPasta()"
                                class="px-6 py-2 bg-white text-black font-bold rounded-lg hover:bg-zinc-200 transition-colors">
                            Salvar
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Modal de ConfirmaÃ§Ã£o de ExclusÃ£o -->
        <template x-teleport="body">
            <div x-show="deleteModal.show"
                 class="fixed inset-0 z-[1000] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 style="display: none;"
                 x-init="$watch('deleteModal.show', v => { if(v) $nextTick(() => lucide.createIcons()) })">

                <div @click.away="deleteModal.show = false"
                     class="bg-zinc-900 border border-zinc-800 rounded-2xl w-full max-w-sm overflow-hidden shadow-2xl"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0">

                    <div class="p-6 text-center">
                        <div class="w-16 h-16 bg-red-900/20 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="alert-triangle" class="w-8 h-8"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Confirmar ExclusÃ£o</h3>
                        <p class="text-zinc-400 text-sm" x-text="deleteModal.message"></p>
                    </div>

                    <div class="px-6 py-4 bg-zinc-800/50 border-t border-zinc-800 flex justify-center gap-3">
                        <button @click="deleteModal.show = false"
                                class="px-4 py-2 text-zinc-400 hover:text-white font-medium transition-colors">
                            Cancelar
                        </button>
                        <button @click="confirmarExclusao()"
                                class="px-6 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition-colors">
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
        currentFolder: new URLSearchParams(window.location.search).get('folder') || null,
        showSearch: false,
        searchQuery: '',
        showModalNova: false,
        showModalEditar: false,
        editUrl: '',
        pastas: <?= json_encode($pastas) ?>,
        propostas: <?= json_encode($propostas) ?>,
        contextMenu: { show: false, x: 0, y: 0, type: 'root', item: null },
        deleteModal: { show: false, id: null, type: '', message: '' },
        draggedItem: null,
        folderModal: { show: false, id: null, nome: '', mode: 'create' },
        modalResumoAberto: false,
        selectedProposta: null,
        recomendacao: '',
        novaUrl: 'proposta_nova.php?layout=modal',
        fechamentoSalvando: false,
        fechamentoForm: {
            pacote_dado_andamento: '',
            valor_total: '',
            escolha_condicoes: '',
            asaas_billing_type: 'UNDEFINED',
            asaas_total_parcelas: 1,
            asaas_first_due_date: '',
            asaas_valor_sinal: '',
            asaas_sinal_vencimento: '',
            prazo_contrato: '',
            pagamento_modo: 'parcelado',
            permitir_parcela_pos_evento: false
        },
        alertaParcelas: '',

        init() {
            this.$watch('currentFolder', (val) => {
                const url = new URL(window.location);
                if (val) {
                    url.searchParams.set('folder', val);
                } else {
                    url.searchParams.delete('folder');
                }
                window.history.pushState({}, '', url);
            });

            this.$watch('propostas', () => {
                this.$nextTick(() => lucide.createIcons());
            }, { deep: true });
        },

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

        getResumoDados() {
            if (!this.selectedProposta) return {};
            try {
                return JSON.parse(this.selectedProposta.dados_json || '{}');
            } catch (e) {
                return {};
            }
        },

        getResponsavel() {
            const dados = this.getResumoDados();
            if (dados.contato_tipo === 'noivo' && dados.nome_noivo) return dados.nome_noivo;
            if (dados.contato_tipo === 'noiva' && dados.nome_noiva) return dados.nome_noiva;
            return dados.responsavel || this.selectedProposta.cliente_nome;
        },

        getResponsavelFromDados(dados, proposta) {
            if (!dados) return proposta ? proposta.cliente_nome : '';
            if (dados.contato_tipo === 'noivo' && dados.nome_noivo) return dados.nome_noivo;
            if (dados.contato_tipo === 'noiva' && dados.nome_noiva) return dados.nome_noiva;
            return dados.responsavel || (proposta ? proposta.cliente_nome : '');
        },

        getPlanoEscolhidoLabel() {
            const mapa = {
                heritage: 'Heritage',
                cinematic: 'Cinematic',
                essencial: 'Essencial'
            };
            return mapa[this.fechamentoForm.pacote_dado_andamento] || 'A definir';
        },

        formatarValorFechamento(valor) {
            const numero = parseFloat(String(valor || '0').replace(/\./g, '').replace(',', '.')) || 0;
            return numero > 0 ? numero.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
        },

        numeroFechamento(valor) {
            return parseFloat(String(valor || '0').replace(/[R$\s]/g, '').replace(/\./g, '').replace(',', '.')) || 0;
        },

        percentualEntradaPlano(plano = this.fechamentoForm.pacote_dado_andamento) {
            return plano === 'heritage' ? 25 : 20;
        },

        maxParcelasPlano(plano = this.fechamentoForm.pacote_dado_andamento) {
            return plano === 'heritage' ? 6 : 5;
        },

        textoPercentualEntrada() {
            return `Entrada sugerida: ${this.percentualEntradaPlano()}% do valor final.`;
        },

        textoLimiteParcelas() {
            return `Máximo padrão: ${this.maxParcelasPlano()} parcelas para este plano.`;
        },

        addMesesIso(dataIso, meses) {
            if (!dataIso) return '';
            const d = new Date(dataIso + 'T00:00:00');
            if (Number.isNaN(d.getTime())) return '';
            d.setMonth(d.getMonth() + meses);
            return d.toISOString().slice(0, 10);
        },

        mesesAteEvento() {
            const dados = this.getResumoDados();
            const primeira = this.fechamentoForm.asaas_first_due_date;
            const evento = dados.data_casamento || dados.data_evento || '';
            if (!primeira || !evento) return this.maxParcelasPlano();
            const inicio = new Date(primeira + 'T00:00:00');
            const fim = new Date(evento + 'T00:00:00');
            if (Number.isNaN(inicio.getTime()) || Number.isNaN(fim.getTime()) || fim < inicio) return 1;
            return Math.max(1, ((fim.getFullYear() - inicio.getFullYear()) * 12) + (fim.getMonth() - inicio.getMonth()) + 1);
        },

        limiteParcelasPermitido() {
            const limitePlano = this.maxParcelasPlano();
            if (this.fechamentoForm.permitir_parcela_pos_evento) return limitePlano;
            return Math.max(1, Math.min(limitePlano, this.mesesAteEvento()));
        },

        ultimaParcelaIso() {
            const parcelas = parseInt(this.fechamentoForm.asaas_total_parcelas || 1, 10) || 1;
            return this.addMesesIso(this.fechamentoForm.asaas_first_due_date, parcelas - 1);
        },

        resumoParcelasFechamento() {
            if (this.fechamentoForm.pagamento_modo === 'avista') return 'Pagamento à vista: sem saldo parcelado.';
            const ultima = this.ultimaParcelaIso();
            return ultima ? `Última parcela prevista para ${new Date(ultima + 'T00:00:00').toLocaleDateString('pt-BR')}.` : '';
        },

        montarCondicoesAutomaticas() {
            const modo = this.fechamentoForm.pagamento_modo;
            const percentual = this.percentualEntradaPlano();
            const valorFinal = this.numeroFechamento(this.fechamentoForm.valor_total);
            const entrada = this.numeroFechamento(this.fechamentoForm.asaas_valor_sinal);
            if (modo === 'avista') {
                return `Pagamento à vista no valor total de R$ ${valorFinal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}.`;
            }
            const parcelas = parseInt(this.fechamentoForm.asaas_total_parcelas || 1, 10) || 1;
            return `Entrada de ${percentual}% no valor de R$ ${entrada.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} + saldo parcelado em até ${parcelas}x.`;
        },

        validarParcelasFechamento() {
            this.alertaParcelas = '';
            if (this.fechamentoForm.pagamento_modo === 'avista') {
                this.fechamentoForm.asaas_total_parcelas = 1;
                this.fechamentoForm.asaas_first_due_date = '';
                return;
            }

            const limite = this.limiteParcelasPermitido();
            let parcelas = parseInt(this.fechamentoForm.asaas_total_parcelas || 1, 10) || 1;
            if (parcelas > limite) {
                parcelas = limite;
                this.fechamentoForm.asaas_total_parcelas = limite;
                this.alertaParcelas = this.fechamentoForm.permitir_parcela_pos_evento
                    ? `Ajustado para o máximo padrão do plano: ${limite} parcelas.`
                    : `Ajustado para ${limite} parcela(s), para não passar da data do evento.`;
            }

            const dados = this.getResumoDados();
            const evento = dados.data_casamento || dados.data_evento || '';
            const ultima = this.ultimaParcelaIso();
            if (evento && ultima && ultima > evento && !this.fechamentoForm.permitir_parcela_pos_evento) {
                this.alertaParcelas = 'A última parcela passaria da data do evento. Ative a permissão para negociar isso.';
            }
        },

        aplicarRegrasPagamento(forcarCondicoes = false) {
            const valor = this.numeroFechamento(this.fechamentoForm.valor_total);
            const percentual = this.percentualEntradaPlano();
            if (valor > 0) {
                const sinal = valor * (percentual / 100);
                this.fechamentoForm.asaas_valor_sinal = this.formatarValorFechamento(sinal);
            }

            if (!this.fechamentoForm.asaas_sinal_vencimento) {
                this.fechamentoForm.asaas_sinal_vencimento = new Date().toISOString().slice(0, 10);
            }

            if (this.fechamentoForm.pagamento_modo === 'parcelado' && !this.fechamentoForm.asaas_first_due_date) {
                this.fechamentoForm.asaas_first_due_date = this.addMesesIso(this.fechamentoForm.asaas_sinal_vencimento, 1);
            }

            if (this.fechamentoForm.pagamento_modo === 'parcelado') {
                const atual = parseInt(this.fechamentoForm.asaas_total_parcelas || 0, 10);
                if (!atual || atual === 1 || forcarCondicoes) {
                    this.fechamentoForm.asaas_total_parcelas = this.limiteParcelasPermitido();
                }
            }

            this.validarParcelasFechamento();
            if (forcarCondicoes || !String(this.fechamentoForm.escolha_condicoes || '').trim()) {
                this.fechamentoForm.escolha_condicoes = this.montarCondicoesAutomaticas();
            }
        },

        inicializarFechamento() {
            const dados = this.getResumoDados();
            const escolha = dados.cliente_escolha || {};
            const hoje = new Date().toISOString().slice(0, 10);
            const sinalVencimento = dados.asaas_sinal_vencimento || hoje;
            const primeiraParcela = dados.asaas_first_due_date || (() => {
                const d = new Date(sinalVencimento + 'T00:00:00');
                d.setMonth(d.getMonth() + 1);
                return d.toISOString().slice(0, 10);
            })();

            this.fechamentoForm = {
                pacote_dado_andamento: dados.pacote_dado_andamento || escolha.plano_id || '',
                valor_total: this.formatarValorFechamento(dados.valor_fechamento || escolha.valor_total || this.selectedProposta?.valor_total || ''),
                escolha_condicoes: dados.escolha_condicoes || escolha.condicoes || '',
                asaas_billing_type: dados.asaas_billing_type || 'UNDEFINED',
                asaas_total_parcelas: parseInt(dados.asaas_total_parcelas || 1, 10),
                asaas_first_due_date: primeiraParcela,
                asaas_valor_sinal: this.formatarValorFechamento(dados.asaas_valor_sinal || ''),
                asaas_sinal_vencimento: sinalVencimento,
                prazo_contrato: dados.prazo_contrato || '',
                pagamento_modo: dados.pagamento_modo || ((parseInt(dados.asaas_total_parcelas || 1, 10) > 1) ? 'parcelado' : 'parcelado'),
                permitir_parcela_pos_evento: !!dados.permitir_parcela_pos_evento
            };
            const condicoesAtuais = String(this.fechamentoForm.escolha_condicoes || '');
            const pareceTextoPadrao = /^Entrada de \d+%(\s+no valor de R\$ [\d.,]+)? \+ saldo parcelado/i.test(condicoesAtuais)
                || /^Entrada de \d+% \+ Saldo parcelado/i.test(condicoesAtuais);
            this.aplicarRegrasPagamento(!condicoesAtuais.trim() || pareceTextoPadrao);
        },

        async salvarFechamento() {
            if (!this.selectedProposta) return;
            this.fechamentoSalvando = true;

            try {
                const response = await fetch('<?= raizUrl('/api/propostas/fechamento.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: this.selectedProposta.id,
                        ...this.fechamentoForm
                    })
                });
                const json = await response.json();
                if (!json.success) {
                    throw new Error(json.erro || 'Falha ao salvar dados do fechamento.');
                }

                this.selectedProposta.dados_json = json.dados_json;
                this.selectedProposta.valor_total = json.valor_total;
                const idx = this.propostas.findIndex(p => p.id === this.selectedProposta.id);
                if (idx >= 0) {
                    this.propostas[idx].dados_json = json.dados_json;
                    this.propostas[idx].valor_total = json.valor_total;
                }
                await this.fetchHistorico(this.selectedProposta.id);
                alert('Dados do fechamento salvos.');
            } catch (e) {
                alert(e.message);
            } finally {
                this.fechamentoSalvando = false;
            }
        },

        // HistÃ³rico / CRM
        historico: [],
        novaNota: '',
        carregandoHistorico: false,

        async fetchHistorico(id) {
            this.carregandoHistorico = true;
            const apiBase = '<?= raizUrl('/api/gerenciamento/proposta_historico.php') ?>';
            const url = apiBase + '?id=' + encodeURIComponent(id);
            console.log('[HistÃ³rico] GET:', url);
            try {
                const response = await fetch(url);
                console.log('[HistÃ³rico] GET status:', response.status);
                const text = await response.text();
                console.log('[HistÃ³rico] GET raw response:', text);
                let data;
                try { data = JSON.parse(text); } catch(pe) {
                    console.error('[HistÃ³rico] JSON parse failed:', pe, text.substring(0, 200));
                    this.historico = [];
                    return;
                }
                if (Array.isArray(data)) {
                    this.historico = data;
                    this.recomendacao = '';
                    console.log('[HistÃ³rico] Loaded', data.length, 'records');
                } else if (data && typeof data === 'object') {
                    this.historico = Array.isArray(data.historico) ? data.historico : [];
                    this.recomendacao = data.recomendacao || '';
                    console.log('[HistÃ³rico] Loaded', this.historico.length, 'records');
                } else {
                    console.error('[HistÃ³rico] Resposta nÃ£o Ã© array nem objeto:', data);
                    this.historico = [];
                    this.recomendacao = '';
                }
            } catch (e) {
                console.error('[HistÃ³rico] Fetch error:', e);
                this.historico = [];
            } finally {
                this.carregandoHistorico = false;
                this.$nextTick(() => lucide.createIcons());
            }
        },

        async adicionarHistorico() {
            if (!this.novaNota.trim()) return;
            const apiBase = '<?= raizUrl('/api/gerenciamento/proposta_historico.php') ?>';
            const payload = {
                proposta_id: this.selectedProposta.id,
                tipo: 'nota',
                conteudo: this.novaNota
            };
            console.log('[HistÃ³rico] POST:', apiBase, payload);

            try {
                const response = await fetch(apiBase, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                console.log('[HistÃ³rico] POST status:', response.status);
                const text = await response.text();
                console.log('[HistÃ³rico] POST raw response:', text);
                let res;
                try { res = JSON.parse(text); } catch(pe) {
                    console.error('[HistÃ³rico] POST JSON parse failed:', pe, text.substring(0, 200));
                    alert('Erro: Servidor retornou resposta invÃ¡lida. Veja o console (F12).');
                    return;
                }
                if (res.sucesso) {
                    console.log('[HistÃ³rico] Salvo com sucesso!', res.debug);
                    this.novaNota = '';
                    this.recomendacao = res.recomendacao || this.recomendacao || '';
                    await this.fetchHistorico(this.selectedProposta.id);
                } else {
                    console.error('[HistÃ³rico] Erro do servidor:', res);
                    alert('Erro ao salvar: ' + (res.erro || JSON.stringify(res)));
                }
            } catch (e) {
                console.error('[HistÃ³rico] POST fetch error:', e);
                alert('Erro de conexÃ£o: ' + e.message);
            }
        },

        abrirResumo(p) {
            this.selectedProposta = p;
            this.modalResumoAberto = true;
            this.historico = [];
            this.inicializarFechamento();
            this.fetchHistorico(p.id);
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

        // AÃ§Ãµes de API (Mockadas por enquanto para resposta rÃ¡pida na UI)
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
            this.folderModal = { show: true, id: null, nome: '', mode: 'create' };
            this.contextMenu.show = false;
            this.$nextTick(() => document.getElementById('folderNameInput').focus());
        },

        renomearPasta(folder) {
            this.folderModal = { show: true, id: folder.id, nome: folder.nome, mode: 'rename' };
            this.contextMenu.show = false;
            this.$nextTick(() => document.getElementById('folderNameInput').focus());
        },

        confirmarPasta() {
            const { id, nome, mode } = this.folderModal;
            if (!nome.trim()) return;

            if (mode === 'create') {
                const newId = crypto.randomUUID();
                const novaPasta = { id: newId, nome: nome.trim(), created_at: new Date().toISOString() };
                this.pastas.push(novaPasta);
                fetch('../api/propostas/organizar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'create_folder', id: newId, nome: nome.trim() })
                });
            } else {
                const folder = this.pastas.find(f => f.id === id);
                if (folder) {
                    folder.nome = nome.trim();
                    fetch('../api/propostas/organizar.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'rename_folder', id: id, nome: nome.trim() })
                    });
                }
            }
            this.folderModal.show = false;
        },

        deletarPasta(id) {
            this.deleteModal = {
                show: true,
                id: id,
                type: 'pasta',
                message: 'Excluir esta pasta? As propostas dentro dela voltarÃ£o para a raiz.'
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

        gerarContrato(proposta) {
            if (!proposta) return;

            let dados = {};
            try {
                dados = JSON.parse(proposta.dados_json || '{}');
            } catch (e) {
                dados = {};
            }

            if (proposta.tipo === 'casamento') {
                const plano = dados?.cliente_escolha?.plano_id || dados?.pacote_dado_andamento || '';
                const valor = parseFloat(dados?.cliente_escolha?.valor_total || proposta.valor_total || 0) || 0;
                const faltas = [];

                if (!plano) faltas.push('escolher o plano fechado pelo casal');
                if (valor <= 0) faltas.push('conferir o valor final');
                if (!dados.data_casamento) faltas.push('preencher a data do casamento');
                if (!dados.whatsapp) faltas.push('preencher o WhatsApp do cliente');

                if (faltas.length > 0) {
                    alert('Antes de gerar o contrato, revise a proposta:\n\n- ' + faltas.join('\n- '));
                    location.href = `proposta_editar.php?id=${proposta.id}&contrato_precheck=1`;
                    return;
                }
            }

            location.href = `contrato_gerar.php?proposta_id=${proposta.id}`;
        },

        copiarLink(slug) {
            const link = `<?= APP_URL ?>/p/${slug}`;
            navigator.clipboard.writeText(link).then(() => alert('Link copiado!'));
        },

        whatsappLoading: false,

        async enviarWhatsApp(proposta) {
            const dados = JSON.parse(proposta.dados_json || '{}');
            const numero = (dados.whatsapp || '').replace(/\D/g, '');

            if (!numero) {
                alert('NÃºmero de WhatsApp nÃ£o cadastrado nesta proposta.\nAdicione o WhatsApp do cliente ao editar a proposta.');
                return;
            }

            this.whatsappLoading = true;

            try {
                const res = await fetch('<?= raizUrl('/api/propostas/mensagem-whatsapp.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: proposta.id })
                });
                const json = await res.json();

                if (json.erro) throw new Error(json.erro);

                window.open(`https://wa.me/${numero}?text=${encodeURIComponent(json.mensagem)}`, '_blank');

            } catch (e) {
                // Fallback direto se a IA falhar
                const dadosF = JSON.parse(proposta.dados_json || '{}');
                const nomeF = this.getResponsavelFromDados(dadosF, proposta);
                const primeiroNome = nomeF.split(' ')[0];
                const link = `<?= APP_URL ?>/p/${proposta.slug}`;
                const fallback = `Oi, ${primeiroNome}! Tudo bem?\n\nAcabei de subir o material do ${proposta.titulo} aqui no sistema. Deixei tudo bem visual pra vocÃª conseguir enxergar o projeto ganhando forma, exatamente como a gente conversou.\n\nDÃ¡ uma olhada aqui:\nðŸ‘‰ ${link}`;
                window.open(`https://wa.me/${numero}?text=${encodeURIComponent(fallback)}`, '_blank');
            } finally {
                this.whatsappLoading = false;
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
});
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
