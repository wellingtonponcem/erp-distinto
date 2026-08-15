import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { asaasService } from '@/lib/asaas';
import { query } from '@/lib/db';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse) => {
  if (req.method !== 'GET') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const configurado = await asaasService.isConfiguredAsync();

    if (configurado) {
      // 1. Busca cobranças (Payments) diretamente na API do Asaas
      const cobrancasRes = await asaasService.listarCobrancas({ limit: 50 });
      const cobrancasData = cobrancasRes.data || [];

      // 2. Busca movimentações do extrato financeiro (Financial Transactions) na API do Asaas
      let extratoData: any[] = [];
      try {
        const extratoRes = await asaasService.listarExtratoFinanceiro({ limit: 50 });
        extratoData = extratoRes.data || [];
      } catch (err: any) {
        console.warn('Extrato estendido requer escopo, exibindo cobranças:', err.message);
      }

      // Combina e formata os dados classificando saídas exclusivamente como SAÍDA
      const listaCombinada = [
        ...cobrancasData.map((c: any) => {
          const isSaida = c.status === 'REFUNDED' || c.status === 'REFUND_REQUESTED' || c.value < 0;
          return {
            id: c.id,
            type: 'cobranca',
            tipoMovimento: isSaida ? 'saida' : 'entrada',
            customerName: c.customerName || c.description || 'Cliente Asaas',
            billingType: c.billingType || 'PIX',
            dueDate: c.dueDate,
            paymentDate: c.paymentDate || c.clientPaymentDate,
            value: Math.abs(parseFloat(c.value || 0)),
            netValue: Math.abs(parseFloat(c.netValue || c.value || 0)),
            status: isSaida ? 'SAIDA' : (c.status || 'PENDING'),
            invoiceUrl: c.invoiceUrl || c.bankSlipUrl,
          };
        }),
        ...extratoData.map((e: any) => {
          const val = parseFloat(e.value || 0);
          const isSaida = val < 0 || e.type?.includes('DEBIT') || e.type?.includes('TRANSFER') || e.type?.includes('FEE');
          return {
            id: e.id,
            type: 'extrato',
            tipoMovimento: isSaida ? 'saida' : 'entrada',
            customerName: e.description || e.type || 'Movimentação Asaas',
            billingType: e.type || 'TRANSFER',
            dueDate: e.date,
            paymentDate: e.date,
            value: Math.abs(val),
            netValue: Math.abs(val),
            status: isSaida ? 'SAIDA' : 'RECEIVED',
            invoiceUrl: null,
          };
        }),
      ];

      return res.status(200).json({ ok: true, origem: 'asaas_api', dados: listaCombinada });
    }
  } catch (err: any) {
    console.warn('Falha ao conectar à API do Asaas:', err.message);
  }

  // Fallback para lançamentos armazenados localmente
  try {
    const locais = await query(`
      SELECT * FROM lancamentos 
      WHERE asaas_payment_id IS NOT NULL 
         OR categoria ILIKE '%asaas%' 
         OR cliente_fornecedor ILIKE '%asaas%' 
         OR forma_pagamento ILIKE '%asaas%' 
         OR forma_pagamento IN ('pix', 'boleto', 'cartao')
      ORDER BY vencimento DESC
    `);
    return res.status(200).json({ ok: true, origem: 'local', dados: locais });
  } catch (e: any) {
    return res.status(500).json({ ok: false, erro: e.message });
  }
});
