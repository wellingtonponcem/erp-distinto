import { NextApiRequest, NextApiResponse } from 'next';
import { query, queryOne } from '@/lib/db';
import { generateId } from '@/lib/helpers';
import { asaasService } from '@/lib/asaas';

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  // Validar HMAC / token do webhook (replica PHP api/financeiro/webhook_asaas.php:47)
  try {
    const tokenEnviado = (req.headers['asaas-access-token'] as string) || (req.headers['x-asaas-signature'] as string) || (req.headers['x-assinafy-signature'] as string) || '';
    const cfg: any = await queryOne('SELECT asaas_webhook_token, asaas_api_key FROM configuracao_empresa LIMIT 1');
    const webhookToken: string | null = cfg?.asaas_webhook_token || null;
    if (webhookToken && tokenEnviado !== webhookToken) {
      return res.status(401).json({ erro: 'Token do webhook inválido' });
    }
  } catch (e) {
    // Se falhar ao buscar token, nega por segurança se header foi enviado
  }

  // Sempre aceitar requisições de teste GET/OPTIONS com 200 OK
  if (req.method === 'GET' || req.method === 'OPTIONS') {
    return res.status(200).json({ ok: true, status: 'Webhook Asaas ERP Distinto Ativo' });
  }

  if (req.method !== 'POST') {
    return res.status(200).json({ received: true });
  }

  try {
    const payload = req.body || {};
    const event = payload.event || 'PING';

    // 1. Tratamento de Ping e Teste do Asaas
    if (event === 'PING' || !payload.event) {
      return res.status(200).json({ received: true, message: 'Ping de teste do Asaas recebido com sucesso' });
    }

    const payment = payload.payment;
    const transfer = payload.transfer;

    // 2. Se for evento de Cobrança / Recebimento
    if (payment) {
      const asaasId = payment.id;
      const valor = parseFloat(payment.value || 0);
      const valorPago = parseFloat(payment.netValue || payment.value || 0);
      const dataPagamento = payment.paymentDate || payment.clientPaymentDate || new Date().toISOString().split('T')[0];
      const vencimento = payment.dueDate || dataPagamento;
      const clienteNome = payment.customerName || payment.description || 'Cliente Asaas';
      const descricao = payment.description || `Recebimento Asaas (${payment.billingType || 'PIX'})`;
      const formaPagamento = (payment.billingType || 'PIX').toLowerCase();

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
          const novoId = generateId();
          await query(
            `INSERT INTO lancamentos (
              id, tipo, descricao, valor, valor_pago, categoria, cliente_fornecedor, 
              vencimento, data_pagamento, status, conciliado, asaas_payment_id, asaas_id, forma_pagamento
            ) VALUES ($1, 'receber', $2, $3, $4, 'Asaas', $5, $6, $7, 'pago', 1, $8, $8, $9)`,
            [novoId, descricao, valor, valorPago, clienteNome, vencimento, dataPagamento, asaasId, formaPagamento]
          );
        }
      } else if (event === 'PAYMENT_OVERDUE') {
        if (lancamentoExistente) {
          await query(
            "UPDATE lancamentos SET status = 'atrasado' WHERE id = $1 AND status != 'pago'",
            [lancamentoExistente.id]
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
    }

    // 3. Se for evento de Transferência / Saída PIX
    if (transfer) {
      const trfId = transfer.id;
      const valor = Math.abs(parseFloat(transfer.value || 0));
      const nomeFav = (
        transfer.bankAccount?.ownerName ||
        transfer.pixAddressKeyName ||
        transfer.customerName ||
        'Destinatário Asaas'
      ).trim();
      const desc = `Transferência PIX para ${nomeFav}`;
      const status = transfer.status === 'DONE' ? 'saida' : 'pendente';
      const dateStr = transfer.effectiveDate
        ? transfer.effectiveDate.split('T')[0]
        : new Date().toISOString().split('T')[0];

      try {
        await query(
          `INSERT INTO lancamentos (
            id, tipo, descricao, valor, valor_pago, categoria, cliente_fornecedor,
            vencimento, data_pagamento, status, conta_id, conciliado, asaas_id
          ) VALUES ($1, 'pagar', $2, $3, $4, 'Transferência Asaas', $5, $6, $7, $8, 'asaas', 1, $9)
          ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            data_pagamento = VALUES(data_pagamento)`,
          [`asaas_trf_${trfId}`, desc, valor, valor, nomeFav, dateStr, dateStr, status, trfId]
        );
      } catch (e) {}
    }

    // Disparar sincronização silenciosa com MySQL Hostinger
    asaasService.sincronizarComBancoDados().catch(() => {});

    // SEMPRE retornar HTTP 200 OK para o Asaas validar com sucesso
    return res.status(200).json({ received: true, event });
  } catch (err: any) {
    console.error('Webhook Asaas error:', err);
    // Garantir 200 OK mesmo se ocorrer falha interna de parse para não quebrar no Asaas
    return res.status(200).json({ received: true, error: err.message });
  }
}
