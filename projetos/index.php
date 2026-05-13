<?php
require_once dirname(__DIR__) . '/includes/db.php';
$page_title = 'Projetos';

// Busca todos os projetos com categorias
$all_projects = [];
try {
    $stmt = db()->query(
        'SELECT p.*,
                STRING_AGG(pc.slug, \' \') AS category_slugs,
                STRING_AGG(pc.name, \',\') AS category_names
         FROM projects p
         LEFT JOIN project_category_relations r ON p.id = r.project_id
         LEFT JOIN project_categories pc ON r.category_id = pc.id
         WHERE p.status = \'published\'
         GROUP BY p.id
         ORDER BY p.menu_order ASC, p.id ASC'
    );
    $all_projects = $stmt->fetchAll();
} catch (PDOException $e) {
    $all_projects = [];
}

// Casamentos ordenados para a sub-barra
$weddings = array_filter($all_projects, fn($p) => str_contains($p['category_slugs'] ?? '', 'casamentos'));

include dirname(__DIR__) . '/includes/header.php';
?>
<main class="pt-40" id="portfolio-main" data-active-cat="casamentos">
<style>
    .portfolio-item { display: none !important; opacity: 0; transform: translateY(15px); transition: opacity 0.5s cubic-bezier(0.16,1,0.3,1), transform 0.5s cubic-bezier(0.16,1,0.3,1); }
    #portfolio-main[data-active-cat="casamentos"] .portfolio-item.is-active { display: block !important; opacity: 1; transform: translateY(0); }
    #portfolio-main:not([data-active-cat="casamentos"]) .portfolio-item.cat-match { display: grid !important; opacity: 1; transform: translateY(0); }
    #collection-filters { transition: opacity 0.4s ease, transform 0.4s ease, height 0.4s ease; transform-origin: top; }
    #portfolio-main:not([data-active-cat="casamentos"]) #collection-filters { opacity: 0 !important; transform: scaleY(0.9); pointer-events: none !important; height: 0 !important; margin: 0 !important; }
    .variant-b .grid-child-1 { order: 3; }
    .variant-b .grid-child-2 { order: 1; }
    .variant-b .grid-child-3 { order: 2; }
    .collection-btn.is-active { color: white !important; border-color: white !important; }
    .asymmetric-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 2rem; }
</style>

<header class="px-[10%] mb-32">
    <h1 class="font-headline font-black text-[7vw] leading-none tracking-tighter uppercase mb-20">
        NÃO CRIAMOS CONTEÚDO.<br/>CRIAMOS PERCEPÇÃO.
    </h1>
    <div class="flex flex-col gap-12 border-t border-white/10 pt-8">
        <div class="flex flex-wrap gap-8 font-mono text-xs tracking-widest uppercase" id="work-filters">
            <button data-filter="casamentos" class="filter-btn text-white transition-all">Casamentos</button>
            <button data-filter="eventos" class="filter-btn text-white/40 hover:text-white transition-all">Eventos</button>
            <button data-filter="branding" class="filter-btn text-white/40 hover:text-white transition-all">Branding</button>
        </div>
        <div class="flex flex-wrap items-center gap-4 h-auto opacity-100 mt-4 overflow-hidden" id="collection-filters">
            <span class="font-mono text-[9px] uppercase tracking-[0.3em] text-white/30">Collections /</span>
            <?php foreach ($weddings as $w): ?>
                <button data-collection="coll-<?php echo $w['id']; ?>" class="collection-btn text-white/40 hover:text-white font-serif italic text-lg transition-all border border-white/10 px-4 py-1 rounded-full text-sm">
                    <?php echo h($w['title']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</header>

<section class="px-[10%] space-y-32 pb-64" id="projects-grid">
    <?php
    $w_idx = 0;
    $o_idx = 0;
    foreach ($all_projects as $proj):
        $curr_id  = $proj['id'];
        $slugs    = $proj['category_slugs'] ?? '';
        $is_wed   = str_contains($slugs, 'casamentos');
        $overlay  = $proj['overlay_title'] ?: $proj['title'];
        $story    = $proj['storytelling'] ?? '';

        if ($is_wed):
            $w_idx++;
            $variant   = ($w_idx % 2 === 0) ? 'variant-b' : 'variant-a';
            $impact    = $proj['impact_title'] ?: $proj['title'];
            $narrative = $proj['narrative_text'] ?: "Captured through the studio's lens.";
            $quote     = $proj['storytelling_quote'] ?: '"Moments captured in eternity."';
            $gallery   = get_project_gallery($curr_id);
    ?>
    <article class="space-y-12 portfolio-item wedding-item" id="coll-<?php echo $curr_id; ?>" data-category="casamentos">
        <div class="flex flex-col md:flex-row justify-between items-baseline border-b border-white/10 pb-8">
            <div>
                <span class="font-mono text-[10px] tracking-[0.3em] uppercase mb-4 text-white/50"><?php printf('%02d', $w_idx); ?> / WEDDING GALLERY</span>
                <h2 class="font-headline font-black text-6xl md:text-8xl tracking-tighter uppercase leading-none"><?php echo h($impact); ?></h2>
            </div>
            <p class="font-serif italic text-2xl text-white/40 md:text-right max-w-xs mt-6 md:mt-0"><?php echo h($narrative); ?></p>
        </div>

        <?php if (!empty($gallery)): ?>
        <div class="grid grid-cols-12 gap-4 h-[120vh] relative <?php echo $variant; ?>">
            <div class="grid-child-1 col-span-12 md:col-span-4 flex flex-col gap-4">
                <div class="h-2/3 bg-neutral-900 overflow-hidden">
                    <img class="w-full h-full object-cover grayscale" src="<?php echo h($gallery[0]['image_url']); ?>"/>
                </div>
                <div class="h-1/3 bg-neutral-800 p-8 flex flex-col justify-center">
                    <p class="font-serif italic text-3xl text-white/90"><?php echo h($quote); ?></p>
                </div>
            </div>
            <div class="grid-child-2 col-span-12 md:col-span-5 flex flex-col gap-4">
                <div class="h-1/3 flex gap-4">
                    <div class="w-1/2 bg-neutral-900 overflow-hidden">
                        <img class="w-full h-full object-cover grayscale" src="<?php echo h($gallery[1]['image_url'] ?? $gallery[0]['image_url']); ?>"/>
                    </div>
                    <div class="w-1/2 bg-neutral-900 overflow-hidden">
                        <img class="w-full h-full object-cover grayscale" src="<?php echo h($gallery[2]['image_url'] ?? $gallery[0]['image_url']); ?>"/>
                    </div>
                </div>
                <div class="h-2/3 bg-neutral-900 overflow-hidden relative group">
                    <img class="w-full h-full object-cover grayscale brightness-50" src="<?php echo h($gallery[3]['image_url'] ?? $gallery[0]['image_url']); ?>"/>
                    <div class="absolute inset-0 flex items-center justify-center p-12 text-center border border-white/5">
                        <p class="font-headline font-black text-4xl uppercase tracking-tighter leading-none text-white"><?php echo h($overlay); ?></p>
                    </div>
                </div>
            </div>
            <div class="grid-child-3 col-span-12 md:col-span-3 h-full">
                <div class="h-full bg-neutral-900 overflow-hidden">
                    <img class="w-full h-full object-cover grayscale" src="<?php echo h($gallery[4]['image_url'] ?? $gallery[0]['image_url']); ?>"/>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </article>

    <?php else:
        $o_idx++;
    ?>
    <article class="asymmetric-grid group portfolio-item" data-category="<?php echo h($slugs); ?>">
        <div class="col-span-12 md:col-span-7 relative overflow-hidden aspect-[4/5] bg-neutral-900">
            <?php if (!empty($proj['thumbnail_url'])): ?>
                <img src="<?php echo h($proj['thumbnail_url']); ?>" alt="<?php echo h($proj['title']); ?>" class="w-full h-full object-cover grayscale group-hover:grayscale-0 duration-700">
            <?php endif; ?>
        </div>
        <div class="col-span-12 md:col-span-5 flex flex-col justify-end pt-8 md:pl-16">
            <span class="font-mono text-[10px] tracking-[0.3em] uppercase mb-4 text-white/50"><?php printf('%02d', $o_idx); ?> / PROJECT</span>
            <h2 class="font-headline font-black text-6xl md:text-8xl tracking-tighter uppercase mb-8 leading-none"><?php echo h($overlay); ?></h2>
            <p class="font-serif text-2xl italic leading-relaxed text-white/80"><?php echo h($story); ?></p>
        </div>
    </article>
    <?php endif; endforeach; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const main = document.getElementById('portfolio-main');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const collBtns = document.querySelectorAll('.collection-btn');
    const allItems = document.querySelectorAll('.portfolio-item');

    function setCategory(cat) {
        main.setAttribute('data-active-cat', cat);
        filterBtns.forEach(btn => {
            const match = btn.getAttribute('data-filter') === cat;
            btn.classList.toggle('text-white', match);
            btn.classList.toggle('text-white/40', !match);
        });
        allItems.forEach(item => {
            const cats = item.getAttribute('data-category') || '';
            item.classList.toggle('cat-match', cats.includes(cat));
        });
        if (cat === 'casamentos') {
            const first = document.querySelector('.collection-btn');
            if (first) first.click();
        }
    }

    function setCollection(collId) {
        allItems.forEach(i => i.classList.remove('is-active'));
        collBtns.forEach(b => b.classList.remove('is-active'));
        const target = document.getElementById(collId);
        if (target) target.classList.add('is-active');
        const btn = document.querySelector(`[data-collection="${collId}"]`);
        if (btn) btn.classList.add('is-active');
    }

    filterBtns.forEach(btn => btn.addEventListener('click', () => setCategory(btn.getAttribute('data-filter'))));
    collBtns.forEach(btn => btn.addEventListener('click', () => setCollection(btn.getAttribute('data-collection'))));

    setCategory('casamentos');
});
</script>
</main>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
