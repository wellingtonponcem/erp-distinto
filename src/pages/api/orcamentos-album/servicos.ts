import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { query } from '@/lib/db';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  if (req.method !== 'GET') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const categoria = (req.query.categoria as string) || '';

    let sql = 'SELECT * FROM servicos WHERE ativo = 1';
    const params: any[] = [];

    if (categoria && categoria !== 'todos') {
      sql += ' AND categoria = ?';
      params.push(categoria);
    }

    sql += ' ORDER BY categoria ASC, preco_venda ASC';

    const rows = await query(sql, params);

    const formatados = rows.map((r: any) => ({
      id: r.id,
      nome: r.nome || '',
      tipo: r.tipo || 'colecao',
      categoria: r.categoria || '15anos',
      categoria_original: r.categoria_original || 'Coleção Premium',
      descricao: r.descricao || '',
      custo_producao: r.custo_producao || 0,
      preco_venda: r.preco_venda || 0,
      valor_lamina_extra: r.valor_lamina_extra || 35,
      estojo_json: r.estojo_json || null,
      acabamento_json: r.acabamento_json || null,
      imagens_json: r.imagens_json || null,
      ativo: r.ativo,
    }));

    return res.status(200).json(formatados);
  } catch (err: any) {
    console.error('Erro ao buscar serviços:', err);
    return res.status(500).json({ erro: err.message });
  }
});
