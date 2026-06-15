# Histórico de Alterações - ERP Distinto

## Objetivo do Projeto
ERP Distinto: gestão de propostas comerciais, clientes e exportação PDF. Foco em integração do frontend público (proposta web) com o painel administrativo.

## Alterações Recentes

- **Correção no Endpoint de Sincronização do Assinafy** *(jun/2026)*:
  - Causa raiz: Erro 404 (Página não encontrada) ao sincronizar manualmente, pois a rota GET para obter os detalhes do documento incluía erroneamente `/accounts/{accountId}` no início da URL.
  - Solução: Ajustado o endpoint no script `api/contratos/sincronizar_status.php` de `/accounts/{accountId}/documents/{documentId}` para `/documents/{documentId}` de acordo com a API do Assinafy.

- **Sincronização de Status do Assinafy e Correção de Webhook** *(jun/2026)*:
  - Causa raiz: Inconsistência de status no Assinafy (signatários como "Assinado" mas documento "Aguardando assinatura"). Bug no webhook local (conclusão prematura no evento `signer_signed_document`). Ocorrência de erro 500 silencioso se as credenciais estivessem em branco ou se o cURL falhasse, interceptado por proxies/hospedagem (Hostinger) que serviam HTML quebrando o JSON.
  - Solução: Criado o endpoint `api/contratos/sincronizar_status.php` consultando a API do Assinafy diretamente, forçando status local para `assinado` se todos assinaram individualmente. Corrigido webhook para ignorar `signer_signed_document`. Envolvido o endpoint com `catch (Throwable)` e retorno de status HTTP 200 em todas as respostas de falhas JSON de negócio, contornando bloqueios de proxy.

- **Sincronização da Cláusula Terceira (Valor e Condições de Pagamento)** *(jun/2026)*:
  - Causa raiz: Alterações do "Valor Total" e "Condições de Pagamento" no painel lateral não refletiam no HTML do contrato no editor principal (CKEditor), fazendo com que as edições fossem sobrescritas por valores zerados ou antigos.
  - Solução: Implementada sincronização automática em tempo real tanto no frontend (JavaScript ao calcular e ao submeter o formulário) quanto no backend (no POST do PHP) substituindo os novos valores no parágrafo 3.1. da Cláusula Terceira.

- **Correção da Inicialização do Alpine.js (Botões Inoperantes)** *(jun/2026)*:
  - Causa raiz: o HTML dinâmico do contrato (com e-mails `@gmail.com`) era renderizado dentro do wrapper `x-data` do Alpine.js, que interpretava `@` como diretiva `x-on:`, quebrando silenciosamente toda a inicialização e desativando botões como "Enviar Assinatura", "PDF" e "Anexo IA".
  - Solução: adicionado `x-ignore` no container do preview A4 em `contrato_visualizar.php` para isolar o HTML dinâmico do parser Alpine.
  - Corrigida também sintaxe de fragmento React inválido (`<></>`) na sidebar (`sidebar.php`).

- **Ajustes de Layout e Quebras de Página no PDF** *(jun/2026)*:
  - Adicionada regra CSS `break-after: avoid` e `page-break-after: avoid` para títulos `h3` e `h4` em `contrato_visualizar.php`, impedindo que os títulos de cláusulas fiquem "órfãos" sozinhos no fim de uma página.
  - Inserida quebra de página explícita (`page-break`) imediatamente antes do logotipo e conteúdo do Anexo I, garantindo que o anexo inicie sempre em uma nova folha do PDF.

