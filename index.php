<?php
require_once __DIR__ . '/includes/db.php';

$opts = get_settings_all();

$hero_headline  = $opts['hero_headline']  ?? 'NARRATIVAS VISUAIS QUE CONECTAM';
$hero_serif     = $opts['hero_serif']     ?? 'estratégia, estética e emoção';
$hero_video_url = $opts['hero_video_url'] ?? 'https://player.vimeo.com/video/928167937';
$cta_title      = $opts['cta_title']      ?? 'Para marcas que querem ser percebidas.';
$cta_subtitle   = $opts['cta_subtitle']   ?? 'Para histórias que merecem ser eternas.';

preg_match('/vimeo\.com\/(?:video\/)?([0-9]+)/', $hero_video_url, $vm);
$hero_vimeo_id = $vm[1] ?? '928167937';

$services_raw = [
    [
        'title' => 'CAPTURAR', 'id' => '01',
        'sub'   => $opts['service_capturar_sub'] ?? 'Captamos mais do que imagem. Captamos intenção, detalhe e o que realmente importa.',
        'img'   => $opts['service_capturar_img'] ?? '',
        'video' => $opts['service_capturar_vid'] ?? 'https://player.vimeo.com/external/371433846.sd.mp4?s=231da6ab57f95c6ef4247514a6e8770dabc88730&profile_id=164&oauth2_token_id=57447761',
        'zi'    => 'z-10',
    ],
    [
        'title' => 'NARRAR', 'id' => '02',
        'sub'   => $opts['service_narrar_sub'] ?? 'Toda marca e toda história precisa de direção. Aqui, transformamos momentos em narrativa.',
        'img'   => $opts['service_narrar_img'] ?? '',
        'video' => $opts['service_narrar_vid'] ?? 'https://player.vimeo.com/external/494252666.sd.mp4?s=72bd74720e74f1d46c6463fb75ec8ca7d08f582d&profile_id=164&oauth2_token_id=57447761',
        'zi'    => 'z-20',
    ],
    [
        'title' => 'CONSTRUIR', 'id' => '03',
        'sub'   => $opts['service_construir_sub'] ?? 'Não entregamos conteúdo. Construímos percepção, presença e valor.',
        'img'   => $opts['service_construir_img'] ?? '',
        'video' => $opts['service_construir_vid'] ?? 'https://player.vimeo.com/external/373111261.sd.mp4?s=1f1ec45c3ac0e2c8845fc86c8a306c28f7311749&profile_id=164&oauth2_token_id=57447761',
        'zi'    => 'z-30',
    ],
];

// Fallback de thumb via Vimeo oEmbed (sem autenticação)
function get_vimeo_thumb(string $url): string {
    if (!preg_match('/vimeo\.com\/(?:video\/|external\/)?([0-9]+)/', $url, $m)) return '';
    $id = $m[1];
    $cache_file = sys_get_temp_dir() . '/vimeo_' . $id . '.txt';
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 86400) {
        return file_get_contents($cache_file);
    }
    $api = @file_get_contents("https://vimeo.com/api/v2/video/{$id}.json");
    if ($api) {
        $data = json_decode($api, true);
        $thumb = $data[0]['thumbnail_large'] ?? '';
        file_put_contents($cache_file, $thumb);
        return $thumb;
    }
    return '';
}

foreach ($services_raw as &$s) {
    if (empty($s['img']) && !empty($s['video'])) {
        $thumb = get_vimeo_thumb($s['video']);
        $s['img'] = $thumb ?: asset('imgs/services/fallback.jpg');
    }
}
unset($s);
$services = $services_raw;

