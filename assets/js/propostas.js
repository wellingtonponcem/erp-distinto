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
    const weddingContainer = document.querySelector('.wedding-proposal');

    let scrollTimeout;
    function showButton() {
        if (!btn) return;
        
        const scrollTop = window.pageYOffset || 
                          (weddingContainer ? weddingContainer.scrollTop : 0) || 
                          wrapper.scrollTop || 
                          document.documentElement.scrollTop;

        if (scrollTop > 150) {
            btn.classList.add('show');
            
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                btn.classList.remove('show');
            }, 2000); // Some após 2 segundos de inatividade
        } else {
            btn.classList.remove('show');
        }
    }

    if (btn) {
        window.addEventListener('scroll', showButton, { passive: true });
        wrapper.addEventListener('scroll', showButton, { passive: true });
        if (weddingContainer) {
            weddingContainer.addEventListener('scroll', showButton, { passive: true });
        }
        showButton();
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

window.exportPDFLegacyPrintDisabled = function(orientation) {
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
        console.warn('Exportação por impressão nativa desativada. Use html2pdf.');
        
        // 4. Limpeza após fechar a janela de impressão
        document.body.classList.remove('exporting-pdf', 'export-horizontal', 'export-vertical');
    }, 500);
};

window.exportPDF = async function() {
    window.hideExportModal();

    if (typeof html2pdf === 'undefined') {
        alert('Biblioteca de exportação PDF não carregada. Recarregue a página e tente novamente.');
        return;
    }

    const source = document.querySelector('.proposal-wrapper');
    if (!source) {
        alert('Conteúdo da proposta não encontrado.');
        return;
    }

    const trigger = document.querySelector('.btn-export-top');
    const originalTriggerHTML = trigger ? trigger.innerHTML : '';
    if (trigger) {
        trigger.innerHTML = '<span>Gerando...</span>';
        trigger.disabled = true;
    }

    const stage = document.createElement('div');
    stage.className = 'pdf-export-stage';
    const clone = source.cloneNode(true);

    clone.querySelectorAll('script, .no-print, #slide-pacote, #plan-modal, .export-modal, .fixed-section-title').forEach(el => el.remove());
    const pages = Array.from(clone.querySelectorAll('.proposal-page, .slide'));
    pages.forEach((page, index) => {
        page.classList.add('is-visible', 'pdf-export-page');
        if (index < pages.length - 1) page.classList.add('pdf-export-break');
        page.classList.remove('is-leaving');
    });

    stage.appendChild(clone);
    document.body.appendChild(stage);
    document.body.classList.add('exporting-pdf-html2pdf');

    await new Promise(resolve => setTimeout(resolve, 400));

    const filenameBase = (document.title || 'proposta-comercial')
        .replace(/[\\/:*?"<>|]+/g, '-')
        .replace(/\s+/g, ' ')
        .trim()
        .slice(0, 90) || 'proposta-comercial';

    const options = {
        margin: 0,
        filename: `${filenameBase}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: {
            scale: 2,
            useCORS: true,
            allowTaint: false,
            letterRendering: true,
            backgroundColor: '#ffffff',
            windowWidth: 1600,
            windowHeight: 1131,
            scrollX: 0,
            scrollY: 0
        },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape', compress: true },
        pagebreak: { mode: ['css', 'legacy'], after: '.pdf-export-break' }
    };

    try {
        await html2pdf().set(options).from(clone).save();
    } catch (error) {
        console.error('Erro ao exportar PDF:', error);
        alert('Não foi possível exportar o PDF. Verifique as imagens da proposta e tente novamente.');
    } finally {
        document.body.classList.remove('exporting-pdf-html2pdf');
        stage.remove();
        if (trigger) {
            trigger.innerHTML = originalTriggerHTML;
            trigger.disabled = false;
            if (window.lucide) lucide.createIcons();
        }
    }
};

window.exportPDF = async function() {
    window.hideExportModal();

    if (typeof html2canvas === 'undefined' || !window.jspdf?.jsPDF) {
        alert('Bibliotecas de exportação PDF não carregadas. Recarregue a página e tente novamente.');
        return;
    }

    const source = document.querySelector('.proposal-wrapper');
    if (!source) {
        alert('Conteúdo da proposta não encontrado.');
        return;
    }

    const trigger = document.querySelector('.btn-export-top');
    const originalTriggerHTML = trigger ? trigger.innerHTML : '';
    if (trigger) {
        trigger.innerHTML = '<span>Gerando...</span>';
        trigger.disabled = true;
    }

    const stage = document.createElement('div');
    stage.className = 'pdf-export-stage';
    const clone = source.cloneNode(true);
    clone.querySelectorAll('script, .no-print, #slide-pacote, #plan-modal, .export-modal, .fixed-section-title').forEach(el => el.remove());

    const pages = Array.from(clone.querySelectorAll('.proposal-page, .slide'));
    pages.forEach(page => {
        page.classList.add('is-visible', 'pdf-export-page');
        page.classList.remove('is-leaving');
    });

    stage.appendChild(clone);
    document.body.appendChild(stage);
    document.body.classList.add('exporting-pdf-html2pdf');

    await new Promise(resolve => setTimeout(resolve, 600));

    const filenameBase = (document.title || 'proposta-comercial')
        .replace(/[\\/:*?"<>|]+/g, '-')
        .replace(/\s+/g, ' ')
        .trim()
        .slice(0, 90) || 'proposta-comercial';

    const pdf = new window.jspdf.jsPDF({
        orientation: 'landscape',
        unit: 'mm',
        format: 'a4',
        compress: true
    });

    try {
        for (let index = 0; index < pages.length; index += 1) {
            const canvas = await html2canvas(pages[index], {
                scale: 2,
                useCORS: true,
                allowTaint: false,
                letterRendering: true,
                backgroundColor: '#ffffff',
                width: pages[index].offsetWidth,
                height: pages[index].offsetHeight,
                scrollX: 0,
                scrollY: 0,
                windowWidth: pages[index].offsetWidth,
                windowHeight: pages[index].offsetHeight
            });

            if (index > 0) {
                pdf.addPage('a4', 'landscape');
            }

            const image = canvas.toDataURL('image/jpeg', 0.98);
            pdf.addImage(image, 'JPEG', 0, 0, 297, 210, undefined, 'FAST');
        }

        pdf.save(`${filenameBase}.pdf`);
    } catch (error) {
        console.error('Erro ao exportar PDF:', error);
        alert('Não foi possível exportar o PDF. Verifique as imagens da proposta e tente novamente.');
    } finally {
        document.body.classList.remove('exporting-pdf-html2pdf');
        stage.remove();
        if (trigger) {
            trigger.innerHTML = originalTriggerHTML;
            trigger.disabled = false;
            if (window.lucide) lucide.createIcons();
        }
    }
};
