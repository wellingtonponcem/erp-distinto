<?php
/**
 * Tabela de Preços e Catálogo de Álbuns Fotográficos (ERP Distinto)
 */
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();

$db = Database::get();

// Auto-seeding para garantir coleções e acabamentos cadastrados
try {
    $checkCount = $db->query("SELECT COUNT(*) FROM servicos WHERE tipo = 'colecao'")->fetchColumn();
    if ($checkCount == 0 && file_exists(__DIR__ . '/../setup/seed_servicos_albuns.php')) {
        include_once __DIR__ . '/../setup/seed_servicos_albuns.php';
    }
} catch (Exception $e) {}

$tituloPagina = 'Tabela de Preços';
include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper" style="display:flex; min-height:100vh;" x-data="servicos()">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet !bg-background !p-6 flex flex-col flex-1">
        <?php include __DIR__ . '/../includes/layout/top_nav.php'; ?>

        <!-- Topbar -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 mb-8 mt-2">
            <div>
                <h1 class="font-display-lg text-on-surface mb-1">Tabela de Preços</h1>
                <p class="text-body-md text-on-surface-variant" x-text="categoriaAtiva === 'albuns' ? 'Catálogo completo de álbuns fotográficos — compare coleções, acabamentos e preços' : 'Catálogo com preço mínimo calculated automaticamente'"></p>
            </div>
            <button class="bg-primary hover:bg-primary-container text-on-primary-container font-bold px-6 py-2.5 rounded-lg text-body-md transition-all active:scale-95 duration-150 shadow-lg flex items-center gap-2" @click="abrirModal()">
                <i data-lucide="plus" class="w-4 h-4"></i> <span x-text="categoriaAtiva === 'albuns' ? 'Nova Coleção de Álbum' : 'Novo Serviço'"></span>
            </button>
        </div>
        
        <!-- Filtro de Categoria (Abas) -->
        <div class="glass-card p-1 rounded-xl flex gap-1.5 mb-6 max-w-lg">
            <button @click="categoriaAtiva = 'marketing'" 
                    :class="categoriaAtiva === 'marketing' ? 'bg-primary text-on-primary shadow-lg' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/40'"
                    class="flex-1 px-4 py-2.5 rounded-lg font-bold text-xs flex items-center justify-center gap-2 transition-all duration-200">
                <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                Marketing
            </button>
            <button @click="categoriaAtiva = 'wedding'" 
                    :class="categoriaAtiva === 'wedding' ? 'bg-primary text-on-primary shadow-lg' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/40'"
                    class="flex-1 px-4 py-2.5 rounded-lg font-bold text-xs flex items-center justify-center gap-2 transition-all duration-200">
                <i data-lucide="camera" class="w-3.5 h-3.5"></i>
                Wedding
            </button>
            <button @click="categoriaAtiva = '15anos'" 
                    :class="categoriaAtiva === '15anos' ? 'bg-primary text-on-primary shadow-lg' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/40'"
                    class="flex-1 px-4 py-2.5 rounded-lg font-bold text-xs flex items-center justify-center gap-2 transition-all duration-200">
                <i data-lucide="party-popper" class="w-3.5 h-3.5"></i>
                15 Anos
            </button>
            <button @click="categoriaAtiva = 'albuns'" 
                    :class="categoriaAtiva === 'albuns' ? 'bg-primary text-on-primary shadow-lg' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/40'"
                    class="flex-1 px-4 py-2.5 rounded-lg font-bold text-xs flex items-center justify-center gap-2 transition-all duration-200">
                <i data-lucide="book" class="w-3.5 h-3.5"></i>
                Álbuns
            </button>
        </div>

        <!-- Configuração de horas mensais (Apenas para Serviços) -->
        <div class="glass-card p-4 rounded-xl flex flex-wrap items-center justify-between gap-4 mb-6 text-xs text-on-surface-variant" x-show="categoriaAtiva !== 'albuns'">
            <div class="flex items-center gap-4 flex-wrap">
                <div class="flex items-center gap-1.5">
                    <i data-lucide="info" class="w-4 h-4 text-primary shrink-0"></i>
                    <span>Horas mensais de capacidade da agência:</span>
                </div>
                <div class="flex items-center gap-2">
                    <input class="input w-24 !py-1 !px-2.5" type="number" min="1" x-model="horasMensais" placeholder="160">
                    <button class="btn-secondary !py-1 !px-3 flex items-center gap-1" @click="abrirPlanejadorIA()">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-primary"></i> Planejar com IA
                    </button>
                </div>
            </div>
            <div class="font-bold">
                Custo fixo total mensal:
                <span class="text-error font-data-tabular ml-1" x-text="formatarMoeda(totalCustosFixos)"></span>
            </div>
        </div>

        <!-- Tabela de Serviços (para Marketing, Wedding, 15 Anos) -->
        <div x-show="categoriaAtiva !== 'albuns'">
            <div class="glass-card rounded-xl overflow-hidden mb-6">
                <div class="grid grid-cols-[2.5fr_100px_1.5fr_1.5fr_1.5fr_100px_100px] items-center px-6 py-4 bg-surface-container-low border-b border-outline-variant/20">
                    <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px]">Serviço</span>
                    <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px]">Horas</span>
                    <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px]">Preço Mínimo</span>
                    <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px]">P. Recorrente</span>
                    <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px]">P. Pontual</span>
                    <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px]">Markup</span>
                    <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px] text-right">Ações</span>
                </div>

                <template x-if="carregando">
                    <div class="p-10 text-center text-on-surface-variant">Carregando...</div>
                </template>

                <template x-if="!carregando && listaFiltrada.length === 0">
                    <div class="p-10 text-center text-on-surface-variant">
                        <i data-lucide="briefcase" class="w-8 h-8 mx-auto mb-3 opacity-40"></i>
                        Nenhum serviço cadastrado nesta categoria
                    </div>
                </template>

                <div class="divide-y divide-outline-variant/20">
                    <template x-for="s in listaFiltrada" :key="s.id">
                        <div class="grid grid-cols-[2.5fr_100px_1.5fr_1.5fr_1.5fr_100px_100px] items-center px-6 py-4 hover:bg-surface-container-high/20 transition-colors group">
                            <div class="min-w-0 pr-4">
                                <div class="text-sm font-bold text-on-surface truncate" x-text="s.nome"></div>
                                <div class="text-xs text-on-surface-variant truncate mt-0.5" :title="s.descricao" x-text="s.descricao || ''"></div>
                            </div>
                            <div class="text-xs text-on-surface-variant font-data-tabular" x-text="s.horas_estimadas + 'h'"></div>
                            <div class="text-xs text-on-surface-variant font-data-tabular" x-text="formatarMoeda(calcularPrecoMinimo(s))"></div>
                            <div>
                                <span class="text-sm font-bold font-data-tabular" :class="s.periodicidade === 'mensal' ? 'text-primary' : 'text-on-surface-variant/40'" x-text="s.preco_venda > 0 ? formatarMoeda(s.preco_venda) : '—'"></span>
                            </div>
                            <div>
                                <span class="text-sm font-bold font-data-tabular" :class="s.periodicidade === 'pontual' ? 'text-primary' : 'text-on-surface-variant/40'" x-text="s.preco_venda_pontual > 0 ? formatarMoeda(s.preco_venda_pontual) : '—'"></span>
                            </div>
                            <div>
                                <span class="text-xs font-bold text-on-surface-variant font-data-tabular" x-text="s.markup + '%'"></span>
                            </div>
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="abrirModal(s)" class="p-1.5 text-on-surface-variant hover:text-on-surface hover:bg-surface-variant rounded transition-colors" title="Editar">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                                <button @click="excluir(s.id)" class="p-1.5 text-error/70 hover:text-error hover:bg-error-container/10 rounded transition-colors" title="Excluir">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <p class="text-xs text-on-surface-variant mt-2">
                💡 Preço mínimo = (horas / capacidade mensal × custos fixos) + custo variável + markup aplicado.
            </p>
        </div>

        <!-- Cards de Álbuns (Visual Premium idêntico a o.php) -->
        <div x-show="categoriaAtiva === 'albuns'" class="space-y-10">
            <div>
                <!-- Filtro interno de categoria de álbuns -->
                <div class="flex flex-wrap items-center gap-2 mb-6">
                    <span class="text-xs font-bold text-on-surface-variant mr-1">Filtrar Categoria:</span>
                    <button @click="filtroAlbum = 'todos'" 
                            :class="filtroAlbum === 'todos' ? 'bg-primary text-on-primary shadow-lg' : 'bg-surface-container-high/40 text-on-surface-variant hover:bg-surface-container-high'" 
                            class="px-3.5 py-1.5 rounded-lg font-bold text-xs transition-all duration-200">
                        Todas as Categorias
                    </button>
                    <button @click="filtroAlbum = '15anos'" 
                            :class="filtroAlbum === '15anos' ? 'bg-primary text-on-primary shadow-lg' : 'bg-surface-container-high/40 text-on-surface-variant hover:bg-surface-container-high'" 
                            class="px-3.5 py-1.5 rounded-lg font-bold text-xs transition-all duration-200">
                        15 Anos
                    </button>
                    <button @click="filtroAlbum = 'wedding'" 
                            :class="filtroAlbum === 'wedding' || filtroAlbum === 'casamento' ? 'bg-primary text-on-primary shadow-lg' : 'bg-surface-container-high/40 text-on-surface-variant hover:bg-surface-container-high'" 
                            class="px-3.5 py-1.5 rounded-lg font-bold text-xs transition-all duration-200">
                        Casamento
                    </button>
                </div>

                <template x-if="carregando">
                    <div class="p-10 text-center text-on-surface-variant">Carregando catálogo de álbuns...</div>
                </template>

                <template x-if="!carregando && albumsFiltrados.length === 0">
                    <div class="p-10 text-center text-on-surface-variant">
                        <i data-lucide="book" class="w-10 h-10 mx-auto mb-3 opacity-40"></i>
                        <p class="text-sm font-bold mb-1">Nenhum álbum encontrado nesta categoria</p>
                        <p class="text-xs">Cadastre um novo álbum clicando no botão "Nova Coleção de Álbum"</p>
                    </div>
                </template>

                <!-- Grid de Cards de Álbuns Premium Idêntico a o.php -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" x-show="!carregando">
                    <template x-for="(s, idx) in albumsFiltrados" :key="s.id">
                        <div class="glass-card rounded-3xl p-6 flex flex-col justify-between relative overflow-hidden group border-2 border-primary/30 hover:border-primary transition-all shadow-xl">
                            
                            <!-- Product Preview Image with Top Badge inside -->
                            <div class="w-full h-56 rounded-2xl overflow-hidden bg-zinc-950 relative group-hover:scale-[1.02] transition-transform mb-5 border border-white/10 shadow-inner">
                                <img :src="parseImg(s.imagens_json, 'capa')" class="absolute inset-0 w-full h-full object-cover blur-xl opacity-45 scale-125 pointer-events-none">
                                <img :src="parseImg(s.imagens_json, 'capa')" 
                                     :alt="s.nome"
                                     class="w-full h-full object-contain relative z-10 p-1.5 opacity-95 group-hover:opacity-100 transition-opacity"
                                     onerror="this.src='https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=800&q=80'">
                                <div class="absolute inset-0 bg-gradient-to-t from-zinc-950/80 via-transparent to-transparent z-20 pointer-events-none"></div>

                                <template x-if="s.categoria_original === 'Top Master'">
                                    <div class="absolute top-3 right-3 z-30 bg-gradient-to-r from-amber-500 to-yellow-400 text-zinc-950 font-black text-[10px] uppercase tracking-widest px-3 py-1 rounded-full shadow-2xl border border-yellow-300/40">
                                        TOP MASTER LUX
                                    </div>
                                </template>
                                <template x-if="s.categoria_original === 'Intermediário'">
                                    <div class="absolute top-3 right-3 z-30 bg-purple-950/90 text-purple-200 border border-purple-400/60 font-bold text-[10px] uppercase tracking-widest px-3 py-1 rounded-full backdrop-blur-md shadow-lg">
                                        MAIS PROCURADO
                                    </div>
                                </template>
                                <template x-if="s.categoria_original === 'Simples'">
                                    <div class="absolute top-3 right-3 z-30 bg-zinc-900/90 text-zinc-300 border border-zinc-700 font-bold text-[10px] uppercase tracking-widest px-3 py-1 rounded-full backdrop-blur-md shadow-lg">
                                        COLEÇÃO ESSENCIAL
                                    </div>
                                </template>
                            </div>

                            <!-- Title & Description -->
                            <div class="space-y-4 flex-1">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-purple-400 block mb-1" 
                                          x-text="s.categoria === 'casamento' ? 'Álbum de Casamento' : (s.categoria === '15anos' ? 'Álbum 15 Anos' : 'Álbum Fotográfico')"></span>
                                    <h4 class="text-2xl font-heading font-extrabold text-white tracking-tight" x-text="s.nome"></h4>
                                    <p class="text-xs text-zinc-400 mt-2 leading-relaxed" x-text="s.descricao || ''"></p>
                                </div>

                                <!-- Technical Finishes Checklist -->
                                <div class="space-y-2 pt-3 border-t border-white/10 text-xs">
                                    <span class="text-[10px] font-bold uppercase text-zinc-400 block tracking-wider mb-2">Acabamento do Álbum:</span>
                                    <template x-for="acab in parseAcabamentos(s.acabamento_json)" :key="acab.chave">
                                        <div class="flex items-center space-x-2.5 text-zinc-300 bg-zinc-900/60 p-2 rounded-xl border border-white/5">
                                            <template x-if="acab.imagem">
                                                <img :src="acab.imagem" class="w-8 h-8 rounded-lg object-cover border border-purple-500/40 shrink-0">
                                            </template>
                                            <template x-if="!acab.imagem">
                                                <i data-lucide="check-circle" class="w-4 h-4 text-purple-400 shrink-0"></i>
                                            </template>
                                            <span class="text-[11px] leading-snug">
                                                <strong class="text-zinc-100 capitalize" x-text="(acab.item || acab.chave || '').replace('_', ' ') + ':'"></strong> 
                                                <span x-text="acab.texto || acab"></span>
                                            </span>
                                        </div>
                                    </template>
                                </div>

                                <!-- Estojo / Case -->
                                <div class="bg-zinc-900/80 p-3.5 rounded-2xl border border-white/10 space-y-2 mt-3">
                                    <div class="flex items-center space-x-2 text-xs font-bold text-amber-300">
                                        <i data-lucide="box" class="w-4 h-4 text-amber-300"></i>
                                        <span x-text="parseEstojo(s.estojo_json, 'tipo') || 'Estojo Personalizado'"></span>
                                    </div>
                                    <template x-if="parseEstojo(s.estojo_json, 'imagem_referencia')">
                                        <div class="w-full h-44 rounded-xl overflow-hidden bg-zinc-950 relative border border-white/10 my-1">
                                            <img :src="parseEstojo(s.estojo_json, 'imagem_referencia')" class="absolute inset-0 w-full h-full object-cover blur-xl opacity-45 scale-125 pointer-events-none">
                                            <img :src="parseEstojo(s.estojo_json, 'imagem_referencia')" class="w-full h-full object-contain relative z-10 p-1.5" onerror="this.parentElement.style.display='none'">
                                        </div>
                                    </template>
                                    <p class="text-[11px] text-zinc-400 leading-tight" x-text="parseEstojo(s.estojo_json, 'descricao') || ''"></p>
                                </div>
                            </div>

                            <!-- Price & Action Buttons -->
                            <div class="pt-6 mt-6 border-t border-white/10 flex flex-col space-y-3">
                                <div class="flex items-baseline justify-between">
                                    <span class="text-xs text-zinc-400 font-medium">Investimento:</span>
                                    <div class="text-right">
                                        <div class="text-2xl font-heading font-extrabold text-white tracking-tight" x-text="formatarMoeda(s.preco_venda || 0)"></div>
                                        <span class="text-[10px] text-purple-300 font-semibold block" x-text="'+ ' + formatarMoeda(s.valor_lamina_extra || 0) + ' / lâmina extra'"></span>
                                    </div>
                                </div>

                                <div class="flex gap-2 pt-2">
                                    <button type="button" @click="abrirModal(s)" class="flex-1 py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs uppercase tracking-wider transition-colors flex items-center justify-center space-x-2 shadow-lg cursor-pointer">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                        <span>Editar Coleção</span>
                                    </button>
                                    <button type="button" @click="excluir(s.id)" class="py-3 px-4 rounded-xl bg-red-500/20 hover:bg-red-500/30 text-red-300 font-bold text-xs transition-colors flex items-center justify-center cursor-pointer" title="Excluir">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Seção dedicada para Editar / Gerenciar a "Galeria de Detalhes & Acabamentos" -->
            <div class="pt-8 border-t border-outline-variant/20">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-xl font-extrabold text-on-surface flex items-center gap-2">
                            <i data-lucide="image" class="w-5 h-5 text-purple-400"></i>
                            Galeria de Detalhes & Acabamentos
                        </h3>
                        <p class="text-xs text-on-surface-variant">Itens em destaque exibidos na seção "Galeria de Detalhes & Acabamentos" das propostas/orçamentos</p>
                    </div>
                    <button type="button" @click="abrirModalAcabamento()" class="bg-purple-600 hover:bg-purple-500 text-white font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 shadow cursor-pointer">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>Novo Acabamento da Galeria</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" x-show="!carregando">
                    <template x-for="acab in acabamentosGaleria" :key="acab.id">
                        <div class="glass-card rounded-2xl border border-white/10 overflow-hidden flex flex-col justify-between hover:border-purple-500/50 transition-all shadow-md">
                            <div class="h-44 bg-zinc-900 relative overflow-hidden">
                                <img :src="parseImg(acab.imagens_json, 'capa')" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=800&q=80'">
                                <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-transparent to-transparent opacity-80"></div>
                            </div>
                            <div class="p-5 space-y-1.5 flex-1">
                                <h4 class="font-bold text-base text-white" x-text="acab.nome"></h4>
                                <p class="text-xs text-zinc-400 leading-relaxed" x-text="acab.descricao || ''"></p>
                            </div>
                            <div class="p-4 pt-0 flex gap-2 justify-end border-t border-white/5 mt-2">
                                <button type="button" @click="abrirModal(acab)" class="px-3 py-1.5 rounded-lg bg-purple-600/20 hover:bg-purple-600/40 text-purple-300 font-bold text-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                    <span>Editar</span>
                                </button>
                                <button type="button" @click="excluir(acab.id)" class="px-3 py-1.5 rounded-lg bg-red-500/20 hover:bg-red-500/40 text-red-300 font-bold text-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    <span>Excluir</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Criação / Edição -->
    <div class="modal-overlay" x-show="modalAberto" x-cloak @click.self="modalAberto=false">
        <div class="modal w-full max-w-3xl p-6 bg-surface-container-low border border-outline-variant/30 rounded-2xl shadow-2xl relative animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] overflow-y-auto">
            <button @click="modalAberto=false" class="absolute top-6 right-6 text-on-surface-variant hover:text-on-surface transition-colors cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            
            <div class="flex items-center gap-3 mb-6 pr-8">
                <h2 class="text-title-sm font-headline-md font-bold text-on-surface" 
                    x-text="form.id ? (form.tipo === 'colecao' ? 'Editar Coleção de Álbum' : (form.tipo === 'acabamento' ? 'Editar Acabamento da Galeria' : 'Editar Serviço')) : (form.tipo === 'colecao' ? 'Nova Coleção de Álbum' : (form.tipo === 'acabamento' ? 'Novo Acabamento da Galeria' : 'Novo Serviço'))"></h2>
            </div>

            <form @submit.prevent="salvar()">
                <!-- Categoria e Tipo -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="label">Categoria</label>
                        <select class="select w-full" x-model="form.categoria">
                            <option value="15anos">15 Anos</option>
                            <option value="wedding">Wedding / Casamento</option>
                            <option value="marketing">Marketing</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Tipo de Item</label>
                        <select class="select w-full font-bold text-primary" x-model="form.tipo" @change="ajustarTipo()">
                            <option value="colecao">Coleção de Álbum</option>
                            <option value="acabamento">Acabamento da Galeria (Foto & Descrição)</option>
                            <option value="servico">Serviço / Upgrade</option>
                        </select>
                    </div>
                </div>

                <!-- ========== FORMULÁRIO DE ACABAMENTO DA GALERIA ========== -->
                <div x-show="form.tipo === 'acabamento'" class="space-y-4">
                    <div>
                        <label class="label">Nome do Acabamento *</label>
                        <input class="input w-full font-bold text-base" x-model="form.nome" required placeholder="Ex: Papel Linho Silk, Corte Lateral Ouro">
                    </div>

                    <div>
                        <label class="label">Descrição Comercial do Acabamento</label>
                        <textarea class="textarea w-full" x-model="form.descricao" rows="3" placeholder="Descreva os detalhes e características desse acabamento..."></textarea>
                    </div>

                    <!-- Imagem Ilustrativa -->
                    <div class="bg-purple-500/5 p-4 rounded-xl border border-purple-500/10">
                        <label class="label text-purple-300 font-bold mb-2">Foto Ilustrativa do Acabamento</label>
                        <div class="flex items-center gap-3">
                            <div class="w-16 h-16 rounded-xl overflow-hidden bg-zinc-900 shrink-0 border border-white/20">
                                <img :src="form.img_capa || ''" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=800&q=80'">
                            </div>
                            <input class="input flex-1 text-xs" x-model="form.img_capa" placeholder="Insira a URL da imagem ou faça upload...">
                            <button type="button" @click="uploadImagem('galeria', 'capa')" class="bg-primary hover:bg-primary-container text-on-primary-container px-3 py-2 rounded-lg text-xs font-bold transition-all shadow">
                                Upload
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ========== FORMULÁRIO DE COLEÇÃO DE ÁLBUM ========== -->
                <div x-show="form.tipo === 'colecao'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="label">Nome da Coleção *</label>
                            <input class="input w-full font-bold text-base" x-model="form.nome" placeholder="Ex: Classic Wood & Gold">
                        </div>
                        <div>
                            <label class="label">Nível em Destaque</label>
                            <select class="select w-full font-bold" x-model="form.categoria_original">
                                <option value="Simples">Simples (Essencial)</option>
                                <option value="Intermediário">Intermediário (Mais Procurado)</option>
                                <option value="Top Master">Top Master (Luxo)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="label">Descrição Comercial</label>
                        <textarea class="textarea w-full" x-model="form.descricao" rows="2" placeholder="Descrição empolgante da coleção para apresentar ao cliente..."></textarea>
                    </div>

                    <!-- Precificação -->
                    <div class="bg-primary/5 p-4 rounded-xl border border-primary/10 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="label">Custo Base (Produção) R$</label>
                                <input class="input w-full" type="number" step="0.01" min="0" x-model="form.custo_producao" placeholder="0,00">
                            </div>
                            <div>
                                <label class="label">Investimento Cliente R$</label>
                                <input class="input w-full !font-extrabold text-primary text-base" type="number" step="0.01" min="0" x-model="form.preco_venda" placeholder="0,00">
                            </div>
                            <div>
                                <label class="label">Valor Lâmina Extra R$</label>
                                <input class="input w-full font-bold" type="number" step="0.01" min="0" x-model="form.valor_lamina_extra" placeholder="35,00">
                            </div>
                        </div>
                    </div>

                    <!-- Imagem da Capa -->
                    <div class="bg-indigo-500/5 p-4 rounded-xl border border-indigo-500/10">
                        <label class="label text-indigo-400 font-bold mb-2">Imagem de Capa do Álbum (Foto Principal)</label>
                        <div class="flex items-center gap-3">
                            <div class="w-16 h-16 rounded-xl overflow-hidden bg-zinc-900 shrink-0 border border-white/20">
                                <img :src="form.img_capa || ''" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=800&q=80'">
                            </div>
                            <input class="input flex-1 text-xs" x-model="form.img_capa" placeholder="Insira a URL da foto de capa...">
                            <button type="button" @click="uploadImagem('galeria', 'capa')" class="bg-primary hover:bg-primary-container text-on-primary-container px-3 py-2 rounded-lg text-xs font-bold transition-all shadow">
                                Upload
                            </button>
                        </div>
                    </div>

                    <!-- Acabamentos Detalhados -->
                    <div class="bg-purple-500/5 p-4 rounded-xl border border-purple-500/10">
                        <label class="label text-purple-300 flex justify-between items-center mb-3">
                            <span class="flex items-center gap-1.5 font-bold"><i data-lucide="layers" class="w-4 h-4"></i> Acabamentos Detalhados</span>
                            <button type="button" @click="adicionarAcabamento()" class="bg-purple-600 hover:bg-purple-500 text-white px-3 py-1 rounded-lg text-xs font-bold flex items-center gap-1 shadow">
                                + Adicionar Acabamento
                            </button>
                        </label>
                        
                        <div class="flex flex-col gap-3">
                            <template x-for="(acab, index) in form.acabamentos_lista" :key="index">
                                <div class="bg-surface-container-high/40 p-3 rounded-xl border border-outline-variant/10 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-purple-300 uppercase tracking-wider" x-text="'Item #' + (index + 1)"></span>
                                        <button type="button" @click="removerAcabamento(index)" class="text-error/70 hover:text-error p-1 cursor-pointer">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-[9px] font-bold text-on-surface-variant uppercase">Nome do Acabamento</label>
                                            <input class="input w-full text-xs font-bold" x-model="acab.item" placeholder="Ex: Capa em Madeira, Corte Lateral Ouro">
                                        </div>
                                        <div>
                                            <label class="text-[9px] font-bold text-on-surface-variant uppercase">Descrição Técnica</label>
                                            <input class="input w-full text-xs" x-model="acab.texto" placeholder="Descrição do acabamento">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-bold text-on-surface-variant uppercase">Foto do Acabamento (Opcional)</label>
                                        <div class="flex items-center gap-2 mt-1">
                                            <div class="w-8 h-8 rounded-lg overflow-hidden bg-zinc-900 shrink-0 border border-white/20">
                                                <img :src="acab.imagem || ''" class="w-full h-full object-cover" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><rect width=%2240%22 height=%2240%22 fill=%22%23333%22/></svg>'">
                                            </div>
                                            <input class="input flex-1 text-xs" x-model="acab.imagem" placeholder="URL da foto do detalhe">
                                            <button type="button" @click="uploadImagem('acabamento', index)" class="bg-surface-container-high hover:bg-surface-variant text-on-surface-variant px-2 py-1.5 rounded-lg text-[10px] font-bold">
                                                Upload
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Estojo / Case -->
                    <div class="bg-amber-500/5 p-4 rounded-xl border border-amber-500/10 space-y-3">
                        <label class="label text-amber-300 font-bold flex items-center gap-1.5">
                            <i data-lucide="box" class="w-4 h-4 text-amber-300"></i> Estojo / Case de Proteção
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="text-[9px] font-bold text-on-surface-variant uppercase">Tipo do Estojo</label>
                                <input class="input w-full text-xs font-bold" x-model="form.estojo_tipo" placeholder="Ex: Maleta Classic Box Luxo">
                            </div>
                            <div>
                                <label class="text-[9px] font-bold text-on-surface-variant uppercase">Descrição do Estojo</label>
                                <input class="input w-full text-xs" x-model="form.estojo_descricao" placeholder="Descrição do estojo">
                            </div>
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-on-surface-variant uppercase">Foto do Estojo</label>
                            <div class="flex items-center gap-2 mt-1">
                                <div class="w-10 h-10 rounded-lg overflow-hidden bg-zinc-900 shrink-0 border border-white/20">
                                    <img :src="form.estojo_imagem || ''" class="w-full h-full object-cover" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><rect width=%2240%22 height=%2240%22 fill=%22%23333%22/></svg>'">
                                </div>
                                <input class="input flex-1 text-xs" x-model="form.estojo_imagem" placeholder="URL da foto do estojo">
                                <button type="button" @click="uploadImagem('estojo')" class="bg-surface-container-high hover:bg-surface-variant text-on-surface-variant px-2 py-1.5 rounded-lg text-[10px] font-bold">
                                    Upload
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========== FORMULÁRIO DE SERVIÇO / PLANO (PADRÃO) ========== -->
                <div x-show="form.tipo !== 'colecao' && form.tipo !== 'acabamento'" class="space-y-4">
                    <div>
                        <label class="label">Nome do Serviço *</label>
                        <input class="input w-full" x-model="form.nome" placeholder="Ex: Consultoria em Marketing">
                    </div>
                    <div>
                        <label class="label">Descrição</label>
                        <textarea class="textarea w-full" x-model="form.descricao" rows="2" placeholder="O que inclui o serviço..."></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Preço Recorrente R$</label>
                            <input class="input w-full font-bold" type="number" step="0.01" x-model="form.preco_venda" placeholder="0,00">
                        </div>
                        <div>
                            <label class="label">Preço Pontual R$</label>
                            <input class="input w-full font-bold" type="number" step="0.01" x-model="form.preco_venda_pontual" placeholder="0,00">
                        </div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-outline-variant/20">
                    <button type="button" @click="modalAberto=false" class="px-5 py-2.5 rounded-xl bg-surface-container-high hover:bg-surface-variant text-on-surface-variant font-bold text-xs cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="salvando" class="px-7 py-2.5 rounded-xl bg-primary hover:opacity-90 text-on-primary font-extrabold text-xs uppercase tracking-wider shadow-lg cursor-pointer">
                        <span x-text="salvando ? 'Salvando...' : 'Salvar Alterações'"></span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
