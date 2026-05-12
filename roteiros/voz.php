<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();

$db = Database::get();
$usuario = usuarioAtual();

// Garante que a tabela de configurações por usuário exista
$db->exec("CREATE TABLE IF NOT EXISTS roteiros_config_usuario (
    user_id VARCHAR(32) PRIMARY KEY,
    persona TEXT,
    estilo TEXT,
    tom_voz TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $persona = $_POST['persona'] ?? '';
    $estilo  = $_POST['estilo'] ?? '';
    $tom     = $_POST['tom_voz'] ?? '';

    $stmt = $db->prepare("INSERT INTO roteiros_config_usuario (user_id, persona, estilo, tom_voz, updated_at) 
                         VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP) 
                         ON CONFLICT (user_id) DO UPDATE SET 
                            persona = EXCLUDED.persona, 
                            estilo = EXCLUDED.estilo, 
                            tom_voz = EXCLUDED.tom_voz,
                            updated_at = CURRENT_TIMESTAMP");
    $stmt->execute([$usuario['id'], $persona, $estilo, $tom]);
    $mensagem = "Identidade da sua IA atualizada!";
}

// Buscar valores atuais
$stmt = $db->prepare("SELECT * FROM roteiros_config_usuario WHERE user_id = ?");
$stmt->execute([$usuario['id']]);
$config = $stmt->fetch() ?: [];

$tituloPagina = "Voz & Estilo";
include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper" style="display:flex; min-height:100vh;">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" style="flex:1; padding:40px; background: #0a0a0a; color: #fff;">
        <div style="max-width: 700px; margin: 0 auto;">
            
            <div style="margin-bottom: 40px;">
                <h1 style="font-family: var(--display); font-size: 42px; line-height: 1; margin-bottom: 8px;">Voz & <em>Estilo</em></h1>
                <p style="color: var(--muted); font-size: 15px;">Ensine a IA como você fala para que os roteiros pareçam 100% autorais.</p>
            </div>

            <?php if ($mensagem): ?>
                <div style="background: rgba(232,255,71,0.1); border: 1px solid var(--accent); color: var(--accent); padding: 16px; border-radius: 12px; margin-bottom: 24px; font-size: 14px;">
                    <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i> <?= $mensagem ?>
                </div>
            <?php endif; ?>

            <form method="POST" style="background: var(--surface); border: 1px solid var(--border); padding: 32px; border-radius: 24px; display: flex; flex-direction: column; gap: 32px;">
                
                <div class="form-group">
                    <label style="display: block; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 12px;">Quem é você? (Persona)</label>
                    <textarea name="persona" rows="3" placeholder="Ex: Sou um estrategista digital focado em alta conversão para infoprodutos de ticket alto..."
                        style="width: 100%; background: var(--surface2); border: 1px solid var(--border); color: #fff; padding: 16px; border-radius: 12px; font-size: 15px; resize: vertical;"><?= sanitizar($config['persona'] ?? '') ?></textarea>
                    <p style="font-size: 11px; color: var(--muted); margin-top: 8px;">Diga sua profissão, nicho e o que você defende.</p>
                </div>

                <div class="form-group">
                    <label style="display: block; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 12px;">Seu Estilo de Escrita</label>
                    <textarea name="estilo" rows="4" placeholder="Ex: Uso frases curtas, gosto de começar com uma pergunta provocativa, uso gírias de marketing mas mantenho o profissionalismo..."
                        style="width: 100%; background: var(--surface2); border: 1px solid var(--border); color: #fff; padding: 16px; border-radius: 12px; font-size: 15px; resize: vertical;"><?= sanitizar($config['estilo'] ?? '') ?></textarea>
                    <p style="font-size: 11px; color: var(--muted); margin-top: 8px;">Descreva o ritmo do seu texto e vícios de linguagem que gosta de usar.</p>
                </div>

                <div class="form-group">
                    <label style="display: block; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 12px;">Tom de Voz</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <?php 
                        $tons = ['Agressivo', 'Educativo', 'Inspirador', 'Cético', 'Engraçado', 'Direto', 'Provocador', 'Autoridade'];
                        $tomAtual = $config['tom_voz'] ?? 'Educativo';
                        foreach($tons as $t): 
                        ?>
                            <label style="display: flex; align-items: center; gap: 10px; padding: 12px; background: var(--surface2); border: 1px solid <?= $tomAtual == $t ? 'var(--accent)' : 'var(--border)' ?>; border-radius: 10px; cursor: pointer; transition: 0.2s;">
                                <input type="radio" name="tom_voz" value="<?= $t ?>" <?= $tomAtual == $t ? 'checked' : '' ?> style="width: auto;">
                                <span style="font-size: 14px; <?= $tomAtual == $t ? 'color: var(--accent); font-weight: 700;' : '' ?>"><?= $t ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" style="background: var(--accent); color: #000; border: none; padding: 18px; border-radius: 100px; font-weight: 800; cursor: pointer; font-size: 16px; transition: transform 0.2s; text-transform: uppercase; letter-spacing: 0.05em;">
                    Salvar Identidade Visual
                </button>

            </form>

        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
