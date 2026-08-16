# Documentação do Módulo de Propostas (ERP Distinto)

Documento de referência para recriação do módulo de Propostas no Next.js. Descreve a aparência, o comportamento e as regras de negócio do módulo PHP legado, com sugestões de mapeamento para a nova stack.

---

## 1. Visão Geral

O módulo de Propostas permite ao time comercial:

- **Criar** propostas comerciais em 3 tipos: `casamento`, `marketing`, `filmmaker`.
- **Editar** em wizard de 4 passos (dados, serviços, cronograma, condições).
- **Organizar** em pastas com arrastar e soltar, contexto e busca.
- **Visualizar** a proposta como apresentação de slides (fullscreen, scroll-snap) via link público `/p/{slug}`.
- **Exportar** PDF da apresentação.
- **Capturar escolha do casal** (plano Heritage/Cinematic/Essencial + upgrades) na própria apresentação e abrir conversa no WhatsApp.
- **Registrar fechamento** (condições de pagamento, parcelas, integração com Asaas) e **histórico** com recomendação de próximo passo por IA.
- **Gerar mensagens** de WhatsApp e conteúdos (seções, título, mensagem pessoal, anexo de contrato) via IA (Gemini / Groq).

### Fluxo principal

```
Admin cria/edita proposta (PHP + Alpine)  →  propostas (dados_json JSON)
        │
        ▼
Link público /p/{slug} (p.php)  →  renderiza includes/propostas/template-{tipo}.php
        │
        ▼
Cliente navega os slides, escolhe plano + upgrades  →  api/propostas/escolher-plano.php
        │
        ▼
Abre WhatsApp (wa.me) com resumo da escolha
        │
        ▼
Admin acompanha (status, histórico, fechamento)  →  status 'aceita' → lançamento financeiro automático
```

---

## 2. Arquitetura e Arquivos

| Área | Arquivo | Papel |
|---|---|---|
| Visualizador público | `p.php` (raiz, 497 linhas) | Rota pública por slug; carrega config, monta moldura/HUD, inclui template, JS de interação |
| Templates | `includes/propostas/template-casamento.php` (≥2550 linhas) | Casamento — 19 slides |
| | `includes/propostas/template-marketing.php` (~824) | Marketing — 14 slides |
| | `includes/propostas/template-filmmaker.php` (99) | Filmmaker — 4 slides |
| | `includes/propostas/template-15anos.php` (84) | 15 Anos — 4 slides |
| Telas admin | `gerenciamento/propostas.php` (1421) | Listagem, pastas, modais, tema escuro |
| | `gerenciamento/proposta_nova.php` (1205) | Wizard de criação (Passo 1 e 2) |
| | `gerenciamento/proposta_editar.php` (1635) | Wizard de edição (Passos 1 a 4) |
| APIs | `api/propostas/gerar.php`, `atualizar.php`, `fechamento.php`, `escolher-plano.php`, `mensagem-whatsapp.php`, `organizar.php` | Backend do módulo |
| | `api/gerenciamento/proposta_historico.php` | Histórico + recomendação de próximo passo |
| | `api/pdf/proposta.php` | Geração de PDF |
| IA | `includes/ia_propostas.php` (325) | Classe `IAPropostas` (Gemini) |
| Assets | `assets/css/propostas.css` (843), `assets/css/propostas-mobile.css` (406), `assets/js/propostas.js` (524) | Aparência e interação da apresentação |
| DB | `setup/migration_propostas.php`, `setup_propostas.php` | Schema da tabela `propostas` |

> **Stack da apresentação**: HTML + CSS puro com `scroll-snap-type: y mandatory`, Alpine.js no admin, Tailwind CSS no admin, `lucide.createIcons()` para ícones, IntersectionObserver para revelar seções.

---

## 3. Modelagem de Dados

### 3.1 Tabela `propostas` — **inconsistência entre schemas**

Existem duas migrações com definições diferentes (registrar como pendência de padronização):

`setup/migration_propostas.php`:

| Coluna | Tipo |
|---|---|
| `id` | TEXT / PK |
| `cliente_id` | TEXT nullable |
| `cliente_nome` | TEXT |
| `tipo` | TEXT (casamento/marketing/filmmaker) |
| `slug` | TEXT UNIQUE (CONSTRAINT `idx_slug`) |
| `titulo` / `subtitulo` | TEXT |
| `validade` | DATE |
| `dados_json` | JSON (TEXT) |
| `valor_total` | NUMERIC(15,2) |
| `status` | DEFAULT `'rascunho'` |
| `oportunidade_id` | TEXT nullable |
| `pasta_id` | TEXT nullable |
| `criado_em` / `atualizado_em` | TIMESTAMP |

`setup_propostas.php`:

- `tipo` como **VARCHAR(100)**
- `status` DEFAULT **`'pendente'`**
- `created_at` / `updated_at` (em vez de `criado_em`/`atualizado_em`)
- `valor_total` como **NUMERIC(10,2)**

> `p.php` lê `created_at` (linha ~35), o que implica que na prática o schema ativo usa `created_at`.

### 3.2 Tabelas relacionadas

- `pastas_propostas` — `id`, `nome` (organização em pastas).
- `propostas_historico` — criada on-the-fly pelos endpoints (não precisa existir antes):

