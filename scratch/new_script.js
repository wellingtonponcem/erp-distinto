    document.addEventListener('DOMContentLoaded', () => {
        // Forçar topo
        window.scrollTo(0, 0);
        
        const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const items = entry.target.querySelectorAll('.reveal-item');
                    items.forEach((item, index) => {
                        setTimeout(() => { item.classList.add('active'); }, index * 150);
                    });
                }
            });
        }, observerOptions);

        document.querySelectorAll('.slide').forEach(slide => { observer.observe(slide); });

        // Bloqueios de Imagem
        document.addEventListener('contextmenu', (e) => { if (e.target.tagName === 'IMG') e.preventDefault(); }, false);
        document.addEventListener('dragstart', (e) => { if (e.target.tagName === 'IMG') e.preventDefault(); }, false);

        // Funções do Modal
        window.openInteractiveModal = function () {
            const modal = document.getElementById('slide-pacote');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                if (window.lucide) lucide.createIcons();
            }
        };

        window.closeInteractiveModal = function () {
            const modal = document.getElementById('slide-pacote');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        };
        
        // Se houver hash na URL (como #portfolio), remove para não pular
        if (window.location.hash) {
            history.replaceState(null, null, ' ');
            window.scrollTo(0, 0);
        }
    });
