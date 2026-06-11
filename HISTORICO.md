# Histórico de Alterações - ERP Distinto

## Objetivo do Projeto
ERP Distinto: gestão de propostas comerciais, clientes e exportação PDF. Foco em integração do frontend público (proposta web) com o painel administrativo.

## Alterações Recentes

- **Integração e Configuração Front-End do Assinafy** *(jun/2026)*:
  - Corrigidos bugs no payload de vinculação (`signerIds` em vez de `signers`) e no parse dos IDs de signatários no backend (`api/contratos/enviar_assinatura.php`).
  - Criado o modal de configurações da API do Assinafy diretamente no front-end (`contratos.php` e `contrato_visualizar.php`) para fácil acesso às chaves.
  - Implementado o endpoint seguro `api/contratos/salvar_config_assinafy.php` para atualização das credenciais via AJAX.

- **Previsão de Entrega de Pré-Wedding e Save the Date** *(jun/2026)*:
  - Adicionados campos de previsão de entrega no formulário de contratos em [contrato_gerar.php](file:///c:/Users/Wellington/Documents/GitHub/erp-distinto/gerenciamento/contrato_gerar.php).
  - Padrão inicial: Fotos do Pré-wedding em "10 dias úteis..." e Save the Date em "Até 15 dias...".
  - A Cláusula Quarta (Das Entregas) passa a gerar dinamicamente os parágrafos 4.3 e 4.4 contendo os prazos especificados para ensaios ativos.
  - Implementada sincronização automática em tempo real no POST: ao salvar a minuta de um contrato legado/existente, o PHP intercepta o HTML e atualiza ou insere as cláusulas de entrega se o checkbox estiver ativo, mantendo a numeração e edições personalizadas do usuário.
  - Ajustados estilos de visualização em [contrato_visualizar.php](file:///c:/Users/Wellington/Documents/GitHub/erp-distinto/gerenciamento/contrato_visualizar.php): padding da folha A4 alterado para `10pt 50.5pt 15pt 47.3pt`, `margin-top: 35px` no logotipo e `line-height: 1.4` para os itens de lista (`li`) para melhorar a legibilidade.

- **Correção da Persistência do Editor e Resiliência de Datas** *(jun/2026)*:
  - Resolvido o erro HTTP 500 no salvamento e visualização de contratos tornando a função `dataExtenso()` em `helpers.php` resiliente a datas nulas, vazias ou em formato inválido.
  - Removida a regeneração automática e destrutiva do template de casamento. Agora, o texto editado pelo usuário no Quill/CKEditor é fielmente preservado no banco e exibido na visualização.

- **Datas por Extenso, Acentuações, Toggle de Pré-Wedding e Calculadora** *(jun/2026)*:
  - Adicionado checkbox "A definir em comum acordo entre as partes" para o Pré-Wedding no formulário.
  - Adicionada Calculadora de Parcelas automática com base no intervalo de meses ou seleção manual de parcelas (para cartão).
  - Corrigidas e restauradas as acentuações em português do Brasil em todas as cláusulas do template de Casamento inicial.

- **Correção do Editor Visual de Contratos** *(jun/2026)*:
  - Substituição do CKEditor 5 por Quill.js 2 (devido à descontinuação de CDN) e correções de sintaxe de headers Alpine.js.

- **Correção da Exportação de PDF e Quebras de Página** *(jun/2026)*:
  - Ajustes de margens no html2pdf.js e estilos de CSS no visualizador para evitar quebras inadequadas de páginas.

- **Integração da Escolha de Plano pelo Cliente (Casamento)** *(jun/2026)*:
  - Criação do endpoint `api/propostas/escolher-plano.php` para integrar escolhas e registrar andamento de proposta.

## Diretrizes para Futuras IDEs / Agentes
1. **Idioma**: Sempre responda em Português do Brasil.
2. **Commit**: Sugira título de commit em português ao finalizar uma ação.
3. **Segurança**: Mantenha integridade das funções PHP. Sanitize sempre antes de reinjetar HTML.
4. **Histórico**: Mantenha este `HISTORICO.md` com menos de 70 linhas.
5. **Resiliência**: Toda chamada de API no frontend deve ter `try/catch` para não bloquear o usuário.
6. **Aditivos**: Mudanças de plano pós-contrato são aditivos manuais pelo administrador.
