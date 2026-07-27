<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php');
    exit;
}

$cliente  = htmlspecialchars(trim($_POST['cliente'] ?? 'Cliente'), ENT_QUOTES, 'UTF-8');
$proposta = trim($_POST['proposta'] ?? '');
$hoje     = date('d/m/Y');
$validade = date('d/m/Y', strtotime('+15 days'));

// Buscar dados da empresa
$db     = Database::get();
$config = $db->query("SELECT * FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch();
$nomeAgencia = $config['nome'] ?? APP_NAME;
$emailAgencia = $config['email'] ?? '';
$telefoneAgencia = $config['telefone'] ?? '';

// Converter markdown simples para HTML
function mdParaHtml(string $texto): string {
    $texto = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    $texto = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $texto);
    $texto = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $texto);
    $texto = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $texto);
    $texto = preg_replace('/^- (.+)$/m', '<li>$1</li>', $texto);
    $texto = preg_replace('/(<li>.*?<\/li>)+/s', '<ul>$0</ul>', $texto);
    $texto = preg_replace('/^---$/m', '<hr>', $texto);
    $texto = str_replace("\n\n", '</p><p>', $texto);
    $texto = str_replace("\n", '<br>', $texto);
    return '<p>' . $texto . '</p>';
}

$propostaHtml = mdParaHtml($proposta);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposta Comercial — <?= $cliente ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Georgia', serif; font-size: 13px; color: #1a1a1a; background: #fff; }

        .cabecalho { background: linear-gradient(135deg, #4c1d95, #1e3a8a); color: white; padding: 40px 48px; }
        .logo-nome { font-size: 28px; font-weight: 700; letter-spacing: -0.5px; margin-bottom: 4px; }
        .logo-subtitulo { font-size: 13px; opacity: 0.7; }
        .cabecalho-titulo { font-size: 13px; opacity: 0.8; margin-top: 32px; text-transform: uppercase; letter-spacing: 1px; }
        .cabecalho-cliente { font-size: 22px; font-weight: 700; margin-top: 6px; }

        .corpo { padding: 40px 48px; }

        .meta-info { display: flex; gap: 40px; margin-bottom: 36px; padding-bottom: 24px; border-bottom: 2px solid #f0f0f0; }
        .meta-item label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; color: #888; display: block; margin-bottom: 4px; }
        .meta-item span { font-size: 14px; font-weight: 600; color: #1a1a1a; }

        .proposta-conteudo h2 { font-size: 18px; font-weight: 700; color: #4c1d95; margin: 28px 0 12px; padding-bottom: 8px; border-bottom: 2px solid #ede9fe; }
        .proposta-conteudo h3 { font-size: 14px; font-weight: 700; color: #1e3a8a; margin: 16px 0 6px; }
        .proposta-conteudo strong { color: #1a1a1a; }
        .proposta-conteudo p { line-height: 1.7; margin-bottom: 10px; color: #374151; }
        .proposta-conteudo ul { padding-left: 20px; margin-bottom: 12px; }
        .proposta-conteudo li { margin-bottom: 5px; color: #374151; line-height: 1.6; }
        .proposta-conteudo hr { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }

        .rodape { margin-top: 60px; padding-top: 24px; border-top: 2px solid #f0f0f0; display: flex; justify-content: space-between; align-items: flex-start; }
        .assinatura { text-align: center; min-width: 200px; }
        .linha-assinatura { border-top: 1px solid #888; padding-top: 8px; font-size: 12px; color: #888; }
        .rodape-contatos { font-size: 12px; color: #888; line-height: 1.7; }
        .rodape-contatos strong { color: #4c1d95; display: block; margin-bottom: 4px; font-size: 13px; }

        .aviso-validade { background: #f5f3ff; border-left: 3px solid #7c3aed; padding: 12px 16px; margin: 24px 0; font-size: 12px; color: #5b21b6; border-radius: 0 6px 6px 0; }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .nao-imprimir { display: none !important; }
        }
    </style>
</head>
<body>

<!-- Botão de impressão (não aparece no PDF) -->
<div class="nao-imprimir" style="background:#f9f9f9; padding:12px 48px; display:flex; gap:12px; align-items:center; border-bottom:1px solid #e5e7eb;">
    <button onclick="window.print()" style="background:#4c1d95; color:white; border:none; padding:8px 20px; border-radius:6px; cursor:pointer; font-size:13px;">⬇ Salvar / Imprimir como PDF</button>
    <span style="font-size:12px; color:#888;">Use Ctrl+P → Salvar como PDF para baixar o arquivo</span>
    <button onclick="window.history.back()" style="margin-left:auto; background:none; border:1px solid #ddd; padding:6px 14px; border-radius:6px; cursor:pointer; font-size:12px; color:#666;">← Voltar</button>
</div>

<!-- Cabeçalho -->
<div class="cabecalho">
    <div class="logo-nome"><?= $nomeAgencia ?></div>
    <div class="logo-subtitulo">Agência de Marketing Digital</div>
    <div class="cabecalho-titulo">Proposta Comercial para</div>
    <div class="cabecalho-cliente"><?= $cliente ?></div>
</div>

<!-- Corpo -->
<div class="corpo">

    <!-- Metadados -->
    <div class="meta-info">
        <div class="meta-item">
            <label>Data de emissão</label>
            <span><?= $hoje ?></span>
        </div>
        <div class="meta-item">
            <label>Válida até</label>
            <span><?= $validade ?></span>
        </div>
        <div class="meta-item">
            <label>Elaborada por</label>
            <span><?= $nomeAgencia ?></span>
        </div>
    </div>

    <!-- Conteúdo da proposta -->
    <div class="proposta-conteudo">
        <?= $propostaHtml ?>
    </div>

    <!-- Aviso de validade -->
    <div class="aviso-validade">
        📅 Esta proposta é válida até <strong><?= $validade ?></strong>. Valores e condições podem sofrer ajustes após esta data.
    </div>

    <!-- Rodapé com assinatura -->
    <div class="rodape">
        <div class="rodape-contatos">
            <strong><?= $nomeAgencia ?></strong>
            <?php if ($emailAgencia): ?><?= $emailAgencia ?><br><?php endif; ?>
            <?php if ($telefoneAgencia): ?><?= $telefoneAgencia ?><br><?php endif; ?>
            <?= $config['cnpj'] ?? '' ?>
        </div>
        <div class="assinatura">
            <div style="height: 50px;"></div>
            <div class="linha-assinatura">
                <?= $nomeAgencia ?><br>Responsável Comercial
            </div>
        </div>
        <div class="assinatura">
            <div style="height: 50px;"></div>
            <div class="linha-assinatura">
                <?= $cliente ?><br>Aprovação do Cliente
            </div>
        </div>
    </div>

</div>

</body>
</html>
