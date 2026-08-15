import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth, generateId } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  const method = req.method;

  if (method === 'GET') {
    const rows = await query(`
      SELECT *,
        CASE
          WHEN COALESCE(valor_pago, 0) >= valor THEN 'pago'
          WHEN COALESCE(valor_pago, 0) > 0 THEN 'pago_parcial'
          WHEN vencimento < CURRENT_DATE AND status NOT IN ('pago','cancelado') THEN 'atrasado'
          ELSE status
        END AS status
      FROM lancamentos
      ORDER BY COALESCE(data_pagamento, vencimento) DESC, id DESC
    `);
    return res.status(200).json(rows);
  }

  if (method === 'POST') {
    const d = req.body || {};
    if (!d.descricao || !d.valor || !d.vencimento) {
      return res.status(422).json({ erro: 'Descrição, valor e vencimento são obrigatórios' });
    }

    if (d.asaas_id) {
      const existing = await queryOne('SELECT id FROM lancamentos WHERE asaas_id = $1 LIMIT 1', [d.asaas_id]);
      if (existing) {
        return res.status(409).json({ erro: 'Esta cobrança do Asaas já foi importada anteriormente.' });
      }
    }

    const modalidade = d.modalidade || 'avista';

    if (modalidade === 'parcelado') {
      const total = Math.max(2, Math.min(120, parseInt(d.total_parcelas || 2)));
      const valorParcela = Math.round(((parseFloat(d.valor) / total) + Number.EPSILON) * 100) / 100;
      const paiId = generateId();
      let vencBase = new Date(d.vencimento);

      await insertLancamento(paiId, d, valorParcela, vencBase.toISOString().split('T')[0], null, 1, total);

      for (let i = 2; i <= total; i++) {
        vencBase.setDate(vencBase.getDate() + 30);
        await insertLancamento(generateId(), d, valorParcela, vencBase.toISOString().split('T')[0], paiId, i, total);
      }
    } else if (modalidade === 'recorrente') {
      const freq = d.frequencia || 'mensal';
      let venc = new Date(d.vencimento);
      const limite = new Date();
      limite.setMonth(limite.getMonth() + 12);
      let paiId: string | null = null;

      while (venc <= limite) {
        const id = generateId();
        const dateStr = venc.toISOString().split('T')[0];
        await insertLancamento(id, d, parseFloat(d.valor), dateStr, paiId);
        if (!paiId) paiId = id;

        if (freq === 'semanal') venc.setDate(venc.getDate() + 7);
        else if (freq === 'anual') venc.setFullYear(venc.getFullYear() + 1);
        else venc.setMonth(venc.getMonth() + 1);
      }
    } else {
      await insertLancamento(generateId(), d, parseFloat(d.valor), d.vencimento);
    }

    return res.status(201).json({ ok: true });
  }

  if (method === 'PUT') {
    const d = req.body || {};
    if (!d.id) return res.status(422).json({ erro: 'ID obrigatório' });

    const old = await queryOne('SELECT * FROM lancamentos WHERE id = $1 LIMIT 1', [d.id]);
    if (old && Number(old.conciliado) === 1) {
      if (
        old.conta_id !== d.conta_id ||
        Math.round(parseFloat(old.valor || 0) * 100) !== Math.round(parseFloat(d.valor || 0) * 100) ||
        old.vencimento !== d.vencimento ||
        old.tipo !== d.tipo ||
        old.descricao !== d.descricao
      ) {
        return res.status(422).json({ erro: 'Lançamentos conciliados não permitem alteração de dados sensíveis de pagamento.' });
      }
    }

    const valorTotal = parseFloat(d.valor);
    const valorPago = d.valor_pago !== undefined ? parseFloat(d.valor_pago) : (old ? parseFloat(old.valor_pago || 0) : 0);
    let dataPagamento = old?.data_pagamento || null;

    if (valorPago >= valorTotal) {
      dataPagamento = old?.data_pagamento || new Date().toISOString().split('T')[0];
    } else if (valorPago === 0) {
      dataPagamento = null;
    }

    await query(
      `UPDATE lancamentos SET 
        tipo = $1, descricao = $2, valor = $3, categoria = $4, cliente_fornecedor = $5,
        vencimento = $6, modalidade = $7, forma_pagamento = $8, observacao = $9,
        status = $10, valor_pago = $11, data_pagamento = $12
       WHERE id = $13`,
      [
        d.tipo, d.descricao, valorTotal, d.categoria || 'outros',
        d.cliente_fornecedor || null, d.vencimento, d.modalidade || 'avista',
        d.forma_pagamento || null, d.observacao || null,
        d.status || (valorPago >= valorTotal ? 'pago' : 'pendente'),
        valorPago, dataPagamento, d.id
      ]
    );

    return res.status(200).json({ ok: true });
  }

  if (method === 'DELETE') {
    const body = req.body || {};
    const ids = Array.isArray(body.ids) ? body.ids : (req.query.id ? [req.query.id] : []);
    if (ids.length === 0) return res.status(422).json({ erro: 'ID obrigatório' });

    await query('DELETE FROM lancamentos WHERE id = ANY($1::text[])', [ids]);
    return res.status(200).json({ ok: true });
  }

  return res.status(405).json({ erro: 'Método não permitido' });
});

async function insertLancamento(
  id: string,
  d: any,
  valor: number,
  vencimento: string,
  paiId: string | null = null,
  parcelaAtual = 1,
  totalParcelas: number | null = null
) {
  const status = d.status || 'pendente';
  const valorPago = d.valor_pago !== undefined ? parseFloat(d.valor_pago) : 0;
  let dataPagamento = d.data_pagamento || null;

  if (valorPago > 0 && !dataPagamento) {
    dataPagamento = (status === 'pago' || valorPago >= valor) ? vencimento : new Date().toISOString().split('T')[0];
  }

  await query(
    `INSERT INTO lancamentos (
      id, tipo, descricao, valor, valor_pago, categoria, cliente_fornecedor,
      vencimento, status, modalidade, total_parcelas, parcela_atual, lancamento_pai_id,
      frequencia, data_termino, observacao, forma_pagamento, conta_id, data_pagamento, conciliado
    ) VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15,$16,$17,$18,$19,$20)`,
    [
      id, d.tipo, d.descricao, valor, valorPago, d.categoria || 'outros',
      d.cliente_fornecedor || null, vencimento, status, d.modalidade || 'avista',
      totalParcelas, parcelaAtual, paiId, d.frequencia || null, d.data_termino || null,
      d.observacao || null, d.forma_pagamento || null, d.conta_id || null, dataPagamento,
      d.conciliado || 0
    ]
  );
}
