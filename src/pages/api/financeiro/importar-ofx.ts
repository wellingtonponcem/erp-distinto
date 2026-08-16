import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth, generateId } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const { transacoes, contaId } = req.body || {};

    if (!Array.isArray(transacoes) || transacoes.length === 0) {
      return res.status(422).json({ erro: 'Nenhuma transação selecionada para importar.' });
    }

    // Garantir estrutura da coluna ofx_fitid
    try {
      await query(`ALTER TABLE lancamentos ADD COLUMN IF NOT EXISTS ofx_fitid TEXT`);
    } catch (e) {}

    // Validar se a conta existe no banco
    let validContaId: string | null = null;
    if (contaId) {
      try {
        const cExists = await queryOne('SELECT id FROM contas WHERE id = $1 LIMIT 1', [contaId]);
        if (cExists) validContaId = cExists.id;
      } catch (e) {}
    }

    let inseridos = 0;

    for (const t of transacoes) {
      const id = generateId();
      const valorNum = parseFloat(t.valor || 0);
      const fitid = t.fitid || null;

      // Evitar duplicidade caso a transação já tenha sido gravada por FITID
      if (fitid) {
        try {
          const ex = await queryOne('SELECT id FROM lancamentos WHERE ofx_fitid = $1 LIMIT 1', [fitid]);
          if (ex) continue;
        } catch (e) {}
      }

      // Checar duplicatas de forma imune a erros de tipo no PostgreSQL
      try {
        const dupMatch = await queryOne(
          `SELECT id FROM lancamentos WHERE vencimento::text LIKE $1 || '%' AND LOWER(descricao) = LOWER($2) LIMIT 1`,
          [t.data, t.descricao || 'Lançamento OFX']
        );
        if (dupMatch) continue;
      } catch (e) {}

      try {
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
            fitid,
            validContaId,
          ]
        );
        inseridos++;
      } catch (insertErr: any) {
        console.error('Erro ao inserir item OFX:', insertErr.message);
      }
    }

    return res.status(200).json({ ok: true, inseridos });
  } catch (err: any) {
    console.error('Erro no importar-ofx:', err);
    return res.status(500).json({ erro: `Erro ao importar transações: ${err.message}` });
  }
});
