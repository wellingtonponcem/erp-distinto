<?php
$tituloPagina = $tituloPagina ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitizar($tituloPagina) ?> - <?= APP_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= raizUrl('/favicon.svg') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= raizUrl('/favicon_io/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= raizUrl('/favicon_io/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= raizUrl('/favicon_io/favicon-16x16.png') ?>">
    <link rel="manifest" href="<?= raizUrl('/favicon_io/site.webmanifest') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,0..1&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (Compiled for Production) -->
    <link href="<?= raizUrl('/assets/css/tailwind.css') ?>" rel="stylesheet">
    <script>
        // Inicializar modo escuro antes de renderizar para evitar flash
        if (localStorage.getItem('dark-mode') === 'true' || (!localStorage.getItem('dark-mode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <script defer src="<?= raizUrl('/assets/js/alpine.min.js') ?>?v=<?= filemtime(__DIR__ . '/../../assets/js/alpine.min.js') ?>"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    
    <!-- Flatpickr (Date Picker) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/pt.js"></script>

    <style>
        * { box-sizing: border-box; }

        html {
            background: #e0e2eb;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: #050505;
            color: #111111;
            font-family: 'Hanken Grotesk', Arial, sans-serif;
            overflow-x: hidden;
        }

        #app-wrapper {
            position: relative;
            z-index: 1;
            display: flex;
            min-height: 100vh;
            background: transparent;
            padding: 16px;
            gap: 16px;
        }

        .sidebar {
            width: 80px !important;
            overflow: hidden;
            background: #f7f3da;
            border-right: 1px solid rgba(0,0,0,0.05);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar:hover {
            width: 256px !important;
        }

        .sidebar .nav-link {
            padding: 10px 12px;
            border-radius: 10px;
            gap: 0;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .sidebar .nav-label,
        .sidebar .sidebar-copy,
        .sidebar .user-meta,
        .sidebar .nav-section,
        .sidebar .bottom-premium {
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .sidebar:hover .nav-label,
        .sidebar:hover .sidebar-copy,
        .sidebar:hover .user-meta,
        .sidebar:hover .nav-section,
        .sidebar:hover .bottom-premium {
            opacity: 1;
        }

        #main-content,
        .content-sheet {
            flex: 1;
            min-width: 0;
            min-height: calc(100vh - 32px);
            margin: 0 0 0 80px !important;
            padding: 30px 34px !important;
            overflow-y: auto;
            max-width: none !important;
            background: #fbfbfb;
            border: 0;
            border-radius: 32px;
            box-shadow: none;
        }

        /* Layout com sidebar fixa (hover-to-expand) */
        .main-sidebar-fixed {
            margin: 0 0 0 80px !important;
        }

        .app-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin: -6px 0 26px;
            padding: 0 0 18px;
            border-bottom: 1px solid #eeeeee;
        }

        .top-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            overflow-x: auto;
        }

        .top-nav a {
            flex: 0 0 auto;
            color: #222222;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            padding: 8px 10px;
            border-radius: 999px;
        }

        .top-nav a:hover {
            background: #f4f4f4;
        }

        .page-title {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -0.04em;
            color: #111111;
        }

        .page-subtitle {
            margin-top: 4px;
            color: #8a8a8a;
            font-size: 13px;
            font-weight: 500;
        }

        .card {
            background: #ffffff;
            border: 1px solid #ececec;
            border-radius: 12px;
            box-shadow: 0 1px 0 rgba(0,0,0,0.02);
            transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
        }

        .card:hover {
            border-color: #dddddd;
            box-shadow: 0 16px 30px rgba(0,0,0,0.05);
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 13px;
            border-radius: 16px;
            color: #777;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.18s ease;
            white-space: nowrap;
        }

        .nav-link:hover,
        .nav-link.ativo {
            color: #111;
            background: rgba(0,0,0,0.05);
        }

        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 14px;
        }

        .nav-section {
            padding: 22px 13px 8px;
            color: #626262;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .btn-primary,
        .btn-secondary {
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: transform 0.18s ease, background 0.18s ease, border-color 0.18s ease;
        }

        .btn-primary {
            background: #111111;
            color: #ffffff;
            border-color: #111111;
        }

        .btn-primary:hover {
            background: #000000;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #ffffff;
            color: #111111;
            border-color: #eeeeee;
        }

        .btn-secondary:hover {
            background: #f7f7f7;
            border-color: #dddddd;
        }

        .trend-up,
        .trend-down {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .trend-up { color: #008758; background: #ecfbf4; }
        .trend-down { color: #c43b3b; background: #fff1f1; }

        .input,
        .select {
            width: 100%;
            min-height: 40px;
            padding: 9px 12px;
            border: 1px solid #e5e5e5;
            border-radius: 9px;
            background: #ffffff;
            color: #111111;
            font-size: 13px;
            outline: none;
        }

        .input:focus,
        .select:focus {
            border-color: #111111;
            box-shadow: 0 0 0 3px rgba(0,0,0,0.06);
        }

        .label {
            display: block;
            margin-bottom: 6px;
            color: #555555;
            font-size: 12px;
            font-weight: 800;
        }

        .table-header {
            padding: 13px 16px;
            background: #f7f7f7;
            color: #777777;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid #eeeeee;
        }

        .table-row {
            border-bottom: 1px solid #eeeeee;
        }

        .table-cell {
            padding: 14px 16px;
            font-size: 13px;
            color: #222222;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(0,0,0,0.56);
            backdrop-filter: blur(10px);
        }

        .modal {
            width: min(720px, 100%);
            max-height: calc(100vh - 48px);
            overflow-y: auto;
            padding: 24px;
            background: #ffffff;
            color: #111111;
            border-radius: 18px;
            border: 1px solid #e8e8e8;
            box-shadow: 0 30px 80px rgba(0,0,0,0.3);
        }

        #main-content [style*="#f1f5f9"],
        #main-content [style*="#e2e8f0"],
        #main-content [style*="#cbd5e1"] {
            color: #111111 !important;
        }

        #main-content [style*="#94a3b8"],
        #main-content [style*="#6b7280"],
        #main-content [style*="#4b5563"] {
            color: #777777 !important;
        }

        #main-content [style*="rgba(255,255,255,0.04)"],
        #main-content [style*="rgba(255,255,255,0.05)"] {
            background: #ffffff !important;
        }

        #main-content [style*="rgba(255,255,255,0.06)"],
        #main-content [style*="rgba(255,255,255,0.1)"] {
            border-color: #eeeeee !important;
        }

        [x-cloak] { display: none !important; }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.18); border-radius: 999px; }

        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 125, 255, 0.25) transparent;
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.15); border-radius: 10px; }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb { background: rgba(148, 125, 255, 0.4); }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(148, 125, 255, 0.2); }
        .dark .custom-scrollbar:hover::-webkit-scrollbar-thumb { background: rgba(148, 125, 255, 0.5); }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
            line-height: 1;
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 72px !important;
            }
            .sidebar:hover {
                width: 240px !important;
            }

            .nav-link {
                justify-content: center;
                padding: 10px;
            }

            .main-sidebar-fixed {
                margin-left: 72px !important;
            }
            #main-content,
            .content-sheet {
                padding: 24px 18px !important;
            }
        }

        @media (max-width: 760px) {
            #app-wrapper {
                display: block;
                padding: 0;
            }

            .sidebar {
                position: relative;
                top: auto;
                left: auto;
                width: auto !important;
                height: auto !important;
                min-height: auto !important;
                margin: 10px;
                border-radius: 22px;
                overflow: visible;
            }
            .sidebar:hover {
                width: auto !important;
            }

            .sidebar nav {
                display: flex;
                gap: 6px;
                overflow-x: auto;
            }

            #main-content,
            .content-sheet {
                min-height: auto;
                margin: 10px;
                border-radius: 22px;
            }
            .main-sidebar-fixed {
                margin: 10px !important;
            }

            .app-topbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        /* Estilos Modo Escuro (Obsidian Finance System) */
        .dark body { background: #0F0F12; color: #e4e1e6; }
        .dark #main-content, 
        .dark .content-sheet { background: #131316; border-color: #2D2D39; }
        .dark .sidebar { background: #1b1b1e; color: #ffffff; border-right: 1px solid #2D2D39; box-shadow: 4px 0 20px rgba(0,0,0,0.3); }
        .dark .nav-link { color: #c9c4d8; }
        .dark .nav-link:hover, .dark .nav-link.ativo { color: #fff; background: rgba(124,92,255,0.15); }
        .dark .sidebar .nav-link.active-nav { background: #947dff; color: #2a0088; }
        .dark .card { background: #131316; border-color: #2D2D39; box-shadow: none; }
        .dark .card:hover { border-color: #7c5cff; box-shadow: 0 0 20px rgba(124, 92, 255, 0.15); }
        .dark .page-title { color: #ffffff; }
        .dark .page-subtitle { color: #c9c4d8; }
        .dark .app-topbar { border-color: #2D2D39; }
        .dark .top-nav a { color: #c9c4d8; }
        .dark .top-nav a:hover { background: #1f1f22; color: #ffffff; }
        .dark .label { color: #c9c4d8; }
        .dark .input, .dark .select { background: #1f1f22; border-color: #2D2D39; color: #ffffff; }
        .dark .input:focus, .dark .select:focus { border-color: #7c5cff; box-shadow: 0 0 0 3px rgba(124, 92, 255, 0.15); }
        .dark select option, .dark .input option, .dark .select option { background-color: #1f1f22 !important; color: #ffffff !important; }
        .dark .table-header { background: #1f1f22; color: #c9c4d8; border-color: #2D2D39; }
        .dark .table-row { border-color: #2d2d39; }
        .dark .table-cell { color: #e4e1e6; }
        .dark .btn-secondary { background: #1f1f22; color: #ffffff; border-color: #2D2D39; }
        .dark .btn-secondary:hover { background: #2D2D39; border-color: #484555; }
        .dark .btn-primary { background: #7c5cff; color: #ffffff; border-color: #7c5cff; }
        .dark .btn-primary:hover { background: #947dff; border-color: #947dff; }
        .dark .trend-up { background: rgba(16,185,129,0.1); color: #10b981; }
        .dark .trend-down { background: rgba(239,68,68,0.1); color: #ef4444; }
        .dark .modal { background: #131316; border-color: #2D2D39; color: #ffffff; }
        .dark .modal-overlay { background: rgba(14,14,17,0.85); }
        .dark ::-webkit-scrollbar-thumb { background: rgba(148, 125, 255, 0.2); }

        /* Ajustes específicos para cores forçadas inline e classes Tailwind */
        .dark [style*="#f1f5f9"], .dark [style*="#e2e8f0"], .dark [style*="#cbd5e1"], .dark [style*="#ffffff"] { color: #f1f1f1 !important; }
        .dark [style*="#94a3b8"], .dark [style*="#6b7280"], .dark [style*="#4b5563"], .dark [style*="#8a8a8a"] { color: #999999 !important; }
        .dark [style*="background:#ffffff"], .dark [style*="background:#fbfbfb"], .dark [style*="background: #ffffff"], .dark [style*="background: #fbfbfb"] { background: #131316 !important; }
        .dark [style*="border-color:#eeeeee"], .dark [style*="border-color:#ececec"], .dark [style*="border: 1px solid #ececec"] { border-color: #2d2d39 !important; }
        
        .dark .text-zinc-950 { color: #ffffff !important; }
        .dark .text-zinc-900 { color: #f1f1f1 !important; }
        .dark .text-zinc-800 { color: #e4e4e7 !important; }
        .dark .text-zinc-700 { color: #d4d4d8 !important; }
        .dark .text-zinc-500 { color: #a1a1aa !important; }
        .dark .text-zinc-400 { color: #71717a !important; }
        .dark .bg-zinc-50 { background-color: #1f1f22 !important; }
        .dark .border-zinc-100 { border-color: #27272a !important; }
        
        /* Garantir que ícones Lucide sigam a cor do texto no modo dark */
        .dark svg { stroke: currentColor !important; }
        .dark .btn-secondary svg { color: #ffffff !important; }
        .dark .text-zinc-500 svg { color: #a1a1aa !important; }
        /* Classes Premium Distinto - Refinamento de UI */
        .label-premium {
            display: block;
            margin-bottom: 8px;
            color: rgba(0, 0, 0, 0.7);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .dark .label-premium { color: rgba(255, 255, 255, 0.85); }

        .input-readonly {
            background: rgba(0, 0, 0, 0.02) !important;
            border: 1px dashed rgba(0, 0, 0, 0.1) !important;
            cursor: not-allowed;
            color: #666 !important;
        }
        .dark .input-readonly {
            background: rgba(255, 255, 255, 0.03) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            color: #999 !important;
        }

        .card-plan {
            border: 2px solid transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-plan-active {
            border-color: #d4af37 !important; /* Dourado Premium */
            background: rgba(212, 175, 55, 0.03) !important;
            transform: scale(1.01);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.1) !important;
        }
        .dark .card-plan-active {
            background: rgba(212, 175, 55, 0.05) !important;
        }

        .section-header-premium {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #111;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dark .section-header-premium { color: #fff; }

        .contract-block {
            padding: 16px;
            background: #fcfcfc;
            border: 1px solid #eee;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.6;
            color: #555;
        }
        .dark .contract-block {
            background: #151515;
            border-color: #222;
            color: #aaa;
        }

        /* Premium Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 38px;
            height: 20px;
        }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(255,255,255,0.1);
            transition: .4s;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 14px;
            width: 14px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        input:checked + .slider { background-color: #d4af37; border-color: #d4af37; }
        input:checked + .slider:before { transform: translateX(18px); }

        /* Melhoria de Contraste para Upgrades */
        .upgrade-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        .upgrade-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(212, 175, 55, 0.3);
        }
        .dark .upgrade-card {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.05);
        }
        .dark .upgrade-card:hover {
            background: rgba(255, 255, 255, 0.06);
        }
        #main-content [style*="#f1f5f9"],
        #main-content [style*="#e2e8f0"],
        #main-content [style*="#cbd5e1"] {
            color: #ffffff !important;
        }

        /* Classes Adicionais do Redesign Stitch */
        .glass-card {
            background: rgba(19, 19, 22, 0.7) !important;
            backdrop-filter: blur(12px);
            border: 1px solid rgba(45, 45, 57, 0.5) !important;
            transition: all 0.3s ease;
        }
        .dark .glass-card {
            background: rgba(19, 19, 22, 0.8) !important;
            border-color: rgba(45, 45, 57, 0.4) !important;
        }
        .glass-card:hover {
            border-color: #7c5cff !important;
            box-shadow: 0 0 20px rgba(124, 92, 255, 0.15);
        }
        .luminous-gradient {
            background: linear-gradient(135deg, rgba(124, 92, 255, 0.15) 0%, rgba(202, 190, 255, 0.05) 100%) !important;
        }
        .font-data-tabular {
            font-family: 'JetBrains Mono', monospace !important;
            font-size: 14px !important;
            font-weight: 500 !important;
        }
        .font-label-caps {
            font-family: 'JetBrains Mono', monospace !important;
            font-size: 11px !important;
            font-weight: 600 !important;
            letter-spacing: 0.08em !important;
            text-transform: uppercase;
        }
        .font-headline-md {
            font-family: 'Hanken Grotesk', sans-serif !important;
            font-size: 24px !important;
            font-weight: 600 !important;
        }
        .font-display-lg {
            font-family: 'Hanken Grotesk', sans-serif !important;
            font-size: 36px !important;
            font-weight: 700 !important;
            letter-spacing: -0.02em !important;
        }
    </style>
</head>
<body>
