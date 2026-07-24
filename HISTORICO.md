# Histórico de Alterações - ERP Distinto

## Objetivo do Projeto
ERP Distinto: gestão de propostas comerciais, clientes e exportação PDF. Foco em integração do frontend público (proposta web) com o painel administrativo.

- **Ordenação por Data de Pagamento no Financeiro** *(jun/2026)*:
  - Lançamentos pagos passam a ser ordenados e filtrados no período por sua data de pagamento real (`data_pagamento`).
  - Atualizada a gravação da `data_pagamento` na baixa manual, conciliação OFX, webhook Asaas e ajuste de saldo.

- **Ajustes de Contratos, IA e PDF Dompdf no Servidor** *(jun-jul/2026)*:
  - Flexibilizados regexes de cláusulas de pagamento contra tags extras do CKEditor.
  - Correção da inicialização de vencimento de sinal e DSN do banco de dados PostgreSQL (Neon).
  - Migrado motor de PDF de client-side para o servidor usando a biblioteca **Dompdf**.
  - Correção de erro 500 no webhook Asaas via fallback global de headers em [helpers.php](file:///c:/xampp/htdocs/erp-distinto/includes/helpers.php).
  - Adicionado `require_once database.php` no `auth.php` para sanar o erro de `Class "Database" not found` no `contas.php`.

- **Redesign e Ajustes Visuais de Fidelidade do Sistema (Stitch)** *(jul/2026)*:
  - Redesenhados o Dashboard e Top Nav com grid plano de 12 colunas, categorias de despesas limpas (sem cards internos de fundo), compatibilidade de fontes Hanken Grotesk e dados reais de KPIs e Fluxo de Caixa.
  - Customizado o `tailwind.config.js` com cores, espaçamentos (`gap-card-gap`) e fontes do protótipo e recompilado o Tailwind de produção.
  - Removidas larguras máximas fixas (`max-w-[calc(100vw-240px)]`) dos contêineres principais de todas as 9 páginas para permitir dimensionamento fluido.
  - Corrigida a duplicidade oculta da lógica de carregamento do banco de dados no `dashboard.php` que realizava requisições redundantes.

- **Importação de Telas e Design System (Stitch)** *(jul/2026)*:
  - Obtido o código e imagens do projeto Stitch para a pasta `/design/`.
  - Baixados os códigos e screenshots das telas *Dashboard Otimizado* e *Configurações Organizas*.
  - Salva a especificação técnica do *Design System* (`design_system.md`) e gerado o seu respectivo arquivo HTML (`design_system.html`) e captura de tela em alta fidelidade (`design_system.png`).

- **Forçar Modo Escuro e Branding Distinto** *(jul/2026)*:
  - Definida a classe `dark` de forma estática no elemento `<html>` em [head.php](file:///c:/xampp/htdocs/erp-distinto/includes/layout/head.php).
  - Removido o botão de alternar modo e lógica JS associada em [sidebar.php](file:///c:/xampp/htdocs/erp-distinto/includes/layout/sidebar.php) e [footer.php](file:///c:/xampp/htdocs/erp-distinto/includes/layout/footer.php), consolidando o sistema de forma permanente no modo escuro.
  - Substituída a marca provisória "FinOps Central" pelo nome dinâmico da aplicação (`APP_NAME`) em [top_nav.php](file:///c:/xampp/htdocs/erp-distinto/includes/layout/top_nav.php).

## Diretrizes para Futuras IDEs / Agentes
1. **Idioma**: Sempre responda em Português do Brasil.
2. **Commit**: Sugira título de commit em português ao finalizar uma ação.
3. **Segurança**: Mantenha integridade das funções PHP. Sanitize sempre antes de reinjetar HTML.
4. **Histórico**: Mantenha este `HISTORICO.md` com menos de 70 linhas.
5. **Resiliência**: Toda chamada de API no frontend deve ter `try/catch` para não bloquear o usuário.
6. **Aditivos**: Mudanças de plano pós-contrato são aditivos manuais pelo administrador.
