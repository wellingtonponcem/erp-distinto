import { NextApiRequest, NextApiResponse } from 'next';
import { queryOne } from '@/lib/db';

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method !== 'GET') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const slug = (req.query.slug as string) || '';

    if (!slug) {
      return res.status(422).json({ erro: 'Slug não informado.' });
    }

    const orcamento = await queryOne('SELECT * FROM orcamentos_b2b WHERE slug = ?', [slug]);
    if (!orcamento) {
      return res.status(404).json({ erro: 'Orçamento não encontrado.' });
    }

    let dados: any = {};
    try {
      dados = typeof orcamento.dados_json === 'string'
        ? JSON.parse(orcamento.dados_json)
        : (orcamento.dados_json || {});
    } catch (e) {}

    // Buscar telefone da empresa para WhatsApp
    let configEmpresa: any = {};
    try {
      configEmpresa = await queryOne('SELECT * FROM configuracao_empresa WHERE id = ? LIMIT 1', ['principal']) || {};
    } catch (e) {}

    let telEmpresa = String(configEmpresa.telefone || '5527988586935').replace(/\D/g, '');
    if (!telEmpresa.startsWith('55') && telEmpresa.length >= 10) {
      telEmpresa = '55' + telEmpresa;
    }

    return res.status(200).json({
      orcamento: {
        id: orcamento.id,
        cliente_nome: orcamento.cliente_nome,
        cliente_empresa: orcamento.cliente_empresa || '',
        titulo: orcamento.titulo,
        slug: orcamento.slug,
        valor_total: parseFloat(orcamento.valor_total) || 0,
        validade: orcamento.validade,
        status: orcamento.status,
        criado_em: orcamento.criado_em,
      },
      dados,
      whatsapp_empresa: telEmpresa,
    });
  } catch (err: any) {
    console.error('Erro ao buscar orcamento B2B publico:', err);
    return res.status(500).json({ erro: err.message });
  }
}
