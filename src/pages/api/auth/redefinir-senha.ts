import type { NextApiRequest, NextApiResponse } from 'next';
import crypto from 'crypto';
import bcrypt from 'bcryptjs';
import { query, queryOne } from '@/lib/db';

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  const token = (req.body?.token || '').toString().trim();
  const senha = (req.body?.senha || '').toString();
  const confirmarSenha = (req.body?.confirmarSenha || req.body?.confirmar_senha || '').toString();

  if (!token) {
    return res.status(400).json({ erro: 'Token ausente. Solicite um novo link.' });
  }
  if (!senha || senha.length < 8) {
    return res.status(422).json({ erro: 'A nova senha deve ter pelo menos 8 caracteres.' });
  }
  if (senha !== confirmarSenha) {
    return res.status(422).json({ erro: 'As senhas não coincidem.' });
  }

  try {
    const tokenHash = crypto.createHash('sha256').update(token).digest('hex');

    const row = await queryOne(
      `SELECT prt.id, prt.user_id, prt.expires_at, u.email
       FROM password_reset_tokens prt
       JOIN users u ON u.id = prt.user_id
       WHERE prt.token_hash = $1 AND prt.used_at IS NULL AND prt.expires_at > NOW()
       LIMIT 1`,
      [tokenHash]
    );

    if (!row) {
      return res.status(400).json({ erro: 'Link inválido ou expirado. Solicite uma nova redefinição.' });
    }

    const hash = await bcrypt.hash(senha, 10);

    // Atualiza senha no MySQL (compatível com login que aceita $2a/$2b/: src/pages/api/auth/login.ts:53)
    const result: any = await query('UPDATE users SET senha = $1 WHERE id = $2', [hash, row.user_id]);

    // mysql2 via src/lib/db retorna RowDataPacket[]; para UPDATE o driver retorna ResultSetHeader em array wrapper.
    // Como query() abstrai, verificamos via SELECT se a senha foi gravada
    const updated = await queryOne('SELECT senha FROM users WHERE id = $1', [row.user_id]);
    const senhaGravada = String((updated as any)?.senha || '');
    const confere = senhaGravada.startsWith('$2a$') || senhaGravada.startsWith('$2b$')
      ? await bcrypt.compare(senha, senhaGravada.replace(/^\$2y\$/, '$2a$'))
      : senhaGravada === senha;

    if (!confere) {
      return res.status(500).json({ erro: 'Não foi possível confirmar a nova senha. Tente novamente.' });
    }

    // Invalida todos os tokens pendentes do usuário
    await query('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = $1 AND used_at IS NULL', [
      row.user_id,
    ]);

    console.log(`RESET SUCESSO: email=${row.email} user_id=${row.user_id}`);

    return res.status(200).json({ ok: true, mensagem: 'Senha redefinida com sucesso. Faça login com a nova senha.' });
  } catch (err: any) {
    console.error('redefinir-senha error:', err);
    return res.status(500).json({ erro: 'Não foi possível redefinir a senha agora. Tente novamente.' });
  }
}
