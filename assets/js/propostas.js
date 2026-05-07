/**
 * Lógica da Apresentação de Propostas
 */
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('btn-approve');
    const sections = document.querySelectorAll('.proposal-page');
    const wrapper = document.querySelector('.proposal-wrapper');

    // 1. Intersection Observer para Animações de Slide
    const observerOptions = {
        threshold: 0.4 // Ativa quando 40% da seção estiver visível
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                entry.target.classList.remove('is-leaving');

                // Toggle on-dark class based on section type
                if (entry.target.classList.contains('dark-page')) {
                    document.body.classList.add('on-dark');
                } else {
                    document.body.classList.remove('on-dark');
                }

                // Toggle scrolled class for HUD logo switch (based on first slide)
                if (entry.target === sections[0]) {
                    document.body.classList.remove('scrolled');
                } else {
                    // Se qualquer outro slide entrar, e o primeiro não estiver visível, garante o scrolled
                    // Mas o IntersectionObserver já cuida disso no 'else' abaixo ou aqui
                }

                // Toggle show-etapas-title class
                if (entry.target.classList.contains('is-etapas')) {
                    document.body.classList.add('show-etapas-title');
                } else {
                    // Só remove se não houver outra seção de etapas visível
                    const visibleEtapas = Array.from(sections).filter(s => 
                        s.classList.contains('is-etapas') && 
                        s.classList.contains('is-visible') && 
                        s !== entry.target
                    );
                    if (visibleEtapas.length === 0) {
                        document.body.classList.remove('show-etapas-title');
                    }
                }

                // Mostrar botão quando entrar em uma nova seção
                if (btn) showButton();
            } else {
                // Ao sair de uma seção de etapas, remove a classe se for a última
                if (entry.target.classList.contains('is-etapas')) {
                    const visibleEtapas = Array.from(sections).filter(s => 
                        s.classList.contains('is-etapas') && 
                        s.classList.contains('is-visible')
                    );
                    if (visibleEtapas.length === 0) {
                        document.body.classList.remove('show-etapas-title');
                    }
                }
                // Se o primeiro slide saiu de vista, ativa o modo scrolled
                if (entry.target === sections[0] && entry.boundingClientRect.top < 0) {
                    document.body.classList.add('scrolled');
                }

                // Se saiu para cima (scroll down), marca como leaving
                if (entry.boundingClientRect.top < 0) {
                    entry.target.classList.add('is-leaving');
                } else {
                    entry.target.classList.remove('is-leaving');
                }
                entry.target.classList.remove('is-visible');
            }
        });
    }, observerOptions);

    sections.forEach(section => observer.observe(section));

    // 2. Lógica do Botão Flutuante
    let timeout;
    function showButton() {
        if (!btn) return;
        btn.classList.add('show');
        clearTimeout(timeout);
        timeout = setTimeout(() => btn.classList.remove('show'), 4000);
    }

    if (btn) {
        showButton(); // Mostrar ao carregar

        // Também mostrar ao mover o mouse ou rolar dentro do wrapper
        wrapper.addEventListener('scroll', showButton);
        window.addEventListener('mousemove', showButton);
    }
});
