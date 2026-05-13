    <footer class="w-full h-[40vw] flex flex-col justify-between pt-16 pb-12 bg-[#131313] px-12 max-w-full relative overflow-hidden">
        <div class="w-full flex justify-between items-start relative z-10 p-[3vw]">
            <div class="flex flex-col items-start">
                <span class="font-mono text-[0.6vw] text-neutral-600 uppercase tracking-[0.3em] mb-8">CONNECT</span>
                <div class="flex flex-col gap-[1vw] font-headline font-bold text-[1vw] tracking-tighter text-neutral-400">
                    <a href="#" class="hover:text-white transition-colors duration-500">INSTAGRAM</a>
                    <a href="#" class="hover:text-white transition-colors duration-500">VIMEO</a>
                    <a href="#" class="hover:text-white transition-colors duration-500">LINKEDIN</a>
                </div>
            </div>
            <div class="max-w-[18vw] text-right mt-24">
                <p class="font-mono text-[0.65vw] text-neutral-500 leading-relaxed uppercase tracking-widest">
                    Para marcas que querem ser percebidas.<br/>
                    Para histórias que merecem ser eternas.
                </p>
            </div>
        </div>

        <div class="absolute bottom-12 left-0 w-[70vw] opacity-[0.03] pointer-events-none select-none z-0 px-12">
            <img src="<?php echo asset('imgs/distinto_footes.png'); ?>" alt="Distinto" class="w-full h-auto">
        </div>

        <div class="w-full flex justify-between items-end relative z-10">
            <div class="font-mono text-[0.7vw] font-bold tracking-[0.2em] text-white">
                DISTINTO STUDIO
            </div>
            <div class="font-mono text-[0.6vw] text-neutral-600 tracking-widest">
                © <?php echo date('Y'); ?> DISTINTO STUDIO. ALL RIGHTS RESERVED.
            </div>
        </div>
    </footer>
</body>
</html>
