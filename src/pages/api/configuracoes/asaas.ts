import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  const method = req.method;

  if (method === 'GET') {
    const config = await queryOne<{ asaas_api_key?: string; asaas_mode?: string }>(
      "SELECT asaas_api_key, asaas_mode FROM configuracao_empresa WHERE id = 'principal' LIMIT 1"
    );

    const rawKey = config?.asaas_api_key || process.env.ASAAS_API_KEY || '';
    const mode = config?.asaas_mode || process.env.ASAAS_MODE || 'test';

    // Mascarar a chave para exibição segura no frontend (ex: $aact_YTU5...****)
    const maskedKey = rawKey
      ? `${rawKey.substring(0, 10)}...${rawKey.substring(rawKey.length - 4)}`
      : '';

    return res.status(200).json({
      configured: Boolean(rawKey),
      maskedKey,
      mode,
    });
  }

  if (method === 'POST') {
    const { apiKey, mode } = req.body || {};

    if (!apiKey) {
      return res.status(422).json({ erro: 'Chave de API do Asaas é obrigatória' });
    }

    const cleanKey = apiKey.trim();
    const cleanMode = (mode || 'prod').trim();

    // Garante que a linha 'principal' exista na tabela configuracao_empresa
    const existe = await queryOne("SELECT id FROM configuracao_empresa WHERE id = 'principal'");

    if (!existe) {
      await query(
        "INSERT INTO configuracao_empresa (id, nome_empresa, asaas_api_key, asaas_mode) VALUES ('principal', 'ERP Distinto', $1, $2)",
        [cleanKey, cleanMode]
      );
    } else {
      await query(
        "UPDATE configuracao_empresa SET asaas_api_key = $1, asaas_mode = $2 WHERE id = 'principal'",
        [cleanKey, cleanMode]
      );
    }

    return res.status(200).json({ ok: true, mensagem: 'Chave de API do Asaas salva com segurança no banco de dados!' });
  }

  return res.status(405).json({ erro: 'Método não permitido' });
});
