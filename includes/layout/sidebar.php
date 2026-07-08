<?php
require_once __DIR__ . '/../../config/database.php';
$usuario = usuarioAtual();

$paginaAtual = $_SERVER['SCRIPT_NAME'];
function menuAtivo(string $path): string {
    global $paginaAtual;
    return str_contains($paginaAtual, $path) ? 'ativo' : '';
}
?>
<aside x-data="{ collapsed: true }" :class="{ 'collapsed': collapsed }" class="sidebar flex flex-col transition-all duration-300 relative group">
    <div style="padding:24px 20px 18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
            <a href="/" class="hide-on-collapse transition-opacity hover:opacity-80">
                <div class="dark:text-[#cabeff] uppercase tracking-tighter" style="color:#111; font-size:22px; font-weight:900; line-height: 1;">DISTINTO</div>
                <div class="sidebar-copy" style="margin-top:2px; color:#888; font-size:9px; font-weight:800; letter-spacing:0.1em;">AGENCY ERP</div>
            </a>
            <button @click="collapsed = !collapsed" 
                    title="Alternar Menu"
                    class="w-10 h-10 flex flex-shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-white/5 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-900 dark:hover:bg-white hover:text-white dark:hover:text-black transition-all shadow-sm mx-auto">
                <span x-show="collapsed" class="flex items-center justify-center">
                    <i data-lucide="chevron-right" class="w-5 h-5"></i>
                </span>
                <span x-show="!collapsed" class="flex items-center justify-center">
                    <i data-lucide="chevron-left" class="w-5 h-5"></i>
                </span>
            </button>
        </div>
    </div>
    

    <nav style="flex:1; padding:8px 14px; overflow-y:auto; overflow-x:hidden;">
        <div class="nav-section hide-on-collapse">Principal</div>
        <a href="<?= raizUrl('/dashboard.php') ?>" class="nav-link <?= menuAtivo('/dashboard') ?>">
            <i data-lucide="layout-dashboard" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Dashboard</span>
        </a>

        <div class="nav-section hide-on-collapse">Financeiro</div>
        <a href="<?= raizUrl('/financeiro/lancamentos.php') ?>" class="nav-link <?= menuAtivo('/lancamentos') ?>">
            <i data-lucide="receipt-text" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Lançamentos</span>
        </a>
        <a href="<?= raizUrl('/financeiro/contas.php') ?>" class="nav-link <?= menuAtivo('/financeiro/contas') ?>">
            <i data-lucide="landmark" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Bancos</span>
        </a>
        <a href="<?= raizUrl('/financeiro/configuracoes.php') ?>" class="nav-link <?= menuAtivo('/financeiro/configuracoes') ?>">
            <i data-lucide="calculator" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Custos Fixos</span>
        </a>
        <a href="<?= raizUrl('/financeiro/asaas.php') ?>" class="nav-link <?= menuAtivo('/financeiro/asaas') ?>">
            <i data-lucide="wallet" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Asaas Pagamentos</span>
        </a>

        <div class="nav-section hide-on-collapse">Serviços</div>
        <a href="<?= raizUrl('/precificacao/servicos.php') ?>" class="nav-link <?= menuAtivo('/servicos') ?>">
            <i data-lucide="package" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Tabela de Preços</span>
        </a>
        <a href="<?= raizUrl('/precificacao/consultor.php') ?>" class="nav-link <?= menuAtivo('/consultor') ?>">
            <i data-lucide="sparkles" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Consultor IA</span>
        </a>

        <div class="nav-section hide-on-collapse">Comercial</div>
        <a href="<?= raizUrl('/gerenciamento/propostas.php') ?>" class="nav-link <?= menuAtivo('/gerenciamento/propostas') ?>">
            <i data-lucide="file-text" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Propostas Web</span>
        </a>
        <a href="<?= raizUrl('/gerenciamento/contratos.php') ?>" class="nav-link <?= menuAtivo('/gerenciamento/contratos') ?>">
            <i data-lucide="scroll" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Contratos</span>
        </a>
        <a href="<?= raizUrl('/gerenciamento/pdf_templates.php') ?>" class="nav-link <?= menuAtivo('/gerenciamento/pdf_templates') ?>">
            <i data-lucide="file-output" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Templates PDF</span>
        </a>
        <a href="<?= raizUrl('/gerenciamento/clientes.php') ?>" class="nav-link <?= menuAtivo('/gerenciamento/clientes') ?>">
            <i data-lucide="users" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Clientes</span>
        </a>
        <a href="<?= raizUrl('/gerenciamento/fornecedores.php') ?>" class="nav-link <?= menuAtivo('/gerenciamento/fornecedores') ?>">
            <i data-lucide="shopping-bag" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Fornecedores</span>
        </a>
        <a href="<?= raizUrl('/gerenciamento/oportunidades.php') ?>" class="nav-link <?= menuAtivo('/gerenciamento/oportunidades') ?>">
            <i data-lucide="trending-up" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Oportunidades</span>
        </a>
        <a href="<?= raizUrl('/gerenciamento/depoimentos.php') ?>" class="nav-link <?= menuAtivo('/gerenciamento/depoimentos') ?>">
            <i data-lucide="message-square-quote" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Depoimentos</span>
        </a>
        <a href="<?= raizUrl('/roteiros/index.php') ?>" class="nav-link <?= menuAtivo('/roteiros') ?>">
            <i data-lucide="video" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Roteiros</span>
        </a>

        <div class="nav-section hide-on-collapse">Configurações</div>
        <a href="<?= raizUrl('/configuracoes.php') ?>" class="nav-link <?= menuAtivo('/configuracoes') ?>">
            <i data-lucide="settings" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Ajustes Gerais</span>
        </a>
        <a href="<?= raizUrl('/assinar.php') ?>" class="nav-link <?= menuAtivo('/assinar') ?>">
            <i data-lucide="credit-card" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Assinatura</span>
        </a>
        <?php if ($usuario['nivel'] == 1): ?>
        <a href="<?= raizUrl('/gerenciamento/usuarios.php') ?>" class="nav-link <?= menuAtivo('/gerenciamento/usuarios') ?>">
            <i data-lucide="shield-check" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Gestão de Equipe</span>
        </a>
        <a href="<?= raizUrl('/financeiro/saas.php') ?>" class="nav-link <?= menuAtivo('/financeiro/saas') ?>">
            <i data-lucide="bar-chart-2" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Financeiro SaaS</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="dark:border-white/10" style="padding:16px 14px 20px; border-top:1px solid rgba(0,0,0,0.05); overflow:hidden;">
        <div class="dark:bg-white/5" style="display:flex; flex-direction:column; gap:10px; padding:10px; border-radius:16px; background:rgba(0,0,0,0.03);">
            <div style="display:flex; align-items:center; gap:10px;">
                <div class="dark:bg-[#7c5cff] dark:text-white" style="width:40px; height:40px; display:grid; place-items:center; flex-shrink:0; border-radius:12px; background:#111; color:#fff; font-size:16px; font-weight:800; mx-auto">
                    <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                </div>
                <div class="user-meta hide-on-collapse" style="min-width:0; flex:1;">
                    <div class="dark:text-white" style="color:#111; font-size:13px; font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; line-height:1.2;"><?= sanitizar($usuario['nome']) ?></div>
                    <div style="color:#777777; font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; mt-1"><?= sanitizar($usuario['email']) ?></div>
                </div>
            </div>
            
            <div class="hide-on-collapse" x-data="{ 
                isDark: document.documentElement.classList.contains('dark'),
                toggle() {
                    this.isDark = !this.isDark;
                    if (this.isDark) {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('dark-mode', 'true');
                    } else {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('dark-mode', 'false');
                    }
                }
            }" style="display:flex; align-items:center; justify-content:space-between; margin-top:4px; padding-top:10px; border-top:1px solid rgba(0,0,0,0.05);" class="dark:border-white/10">
                <button @click="toggle()" :title="isDark ? 'Ativar Modo Claro' : 'Ativar Modo Escuro'" class="dark:text-zinc-300 dark:hover:text-white dark:bg-white/10 dark:hover:bg-white/20 hover:bg-black/10 hover:text-black transition-colors" style="display:flex; align-items:center; gap:6px; padding:6px 10px; color:#555; border-radius:10px; background:none; border:none; cursor:pointer; font-size:11px; font-weight:bold;">
                    <template x-if="isDark">
                        <span class="flex items-center gap-1.5"><i data-lucide="sun" class="w-3.5 h-3.5"></i> Claro</span>
                    </template>
                    <template x-if="!isDark">
                        <span class="flex items-center gap-1.5"><i data-lucide="moon" class="w-3.5 h-3.5"></i> Escuro</span>
                    </template>
                </button>

                <a href="<?= raizUrl('/api/auth/logout.php') ?>" title="Sair" class="dark:text-red-400 dark:hover:text-red-300 hover:text-red-600 transition-colors" style="display:grid; place-items:center; width:28px; height:28px; flex-shrink:0; color:#888; border-radius:8px; text-decoration:none;">
                    <i data-lucide="log-out" style="width:16px;height:16px;"></i>
                </a>
            </div>
        </div>
    </div>
</aside>
