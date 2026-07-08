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

- **Redesign de Interface do Sistema (Stitch/Obsidian)** *(jul/2026)*:
  - Redesenhadas 8 telas (Extrato, Custos Fixos, Asaas, Serviços, PDF Templates, Clientes, Pipeline Kanban e Configurações Gerais).
  - Unificado o layout com `app-wrapper`, `sidebar` e `top_nav` em todas as páginas.
  - Aplicadas classes do design system Obsidian (`glass-card`, `font-display-lg`, `label`, `input`, `select`, `btn-primary`, `btn-secondary`).

## Diretrizes para Futuras IDEs / Agentes
1. **Idioma**: Sempre responda em Português do Brasil.
2. **Commit**: Sugira título de commit em português ao finalizar uma ação.
3. **Segurança**: Mantenha integridade das funções PHP. Sanitize sempre antes de reinjetar HTML.
4. **Histórico**: Mantenha este `HISTORICO.md` com menos de 70 linhas.
5. **Resiliência**: Toda chamada de API no frontend deve ter `try/catch` para não bloquear o usuário.
6. **Aditivos**: Mudanças de plano pós-contrato são aditivos manuais pelo administrador.
