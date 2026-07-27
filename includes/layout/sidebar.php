<?php
require_once __DIR__ . '/../../config/database.php';
$usuario = usuarioAtual();

$paginaAtual = $_SERVER['SCRIPT_NAME'];
function menuAtivo(string $path): string {
    global $paginaAtual;
    return str_contains($paginaAtual, $path) ? 'ativo' : '';
}
?>
<aside class="sidebar fixed left-0 top-0 h-full z-50 flex flex-col shadow-md transition-all duration-300 group">
    <!-- Brand -->
    <div class="flex items-center px-6 mb-8 pt-gutter space-x-4">
        <div class="w-8 h-8 rounded-lg bg-primary-container flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-on-primary-container">dashboard</span>
        </div>
        <div class="sidebar-copy">
            <h2 class="font-headline-md text-primary leading-none" style="font-size:18px;">DISTINTO</h2>
            <p class="text-label-caps font-label-caps text-on-surface-variant opacity-60" style="font-size:9px;">AGENCY ERP</p>
        </div>
    </div>

    <!-- Menu -->
    <div class="flex-1 space-y-1 px-3 overflow-y-auto overflow-x-hidden custom-scrollbar">
        <div class="nav-section">Principal</div>
        <a href="<?= raizUrl('/dashboard.php') ?>" class="nav-link flex items-center px-3 py-2.5 rounded-lg transition-all <?= menuAtivo('/dashboard') ?>">
            <span class="material-symbols-outlined shrink-0" style="font-size:20px;">home</span>
            <span class="nav-label ml-3 text-sm font-semibold whitespace-nowrap">Dashboard</span>
        </a>

        <div class="nav-section">Financeiro</div>
        <a href="<?= raizUrl('/financeiro/lancamentos.php') ?>" class="nav-link flex items-center px-3 py-2.5 rounded-lg transition-all <?= menuAtivo('/lancamentos') ?>">
            <span class="material-symbols-outlined shrink-0" style="font-size:20px;">receipt_long</span>
            <span class="nav-label ml-3 text-sm font-semibold whitespace-nowrap">Lançamentos</span>
        </a>
        <a href="<?= raizUrl('/financeiro/contas.php') ?>" class="nav-link flex items-center px-3 py-2.5 rounded-lg transition-all <?= menuAtivo('/financeiro/contas') ?>">
            <span class="material-symbols-outlined shrink-0" style="font-size:20px;">account_balance</span>
            <span class="nav-label ml-3 text-sm font-semibold whitespace-nowrap">Bancos</span>
        </a>
        <a href="<?= raizUrl('/financeiro/configuracoes.php') ?>" class="nav-link flex items-center px-3 py-2.5 rounded-lg transition-all <?= menuAtivo('/financeiro/configuracoes') ?>">
            <span class="material-symbols-outlined shrink-0" style="font-size:20px;">calculate</span>
            <span class="nav-label ml-3 text-sm font-semibold whitespace-nowrap">Custos Fixos</span>
        </a>
        <a href="<?= raizUrl('/financeiro/asaas.php') ?>" class="nav-link flex items-center px-3 py-2.5 rounded-lg transition-all <?= menuAtivo('/financeiro/asaas') ?>">
            <span class="material-symbols-outlined shrink-0" style="font-size:20px;">payments</span>
            <span class="nav-label ml-3 text-sm font-semibold whitespace-nowrap">Asaas Pagamentos</span>
        </a>

        <div class="nav-section">Serviços</div>
        <a href="<?= raizUrl('/precificacao/servicos.php') ?>" class="nav-link flex items-center px-3 py-2.5 rounded-lg transition-all <?= menuAtivo('/servicos') ?>">
            <span class="material-symbols-outlined shrink-0" style="font-size:20px;">inventory_2</span>
            <span class="nav-label ml-3 text-sm font-semibold whitespace-nowrap">Tabela de Preços</span>
        </a>
        <a href="<?= raizUrl('/precificacao/consultor.php') ?>" class="nav-link flex items-center px-3 py-2.5 rounded-lg transition-all <?= menuAtivo('/consultor') ?>">
            <span class="material-symbols-outlined shrink-0" style="font-size:20px;">auto_awesome</span>
            <span class="nav-label ml-3 text-sm font-semibold whitespace-nowrap">Consultor IA</span>
        </a>
        <a href="<?= raizUrl('/precificacao/simulador.php') ?>" class="nav-link flex items-center px-3 py-2.5 rounded-lg transition-all <?= menuAtivo('/simulador') ?>">
            <span class="material-symbols-outlined shrink-0" style="font-size:20px;">query_stats</span>
            <span class="nav-label ml-3 text-sm font-semibold whitespace-nowrap">Simulador</span>
        </a>

        <div class="nav-section">Comercial</div>
        <a href="<?= raizUrl('/gerenciamento/propostas.php') ?>" class="nav-link flex items-center px-3 py-2.5 rounded-lg transition-all <?= menuAtivo('/gerenciamento/propostas') ?>">
            <span class="material-symbols-outlined shrink-0" style="font-size:20px;">description</span>
            <span class="nav-label ml-3 text-sm font-semibold whitespace-nowrap">Propostas Web</span>
        </a>
        <a href="<?= raizUrl('/gerenciamento/contratos.php') ?>" class="nav-link flex items-center px-3 py-2.5 rounded-lg transition-all <?= menuAtivo('/gerenciamento/contratos') ?>">
            <span class="material-symbols-outlined shrink-0" style="font-size:20px;">scroll</span>
            <span class="nav-label ml-3 text-sm font-semibold whitespace-nowrap">Contratos</span>
        </a>
        <a href="<?= raizUrl('/gerenciamento/pdf_templates.php') ?>" class="nav-link flex items-center px-3 py-2.5 rounded-lg transition-all <?= menuAtivo('/gerenciamento/pdf_templates') ?>">
            <span class="material-symbols-outlined shrink-0" style="font-size:20px;">file_present</span>
            <span class="nav-label ml-3 text-sm font-semibold whitespace-nowrap">Templates PDF</span>
        </a>
        <a href="<?= raizUrl('/gerenciamento/clientes.php') ?>" class="nav-link flex items-center px-3 py-2.5 rounded-lg transition-all <?= menuAtivo('/gerenciamento/clientes') ?>">
            <span class="material-symbols-outlined shrink-0" style="font-size:20px;">group</span>
            <span class="nav-label ml-3 text-sm font-semibold whitespace-nowrap">Clientes</span>
        </a>
        <a href="<?= raizUrl('/gerenciamento/fornecedores.php') ?>" class="nav-link flex items-center px-3 py-2.5 rounded-lg transition-all <?= menuAtivo('/gerenciamento/fornecedores') ?>">
            <span class="material-symbols-outlined shrink-0" style="font-size:20px;">local_shipping</span>
            <span class="nav-label ml-3 text-sm font-semibold whitespace-nowrap">Fornecedores</span>
        </a>
        <a href="<?= raizUrl('/gerenciamento/oportunidades.php') ?>" class="nav-link flex items-center px-3 py-2.5 rounded-lg transition-all <?= menuAtivo('/gerenciamento/oportunidades') ?>">
            <span class="material-symbols-outlined shrink-0" style="font-size:20px;">trending_up</span>
            <span class="nav-label ml-3 text-sm font-semibold whitespace-nowrap">Oportunidades</span>
        </a>
        <a href="<?= raizUrl('/gerenciamento/depoimentos.php') ?>" class="nav-link flex items-center px-3 py-2.5 rounded-lg transition-all <?= menuAtivo('/gerenciamento/depoimentos') ?>">
            <span class="material-symbols-outlined shrink-0" style="font-size:20px;">star</span>
            <span class="nav-label ml-3 text-sm font-semibold whitespace-nowrap">Depoimentos</span>
        </a>

        <div class="nav-section">Configurações</div>
        <a href="<?= raizUrl('/configuracoes.php') ?>" class="nav-link flex items-center px-3 py-2.5 rounded-lg transition-all <?= menuAtivo('/configuracoes') ?>">
            <span class="material-symbols-outlined shrink-0" style="font-size:20px;">settings</span>
            <span class="nav-label ml-3 text-sm font-semibold whitespace-nowrap">Ajustes Gerais</span>
        </a>
        <?php if ($usuario['nivel'] == 1): ?>
        <a href="<?= raizUrl('/gerenciamento/usuarios.php') ?>" class="nav-link flex items-center px-3 py-2.5 rounded-lg transition-all <?= menuAtivo('/gerenciamento/usuarios') ?>">
            <span class="material-symbols-outlined shrink-0" style="font-size:20px;">admin_panel_settings</span>
            <span class="nav-label ml-3 text-sm font-semibold whitespace-nowrap">Gestão de Equipe</span>
        </a>
        <?php endif; ?>
    </div>

    <!-- Bottom: User + Dark Mode + Logout -->
    <div class="px-3 py-4 border-t border-black/5 dark:border-white/10 overflow-hidden shrink-0">
        <div class="flex items-center gap-3 px-2 py-2 rounded-lg">
            <div class="w-9 h-9 rounded-xl bg-[#111] dark:bg-[#947dff] text-white dark:text-[#2a0088] flex items-center justify-center text-sm font-bold shrink-0">
                <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
            </div>
            <div class="user-meta min-w-0 flex-1">
                <div class="text-sm font-bold truncate" style="color:#111;" class="dark:text-white"><?= sanitizar($usuario['nome']) ?></div>
                <div style="color:#888; font-size:10px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= sanitizar($usuario['email']) ?></div>
            </div>
        </div>
        <div class="flex items-center justify-between mt-2 px-2">
            <button id="dark-mode-toggle" title="Alternar Modo Escuro/Claro" style="background:none; border:none; cursor:pointer; display:flex; align-items:center; gap:6px; padding:4px 8px; border-radius:8px; color:#888; font-size:10px; font-weight:bold;" class="hover:bg-black/5 dark:hover:bg-white/5 transition-colors">
                <span id="dark-mode-icon" class="material-symbols-outlined" style="font-size:16px;">dark_mode</span>
                <span id="dark-mode-label" class="sidebar-copy">Escuro</span>
            </button>
            <a href="<?= raizUrl('/api/auth/logout.php') ?>" title="Sair" style="display:flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:8px; color:#888; text-decoration:none;" class="hover:text-red-500 hover:bg-red-500/10 transition-colors">
                <span class="material-symbols-outlined" style="font-size:16px;">logout</span>
            </a>
        </div>
    </div>
</aside>
