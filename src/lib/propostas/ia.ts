import { queryOne } from '@/lib/db';

/**
 * Port de includes/ia_propostas.php (classe IAPropostas).
 * Geração de textos de seções, melhoria de objetivo, recomendação de próximo
 * passo e mensagem de WhatsApp via Gemini, com fallbacks locais.
 */

let geminiKeyCache: string | null | undefined;

async function getGeminiKey(): Promise<string | null> {
  if (geminiKeyCache !== undefined) return geminiKeyCache;
  try {
    const row = await queryOne<{ gemini_api_key?: string }>(
      `SELECT gemini_api_key FROM configuracao_empresa WHERE id = 'principal' LIMIT 1`
    );
    let key = row?.gemini_api_key || '';
    if (!key) key = process.env.GEMINI_API_KEY || '';
    geminiKeyCache = key && !key.startsWith('SUA_') ? key : null;
  } catch (e) {
    geminiKeyCache = null;
  }
  return geminiKeyCache;
}

export function resetGeminiKeyCache(): void {
  geminiKeyCache = undefined;
}

const MODELS = ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-1.5-flash'];

async function chamarGemini(parts: Array<{ text: string }>, model = 'gemini-2.5-flash'): Promise<string> {
  const apiKey = await getGeminiKey();
  if (!apiKey) return 'Erro: Chave da API do Gemini não configurada nas configurações da empresa.';

  const modelsToTry = [model, ...MODELS.filter((m) => m !== model)];
  let lastError = '';

  for (const attemptModel of modelsToTry) {
    for (let attempt = 1; attempt <= 2; attempt++) {
      try {
        const res = await fetch(
          `https://generativelanguage.googleapis.com/v1beta/models/${attemptModel}:generateContent?key=${encodeURIComponent(apiKey)}`,
          {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              contents: [{ parts }],
              generationConfig: { temperature: 0.3, maxOutputTokens: 8192 },
            }),
            signal: AbortSignal.timeout(90000),
          }
        );

        if (res.status === 200) {
          const data = await res.json();
          return data?.candidates?.[0]?.content?.parts?.[0]?.text || 'Erro: resposta inesperada da API.';
        }

        const errData = await res.json().catch(() => ({}));
        const msg = errData?.error?.message || '';
        lastError = `Erro Gemini (HTTP ${res.status}): ${msg}`;

        if ([429, 403, 401, 400].includes(res.status)) {
          return lastError;
        }

        if (res.status === 503) {
          await new Promise((r) => setTimeout(r, 500));
          break;
        }

        await new Promise((r) => setTimeout(r, 300));
      } catch (e: any) {
        lastError = `Erro Gemini: ${e?.message || e}`;
        if (e?.name === 'AbortError') {
          await new Promise((r) => setTimeout(r, 300));
        }
      }
    }
  }

  return lastError || 'Erro: não foi possível contactar a API do Gemini após múltiplas tentativas.';
}

function textoSecaoFallback(tipo: string, secao: string, cliente: string, briefing: string): string {
  if (tipo === 'casamento') {
    return briefing !== ''
      ? `Preparamos esta proposta pensando no que vocês compartilharam: ${briefing}. A ideia é registrar o casamento com cuidado, sensibilidade e clareza em cada entrega.`
      : 'Preparamos esta proposta para contar a história do casal com cuidado, sensibilidade e uma entrega visual que continue fazendo sentido com o passar dos anos.';
  }

  if (secao === 'desafio') {
    return briefing !== ''
      ? `O principal desafio é transformar o contexto apresentado em um plano claro, viável e mensurável: ${briefing}.`
      : 'O principal desafio é organizar as prioridades do projeto e transformar as necessidades do cliente em ações claras.';
  }

  if (secao === 'objetivo') {
    return `O objetivo é entregar uma solução clara, bem executada e alinhada ao momento de ${cliente}, com foco em resultado e consistência.`;
  }

  return `Esta proposta organiza o escopo, os prazos e os investimentos para que ${cliente} tenha clareza sobre o trabalho e os próximos passos.`;
}

export async function gerarTextoSecao(
  tipo: string,
  secao: string,
  contexto: Record<string, any> = {}
): Promise<string> {
  const briefing = String(contexto.briefing ?? contexto.objetivo ?? '').trim();
  const cliente = String(contexto.cliente_nome ?? contexto.cliente ?? 'cliente').trim();
  const fallback = textoSecaoFallback(tipo, secao, cliente, briefing);

  try {
    if (!(await getGeminiKey())) return fallback;

    const contextoJson = JSON.stringify(contexto, null, 2);
    const prompt = `Voce e um estrategista comercial da Distinto.
Escreva uma secao curta, clara e humana para uma proposta do tipo '${tipo}'.
Secao solicitada: '${secao}'.
O texto deve ser em portugues do Brasil, facil de entender, sem jargoes e sem markdown.
Use no maximo 2 paragrafos.

Contexto:
${contextoJson}`;

    const resposta = (await chamarGemini([{ text: prompt }])).trim();
    if (resposta === '' || resposta.startsWith('Erro')) return fallback;
    return resposta;
  } catch (e) {
    return fallback;
  }
}

