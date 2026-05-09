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
    const weddingContainer = document.querySelector('.wedding-proposal');

    function showButton() {
        if (!btn) return;
        
        const scrollSource = weddingContainer || wrapper;
        // Só mostra se houver algum movimento de scroll real
        if (scrollSource.scrollTop > 10) {
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
        // Mostrar EXCLUSIVAMENTE ao rolar dentro do wrapper ou container de casamento
        wrapper.addEventListener('scroll', showButton, { passive: true });
        if (weddingContainer) {
            weddingContainer.addEventListener('scroll', showButton, { passive: true });
        }
    }
});

// 3. Funções de Exportação PDF (Globais para evitar erros de onclick)
window.showExportModal = function() {
    const modal = document.getElementById('export-modal');
    if (modal) modal.style.display = 'flex';
};

window.hideExportModal = function() {
    const modal = document.getElementById('export-modal');
    if (modal) modal.style.display = 'none';
};

window.exportPDF = function(orientation) {
    window.hideExportModal();
    
    // 1. Cria ou atualiza o estilo dinâmico para a orientação
    let style = document.getElementById('print-orientation-style');
    if (!style) {
        style = document.createElement('style');
        style.id = 'print-orientation-style';
        document.head.appendChild(style);
    }
    
    // Força a orientação no @page
    style.innerHTML = `
        @media print {
            @page { 
                size: ${orientation === 'horizontal' ? 'A4 landscape' : 'A4 portrait'}; 
                margin: 0 !important; 
            }
        }
    `;
    
    // 2. Adiciona as classes de estado para o CSS agir
    document.body.classList.add('exporting-pdf', orientation === 'horizontal' ? 'export-horizontal' : 'export-vertical');
    
    // Garante que todas as páginas estejam visíveis (remove delay de animação)
    document.querySelectorAll('.proposal-page').forEach(p => p.classList.add('is-visible'));

    // 3. Dispara a impressão nativa
    // Pequeno delay para o navegador processar as novas regras de CSS
    setTimeout(() => {
        window.print();
        
        // 4. Limpeza após fechar a janela de impressão
        document.body.classList.remove('exporting-pdf', 'export-horizontal', 'export-vertical');
    }, 500);
};
