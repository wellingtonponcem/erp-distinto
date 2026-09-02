import type { NextApiRequest, NextApiResponse } from 'next';
import crypto from 'crypto';
import { queryOne } from '@/lib/db';

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  const token = (req.query.token || req.body?.token || '').toString().trim();
  if (!token) return res.status(400).json({ valido: false, erro: 'Token ausente' });

  try {
    const tokenHash = crypto.createHash('sha256').update(token).digest('hex');
    const row = await queryOne(
      `SELECT prt.id FROM password_reset_tokens prt WHERE prt.token_hash = $1 AND prt.used_at IS NULL AND prt.expires_at > NOW() LIMIT 1`,
      [tokenHash]
    );
    return res.status(200).json({ valido: !!row });
  } catch (err: any) {
    console.error('verificar-token error:', err?.message);
    return res.status(200).json({ valido: false, detail: err?.message });
  }
}