```sql
CREATE TABLE IF NOT EXISTS propostas_historico (
    id SERIAL PRIMARY KEY,
    proposta_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    tipo TEXT DEFAULT 'nota',
    conteudo TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

  Tipos de `tipo` usados: `nota`, `escolha_cliente`, `escolha_admin`, `fechamento`.

- `clientes` — `id`, `nome`, `contato` (whatsapp), `segmento`, `criado_em`.
- `oportunidades` — `id`, `cliente_id`, `nome`, `valor_estimado`, `etapa` (`novo`/`qualificado`/`proposta`...), `previsao`, `responsavel`, `descricao`.
- `servicos` — catálogo; campo `categoria='wedding'`, `tipo='plano'`, `ativo=1` para fallback dos pacotes.
- `lancamentos` — financeiro; recebe lançamento automático quando proposta vira `aceita`.
- `configuracao_empresa` — registro `id='principal'`; guarda `gemini_api_key` e dados de contato padrão.

### 3.3 Estrutura de `dados_json` (campos principais)

```jsonc
{
  "secoes": { "visao": "...", "desafio": "...", "objetivo": "..." }, // IA
  "servicos": [{ "id", "nome", "descricao", "valor_individual", "valor_mensal", "tipo_cobranca", "frequencia" }],
  "fases_cronograma": [{ "nome", "dias", "descricao" }],
  "briefing": "",
  "objetivo_original": "",
  "data_inicio": "YYYY-MM-DD",
  "meses_contrato": 12,
  "forma_pagamento": "boleto_pix",
  "adicional": { "titulo", "valor", "descricao", "fornecedor_id" },
  "responsavel": "...",          // via contatoResponsavel()
  "responsavel_manual": "",
  "whatsapp": "",
  "is_plural": false,
  "etapas_ativas": [],
  "etapas_dias": [],
  // Casamento
  "nome_noivo": "", "nome_noiva": "",
  "data_casamento": "YYYY-MM-DD",
  "data_limite_desconto": "YYYY-MM-DD",
  "condicao_especial": "",
  "valor_heritage"/"itens_heritage",
  "valor_cinematic"/"itens_cinematic",
  "valor_essencial"/"itens_essencial",
  "valor_boudoir"/"valor_prewedding",
  "atualizacoes_versao": "",
  "andamento_proposta": "dd/mm/aaaa hh:mm | ... \n ...",
  "mostrar_andamento_cliente": bool,
  "versao_proposta": "v1",
  "itens_personalizados": { "heritage": [], "cinematic": [], "essencial": [] },
  "mensagem_pessoal": "",
  "prazo_previas": "48 horas",
  "prazo_final": "60 dias úteis",
  "validade_proposta": "7",
  "instagram_handle": "@distintowedding",
  "email_contato": "contato@wedistinto.com",
  "whatsapp_numero": "+55 27 9 8858-6935",
  // Visibilidade / inclusão
  "show_heritage": bool, "show_cinematic": bool, "show_essencial": bool,
  "include_boudoir": bool, "include_prewedding": bool,
  "include_boudoir_heritage" ... "include_prewedding_essencial": bool,
  "condicoes_reserva": "", "condicoes_heritage_cinematic": "", "condicoes_essencial": "",
  "contato_tipo": "noiva",
  "upgrades": { "heritage": [], "cinematic": [], "essencial": [] },
  "pacote_dado_andamento": "cinematic",
  "cliente_escolha": {
    "plano_id": "heritage|cinematic|essencial",
    "extras": ["boudoir_static", "prewedding_static", "<servico_id>"],
    "itens_selecionados": ["Boudoir da Noiva", ...],
    "valor_total": 0.0,
    "condicoes": "",
    "selecionado_em": "YYYY-MM-DD HH:MM:SS",
    "whatsapp_fechamento": bool
  }
}
```

### 3.4 Pacotes de Casamento (padrões de valor)

| Plano | `plano_id` | Valor de referência | Natureza |
|---|---|---|---|
| Experiência Heritage | `heritage` | ~R$ 7.900,00 | Luxo completo, foto + vídeo, equipe expandida, ensaios pré-wedding/boudoir |
| Experiência Cinematic | `cinematic` | ~R$ 4.500,00 | Foto + vídeo, 1 fotógrafo + 1 videomaker |
| Registro Essencial | `essencial` | ~R$ 2.800,00 | Somente fotografia, 1 fotógrafo |

- Valores podem vir de `dados_json` (`valor_*`), de `servicos` (categoria `wedding`, tipo `plano`) ou do fallback estático.
- Upgrades estáticos: `boudoir_static` (default 500) e `prewedding_static` (default 1100). Upgrades dinâmicos referenciam IDs de `servicos`.

---

## 4. Autenticação e Permissões

- `exigirAutenticacao()` — exige sessão logada (usado em APIs e telas admin).
- `exigirAdmin()` — exige usuário admin.
- `estaAutenticado()` — verificação booleana.
- Tela pública `p.php` **não exige autenticação** (é o link enviado ao cliente).
- `escolher-plano.php` e `mensagem-whatsapp.php` também são públicos (consumidos pela apresentação).
- `proposta_historico.php` exige sessão: POST usa `$_SESSION['user_id']` (sem ele → erro `Sessão sem user_id.`).

---

## 5. Funções Utilitárias Compartilhadas

| Função | Comportamento |
|---|---|
| `contatoResponsavel([...])` | Determina o responsável pelo contato: se `casamento`, prefere noiva/noivo pelo `contato_tipo`; senão usa `responsavel`. |
| `normalizarDataFormulario($data)` | Aceita `YYYY-MM-DD` ou `dd/mm/aaaa` e normaliza. |
| `responderJson($dados, $codigo=200)` | Emite JSON + código HTTP e encerra. |
| `slugify($texto)` | Slug robusto sem `iconv`: remove acentos via mapa, `[^\pL\d]+` → `-`, lowercase; retorna `'n-a'` se vazio. Usado em `gerar.php` com sufixo `-{tipo}-{dmY}` e deduplicação `-1`, `-2`... |
| `formatarMoeda($v)` | Formata `R$ 1.234,56`. |
| `adicionarMesesIso($dataIso, $meses)` | Soma meses em data ISO (usado no fechamento). |
| `mesesAteEvento($dataInicial, $dataEvento)` | Limita parcelas até o evento (fechamento). |
| `mdParaHtml($texto)` | `##`→`<h2>`, `###`→`<h3>`, `**`→`<strong>`, `- `→`<li>/<ul>`, `---`→`<hr>` (usado no PDF). |
| `gerarId()` | Gera ID único para inserts. |
| `jsonParaJs($dados)` (em `proposta_editar.php`) | `json_encode` com `JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT`. |
| `renderItensPersonalizadosCasamento($itens)` | Renderiza itens personalizados no slide do pacote. |

