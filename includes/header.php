<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? h($page_title) . ' — Distinto Studio' : 'Distinto Studio'; ?></title>
    <meta name="description" content="<?php echo isset($page_desc) ? h($page_desc) : 'Narrativas visuais que conectam estratégia, estética e emoção.'; ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://player.vimeo.com">
    <link rel="preconnect" href="https://f.vimeocdn.com">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&family=Playfair+Display:ital,wght@0,700;1,700&family=Space+Grotesk:wght@500;700;900&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary-container": "#d4d4d4",
                    "secondary-fixed": "#c8c6c5",
                    "surface-container": "#1f1f1f",
                    "on-tertiary-container": "#131313",
                    "tertiary": "#e2e2e2",
                    "secondary-container": "#474746",
                    "on-error-container": "#ffdad6",
                    "on-secondary": "#1c1b1b",
                    "surface-container-low": "#1b1b1b",
                    "primary-fixed-dim": "#454747",
                    "error": "#ffb4ab",
                    "tertiary-fixed": "#5d5f5f",
                    "inverse-surface": "#e2e2e2",
                    "error-container": "#93000a",
                    "surface-tint": "#c6c6c7",
                    "surface-container-lowest": "#0e0e0e",
                    "surface-bright": "#393939",
                    "on-secondary-fixed-variant": "#3c3b3b",
                    "on-primary": "#1a1c1c",
                    "on-primary-fixed-variant": "#e2e2e2",
                    "on-tertiary-fixed-variant": "#e2e2e2",
                    "on-secondary-container": "#e5e2e1",
                    "on-secondary-fixed": "#1c1b1b",
                    "on-background": "#e2e2e2",
                    "secondary": "#c8c6c5",
                    "secondary-fixed-dim": "#adabaa",
                    "surface-dim": "#131313",
                    "on-tertiary": "#1a1c1c",
                    "outline": "#919191",
                    "inverse-primary": "#5d5f5f",
                    "on-primary-container": "#131313",
                    "on-tertiary-fixed": "#ffffff",
                    "inverse-on-surface": "#303030",
                    "background": "#131313",
                    "on-error": "#690005",
                    "primary-fixed": "#5d5f5f",
                    "on-primary-fixed": "#ffffff",
                    "tertiary-fixed-dim": "#454747",
                    "primary": "#ffffff",
                    "surface-container-highest": "#353535",
                    "outline-variant": "#474747",
                    "surface": "#131313",
                    "surface-variant": "#353535",
                    "on-surface-variant": "#c6c6c6",
                    "surface-container-high": "#2a2a2a",
                    "on-surface": "#e2e2e2",
                    "tertiary-container": "#909191"
                },
                borderRadius: {
                    "DEFAULT": "0px", "lg": "0px", "xl": "0px", "full": "9999px"
                },
                fontFamily: {
                    "headline": ["Space Grotesk"],
                    "body": ["Inter"],
                    "label": ["Space Grotesk"]
                }
            }
        }
    }
    </script>
    <style>
    body {
        background-color: #131313;
        color: #e2e2e2;
        font-family: "Inter", sans-serif;
        overflow-x: hidden;
    }
    .font-headline { font-family: "Space Grotesk", sans-serif; }
    .font-serif { font-family: "Playfair Display", serif; }
    .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
    .text-outline {
        -webkit-text-stroke: 1px rgba(255,255,255,0.3);
        color: transparent;
    }
    .group img {
        filter: grayscale(1);
        transition: filter 0.8s cubic-bezier(0.4,0,0.2,1), transform 0.8s cubic-bezier(0.4,0,0.2,1) !important;
        will-change: filter, transform;
    }
    .group:hover img {
        filter: var(--tw-blur) var(--tw-brightness) var(--tw-contrast) grayscale(0) saturate(0.75) !important;
        transform: scale(1.05);
    }
    .btn-cta-wipe {
        position: relative; overflow: hidden; background-color: transparent;
        color: inherit; transition: color 0.5s ease, border-color 0.5s ease;
        z-index: 1; display: inline-block;
    }
    .btn-cta-wipe::before {
        content: ''; position: absolute; top: 0; left: 0;
        width: 100%; height: 100%; background-color: white;
        transform: translateX(-101%);
        transition: transform 0.6s cubic-bezier(0.19,1,0.22,1); z-index: -1;
    }
    .btn-cta-wipe:hover::before { transform: translateX(0); }
    .btn-cta-wipe:hover { color: black !important; border-color: black !important; }
    .btn-cta-wipe .arrow {
        display: inline-block; opacity: 0; transform: translateX(-10px);
        transition: all 0.4s ease; margin-left: 10px;
    }
    .btn-cta-wipe:hover .arrow { opacity: 1; transform: translateX(0); }
    #assistir-circle {
        position: fixed; width: 110px; height: 110px;
        background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        color: black; font-family: 'Space Grotesk', sans-serif; font-weight: 900;
        font-size: 11px; letter-spacing: 0.15em; pointer-events: none; z-index: 9999;
        opacity: 0; transform: scale(0.5);
        transition: opacity 0.4s ease, transform 0.4s cubic-bezier(0.19,1,0.22,1);
        box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    }
    #assistir-circle.active { opacity: 1; transform: scale(1); }
    .hero-bg-zoom { transform: scale(1); transition: transform 4s cubic-bezier(0.165,0.84,0.44,1); }
    .hero-bg-zoom.zoomed { transform: scale(1.08); }
    </style>
