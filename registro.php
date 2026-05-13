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
    <title>Criar conta — <?= APP_NAME ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --bg: #050505; 
            --surface: #0a0a0a;
            --border: rgba(255,255,255,0.05);
            --accent: #fff;
            --text: #f1f5f9;
            --muted: #6b7280;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--bg); 
            color: var(--text);
            font-family: 'Outfit', sans-serif; 
            min-height: 100vh;
            display: flex; 
            align-items: center; 
            justify-content: center;
            padding: 2rem 1rem;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 3rem 2.5rem;
            width: 100%; 
            max-width: 460px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
        }
        .logo-container {
            width: 56px;
            height: 56px;
            background: #111;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }
        .headline {
            font-size: 28px;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 8px;
            color: #fff;
            letter-spacing: -0.05em;
        }
        .subheadline {
            font-size: 15px;
            color: var(--muted);
            margin-bottom: 24px;
            font-weight: 500;
        }
        .trial-badge {
            display: inline-block;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            color: #fff;
            padding: 8px 16px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.02em;
            margin-bottom: 32px;
        }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label {
            display: block; 
            font-size: 11px;
            color: #4b5563; 
            margin-bottom: 8px;
            text-transform: uppercase; 
            letter-spacing: 0.05em;
            font-weight: 700;
        }
        .form-control {
            width: 100%;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            color: var(--text);
            padding: 12px 16px;
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            transition: all 0.2s;
            outline: none;
        }
        .form-control:focus {
            border-color: rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.05);
            box-shadow: 0 0 0 4px rgba(255,255,255,0.03);
        }
        .btn {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            margin-top: 0.5rem;
        }
        .btn-primary {
            background: #f1f5f9;
            color: #050505;
        }
        .btn-primary:hover { 
            transform: translateY(-1px); 
            background: #fff;
            box-shadow: 0 10px 20px -5px rgba(255,255,255,0.1); 
        }
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
            font-weight: 500;
        }
        .footer-link a { color: #fff; text-decoration: none; font-weight: 700; border-bottom: 1px solid #374151; }
        
        .erro-msg {
            background: rgba(239,68,68,0.05);
            border: 1px solid rgba(239,68,68,0.2);
            color: #f87171;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
        }
        .beneficios {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 32px;
        }
        .beneficio {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: var(--muted);
            font-weight: 500;
        }
        .beneficio span:first-child { color: #fff; font-size: 16px; font-weight: 800; }
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
                            window.location.href = data.redirect || '<?= raizUrl('/dashboard.php') ?>';
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
        <div class="logo-container">
            <img src="<?= raizUrl('/favicon.svg') ?>" alt="Logo Distinto" style="width:32px; height:32px; filter: invert(1);">
        </div>
        <div class="headline">Assuma o controle.</div>
        <p class="subheadline">Gestão financeira completa para sua agência.</p>

        <div class="trial-badge">✨ TESTE POR 30 DIAS · SEM COMPROMISSO</div>

        <div class="beneficios">
            <div class="beneficio"><span>✓</span><span>Fluxo de caixa inteligente</span></div>
            <div class="beneficio"><span>✓</span><span>CRM integrado com propostas</span></div>
            <div class="beneficio"><span>✓</span><span>Dashboard Bento UI intuitivo</span></div>
        </div>

        <div x-show="erro" class="erro-msg" x-text="erro" x-cloak></div>

        <div class="form-group">
            <label>Nome da Agência ou Seu Nome</label>
            <input type="text" class="form-control" x-model="form.nome"
                placeholder="Ex: Agência Digital" autocomplete="name" @keydown.enter="registrar()">
        </div>
        <div class="form-group">
            <label>E-mail Corporativo</label>
            <input type="email" class="form-control" x-model="form.email"
                placeholder="seu@email.com" autocomplete="email" @keydown.enter="registrar()">
        </div>
        <div class="form-group">
            <label>Sua Senha</label>
            <input type="password" class="form-control" x-model="form.senha"
                placeholder="No mínimo 8 caracteres" autocomplete="new-password" @keydown.enter="registrar()">
        </div>
        <div class="form-group">
            <label>Confirmar Senha</label>
            <input type="password" class="form-control" x-model="form.confirmar_senha"
                placeholder="Repita a senha" autocomplete="new-password" @keydown.enter="registrar()">
        </div>

        <button class="btn btn-primary" @click="registrar()" :disabled="loading">
            <span x-show="!loading">Começar agora gratuitamente →</span>
            <span x-show="loading">Criando sua conta...</span>
        </button>

        <hr class="divider">

        <div class="footer-link">
            Já possui uma conta? <a href="<?= raizUrl('/index.php') ?>" style="margin-left: 4px;">Fazer login →</a>
        </div>
        <div class="footer-link" style="margin-top: 1.25rem;">
            <a href="<?= raizUrl('/landing.php') ?>" style="color: var(--muted); border: none; font-size: 13px; font-weight: 500;">← Voltar para o site</a>
        </div>
    </div>

</body>
</html>