---

## 6. IA — `IAPropostas` (`includes/ia_propostas.php`)

### 6.1 Chave da API

- `getGeminiKey()`: lê `configuracao_empresa.gemini_api_key` → constante `GEMINI_API_KEY` → `getenv('GEMINI_API_KEY')`. Retorna `null` se o valor começa com `SUA_` (placeholder). Se `null`, todas as funções caem em fallback local.

### 6.2 `chamarGemini(array $parts, string $modelo = 'gemini-2.5-flash')`

- POST `https://generativelanguage.googleapis.com/v1beta/models/{modelo}:generateContent?key={key}` via cURL, timeout 90s, `temperature: 0.3`.
- Fallback de modelos: `gemini-2.0-flash` → `gemini-1.5-flash` (2 tentativas por modelo).
- `429/403/401/400` → retorna erro imediato (não tenta de novo).
- `503` → espera 500ms e tenta próximo modelo. Outros erros → espera 300ms e tenta de novo.

### 6.3 Métodos

| Método | Descrição | Fallback sem IA |
|---|---|---|
| `gerarTextoSecao($tipo, $secao, $contexto)` | Texto curto (≤2 parágrafos) para `visao`/`desafio`/`objetivo`. | `textoSecaoFallback()` (textos fixos por tipo/seção). |
| `melhorarObjetivo($objetivo, $contexto)` | Reescreve objetivo do briefing. | Retorna objetivo original ou `gerarTextoSecao`. |
| `recomendarProximoPasso($proposta, $historico)` | Regras de negócio (ver 6.4). | Sem IA — só regras. |
| `gerarMensagemWhatsApp($nomeNoivo, $nomeNoiva, $nomeCasal)` | Mensagem dos noivos para o estúdio. | "Olá Wellington! Ficamos encantados com a proposta do nosso casamento ({nomes}). ... ✨" |
| `refinarTitulo($titulo, $servicos)` | Melhora título (usado só em marketing). | Título original. |
| `otimizarClausula($texto, $tipoContrato)` | Revisa cláusula contratual (prompt de advogado). | — |
| `gerarAnexoI($proposta)` | Gera "Anexo I — Descrição dos Serviços" em HTML, com escopo rígido conforme o plano (essencial = só fotografia/1 profissional; cinematic = foto+vídeo; heritage = luxo completo). Galeria 250–350 fotos com tratamento básico + até 30 com tratamento avançado. | — |

### 6.4 Regras de `recomendarProximoPasso`

- `casamento` + `aceita` + plano + valor>0 → "pronta para virar contrato; confira CPF, e-mail de assinatura e locais".
- `casamento` sem plano → "Defina o plano escolhido pelo casal e os opcionais".
- `casamento` valor ≤ 0 → "Confira o valor final do fechamento".
- `casamento` demais → "Revise as condições de pagamento e gere o contrato".
- `rascunho` → revisar, visualizar e enviar. `pendente` → acompanhar e registrar retorno. `aceita` → gerar contrato. `recusada` → registrar motivo.

---

## 7. APIs

### 7.1 `api/propostas/gerar.php` — Cria proposta

- **Método**: POST. **Auth**: `exigirAutenticacao()`.
- **Validação de lead**: se não-casamento, exige `empresa_nome` e `responsavel` (senão 422).
- **Entrada**: `tipo`, `cliente_nome`, `responsavel`, `whatsapp`, `briefing`, `objetivo`, `servicos[]` (`id`, `valor`, `tipo_cobranca`, `frequencia`), `titulo`, `subtitulo`, `validade`, `valor_total`, `pasta_id`, e todos os campos de casamento (`nome_noivo`, `nome_noiva`, `valor_*`, `itens_*`, flags `show_*`/`include_*`...).
- **Regras**:
  1. Se `oportunidade_id` vazio e tipo não-casamento → cria `clientes` (nome do cliente, contato=whatsapp, segmento conforme tipo). Se `casamento` → cria cliente com `"{noivo} & {noiva}"` e segmento `'Novo Casamento'`. Pluralização inteligente (`is_plural`) quando responsável contém `,` ou ` e ` ou tipo casamento.
  2. Se oportunidade em etapa `novo`/`qualificado` → atualiza etapa para `proposta`.
  3. Gera slug único.
  4. Gera seção por IA: marketing → `desafio` (+ `objetivo` melhorado se informado); casamento/filmmaker → `visao`. Se erro → `secoes.error = "IA Indisponível: ..."`.
  5. Para casamento: se briefing preenchido e `secoes.visao` OK e mensagem pessoal vazia ou padrão → `mensagem_pessoal = secoes.visao`. Se ainda vazia → fallback fixo.
  6. Insert em `propostas` com `status='rascunho'`, `validade` default hoje+15 dias, título default `"Proposta Comercial - {cliente}"`.
  7. Marketing: `refinarTitulo()` via IA (mantém original em erro).
  8. Se sem oportunidade → cria `oportunidades` automaticamente (etapa `proposta`, previsão +30d casamento / +15d outros, valor=valor_total, descrição "Oportunidade criada automaticamente a partir da proposta {id}") e vincula à proposta.
