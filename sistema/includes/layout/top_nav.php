<?php
$current_uri = $_SERVER['REQUEST_URI'];
$is_dashboard = strpos($current_uri, '/dashboard.php') !== false;
$is_lancamentos = strpos($current_uri, '/financeiro/lancamentos.php') !== false;
$is_custos = strpos($current_uri, '/financeiro/configuracoes.php') !== false;
$is_simulador = strpos($current_uri, '/precificacao/simulador.php') !== false;
?>
<style>
    .top-nav-bento {
        background: #fff;
        border-radius: 999px;
        padding: 6px;
        display: inline-flex;
        gap: 4px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    }

    .dark .top-nav-bento {
        background: #111;
        border: 1px solid #222;
    }

    .top-nav-bento a {
        padding: 10px 18px;
        font-size: 13px;
        font-weight: 600;
        color: #555;
        border-radius: 999px;
        transition: 0.2s;
    }

    .dark .top-nav-bento a {
        color: #aaa;
    }

    .top-nav-bento a:hover {
        background: #f0f0f0;
        color: #111;
    }

    .dark .top-nav-bento a:hover {
        background: #222;
        color: #fff;
    }

    .top-nav-bento a.active {
        background: #e0f2fe;
        color: #0369a1;
    }

    .dark .top-nav-bento a.active {
        background: #0c4a6e;
        color: #38bdf8;
    }
</style>

<!-- Top Navigation Area -->
<div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-6">
    <div class="top-nav-bento">
        <a href="<?= raizUrl('/dashboard.php') ?>" class="<?= $is_dashboard ? 'active' : '' ?>">Dashboard</a>
        <a href="<?= raizUrl('/financeiro/lancamentos.php') ?>" class="<?= $is_lancamentos ? 'active' : '' ?>">Lançamentos</a>
        <a href="<?= raizUrl('/financeiro/configuracoes.php') ?>" class="<?= $is_custos ? 'active' : '' ?>">Custos</a>
        <a href="<?= raizUrl('/precificacao/simulador.php') ?>" class="<?= $is_simulador ? 'active' : '' ?>">Simulador</a>
    </div>
    <div class="flex items-center gap-3">
        <span
            class="text-xs font-bold text-zinc-500 bg-white dark:bg-zinc-900 px-4 py-2 rounded-full shadow-sm">PT-BR</span>
        <span
            class="text-xs font-bold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-900 px-4 py-2 rounded-full shadow-sm"><?= sanitizar(usuarioAtual()['email']) ?></span>
    </div>
</div>
