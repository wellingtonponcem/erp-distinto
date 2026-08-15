import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { asaasService } from '@/lib/asaas';
import { query } from '@/lib/db';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse) => {
  if (req.method !== 'GET') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    if (asaasService.isConfigured()) {
      // 1. Busca cobranças (Payments)
      const cobrancasRes = await asaasService.listarCobrancas({ limit: 50 });
      const cobrancasData = cobrancasRes.data || [];

      // 2. Busca movimentações do extrato financeiro (Financial Transactions)
      let extratoData: any[] = [];
      try {
        const extratoRes = await asaasService.listarExtratoFinanceiro({ limit: 50 });
        extratoData = extratoRes.data || [];
      } catch (err: any) {
        console.warn('Extrato estendido requer escopo, exibindo cobranças...');
      }

      // Combina e formata os dados para exibição completa
      const listaCombinada = [
        ...cobrancasData.map((c: any) => ({
          id: c.id,
          type: 'cobranca',
          customerName: c.customerName || c.description || 'Cliente Asaas',
          billingType: c.billingType || 'PIX',
          dueDate: c.dueDate,
          paymentDate: c.paymentDate || c.clientPaymentDate,
          value: parseFloat(c.value || 0),
          netValue: parseFloat(c.netValue || c.value || 0),
          status: c.status || 'PENDING',
          invoiceUrl: c.invoiceUrl || c.bankSlipUrl,
        })),
        ...extratoData.map((e: any) => ({
          id: e.id,
          type: 'extrato',
          customerName: e.description || e.type || 'Movimentação Asaas',
          billingType: e.type || 'TRANSFER',
          dueDate: e.date,
          paymentDate: e.date,
          value: parseFloat(e.value || 0),
          netValue: parseFloat(e.value || 0),
          status: e.value >= 0 ? 'RECEIVED' : 'REFUNDED',
          invoiceUrl: null,
        })),
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
