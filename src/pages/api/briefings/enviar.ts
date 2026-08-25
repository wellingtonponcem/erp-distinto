import { NextApiRequest, NextApiResponse } from 'next';
import { query, queryOne } from '@/lib/db';
import { generateId } from '@/lib/helpers';

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method === 'GET') {
    try {
      const rows = await query('SELECT * FROM briefings_resposta ORDER BY criado_em DESC');
      const briefings = rows.map((r: any) => ({
        ...r,
        dados: typeof r.dados_json === 'string' ? JSON.parse(r.dados_json || '{}') : (r.dados_json || {}),
      }));
      return res.status(200).json(briefings);
    } catch (err: any) {
      console.error('Erro ao listar briefings:', err);
      return res.status(500).json({ erro: 'Erro interno ao carregar briefings' });
    }
  }

  if (req.method === 'POST') {
    try {
      const payload = req.body || {};
      const clienteNome = payload.nome_noivos || payload.cliente || payload.nome_casal || 'Casal de Noivos';
      const dataCasamento = payload.data_casamento || null;

      if (!payload.nome_noivos && !payload.cliente) {
        return res.status(422).json({ erro: 'O nome do casal / cliente é obrigatório.' });
      }

      const id = generateId();
      const dadosJsonStr = JSON.stringify(payload);

      await query(
        'INSERT INTO briefings_resposta (id, cliente_nome, data_casamento, dados_json, status) VALUES ($1, $2, $3, $4, $5)',
        [id, clienteNome, dataCasamento, dadosJsonStr, 'novo']
      );

      return res.status(201).json({
        ok: true,
        id,
        mensagem: 'Briefing logístico enviado com sucesso!',
      });
    } catch (err: any) {
      console.error('Erro ao salvar briefing:', err);
      return res.status(500).json({ erro: 'Erro ao processar envio do briefing logístico: ' + err.message });
    }
  }

  return res.status(405).json({ erro: 'Método não permitido' });
}