// Portfolio slots (4 projetos em destaque)
$portfolio_items = [];
for ($i = 1; $i <= 4; $i++) {
    $pid = (int)($opts["slot_{$i}"] ?? 0);
    if ($pid > 0) {
        $pj = get_project($pid);
        if ($pj) {
            $portfolio_items[$i] = $pj;
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<main class="w-full">

<!-- Hero Section -->
<section class="relative h-[90vh] flex flex-col justify-center items-center px-[10%] overflow-hidden">
    <style>
    .hero-grain-overlay {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        pointer-events: none; z-index: 5; opacity: 0.06;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='200' height='200' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
        background-size: 200px 200px;
    }
    .hero-checkerboard {
        position: absolute; inset: 0;
        background-image: linear-gradient(to right, rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,0.03) 1px, transparent 1px);
        background-size: 8vw 8vw; z-index: 3; pointer-events: none;
    }
    #hero-vimeo-wrapper { opacity: 0; transition: opacity 1.5s ease; }
    #hero-vimeo-wrapper.loaded { opacity: 1; }
    </style>

    <div class="absolute inset-0 z-0 opacity-45 hero-bg-zoom">
        <div class="hero-grain-overlay"></div>
        <div class="hero-checkerboard"></div>
        <div class="absolute inset-0 bg-cover bg-center grayscale brightness-[0.4] transition-opacity duration-1000" style="background-color: #131313;"></div>
        <div id="hero-vimeo-wrapper" class="absolute inset-0 w-full h-full grayscale brightness-[0.4] pointer-events-none"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle,_transparent_30%,_rgba(0,0,0,0.8)_100%)] z-[1]"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-black/80 z-[2]"></div>
    </div>

    <div class="relative z-10 w-full flex flex-col items-center">
        <div class="mt-12 flex items-center gap-4 group cursor-pointer" id="play-reel-trigger">
            <div class="w-16 h-1 bg-white/20 relative overflow-hidden">
                <div class="absolute inset-0 bg-white translate-x-[-100%] group-hover:translate-x-0 transition-transform duration-500"></div>
            </div>
            <span class="font-mono text-xs uppercase tracking-widest text-white/60 group-hover:text-white transition-colors duration-300">VEJA COMO ISSO É SENTIDO</span>
        </div>

        <div class="mt-20 max-w-6xl text-center">
            <p class="text-4xl md:text-7xl font-light leading-[1.1] tracking-tight text-white mb-0">
                <span class="font-headline font-black inline-block mb-2"><?php echo h($hero_headline); ?></span><br/>
                <?php
                $serif_parts = explode(',', $hero_serif);
                foreach ($serif_parts as $idx => $part) {
                    echo '<span class="font-serif text-white/90">' . h(trim($part)) . '</span>';
                    if ($idx < count($serif_parts) - 1) echo '<span class="mx-2"> </span>';
                }
                ?>
            </p>
        </div>

        <div class="mt-24 flex flex-col items-center gap-4">
            <span class="font-mono text-xs tracking-widest text-white/40 uppercase">SCROOL TO VIEW MORE</span>
            <div class="w-px h-24 bg-gradient-to-b from-white to-transparent opacity-30"></div>
        </div>
    </div>
</section>

<!-- Video Popup Modal -->
<div id="video-modal" class="fixed inset-0 z-[100] invisible opacity-0 transition-all duration-500 ease-out flex items-center justify-center">
    <div class="absolute inset-0 bg-[#131313]/90 backdrop-blur-2xl" id="modal-backdrop"></div>
    <div class="relative w-[75vw] aspect-video scale-95 opacity-0 transition-all duration-500 delay-100 ease-out z-[105]" id="modal-content">
        <div class="absolute inset-[-40px] bg-white/15 blur-[100px] rounded-[100px] pointer-events-none z-[-1]"></div>
        <button id="close-modal" class="absolute -top-12 -right-12 z-[110] text-white/50 hover:text-white transition-colors duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <div id="vimeo-player-container" class="w-full h-full shadow-[0_0_120px_rgba(0,0,0,0.9)] rounded-[30px] overflow-hidden border border-white/20 bg-[#131313]"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const trigger = document.getElementById('play-reel-trigger');
    const modal = document.getElementById('video-modal');
    const modalContent = document.getElementById('modal-content');
    const playerContainer = document.getElementById('vimeo-player-container');
    const closeBtn = document.getElementById('close-modal');
    const backdrop = document.getElementById('modal-backdrop');
    const vimeoId = '<?php echo $hero_vimeo_id; ?>';

    function openModal() {
        modal.classList.remove('invisible');
        modal.classList.add('opacity-100');
        modalContent.classList.add('scale-100', 'opacity-100');
        document.body.style.overflow = 'hidden';
        playerContainer.innerHTML = `<iframe src="https://player.vimeo.com/video/${vimeoId}?autoplay=1&badge=0&autopause=0" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" style="width:100%;height:100%;" title="Distinto Studio Reel"></iframe>`;
    }
    function closeModal() {
        modal.classList.remove('opacity-100');
        modalContent.classList.remove('scale-100', 'opacity-100');
        setTimeout(() => { modal.classList.add('invisible'); playerContainer.innerHTML = ''; document.body.style.overflow = ''; }, 500);
    }
    trigger?.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.classList.contains('invisible')) closeModal(); });
});

