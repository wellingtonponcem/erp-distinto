<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
exigirAutenticacao();

$db = Database::get();
$stmt = $db->query("SELECT * FROM roteiros_conhecimento ORDER BY created_at DESC");
$arquivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Base de Conhecimento — Distinto</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --bg: #0a0a0a;
            --surface: #111111;
            --surface2: #181818;
            --border: rgba(255,255,255,0.07);
            --accent: #E8FF47;
            --text: #F0EDE6;
            --muted: #888;
            --serif: 'Instrument Serif', Georgia, serif;
            --sans: 'DM Sans', sans-serif;
            --display: 'Bebas Neue', sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: var(--sans);
            font-size: 15px;
            line-height: 1.65;
            min-height: 100vh;
        }

        .page-wrap {
            max-width: 800px;
            margin: 0 auto;
            padding: 3rem 2rem 5rem;
        }

        .header { margin-bottom: 3rem; }
        .back-link {
            text-decoration: none;
            color: var(--muted);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1rem;
        }

        h1 {
            font-family: var(--display);
            font-size: 48px;
            line-height: 1;
            color: var(--text);
        }

        .upload-card {
            background: var(--surface);
            border: 2px dashed var(--border);
            padding: 3rem;
            text-align: center;
            border-radius: 12px;
            margin-bottom: 3rem;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .upload-card:hover { border-color: var(--accent); }

        .file-list { display: flex; flex-direction: column; gap: 10px; }
        
        .file-item {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 1rem 1.5rem;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .file-info { display: flex; align-items: center; gap: 15px; }
        .file-icon { color: var(--accent); font-size: 20px; }
        .file-name { font-weight: 500; }
        .file-date { font-size: 12px; color: var(--muted); }

        .btn-delete {
            background: none; border: none; color: #ff4747; cursor: pointer; font-size: 12px;
        }

        input[type="file"] { display: none; }
    </style>
</head>
<body x-data="knowledgeManager()">
    <div class="page-wrap">
        <a href="index.php" class="back-link">← Voltar para Roteiros</a>
        
        <div class="header">
            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.2em; color: var(--accent); margin-bottom: 8px;">Aprendizado IA (NotebookLM Style)</div>
            <h1>Base de <em>Conhecimento</em></h1>
        </div>

        <div class="upload-card" @click="$refs.fileInput.click()">
            <input type="file" x-ref="fileInput" @change="uploadFile($event)" accept=".pdf,.txt,.md">
            <div style="font-size: 40px; margin-bottom: 10px;">📄</div>
            <p style="font-size: 18px; font-family: var(--serif); font-style: italic;">Clique para subir suas aulas, PDFs ou notas</p>
            <p style="font-size: 12px; color: var(--muted); margin-top: 10px;">Suporta PDF, TXT e MD</p>
            <div x-show="uploading" style="margin-top: 20px; color: var(--accent);">Subindo arquivo...</div>
        </div>

        <div class="file-list">
            <template x-for="file in files" :key="file.id">
                <div class="file-item">
                    <div class="file-info">
                        <div class="file-icon" x-text="file.tipo_arquivo === 'pdf' ? '📕' : '📄'"></div>
                        <div>
                            <div class="file-name" x-text="file.nome_arquivo"></div>
                            <div class="file-date" x-text="formatDate(file.created_at)"></div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <span style="font-size: 10px; color: #47ff85;" x-show="file.ativo">Ativo</span>
                        <!-- <button class="btn-delete">Excluir</button> -->
                    </div>
                </div>
            </template>
        </div>
    </div>

    <script>
        function knowledgeManager() {
            return {
                files: <?php echo json_encode($arquivos); ?>,
                uploading: false,

                uploadFile(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    this.uploading = true;
                    const formData = new FormData();
                    formData.append('arquivo', file);

                    fetch('../api/roteiros/upload_conhecimento.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(res => {
                        this.uploading = false;
                        if (res.success) {
                            location.reload();
                        } else {
                            alert('Erro: ' + res.error);
                        }
                    });
                },

                formatDate(dateStr) {
                    return new Date(dateStr).toLocaleDateString('pt-BR');
                }
            }
        }
    </script>
</body>
</html>
