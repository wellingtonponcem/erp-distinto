import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { query } from '@/lib/db';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  if (req.method !== 'GET') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const status = (req.query.status as string) || '';
    const busca = (req.query.q as string) || '';

    let sql = 'SELECT * FROM orcamentos_b2b WHERE 1=1';
    const params: any[] = [];

    if (status) {
      sql += ' AND status = ?';
      params.push(status);
    }

    if (busca) {
      sql += ' AND (cliente_nome LIKE ? OR titulo LIKE ? OR slug LIKE ? OR cliente_empresa LIKE ?)';
      const term = `%${busca}%`;
      params.push(term, term, term, term);
    }

    sql += ' ORDER BY criado_em DESC';

    const rows = await query(sql, params);

    const formatados = rows.map((r: any) => {
      let dadosParsed: any = {};
      try {
        dadosParsed = typeof r.dados_json === 'string' ? JSON.parse(r.dados_json) : (r.dados_json || {});
      } catch (e) {}

      return {
        id: r.id,
        cliente_nome: r.cliente_nome || '',
        cliente_empresa: r.cliente_empresa || '',
        titulo: r.titulo || '',
        slug: r.slug || '',
        valor_total: parseFloat(r.valor_total) || 0,
        validade: r.validade || null,
        status: r.status || 'rascunho',
        criado_em: r.criado_em,
        dados: dadosParsed,
      };
    });

    return res.status(200).json(formatados);
  } catch (err: any) {
    console.error('Erro ao listar orcamentos B2B:', err);
    return res.status(500).json({ erro: err.message });
  }
});
