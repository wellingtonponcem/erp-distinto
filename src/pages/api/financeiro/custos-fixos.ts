import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth, generateId } from '@/lib/helpers';
import { query } from '@/lib/db';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse) => {
  const method = req.method;

  if (method === 'GET') {
    const rows = await query('SELECT * FROM custos_fixos ORDER BY created_at DESC');
    return res.status(200).json(rows);
  }

  if (method === 'POST') {
    const { nome, valor, categoria, recorrencia, dia_vencimento, forma_pagamento } = req.body || {};
    if (!nome || !valor) return res.status(422).json({ erro: 'Nome e valor são obrigatórios' });

    const id = generateId();
    await query(
      `INSERT INTO custos_fixos (
        id, nome, valor, categoria, recorrencia, dia_vencimento, forma_pagamento, ativo
      ) VALUES ($1, $2, $3, $4, $5, $6, $7, 1)`,
      [id, nome, parseFloat(valor), categoria || 'outros', recorrencia || 'mensal', parseInt(dia_vencimento || 5), forma_pagamento || 'pix']
    );

    return res.status(201).json({ ok: true, id });
  }

  if (method === 'DELETE') {
    const { id } = req.query;
    if (!id) return res.status(422).json({ erro: 'ID é obrigatório' });

    await query('DELETE FROM custos_fixos WHERE id = $1', [id]);
    return res.status(200).json({ ok: true });
  }

  return res.status(405).json({ erro: 'Método não permitido' });
});
