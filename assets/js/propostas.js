/**
 * Lógica da Apresentação de Propostas
 */
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('btn-approve');
    const sections = document.querySelectorAll('.proposal-page');
    const wrapper = document.querySelector('.proposal-wrapper');

    // 1. Intersection Observer para Animações de Slide
    const observerOptions = {
        threshold: 0.5 // Ativa quando 50% da seção estiver visível (ideal para scroll snap)
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
                    document.body.classList.add('scrolled');
                }

                // Toggle show-etapas-title class - Lógica Agressiva
                if (entry.target.classList.contains('is-etapas')) {
                    document.body.classList.add('show-etapas-title');
                } else {
                    document.body.classList.remove('show-etapas-title');
                }

                // Mostrar botão quando entrar em uma nova seção
                if (btn) showButton();
            } else {
                // Ao sair da seção
                entry.target.classList.remove('is-visible');
                
                // Se saiu para cima (scroll down), marca como leaving
                if (entry.boundingClientRect.top < 0) {
                    entry.target.classList.add('is-leaving');
                } else {
                    entry.target.classList.remove('is-leaving');
                }
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
        // Também mostrar ao rolar dentro do wrapper
        wrapper.addEventListener('scroll', showButton);
    }
});
