# Histórico de Alterações - ERP Distinto

## Objetivo do Projeto
ERP Distinto: gestão de propostas comerciais, clientes e exportação PDF. Foco em integração do frontend público (proposta web) com o painel administrativo.

## Alterações Recentes

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
