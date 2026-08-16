import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth, generateId } from '@/lib/helpers';
import { query } from '@/lib/db';

const categoriasPadraoPadrao = [
  'Serviços',
  'Fotografia',
  'Vídeo',
  'Design',
  'Desenvolvimento',
  'Marketing',
  'Hospedagem & Servidores',
  'Impostos',
  'Aluguel',
  'Folha de Pagamento',
  'Equipamentos',
  'Alimentação',
  'Transporte',
  'Asaas',
  'Outros',
];

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse) => {
  const { method } = req;

  // Garantir existência da tabela categorias no banco Neon
  try {
    await query(`
      CREATE TABLE IF NOT EXISTS categorias (
        id TEXT PRIMARY KEY,
        nome TEXT UNIQUE NOT NULL,
        cor TEXT DEFAULT '#6b7280',
        criado_em TIMESTAMP DEFAULT NOW()
      )
    `);
  } catch (e) {}

  if (method === 'GET') {
    try {
      let rows = await query('SELECT * FROM categorias ORDER BY nome ASC');

      // Se a tabela estiver vazia, criar as categorias padrão da empresa
      if (!Array.isArray(rows) || rows.length === 0) {
        for (const cat of categoriasPadraoPadrao) {
          const id = generateId();
          try {
            await query(
              'INSERT INTO categorias (id, nome, cor) VALUES ($1, $2, $3) ON CONFLICT (nome) DO NOTHING',
              [id, cat, '#3b82f6']
            );
          } catch (e) {}
        }
        rows = await query('SELECT * FROM categorias ORDER BY nome ASC');
      }

      return res.status(200).json(rows);
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'POST') {
    try {
      const { nome, cor } = req.body || {};
      if (!nome || !nome.trim()) {
        return res.status(422).json({ erro: 'O nome da categoria é obrigatório.' });
      }

      const id = generateId();
      const nomeClean = nome.trim();

      await query(
        'INSERT INTO categorias (id, nome, cor) VALUES ($1, $2, $3) ON CONFLICT (nome) DO UPDATE SET cor = $3',
        [id, nomeClean, cor || '#3b82f6']
      );

      return res.status(201).json({ ok: true, id, nome: nomeClean });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'DELETE') {
    try {
      const { id } = req.query;
      if (!id) return res.status(422).json({ erro: 'ID da categoria é obrigatório.' });

      await query('DELETE FROM categorias WHERE id = $1', [id]);
      return res.status(200).json({ ok: true });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  return res.status(405).json({ erro: 'Método não permitido' });
});
