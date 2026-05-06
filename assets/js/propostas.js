/**
 * Lógica da Apresentação de Propostas
 */
document.addEventListener('DOMContentLoaded', function() {
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
                
                // Mostrar botão quando entrar em uma nova seção
                if (btn) showButton();
            } else {
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
