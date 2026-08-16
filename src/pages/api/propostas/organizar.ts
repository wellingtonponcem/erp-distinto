import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { query } from '@/lib/db';

/**
 * Port de api/propostas/organizar.php — ações de organização:
 * move / create_folder / delete_folder / rename_folder / delete_proposal.
 */
export default requireAuth(async (req: NextApiRequest, res: NextApiResponse) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Método não permitido' });
  }

  const data = req.body || {};
  if (!data.action) {
    return res.status(400).json({ error: 'Ação não informada' });
  }

  try {
    switch (data.action) {
      case 'move':
        await query(`UPDATE propostas SET pasta_id = $1 WHERE id = $2`, [data.pasta_id ?? null, data.proposta_id]);
        return res.status(200).json({ success: true });

      case 'create_folder':
        await query(`INSERT INTO pastas_propostas (id, nome) VALUES ($1, $2)`, [data.id, data.nome]);
        return res.status(200).json({ success: true });

      case 'delete_folder':
        await query(`UPDATE propostas SET pasta_id = NULL WHERE pasta_id = $1`, [data.id]);
        await query(`DELETE FROM pastas_propostas WHERE id = $1`, [data.id]);
        return res.status(200).json({ success: true });

      case 'rename_folder':
        await query(`UPDATE pastas_propostas SET nome = $1 WHERE id = $2`, [data.nome, data.id]);
        return res.status(200).json({ success: true });

      case 'delete_proposal':
        await query(`DELETE FROM propostas WHERE id = $1`, [data.id]);
        return res.status(200).json({ success: true });

      default:
        return res.status(400).json({ error: 'Ação inválida' });
    }
  } catch (err: any) {
    return res.status(500).json({ error: err.message });
  }
});