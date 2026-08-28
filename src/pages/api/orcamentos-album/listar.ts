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

    let sql = 'SELECT * FROM orcamentos WHERE 1=1';
    const params: any[] = [];

    if (status) {
      sql += ' AND status = ?';
      params.push(status);
    }

    if (busca) {
      sql += ' AND (cliente_nome LIKE ? OR titulo LIKE ? OR slug LIKE ?)';
      const term = `%${busca}%`;
      params.push(term, term, term);
    }

    sql += ' ORDER BY created_at DESC';

    const rows = await query(sql, params);

    const formatados = rows.map((r: any) => {
      let dadosParsed: any = {};
      try {
        dadosParsed = typeof r.dados_json === 'string' ? JSON.parse(r.dados_json) : (r.dados_json || {});
      } catch (e) {}

      return {
        id: r.id,
        cliente_nome: r.cliente_nome || '',
        tipo: r.tipo || 'albuns_15anos',
        slug: r.slug || '',
        titulo: r.titulo || '',
        subtitulo: r.subtitulo || '',
        validade: r.validade || null,
        valor_total: r.valor_total || 0,
        status: r.status || 'pendente',
        created_at: r.created_at,
        dados: dadosParsed,
      };
    });

    return res.status(200).json(formatados);
  } catch (err: any) {
    console.error('Erro ao listar orçamentos de álbuns:', err);
    return res.status(500).json({ erro: err.message });
  }
});
