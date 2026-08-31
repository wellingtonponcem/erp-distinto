import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth, requireAdmin } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';
import { asaasService } from '@/lib/asaas';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  const method = req.method;

  // Garantir tabela configuracao_empresa e suas colunas no MySQL da Hostinger
  try {
    await query(`
      CREATE TABLE IF NOT EXISTS configuracao_empresa (
        id VARCHAR(64) PRIMARY KEY,
        asaas_api_key TEXT,
        asaas_mode VARCHAR(32) DEFAULT 'prod',
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);
  } catch (e) {}

  try { await query(`ALTER TABLE configuracao_empresa ADD COLUMN asaas_api_key TEXT;`); } catch (e) {}
  try { await query(`ALTER TABLE configuracao_empresa ADD COLUMN asaas_mode VARCHAR(32) DEFAULT 'prod';`); } catch (e) {}

  if (method === 'GET') {
    try {
      const config = await queryOne<{ asaas_api_key?: string; asaas_mode?: string }>(
        "SELECT asaas_api_key, asaas_mode FROM configuracao_empresa ORDER BY (id = 'principal') DESC LIMIT 1"
      );

      const rawKey = config?.asaas_api_key || process.env.ASAAS_API_KEY || '';
      const mode = config?.asaas_mode || process.env.ASAAS_MODE || 'prod';

      // Mascarar a chave para exibição segura no frontend
      const maskedKey = rawKey && rawKey.length > 10
        ? `${rawKey.substring(0, 10)}...${rawKey.substring(rawKey.length - 4)}`
        : (rawKey ? '****************' : '');

      return res.status(200).json({
        configured: Boolean(rawKey),
        maskedKey,
        mode,
      });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'POST') {
    if (user.nivel !== 1) {
      return res.status(403).json({ erro: 'Acesso negado: permissão de administrador requerida.' });
    }
    try {
      const { apiKey, mode } = req.body || {};

      if (!apiKey) {
        return res.status(422).json({ erro: 'Chave de API do Asaas é obrigatória' });
      }

      const cleanKey = apiKey.trim();
      const cleanMode = (mode || 'prod').trim();

      // Garantir atualização limpa e persistente no MySQL Hostinger sem depender de nome_empresa
      try {
        await query("DELETE FROM configuracao_empresa WHERE id = 'principal'");
      } catch (e) {}

      await query(
        "INSERT INTO configuracao_empresa (id, asaas_api_key, asaas_mode) VALUES ('principal', $1, $2)",
        [cleanKey, cleanMode]
      );

      // Atualizar a chave diretamente na memória do serviço Asaas
      asaasService.setApiKey(cleanKey, cleanMode);

      // Sincronizar em background no MySQL Hostinger
      asaasService.sincronizarComBancoDados().catch((err) => {
        console.error('Erro na sincronizacao automatica em background:', err);
      });

      return res.status(200).json({
        ok: true,
        mensagem: 'Chave de API do Asaas salva com sucesso no banco MySQL da Hostinger! Sincronização iniciada.'
      });
    } catch (err: any) {
      console.error('Erro ao salvar configuracao Asaas:', err);
      return res.status(500).json({ erro: `Erro ao salvar chave do Asaas: ${err.message}` });
    }
  }

  return res.status(405).json({ erro: 'Método não permitido' });
});