- **Integração e Configuração Front-End do Assinafy** *(jun/2026)*:
  - Corrigidos bugs no payload de vinculação (`signerIds` em vez de `signers`) e no parse dos IDs de signatários no backend (`api/contratos/enviar_assinatura.php`).
  - Adicionado o Signatário da Distinto (Contratada) por padrão (`jeaneponcemsm@gmail.com`) no formulário (`contrato_gerar.php`), permitindo edição no front-end e sincronizando seu registro no Assinafy.
  - Implementada substituição automática no POST de placeholders e formatação de CPF/CNPJ (ex: `000.000.000-00`) caso o usuário preencha apenas números em [contrato_gerar.php](file:///c:/Users/Wellington/Documents/GitHub/erp-distinto/gerenciamento/contrato_gerar.php).
  - Removido o bloco visual de assinaturas físicas (tabela pontilhada) de [contrato_visualizar.php](file:///c:/Users/Wellington/Documents/GitHub/erp-distinto/gerenciamento/contrato_visualizar.php).
  - Substituído o `confirm` nativo do navegador por um modal de confirmação de envio escuro e premium com Alpine.js na pré-visualização.
  - Criado o modal de configurações da API do Assinafy diretamente no front-end (`contratos.php` e `contrato_visualizar.php`) para fácil acesso às chaves.
  - Implementado o endpoint seguro `api/contratos/salvar_config_assinafy.php` para atualização das credenciais via AJAX.

- **Previsão de Entrega de Pré-Wedding e Save the Date** *(jun/2026)*:
  - Adicionados campos de previsão de entrega no formulário de contratos em [contrato_gerar.php](file:///c:/Users/Wellington/Documents/GitHub/erp-distinto/gerenciamento/contrato_gerar.php).
  - Padrão inicial: Fotos do Pré-wedding em "10 dias úteis..." e Save the Date em "Até 15 dias...".
  - A Cláusula Quarta (Das Entregas) passa a gerar dinamicamente os parágrafos 4.3 e 4.4 contendo os prazos especificados para ensaios ativos.
  - Implementada sincronização automática em tempo real no POST: ao salvar a minuta de um contrato legado/existente, o PHP intercepta o HTML e atualiza ou insere as cláusulas de entrega se o checkbox estiver ativo, mantendo a numeração e edições personalizadas do usuário.
  - Ajustados estilos de visualização em [contrato_visualizar.php](file:///c:/Users/Wellington/Documents/GitHub/erp-distinto/gerenciamento/contrato_visualizar.php): padding da folha A4 alterado para `10pt 50.5pt 15pt 47.3pt`, `margin-top: 35px` no logotipo e `line-height: 1.4` para os itens de lista (`li`) para melhorar a legibilidade.

- **Subdomínio Assinafy, Double Encoding, Layout e CPF dos Signatários** *(jun/2026)*:
  - Causa raiz: Subdomínio `painel.assinafy.com.br` inativo. Nomes e CPFs sofriam escapes aninhados de HTML por causa de `sanitizar()` no POST. Os CPFs também eram renderizados sem formatação (números crus) na minuta do contrato, pois a geração inicial e a edição lateral não os atualizavam no corpo do HTML.
  - Solução: Corrigido domínio para `app.assinafy.com.br` e removido botão e divs duplicadas no layout. Substituído `sanitizar()` por `trim()` + `decodificarEntidades()` no POST e inicialização. Adicionado `formatarCpfCnpj()` na criação inicial e implementada substituição dinâmica no POST que sincroniza CPFs/Nomes crus e formatados no corpo do texto do contrato.

- **Melhorias de Estabilidade, Editor, PDF e Integrações Anteriores** *(jun/2026)*:
  - Correção de erro HTTP 500 com datas vazias, preservação das edições do Quill, toggle de Pré-Wedding, calculadora automática de parcelas, correções de acentuação nos templates de casamento, configuração de margens no html2pdf.js e endpoint de escolha de planos do cliente.

## Diretrizes para Futuras IDEs / Agentes
1. **Idioma**: Sempre responda em Português do Brasil.
2. **Commit**: Sugira título de commit em português ao finalizar uma ação.
3. **Segurança**: Mantenha integridade das funções PHP. Sanitize sempre antes de reinjetar HTML.
4. **Histórico**: Mantenha este `HISTORICO.md` com menos de 70 linhas.
5. **Resiliência**: Toda chamada de API no frontend deve ter `try/catch` para não bloquear o usuário.
6. **Aditivos**: Mudanças de plano pós-contrato são aditivos manuais pelo administrador.