- **Resposta**: `{ success: true, id, slug }` com HTTP 201. Sempre JSON (error handler global).

### 7.2 `api/propostas/atualizar.php` — Atualiza proposta

- **Método**: POST. **Auth**: `exigirAutenticacao()`.
- **Entrada**: `id` obrigatório + mesmos campos do gerar + `fases[]` (`nome`, `dias`, `descricao`), `status`, `contato_tipo`, `responsavel`, `cliente_id`, `oportunidade_id`.
- **Regras**:
  1. Carrega proposta atual; merge de `secoes` preservando seções de IA existentes.
  2. `servicos[]` enriquecidos com dados da tabela `servicos` (`valor_individual`/`valor_mensal`/`tipo_cobranca`/`frequencia`). Ignora itens sem `id`.
  3. `fases_cronograma` com `dias = max(0, ...)`.
  4. Se `oportunidade_id` informado → valida existência e herda `cliente_id` da oportunidade.
  5. **Escolha do casal via admin**: se tipo casamento e `pacote_dado_andamento` preenchido, recompõe `cliente_escolha` a partir de `escolha_boudoir`/`escolha_prewedding` (`1`) e `escolha_upgrades[]` (`1`). Se o plano mudou (`planoAnterior`):
     - Registra linha em `andamento_proposta`: `"{data} | Administrador registrou fechamento no plano: {nomePlano} com upgrades (...) | Investimento: R$ ... | Registrado via painel administrativo"`.
     - Insere em `propostas_historico` tipo `escolha_admin`.
  6. `valor_total` sincroniza com `escolha_valor_total`; flags `show_*`/`include_*` sobrescritas para consistência com o plano.
  7. `cliente_nome`: casamento → `"{noivo} & {noiva}"`; senão usa `responsavel_manual` ou `cliente_nome`.
  8. **Automação financeira**: se `status='aceita'` e há `cliente_id`, verifica se já existe lançamento com `observacao LIKE "%Ref. Proposta: {id}%"`; se não, cria lançamento `tipo='receber'`, `categoria='serviços'`, `status='pendente'`, `modalidade='avista'`, vencimento hoje, descrição `"Fechamento: {titulo}"`, observação `"Gerado automaticamente. Ref. Proposta: {id}"`.
  9. Após salvar, gera `recomendacao` com `IAPropostas::recomendarProximoPasso` usando as últimas 3 linhas do histórico.
- **Resposta**: `{ success: true, id, slug, recomendacao }`.

### 7.3 `api/propostas/fechamento.php` — Fechamento (admin)

- **Método**: POST. **Auth**: `exigirAutenticacao()` + verificação de admin.
- **Regras**:
  - Valida plano (`heritage|cinematic|essencial`) se informado — senão 422.
  - `percentualEntrada` = **25%** (heritage) / **20%** (demais). `maxParcelasPlano` = **6** (heritage) / **5** (demais).
  - Se `valor_total > 0` e `asaas_valor_sinal <= 0` → sinal = valor × percentual/100.
  - **Parcelado**: `asaas_total_parcelas = max(1, min(parcelas, limiteEvento))`; `limiteEvento = maxParcelasPlano` reduzido por `mesesAteEvento(first_due_date, data_evento)` quando `permitir_parcela_pos_evento` for falso. `asaas_first_due_date` default = sinal_vencimento + 1 mês.
  - **À vista**: `asaas_total_parcelas = 1`, `asaas_first_due_date = ''`.
  - Atualiza `cliente_escolha` (`plano_id`, `valor_total`, `condicoes`, `ajustado_admin_em`).
  - Grava histórico tipo `fechamento`. Erros no histórico não impedem o salvamento (try/catch Throwable).
- **Resposta**: `{ success, dados_json, valor_total }`.

### 7.4 `api/propostas/escolher-plano.php` — Escolha do cliente (público)

- **Método**: POST. **Auth**: nenhuma (público).
- **Entrada**: `slug`, `plano_id`, `extras[]`, `condicoes`.
- **Regras**:
  - `mapaValor[$plano]`/`mapaShow[$plano]` resolvem valor e flags (`boudoir_static` default 500, `prewedding_static` default 1100; extras dinâmicos somam valores de `servicos`).
  - Recalcula total somando extras do plano.
  - Grava no `dados_json`: `show_*` (só o plano escolhido), `include_boudoir_*`/`include_prewedding_*`, `upgrades[$planoId]`, `cliente_escolha` (`plano_id`, `extras`, `valor_total`, `condicoes`, `selecionado_em`).
  - Linha em `andamento_proposta`: `"{data} | Cliente selecionou o plano: {nomePlano} com upgrades (...) | Investimento: {moeda} | Escolha realizada via proposta web"`.
  - UPDATE `propostas SET dados_json, valor_total, status='pendente'`.
  - Histórico tipo `escolha_cliente` (user_id `'publico'`). Histórico é complementar (try/catch).
- **Resposta**: `{ success: true, valor_total }`.

### 7.5 `api/propostas/mensagem-whatsapp.php` — Mensagem de follow-up

