import { NextApiRequest, NextApiResponse } from 'next';
import { GoogleGenerativeAI } from '@google/generative-ai';
import { requireAuth } from '@/lib/helpers';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  const { prompt, contexto } = req.body || {};

  if (!prompt) {
    return res.status(422).json({ erro: 'Prompt é obrigatório' });
  }

  const apiKey = process.env.GEMINI_API_KEY;
  if (!apiKey) {
    return res.status(500).json({ erro: 'Chave GEMINI_API_KEY não configurada no servidor.' });
  }

  try {
    const genAI = new GoogleGenerativeAI(apiKey);
    const model = genAI.getGenerativeModel({ model: 'gemini-1.5-flash' });

    const systemPrompt = `Você é um assistente especialista em redação de propostas comerciais e contratos para o ERP Distinto. Responda em Português do Brasil com tom profissional e direto.\nContexto: ${contexto || 'Geração de proposta/roteiro'}`;

    const result = await model.generateContent(`${systemPrompt}\n\nInstrução: ${prompt}`);
    const text = result.response.text();

    return res.status(200).json({ ok: true, resposta: text });
  } catch (err: any) {
    console.error('AI Processing Error:', err);
    return res.status(500).json({ erro: 'Erro ao processar chamada de IA: ' + err.message });
  }
});
