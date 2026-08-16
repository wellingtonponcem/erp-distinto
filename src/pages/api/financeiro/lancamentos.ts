import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth, generateId } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';
import { asaasService } from '@/lib/asaas';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  const method = req.method;

  if (method === 'GET') {
    // 1. Busca lançamentos do banco local (Neon PostgreSQL)
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

    // 2. Tenta mesclar transações em tempo real da API do Asaas se estiver configurada
    let asaasRows: any[] = [];
    try {
      if (await asaasService.isConfiguredAsync()) {
        const asaasRes = await asaasService.listarCobrancas({ limit: 50 });
        const cobrancasAsaas = asaasRes.data || [];

        // Filtra para incluir apenas cobranças que ainda não foram salvas manualmente no banco local
        const asaasIdsLocais = new Set(rows.map((r: any) => r.asaas_id || r.asaas_payment_id).filter(Boolean));

        asaasRows = cobrancasAsaas
          .filter((c: any) => !asaasIdsLocais.has(c.id))
          .map((c: any) => {
            const isSaida = c.status === 'REFUNDED' || c.status === 'REFUND_REQUESTED' || c.value < 0;
            return {
              id: `asaas_${c.id}`,
              tipo: isSaida ? 'pagar' : 'receber',
              descricao: c.description || `Cobrança Asaas ${c.billingType || 'PIX'}`,
              valor: Math.abs(parseFloat(c.value || 0)),
              valor_pago: (c.status === 'RECEIVED' || c.status === 'CONFIRMED' || c.status === 'pago') ? Math.abs(parseFloat(c.netValue || c.value || 0)) : 0,
              categoria: 'Asaas',
              cliente_fornecedor: c.customerName || 'Cliente Asaas',
              vencimento: c.dueDate,
              data_pagamento: c.paymentDate || c.clientPaymentDate || null,
              status: isSaida ? 'saida' : (c.status === 'RECEIVED' || c.status === 'CONFIRMED' ? 'pago' : c.status === 'OVERDUE' ? 'atrasado' : 'pendente'),
              conciliado: 1,
              asaas_id: c.id,
              conta_id: 'asaas',
              forma_pagamento: (c.billingType || 'PIX').toLowerCase(),
            };
          });
      }
    } catch (e: any) {
      console.warn('Falha ao mesclar Asaas no extrato geral:', e.message);
    }

    const extratoConsolidado = [...rows, ...asaasRows].sort((a, b) => {
      const dataA = new Date(a.data_pagamento || a.vencimento || 0).getTime();
      const dataB = new Date(b.data_pagamento || b.vencimento || 0).getTime();
      return dataB - dataA;
    });

    return res.status(200).json(extratoConsolidado);
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

    // Lançamento virtual em tempo real do Asaas
    if (d.id.startsWith('asaas_')) {
      const realAsaasId = d.id.replace('asaas_', '');
      const idNovo = generateId();
      await query(
        `INSERT INTO lancamentos (
          id, tipo, descricao, valor, valor_pago, categoria, cliente_fornecedor,
          vencimento, data_pagamento, status, conciliado, asaas_id, conta_id
        ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, 1, $11, 'asaas')`,
        [
          idNovo,
          d.tipo || 'receber',
          d.descricao,
          parseFloat(d.valor || 0),
          parseFloat(d.valor_pago || d.valor || 0),
          d.categoria || 'Asaas',
          d.cliente_fornecedor || 'Cliente Asaas',
          d.vencimento,
          d.data_pagamento || d.vencimento,
          d.status || 'pago',
          realAsaasId,
        ]
      );
      return res.status(200).json({ ok: true });
    }

    const old = await queryOne('SELECT * FROM lancamentos WHERE id = $1 LIMIT 1', [d.id]);
    if (!old) return res.status(404).json({ erro: 'Lançamento não encontrado' });

    // Regra de Segurança: Se for conciliado (OFX ou Asaas), permite editar apenas Descrição e Categoria
    if (Number(old.conciliado) === 1 || old.ofx_fitid || old.asaas_id) {
      await query(
        `UPDATE lancamentos SET descricao = $1, categoria = $2 WHERE id = $3`,
        [d.descricao, d.categoria || old.categoria || 'Outros', d.id]
      );
      return res.status(200).json({ ok: true, mensagem: 'Lançamento conciliado: alterado apenas descrição e categoria.' });
    }

    const valorTotal = parseFloat(d.valor);
    const valorPago = d.valor_pago !== undefined ? parseFloat(d.valor_pago) : parseFloat(old.valor_pago || 0);
    let dataPagamento = old.data_pagamento || null;

    if (valorPago >= valorTotal) {
      dataPagamento = old.data_pagamento || new Date().toISOString().split('T')[0];
    } else if (valorPago === 0) {
      dataPagamento = null;
    }

    await query(
      `UPDATE lancamentos SET 
        tipo = $1, descricao = $2, valor = $3, categoria = $4, cliente_fornecedor = $5,
        vencimento = $6, modalidade = $7, forma_pagamento = $8, observacao = $9,
        status = $10, valor_pago = $11, data_pagamento = $12, conta_id = $13
       WHERE id = $14`,
      [
        d.tipo || old.tipo,
        d.descricao,
        valorTotal,
        d.categoria || 'Outros',
        d.cliente_fornecedor || null,
        d.vencimento,
        d.modalidade || 'avista',
        d.forma_pagamento || null,
        d.observacao || null,
        d.status || (valorPago >= valorTotal ? 'pago' : 'pendente'),
        valorPago,
        dataPagamento,
        d.conta_id || null,
        d.id
      ]
    );

    return res.status(200).json({ ok: true });
  }

  if (method === 'DELETE') {
    const body = req.body || {};
    const ids = Array.isArray(body.ids) ? body.ids : (req.query.id ? [req.query.id] : []);
    if (ids.length === 0) return res.status(422).json({ erro: 'ID obrigatório' });

    // Bloquear exclusão de itens virtuais do Asaas
    if (ids.some((id: string) => id.startsWith('asaas_'))) {
      return res.status(422).json({ erro: 'Cobranças sincronizadas em tempo real do Asaas não podem ser excluídas.' });
    }

    // Bloquear exclusão de itens conciliados gravados no banco
    const conciliados = await query(
      `SELECT id FROM lancamentos WHERE id = ANY($1::text[]) AND (conciliado = 1 OR ofx_fitid IS NOT NULL OR asaas_id IS NOT NULL)`,
      [ids]
    );
    if (conciliados.length > 0) {
      return res.status(422).json({ erro: 'Lançamentos conciliados (OFX / Asaas) não podem ser excluídos.' });
    }

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