- **Método**: GET/POST público.
- **Atenção — inconsistência**: este endpoint usa **Groq** (`https://api.groq.com/openai/v1/chat/completions`, `GROQ_MODEL`, `GROQ_API_KEY`), diferentemente do restante que usa Gemini.
- Prompt pede mensagem curta com exatamente um `[LINK]` placeholder, call to action, fechamento caloroso; proíbe "prezado"/"segue em anexo"/"cordialmente", sem markdown.
- `temperature 0.75`, `max_tokens 300`, timeout 15s.
- Fallback (se falhar ou HTTP ≠ 200): `"Oi, {primeiroNome}! Tudo bem?\n\nAcabei de subir o material do {titulo} aqui no sistema...\n\nDá uma olhada aqui:\n👉 {link}"`.
- Substitui `[LINK]` por `"👉 {link}"`.

### 7.6 `api/propostas/organizar.php` — Pastas e organização

- **Método**: POST. **Auth**: `exigirAutenticacao()`.
- **Ações** (`action`): `move` (proposta para pasta), `create_folder`, `delete_folder`, `rename_folder`, `delete_proposal`.
- Erros: 401 sem auth, 400 "Ação inválida". Respostas `{ success: true }` / `{ erro }`.

### 7.7 `api/gerenciamento/proposta_historico.php` — Histórico e notas

- **GET `?id=`**: cria a tabela se faltar (`information_schema`), retorna linhas `LEFT JOIN users` com `COALESCE(u.nome,'Sistema')`, `ORDER BY created_at DESC`; anexa `recomendacao` via `IAPropostas::recomendarProximoPasso` (últimas 3 linhas).
- **POST**: exige `proposta_id` + `conteudo` + `user_id` da sessão. Insere linha (`tipo` default `nota`), retorna `{ sucesso, debug, recomendacao }`.

### 7.8 `api/pdf/proposta.php` — Exportação de PDF

- **Método**: POST.
- Busca proposta por id; `validade = hoje + 15 dias`.
- Converte `dados_json.secoes` com `mdParaHtml()`.
- Retorna o HTML estilizado (usado pelo `showExportModal` do front).

---

## 8. Telas Administrativas

### 8.1 `gerenciamento/propostas.php` — Listagem (tema escuro)

- **Componente Alpine**: `x-data="propostasApp()"` (carrega pastas e propostas via fetch).
- **Layout**: sidebar (`sidebar.php`), conteúdo com `main-content`.
- **Aparência**: fundo `#121212` (painel de pastas) e `#181818` (área de documentos), bordas `rgba(255,255,255,0.03–0.1)`, radius 16–20px, cards com hover `translateY(-5px)` + elevação.
- **Pastas**: sidebar horizontal com breadcrumb (`breadcrumb-item` separado por `::after '/'`), indicador de contagem de itens por pasta.
- **Cards de proposta**: nome, título, tipo, status, valor, validade, mini-ícones; hover mostra ações.
- **Context menu** (botão direito): fixed, fundo `#1a1a1a`, radius 10px, min-width 160px, sombra; ações: abrir, editar, copiar link, mover para pasta, excluir.
- **Drag & drop**: classes `drag-over` (borda tracejada) ao arrastar card sobre pasta.
- **Modais** (Alpine `x-show`/`x-teleport`):
  - **Nova pasta**: input + `@keydown.enter="confirmarPasta()"`, `@keydown.escape` fecha; botão Salvar com `bg-white text-black rounded-lg`.
  - **Excluir**: `x-teleport="body"`, confirmação; `$watch('deleteModal.show')` dispara `lucide.createIcons()` para renderizar ícones.
- **Status visual**: chips coloridos por status (rascunho/pendente/aceita/recusada).

### 8.2 `gerenciamento/proposta_nova.php` — Criar (Passos 1–2)

- Formulário em `proposal-stepper` (grid de passos), pré-seleciona casamento.
- **Passo 1**: tipo (casamento/marketing/filmmaker), cliente (busca via combobox), oportunidade, briefing/objetivo, mensagem pessoal, e **campos de casamento**: `nome_noivo`, `nome_noiva`, `data_casamento`, `data_limite_desconto`, `condicao_especial`, valores e itens dos 3 pacotes, flags `show_*` e `include_*`.
- **Passo 2**: serviços selecionados (tabela `servicos`) com `valor_individual`/`valor_mensal`, tipo de cobrança, frequência; adiciona/remove com confirmação; `removerServico` usa lucide `trash-2`.
- **Cálculo**: total soma serviço único (`frequencia ≤ 1` → "Valor único diluído em X meses de contrato.") ou contrato mensal (`frequencia > 1` → "Frequência mensal detectada. Usando valor de contrato."). Notas em 10px itálico `zinc-400`.
- **Dados pré-carregados de pacotes**: `$beneficiosH/C/E` e `obterBeneficiosTexto()`; `$pacoteDadoAndamento = 'cinematic'` como padrão.
- **Modo modal**: quando aberto como modal (`is-modal-layout`), CSS override deixa fundo transparente e texto branco.
- Submete para `api/propostas/gerar.php`; sucesso → redireciona para edição.

### 8.3 `gerenciamento/proposta_editar.php` — Editar (Passos 1–4)

