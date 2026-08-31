import { NextApiRequest, NextApiResponse } from 'next';
import { AsaasService } from '@/lib/asaas';
import { queryOne } from '@/lib/db';
import { requireAuth } from '@/lib/helpers';

async function handler(req: NextApiRequest, res: NextApiResponse) {
  try {
    const asaas = new AsaasService();
    await asaas.initFromDb();

    if (!asaas.isConfigured()) {
      return res.status(200).json({
        ok: false,
        configurado: false,
        saldo: 0,
        mensagem: 'Chave do Asaas não configurada'
      });
    }

    const { saldo, cobrancas } = await asaas.getBalanceAndExtract();

    return res.status(200).json({
      ok: true,
      configurado: true,
      saldo,
      cobrancas
    });
  } catch (err: any) {
    console.error('Erro ao buscar saldo do Asaas:', err);
    return res.status(200).json({
      ok: false,
      configurado: true,
      saldo: 0,
      erro: err.message
    });
  }
}

export default requireAuth(async (req, res, _user) => {
  await handler(req, res);
});
