import { NextApiRequest, NextApiResponse } from 'next';
import { query, queryOne } from '@/lib/db';

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const payload = req.body || {};
    const event = payload.event;
    const payment = payload.payment;

    if (!event || !payment) {
      return res.status(400).json({ erro: 'Payload inválido' });
    }

    const asaasId = payment.id;
    const valorPago = parseFloat(payment.value || 0);
    const dataPagamento = payment.paymentDate || payment.clientPaymentDate || new Date().toISOString().split('T')[0];

    if (event === 'PAYMENT_RECEIVED' || event === 'PAYMENT_CONFIRMED') {
      await query(
        `UPDATE lancamentos SET 
          status = 'pago', 
          valor_pago = $1, 
          data_pagamento = $2, 
          conciliado = 1 
         WHERE asaas_id = $3`,
        [valorPago, dataPagamento, asaasId]
      );
    } else if (event === 'PAYMENT_OVERDUE') {
      await query(
        "UPDATE lancamentos SET status = 'atrasado' WHERE asaas_id = $1 AND status != 'pago'",
        [asaasId]
      );
    } else if (event === 'PAYMENT_DELETED' || event === 'PAYMENT_REFUNDED') {
      await query(
        "UPDATE lancamentos SET status = 'cancelado', valor_pago = 0 WHERE asaas_id = $1",
        [asaasId]
      );
    }

    return res.status(200).json({ received: true });
  } catch (err: any) {
    console.error('Webhook Asaas error:', err);
    return res.status(500).json({ erro: err.message });
  }
}
