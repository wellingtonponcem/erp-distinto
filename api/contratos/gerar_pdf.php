<?php
/**
 * API: Gerar PDF do Contrato
 * Renderiza o contrato em PDF no lado do servidor usando Dompdf.
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

try {
    exigirAutenticacao();

    $id = $_GET['id'] ?? $_POST['id'] ?? '';
    if (!$id) {
        http_response_code(422);
        die("ID do contrato é obrigatório.");
    }

    $db = Database::get();

    // 1. Buscar dados do contrato
    $stmt = $db->prepare("SELECT * FROM contratos WHERE id = ?");
    $stmt->execute([$id]);
    $contrato = $stmt->fetch();

    if (!$contrato) {
        http_response_code(404);
        die("Contrato não encontrado.");
    }

    $dadosJson = json_decode($contrato['dados_json'], true) ?: [];
    $contratoTexto = $dadosJson['contrato_texto'] ?? '';
    $anexoTexto = $dadosJson['anexo_texto'] ?? '';

    // 2. Carregar logotipo em Base64 para garantir a renderização local
    $logoPath = __DIR__ . '/../../assets/logo-contrato.png';
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
    }

    // 3. Montar HTML estruturado para o Dompdf
    $html = '
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Contrato_' . htmlspecialchars($id) . '</title>
        <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;700&display=swap" rel="stylesheet">
        <style>
            @page {
                margin: 20mm 18mm 20mm 18mm;
            }
            body {
                font-family: "Sora", "Arial", sans-serif;
                font-size: 9.5pt;
                color: #231f20;
                line-height: 1.45;
                background-color: #ffffff;
            }
            .pdf-logo-wrapper {
                margin-bottom: 25pt;
                text-align: left;
            }
            .pdf-logo {
                width: 170px;
                height: auto;
                display: block;
            }
            .pdf-body {
                text-align: justify;
            }
            h3 {
                font-size: 14pt;
                font-weight: 700;
                text-transform: uppercase;
                text-align: center;
                margin: 0 0 15pt 0;
                line-height: 1.2;
            }
            .pdf-subtitle {
                font-size: 10pt;
                font-weight: 400;
                margin: 0 0 5pt 0;
                text-align: left;
            }
            .pdf-numero {
                font-size: 10pt;
                font-weight: 400;
                margin: 0 0 15pt 0;
                text-align: left;
            }
            h4 {
                font-size: 10pt;
                font-weight: 700;
                text-transform: uppercase;
                margin: 18pt 0 6pt 0;
                line-height: 1.2;
                page-break-after: avoid;
            }
            p {
                margin: 0 0 10pt 0;
                text-align: justify;
                text-indent: 20pt;
            }
            p.p0, p.pdf-subtitle, p.pdf-numero {
                text-indent: 0 !important;
            }
            p.p-closing {
                text-indent: 0 !important;
                margin-top: 25pt;
            }
            ul, ol {
                margin: 0 0 10pt 0;
                padding-left: 25pt;
            }
            li {
                margin-bottom: 5pt;
                text-align: justify;
            }
            .page-break {
                page-break-before: always;
            }
            
            /* Correções de margens internas de parágrafos vindos do CKEditor */
            .pdf-body p strong {
                font-weight: 700;
            }
        </style>
    </head>
    <body>
        <!-- Página do Contrato -->
        <div class="pdf-logo-wrapper">
            ' . ($logoBase64 ? '<img src="' . $logoBase64 . '" class="pdf-logo" alt="Logo">' : '') . '
        </div>
        <div class="pdf-body">
            ' . $contratoTexto . '
        </div>

        <!-- Quebra de página para o Anexo I -->
        <div class="page-break"></div>

        <div class="pdf-logo-wrapper">
            ' . ($logoBase64 ? '<img src="' . $logoBase64 . '" class="pdf-logo" alt="Logo">' : '') . '
        </div>
        <div class="pdf-body">
            ' . (!empty($anexoTexto) ? $anexoTexto : '<h4>ANEXO I - DESCRIÇÃO DOS SERVIÇOS</h4><p class="p0">A descrição detalhada dos serviços será incluída após a definição do escopo do evento.</p>') . '
        </div>
    </body>
    </html>
    ';

    // 4. Configurar e instanciar o Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true); // Permite carregar fontes do Google Fonts
    $options->set('defaultMediaType', 'print');
    $options->set('dpi', 150);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // 5. Devolver o PDF para o navegador
    $dompdf->stream('Contrato_' . $contrato['id'] . '.pdf', [
        'Attachment' => false // Abre no navegador em vez de forçar o download direto de arquivo
    ]);

} catch (Exception $e) {
    http_response_code(500);
    die("Erro ao gerar PDF: " . $e->getMessage());
}
