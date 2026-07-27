<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAdmin();

$me = usuarioAtual();
// Patch: Se o nível não estiver na sessão, tenta buscar no banco
if (!isset($me['nivel'])) {
    $db = Database::get();
    $stmt = $db->prepare("SELECT nivel FROM users WHERE id = ?");
    $stmt->execute([$me['id']]);
    $nivel = $stmt->fetchColumn();
    $me['nivel'] = (int)$nivel;
}

if ($me['nivel'] != 1) {
    header("Location: " . raizUrl('/dashboard.php'));
    exit;
}

$tituloPagina = "Gestão de Usuários";
require_once __DIR__ . '/../includes/layout/head.php';
?>

<style>
[x-cloak] { display: none !important; }
</style>

<div id="app-wrapper" x-data="usuariosApp()">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="min-h-screen bg-[#050505] text-white">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10">
            <div>
                <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500 mb-2">
                    <i data-lucide="shield-check" class="w-3 h-3"></i>
                    Administração do Sistema
                </div>
                <h1 class="text-3xl font-black tracking-tighter text-white">Usuários & Acessos</h1>
                <p class="text-zinc-500 text-sm mt-1">Gerencie a equipe e controle quem pode acessar as áreas críticas.</p>
            </div>

            <button @click="abrirModal()" class="h-12 px-6 bg-white text-black rounded-2xl font-bold text-sm flex items-center gap-2 hover:bg-zinc-200 transition-all shadow-lg shadow-white/5 active:scale-95">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                Adicionar Usuário
            </button>
        </div>

        <!-- Grid de Usuários -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="u in lista" :key="u.id">
                <div class="group relative bg-zinc-900/40 border border-white/5 rounded-[2rem] p-6 hover:bg-zinc-900/60 transition-all duration-500 hover:border-white/10 hover:-translate-y-1">
                    
                    <!-- Ações Rápidas (Hover) -->
                    <div class="absolute top-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button @click="abrirModal(u)" class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center text-zinc-400 hover:text-white hover:bg-white/10 transition-all">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </button>
                        <button @click="excluir(u.id)" x-show="u.id !== '<?= $me['id'] ?>'" class="w-8 h-8 rounded-full bg-red-500/10 flex items-center justify-center text-red-500 hover:bg-red-500 hover:text-white transition-all">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <div class="flex items-start gap-5">
                        <!-- Avatar com Inicial -->
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl font-black shadow-inner"
                             :class="u.nivel == 1 ? 'bg-gradient-to-br from-zinc-200 to-zinc-400 text-zinc-900' : 'bg-zinc-800 text-zinc-400'"
                             x-text="u.nome.substring(0,1).toUpperCase()">
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <h3 class="font-bold text-lg text-white truncate" x-text="u.nome"></h3>
                                <template x-if="u.nivel == 1">
                                    <span class="w-4 h-4 text-zinc-400" title="Administrador">
                                        <i data-lucide="crown" class="w-3.5 h-3.5"></i>
                                    </span>
                                </template>
                            </div>
                            <p class="text-zinc-500 text-xs truncate mb-4" x-text="u.email"></p>
                            
                            <div class="flex items-center justify-between">
                                <div class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border"
                                     :class="u.nivel == 1 ? 'border-zinc-100/20 text-zinc-100 bg-white/5' : 'border-zinc-800 text-zinc-600'">
                                    <span x-text="u.nivel == 1 ? 'Administrador' : 'Usuário Comum'"></span>
                                </div>
                                <span class="text-[10px] text-zinc-700 font-bold" x-show="u.id === '<?= $me['id'] ?>'">(VOCÊ)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Card de Adicionar (Estilo Tracejado) -->
            <div @click="abrirModal()" class="group border-2 border-dashed border-zinc-800 rounded-[2rem] p-6 flex flex-col items-center justify-center gap-3 cursor-pointer hover:border-zinc-600 hover:bg-zinc-900/20 transition-all min-h-[140px]">
                <div class="w-12 h-12 rounded-full bg-zinc-900 flex items-center justify-center text-zinc-700 group-hover:text-zinc-400 group-hover:scale-110 transition-all">
                    <i data-lucide="user-plus" class="w-6 h-6"></i>
                </div>
                <span class="text-xs font-bold text-zinc-700 group-hover:text-zinc-400">Novo Integrante</span>
            </div>
        </div>

        <!-- Modal -->
        <div x-show="modalAberto" 
             class="fixed inset-0 z-[1000] flex items-center justify-center p-6 bg-black/90 backdrop-blur-md"
             x-cloak
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            
            <div class="bg-[#0c0c0c] border border-white/5 rounded-[2.5rem] w-full max-w-lg overflow-hidden shadow-2xl"
                 @click.away="modalAberto = false"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0">
                
                <div class="px-8 py-6 border-b border-white/5 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-white" x-text="form.id ? 'Editar Usuário' : 'Novo Integrante'"></h2>
                    <button @click="modalAberto = false" class="text-zinc-500 hover:text-white transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>

                <form @submit.prevent="salvar()" class="p-8 space-y-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-zinc-500 ml-1">Nome Completo</label>
                            <input class="w-full bg-zinc-900 border border-white/5 rounded-2xl px-5 py-4 text-sm text-white focus:border-zinc-500 outline-none transition-all" 
                                   type="text" x-model="form.nome" required placeholder="Ex: João Silva">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-zinc-500 ml-1">E-mail Corporativo</label>
                            <input class="w-full bg-zinc-900 border border-white/5 rounded-2xl px-5 py-4 text-sm text-white focus:border-zinc-500 outline-none transition-all" 
                                   type="email" x-model="form.email" required placeholder="email@agencia.com">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-zinc-500 ml-1" x-text="form.id ? 'Alterar Senha (opcional)' : 'Senha de Acesso'"></label>
                            <input class="w-full bg-zinc-900 border border-white/5 rounded-2xl px-5 py-4 text-sm text-white focus:border-zinc-500 outline-none transition-all" 
                                   type="password" x-model="form.senha" :required="!form.id" placeholder="••••••••">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-zinc-500 ml-1">Nível de Permissão</label>
                            <select class="w-full bg-zinc-900 border border-white/5 rounded-2xl px-5 py-4 text-sm text-white focus:border-zinc-500 outline-none transition-all appearance-none" 
                                    x-model="form.nivel">
                                <option value="0">Acesso Padrão (Usuário)</option>
                                <option value="1">Acesso Administrativo (Total)</option>
                            </select>
                        </div>

                        <div class="pt-4 flex gap-4">
                            <button type="button" @click="modalAberto = false" class="flex-1 h-14 rounded-2xl font-bold text-zinc-500 hover:bg-white/5 transition-all">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="salvando" 
                                    class="flex-[2] h-14 bg-white text-black rounded-2xl font-black text-sm hover:bg-zinc-200 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                                <template x-if="salvando">
                                    <svg class="animate-spin h-5 w-5 text-black" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                </template>
                                <span x-text="salvando ? 'Processando...' : 'Salvar Integrante'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function usuariosApp() {
    return {
        lista: [],
        salvando: false,
        modalAberto: false,
        form: { id: null, nome: '', email: '', senha: '', nivel: 0 },

        async init() {
            await this.carregar();
        },

        async carregar() {
            try {
                const r = await fetch('<?= raizUrl('/api/sistema/usuarios.php') ?>');
                if (!r.ok) throw new Error('Erro ao carregar lista');
                this.lista = await r.json();
                this.$nextTick(() => lucide.createIcons());
            } catch (e) {
                console.error(e);
            }
        },

        abrirModal(u = null) {
            this.form = u ? { ...u, senha: '' } : { id: null, nome: '', email: '', senha: '', nivel: 0 };
            this.modalAberto = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async salvar() {
            this.salvando = true;
            try {
                const metodo = this.form.id ? 'PUT' : 'POST';
                const r = await fetch('<?= raizUrl('/api/sistema/usuarios.php') ?>', {
                    method: metodo,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                
                const res = await r.json();
                if (r.ok) {
                    await this.carregar();
                    this.modalAberto = false;
                    // Opcional: mostrar toast
                } else {
                    alert(res.erro || 'Erro ao salvar');
                }
            } catch (e) {
                alert('Erro de conexão');
            }
            this.salvando = false;
        },

        async excluir(id) {
            if (!confirm('Deseja realmente excluir este integrante do sistema?')) return;
            try {
                const r = await fetch('<?= raizUrl('/api/sistema/usuarios.php?id=') ?>' + id, { method: 'DELETE' });
                if (r.ok) {
                    await this.carregar();
                }
            } catch (e) {
                alert('Erro ao excluir');
            }
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
