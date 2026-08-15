import { NextApiRequest, NextApiResponse } from 'next';
import { query, queryOne } from '@/lib/db';
import { generateId } from '@/lib/helpers';

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
    const valor = parseFloat(payment.value || 0);
    const valorPago = parseFloat(payment.netValue || payment.value || 0);
    const dataPagamento = payment.paymentDate || payment.clientPaymentDate || new Date().toISOString().split('T')[0];
    const vencimento = payment.dueDate || dataPagamento;
    const clienteNome = payment.customerName || payment.description || 'Cliente Asaas';
    const descricao = payment.description || `Recebimento Asaas ${payment.billingType || 'PIX'}`;
    const formaPagamento = (payment.billingType || 'PIX').toLowerCase();

    // 1. Verifica se já existe um lançamento com esse asaas_payment_id ou asaas_id
    const lancamentoExistente = await queryOne(
      'SELECT id FROM lancamentos WHERE asaas_payment_id = $1 OR asaas_id = $1',
      [asaasId]
    );

    if (event === 'PAYMENT_RECEIVED' || event === 'PAYMENT_CONFIRMED') {
      if (lancamentoExistente) {
        await query(
          `UPDATE lancamentos SET 
            status = 'pago', 
            valor_pago = $1, 
            data_pagamento = $2, 
            conciliado = 1 
           WHERE id = $3`,
          [valorPago, dataPagamento, lancamentoExistente.id]
        );
      } else {
        // Inserção automática de novíssimas movimentações recebidas pelo Asaas
        const novoId = generateId();
        await query(
          `INSERT INTO lancamentos (
            id, tipo, descricao, valor, valor_pago, categoria, cliente_fornecedor, 
            vencimento, data_pagamento, status, conciliado, asaas_payment_id, forma_pagamento
          ) VALUES ($1, 'receber', $2, $3, $4, 'Asaas', $5, $6, $7, 'pago', 1, $8, $9)`,
          [novoId, descricao, valor, valorPago, clienteNome, vencimento, dataPagamento, asaasId, formaPagamento]
        );
      }
    } else if (event === 'PAYMENT_OVERDUE') {
      if (lancamentoExistente) {
        await query(
          "UPDATE lancamentos SET status = 'atrasado' WHERE id = $1 AND status != 'pago'",
          [lancamentoExistente.id]
        );
      } else {
        const novoId = generateId();
        await query(
          `INSERT INTO lancamentos (
            id, tipo, descricao, valor, valor_pago, categoria, cliente_fornecedor, 
            vencimento, status, conciliado, asaas_payment_id, forma_pagamento
          ) VALUES ($1, 'receber', $2, $3, 0, 'Asaas', $4, $5, 'atrasado', 0, $6, $7)`,
          [novoId, descricao, valor, clienteNome, vencimento, asaasId, formaPagamento]
        );
      }
    } else if (event === 'PAYMENT_DELETED' || event === 'PAYMENT_REFUNDED') {
      if (lancamentoExistente) {
        await query(
          "UPDATE lancamentos SET status = 'cancelado', valor_pago = 0 WHERE id = $1",
          [lancamentoExistente.id]
        );
      }
    }

    return res.status(200).json({ received: true });
  } catch (err: any) {
    console.error('Webhook Asaas error:', err);
    return res.status(500).json({ erro: err.message });
  }
}
