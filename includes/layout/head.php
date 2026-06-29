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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

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
            font-family: 'Outfit', Arial, sans-serif;
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
            width: 256px !important;
            min-height: calc(100vh - 32px) !important;
            height: calc(100vh - 32px) !important;
            margin-left: 0;
            flex-shrink: 0;
            position: sticky;
            top: 16px;
            color: #111111;
            background: #f7f3da;
            border-right: none;
            border-radius: 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            overflow: hidden;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar.collapsed {
            width: 88px !important;
        }
        
        .sidebar.collapsed .hide-on-collapse {
            display: none !important;
        }

        #main-content,
        .content-sheet {
            flex: 1;
            min-width: 0;
            min-height: calc(100vh - 32px);
            margin: 0;
            padding: 30px 34px !important;
            overflow-y: auto;
            max-width: none !important;
            background: #fbfbfb;
            border: 0;
            border-radius: 32px;
            box-shadow: none;
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

        @media (max-width: 1024px) {
            .sidebar {
                width: 92px !important;
                margin-left: 0;
                top: 0;
                height: 100vh !important;
                min-height: 100vh !important;
            }

            .sidebar .sidebar-copy,
            .sidebar .nav-section,
            .sidebar .nav-label,
            .sidebar .user-meta {
                display: none;
            }

            .nav-link {
                justify-content: center;
                padding: 12px;
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
                width: auto !important;
                height: auto !important;
                min-height: auto !important;
                margin: 10px;
                border-radius: 22px;
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

            .app-topbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        /* Estilos Modo Escuro */
        .dark body { background: #000000; color: #f1f1f1; }
        .dark #main-content, 
        .dark .content-sheet { background: #0a0a0a; border-color: #1a1a1a; }
        .dark .sidebar { background: #111111; color: #ffffff; border: 1px solid #222; box-shadow: none; }
        .dark .nav-link { color: #888; }
        .dark .nav-link:hover, .dark .nav-link.ativo { color: #fff; background: rgba(255,255,255,0.08); }
        .dark .card { background: #111111; border-color: #222222; box-shadow: none; }
        .dark .card:hover { border-color: #333333; }
        .dark .page-title { color: #ffffff; }
        .dark .page-subtitle { color: #777777; }
        .dark .app-topbar { border-color: #222222; }
        .dark .top-nav a { color: #aaaaaa; }
        .dark .top-nav a:hover { background: #1a1a1a; color: #ffffff; }
        .dark .label { color: #aaaaaa; }
        .dark .input, .dark .select { background: #1a1a1a; border-color: #333333; color: #ffffff; }
        .dark .input:focus, .dark .select:focus { border-color: #444444; box-shadow: 0 0 0 3px rgba(255,255,255,0.05); }
        .dark select option, .dark .input option, .dark .select option { background-color: #1a1a1a !important; color: #ffffff !important; }
        .dark .table-header { background: #1a1a1a; color: #888888; border-color: #222222; }
        .dark .table-row { border-color: #1a1a1a; }
        .dark .table-cell { color: #dddddd; }
        .dark .btn-secondary { background: #111111; color: #ffffff; border-color: #333333; }
        .dark .btn-secondary:hover { background: #1a1a1a; border-color: #444444; }
        .dark .trend-up { background: rgba(0,135,88,0.1); color: #00c882; }
        .dark .trend-down { background: rgba(196,59,59,0.1); color: #ff6b6b; }
        .dark .modal { background: #111111; border-color: #222222; color: #ffffff; }
        .dark .modal-overlay { background: rgba(0,0,0,0.85); }
        .dark ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }

        /* Ajustes específicos para cores forçadas inline e classes Tailwind */
        .dark [style*="#f1f5f9"], .dark [style*="#e2e8f0"], .dark [style*="#cbd5e1"], .dark [style*="#ffffff"] { color: #f1f1f1 !important; }
        .dark [style*="#94a3b8"], .dark [style*="#6b7280"], .dark [style*="#4b5563"], .dark [style*="#8a8a8a"] { color: #999999 !important; }
        .dark [style*="background:#ffffff"], .dark [style*="background:#fbfbfb"], .dark [style*="background: #ffffff"], .dark [style*="background: #fbfbfb"] { background: #111111 !important; }
        .dark [style*="border-color:#eeeeee"], .dark [style*="border-color:#ececec"], .dark [style*="border: 1px solid #ececec"] { border-color: #222222 !important; }
        
        .dark .text-zinc-950 { color: #ffffff !important; }
        .dark .text-zinc-900 { color: #f1f1f1 !important; }
        .dark .text-zinc-800 { color: #e4e4e7 !important; }
        .dark .text-zinc-700 { color: #d4d4d8 !important; }
        .dark .text-zinc-500 { color: #a1a1aa !important; }
        .dark .text-zinc-400 { color: #71717a !important; }
        .dark .bg-zinc-50 { background-color: #18181b !important; }
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
    </style>
</head>
<body>
