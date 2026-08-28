import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { queryOne } from '@/lib/db';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const { id } = req.body;

    if (!id) {
      return res.status(422).json({ erro: 'ID do orçamento obrigatório.' });
    }

    const orcamento = await queryOne('SELECT * FROM orcamentos WHERE id = ?', [String(id)]);
    if (!orcamento) {
      return res.status(404).json({ erro: 'Orçamento não encontrado.' });
    }

    const cliente = orcamento.cliente_nome;
    const titulo = orcamento.titulo;
    const link = `${process.env.NEXT_PUBLIC_APP_URL || ''}/o/${orcamento.slug}`;
    const primeiroNome = cliente.split(' ')[0];

    const apiKey = process.env.GROQ_API_KEY || '';

    if (!apiKey) {
      const msg = `Oi, ${primeiroNome}! Tudo bem?\n\nPreparei o seu *${titulo}* com todas as opções de acabamentos e detalhes exatamente como conversamos.\n\nVocê pode visualizar e escolher sua coleção diretamente neste link exclusivo:\n👉 ${link}\n\nFico à disposição para qualquer dúvida!`;
      return res.status(200).json({ mensagem: msg });
    }

    const prompt = `Você é um consultor comercial da agência de fotografia e design de luxo Distinto.
Escreva uma mensagem de WhatsApp curta, elegante e descontraída para enviar ao cliente *${cliente}* apresentando o seu orçamento comercial.

Tom: próximo, profissional, elegante, entusiasmado.
NÃO use "prezado", "segue em anexo", "cordialmente" ou linguagem ultrapassada.
Use o primeiro nome do cliente: ${primeiroNome}.

Estrutura:
1. Saudação simpática (uma linha)
2. Apresentar que o orçamento está pronto e destacar que ele pode interagir e simular as opções de coleções diretamente na página.
3. Call to action com a tag exata [LINK]
4. Fechamento cordial.

Onde deve entrar a URL, escreva rigorosamente [LINK]. Responda APENAS com o texto final.`;

    const payload = {
      model: process.env.GROQ_MODEL || 'llama-3.3-70b-versatile',
      messages: [{ role: 'user', content: prompt }],
      temperature: 0.7,
      max_tokens: 300,
    };

    const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${apiKey}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    if (!response.ok) {
      const msg = `Oi, ${primeiroNome}! Tudo bem?\n\nPreparei o seu *${titulo}* com todas as opções de acabamentos e detalhes de coleções.\n\nVocê pode visualizar e simular sua coleção direto por este link:\n👉 ${link}\n\nQualquer dúvida estou por aqui!`;
      return res.status(200).json({ mensagem: msg });
    }

    const result = await response.json();
    const mensagemIA = (result.choices?.[0]?.message?.content || '').trim();

    if (!mensagemIA) {
      const msg = `Oi, ${primeiroNome}! Tudo bem?\n\nPreparei o seu *${titulo}* com todas as opções de acabamentos e coleções.\n\nVisualizar orçamento:\n👉 ${link}`;
      return res.status(200).json({ mensagem: msg });
    }

    const mensagemFinal = mensagemIA.replace(/\[LINK\]/g, `👉 ${link}`);

    return res.status(200).json({ mensagem: mensagemFinal });
  } catch (err: any) {
    console.error('Erro ao gerar mensagem WhatsApp:', err);
    return res.status(500).json({ erro: err.message });
  }
});
