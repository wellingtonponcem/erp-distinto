import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { asaasService } from '@/lib/asaas';
import { query } from '@/lib/db';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse) => {
  if (req.method !== 'GET') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    // Tenta buscar cobranças em tempo real na API v3 do Asaas
    if (asaasService.isConfigured()) {
      const cobrançasAsaas = await asaasService.listarCobrancas({ limit: 50 });
      return res.status(200).json({ ok: true, origem: 'asaas_api', dados: cobrançasAsaas.data || [] });
    }
  } catch (err: any) {
    console.warn('Falha na API oficial do Asaas, buscando lançamentos com tag Asaas local:', err.message);
  }

  // Fallback: Busca lançamentos no banco local marcados como Asaas
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