- Carrega proposta por `?id=`; `isCasamento` detectado por `dados_json` (presença de `nome_noivo`/`nome_noiva`, `data_casamento`, flags `show_*`, `pacote_dado_andamento`).
- **Passo 1 — Dados**: mesmos campos de criação + responsável, contato, datas exibidas em `dd/mm/aaaa` (conversão por `preg_match`), `contato_tipo` inferido comparando `responsavel` com noivo/noiva (lower/trim).
- **Passo 2 — Serviços**: igual ao de criação.
- **Passo 3 — Cronograma**: fases com nome, dias, descrição.
- **Passo 4 — CONTRATO E PRAZOS** (lucide `file-check`): condições de reserva (`condicoes_reserva` textarea com `x-model`), prazos (`prazo_previas`, `prazo_final`, `validade_proposta`), contatos (`instagram_handle`, `email_contato`, `whatsapp_numero`), andamento da proposta.
- Navegação por botões `data-go-step` (Voltar `data-go-step="2"`, Próximo `data-go-step="4"`).
- Submete para `api/propostas/atualizar.php`; após salvar exibe a `recomendacao` da IA.

---

## 9. Visualizador Público `p.php`

### 9.1 Fluxo

1. Resolve `{slug}` (query ou path). Proposta não encontrada ou fora do prazo → mensagem de erro.
2. Carrega `configuracao_empresa` id `'principal'` para dados de contato padrão e `gemini_api_key`.
3. Decodifica `dados_json`; `$categoriaProjeto = dados['categoria_projeto'] ?? 'PROJETO DE ESTRATÉGIA'`.
4. Monta **moldura (HUD)** fixa (`proposal-frame`) com 3 colunas `40% 45% 15%` e padding `6vh 6vw`: título da seção (esquerda), "DISTINTO STUDIO / {tipo}" central e mês/ano à direita.
5. Inclui `includes/propostas/template-{$tipo}.php` (senão `die("Template de proposta não configurado.")`).
6. Mês do título em maiúsculas PT-BR (`JANEIRO`…`DEZEMBRO`) a partir de `created_at`.
7. Enfileira `assets/js/propostas.js` (ao final, sem defer) + CSS `propostas.css` e `propostas-mobile.css`.

### 9.2 Aparência e estrutura

- Fundo da página `#000`; slides (`proposal-page`) brancos, 100vh cada, em `.proposal-wrapper` com `overflow-y: auto`, `scroll-snap-type: y mandatory`, z-index 1.
- Fontes Google: **Inter, Montserrat, Playfair Display, Cinzel, Dancing Script**.
- HUD (`proposal-frame`): uppercase, `letter-spacing: 2px`, cor `#000` com transição; slides escuros recebem classe `dark-page` → HUD branco (`body.on-dark`).
- `.proposal-page::before` / `::after`: linhas verticais fixas nas colunas `left: 40%` e `85%` (`rgba(0,0,0,0.1)`; `rgba(255,255,255,0.15)` em `dark-page`).
- `.fixed-section-title`: título da seção fixo à esquerda (top 50%, `left: 6vw`, `width: calc(40% - 6vw)`), `h2 3.25rem/800/uppercase`; visível só com `body.show-etapas-title` (acionado por IntersectionObserver) e oculto < 1024px.

### 9.3 Interações (`propostas.js`)

- **Scroll spy**: IntersectionObserver marca seções; seta `is-leaving`/`show-etapas-title` para revelar títulos do HUD.
- **Botão flutuante** (WhatsApp/send): aparece após scroll > 150px (`showButton()`), some após 2000ms.
- **`window.showExportModal`**: abre modal de exportação (opções coluna no mobile, 90vw) para gerar PDF via `api/pdf/proposta.php`.
- **Staging de PDF**: cada seção vira página; `field.x/y/w/h` percentuais definem posição; `fontSize = (field.size || 18) * 0.63`; `valign` via flexbox. Serve também para o mapa de captura.
- **Páginas de pacote**: `packagePages = pages.filter(is_pacote)`; total de pacotes via `pkgCounter` (busca `plano_id` em `values.planos`, senão `values.planos[pkgCounter]` quando há múltiplas páginas).

### 9.4 Modal de pacote e fluxo WhatsApp (casamento)

- Slides de pacote (`is-pacote`) têm botão "ESCOLHER ESTE PACOTE" que abre modal Alpine com:
  - Resumo do pacote (valor, itens), extras opcionais (Boudoir / Pré-Wedding e upgrades dinâmicos) com preços somados ao total.
  - Campo de condições/mensagem.
  - Botão confirmar.
- Ao confirmar (`m-send-btn`):
  1. Monta `linhas` com cada item/extras escolhido + valores (`fmtBRL`).
  2. Desabilita botão com texto "Gravando escolha...".
  3. `fetch` POST `raizUrl('/api/propostas/escolher-plano.php')` com `{ slug, plano_id, extras, condicoes }`.
  4. Monta mensagem:
     ```
     Olá! Somos {nomeCasal} e gostaríamos de confirmar nosso interesse na proposta da Distinto.

     {linhas}

     Total: {fmtBRL}

     Ref: {slug}
     ```
  5. `window.open('https://wa.me/{WA_NUMBER}?text=' + encodeURIComponent(msg))`.
- CSS mobile do `#btn-approve` (apenas casamento): fixed bottom 30px / left 50% / translateX(-50%), min-width 250px, z-index 10001, opacidade 0→visível, pílula `border-radius: 50px`, fundo `#a8a8a8`, Montserrat.

### 9.5 Temas

- `theme-filmmaker`: `--bg #0a0a0a`, `--text #fff`, `--accent #ff6b00`; `.highlight` (font script 1.2em) e h1 com `font-cinematic` + `letter-spacing: 2px`.
- Slides com fundo escuro usam `dark-page` (HUD e linhas invertem para branco).

---

## 10. Templates Slide a Slide

### 10.1 `template-casamento.php` (19 páginas/slides)

