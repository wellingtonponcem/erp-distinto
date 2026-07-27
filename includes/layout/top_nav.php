<?php
$current_uri = $_SERVER['REQUEST_URI'];
$is_dashboard = strpos($current_uri, '/dashboard.php') !== false;
$is_lancamentos = strpos($current_uri, '/financeiro/lancamentos.php') !== false;
$is_custos = strpos($current_uri, '/financeiro/configuracoes.php') !== false;
$is_simulador = strpos($current_uri, '/precificacao/simulador.php') !== false;

$usuarioLogado = usuarioAtual();
$inicial = strtoupper(substr($usuarioLogado['nome'] ?? 'U', 0, 1));
?>

<!-- TopAppBar Header -->
<header class="flex items-center justify-between py-4 mb-8 border-b border-outline-variant/30 sticky top-0 bg-[#050505]/80 backdrop-blur-md z-40">
    <div class="flex items-center gap-12">
        <span class="text-title-sm font-headline-md font-bold text-on-surface whitespace-nowrap">FinOps Central</span>
        <nav class="hidden md:flex items-center gap-8">
            <a href="<?= raizUrl('/dashboard.php') ?>" 
               class="<?= $is_dashboard ? 'text-primary font-bold border-b-2 border-primary pb-2' : 'text-on-surface-variant hover:text-on-surface' ?> text-sm transition-colors pb-2">
                Dashboard
            </a>
            <a href="<?= raizUrl('/financeiro/lancamentos.php') ?>" 
               class="<?= $is_lancamentos ? 'text-primary font-bold border-b-2 border-primary pb-2' : 'text-on-surface-variant hover:text-on-surface' ?> text-sm transition-colors pb-2">
                Lançamentos
            </a>
            <a href="<?= raizUrl('/financeiro/configuracoes.php') ?>" 
               class="<?= $is_custos ? 'text-primary font-bold border-b-2 border-primary pb-2' : 'text-on-surface-variant hover:text-on-surface' ?> text-sm transition-colors pb-2">
                Custos
            </a>
            <a href="<?= raizUrl('/precificacao/simulador.php') ?>" 
               class="<?= $is_simulador ? 'text-primary font-bold border-b-2 border-primary pb-2' : 'text-on-surface-variant hover:text-on-surface' ?> text-sm transition-colors pb-2">
                Simulador
            </a>
        </nav>
    </div>
    
    <div class="flex items-center gap-4">
        <!-- Notification Button -->
        <button class="hover:bg-surface-container-highest/50 text-on-surface-variant hover:text-on-surface transition-colors p-2 rounded-full flex items-center justify-center relative group" title="Notificações">
            <span class="material-symbols-outlined">notifications</span>
            <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-primary rounded-full border-2 border-[#050505] hidden group-hover:block"></span>
        </button>
        
        <!-- Help Button -->
        <button class="hover:bg-surface-container-highest/50 text-on-surface-variant hover:text-on-surface transition-colors p-2 rounded-full flex items-center justify-center" title="Ajuda">
            <span class="material-symbols-outlined">help_outline</span>
        </button>
        
        <!-- New Entry Action -->
        <a href="<?= raizUrl('/financeiro/lancamentos.php?novo=1') ?>"
           class="bg-primary hover:bg-primary-container text-on-primary-container px-4 py-2 rounded-lg font-bold text-xs transition-all active:scale-95 duration-150 shadow-md whitespace-nowrap">
            Novo Lançamento
        </a>
        
        <!-- User Profile Avatar with Initials -->
        <div class="w-10 h-10 rounded-full overflow-hidden border border-outline-variant bg-[#191916] flex items-center justify-center text-sm font-bold text-primary shrink-0 cursor-pointer hover:border-primary/50 transition-colors" title="<?= sanitizar($usuarioLogado['nome'] ?? '') ?> (<?= sanitizar($usuarioLogado['email'] ?? '') ?>)">
            <span><?= $inicial ?></span>
        </div>
    </div>
</header>