</head>
<body class="bg-surface selection:bg-primary selection:text-on-primary">
<?php
$current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$current_path = rtrim($current_path, '/') ?: '/';
// Remove o BASE_PATH do início para comparar apenas a rota
$route = '/' . ltrim(str_replace(BASE_PATH, '', $current_path), '/');
$route = rtrim($route, '/') ?: '/';

$active = "font-['Space_Grotesk'] tracking-tighter uppercase font-bold text-white";
$normal = "font-['Space_Grotesk'] tracking-tighter uppercase font-medium text-white/60 hover:text-white transition-opacity duration-200";

$nav_items = [
    '/projetos' => ['label' => 'Projetos', 'href' => url('projetos')],
    '/sobre'    => ['label' => 'Estúdio',  'href' => url('sobre')],
    '/contato'  => ['label' => 'Contato',  'href' => url('contato')],
];
?>
<nav class="fixed top-0 w-full z-50 bg-transparent backdrop-blur-xl flex justify-between items-center px-[7%] py-10 mix-blend-difference">
    <a href="<?php echo url(); ?>" class="block w-[200px] text-white hover:opacity-80 transition-opacity">
        <img src="<?php echo asset('imgs/distinto_footes.png'); ?>" alt="Distinto" class="w-full h-auto">
    </a>
    <div class="hidden md:flex gap-12">
        <?php foreach ($nav_items as $path => $item):
            $is_active = ($route === $path || strpos($route, $path) === 0);
            $class = $is_active ? $active : $normal;
        ?>
            <a class="<?php echo $class; ?>" href="<?php echo $item['href']; ?>">
                <?php echo $item['label']; ?>
            </a>
        <?php endforeach; ?>
    </div>
    <button class="md:hidden text-white" aria-label="Menu" id="mobile-menu-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>
</nav>
<!-- Mobile Menu -->
<div id="mobile-menu" class="fixed inset-0 z-40 bg-[#131313]/95 backdrop-blur-xl flex flex-col items-center justify-center gap-12 hidden">
    <button id="mobile-menu-close" class="absolute top-8 right-8 text-white">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <?php foreach ($nav_items as $path => $item): ?>
        <a href="<?php echo $item['href']; ?>" class="font-headline font-black text-4xl uppercase tracking-tighter text-white"><?php echo $item['label']; ?></a>
    <?php endforeach; ?>
</div>
<script>
document.getElementById('mobile-menu-btn')?.addEventListener('click', () => document.getElementById('mobile-menu').classList.remove('hidden'));
document.getElementById('mobile-menu-close')?.addEventListener('click', () => document.getElementById('mobile-menu').classList.add('hidden'));
</script>
