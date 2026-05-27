# Histórico de Alterações - ERP Distinto

## Objetivo do Projeto
Melhorias no ERP Distinto, com foco na consistência visual entre a proposta web e a exportação do PDF via template.

## Alterações Recentes
- **Negrito no Editor de Template e Exportação PDF**:
  - Ajustado o texto padrão de `experiencias_distintas_texto` em `includes/pdf_templates.php` e no editor `pdf_template_editor.php` para incluir o nome do casal e a última frase em negrito usando a tag `<b>`.
  - Atualizado o editor `pdf_template_editor.php` (no palco do Alpine.js com `x-html` e na janela de `preview`) para permitir a renderização de tags `<b>` e `<strong>` com segurança.
  - Modificado o arquivo `assets/js/propostas.js` na função `exportPdfTemplate` para interpretar tags HTML básicas de negrito (`<b>` e `<strong>`) utilizando `innerHTML` em vez de `textContent`, após sanitização.

## Planejado (Nova Fase)
- **Upscaling de Fontes**: Adicionar a propriedade de escala individual `scale` aos campos de texto no editor e renderizadores.
- **Páginas Dinâmicas de Pacotes**: Multiplicar automaticamente as páginas de pacotes no PDF com base nos planos ativos na proposta, mapeando inclusive a foto ilustrativa correspondente (`pacote_foto`).

## Diretrizes para Futuras IDEs / Agentes
1. **Idioma**: Sempre responda em Português do Brasil.
2. **Commit**: Sempre dê sugestões de título para o commit em português ao finalizar uma ação.
3. **Padrão de Código**: Mantenha a integridade das funções PHP existentes e de sua segurança. Ao interpretar HTML nos templates PDF, sempre realize o escape de caracteres indesejados antes de re-injetar as tags `<b>` e `<strong>` seguras.
4. **Histórico**: Mantenha este arquivo `HISTORICO.md` atualizado e com menos de 70 linhas de extensão.
