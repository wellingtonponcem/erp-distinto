import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth, generateId } from '@/lib/helpers';
import { query } from '@/lib/db';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse) => {
  const method = req.method;

  if (method === 'GET') {
    const clientes = await query('SELECT * FROM clientes ORDER BY created_at DESC');
    return res.status(200).json(clientes);
  }

  if (method === 'POST') {
    const { nome, email, telefone, cpf_cnpj } = req.body || {};
    if (!nome) return res.status(422).json({ erro: 'Nome é obrigatório' });

    const id = generateId();
    await query(
      'INSERT INTO clientes (id, nome, email, telefone, cpf_cnpj) VALUES ($1, $2, $3, $4, $5)',
      [id, nome, email || null, telefone || null, cpf_cnpj || null]
    );

    return res.status(201).json({ ok: true, id });
  }

  if (method === 'DELETE') {
    const { id } = req.query;
    if (!id) return res.status(422).json({ erro: 'ID é obrigatório' });

    await query('DELETE FROM clientes WHERE id = $1', [id]);
    return res.status(200).json({ ok: true });
  }

  return res.status(405).json({ erro: 'Método não permitido' });
});
