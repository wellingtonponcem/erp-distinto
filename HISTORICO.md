# Histórico de Alterações - ERP Distinto

## Objetivo do Projeto
ERP Distinto: gestão de propostas comerciais, clientes e exportação PDF. Foco em integração do frontend público (proposta web) com o painel administrativo.

- **Ordenação por Data de Pagamento no Financeiro** *(jun/2026)*:
  - Lançamentos pagos passam a ser ordenados e filtrados no período por sua data de pagamento real (`data_pagamento`), mantendo os pendentes ordenados pelo vencimento.
  - Criada coluna dedicada a "Pagamento" (`data_pagamento`) ao lado do Vencimento, destacando a data em verde se pago e com traço se pendente.
  - Atualizada a gravação da `data_pagamento` na baixa manual (`baixa.php`), importação/conciliação OFX, webhook do Asaas e ajuste de saldo.

- **Ajuste de Codificação e Visualização de Contratos (Mojibake)** *(jun/2026)*:
  - Corrigido o charset mojibake via iconv no `contrato_gerar.php` e habilitado o carregamento correto do CKEditor.

- **Financeiro, Gateway Asaas e Conciliação OFX** *(jun/2026)*:
  - Implementada integração com Asaas API v3, webhooks de conciliação bancária automática e upload/leitura de extrato OFX local.

- **Edição e Fechamento de Propostas & CRM** *(jun/2026)*:
  - Novo stepper guiado com salvamento automático reativo, fluxo de casamento no admin e CRM promovendo status ao assinar contrato.

- **Correções do Sistema e Conexão Neon** *(jun/2026)*:
  - Habilitado PDO PGSQL no Laragon, migração e restrições únicas para índices parciais no Postgres.

- **Ajuste de Exibição de Valores no Contrato** *(jun/2026)*:
  - Atualizado o painel do "Pacote de Casamento" no formulário de geração/edição de contrato (`contrato_gerar.php`) para exibir de forma condicional apenas o input de valor do plano atualmente selecionado.
  - Flexibilizados os regexes de detecção e atualização de cláusulas (`CLÁUSULA SEGUNDA`, `TERCEIRA` e `QUARTA`) no PHP e JS para suportar variações de tags de cabeçalho (`<h3>`, `<h4>`, `<p><strong>` etc.) geradas pelo CKEditor, garantindo o correto preenchimento dinâmico de valores e condições de pagamento.
  - Corrigida a inicialização da calculadora de condições de pagamento, fazendo o campo de data "Vencimento do Sinal" carregar a data de sinal previamente salva no contrato (em vez de sempre resetar para o dia atual).
  - Implementada sincronização mútua e automática em tempo real entre o input "Vencimento do Sinal" da Calculadora e o da seção de "Cobrança Asaas".
  - Corrigido problema de sobreposição visual na pré-visualização de contrato (`contrato_visualizar.php`) aplicando z-index inline (`style="z-index: 9998/9999;"`) em modais e overlays, garantindo correto empilhamento de camadas independentemente do build do Tailwind.
  - Resolvido o travamento da rolagem (scroll) do papel de contrato dentro do modal adicionando as propriedades `min-h-0` e `overflow-y-auto` na div flexível, e utilizando `mx-auto` no elemento filho para centralização correta.
- **Correção de Conexão Neon (SNI) e Acesso ao ERP** *(jun/2026)*:
  - Corrigida a montagem do DSN do Neon em [database.php](file:///c:/xampp/htdocs/erp-distinto/config/database.php) (resolvendo o erro 500) e implementada a auto-migração de colunas de plano em `users`.
  - Corrigido o apontamento incorreto de banco de dados do ERP na Hostinger via reconfiguração do `env.php` de produção, restaurando todos os lançamentos reais.
  - Atualizada a senha de `faustinosdg@gmail.com` no banco Neon de produção para sincronizar com a senha informada, e removidos todos os arquivos de diagnóstico temporários.
  - Corrigida a compatibilidade dos scripts de webhooks do Asaas e Assinafy com o PostgreSQL, substituindo a sintaxe `AUTOINCREMENT` (SQLite) por `SERIAL` para chaves primárias nos logs de auditoria de eventos, solucionando erros 500 silenciosos que causavam pausa de filas no Asaas.
- **Aprimoramento do Anexo I de Contratos via IA** *(jun/2026)*:
  - Atualizado o prompt do Gemini em [ia_propostas.php](file:///c:/xampp/htdocs/erp-distinto/includes/ia_propostas.php) impondo regras estritas de escopo de pacotes, garantindo que o plano Essencial contenha exclusivamente fotografia, sem itens ou entregas de vídeo/audiovisual.
  - Inseridas regras no prompt para detalhar a entrega de fotos (galeria geral com tratamento básico de luz e cor) e a escolha de até 30 fotos pelo casal para tratamento profissional avançado e detalhado.
  - Corrigido o envio de PDFs em branco para o Assinafy em [contrato_visualizar.php](file:///c:/xampp/htdocs/erp-distinto/gerenciamento/contrato_visualizar.php) forçando a remoção da classe de exportação do clone, aplicando cores escuras/background branco diretamente no DOM temporário contra interferências do Modo Escuro, e adicionando um delay de 150ms para garantir o reflow completo antes da captura pelo html2pdf.
  - Corrigidos erros de sintaxe JS (`Uncaught SyntaxError: missing ) after argument list`) em [contrato_visualizar.php](file:///c:/xampp/htdocs/erp-distinto/gerenciamento/contrato_visualizar.php) fechando adequadamente as chamadas de `setTimeout` nas funções `confirmarEnvio()` e `confirmarEnvioAssinatura()`, restaurando a inicialização do Alpine.js.

## Diretrizes para Futuras IDEs / Agentes
1. **Idioma**: Sempre responda em Português do Brasil.
2. **Commit**: Sugira título de commit em português ao finalizar uma ação.
3. **Segurança**: Mantenha integridade das funções PHP. Sanitize sempre antes de reinjetar HTML.
4. **Histórico**: Mantenha este `HISTORICO.md` com menos de 70 linhas.
5. **Resiliência**: Toda chamada de API no frontend deve ter `try/catch` para não bloquear o usuário.
6. **Aditivos**: Mudanças de plano pós-contrato são aditivos manuais pelo administrador.
