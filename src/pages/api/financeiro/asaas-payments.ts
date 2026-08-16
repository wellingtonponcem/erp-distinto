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
      // 1. Tentar sincronizar os dados do Asaas diretamente com a tabela lancamentos do MySQL Hostinger
      try {
        await asaasService.sincronizarComBancoDados();
      } catch (err: any) {
        console.warn('Erro ao sincronizar Asaas no banco, prosseguindo com consulta local:', err.message);
      }
    }
  } catch (err: any) {
    console.warn('Falha na verificacao da chave Asaas:', err.message);
  }

  // 2. Retornar todos os lançamentos sincronizados no MySQL Hostinger
  try {
    const locais = await query(`
      SELECT 
        id,
        tipo as tipoMovimento,
        cliente_fornecedor as customerName,
        descricao,
        forma_pagamento as billingType,
        vencimento as dueDate,
        data_pagamento as paymentDate,
        valor as value,
        valor_pago as netValue,
        status,
        asaas_id
      FROM lancamentos 
      WHERE asaas_id IS NOT NULL 
         OR conta_id = 'asaas'
         OR categoria = 'Asaas'
      ORDER BY vencimento DESC
    `);

    const formatados = locais.map((l: any) => ({
      id: l.asaas_id || l.id,
      type: 'cobranca',
      tipoMovimento: l.tipoMovimento === 'pagar' ? 'saida' : 'entrada',
      customerName: l.customerName || l.descricao || 'Cliente Asaas',
      billingType: l.billingType || 'PIX',
      dueDate: l.dueDate,
      paymentDate: l.paymentDate,
      value: parseFloat(l.value || 0),
      netValue: parseFloat(l.netValue || l.value || 0),
      status: (l.status || 'PENDING').toUpperCase(),
      invoiceUrl: null,
    }));

    return res.status(200).json({ ok: true, origem: 'banco_hostinger_mysql', dados: formatados });
  } catch (e: any) {
    return res.status(500).json({ ok: false, erro: e.message });
  }
});
