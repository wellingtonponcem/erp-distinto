# Histórico de Alterações - ERP Distinto

## Objetivo do Projeto
Melhorias no ERP Distinto, com foco na consistência visual entre a proposta web e a exportação do PDF via template.

## Alterações Recentes
- **Negrito no Editor de Template e Exportação PDF**:
  - Ajustado o texto padrão de `experiencias_distintas_texto` em `includes/pdf_templates.php` e no editor `pdf_template_editor.php` para incluir o nome do casal e a última frase em negrito usando a tag `<b>`.
  - Atualizado o editor `pdf_template_editor.php` (no palco do Alpine.js com `x-html` e na janela de `preview`) para permitir a renderização de tags `<b>` e `<strong>` com segurança.
  - Modificado o arquivo `assets/js/propostas.js` na função `exportPdfTemplate` para interpretar tags HTML básicas de negrito (`<b>` e `<strong>`) utilizando `innerHTML` em vez de `textContent`, após sanitização.
- **Páginas Dinâmicas de Pacotes Sequenciais**:
  - Multiplica automaticamente as páginas de pacotes no PDF com base nos planos ativos na proposta de forma sequencial (Heritage, Cinematic e Essencial), mapeando as fotos correspondentes (`pacote_foto`).
  - Adicionado o **terceiro plano mockado** (Registro Essencial) na lista de planos de teste do editor para que o preview simule fielmente as 3 páginas completas.
- **Usabilidade Simplificada de Foto**:
  - Criado o botão direto **"Adicionar Foto do Pacote"** no painel do editor para inserir o campo de imagem dinâmico instantaneamente.
  - Adicionado card informativo de instrução na barra lateral quando o campo `pacote_foto` for selecionado.
- **Formato do Texto (Maiúsculas / Normal)**:
  - Adicionada a propriedade `transform` (`none`/`uppercase`) nos campos dinâmicos do editor de templates para que o usuário possa escolher se o texto deve ficar em caixa alta ou normal.

## Diretrizes para Futuras IDEs / Agentes
1. **Idioma**: Sempre responda em Português do Brasil.
2. **Commit**: Sempre dê sugestões de título para o commit em português ao finalizar uma ação.
3. **Padrão de Código**: Mantenha a integridade das funções PHP existentes e de sua segurança. Ao interpretar HTML nos templates PDF, sempre realize o escape de caracteres indesejados antes de re-injetar as tags `<b>` e `<strong>` seguras.
4. **Histórico**: Mantenha este arquivo `HISTORICO.md` atualizado e com menos de 70 linhas de extensão.