export async function melhorarObjetivo(objetivo: string, contexto: Record<string, any> = {}): Promise<string> {
  const objetivoClean = String(objetivo).trim();
  if (objetivoClean === '') {
    return gerarTextoSecao(String(contexto.tipo ?? 'marketing'), 'objetivo', contexto);
  }

  try {
    if (!(await getGeminiKey())) return objetivoClean;

    const prompt = `Reescreva o objetivo abaixo para uma proposta comercial da Distinto.
Use portugues simples, direto e profissional. Nao invente dados.
Retorne apenas o texto final, sem explicacoes.

Objetivo:
${objetivoClean}`;

    const resposta = (await chamarGemini([{ text: prompt }])).trim();
    if (resposta === '' || resposta.startsWith('Erro')) return objetivoClean;
    return resposta;
  } catch (e) {
    return objetivoClean;
  }
}

export async function recomendarProximoPasso(
  proposta: any,
  historico: any[] = []
): Promise<string> {
  const tipo = String(proposta.tipo ?? '');
  const status = String(proposta.status ?? '');

  let dados: any = {};
  try {
    dados = typeof proposta.dados_json === 'string' ? JSON.parse(proposta.dados_json) : (proposta.dados_json || {});
  } catch (e) {}

  if (tipo === 'casamento') {
    const plano = dados?.cliente_escolha?.plano_id ?? dados?.pacote_dado_andamento ?? '';
    const valor = Number(dados?.cliente_escolha?.valor_total ?? proposta.valor_total ?? 0) || 0;
    if (status === 'aceita' && plano && valor > 0) {
      return 'A proposta esta pronta para virar contrato. Confira CPF, e-mail de assinatura e locais do casamento antes de enviar.';
    }
    if (!plano) {
      return 'Defina o plano escolhido pelo casal e os opcionais antes de gerar o contrato.';
    }
    if (valor <= 0) {
      return 'Confira o valor final do fechamento antes de gerar o contrato.';
    }
    return 'Revise as condicoes de pagamento e gere o contrato quando os dados de assinatura estiverem completos.';
  }

  if (status === 'rascunho') {
    return 'Revise os dados principais, visualize a proposta e envie para o cliente.';
  }
  if (status === 'pendente') {
    return 'Faca o acompanhamento com o cliente e registre o retorno no historico.';
  }
  if (status === 'aceita') {
    return 'Gere o contrato e confira os dados financeiros antes da assinatura.';
  }
  if (status === 'recusada') {
    return 'Registre o motivo da recusa para melhorar as proximas propostas.';
  }

  return 'Revise a proposta e escolha o proximo passo no funil comercial.';
}

export async function gerarMensagemWhatsApp(
  nomeNoivo: string,
  nomeNoiva: string,
  nomeCasal: string
): Promise<string> {
  const nomeNoivaSimples = String(nomeNoiva).trim().split(' ')[0] || '';
  const nomeNoivoSimples = String(nomeNoivo).trim().split(' ')[0] || '';
  const nomes = nomeNoivaSimples && nomeNoivoSimples ? `${nomeNoivaSimples} e ${nomeNoivoSimples}` : nomeCasal;

  const fallback = `Olá Wellington! Ficamos encantados com a proposta do nosso casamento (${nomes}). Gostaríamos de conversar para alinhar os detalhes e dar o próximo passo! ✨`;

  try {
    if (!(await getGeminiKey())) return fallback;

    const prompt = `Você é um assistente simpático e caloroso de um estúdio de fotografia e filmmaking de luxo para casamentos chamado Distinto.
Gere uma mensagem curta, calorosa e engajadora que os noivos (${nomes}) enviariam pelo WhatsApp para o estúdio para demonstrar interesse em fechar a proposta do casamento deles.
A mensagem deve ser escrita na perspectiva dos noivos enviando para o estúdio.
Exemplo de tom: 'Olá Wellington! Amamos a proposta comercial e a forma como vocês enxergam nosso casamento. Queremos conversar sobre os próximos passos! ✨'
Retorne APENAS a mensagem direta, sem aspas, sem explicações e sem introduções.`;

    const resposta = (await chamarGemini([{ text: prompt }])).trim().replace(/^["']|["']$/g, '');
    return resposta || fallback;
  } catch (e) {
    return fallback;
  }
}

export { chamarGemini };