| # | Página | Conteúdo |
|---|---|---|
| 01 | CAPA | `h1` script (Dancing Script ~6rem) com nome do casal, "PROPOSTA COMERCIAL" + ano, moldura (`radial-gradient` 10px 10px, `-30deg`), selo "moldura" (ícone câmera) e `barra-superior`; `h2` `{noivo} & {noiva}`, data do casamento em "dd de mês de yyyy" (dia em negrito), seta "Scroll para explorar" |
| 02 | BOAS-VINDAS | "A Proposta / {categoriaProjeto}", história da Distinto |
| 03 | ONDE O TEMPO PARA (MANIFESTO) | Manifesto com `secoes.visao` (IA) |
| 04 | VISÃO E MISSÃO | Grid visão/missão |
| 05 | PERSPECTIVA | Foto full + copy |
| 06 | EXPERIÊNCIAS DISTINTAS | Cards dos 3 pacotes (Heritage/Cinematic/Essencial) com benefícios |
| 07 | FULL IMAGE | Imagem em tela cheia |
| 08 | EXPERIÊNCIA HERITAGE | Detalhes do pacote (montserrat 3.2rem/300 uppercase), upgrades |
| 09 | EXPERIÊNCIA CINEMATIC | Detalhes do pacote Cinematic |
| 10 | REGISTRO ESSENCIAL | Pacote essencial + `renderItensPersonalizadosCasamento($itensEssencial)`; valor fallback `R$ 2.800,00` |
| 10.5 | INVESTIMENTO E PLANEJAMENTO | "Atualizações desta versão" e "Andamento da proposta" (`andamento_proposta`) |
| 11 | ESCOLHA SEU PACOTE — INTERATIVO | Slides de pacote com modal de escolha + upgrades (boudoir/pre-wedding) |
| 12 | WEDDING PORTFOLIO CAPA | "WEDDING PORTFOLIO / VERSÕES DA HISTÓRIA" (Montserrat 1.4rem #444 `letter-spacing: 0.2em`), `foto-section-11.png` |
| 13 | PORTFÓLIO PEDRO E VANESSA | bg `#000`, flex row gap 2px, `portfolio-left-col` flex 2, `portfolio-label` (top 40px / left 40px), `foto-section-cima-12.png` |
| 14 | PORTFÓLIO GABRIEL E JULIA | Mesmo padrão |
| 15 | PORTFÓLIO BRUNA E ROBSON | Mesmo padrão |
| 16 | PORTFÓLIO CHRISTIAN E ALINE | Mesmo padrão |
| 17 | OS OLHARES POR TRÁS DAS LENTES (EQUIPE) | Grid de equipe (`.team-item` width 45% no mobile) |
| 18 | PROVA SOCIAL & COMPROMISSO | Depoimentos (2 ativos, categoria `casamento`, coluna no mobile) |
| 19 | VAMOS DAR O PRÓXIMO PASSO? | CTA + contatos |
| 19 (bônus) | THANK YOU | Fechamento |

- **Dados padrão** (fallback no template): `prazo_previas='48 horas'`, `prazo_final='60 dias úteis'`, `validade_proposta='7'`, `instagram='@distintowedding'`, `email='contato@wedistinto.com'`, `whatsapp='+55 27 9 8858-6935'`.
- Mensagem pessoal via `IAPropostas::gerarMensagemWhatsApp($nomeNoivo, $nomeNoiva, $nomeCasal)`.

### 10.2 `template-marketing.php` (14 slides)

- **Slide 1 (capa)**: `h1` 2rem/800/uppercase `letter-spacing:-2px` preto; moldura.
- **Slide 2**: `h2` 3.25rem/800 uppercase branco, `width:60%`.
- **Slide 3**: `h3` `clamp(24px, 1.75rem, 56px)` branco.
- **Slides de etapas** (`is-etapas`): `h2` com `visibility: hidden` (título controlado pelo HUD); **pílulas de etapas** (`etapas-title`, z-index 9999, lista 14rem com `margin-left: -7rem`, `col 2/span 2`), etapa ativa com fundo preto. `$etapasAtivas` default inclui imersão/diagnóstico/planejamento/linguagem visual...
- **Slide investimento** (`is-investimento`): valor + condições de pagamento.
- Slides finais: benefícios e CTA; slide escuro final.

### 10.3 `template-filmmaker.php` (4 slides)

- **Slide 1**: capa com foto cover `linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.8))` sobre Unsplash (`photo-1492691523567`), `h1 80px` uppercase.
- **Slide 2 — "01. A Visão Criativa"**: `font-cinematic` 14px uppercase na cor `--accent`, `h2 42px` "A Arte de Contar **Histórias**" (`.highlight`), texto `secoes.visao` (fallback), foto `photo-1485846234645` com moldura 150px border accent.
- **Slide 3 — "02. O Que Está Incluso"**: grid com `h3` 18px accent (EQUIPAMENTO / EQUIPE / PÓS-PRODUÇÃO) + listas.
- **Slide 4**: investimento e CTA.

### 10.4 `template-15anos.php` (4 slides)

- **Slide 1**: capa `h1` "IT'S HER **TIME**."
- **Slide 2**: sobre.
- **Slide 3**: grid Details com `h3` **Visuals / Experience / Deliverables** (fallbacks).
- **Slide 4 — Investimento**: `.price-tag` (72px), pílula validade (`border-radius: 50px`, padding 10px; validade vencida → borda `--accent`), CTAs.

---

## 11. CSS e JS da Apresentação

### `assets/css/propostas.css` (843 linhas)

- Base da apresentação (slides, wrapper, moldura HUD, linhas verticais, títulos fixos, botões flutuantes, modal de exportação).
- `.btn-export-top`: círculo 45px, top 15px, right 30px, z-index 1001, blur, fundo `rgba(0,0,0,.4)`, com tooltip (`span` right 55px, fundo `#000`, radius 10px).
- `@media (max-width: 1023px)`: esconde `.fixed-section-title`, linhas `::before/::after`, ajusta grid.
- Animações de entrada por seção (IntersectionObserver → classe visível).

### `assets/css/propostas-mobile.css` (406 linhas)

- `.mobile-header`: sticky, z-index 600, `backdrop-filter: blur(12px)`, `rgba(255,255,255,.96)`; logo 11px/800 uppercase; título 10px `#6b7280` com ellipsis.
- `.mobile-action-bar`: fundo `#000`, padding 20px + `env(safe-area-inset-bottom)`, itens flex:1 (pílulas).
- Modal de exportação: 90vw, padding 24px, opções em coluna.
- Pílulas: validade (`border-radius: 3.125rem` + `border: 1px solid`) 9px padding 10px 16px; badges de serviço (`border-radius: 3.125rem` + `background: #333`) 9px padding 6px 14px.

### `assets/js/propostas.js` (524 linhas)

- Config: `field` map (posições percentuais para staging de PDF), seções do template, `fmtBRL`.
- IntersectionObserver para `is-leaving` e títulos do HUD.
- `showButton()`: mostra botão fixo (scroll > 150), esconde após 2s.
- `window.showExportModal`, `showExportPDF`, staging das páginas.
- Modal de pacote (Alpine `x-data`), montagem de `linhas`, POST para `escolher-plano.php`, abertura do `wa.me`.

---

## 12. Sugestões de Mapeamento para Next.js

### 12.1 Backend / APIs → Route Handlers

| PHP | Next.js (sugestão) |
|---|---|
| `api/propostas/gerar.php` | `POST /api/propostas` (App Router Route Handler) |
| `api/propostas/atualizar.php` | `PATCH /api/propostas/:id` |
| `api/propostas/fechamento.php` | `PATCH /api/propostas/:id/fechamento` |
| `api/propostas/escolher-plano.php` | `POST /api/propostas/:slug/escolha` (sem auth) |
| `api/propostas/mensagem-whatsapp.php` | `GET /api/propostas/mensagem-whatsapp` |
| `api/propostas/organizar.php` | `POST /api/pastas` + `POST /api/pastas/move` + `DELETE /api/propostas/:id` |
| `api/gerenciamento/proposta_historico.php` | `GET/POST /api/propostas/:id/historico` |
| `api/pdf/proposta.php` | `POST /api/propostas/:id/pdf` (ou geração client-side com `html2canvas`/`puppeteer`) |
| `gerenciamento/propostas.php` | `app/(dashboard)/propostas/page.tsx` |
| `gerenciamento/proposta_nova.php` | `app/(dashboard)/propostas/nova/page.tsx` |
| `gerenciamento/proposta_editar.php` | `app/(dashboard)/propostas/[id]/editar/page.tsx` |
| `p.php` | **`src/pages/p/[slug].tsx` já existe** — replicar moldura/templates como componentes |
| `includes/ia_propostas.php` | `lib/ia/propostas.ts` (SDK `@google/genai`, `GEMINI_API_KEY` via env; fallbacks idênticos) |

### 12.2 Componentes sugeridos

- `ProposalShell` (wrapper + HUD/Moldura + scroll-snap) — componente cliente (`use client`).
- `ProposalPage` (slide) — recebe layout/tema (`dark-page`, `is-etapas`, `is-pacote`, `is-investimento`).
- `WeddingPackageSlide`, `PackageChoiceModal` (Alpine → React state/context).
- `PortfolioPage`, `TeamSlide`, `TestimonialsSlide`, `EtapasPills`.
- `ExportModal` + gerador de PDF (mantendo o staging por campos percentuais).
- `FixedSectionTitle` (HUD) sincronizado por `IntersectionObserver`.

### 12.3 Migração de dados

- Padronizar schema de `propostas` (definir `criado_em` vs `created_at`, `status` default, precisão de `valor_total`).
- Embrulhar `dados_json` como `jsonb` no Postgres e criar tipos/validação (zod) para cada tipo de proposta.
- Migrar `propostas_historico` para tabela formal (FK em `propostas.id`, `user_id` FK em `users.id`) — hoje é criada on-the-fly.
- Manter **todos os fallbacks de IA** (a IA pode estar indisponível) e os textos padrão em PT-BR.
- Unificar IA: decidir entre Gemini (`ia_propostas.php`) e Groq (`mensagem-whatsapp.php`).

---

## 13. Pendências / Inconsistências a Registrar

1. Schema `propostas` divergente entre as duas migrações (colunas de data, default de `status`, precisão de `valor_total`).
2. `propostas_historico` sem FK e criada on-the-fly em 4 lugares diferentes (SQL duplicado).
3. Sessão: `atualizar.php`/`fechamento.php` usam `$_SESSION['usuario_id']`; `proposta_historico.php` usa `$_SESSION['user_id']` (padronizar).
4. IA: `mensagem-whatsapp.php` usa Groq, o restante usa Gemini.
5. `p.php` lê `created_at` (implica schema com `created_at`).
6. `escolher-plano.php` usa `user_id='publico'`; sem rastreabilidade real do cliente.
7. Encodings corrompidos em `ia_propostas.php` (ex.: `redaÃ§Ã£o`, `Ã©`, `âœ¨`) — UTF-8 mal duplo-encoded em strings de prompt/fallback.