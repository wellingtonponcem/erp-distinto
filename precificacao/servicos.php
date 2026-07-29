<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
exigirAutenticacao();
$db = Database::get();
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
                <p class="text-body-md text-on-surface-variant" x-text="categoriaAtiva === 'albuns' ? 'Catálogo completo de álbuns fotográficos — compare coleções, acabamentos e preços' : 'Catálogo com preço mínimo calculado automaticamente'"></p>
            </div>
            <button class="bg-primary hover:bg-primary-container text-on-primary-container font-bold px-6 py-2.5 rounded-lg text-body-md transition-all active:scale-95 duration-150 shadow-lg flex items-center gap-2" @click="abrirModal()">
                <i data-lucide="plus" class="w-4 h-4"></i> <span x-text="categoriaAtiva === 'albuns' ? 'Nova Coleção' : 'Novo Serviço'"></span>
            </button>
        </div>
        
        <!-- Filtro de Categoria (Abas) -->
        <div class="glass-card p-1 rounded-xl flex gap-1.5 mb-6 max-w-md">
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

        <!-- Configuração de horas mensais -->
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
                        Nenhum serviço cadastrado
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

        <!-- Cards de Álbuns (para aba Álbuns) -->
        <div x-show="categoriaAtiva === 'albuns'" class="space-y-6">
            <!-- Filtro interno de categoria de álbuns -->
            <div class="flex flex-wrap items-center gap-2 mb-6">
                <span class="text-xs font-bold text-on-surface-variant mr-1">Filtrar:</span>
                <template x-for="cat in ['todos', 'casamento', '15anos', 'familia', 'book']">
                    <button @click="filtroAlbum = cat" 
                            :class="filtroAlbum === cat ? 'bg-primary text-on-primary shadow-lg' : 'bg-surface-container-high/40 text-on-surface-variant hover:bg-surface-container-high'"
                            class="px-3 py-1.5 rounded-lg font-bold text-[10px] transition-all duration-200">
                        <span x-text="cat === 'todos' ? 'Todas as Categorias' : (cat === 'casamento' ? 'Casamento' : cat === '15anos' ? '15 Anos' : cat === 'familia' ? 'Família' : 'Book Fotográfico')"></span>
                    </button>
                </template>
            </div>

            <template x-if="carregando">
                <div class="p-10 text-center text-on-surface-variant">Carregando...</div>
            </template>

            <template x-if="!carregando && albumsFiltrados.length === 0">
                <div class="p-10 text-center text-on-surface-variant">
                    <i data-lucide="book" class="w-10 h-10 mx-auto mb-3 opacity-40"></i>
                    <p class="text-sm font-bold mb-1">Nenhuma coleção encontrada</p>
                    <p class="text-xs">Cadastre álbuns clicando em "Nova Coleção"</p>
                </div>
            </template>

            <!-- Grid de Cards Comparativos -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6" x-show="!carregando">
                <template x-for="(s, idx) in albumsFiltrados" :key="s.id">
                    <div class="glass-card rounded-2xl border-2 overflow-hidden flex flex-col transition-all duration-300 hover:shadow-xl hover:-translate-y-1"
                         :class="idx === 0 ? 'border-zinc-500/40' : (idx === albumsFiltrados.length - 1 ? 'border-amber-500/40' : 'border-primary/30')">
                        
                        <!-- Header -->
                        <div class="p-5 pb-3">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex gap-1.5 flex-wrap">
                                    <span class="px-2 py-0.5 rounded bg-surface-container-high text-[9px] font-bold uppercase tracking-wider text-on-surface-variant"
                                          x-text="s.categoria === 'casamento' ? 'Casamento' : s.categoria === '15anos' ? '15 Anos' : s.categoria === 'familia' ? 'Família' : s.categoria === 'book' ? 'Book' : s.categoria"></span>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider"
                                          :class="s.categoria_original === 'Simples' ? 'bg-blue-500/20 text-blue-300' : (s.categoria_original === 'Intermediário' ? 'bg-amber-500/20 text-amber-300' : 'bg-rose-500/20 text-rose-300')"
                                          x-text="s.categoria_original || 'Coleção'"></span>
                                </div>
                                <span class="text-lg font-bold font-data-tabular text-primary" x-text="formatarMoeda(s.preco_venda || 0)"></span>
                            </div>
                            <h3 class="text-sm font-extrabold text-on-surface" x-text="s.nome"></h3>
                            <p class="text-[11px] text-on-surface-variant mt-1 leading-relaxed line-clamp-2" x-text="s.descricao || ''"></p>
                        </div>

                        <!-- Imagem Principal -->
                        <div class="px-5">
                            <div class="w-full h-36 rounded-xl overflow-hidden bg-surface-container-high relative">
                                <img :src="parseImg(s.imagens_json, 'capa')" class="w-full h-full object-cover" 
                                     onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 200%22><rect width=%22200%22 height=%22200%22 fill=%22%23333%22/><text x=%22100%22 y=%22110%22 text-anchor=%22middle%22 fill=%22%23777%22 font-size=%2214%22>Sem foto</text></svg>'">
                            </div>
                        </div>

                        <!-- Tabela de Acabamentos -->
                        <div class="px-5 mt-4">
                            <div class="bg-surface-container-low rounded-xl border border-outline-variant/10 divide-y divide-outline-variant/10">
                                <template x-for="acab in parseAcabamentos(s.acabamento_json)" :key="acab.chave">
                                    <div class="flex items-center gap-3 px-3 py-2">
                                        <div class="w-7 h-7 rounded-lg overflow-hidden bg-surface-container-high shrink-0">
                                            <img :src="acab.imagem || ''" class="w-full h-full object-cover" 
                                                 onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23555%22><circle cx=%2212%22 cy=%2212%22 r=%223%22/></svg>'">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-[10px] font-bold text-on-surface-variant uppercase" x-text="acab.chave || 'detalhe'"></div>
                                            <div class="text-xs text-on-surface truncate" x-text="acab.texto || ac"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Estojo -->
                        <div class="px-5 mt-3">
                            <div class="flex items-center gap-2.5 bg-surface-container-low/60 rounded-xl px-3 py-2 border border-outline-variant/10">
                                <div class="w-8 h-8 rounded-lg overflow-hidden bg-surface-container-high shrink-0">
                                    <img :src="parseEstojo(s.estojo_json, 'imagem_referencia')" class="w-full h-full object-cover"
                                         onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23555%22><rect x=%224%22 y=%226%22 width=%2216%22 height=%2212%22 rx=%222%22/></svg>'">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-[10px] font-bold text-on-surface-variant uppercase">Estojo</div>
                                    <div class="text-xs text-on-surface truncate" x-text="parseEstojo(s.estojo_json, 'tipo') || '—'"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Lâmina extra e Ações -->
                        <div class="px-5 pb-5 mt-auto">
                            <div class="flex items-center justify-between pt-3 border-t border-outline-variant/10 mt-3">
                                <div class="text-[10px] text-on-surface-variant">
                                    Lâmina extra: <span class="font-bold font-data-tabular" x-text="formatarMoeda(s.valor_lamina_extra || 0)"></span>
                                </div>
                                <div class="flex gap-1">
                                    <button @click="abrirModal(s)" class="p-1.5 text-on-surface-variant hover:text-on-surface hover:bg-surface-variant rounded transition-colors" title="Editar">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <button @click="excluir(s.id)" class="p-1.5 text-error/70 hover:text-error hover:bg-error-container/10 rounded transition-colors" title="Excluir">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </main>

    <!-- Modal -->
    <div class="modal-overlay" x-show="modalAberto" x-cloak @click.self="modalAberto=false">
        <div class="modal w-full max-w-3xl p-6 bg-surface-container-low border border-outline-variant/30 rounded-2xl shadow-2xl relative animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] overflow-y-auto">
            <button @click="modalAberto=false" class="absolute top-6 right-6 text-on-surface-variant hover:text-on-surface transition-colors cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            
            <div class="flex items-center gap-3 mb-6 pr-8">
                <h2 class="text-title-sm font-headline-md font-bold text-on-surface" 
                    x-text="form.id ? (form.tipo === 'colecao' ? 'Editar Coleção' : form.tipo === 'acabamento' ? 'Editar Acabamento' : 'Editar Serviço') : (form.tipo === 'colecao' ? 'Nova Coleção' : form.tipo === 'acabamento' ? 'Novo Acabamento' : 'Novo Serviço')"></h2>
                <button type="button" @click="melhorarServicoIA()" :disabled="melhorandoIA" class="bg-primary/10 hover:bg-primary/20 text-primary border border-primary/20 px-3 py-1 rounded-md text-[10px] font-label-caps transition-all flex items-center gap-1 cursor-pointer" x-show="form.tipo !== 'colecao' && form.tipo !== 'acabamento'">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    <span x-text="melhorandoIA ? 'Melhorando...' : 'Editar com IA'"></span>
                </button>
            </div>

            <form @submit.prevent="salvar()">
                <!-- Categoria e Tipo - sempre visíveis -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="label">Categoria</label>
                        <select class="select w-full" x-model="form.categoria">
                            <option value="marketing">Marketing</option>
                            <option value="wedding">Wedding</option>
                            <option value="15anos">15 Anos</option>
                            <option value="casamento">Casamento (Álbum)</option>
                            <option value="familia">Família (Álbum)</option>
                            <option value="book">Book Fotográfico (Álbum)</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Tipo de Item</label>
                        <select class="select w-full" x-model="form.tipo" @change="ajustarTipo()">
                            <option value="servico">Serviço / Upgrade</option>
                            <option value="plano">Plano Base (Pacote)</option>
                            <option value="colecao">Coleção de Álbum</option>
                            <option value="acabamento">Acabamento (Galeria)</option>
                        </select>
                    </div>
                </div>

                <!-- ========== CAMPOS DE COLEÇÃO DE ÁLBUM ========== -->
                <div x-show="form.tipo === 'colecao'" class="space-y-4">
                    <!-- Nome e Descrição -->
                    <div>
                        <label class="label">Nome da Coleção *</label>
                        <input class="input w-full" x-model="form.nome" required placeholder="Ex: Classic Wood & Gold">
                    </div>
                    <div>
                        <label class="label">Descrição</label>
                        <textarea class="textarea w-full" x-model="form.descricao" rows="2" placeholder="Descrição comercial da coleção..."></textarea>
                    </div>

                    <!-- Categoria Original e Precificação -->
                    <div class="bg-primary/5 p-4 rounded-xl border border-primary/10 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="label">Nível</label>
                                <select class="select w-full" x-model="form.categoria_original">
                                    <option value="Simples">Simples</option>
                                    <option value="Intermediário">Intermediário</option>
                                    <option value="Top Master">Top Master</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Custo Base (Fullcolor) R$</label>
                                <input class="input w-full" type="number" step="0.01" min="0" x-model="form.custo_producao" placeholder="0,00">
                            </div>
                            <div>
                                <label class="label">Investimento Cliente R$</label>
                                <input class="input w-full !font-bold text-primary" type="number" step="0.01" min="0" x-model="form.preco_venda" placeholder="0,00">
                            </div>
                        </div>
                        <div>
                            <label class="label">Valor Lâmina Extra (R$)</label>
                            <input class="input w-full max-w-[200px]" type="number" step="0.01" min="0" x-model="form.valor_lamina_extra" placeholder="0,00">
                        </div>
                    </div>

                    <!-- Acabamentos -->
                    <div class="bg-tertiary/5 p-4 rounded-xl border border-tertiary/10">
                        <label class="label text-tertiary flex justify-between items-center mb-3">
                            <span class="flex items-center gap-1.5"><i data-lucide="layers" class="w-4 h-4"></i> Acabamentos da Coleção</span>
                            <button type="button" @click="adicionarAcabamento()" class="bg-tertiary text-on-tertiary px-2 py-0.5 rounded text-[10px] font-bold flex items-center gap-1">
                                + Adicionar
                            </button>
                        </label>
                        <div class="flex flex-col gap-3">
                            <template x-for="(acab, index) in form.acabamentos_lista" :key="index">
                                <div class="bg-surface-container-high/40 p-3 rounded-xl border border-outline-variant/10 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-on-surface-variant uppercase" x-text="'Item #' + (index + 1)"></span>
                                        <button type="button" @click="removerAcabamento(index)" class="text-error/70 hover:text-error p-1">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                        <div>
                                            <label class="text-[9px] font-bold text-on-surface-variant">Chave</label>
                                            <input class="input w-full text-xs" x-model="acab.chave" placeholder="Ex: capa, papel, corte_lateral">
                                        </div>
                                        <div>
                                            <label class="text-[9px] font-bold text-on-surface-variant">Item</label>
                                            <input class="input w-full text-xs" x-model="acab.item" placeholder="Ex: Capa em Madeira">
                                        </div>
                                        <div>
                                            <label class="text-[9px] font-bold text-on-surface-variant">Texto</label>
                                            <input class="input w-full text-xs" x-model="acab.texto" placeholder="Descrição do acabamento">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-bold text-on-surface-variant">Imagem</label>
                                        <div class="flex items-center gap-2 mt-1">
                                            <div class="w-10 h-10 rounded-lg overflow-hidden bg-surface-container-high shrink-0 border border-outline-variant/20">
                                                <img :src="acab.imagem || ''" class="w-full h-full object-cover" 
                                                     onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><rect width=%2240%22 height=%2240%22 fill=%22%23333%22/></svg>'">
                                            </div>
                                            <input class="input flex-1 text-xs" x-model="acab.imagem" placeholder="URL da imagem ou faça upload">
                                            <button type="button" @click="uploadImagem('acabamento', index)" class="bg-surface-container-high hover:bg-surface-variant text-on-surface-variant px-2 py-1.5 rounded-lg text-[10px] font-bold transition-colors">
                                                Upload
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="!form.acabamentos_lista || form.acabamentos_lista.length === 0" class="text-xs text-on-surface-variant text-center py-3 italic">
                                Nenhum acabamento cadastrado. Clique em "+ Adicionar" para incluir.
                            </div>
                        </div>
                    </div>

                    <!-- Estojo -->
                    <div class="bg-amber-500/5 p-4 rounded-xl border border-amber-500/10">
                        <label class="label text-amber-500 flex items-center gap-1.5 mb-3">
                            <i data-lucide="package" class="w-4 h-4"></i> Estojo / Embalagem
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="text-[9px] font-bold text-on-surface-variant">Tipo</label>
                                <input class="input w-full text-xs" x-model="form.estojo_tipo" placeholder="Ex: Case Slim, Maleta Classic, Crystal Box">
                            </div>
                            <div>
                                <label class="text-[9px] font-bold text-on-surface-variant">Descrição</label>
                                <input class="input w-full text-xs" x-model="form.estojo_descricao" placeholder="Descrição do estojo">
                            </div>
                        </div>
                        <div class="mt-2">
                            <label class="text-[9px] font-bold text-on-surface-variant">Imagem de Referência</label>
                            <div class="flex items-center gap-2 mt-1">
                                <div class="w-12 h-12 rounded-lg overflow-hidden bg-surface-container-high shrink-0 border border-outline-variant/20">
                                    <img :src="form.estojo_imagem || ''" class="w-full h-full object-cover"
                                         onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 48 48%22><rect width=%2248%22 height=%2248%22 fill=%22%23333%22/></svg>'">
                                </div>
                                <input class="input flex-1 text-xs" x-model="form.estojo_imagem" placeholder="URL ou upload">
                                <button type="button" @click="uploadImagem('estojo')" class="bg-surface-container-high hover:bg-surface-variant text-on-surface-variant px-2 py-1.5 rounded-lg text-[10px] font-bold transition-colors">
                                    Upload
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Galeria de Imagens -->
                    <div class="bg-indigo-500/5 p-4 rounded-xl border border-indigo-500/10">
                        <label class="label text-indigo-400 flex items-center gap-1.5 mb-3">
                            <i data-lucide="image" class="w-4 h-4"></i> Galeria de Imagens do Álbum
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="text-[9px] font-bold text-on-surface-variant">Capa (Foto Principal)</label>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="w-10 h-10 rounded-lg overflow-hidden bg-surface-container-high shrink-0 border border-outline-variant/20">
                                        <img :src="form.img_capa || ''" class="w-full h-full object-cover"
                                             onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><rect width=%2240%22 height=%2240%22 fill=%22%23333%22/></svg>'">
                                    </div>
                                    <input class="input flex-1 text-xs" x-model="form.img_capa" placeholder="URL">
                                    <button type="button" @click="uploadImagem('galeria', 'capa')" class="bg-surface-container-high hover:bg-surface-variant text-on-surface-variant px-2 py-1.5 rounded-lg text-[10px] font-bold transition-colors">
                                        Upload
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="text-[9px] font-bold text-on-surface-variant">Aberto (Vista Interna)</label>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="w-10 h-10 rounded-lg overflow-hidden bg-surface-container-high shrink-0 border border-outline-variant/20">
                                        <img :src="form.img_aberto || ''" class="w-full h-full object-cover"
                                             onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><rect width=%2240%22 height=%2240%22 fill=%22%23333%22/></svg>'">
                                    </div>
                                    <input class="input flex-1 text-xs" x-model="form.img_aberto" placeholder="URL">
                                    <button type="button" @click="uploadImagem('galeria', 'aberto')" class="bg-surface-container-high hover:bg-surface-variant text-on-surface-variant px-2 py-1.5 rounded-lg text-[10px] font-bold transition-colors">
                                        Upload
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="text-[9px] font-bold text-on-surface-variant">Detalhe</label>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="w-10 h-10 rounded-lg overflow-hidden bg-surface-container-high shrink-0 border border-outline-variant/20">
                                        <img :src="form.img_detalhe || ''" class="w-full h-full object-cover"
                                             onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><rect width=%2240%22 height=%2240%22 fill=%22%23333%22/></svg>'">
                                    </div>
                                    <input class="input flex-1 text-xs" x-model="form.img_detalhe" placeholder="URL">
                                    <button type="button" @click="uploadImagem('galeria', 'detalhe')" class="bg-surface-container-high hover:bg-surface-variant text-on-surface-variant px-2 py-1.5 rounded-lg text-[10px] font-bold transition-colors">
                                        Upload
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========== CAMPOS DE ACABAMENTO (GALERIA) ========== -->
                <div x-show="form.tipo === 'acabamento'" class="space-y-4">
                    <div>
                        <label class="label">Nome do Acabamento *</label>
                        <input class="input w-full" x-model="form.nome" required placeholder="Ex: Papel Linho Silk">
                    </div>
                    <div>
                        <label class="label">Descrição</label>
                        <textarea class="textarea w-full" x-model="form.descricao" rows="2" placeholder="Descrição do acabamento..."></textarea>
                    </div>
                    <div>
                        <label class="label">Imagem de Exemplo</label>
                        <div class="flex items-center gap-2 mt-1">
                            <div class="w-16 h-16 rounded-xl overflow-hidden bg-surface-container-high shrink-0 border border-outline-variant/20">
                                <img :src="form.img_exemplo || ''" class="w-full h-full object-cover"
                                     onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 64 64%22><rect width=%2264%22 height=%2264%22 fill=%22%23333%22/></svg>'">
                            </div>
                            <input class="input flex-1 text-xs" x-model="form.img_exemplo" placeholder="URL da imagem ou faça upload">
                            <button type="button" @click="uploadImagem('acabamento_avulso')" class="bg-surface-container-high hover:bg-surface-variant text-on-surface-variant px-2 py-1.5 rounded-lg text-[10px] font-bold transition-colors">
                                Upload
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ========== CAMPOS DE SERVIÇO / PLANO (EXISTENTE) ========== -->
                <div x-show="form.tipo !== 'colecao' && form.tipo !== 'acabamento'">
                    <!-- Configuração de Itens (Apenas para Planos que não sejam de Eventos) -->
                    <div x-show="form.tipo === 'plano' && form.categoria === 'marketing'" class="bg-primary/5 p-4 rounded-xl border border-primary/10 mb-4">
                        <label class="label flex items-center gap-1.5 text-primary">
                            <i data-lucide="list-checks" class="w-4 h-4"></i>
                            Configuração de Itens (JSON)
                        </label>
                        <textarea class="input w-full font-mono text-[11px] mt-1" x-model="form.itens_json" rows="3" placeholder='Ex: {"servico_id": "incluso", "outro_id": "opcional"}'></textarea>
                        <p class="text-[10px] text-on-surface-variant mt-1.5">
                            💡 Liste os IDs dos serviços e defina se são "incluso" ou "opcional".
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="label">Nome do Serviço *</label>
                        <input class="input w-full" x-model="form.nome" required placeholder="Ex: Registro Essencial">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4" x-show="form.categoria === 'marketing'">
                        <div>
                            <label class="label">Periodicidade</label>
                            <select class="select w-full" x-model="form.periodicidade">
                                <option value="mensal">Mensal (Recorrente)</option>
                                <option value="pontual">Pontual (Projeto Único)</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Prazo Mínimo (Meses)</label>
                            <input class="input w-full" type="number" min="0" x-model="form.prazo_minimo" placeholder="Ex: 6 (0 para s/ mínimo)">
                        </div>
                    </div>

                    <div class="mb-4" x-show="form.categoria === 'marketing'">
                        <label class="label">Descrição Básica</label>
                        <textarea class="textarea w-full" x-model="form.descricao" rows="2" placeholder="O que é o serviço de forma resumida..."></textarea>
                    </div>

                    <!-- Campos Específicos para Eventos (Wedding / 15 Anos) -->
                    <div x-show="form.categoria === 'wedding' || form.categoria === '15anos' || form.categoria === 'casamento' || form.categoria === 'familia' || form.categoria === 'book'" class="bg-tertiary/5 p-4 rounded-xl border border-tertiary/10 mb-4">
                        <div class="mb-3">
                            <label class="label text-tertiary">Subtítulo (Tagline)</label>
                            <input class="input w-full" x-model="form.subtitulo" placeholder="Ex: O plano definitivo para casais...">
                        </div>
                        
                        <div>
                            <label class="label text-tertiary flex justify-between items-center mb-2">
                                Benefícios / Diferenciais (Itens)
                                <button type="button" @click="adicionarBeneficio()" class="bg-tertiary text-on-tertiary px-2 py-0.5 rounded text-[10px] font-bold">
                                    + Adicionar Item
                                </button>
                            </label>
                            <div class="flex flex-col gap-2">
                                <template x-for="(b, index) in form.beneficios_lista" :key="index">
                                    <div class="flex gap-2">
                                        <input class="input w-full text-xs" x-model="form.beneficios_lista[index]" placeholder="Descreva o benefício...">
                                        <button type="button" @click="removerBeneficio(index)" class="text-error/70 hover:text-error p-1">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Vínculo de Upgrades / Adicionais (Apenas para Planos de Eventos) -->
                    <div x-show="form.tipo === 'plano' && (form.categoria === 'wedding' || form.categoria === '15anos')" class="bg-primary/5 p-4 rounded-xl border border-primary/10 mb-4">
                        <label class="label flex items-center gap-1.5 text-primary mb-3">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                            Upgrades e Adicionais Sugeridos
                        </label>
                        <div class="flex flex-col gap-2">
                            <template x-for="u in upgradesDisponiveis" :key="u.id">
                                <div class="flex items-center justify-between bg-surface-container-high/40 p-2.5 rounded-lg border border-outline-variant/10">
                                    <span class="text-xs font-bold text-on-surface" x-text="u.nome"></span>
                                    <div class="flex gap-4">
                                        <label class="flex items-center gap-1.5 text-[11px] text-on-surface-variant cursor-pointer">
                                            <input type="checkbox" :checked="isUpgradeVinculado(u.id, 'opcional')" @change="toggleUpgrade(u.id, 'opcional')">
                                            Disponível
                                        </label>
                                        <label class="flex items-center gap-1.5 text-[11px] text-on-surface-variant cursor-pointer">
                                            <input type="checkbox" :checked="isUpgradeVinculado(u.id, 'incluso')" @change="toggleUpgrade(u.id, 'incluso')">
                                            Incluso
                                        </label>
                                    </div>
                                </div>
                            </template>
                            <div x-show="upgradesDisponiveis.length === 0" class="text-xs text-on-surface-variant text-center py-2 italic">
                                Nenhum upgrade cadastrado nesta categoria.
                            </div>
                        </div>
                    </div>

                    <div class="mb-4" x-show="form.categoria === 'marketing'">
                        <label class="label">Entregáveis (Escopo)</label>
                        <textarea class="textarea w-full" x-model="form.entregaveis" rows="2" placeholder="Ex: 4 posts semanais, 1 relatório mensal, etc..."></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4" x-show="form.categoria === 'marketing'">
                        <div>
                            <label class="label">Ferramentas Adicionais</label>
                            <input class="input w-full" x-model="form.ferramentas" placeholder="Ex: Canva Pro, RD Station...">
                        </div>
                        <div>
                            <label class="label">Terceirização (Custo/O que)</label>
                            <input class="input w-full" x-model="form.terceirizacao" placeholder="Ex: R$ 200,00 - Designer Freelancer">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4" x-show="form.categoria === 'marketing'">
                        <div>
                            <label class="label">Horas/Dia (Dedicadas) *</label>
                            <input class="input w-full" type="number" step="0.1" min="0" x-model="form.horas_dia" @input="calcularHorasMensaisServico()" :required="form.categoria === 'marketing'" placeholder="Ex: 0.5">
                        </div>
                        <div>
                            <label class="label">Horas Estimadas (Mês) *</label>
                            <input class="input w-full" type="number" step="0.5" min="0.5" x-model="form.horas_estimadas" :required="form.categoria === 'marketing'" placeholder="Ex: 20">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4" x-show="form.categoria === 'marketing'">
                        <div>
                            <label class="label">Custo de Produção (R$) *</label>
                            <input class="input w-full" type="number" step="0.01" min="0" x-model="form.custo_producao" :required="form.categoria === 'marketing'" placeholder="0,00">
                        </div>
                        <div>
                            <label class="label">Custos Variáveis (R$)</label>
                            <input class="input w-full" type="number" step="0.01" min="0" x-model="form.custos_variaveis" placeholder="Ferramentas, etc.">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                        <div class="bg-primary/5 p-4 rounded-xl border border-primary/20">
                            <div class="flex justify-between items-center mb-2">
                                <label class="label !mb-0" x-text="form.categoria === 'marketing' ? 'Preço Recorrente (R$)' : 'Investimento (R$)'"></label>
                                <button type="button" class="bg-primary/10 hover:bg-primary/20 text-primary border border-primary/20 px-2 py-0.5 rounded text-[10px] font-bold transition-all flex items-center gap-1" @click="sugerirPrecoIA('recorrente')" :disabled="sugerindoPreco" x-show="form.categoria === 'marketing'">
                                    <i data-lucide="sparkles" class="w-3 h-3"></i> Sugerir
                                </button>
                            </div>
                            <input class="input w-full !text-base !font-bold text-primary font-data-tabular bg-transparent border-none focus:ring-0 p-0" type="number" step="0.01" x-model="form.preco_venda" placeholder="0,00">
                        </div>

                        <div class="bg-primary/5 p-4 rounded-xl border border-primary/20" x-show="form.categoria === 'marketing'">
                            <div class="flex justify-between items-center mb-2">
                                <label class="label !mb-0">Preço Pontual (R$)</label>
                                <button type="button" class="bg-primary/10 hover:bg-primary/20 text-primary border border-primary/20 px-2 py-0.5 rounded text-[10px] font-bold transition-all flex items-center gap-1" @click="sugerirPrecoIA('pontual')" :disabled="sugerindoPreco">
                                    <i data-lucide="sparkles" class="w-3 h-3"></i> Sugerir
                                </button>
                            </div>
                            <input class="input w-full !text-base !font-bold text-primary font-data-tabular bg-transparent border-none focus:ring-0 p-0" type="number" step="0.01" x-model="form.preco_venda_pontual" placeholder="0,00">
                        </div>
                    </div>

                    <div class="text-[10px] text-on-surface-variant font-label-caps mb-4 pl-1" x-show="form.categoria === 'marketing'">
                        💡 Piso mínimo (custo + rateio): <span class="font-bold font-data-tabular" x-text="formatarMoeda(calcularPrecoMinimo(form))"></span>
                    </div>
                    
                    <div class="mb-6" x-show="form.categoria === 'marketing'">
                        <label class="label">Markup Manual (%)</label>
                        <input class="input w-full" type="number" step="0.5" min="0" x-model="form.markup" @input="recalcularPrecoPeloMarkup()" placeholder="Ex: 30">
                    </div>
                </div>

                <div class="flex gap-3 justify-end pt-2">
                    <button type="button" class="btn-secondary" @click="modalAberto=false">Cancelar</button>
                    <button type="submit" class="btn-primary" :disabled="salvando" x-text="salvando ? 'Salvando...' : (form.id ? 'Atualizar' : 'Criar')"></button>
                </div>
            </form>
                    <!-- Modal Planejador IA -->
    <div class="modal-overlay" x-show="modalPlanejadorAberto" x-cloak @click.self="modalPlanejadorAberto=false">
        <div class="modal w-full max-w-md p-6 bg-surface-container-low border border-outline-variant/30 rounded-2xl shadow-2xl relative animate-in fade-in zoom-in-95 duration-200">
            <button @click="modalPlanejadorAberto=false" class="absolute top-6 right-6 text-on-surface-variant hover:text-on-surface transition-colors cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <div class="mb-6">
                <h2 class="text-title-sm font-headline-md font-bold text-on-surface flex items-center gap-2">
                    <i data-lucide="sparkles" class="w-5 h-5 text-primary"></i>
                    Planejador de Capacidade IA
                </h2>
                <p class="text-body-md text-on-surface-variant mt-1">Responda brevemente para a IA calcular sua capacidade mensal real.</p>
            </div>

            <div class="space-y-4 mb-6">
                <div>
                    <label class="label">Quantas pessoas trabalham na produção?</label>
                    <input class="input w-full" x-model="planejador.equipe" placeholder="Ex: 2 pessoas + eu">
                </div>
                <div>
                    <label class="label">Qual a jornada diária e dias por semana?</label>
                    <input class="input w-full" x-model="planejador.jornada" placeholder="Ex: 8h por dia, segunda a sexta">
                </div>
                <div>
                    <label class="label">Alguma observação sobre produtividade?</label>
                    <textarea class="textarea w-full" x-model="planejador.obs" rows="2" placeholder="Ex: Perdemos 20% do tempo em reuniões..."></textarea>
                </div>
            </div>

            <div class="flex gap-3 justify-end">
                <button class="btn-secondary" @click="modalPlanejadorAberto=false">Cancelar</button>
                <button class="btn-primary" @click="calcularCapacidadeIA()" :disabled="planejando">
                    <span x-show="!planejando">Calcular Capacidade</span>
                    <span x-show="planejando">Analisando...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Painel de Chat IA -->
    <div class="chat-ai-panel bg-surface-container-low border-l border-outline-variant/30 shadow-2xl" :class="chatAberto ? 'active' : ''" x-cloak>
        <div class="flex flex-col h-full">
            <div class="p-5 border-b border-outline-variant/20 flex justify-between items-center bg-surface-container-low/60 backdrop-blur-md">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <div class="font-bold text-on-surface text-xs font-label-caps truncate max-w-[200px]" x-text="form.nome || 'Novo Serviço'"></div>
                        <div class="text-[9px] font-label-caps text-primary tracking-wider mt-0.5">ESTRUTURAÇÃO & PRECIFICAÇÃO</div>
                    </div>
                </div>
                <button @click="chatAberto=false" class="text-on-surface-variant hover:text-on-surface transition-colors">
                    <i data-lucide="chevron-right" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Corpo do Chat -->
            <div id="chat-body" class="flex-1 overflow-y-auto p-5 flex flex-col gap-4 scroll-smooth">
                <template x-for="msg in chatHistorico">
                    <div class="max-w-[85%] flex flex-col" :class="msg.role === 'user' ? 'self-end' : 'self-start'">
                        <div class="p-3.5 rounded-2xl text-xs line-height-relaxed"
                             :class="msg.role === 'user' 
                                ? 'bg-primary text-on-primary rounded-tr-none' 
                                : 'bg-surface-container-high border border-outline-variant/20 text-on-surface rounded-tl-none'"
                             x-text="msg.content">
                        </div>
                        <span class="text-[9px] text-on-surface-variant font-label-caps mt-1"
                              :class="msg.role === 'user' ? 'text-right' : 'text-left'"
                              x-text="msg.role === 'user' ? 'Você' : 'Assistente IA'"></span>
                    </div>
                </template>
                
                <div x-show="melhorandoIA" class="self-start bg-surface-container-high/40 p-3 rounded-2xl border border-outline-variant/10 flex gap-1 items-center">
                    <span class="dot-loading"></span>
                    <span class="dot-loading" style="animation-delay:0.2s"></span>
                    <span class="dot-loading" style="animation-delay:0.4s"></span>
                </div>
            </div>

            <!-- Input do Chat -->
            <div class="p-5 bg-surface-container-low border-t border-outline-variant/20">
                <div class="relative">
                    <textarea 
                        x-model="chatMensagem" 
                        @keydown.enter.prevent="enviarMensagemChatIA()" 
                        placeholder="Diga o que deseja alterar..." 
                        rows="1" 
                        class="w-full input pr-12 text-xs focus:ring-1 focus:ring-primary/40 resize-none py-3"
                        :disabled="melhorandoIA"
                    ></textarea>
                    <button 
                        @click="enviarMensagemChatIA()" 
                        :disabled="!chatMensagem.trim() || melhorandoIA"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-primary text-on-primary hover:bg-primary-container hover:text-on-primary-container transition-all flex items-center justify-center disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer"
                    >
                        <i data-lucide="send" class="w-4 h-4"></i>
                    </button>
                </div>
                <div class="text-center mt-2.5 text-[9px] text-on-surface-variant font-label-caps">
                    Pressione Enter para enviar
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .chat-ai-panel {
        position: fixed;
        right: -400px;
        top: 0;
        bottom: 0;
        width: 380px;
        z-index: 10001;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
    }
    .chat-ai-panel.active {
        right: 0;
    }
    .chat-ai-panel * {
        position: relative;
        z-index: 2;
    }
    .dot-loading {
        width: 6px;
        height: 6px;
        background: var(--color-on-surface-variant, #6b7280);
        border-radius: 50%;
        display: inline-block;
        animation: dot-pulse 1.4s infinite ease-in-out;
    }
    @keyframes dot-pulse {
        0%, 80%, 100% { transform: scale(0); opacity: 0.3; }
        40% { transform: scale(1); opacity: 1; }
    .modal-overlay {
        z-index: 10000;
    }
</style>

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
        categoriaAtiva: 'marketing',
        filtroAlbum: 'todos',
        
        sugerindoPreco: false,
        melhorandoIA: false,
        chatAberto: false,
        chatMensagem: '',
        chatHistorico: [],
        planejador: { equipe: '', jornada: '', obs: '' },

        // ---- FILTROS ----
        get listaFiltrada() {
            return this.lista.filter(s => (s.categoria || 'marketing') === this.categoriaAtiva && s.tipo !== 'colecao' && s.tipo !== 'acabamento');
        },

        get albumsFiltrados() {
            return this.lista.filter(s => {
                if (s.tipo !== 'colecao') return false;
                if (this.filtroAlbum === 'todos') return true;
                return (s.categoria || '') === this.filtroAlbum;
            }).sort((a, b) => (a.preco_venda || 0) - (b.preco_venda || 0));
        },

        get upgradesDisponiveis() {
            return this.lista.filter(s => 
                (s.categoria || 'marketing') === this.form.categoria && 
                s.tipo === 'servico' && 
                s.id !== this.form.id
            );
        },

        // ---- HELPERS DE DADOS ----
        parseImg(imagensJson, chave) {
            if (!imagensJson) return '';
            try {
                const obj = typeof imagensJson === 'object' ? imagensJson : JSON.parse(imagensJson);
                return obj[chave] || '';
            } catch(e) { return ''; }
        },

        parseAcabamentos(acabamentoJson) {
            if (!acabamentoJson) return [];
            try {
                const arr = typeof acabamentoJson === 'object' ? acabamentoJson : JSON.parse(acabamentoJson);
                return Array.isArray(arr) ? arr : Object.entries(arr).map(([chave, texto]) => ({ chave, texto, item: chave, imagem: '' }));
            } catch(e) { return []; }
        },

        parseEstojo(estojoJson, chave) {
            if (!estojoJson) return '';
            try {
                const obj = typeof estojoJson === 'object' ? estojoJson : JSON.parse(estojoJson);
                return obj[chave] || '';
            } catch(e) { return ''; }
        },

        // ---- INICIALIZAÇÃO ----
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
            } catch(e) { toast('Erro ao carregar', 'erro'); }
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

        // ---- MODAL ----
        abrirModal(item = null) {
            this.form = item ? { ...item } : this.formVazio();
            
            // Parse JSON fields for albums
            if (this.form.tipo === 'colecao') {
                this.parseColecaoForm(item);
            } else if (this.form.tipo === 'acabamento') {
                this.parseAcabamentoForm(item);
            } else {
                this.parseServicoForm(item);
            }
            
            this.chatAberto = false;
            this.chatHistorico = [];
            this.chatMensagem = '';
            this.modalAberto = true;
            this.$nextTick(() => lucide.createIcons());
        },

        formVazio() {
            const cat = this.categoriaAtiva === 'albuns' ? '15anos' : this.categoriaAtiva;
            return {
                nome:'', categoria: cat, tipo: 'servico', itens_json: '',
                descricao:'', entregaveis: '', ferramentas: '', terceirizacao: '',
                periodicidade: 'mensal', prazo_minimo: 0,
                horas_dia: '', horas_estimadas:'', custo_producao:'', custos_variaveis:'0',
                preco_venda: 0, preco_venda_pontual: 0, markup:'30',
                subtitulo: '', beneficios_lista: [],
                categoria_original: 'Simples', valor_lamina_extra: 0,
                acabamentos_lista: [],
                estojo_tipo: '', estojo_descricao: '', estojo_imagem: '',
                img_capa: '', img_aberto: '', img_detalhe: '', img_exemplo: ''
            };
        },

        parseColecaoForm(item) {
            if (!item) {
                this.form.categoria_original = 'Simples';
                this.form.acabamentos_lista = [];
                this.form.estojo_tipo = '';
                this.form.estojo_descricao = '';
                this.form.estojo_imagem = '';
                this.form.img_capa = '';
                this.form.img_aberto = '';
                this.form.img_detalhe = '';
                return;
            }
            // Parse acabamentos
            try {
                const raw = typeof item.acabamento_json === 'object' ? item.acabamento_json : JSON.parse(item.acabamento_json || '[]');
                this.form.acabamentos_lista = Array.isArray(raw) ? raw : Object.entries(raw).map(([chave, texto]) => ({ chave, texto, item: chave, imagem: '' }));
            } catch(e) { this.form.acabamentos_lista = []; }
            // Parse estojo
            try {
                const est = typeof item.estojo_json === 'object' ? item.estojo_json : JSON.parse(item.estojo_json || '{}');
                this.form.estojo_tipo = est.tipo || '';
                this.form.estojo_descricao = est.descricao || '';
                this.form.estojo_imagem = est.imagem_referencia || '';
            } catch(e) {
                this.form.estojo_tipo = '';
                this.form.estojo_descricao = '';
                this.form.estojo_imagem = '';
            }
            // Parse imagens
            try {
                const img = typeof item.imagens_json === 'object' ? item.imagens_json : JSON.parse(item.imagens_json || '{}');
                this.form.img_capa = img.capa || '';
                this.form.img_aberto = img.aberto || '';
                this.form.img_detalhe = img.detalhe || img.detalhe_madeira || img.detalhe_acrilico || '';
            } catch(e) {
                this.form.img_capa = '';
                this.form.img_aberto = '';
                this.form.img_detalhe = '';
            }
        },

        parseAcabamentoForm(item) {
            if (!item) {
                this.form.img_exemplo = '';
                return;
            }
            try {
                const img = typeof item.imagens_json === 'object' ? item.imagens_json : JSON.parse(item.imagens_json || '{}');
                this.form.img_exemplo = img.imagem_exemplo || '';
            } catch(e) { this.form.img_exemplo = ''; }
        },

        parseServicoForm(item) {
            if (item && item.beneficios_json) {
                try {
                    this.form.beneficios_lista = JSON.parse(item.beneficios_json);
                } catch(e) { this.form.beneficios_lista = []; }
            } else if (!item) {
                this.form.beneficios_lista = [];
            }
            if (this.form.id && this.form.horas_estimadas) {
                this.form.horas_dia = (parseFloat(this.form.horas_estimadas) / 22).toFixed(1);
            }
            if (!this.form.preco_venda) {
                this.form.preco_venda = this.calcularPrecoMinimo(this.form);
            }
        },

        ajustarTipo() {
            if (this.form.tipo === 'colecao' && !this.form.categoria_original) {
                this.form.categoria_original = 'Simples';
            }
            if (!this.form.acabamentos_lista) this.form.acabamentos_lista = [];
        },

        // ---- ACABAMENTOS ----
        adicionarAcabamento() {
            if (!this.form.acabamentos_lista) this.form.acabamentos_lista = [];
            this.form.acabamentos_lista.push({ chave: '', item: '', texto: '', imagem: '' });
        },

        removerAcabamento(index) {
            this.form.acabamentos_lista.splice(index, 1);
        },

        // ---- BENEFÍCIOS ----
        adicionarBeneficio() {
            if (!this.form.beneficios_lista) this.form.beneficios_lista = [];
            this.form.beneficios_lista.push('');
        },

        removerBeneficio(index) {
            this.form.beneficios_lista.splice(index, 1);
        },

        isUpgradeVinculado(id, status) {
            if (!this.form.itens_json) return false;
            try {
                const itens = JSON.parse(this.form.itens_json);
                return itens[id] === status;
            } catch(e) { return false; }
        },

        toggleUpgrade(id, status) {
            let itens = {};
            try {
                if (this.form.itens_json) itens = JSON.parse(this.form.itens_json);
            } catch(e) { itens = {}; }
            if (itens[id] === status) {
                delete itens[id];
            } else {
                itens[id] = status;
            }
            this.form.itens_json = JSON.stringify(itens);
        },

        recalcularPrecoPeloMarkup() {
            this.form.preco_venda = this.calcularPrecoMinimo(this.form);
            this.form.preco_venda_pontual = this.form.preco_venda * 1.5;
        },

        // ---- UPLOAD ----
        async uploadImagem(tipo, extra = null) {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/png,image/jpeg,image/webp';
            input.onchange = async () => {
                const file = input.files[0];
                if (!file) return;
                const formData = new FormData();
                formData.append('imagem', file);
                try {
                    const r = await fetch('<?= raizUrl('/api/precificacao/upload-imagem.php') ?>', {
                        method: 'POST',
                        body: formData
                    });
                    const res = await r.json();
                    if (res.success) {
                        const url = res.url;
                        if (tipo === 'acabamento' && extra !== null) {
                            this.form.acabamentos_lista[extra].imagem = url;
                        } else if (tipo === 'estojo') {
                            this.form.estojo_imagem = url;
                        } else if (tipo === 'galeria' && extra === 'capa') {
                            this.form.img_capa = url;
                        } else if (tipo === 'galeria' && extra === 'aberto') {
                            this.form.img_aberto = url;
                        } else if (tipo === 'galeria' && extra === 'detalhe') {
                            this.form.img_detalhe = url;
                        } else if (tipo === 'acabamento_avulso') {
                            this.form.img_exemplo = url;
                        }
                        toast('Imagem enviada!', 'sucesso');
                    } else {
                        toast(res.erro || 'Erro no upload', 'erro');
                    }
                } catch(e) { toast('Erro ao enviar imagem', 'erro'); }
            };
            input.click();
        },

        // ---- IA ----
        async melhorarServicoIA() {
            if (!this.form.nome) { toast('Dê um nome primeiro', 'aviso'); return; }
            this.chatAberto = true;
            if (this.chatHistorico.length === 0) {
                this.chatHistorico.push({ role: 'assistant', content: `Olá! Como posso ajudar a melhorar o serviço "${this.form.nome}"?` });
            }
            this.$nextTick(() => {
                const body = document.getElementById('chat-body');
                if (body) body.scrollTop = body.scrollHeight;
                if (window.lucide) lucide.createIcons();
            });
        },

        async enviarMensagemChatIA() {
            if (!this.chatMensagem.trim() || this.melhorandoIA) return;
            const userMsg = this.chatMensagem.trim();
            this.chatHistorico.push({ role: 'user', content: userMsg });
            this.chatMensagem = '';
            this.melhorandoIA = true;
            this.$nextTick(() => { const b = document.getElementById('chat-body'); if (b) b.scrollTop = b.scrollHeight; });
            try {
                const r = await fetch('<?= raizUrl('/api/precificacao/editar-servico-ia.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ servico: this.form, mensagens: this.chatHistorico })
                });
                const res = await r.json();
                if (r.ok) {
                    this.chatHistorico.push({ role: 'assistant', content: res.mensagem });
                    if (res.nome) this.form.nome = res.nome;
                    if (res.descricao) this.form.descricao = res.descricao;
                    if (res.entregaveis) this.form.entregaveis = res.entregaveis;
                    if (res.preco_venda) this.form.preco_venda = res.preco_venda;
                    if (res.preco_venda_pontual) this.form.preco_venda_pontual = res.preco_venda_pontual;
                    if (res.markup) this.form.markup = res.markup;
                } else {
                    toast(res.erro || 'Erro na IA', 'erro');
                    this.chatHistorico.push({ role: 'assistant', content: 'Desculpe, tive um problema.' });
                }
            } catch(e) { toast('Erro de conexão', 'erro'); }
            this.melhorandoIA = false;
        },

        async sugerirPrecoIA(tipo = 'recorrente') {
            if (!this.form.nome || !this.form.horas_estimadas) { toast('Preencha nome e horas', 'aviso'); return; }
            this.sugerindoPreco = true;
            try {
                const r = await fetch('<?= raizUrl('/api/precificacao/sugerir-preco-servico.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        servico: this.form, tipo: tipo,
                        totalCustosFixos: this.totalCustosFixos,
                        horasMensais: this.horasMensais,
                        precoMinimo: this.calcularPrecoMinimo(this.form)
                    })
                });
                const res = await r.json();
                if (r.ok) {
                    if (tipo === 'recorrente') { this.form.preco_venda = res.preco; this.form.markup = res.markup_sugerido; }
                    else { this.form.preco_venda_pontual = res.preco; }
                    toast('Preço sugerido!', 'sucesso');
                } else { toast(res.erro || 'Erro na IA', 'erro'); }
            } catch(e) { toast('Erro de conexão', 'erro'); }
            this.sugerindoPreco = false;
        },

        calcularHorasMensaisServico() {
            const hDia = parseFloat(this.form.horas_dia || 0);
            this.form.horas_estimadas = (hDia * 22).toFixed(1);
        },

        abrirPlanejadorIA() {
            this.modalPlanejadorAberto = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async calcularCapacidadeIA() {
            if (!this.planejador.equipe || !this.planejador.jornada) return;
            this.planejando = true;
            try {
                const r = await fetch('<?= raizUrl('/api/precificacao/planejar-capacidade.php') ?>', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.planejador)
                });
                const res = await r.json();
                if (r.ok) {
                    const horasResult = parseInt(res.horas);
                    if (isNaN(horasResult) || horasResult <= 0) { toast('Valor inválido', 'erro'); return; }
                    this.horasMensais = horasResult;
                    localStorage.setItem('cap_horas_mensais', horasResult);
                    toast(`Capacidade ajustada para ${horasResult}h`, 'sucesso');
                    this.modalPlanejadorAberto = false;
                } else { toast(res.erro || 'Erro', 'erro'); }
            } catch(e) { toast('Erro de conexão', 'erro'); }
            this.planejando = false;
        },

        // ---- SALVAR ----
        async salvar() {
            this.salvando = true;
            try {
                const metodo = this.form.id ? 'PUT' : 'POST';
                const payload = { ...this.form };
                
                // Serializa campos específicos de álbum
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
                    payload.imagens_json = JSON.stringify({ imagem_exemplo: payload.img_exemplo || '' });
                }
                
                // Serializa beneficios
                if (payload.beneficios_lista) {
                    payload.beneficios_json = JSON.stringify(payload.beneficios_lista.filter(b => b && b.trim() !== ''));
                }
                
                // Remove campos auxiliares
                delete payload.acabamentos_lista;
                delete payload.estojo_tipo;
                delete payload.estojo_descricao;
                delete payload.estojo_imagem;
                delete payload.img_capa;
                delete payload.img_aberto;
                delete payload.img_detalhe;
                delete payload.img_exemplo;
                delete payload.beneficios_lista;

                const r = await fetch('<?= raizUrl('/api/precificacao/servicos.php') ?>', {
                    method: metodo,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                if (r.ok) {
                    toast('Salvo!', 'sucesso');
                    this.modalAberto = false;
                    await this.carregar();
                } else {
                    const res = await r.json();
                    toast(res.erro || 'Erro ao salvar', 'erro');
                }
            } catch(e) { toast('Erro de conexão', 'erro'); }
            this.salvando = false;
        },

        async excluir(id) {
            const nome = this.lista.find(s => s.id === id)?.nome || '';
            if (!confirm(`Excluir "${nome}"?`)) return;
            try {
                const r = await fetch('<?= raizUrl('/api/precificacao/servicos.php') ?>?id=' + id, { method: 'DELETE' });
                if (r.ok) { toast('Excluído', 'sucesso'); await this.carregar(); }
                else { toast('Erro ao excluir', 'erro'); }
            } catch(e) { toast('Erro de conexão', 'erro'); }
        },

        formatarMoeda(val) { return window.formatarMoeda(val); },
    };
}
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
