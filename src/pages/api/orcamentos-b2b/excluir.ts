import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { query } from '@/lib/db';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const { id } = req.body;

    if (!id) {
      return res.status(422).json({ erro: 'ID do orçamento é obrigatório.' });
    }

    await query('DELETE FROM orcamentos_b2b WHERE id = ?', [String(id)]);

    return res.status(200).json({ success: true, mensagem: 'Orçamento excluído com sucesso.' });
  } catch (err: any) {
    console.error('Erro ao excluir orcamento B2B:', err);
    return res.status(500).json({ erro: err.message });
  }
});
