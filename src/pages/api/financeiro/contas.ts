import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth, generateId } from '@/lib/helpers';
import { query } from '@/lib/db';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse) => {
  const method = req.method;

  if (method === 'GET') {
    const rows = await query('SELECT * FROM contas ORDER BY created_at DESC');
    return res.status(200).json(rows);
  }

  if (method === 'POST') {
    const { nome, tipo, saldo_inicial, cor } = req.body || {};
    if (!nome) return res.status(422).json({ erro: 'Nome da conta é obrigatório' });

    const id = generateId();
    await query(
      'INSERT INTO contas (id, nome, tipo, saldo_inicial, cor) VALUES ($1, $2, $3, $4, $5)',
      [id, nome, tipo || 'corrente', parseFloat(saldo_inicial || 0), cor || '#000000']
    );

    return res.status(201).json({ ok: true, id });
  }

  if (method === 'DELETE') {
    const { id } = req.query;
    if (!id) return res.status(422).json({ erro: 'ID é obrigatório' });

    await query('DELETE FROM contas WHERE id = $1', [id]);
    return res.status(200).json({ ok: true });
  }

  return res.status(405).json({ erro: 'Método não permitido' });
});
