# Histórico de Soluções - Projeto Distinto

Este arquivo registra as atividades, decisões arquiteturais e soluções implementadas no projeto para garantir a continuidade entre diferentes sessões e IDEs.

## Regras de Manutenção
- Manter este arquivo sempre atualizado.
- Documentar bugs encontrados e suas respectivas soluções.
- Registrar mudanças significativas na arquitetura ou banco de dados.

---

## [2026-05-13] - Início da Sessão
- **Status Atual:** O projeto está em fase de ajuste fino, com foco recente em correções de implantação no Hostinger e sincronização com o banco Neon.
- **Atividade:** Inicialização do arquivo de histórico conforme as regras do projeto.

### Contexto Recente (Resumo das Conversas Anteriores)
- **Correção de Rotas:** Ajustes no `.htaccess` para lidar com caminhos aninhados no Hostinger.
- **Migração WordPress:** Remoção de dependências do WordPress e migração de dados para JSON.

## [2026-05-13] - Correção de Navegação e Roteamento (Live)
- **Problema 1:** Erro 404 ao acessar o dashboard do ERP e redirecionamentos incorretos no logo.
- **Problema 2:** Link do logo no site institucional apontando para `/sistema/` indevidamente.
- **Causa Raiz:** O `BASE_PATH` estava sendo detectado como `/sistema` mesmo na raiz do domínio devido a inconsistências no `DOCUMENT_ROOT` do Hostinger. Além disso, o `.htaccess` do ERP não estava tratando corretamente arquivos físicos em subpastas.

### Ações Realizadas:
1.  **Ajuste de `BASE_PATH` (`includes/db.php`):** Implementada detecção inteligente que ignora o prefixo `/sistema` se o script atual não estiver dentro da pasta do sistema. Isso corrige o link do logo no site institucional.
2.  **Ajuste de Navegação no ERP (`sistema/includes/layout/sidebar.php`):** O logo "DISTINTO" agora aponta explicitamente para a raiz `/`, permitindo o retorno ao site institucional.
3.  **Melhoria do `.htaccess` do ERP:** Adicionadas regras para servir arquivos físicos diretamente e simplificado o `RewriteBase`.
4.  **Depuração:** Criado script `debug_base.php` para validar caminhos reais no servidor.

### Instruções para Próximas Sessões:
- Sempre validar o `BASE_PATH` ao mover arquivos entre raiz e subpastas no Hostinger.
- Garantir que `APP_URL` no `env.php` do ERP não termine com barra para evitar links duplicados.
- Se houver falha de implantação (404), verificar se todos os arquivos (especialmente `dashboard.php`) foram enviados para o subdiretório correto no servidor.


### Se fizer alterações via SSH
Sempre que fizer uma alteração via SSH, siga este passo a passo dentro do terminal SSH da Hostinger:

Navegue até a pasta do seu projeto (ex: cd domains/seusite.com.br/public_html/sistema).

Adicione e "commite" as alterações:

Bash
git add .
git commit -m "Hotfix: Alterações feitas pelo Claude via SSH"
Envie para o GitHub:

Bash
git push origin main

(Nota: Se o terminal pedir sua senha do GitHub, você precisará gerar um "Personal Access Token" nas configurações de desenvolvedor da sua conta do GitHub e colar no lugar da senha, pois o GitHub não aceita mais senhas normais no terminal).
Personal Access Token: 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