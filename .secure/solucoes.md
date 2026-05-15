Adote o Property-Based Testing. Em vez de testes comuns com inputs fixos, use bibliotecas (como o fast-check para ambientes Node.js) que bombardeiam suas funções com diversos cenários para garantir que o estado final da propriedade se mantenha correto.

Utilize profilers para monitorar o consumo de CPU e memória do processo rodando (como o DevTools do Chrome para Node ou py-spy). Faça capturas (snapshots) da Heap para encontrar itens alocados sem referência.

Pergunte a si mesmo: o que acontece com essa lógica gerada se o banco de dados cair no meio do request? Sempre considere os trade-offs da arquitetura sugerida.

Sempre crie um arquivo MD com um histórico do que estamos fazendo para que se eu usar outra IDE ela saiba o que fizemos e deixe uma instrução para que ela também faça isso no seus padrões. E não é para criar outro MD é manter o MD criado sempre atualiazado.

## Histórico de Correções - Debug Roteiros 404

### Problema: "Falha ao carregar roteiros: Erro HTTP: 404"
- O frontend em `/roteiros/` tenta buscar dados em `/sistema/api/roteiros/listar.php`.
- O servidor retorna 404 para o browser do usuário, mas retorna 401 (OK, mas sem auth) em testes externos.

### Ações Realizadas:
1. **Verificação de Arquivos**: Confirmado via script de diagnóstico que o arquivo físico existe no servidor em `/public_html/sistema/api/roteiros/listar.php`.
2. **CORS/Preflight**: Identificado que o método `OPTIONS` (pre-flight do browser) não estava sendo tratado sem autenticação, o que pode causar falhas em alguns navegadores.
   - *Correção*: Adicionado check de `OPTIONS` no `exigirAutenticacao()` do `auth.php` para responder 200 OK sem exigir login.
3. **Teste de Endpoints**: Criado `/sistema/api/ping.php` para testar se a pasta `api` está acessível publicamente sem passar pelo filtro de autenticação complexo.

### Próximos Passos:
- Verificar se o `ping.php` retorna 404 ou JSON. Se for 404, o problema é no `.htaccess` da raiz ou do sistema.
- Testar se o `raizUrl` em `index.php` está gerando o caminho correto (confirmado via diagnóstico como `/sistema/...`).
