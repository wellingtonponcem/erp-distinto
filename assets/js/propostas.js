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
    
    // Elemento que contém os slides
    const element = document.querySelector('.proposal-wrapper');
    const filename = `Proposta - ${document.title}.pdf`;
    
    // 1. Preparação: Adiciona classes para o CSS preparar o layout
    document.body.classList.add('exporting-pdf', orientation === 'horizontal' ? 'export-horizontal' : 'export-vertical');
    
    // Garante que todas as seções estejam visíveis para a captura (sem animações pendentes)
    const allPages = document.querySelectorAll('.proposal-page');
    allPages.forEach(p => p.classList.add('is-visible'));

    // 2. Configurações do html2pdf
    const opt = {
        margin:       0,
        filename:     filename,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { 
            scale: 2, // Aumenta a resolução
            useCORS: true, 
            letterRendering: true,
            scrollY: 0,
            scrollX: 0
        },
        jsPDF:        { 
            unit: 'mm', 
            format: 'a4', 
            orientation: orientation === 'horizontal' ? 'landscape' : 'portrait',
            compress: true
        },
        pagebreak: { 
            mode: ['avoid-all', 'css', 'legacy'],
            before: '.proposal-page' 
        }
    };

    // 3. Execução
    // Pequeno delay para garantir que o CSS de preparação foi aplicado
    setTimeout(() => {
        html2pdf()
            .set(opt)
            .from(element)
            .toPdf()
            .get('pdf')
            .save()
            .then(() => {
                // 4. Limpeza: Remove as classes de exportação
                document.body.classList.remove('exporting-pdf', 'export-horizontal', 'export-vertical');
                // Remove o is-visible forçado se necessário (opcional, já que o scroll vai reativar)
            });
    }, 500);
};
