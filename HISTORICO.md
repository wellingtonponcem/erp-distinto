# Histórico de Alterações - ERP Distinto

## Objetivo do Projeto
ERP Distinto: gestão de propostas comerciais, clientes e exportação PDF. Foco em integração do frontend público (proposta web) com o painel administrativo.

## Alterações Recentes

- **Integração de Gateway de Pagamentos Asaas** *(jun/2026)*:
  - Integrada a API v3 do Asaas com geração de faturamento imediato (sinal) e parcelado de contratos assinados (Assinafy/sincronização).
  - Criados os endpoints `gerar_asaas.php` e `webhook_asaas.php` para emissão manual e conciliação bancária automática (recebido/atrasado/cancelado) via webhooks.
  - Criada tela de Gestão/Extrato (`financeiro/asaas.php`) e sidebar lateral em `contrato_visualizar.php` exibindo faturas, boletos, links de Pix e botão de emissão.
  - Desenvolvido script de testes automatizados de resiliência a falhas de rede/API e webhooks (`scratch/teste_asaas_prop.php`).

- **Correção e Sincronização do Assinafy & CRM** *(jun/2026)*:
  - Corrigido erro 404 de sincronização manual removendo `/accounts/{accountId}` das URLs.
  - Adicionado suporte a status `certificated` / `registrado` e mapeamento automático.
  - Integração CRM: alteração de oportunidade para `ganha` e promoção de CPF/CNPJ de signatários na tabela local de clientes ao assinar o contrato.
  - Corrigidos bugs de payload, tags de assinaturas físicas e inicialização do Alpine.js isolando o preview do CKEditor com `x-ignore`.

- **Resolução de Tela em Branco e Conexão Neon (Ambiente Local)** *(jun/2026)*:
  - Criados `config/env.php` e `config/env.example.php` com chaves necessárias (`DB_*`, `APP_*`, `GEMINI_API_KEY`, `SESSION_NAME`, `SESSION_LIFETIME`, etc.), eliminando erros fatais.
  - Habilitadas as extensões `pdo_pgsql` e `pgsql` no `php.ini` do Laragon para dar suporte a conexões PostgreSQL.
  - Configurada a conexão local com o Neon e sincronizada a estrutura financeira (`contratos.asaas_cobranca_gerada`, `clientes.asaas_customer_id`, etc.), corrigindo o erro 500 no faturamento manual.

- **Correção de Transação Abortada (SQLSTATE[25P02]) no Faturamento Asaas** *(jun/2026)*:
  - Identificada falha crítica: sintaxe inválida de `ALTER TABLE ... ADD CONSTRAINT ... WHERE` no PostgreSQL gerava erro de sintaxe, abortando a transação de forma silenciosa e quebrando chamadas subsequentes.
  - Resolvido migrando a restrição única para um índice único parcial nativo (`CREATE UNIQUE INDEX IF NOT EXISTS`) e limpando duplicados da tabela `lancamentos`.
  - Otimizada a rotina de DDL com early return se as colunas já existem e incluído `ROLLBACK` seguro nos blocos catch para restaurar o estado da conexão no PostgreSQL.

- **Automação da Calculadora de Pagamento e Correção de Parcelamento Asaas** *(jun/2026)*:
  - Vinculado o resultado da "Calculadora de Condições de Pagamento" diretamente aos campos de configuração do Asaas na sidebar de `contrato_gerar.php`, preenchendo automaticamente a quantidade de parcelas, valores de sinal e vencimentos ao clicar em calcular.
  - Corrigido bug em `includes/asaas.php` que não enviava o campo obrigatório `dueDate` quando uma cobrança continha parcelas sem sinal, resultando em cobrança única por padrão no Asaas.


## Diretrizes para Futuras IDEs / Agentes
1. **Idioma**: Sempre responda em Português do Brasil.
2. **Commit**: Sugira título de commit em português ao finalizar uma ação.
3. **Segurança**: Mantenha integridade das funções PHP. Sanitize sempre antes de reinjetar HTML.
4. **Histórico**: Mantenha este `HISTORICO.md` com menos de 70 linhas.
5. **Resiliência**: Toda chamada de API no frontend deve ter `try/catch` para não bloquear o usuário.
6. **Aditivos**: Mudanças de plano pós-contrato são aditivos manuais pelo administrador.