window.addEventListener('load', function() {
    setTimeout(function() {
        var wrapper = document.getElementById('hero-vimeo-wrapper');
        if (wrapper) {
            wrapper.innerHTML = '<iframe src="https://player.vimeo.com/video/<?php echo $hero_vimeo_id; ?>?background=1&autoplay=1&loop=1&muted=1&quality=720p" frameborder="0" allow="autoplay; fullscreen" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) scale(1.3);width:115vw;height:115vh;"></iframe>';
            setTimeout(() => wrapper.classList.add('loaded'), 100);
        }
    }, 2000);
});
</script>

<!-- Manifesto Services -->
<section class="bg-background overflow-hidden select-none pb-20 pt-0 relative z-30">
    <div class="flex flex-col">
        <?php foreach ($services as $index => $s):
            $isLast = ($index === count($services) - 1);
            $v_url  = str_replace(['dl=0','dl=1'], 'raw=1', $s['video']);
        ?>
        <div class="group relative w-full <?php echo ($index === 0) ? 'h-[12vw]' : 'h-[8vw]'; ?> hover:h-[30vw] transition-all duration-700 ease-in-out flex items-center px-[10%] bg-background <?php echo $s['zi']; ?> hover:z-50 overflow-visible"
             onmouseenter="const v=this.querySelector('video');if(v){v.play().catch(()=>{})}"
             onmouseleave="this.querySelector('video')?.pause()">

            <div class="absolute right-0 inset-y-0 w-[60%] z-0 grayscale group-hover:grayscale-[0.8] overflow-hidden"
                 style="-webkit-mask-image:linear-gradient(to right,transparent,black 40%);mask-image:linear-gradient(to right,transparent,black 40%);">
                <img src="<?php echo h($s['img']); ?>" alt="<?php echo h($s['title']); ?>" class="absolute inset-0 w-full h-full object-cover object-center">
                <video src="<?php echo h($v_url); ?>" loop muted playsinline preload="metadata" class="absolute inset-0 w-full h-full object-cover object-center opacity-0 group-hover:opacity-100 transition-opacity duration-700"></video>
                <div class="absolute inset-0 bg-[#131313] opacity-20 group-hover:opacity-0 transition-opacity duration-700 z-10"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-[#131313] via-transparent to-transparent opacity-90 z-20"></div>
            </div>

            <div class="relative z-30 w-full flex justify-between items-center h-full pointer-events-none">
                <div class="flex flex-col h-full justify-center <?php echo ($index === 0) ? 'pt-12' : ''; ?>">
                    <span class="font-mono text-[0.7vw] text-white/30 lowercase mb-[-1vw] ml-1">service_<?php echo $s['id']; ?></span>
                    <h2 class="font-headline font-black text-[10vw] leading-none uppercase tracking-tighter text-white drop-shadow-[0_15px_15px_rgba(0,0,0,0.8)]">
                        <?php echo $s['title']; ?>
                    </h2>
                </div>
                <style>.texto-sobre-imagem{mix-blend-mode:difference;color:white!important}</style>
                <div class="hidden md:block text-right pr-10 max-w-[30vw] relative z-20">
                    <p class="texto-sobre-imagem font-mono text-[0.8vw] uppercase tracking-[0.1em] leading-relaxed">
                        <?php echo h($s['sub']); ?>
                    </p>
                </div>
            </div>
            <?php if (!$isLast): ?><div class="absolute inset-x-0 bottom-0 h-[6vw] bg-gradient-to-t from-[#131313] to-transparent z-20 pointer-events-none"></div><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<script>
window.addEventListener('load', () => {
    setTimeout(() => { document.querySelectorAll('section video').forEach(v => { v.preload = 'auto'; }); }, 2000);
});
</script>

<!-- Featured Selection -->
<section class="py-40 px-[10%] bg-background relative z-40">
    <div class="flex justify-between items-end mb-24 border-b border-white/10 pb-8">
        <h2 class="font-headline font-black text-[3.5vw] uppercase tracking-tighter leading-[0.9] text-white">
            NÃO É SOBRE O QUE VOCÊ VÊ.<br/>
            <span class="text-[2.2vw] text-white/30 italic font-serif">É SOBRE O QUE ISSO PROVOCA.</span>
        </h2>
        <a href="<?php echo url('projetos'); ?>" class="font-mono text-[0.7vw] tracking-widest text-white/40 hover:text-white transition-colors uppercase">ARCHIVE</a>
    </div>

    <div class="grid grid-cols-12 gap-x-12 gap-y-40">
        <?php if (!empty($portfolio_items)): ?>

            <?php if (isset($portfolio_items[1])): $p = $portfolio_items[1]; ?>
            <div class="col-span-12 md:col-span-6 relative group">
                <div class="relative aspect-[4/5] overflow-hidden bg-neutral-900 mb-8">
                    <?php if ($p['media_type'] === 'video' && !empty($p['video_url'])): ?>
                        <video src="<?php echo h($p['video_url']); ?>" autoplay loop muted playsinline class="w-full h-full object-cover grayscale brightness-75 group-hover:scale-105 group-hover:brightness-90 transition-all duration-1000"></video>
                    <?php else: ?>
                        <img src="<?php echo h($p['thumbnail_url']); ?>" alt="<?php echo h($p['title']); ?>" class="w-full h-full object-cover grayscale brightness-75 group-hover:scale-105 group-hover:brightness-90 transition-all duration-1000">
                    <?php endif; ?>
                    <h3 class="absolute top-[5%] left-1/2 -translate-x-1/2 z-20 font-headline font-black text-[10vw] text-white/10 uppercase select-none pointer-events-none transition-all duration-700 mix-blend-screen whitespace-nowrap"><?php echo h($p['overlay_title'] ?? $p['title']); ?></h3>
                </div>
                <div class="flex justify-between items-center font-mono text-[0.7vw] text-white/30 uppercase tracking-[0.2em]">
                    <span><?php echo h($p['storytelling'] ?? ''); ?></span>
                    <span>01 / 04</span>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($portfolio_items[2])): $p = $portfolio_items[2]; ?>
            <div class="col-span-12 md:col-start-8 md:col-span-5 mt-32 group">
                <div class="aspect-square overflow-hidden bg-neutral-900 mb-10">
                    <?php if ($p['media_type'] === 'video' && !empty($p['video_url'])): ?>
                        <video src="<?php echo h($p['video_url']); ?>" autoplay loop muted playsinline class="w-full h-full object-cover grayscale brightness-90 group-hover:scale-105 transition-all duration-1000"></video>
                    <?php else: ?>
                        <img src="<?php echo h($p['thumbnail_url']); ?>" alt="<?php echo h($p['title']); ?>" class="w-full h-full object-cover grayscale brightness-90 group-hover:scale-105 transition-all duration-1000">
                    <?php endif; ?>
                </div>
                <div class="flex justify-between items-end">
                    <div class="space-y-2">
                        <h4 class="font-headline font-bold text-2xl uppercase tracking-tight text-white"><?php echo h($p['title']); ?></h4>
                        <p class="font-mono text-[0.7vw] text-white/30 uppercase tracking-[0.2em]"><?php echo h($p['storytelling'] ?? ''); ?></p>
                    </div>
                    <span class="font-mono text-[0.7vw] text-white/30 uppercase tracking-[0.2em] mb-1">02 / 04</span>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($portfolio_items[3])): $p = $portfolio_items[3]; ?>
            <div class="col-span-12 md:col-span-5 relative group">
                <div class="relative aspect-[3/5] overflow-hidden bg-neutral-900 mb-8">
                    <?php if ($p['media_type'] === 'video' && !empty($p['video_url'])): ?>
                        <video src="<?php echo h($p['video_url']); ?>" autoplay loop muted playsinline class="w-full h-full object-cover grayscale brightness-50 group-hover:brightness-75 transition-all duration-1000"></video>
                    <?php else: ?>
                        <img src="<?php echo h($p['thumbnail_url']); ?>" alt="<?php echo h($p['title']); ?>" class="w-full h-full object-cover grayscale brightness-50 group-hover:brightness-75 transition-all duration-1000">
                    <?php endif; ?>
                    <div class="absolute inset-y-0 right-0 flex items-center translate-x-[35%]">
                        <h4 class="font-headline font-black text-[10vw] uppercase text-white/10 rotate-90 leading-none select-none pointer-events-none"><?php echo h($p['overlay_title'] ?? $p['title']); ?></h4>
                    </div>
                </div>
                <div class="flex justify-between items-center font-mono text-[0.7vw] text-white/30 uppercase tracking-[0.2em]">
                    <span><?php echo h($p['storytelling'] ?? ''); ?></span>
                    <span>03 / 04</span>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($portfolio_items[4])): $p = $portfolio_items[4]; ?>
            <div class="col-span-12 md:col-start-7 md:col-span-6 group pt-10">
                <div class="aspect-video overflow-hidden bg-neutral-900 mb-10">
                    <?php if ($p['media_type'] === 'video' && !empty($p['video_url'])): ?>
                        <video src="<?php echo h($p['video_url']); ?>" autoplay loop muted playsinline class="w-full h-full object-cover grayscale brightness-75 group-hover:scale-105 transition-all duration-1000"></video>
                    <?php else: ?>
                        <img src="<?php echo h($p['thumbnail_url']); ?>" alt="<?php echo h($p['title']); ?>" class="w-full h-full object-cover grayscale brightness-75 group-hover:scale-105 transition-all duration-1000">
                    <?php endif; ?>
                </div>
                <div class="flex justify-between items-end">
                    <div class="space-y-2">
                        <h4 class="font-headline font-bold text-2xl uppercase tracking-tight text-white"><?php echo h($p['title']); ?></h4>
                        <p class="font-mono text-[0.7vw] text-white/30 uppercase tracking-[0.2em]"><?php echo h($p['storytelling'] ?? ''); ?></p>
                    </div>
                    <span class="font-mono text-[0.7vw] text-white/30 uppercase tracking-[0.2em] mb-1">04 / 04</span>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <p class="col-span-12 text-white/40 font-mono text-center">Nenhum projeto selecionado nos destaques. Configure no painel admin.</p>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Section -->
