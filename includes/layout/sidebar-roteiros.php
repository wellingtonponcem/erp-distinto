<?php
require_once __DIR__ . '/../../config/database.php';
$usuario = usuarioAtual();

$paginaAtual = $_SERVER['SCRIPT_NAME'];
function menuAtivoRoteiros(string $path): string {
    global $paginaAtual;
    return str_contains($paginaAtual, $path) ? 'ativo' : '';
}
?>
<aside x-data="{ collapsed: true }" :class="{ 'collapsed': collapsed }" class="sidebar flex flex-col transition-all duration-300 relative group">
    <div style="padding:24px 20px 18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
            <div class="hide-on-collapse transition-opacity">
                <div style="color:var(--text); font-size:18px; font-weight:900; line-height: 1; letter-spacing: -0.02em;">
                    <span style="font-family: var(--display); text-transform: uppercase;">MEUS</span>
                    <span style="font-family: var(--serif); font-style: italic; color: var(--accent); font-weight: 400; margin-left: 4px;">Roteiros</span>
                </div>
            </div>
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
        <div class="nav-section hide-on-collapse">Menu</div>
        
        <a href="<?= raizUrl('/roteiros/index.php') ?>" class="nav-link <?= menuAtivoRoteiros('/roteiros/index.php') ?>">
            <i data-lucide="video" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Meus Roteiros</span>
        </a>

        <a href="<?= raizUrl('/roteiros/conhecimento.php') ?>" class="nav-link <?= menuAtivoRoteiros('/roteiros/conhecimento.php') ?>">
            <i data-lucide="brain-circuit" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Base de Conhecimento</span>
        </a>

        <a href="<?= raizUrl('/roteiros/voz.php') ?>" class="nav-link <?= menuAtivoRoteiros('/roteiros/voz.php') ?>">
            <i data-lucide="mic-2" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Voz & Estilo</span>
        </a>

        <div class="nav-section hide-on-collapse">Assinatura</div>
        <a href="<?= raizUrl('/assinar.php') ?>" class="nav-link <?= menuAtivoRoteiros('/assinar.php') ?>">
            <i data-lucide="credit-card" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Meu Plano</span>
        </a>

        <div class="nav-section hide-on-collapse">Conta</div>
        <a href="<?= raizUrl('/roteiros/perfil.php') ?>" class="nav-link <?= menuAtivoRoteiros('/roteiros/perfil.php') ?>">
            <i data-lucide="user" style="width:20px;height:20px; flex-shrink:0;"></i>
            <span class="nav-label hide-on-collapse transition-opacity">Meu Perfil</span>
        </a>
    </nav>

    <div class="dark:border-white/10" style="padding:16px 14px 20px; border-top:1px solid rgba(0,0,0,0.05); overflow:hidden;">
        <div class="dark:bg-white/5" style="display:flex; flex-direction:column; gap:10px; padding:10px; border-radius:16px; background:rgba(0,0,0,0.03);">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:40px; height:40px; display:grid; place-items:center; flex-shrink:0; border-radius:12px; background:var(--accent); color:#000; font-size:16px; font-weight:800; mx-auto">
                    <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                </div>
                <div class="user-meta hide-on-collapse" style="min-width:0; flex:1;">
                    <div class="dark:text-white" style="color:#111; font-size:13px; font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; line-height:1.2;"><?= sanitizar($usuario['nome']) ?></div>
                    <div style="color:#777777; font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; mt-1"><?= sanitizar($usuario['email']) ?></div>
                </div>
            </div>
            
            <div class="hide-on-collapse" style="display:flex; align-items:center; justify-content:flex-end; margin-top:4px; padding-top:10px; border-top:1px solid rgba(0,0,0,0.05);" class="dark:border-white/10">
                <a href="<?= raizUrl('/api/auth/logout.php') ?>" title="Sair" class="dark:text-red-400 dark:hover:text-red-300 hover:text-red-600 transition-colors" style="display:grid; place-items:center; width:28px; height:28px; flex-shrink:0; color:#888; border-radius:8px; text-decoration:none;">
                    <i data-lucide="log-out" style="width:16px;height:16px;"></i>
                </a>
            </div>
        </div>
    </div>
</aside>
