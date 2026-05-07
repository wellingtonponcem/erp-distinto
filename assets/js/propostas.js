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

    // 2. Lógica do Botão Flutuante (Exclusiva por Scroll)
    let timeout;
    function showButton() {
        if (!btn) return;
        
        // Só mostra se houver algum movimento de scroll real
        if (wrapper.scrollTop > 10) {
            btn.classList.add('show');
            
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                btn.classList.remove('show');
            }, 6000); // 6 segundos de visibilidade
        } else {
            btn.classList.remove('show');
        }
    }

    if (btn) {
        // Mostrar EXCLUSIVAMENTE ao rolar dentro do wrapper
        wrapper.addEventListener('scroll', showButton, { passive: true });
    }

    // 3. Funções de Exportação PDF
    const exportModal = document.getElementById('export-modal');
    
    window.showExportModal = function() {
        if (exportModal) exportModal.style.display = 'flex';
    };

    window.hideExportModal = function() {
        if (exportModal) exportModal.style.display = 'none';
    };

    window.exportPDF = function(orientation) {
        window.hideExportModal();
        
        // Adiciona classe de orientação para o CSS handle
        document.body.classList.remove('export-horizontal', 'export-vertical');
        document.body.classList.add('exporting-pdf', orientation === 'horizontal' ? 'export-horizontal' : 'export-vertical');
        
        // Pequeno delay para o CSS renderizar antes do print
        setTimeout(() => {
            window.print();
            
            // Remove as classes após o print (ou cancelamento)
            setTimeout(() => {
                document.body.classList.remove('exporting-pdf', 'export-horizontal', 'export-vertical');
            }, 1000);
        }, 300);
    };
});