<section class="min-h-[60vh] flex flex-col justify-center items-center bg-white text-black text-center relative z-40 py-32 border-t border-black/5">
    <div class="max-w-6xl mx-auto px-[10%] flex flex-col items-center">
        <h3 class="font-serif italic text-2xl md:text-3xl mb-4 text-black/60"><?php echo h($cta_title); ?></h3>
        <span class="font-headline font-black text-4xl md:text-[6vw] leading-none uppercase tracking-tighter block mb-24 text-center">
            <?php echo h($cta_subtitle); ?>
        </span>
        <a href="<?php echo url('contato'); ?>" class="btn-cta-wipe px-20 py-8 font-headline font-black text-xs uppercase tracking-[0.4em] rounded-none border-2 border-black">
            <span>QUERO CONTAR MINHA HISTÓRIA <span class="arrow">></span></span>
        </a>
    </div>
</section>

</main>

<div id="assistir-circle">ASSISTIR</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const circle = document.getElementById('assistir-circle');
    const trigger = document.getElementById('play-reel-trigger');
    const heroContent = document.querySelector('.hero-bg-zoom');
    let mouseX = 0, mouseY = 0, circleX = 0, circleY = 0;

    document.addEventListener('mousemove', e => {
        mouseX = e.clientX; mouseY = e.clientY;
        if (trigger) {
            const rect = trigger.getBoundingClientRect();
            const tx = rect.left + rect.width / 2, ty = rect.top + rect.height / 2;
            const dist = Math.hypot(mouseX - tx, mouseY - ty);
            if (dist < 300) {
                circle.classList.add('active');
                heroContent?.classList.add('zoomed');
                if (dist < 100) {
                    trigger.style.transform = `translate(${(mouseX-tx)*0.2}px,${(mouseY-ty)*0.2}px)`;
                } else { trigger.style.transform = 'translate(0,0)'; }
            } else {
                circle.classList.remove('active');
                heroContent?.classList.remove('zoomed');
                trigger.style.transform = 'translate(0,0)';
            }
        }
    });

    function updateCircle() {
        circleX += (mouseX - circleX) * 0.15;
        circleY += (mouseY - circleY) * 0.15;
        if (circle) { circle.style.left = `${circleX-50}px`; circle.style.top = `${circleY-50}px`; }
        requestAnimationFrame(updateCircle);
    }
    updateCircle();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
