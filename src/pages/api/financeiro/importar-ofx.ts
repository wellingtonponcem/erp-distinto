import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth, generateId } from '@/lib/helpers';
import { query } from '@/lib/db';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const { transacoes, contaId } = req.body || {};

    if (!Array.isArray(transacoes) || transacoes.length === 0) {
      return res.status(422).json({ erro: 'Nenhuma transação selecionada para importar.' });
    }

    let inseridos = 0;

    for (const t of transacoes) {
      const id = generateId();
      const valorNum = parseFloat(t.valor || 0);

      await query(
        `INSERT INTO lancamentos (
          id, tipo, descricao, valor, valor_pago, categoria, cliente_fornecedor,
          vencimento, data_pagamento, status, conciliado, ofx_fitid, conta_id
        ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, 'pago', 1, $10, $11)`,
        [
          id,
          t.tipo || 'receber',
          t.descricao || 'Lançamento OFX',
          valorNum,
          valorNum,
          t.categoria || 'Outros',
          t.cliente_fornecedor || 'Extrato Bancário OFX',
          t.data,
          t.data,
          t.fitid || null,
          contaId || null,
        ]
      );

      inseridos++;
    }

    return res.status(200).json({ ok: true, inseridos });
  } catch (err: any) {
    return res.status(500).json({ erro: err.message });
  }
});
