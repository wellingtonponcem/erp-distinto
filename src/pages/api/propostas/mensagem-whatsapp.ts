import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { queryOne } from '@/lib/db';
import { gerarMensagemWhatsApp as gerarMensagemIa } from '@/lib/propostas/ia';

/**
 * Port de api/propostas/mensagem-whatsapp.php.
 * Gera a mensagem de WhatsApp para envio da proposta ao cliente.
 * O PHP usava Groq; aqui replicamos o fluxo com Gemini via ia.ts (fallback local).
 */
export default requireAuth(async (req: NextApiRequest, res: NextApiResponse) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  const { id } = req.body || {};
  if (!id) {
    return res.status(422).json({ erro: 'ID da proposta obrigatório.' });
  }

  try {
    const proposta = await queryOne(`SELECT * FROM propostas WHERE id = $1 LIMIT 1`, [String(id)]);
    if (!proposta) {
      return res.status(404).json({ erro: 'Proposta não encontrada.' });
    }

    let dados: any = {};
    try {
      dados = typeof proposta.dados_json === 'string' ? JSON.parse(proposta.dados_json) : (proposta.dados_json || {});
    } catch (e) {}

    const cliente = proposta.cliente_nome || '';
    const titulo = proposta.titulo || '';
    const link = `${process.env.NEXT_PUBLIC_APP_URL || process.env.APP_URL || ''}/p/${proposta.slug}`;

    const servicos = Array.isArray(dados.servicos) ? dados.servicos : [];
    const nomesServicos = servicos.map((s: any) => s?.nome || '').filter(Boolean);
    const resumoServicos = nomesServicos.join(', ');

    const briefing = dados.briefing || '';
    const objetivo = dados.objetivo_original || '';
    const meses = dados.meses_contrato || 12;

    const responsavel = dados.responsavel || '';
    const nomeParaSaudacao = responsavel || cliente;
    const primeiroNome = nomeParaSaudacao.trim().split(' ')[0];

    const fallback = `Oi, ${primeiroNome}! Tudo bem?\n\nAcabei de subir o material do ${titulo} aqui no sistema. Deixei tudo bem visual pra você conseguir enxergar o projeto ganhando forma, exatamente como a gente conversou.\n\nDá uma olhada aqui:\n👉 ${link}`;

    if (proposta.tipo === 'casamento') {
      const nomeNoivo = dados.nome_noivo || '';
      const nomeNoiva = dados.nome_noiva || '';
      const mensagemIA = await gerarMensagemIa(nomeNoivo, nomeNoiva, cliente);
      const mensagemFinal = mensagemIA.replace(/\[LINK\]/g, `👉 ${link}`);
      return res.status(200).json({ mensagem: mensagemFinal });
    }

    const contexto = `Proposta: ${titulo}`
      + (proposta.subtitulo ? ` — ${proposta.subtitulo}` : '')
      + (resumoServicos ? `\nServiços: ${resumoServicos}` : '')
      + (objetivo ? `\nObjetivo do cliente: ${objetivo}` : '')
      + (briefing ? `\nBriefing: ${briefing}` : '')
      + `\nDuração do contrato: ${meses} meses`;

    const prompt = `Você é um estrategista de comunicação de uma agência de marketing premium brasileira chamada Distinto.
Escreva uma mensagem de WhatsApp curta e natural para enviar ao cliente *${cliente}* comunicando que a proposta comercial está pronta.

Tom: descontraído, próximo, confiante — como um amigo profissional que quer que o cliente se anime com o projeto.
NÃO use "prezado", "segue em anexo", "cordialmente" ou linguagem corporativa.
NÃO invente informações que não estejam no contexto.
Use o primeiro nome do cliente: ${primeiroNome}.

Estrutura obrigatória:
1. Saudação informal (uma linha só)
2. Anunciar que a proposta está pronta — transmitir empolgação genuína com o projeto, conectando com o que foi conversado (use o contexto abaixo)
3. Uma linha de call to action com o link (já incluído no placeholder [LINK])
4. Fechamento curto e caloroso (uma linha)

Contexto da proposta:
${contexto}

IMPORTANTE: onde deve aparecer o link, escreva exatamente o placeholder [LINK] — sem alterar, sem colocar URL real.
Responda APENAS com o texto da mensagem, sem explicações, sem aspas, sem markdown.`;

    let mensagemFinal = fallback;
    try {
      const { chamarGemini } = await import('@/lib/propostas/ia');
      const resposta = (await chamarGemini([{ text: prompt }], 'gemini-2.5-flash')).trim();
      if (resposta && !resposta.startsWith('Erro')) {
        mensagemFinal = resposta.replace(/\[LINK\]/g, `👉 ${link}`);
      }
    } catch (e) {}

    return res.status(200).json({ mensagem: mensagemFinal });
  } catch (err: any) {
    return res.status(500).json({ erro: err.message });
  }
});