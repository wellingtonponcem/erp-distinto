import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { queryOne } from '@/lib/db';
import axios from 'axios';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const { acao, tipo, cliente, briefing, objetivo } = req.body || {};

    // 1. Obter chave da API do Gemini no banco de dados ou variáveis de ambiente
    const config = await queryOne<{ gemini_api_key?: string }>(
      `SELECT gemini_api_key FROM configuracao_empresa WHERE id = 'principal' LIMIT 1`
    );

    const geminiKey = config?.gemini_api_key || process.env.GEMINI_API_KEY || process.env.GOOGLE_AI_KEY;

    let textoGerado = '';

    if (geminiKey && !geminiKey.startsWith('SUA_')) {
      try {
        let prompt = '';
        if (acao === 'gerar_whatsapp') {
          prompt = `Você é um consultor comercial de alta performance do estúdio ERP Distinto.
Escreva uma mensagem curta, elegante e entusiasmada para enviar via WhatsApp para o cliente "${cliente || 'Cliente'}" apresentando a Proposta Comercial.
Não use termos genéricos como "prezado" ou "segue em anexo". Inclua Emojis discretos.`;
        } else {
          prompt = `Você é um estrategista sênior e diretor criativo de propostas comerciais de alto padrão.
Escreva a seção "${acao || 'visao'}" de uma proposta comercial para o cliente "${cliente || 'Cliente'}" na categoria "${tipo || 'casamento'}".
Briefing informado: "${briefing || objetivo || 'Projeto exclusivo de alta qualidade'}".
Seja persuasivo, elegante e focado no valor percebido. Escreva em no máximo 3 parágrafos curtos. Sem usar markdown exagerado.`;
        }

        const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=${geminiKey}`;
        const response = await axios.post(
          url,
          {
            contents: [{ parts: [{ text: prompt }] }],
          },
          { timeout: 15000 }
        );

        textoGerado = response.data?.candidates?.[0]?.content?.parts?.[0]?.text || '';
      } catch (e: any) {
        console.error('Erro na chamada da API do Gemini:', e.message);
      }
    }

    // Fallbacks inteligentes caso a IA esteja sem chave ou indisponível
    if (!textoGerado) {
      if (acao === 'gerar_whatsapp') {
        textoGerado = `Olá ${cliente || 'tudo bem'}! ✨\n\nAcabei de preparar o projeto exclusivo da sua proposta comercial.\n\nAcesse no link abaixo para visualizar a apresentação completa em alta definição e selecionar o seu pacote:\n👉 [LINK]`;
      } else if (tipo === 'marketing') {
        textoGerado = `Prezados executivos da ${cliente || 'Empresa'},\n\nApresentamos uma abordagem inovadora e integrada para acelerar o crescimento e o posicionamento da marca no mercado digital.\n\nNossa estratégia une captação audiovisual de alta definição, planejamento de conteúdo inteligente e gestão de anúncios pagos para atração constante de clientes qualificados.`;
      } else if (tipo === 'filmmaker') {
        textoGerado = `A visão artística para o projeto de ${cliente || 'Vídeo'} é fundamentada em linguagem cinematográfica de alto impacto.\n\nCombinamos equipamentos de última geração, captação aérea com drone 4K e edição de som imersiva para criar uma narrativa inesquecível.`;
      } else {
        textoGerado = `Preparado exclusivamente para ${cliente || 'o Casal'}.\n\nA visão artística para o seu grande dia une sofisticação, emoção e uma abordagem estética refinada. Nosso objetivo é eternizar cada detalhe em filmes e fotografias memoráveis.`;
      }
    }

    return res.status(200).json({ ok: true, texto: textoGerado });
  } catch (err: any) {
    return res.status(500).json({ erro: err.message });
  }
});
