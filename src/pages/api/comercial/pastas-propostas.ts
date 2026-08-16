import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { query } from '@/lib/db';
import { generateId } from '@/lib/helpers';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  const method = req.method;

  if (method === 'GET') {
    try {
      const rows = await query(`SELECT * FROM pastas_propostas ORDER BY nome ASC`);
      return res.status(200).json(rows);
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'POST') {
    try {
      const { nome, cor } = req.body || {};
      if (!nome) return res.status(422).json({ erro: 'Nome da pasta é obrigatório' });

      const id = generateId();
      await query(
        `INSERT INTO pastas_propostas (id, nome, cor) VALUES ($1, $2, $3)`,
        [id, nome.trim(), cor || '#3b82f6']
      );

      return res.status(201).json({ ok: true, id, nome, cor });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'DELETE') {
    try {
      const { id } = req.query;
      if (!id) return res.status(422).json({ erro: 'ID da pasta é obrigatório' });

      await query(`DELETE FROM pastas_propostas WHERE id = $1`, [id]);
      await query(`UPDATE propostas SET pasta_id = NULL WHERE pasta_id = $1`, [id]);

      return res.status(200).json({ ok: true });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  return res.status(405).json({ erro: 'Método não permitido' });
});
