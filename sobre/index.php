<?php
require_once dirname(__DIR__) . '/includes/db.php';
$page_title = 'Estúdio';
include dirname(__DIR__) . '/includes/header.php';
?>
<main>
<!-- Hero Section -->
<section class="relative min-h-[85vh] md:min-h-screen flex flex-col justify-end px-[10%] pb-20 md:pb-32 pt-40">
    <div class="absolute top-0 right-0 w-full h-full -z-10 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-t from-background via-transparent to-transparent z-10"></div>
    </div>
    <div class="max-w-[1200px] w-full">
        <span class="font-mono text-[10px] tracking-[0.3em] uppercase text-white/30 mb-2 block">EST. 2020 / STUDIO PROFILE</span>
        <h1 class="font-headline text-[12vw] lg:text-[15vw] leading-[0.8] font-black uppercase tracking-tighter mb-12">
            A FUSÃO<br/>
            <span class="text-outline">DISTINTA</span>
        </h1>
        <div class="grid grid-cols-12 gap-8 mt-12">
            <div class="col-span-12 md:col-span-8 flex gap-12 items-start border-l border-white/20 pl-8">
                <div class="space-y-6">
                    <p class="font-mono text-[8px] tracking-[0.3em] uppercase text-white/80">The Duality / A Essência</p>
                    <p class="font-serif text-2xl md:text-1xl leading-tight text-white/90">
                        A Distinto nasceu de uma constatação clara: em um mercado saturado de ruído,
                        estar presente não é o suficiente; é preciso ser percebido. O que começou como
                        o Poncem Studio evoluiu para uma empresa de transformação de marcas, onde a
                        clareza não é apenas uma escolha estética, mas o fundamento de toda estratégia.<br><br>
                        Nossa essência reside no equilíbrio de forças complementares. De um lado, a
                        precisão técnica, a geometria e a luz de Wellington, que garantem uma execução
                        de alto nível e uma estrutura sólida. Do outro, a alma e a profundidade narrativa
                        de Jeane, que capturam a essência do negócio e criam conexões reais com o público.<br><br>
                        Não acreditamos em "fazer posts" ou em beleza vazia. Acreditamos que marcas
                        extraordinárias são construídas através de um método rigoroso que une análise
                        profunda, posicionamento intencional e estética com propósito.<br><br>
                        <span class="text-white/50">Existimos para
                        tirar negócios do lugar-comum e elevá-los ao status de referência. Porque,
                        para nós, ser só mais um nunca foi uma opção. Ser Distinto é uma escolha.</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- The Collective Section -->
<section class="py-24 md:py-40 bg-background overflow-hidden border-t border-white/5">
    <div class="px-[10%] mb-12 md:mb-20">
        <span class="font-mono text-[10px] tracking-[0.3em] uppercase text-white/40 mb-4 block">Our Team / The Mindset</span>
        <h2 class="font-headline text-6xl md:text-9xl font-black uppercase tracking-tighter leading-none">THE <span class="text-outline">CREW</span></h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:flex lg:flex-row gap-4 h-auto lg:h-[70vh] px-4 md:px-[5%] overflow-hidden">

        <?php
        $crew = [
            ['num' => '01', 'name' => 'MARCUS V.',   'bio' => 'Especialista em iluminação dramática e texturas digitais. Marcus traz o olhar técnico para a composição visual da Distinto.',   'img' => asset('imgs/team/member-01.jpg')],
            ['num' => '02', 'name' => 'ANA CLARA',   'bio' => 'Transformando narrativas complexas em identidades visuais potentes que ressoam no mercado global de luxo.',                    'img' => asset('imgs/team/member-02.jpg')],
            ['num' => '03', 'name' => 'LUCAS SILVA', 'bio' => 'A montagem é onde a alma do projeto ganha ritmo. Lucas domina a cadência cinematográfica da Distinto.',                        'img' => asset('imgs/team/member-03.jpg')],
            ['num' => '04', 'name' => 'FELIPE M.',   'bio' => 'Especialista em sound design e efeitos visuais, Felipe finaliza cada frame com a precisão exigida pela Distinto.',           'img' => asset('imgs/team/member-04.jpg')],
        ];
        foreach ($crew as $m):
        ?>
        <div class="aspect-[4/5] lg:flex-[1] lg:hover:flex-[3] transition-all duration-700 ease-[cubic-bezier(0.23,1,0.32,1)] group relative overflow-hidden bg-neutral-900 border border-white/5">
            <img class="absolute inset-0 w-full h-full object-cover grayscale brightness-50 contrast-125 transition-all duration-700 group-hover:grayscale-0 group-hover:scale-105 group-hover:brightness-75"
                 src="<?php echo $m['img']; ?>" alt="<?php echo $m['name']; ?>"/>
            <div class="absolute inset-x-0 bottom-0 p-[10%] flex flex-col justify-end h-full bg-gradient-to-t from-black via-black/20 to-transparent translate-y-[15%] group-hover:translate-y-0 transition-transform duration-700">
                <h3 class="font-headline text-4xl font-black uppercase tracking-tighter mb-4"><?php echo $m['name']; ?></h3>
                <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-700 delay-200">
                    <p class="font-serif italic text-lg text-white/80 mb-4 max-w-sm"><?php echo $m['bio']; ?></p>
                </div>
            </div>
            <div class="absolute top-8 left-8 mix-blend-difference">
                <span class="font-mono text-[10px] opacity-20 group-hover:opacity-100 transition-opacity"><?php echo $m['num']; ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
</main>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
