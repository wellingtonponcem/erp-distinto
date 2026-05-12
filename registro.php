<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Já logado → vai direto para os roteiros
if (estaAutenticado()) {
    header('Location: ' . raizUrl('/roteiros/index.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar conta grátis — Meus Roteiros</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --bg: #0a0a0a; --surface: #111; --surface2: #181818;
            --border: rgba(255,255,255,0.07); --accent: #E8FF47; 
            --text: #F0EDE6; --muted: #888;
            --serif: 'Instrument Serif', Georgia, serif;
            --sans: 'DM Sans', sans-serif;
            --display: 'Bebas Neue', sans-serif;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--bg); color: var(--text);
            font-family: var(--sans); min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 2rem 1rem;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 3rem 2.5rem;
            width: 100%; max-width: 440px;
        }
        .logo {
            font-size: 32px;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .logo span:first-child {
            font-family: var(--display);
            color: var(--text);
            text-transform: uppercase;
        }
        .logo span:last-child {
            font-family: var(--serif);
            font-style: italic;
            color: var(--accent);
            font-weight: 400;
        }
        .headline {
            font-family: var(--serif);
            font-style: italic;
            font-size: 24px;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--text);
            opacity: 0.9;
        }
        .trial-badge {
            display: inline-block;
            background: rgba(232,255,71,0.08);
            border: 1px solid rgba(232,255,71,0.2);
            color: var(--accent);
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
            margin-bottom: 2rem;
        }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block; font-size: 11px;
            color: var(--muted); margin-bottom: 6px;
            text-transform: uppercase; letter-spacing: 0.1em;
        }
        .form-control {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 12px 14px;
            border-radius: 8px;
            font-family: var(--sans);
            font-size: 15px;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: rgba(232,255,71,0.4);
        }
        .btn {
            width: 100%;
            padding: 14px;
            border-radius: 100px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            margin-top: 0.5rem;
        }
        .btn-primary {
            background: var(--accent);
            color: #0a0a0a;
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(232,255,71,0.2); }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
        .divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 2rem 0;
        }
        .footer-link {
            text-align: center;
            font-size: 14px;
            color: var(--muted);
        }
        .footer-link a { color: var(--accent); text-decoration: none; font-weight: 600; }
        .footer-link a:hover { text-decoration: underline; }
        .erro-msg {
            background: rgba(255,71,71,0.1);
            border: 1px solid rgba(255,71,71,0.3);
            color: #ff6b6b;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .beneficios {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 1.5rem;
        }
        .beneficio {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: var(--muted);
        }
        .beneficio span:first-child { color: var(--accent); font-size: 14px; }
    </style>
    <script>
        function registroApp() {
            return {
                form: { nome: '', email: '', senha: '', confirmar_senha: '' },
                loading: false,
                erro: '',

                registrar() {
                    this.erro = '';

                    if (!this.form.nome || !this.form.email || !this.form.senha) {
                        this.erro = 'Preencha todos os campos obrigatórios.';
                        return;
                    }
                    if (this.form.senha.length < 8) {
                        this.erro = 'A senha deve ter pelo menos 8 caracteres.';
                        return;
                    }
                    if (this.form.confirmar_senha && this.form.confirmar_senha !== this.form.senha) {
                        this.erro = 'As senhas não coincidem.';
                        return;
                    }

                    this.loading = true;

                    fetch('<?= raizUrl('/api/auth/registro.php') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form)
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.ok) {
                            window.location.href = data.redirect || '<?= raizUrl('/roteiros/index.php') ?>';
                        } else {
                            this.erro = data.erro || 'Erro ao criar conta. Tente novamente.';
                            this.loading = false;
                        }
                    })
                    .catch(() => {
                        this.erro = 'Erro de conexão. Verifique sua internet.';
                        this.loading = false;
                    });
                }
            };
        }
    </script>
</head>
<body x-data="registroApp()">

    <div class="card">
        <div class="logo"><span>MEUS</span> <span>Roteiros</span></div>
        <div class="headline">Crie roteiros que<br>geram resultado.</div>

        <div class="trial-badge">✦ 35 DIAS GRÁTIS · SEM CARTÃO</div>

        <div class="beneficios">
            <div class="beneficio"><span>✓</span><span>IA aprende sua narrativa e voz</span></div>
            <div class="beneficio"><span>✓</span><span>Gere roteiros com briefing ou do zero</span></div>
            <div class="beneficio"><span>✓</span><span>Funciona offline no celular</span></div>
        </div>

        <div x-show="erro" class="erro-msg" x-text="erro" style="display: none;"></div>

        <div class="form-group">
            <label>Nome completo</label>
            <input type="text" class="form-control" x-model="form.nome"
                placeholder="Seu nome" autocomplete="name" @keydown.enter="registrar()">
        </div>
        <div class="form-group">
            <label>E-mail</label>
            <input type="email" class="form-control" x-model="form.email"
                placeholder="seu@email.com" autocomplete="email" @keydown.enter="registrar()">
        </div>
        <div class="form-group">
            <label>Senha <span style="color: var(--muted); font-weight:300;">(mínimo 8 caracteres)</span></label>
            <input type="password" class="form-control" x-model="form.senha"
                placeholder="••••••••" autocomplete="new-password" @keydown.enter="registrar()">
        </div>
        <div class="form-group">
            <label>Confirmar senha</label>
            <input type="password" class="form-control" x-model="form.confirmar_senha"
                placeholder="••••••••" autocomplete="new-password" @keydown.enter="registrar()">
        </div>

        <button class="btn btn-primary" @click="registrar()" :disabled="loading" style="color: #000 !important;">
            Começar gratuitamente →
        </button>

        <hr class="divider">

        <div class="footer-link">
            Já tem conta? <a href="login-roteiros.php" style="color: var(--accent); font-weight: 700; text-decoration: underline; display: inline-block; margin-left: 5px;">Fazer login →</a>
        </div>
        <div class="footer-link" style="margin-top: 0.75rem;">
            <a href="<?= raizUrl('/landing.php') ?>" style="color: var(--muted);">← Voltar para a página inicial</a>
        </div>
    </div>

</body>
</html>