function servicos() {
    return {
        lista: [],
        carregando: true,
        salvando: false,
        modalAberto: false,
        form: {},
        totalCustosFixos: 0,
        horasMensais: parseInt(localStorage.getItem('cap_horas_mensais') || 160),
        categoriaAtiva: 'albuns',
        filtroAlbum: 'todos',

        get listaFiltrada() {
            return this.lista.filter(s => (s.categoria || 'marketing') === this.categoriaAtiva && s.tipo !== 'colecao' && s.tipo !== 'acabamento');
        },

        get albumsFiltrados() {
            return this.lista.filter(s => {
                const isColecao = (s.tipo === 'colecao' || s.tipo === 'plano' || (s.acabamento_json && s.tipo !== 'acabamento'));
                if (!isColecao) return false;
                if (this.filtroAlbum === 'todos') return true;
                return (s.categoria || '') === this.filtroAlbum;
            }).sort((a, b) => (parseFloat(a.preco_venda) || 0) - (parseFloat(b.preco_venda) || 0));
        },

        get acabamentosGaleria() {
            return this.lista.filter(s => s.tipo === 'acabamento');
        },

        parseImg(imagensJson, chave) {
            if (!imagensJson) return 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=800&q=80';
            try {
                const obj = typeof imagensJson === 'object' ? imagensJson : JSON.parse(imagensJson);
                return obj[chave] || obj.imagem_exemplo || obj.capa || 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=800&q=80';
            } catch(e) { return 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=800&q=80'; }
        },

        parseAcabamentos(acabamentoJson) {
            if (!acabamentoJson) return [];
            try {
                const parsed = typeof acabamentoJson === 'object' ? acabamentoJson : JSON.parse(acabamentoJson);
                if (Array.isArray(parsed)) {
                    return parsed.map(a => ({
                        chave: a.chave || a.item || 'detalhe',
                        item: a.item || a.chave || 'Acabamento',
                        texto: a.texto || (typeof a === 'string' ? a : ''),
                        imagem: a.imagem || ''
                    }));
                }
                if (parsed && typeof parsed === 'object') {
                    return Object.entries(parsed).map(([chave, texto]) => ({
                        chave,
                        item: chave,
                        texto: typeof texto === 'string' ? texto : (texto.texto || ''),
                        imagem: texto.imagem || ''
                    }));
                }
                return [];
            } catch(e) { return []; }
        },

        parseEstojo(estojoJson, chave) {
            if (!estojoJson) return '';
            try {
                const obj = typeof estojoJson === 'object' ? estojoJson : JSON.parse(estojoJson);
                return obj[chave] || '';
            } catch(e) { return ''; }
        },

        async init() {
            await Promise.all([this.carregar(), this.carregarCustosFixos()]);
            this.$watch('categoriaAtiva', () => {
                this.$nextTick(() => lucide.createIcons());
            });
        },

        async carregar() {
            this.carregando = true;
            try {
                const r = await fetch('<?= raizUrl('/api/precificacao/servicos.php') ?>');
                this.lista = await r.json();
            } catch(e) { toast('Erro ao carregar serviços', 'erro'); }
            this.carregando = false;
            this.$nextTick(() => lucide.createIcons());
        },

        async carregarCustosFixos() {
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/custos-fixos.php') ?>');
                const custos = await r.json();
                this.totalCustosFixos = custos
                    .filter(c => c.ativo == '1')
                    .reduce((s, c) => s + (c.recorrencia === 'anual' ? parseFloat(c.valor)/12 : parseFloat(c.valor)), 0);
            } catch(e) {}
        },

        calcularPrecoMinimo(s) {
            const horas = parseFloat(s.horas_estimadas || 0);
            const custo = parseFloat(s.custo_producao || 0) + parseFloat(s.custos_variaveis || 0);
            const rateio = this.horasMensais > 0 ? (horas / this.horasMensais) * this.totalCustosFixos : 0;
            const markup = parseFloat(s.markup || 0) / 100;
            return (rateio + custo) * (1 + markup);
        },

        abrirModal(item = null) {
            if (!item && this.categoriaAtiva === 'albuns') {
                this.form = this.formVazio();
                this.form.tipo = 'colecao';
                this.parseColecaoForm(null);
            } else if (item) {
                this.form = JSON.parse(JSON.stringify(item));
                if (this.form.tipo === 'colecao') {
                    this.parseColecaoForm(item);
                } else if (this.form.tipo === 'acabamento') {
                    this.parseAcabamentoForm(item);
                }
            } else {
                this.form = this.formVazio();
                this.form.tipo = 'servico';
            }
            this.modalAberto = true;
            this.$nextTick(() => lucide.createIcons());
        },

        abrirModalAcabamento() {
            this.form = {
                id: '',
                nome: '',
                categoria: '15anos',
                tipo: 'acabamento',
                descricao: '',
                img_capa: ''
            };
            this.modalAberto = true;
            this.$nextTick(() => lucide.createIcons());
        },

        formVazio() {
            return {
                id: '',
                nome: '',
                categoria: '15anos',
                tipo: 'colecao',
                descricao: '',
                custo_producao: 445,
                preco_venda: 1250,
                valor_lamina_extra: 35,
                categoria_original: 'Simples',
                acabamentos_lista: [
                    { chave: 'capa', item: 'Capa', texto: 'Foto Total com Revestimento Nobre', imagem: '' },
                    { chave: 'fechamento', item: 'Fechamento', texto: 'Sistema de Ímã Invisível', imagem: '' },
                    { chave: 'papel', item: 'Papel', texto: 'Fotográfico Fosco Silk (Anti-Digital)', imagem: '' },
                    { chave: 'laminação', item: 'Laminação', texto: 'UV Proteção Extra', imagem: '' }
                ],
                estojo_tipo: 'Case Slim Personalizado',
                estojo_descricao: 'Estojo tipo luva com acabamento aveludado.',
                estojo_imagem: '',
                img_capa: '',
                img_aberto: '',
                img_detalhe: ''
            };
        },

        parseColecaoForm(item) {
            if (!item) return;
            
            // Parse acabamentos
            let rawAcab = [];
            try {
                const parsed = typeof item.acabamento_json === 'object' ? item.acabamento_json : JSON.parse(item.acabamento_json || '[]');
                if (Array.isArray(parsed)) {
                    rawAcab = parsed.map(a => ({
                        chave: a.chave || a.item || 'detalhe',
                        item: a.item || a.chave || 'Acabamento',
                        texto: a.texto || (typeof a === 'string' ? a : ''),
                        imagem: a.imagem || ''
                    }));
                } else if (parsed && typeof parsed === 'object') {
                    rawAcab = Object.entries(parsed).map(([k, v]) => ({
                        chave: k,
                        item: k.replace('_', ' ').toUpperCase(),
                        texto: typeof v === 'string' ? v : (v.texto || ''),
                        imagem: v.imagem || ''
                    }));
                }
            } catch(e) { rawAcab = []; }
            this.form.acabamentos_lista = rawAcab;

            // Parse estojo
            try {
                const est = typeof item.estojo_json === 'object' ? item.estojo_json : JSON.parse(item.estojo_json || '{}');
                this.form.estojo_tipo = est.tipo || '';
                this.form.estojo_descricao = est.descricao || '';
                this.form.estojo_imagem = est.imagem_referencia || '';
            } catch(e) {}

            // Parse imagens
            try {
                const img = typeof item.imagens_json === 'object' ? item.imagens_json : JSON.parse(item.imagens_json || '{}');
                this.form.img_capa = img.capa || '';
                this.form.img_aberto = img.aberto || '';
                this.form.img_detalhe = img.detalhe || '';
            } catch(e) {}
        },

        parseAcabamentoForm(item) {
            if (!item) return;
            try {
                const img = typeof item.imagens_json === 'object' ? item.imagens_json : JSON.parse(item.imagens_json || '{}');
                this.form.img_capa = img.imagem_exemplo || img.capa || '';
            } catch(e) {}
        },

        ajustarTipo() {
            if (this.form.tipo === 'colecao' && (!this.form.acabamentos_lista || this.form.acabamentos_lista.length === 0)) {
                this.parseColecaoForm(null);
            }
        },

        adicionarAcabamento() {
            if (!this.form.acabamentos_lista) this.form.acabamentos_lista = [];
            this.form.acabamentos_lista.push({ chave: '', item: '', texto: '', imagem: '' });
        },

        removerAcabamento(index) {
            this.form.acabamentos_lista.splice(index, 1);
        },

        uploadImagem(tipoTarget, indexOuChave = null) {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/png, image/jpeg, image/webp';
            input.onchange = async () => {
                if (!input.files || input.files.length === 0) return;
                const file = input.files[0];
                const formData = new FormData();
                formData.append('imagem', file);

                try {
                    if (typeof toast === 'function') toast('Enviando imagem...', 'info');
                    const r = await fetch('<?= raizUrl('/api/precificacao/upload-imagem.php') ?>', {
                        method: 'POST',
                        body: formData
                    });
                    const res = await r.json();
                    if (r.ok && res.url) {
                        toast('Imagem enviada com sucesso!', 'sucesso');
                        if (tipoTarget === 'galeria') {
                            if (indexOuChave === 'capa') this.form.img_capa = res.url;
                            else if (indexOuChave === 'aberto') this.form.img_aberto = res.url;
                            else if (indexOuChave === 'detalhe') this.form.img_detalhe = res.url;
                            else this.form.img_capa = res.url;
                        } else if (tipoTarget === 'estojo') {
                            this.form.estojo_imagem = res.url;
                        } else if (tipoTarget === 'acabamento' && typeof indexOuChave === 'number') {
                            if (this.form.acabamentos_lista && this.form.acabamentos_lista[indexOuChave]) {
                                this.form.acabamentos_lista[indexOuChave].imagem = res.url;
                            }
                        }
                    } else {
                        toast(res.erro || 'Falha no upload da imagem', 'erro');
                    }
                } catch(e) {
                    toast('Erro ao enviar imagem: ' + e.message, 'erro');
                }
            };
            input.click();
        },

        async salvar() {
            this.salvando = true;
            try {
                const metodo = this.form.id ? 'PUT' : 'POST';
                const payload = { ...this.form };
                
                if (payload.tipo === 'colecao') {
                    payload.acabamento_json = JSON.stringify(payload.acabamentos_lista || []);
                    payload.estojo_json = JSON.stringify({
                        tipo: payload.estojo_tipo || '',
                        descricao: payload.estojo_descricao || '',
                        imagem_referencia: payload.estojo_imagem || ''
                    });
                    payload.imagens_json = JSON.stringify({
                        capa: payload.img_capa || '',
                        aberto: payload.img_aberto || '',
                        detalhe: payload.img_detalhe || ''
                    });
                } else if (payload.tipo === 'acabamento') {
                    payload.imagens_json = JSON.stringify({
                        imagem_exemplo: payload.img_capa || ''
                    });
                }
                
                delete payload.acabamentos_lista;
                delete payload.estojo_tipo;
                delete payload.estojo_descricao;
                delete payload.estojo_imagem;
                delete payload.img_capa;
                delete payload.img_aberto;
                delete payload.img_detalhe;

                const r = await fetch('<?= raizUrl('/api/precificacao/servicos.php') ?>', {
                    method: metodo,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const textRes = await r.text();
                let res = {};
                try { res = JSON.parse(textRes); } catch(err) { res = { erro: textRes }; }
                
                if (r.ok && (res.ok || res.id)) {
                    toast('Salvo com sucesso!', 'sucesso');
                    this.modalAberto = false;
                    await this.carregar();
                } else {
                    toast(res.erro || 'Erro ao salvar', 'erro');
                }
            } catch(e) {
                console.error(e);
                toast('Erro ao salvar: ' + e.message, 'erro');
            }
            this.salvando = false;
        },

        async excluir(id) {
            const item = this.lista.find(s => s.id === id);
            if (!confirm(`Tem certeza que deseja excluir "${item ? item.nome : 'este item'}"?`)) return;
            try {
                const r = await fetch('<?= raizUrl('/api/precificacao/servicos.php') ?>?id=' + id, { method: 'DELETE' });
                if (r.ok) { toast('Excluído com sucesso', 'sucesso'); await this.carregar(); }
                else { toast('Erro ao excluir', 'erro'); }
            } catch(e) { toast('Erro de conexão', 'erro'); }
        },

        formatarMoeda(val) { return window.formatarMoeda(val); }
    };
}
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
