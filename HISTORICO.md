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
- **Alinhamento Vertical do Texto**:
  - Adicionada a propriedade `valign` (`flex-start`/`center`/`flex-end`) nos campos do editor de templates para que o usuário controle o alinhamento vertical dentro da caixa cinza.
  - O alinhamento vertical padrão agora é o **Topo** (`flex-start`), impedindo que o texto fique empurrado para a parte inferior ("lá embaixo") na caixa de texto por padrão no editor, no preview e na exportação final.
- **Textos Completos e Ricos dos Pacotes (Heritage, Cinematic, Essencial)**:
  - Adicionados fallbacks com os textos ricos padrão completos nos pacotes em `includes/pdf_templates.php`, garantindo consistência total com os benefícios exibidos na proposta web e no banco.
  - Implementada a função PHP `formatarItemRico($linha)` em `includes/pdf_templates.php` para automaticamente formatar cada item do pacote como uma lista com a bolinha `• ` no início e o título (texto antes do caractere `:`) em **negrito** e a descrição regular.
  - Atualizados os dados mockados no editor `pdf_template_editor.php` (`values` e `planosMockados`) para simularem os textos reais ricos com as bolinhas e tags `<b>` em toda a sua extensão na pré-visualização.
  - Implementada a função PHP `obterBeneficiosTexto()` no início de `proposta_nova.php` e `proposta_editar.php` para carregar e formatar os benefícios completos do banco separados por quebra de linha (`\n`), injetando com `json_encode` no Alpine de forma 100% segura contra erros de sintaxe JS.
  - Ajustadas as funções `fieldPreview` em `pdf_template_editor.php` e a função de renderização `exportPdfTemplate` em `assets/js/propostas.js` para converterem quebras de linha `\n` em `<br>` ao injetarem no HTML, garantindo espaçamento e separação perfeita entre os itens.
- **Campo Dinâmico de Condição Especial e Resolução de Imagens Quebradas**:
  - Incluído o campo `condicao_especial` no PDF, nos planos e nas seções `$camposPorSessao` do editor, com dados mockados.
  - Corrigido o bug de imagens quebradas nos mockups dos pacotes (`pacote_foto`) no palco do editor envolvendo os caminhos estáticos com a função PHP `raizUrl()`. Isso garante que o servidor resolva os caminhos corretamente mesmo rodando em subpastas no Apache/Nginx.

## Diretrizes para Futuras IDEs / Agentes
1. **Idioma**: Sempre responda em Português do Brasil.
2. **Commit**: Sempre dê sugestões de título para o commit em português ao finalizar uma ação.
3. **Padrão de Código**: Mantenha a integridade das funções PHP existentes e de sua segurança. Ao interpretar HTML nos templates PDF, sempre realize o escape de caracteres indesejados antes de re-injetar as tags `<b>` e `<strong>` seguras.
4. **Histórico**: Mantenha este arquivo `HISTORICO.md` atualizado e com menos de 70 linhas de extensão.
