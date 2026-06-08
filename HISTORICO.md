# Histórico de Alterações - ERP Distinto

## Objetivo do Projeto
ERP Distinto: gestão de propostas comerciais, clientes e exportação PDF. Foco em integração do frontend público (proposta web) com o painel administrativo.

## Alterações Recentes

- **Datas por Extenso, Acentuações, Toggle de Pré-Wedding e Calculadora de Parcelamento** *(jun/2026)*:
  - Adicionado checkbox "A definir em comum acordo entre as partes" para o Pré-Wedding no formulário de `contrato_gerar.php`.
  - Criado comportamento dinâmico em JS para desabilitar/esmaecer o input de endereço físico quando o toggle estiver ativo.
  - Implementada a renderização de datas de ensaios/evento por extenso (`dataExtenso()`) nas minutas e visualizações dos contratos (`contrato_gerar.php` e `contrato_visualizar.php`).
  - Corrigidas e restauradas as acentuações do português do Brasil em todas as cláusulas do template de Casamento nas páginas `contrato_gerar.php` e `contrato_visualizar.php`.
  - Adicionada uma **Calculadora de Parcelas Automática** que calcula dinamicamente o número de parcelas baseando-se na diferença de meses entre a data de Entrada/Sinal e a do Último Pagamento (em caso de Pix/Boleto), ou permite a seleção manual da quantidade de parcelas (para Cartão de Crédito).

- **Correção do Editor Visual de Contratos** *(jun/2026)*:
  - `contrato_gerar.php` exibia HTML cru porque o CKEditor 5 tinha CDN descontinuada. Substituído por **Quill.js 2**.
  - Corrigidos erros de sintaxe nos headers dos fetches (`'Content-Type: application/json'` -> `'Content-Type': 'application/json'`) que travavam o Alpine.js e deixavam os botões de ação e do anexo invisíveis (tracinhos pretos).
  - Adicionado botão de submissão ("Salvar Minuta") de alta visibilidade no cabeçalho superior (header) da página para facilitar a navegação.
  - Editor exibe o texto do contrato com formatação Sora font, negrito, listas e cabeçalhos.
  - Hidden inputs sincronizam o HTML gerado pelo Quill antes do submit.

- **Correção da Exportação de PDF e Quebras de Página** *(jun/2026)*:
  - Corrigido o corte horizontal de letras e a falta de margens internas no PDF de `contrato_visualizar.php`.
  - Adicionado `margin: [15, 0, 18, 0]` e configurado o parâmetro `pagebreak` com `avoid` de blocos de texto (`p`, `h3`, `h4`, `li`, `tr`, `.pdf-signatures-wrapper`, `table`) nas opções do **`html2pdf.js`** (tanto no download quanto no envio de assinatura).
  - Adicionado `page-break-inside: avoid; break-inside: avoid` no CSS para evitar fracionamento de elementos de texto nas páginas.
  - Reduzido padding vertical do `.a4-page-content` para evitar margens duplas e ajustado `.page-break` na impressão.

- **Integração da Escolha de Plano pelo Cliente (Casamento)** *(jun/2026)*:
  - Criado `api/propostas/escolher-plano.php`: recebe POST com plano + upgrades, calcula `valor_total`, atualiza `status` para `pendente` e registra em `dados_json['cliente_escolha']`.
  - Um evento legível é inserido automaticamente em `andamento_proposta` (JSON), visível no painel admin.
  - `p.php` faz `await fetch()` para a API antes de abrir o WhatsApp. Se a API falhar, o `catch` garante que o cliente prossiga normalmente.
  - Administrador pode alterar o plano ou adicionar upgrades manuais como aditivos contratuais a qualquer tempo.

- **Correção da Corrupção do p.php** *(jun/2026)*:
  - Arquivo estava corrompido (IIFE duplicada, `mRefresh` incompleto). Restaurado via git (`4331ff5`).
  - Adicionado alias `window.openInteractiveModal = window.openPlanModal` para o botão flutuante funcionar.

- **Melhorias de PDF e Editor** *(anteriores)*:
  - Negrito nos templates PDF com `<b>`/`<strong>` via sanitização.
  - Páginas de pacotes vinculadas a `plano_id` com omissão automática se inativo.
  - Reordenação de páginas (▲/▼) no editor, campo `condicao_especial`, alinhamento vertical (`valign`).
  - Correção de reatividade Alpine.js (`:key`, normalização de `is_pacote` para booleano).
  - Fallback robusto de API Gemini via `getenv('GEMINI_API_KEY')`.

## Diretrizes para Futuras IDEs / Agentes
1. **Idioma**: Sempre responda em Português do Brasil.
2. **Commit**: Sugira título de commit em português ao finalizar uma ação.
3. **Segurança**: Mantenha integridade das funções PHP. Sanitize sempre antes de reinjetar HTML.
4. **Histórico**: Mantenha este `HISTORICO.md` com menos de 70 linhas.
5. **Resiliência**: Toda chamada de API no frontend deve ter `try/catch` para não bloquear o usuário.
6. **Aditivos**: Mudanças de plano pós-contrato são aditivos manuais pelo administrador.
