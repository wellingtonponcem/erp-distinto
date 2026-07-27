Adote o Property-Based Testing. Em vez de testes comuns com inputs fixos, use bibliotecas (como o fast-check para ambientes Node.js) que bombardeiam suas funções com diversos cenários para garantir que o estado final da propriedade se mantenha correto.

Utilize profilers para monitorar o consumo de CPU e memória do processo rodando (como o DevTools do Chrome para Node ou py-spy). Faça capturas (snapshots) da Heap para encontrar itens alocados sem referência.

Pergunte a si mesmo: o que acontece com essa lógica gerada se o banco de dados cair no meio do request? Sempre considere os trade-offs da arquitetura sugerida.

Sempre crie um arquivo MD com um histórico do que estamos fazendo para que se eu usar outra IDE ela saiba o que fizemos e deixe uma instrução para que ela também faça isso no seus padrões. E não é para criar outro MD é manter o MD criado sempre atualiazado.

## Histórico de Correções - Debug Roteiros 404 & Estrutura de Servidor

### Problema 1: "Falha ao carregar roteiros: Erro HTTP: 404"
- **Causa**: O método `OPTIONS` (Preflight) estava sendo bloqueado por falta de autenticação no `auth.php`, impedindo a comunicação CORS entre `/roteiros/` e `/sistema/api/`.
- **Solução**: Adicionada exceção para requisições `OPTIONS` no `exigirAutenticacao()`.

### Problema 2: "403 Forbidden" após o Deploy
- **Causa**: O script de deploy tentou puxar o repositório `meus-roteiros` na pasta raiz (`public_html`) em vez de na subpasta `/roteiros/`. Isso aconteceu porque a subpasta não tinha um repositório Git inicializado e o Git subiu para o pai. Isso sobrescreveu o `index.php` do site principal e bagunçou as rotas.
- **Solução**:
  1. Restaurado o repositório `distinto-site` na pasta raiz (`public_html`).
  2. Inicializado e configurado corretamente o repositório `meus-roteiros` dentro da pasta `/roteiros/`.
  3. Configurado o Token de Acesso (PAT) no repositório de roteiros para permitir deploys futuros sem erro de credenciais.

### Sincronização de Contas (Nova Regra)
- **Requisito**: Os usuários `faustinosdg@gmail.com` e `jeaneponcem13@gmail.com` devem compartilhar a mesma base de dados.
- **Implementação**: Modificada a função `usuarioAtual()` no arquivo `config/auth.php` para que ambos utilizem o ID do usuário Faustino como "ID Mestre" para todas as operações de banco de dados. Isso garante que qualquer roteiro criado ou editado por um apareça instantaneamente para o outro.

### Estado Atual:
- **Site Principal**: Restaurado e funcional.
- **Roteiros**: Pasta `/roteiros/` isolada e com repositório próprio sincronizado.
- **Sincronização**: Ativa para os dois e-mails especificados.
