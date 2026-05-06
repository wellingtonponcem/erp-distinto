/**
 * Lógica do Botão Flutuante (Propostas)
 */
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('btn-approve');
    if (!btn) return;

    let timeout;
    let isVisible = false;

    function showButton() {
        btn.classList.add('show');
        isVisible = true;
        
        // Limpar timeout anterior se existir
        clearTimeout(timeout);
        
        // Esconder após 3 segundos
        timeout = setTimeout(() => {
            hideButton();
        }, 3000);
    }

    function hideButton() {
        btn.classList.remove('show');
        isVisible = false;
    }

    // Mostrar ao carregar
    showButton();

    // Mostrar ao rolar a página
    window.addEventListener('scroll', function() {
        if (!isVisible) {
            showButton();
        } else {
            // Se já está visível e está rolando, renova o timer de 3s
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                hideButton();
            }, 3000);
        }
    });
});
