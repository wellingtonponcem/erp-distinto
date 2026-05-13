Adote o Property-Based Testing. Em vez de testes comuns com inputs fixos, use bibliotecas (como o fast-check para ambientes Node.js) que bombardeiam suas funções com diversos cenários para garantir que o estado final da propriedade se mantenha correto.

Utilize profilers para monitorar o consumo de CPU e memória do processo rodando (como o DevTools do Chrome para Node ou py-spy). Faça capturas (snapshots) da Heap para encontrar itens alocados sem referência.

Pergunte a si mesmo: o que acontece com essa lógica gerada se o banco de dados cair no meio do request? Sempre considere os trade-offs da arquitetura sugerida.

Sempre crie um arquivo MD com um histórico do que estamos fazendo para que se eu usar outra IDE ela saiba o que fizemos e deixe uma instrução para que ela também faça isso no seus padrões. E não é para criar outro MD é manter o MD criado sempre atualiazado.
