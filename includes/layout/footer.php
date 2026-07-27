<script>
    function initIcons() {
        if (window.lucide) {
            lucide.createIcons();
        }
    }

    // Dark mode toggle (vanilla JS, substitui o Alpine anterior)
    function initDarkModeToggle() {
        const btn = document.getElementById('dark-mode-toggle');
        const icon = document.getElementById('dark-mode-icon');
        const label = document.getElementById('dark-mode-label');
        if (!btn) return;

        function updateUI(isDark) {
            if (icon) icon.textContent = isDark ? 'light_mode' : 'dark_mode';
            if (label) label.textContent = isDark ? 'Claro' : 'Escuro';
        }

        btn.addEventListener('click', function() {
            const isDark = !document.documentElement.classList.contains('dark');
            document.documentElement.classList.toggle('dark', isDark);
            localStorage.setItem('dark-mode', isDark ? 'true' : 'false');
            updateUI(isDark);
        });

        // Sync UI on page load
        const isDark = document.documentElement.classList.contains('dark');
        updateUI(isDark);
    }

    window.toast = function(msg, tipo = 'sucesso') {
        const cores = {
            sucesso: 'border-green-500/20 bg-white text-green-700',
            erro:    'border-red-500/20 bg-white text-red-700',
            aviso:   'border-yellow-500/20 bg-white text-yellow-700',
            info:    'border-blue-500/20 bg-white text-blue-700',
        };
        const t = document.createElement('div');
        t.className = `fixed bottom-6 right-6 z-50 px-5 py-3 rounded-xl border text-sm font-bold shadow-lg \${cores[tipo] || cores.info}`;
        t.style.animation = 'slideIn 0.2s ease';
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => {
            t.style.opacity = '0';
            t.style.transition = 'opacity 0.3s';
            setTimeout(() => t.remove(), 300);
        }, 3000);
    };

    window.formatarMoeda = function(val) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val || 0);
    };

    window.formatarData = function(str) {
        if (!str) return '-';
        const [y, m, d] = str.split('-');
        return `${d}/${m}/${y}`;
    };

    document.addEventListener('DOMContentLoaded', function() {
        initIcons();
        initDarkModeToggle();
    });
    document.addEventListener('alpine:initialized', initIcons);
    
    // Fallback para garantir que ícones carreguem mesmo com atraso de rede
    window.addEventListener('load', function() {
        initIcons();
        initDarkModeToggle();
    });
    setTimeout(initIcons, 500);
    setTimeout(initIcons, 2000);
</script>

<style>
@keyframes slideIn {
    from { transform: translateX(20px); opacity: 0; }
    to   { transform: translateX(0); opacity: 1; }
}
</style>
</body>
</html>
