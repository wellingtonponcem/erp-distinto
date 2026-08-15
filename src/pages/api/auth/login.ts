import { NextApiRequest, NextApiResponse } from 'next';
import bcrypt from 'bcryptjs';
import { queryOne } from '@/lib/db';
import { generateToken, setAuthCookie } from '@/lib/auth';

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const { email, senha } = req.body || {};
    const emailClean = (email || '').trim().toLowerCase();
    const senhaClean = (senha || '').trim();

    if (!emailClean || !senhaClean) {
      return res.status(422).json({ erro: 'E-mail e senha são obrigatórios' });
    }

    const user = await queryOne(
      'SELECT id, nome, email, senha, nivel FROM users WHERE LOWER(TRIM(email)) = $1 LIMIT 1',
      [emailClean]
    );

    if (!user) {
      return res.status(401).json({ erro: 'Credenciais inválidas' });
    }

    // Suporta hash bcrypt nativo ou comparação direta se senha legado
    let isMatch = false;
    if (user.senha.startsWith('$2a$') || user.senha.startsWith('$2b$') || user.senha.startsWith('$2y$')) {
      // Ajustar prefixo $2y$ (PHP bcrypt) para $2a$ se necessário
      const formattedHash = user.senha.replace(/^\$2y\$/, '$2a$');
      isMatch = await bcrypt.compare(senhaClean, formattedHash);
    } else {
      isMatch = (user.senha === senhaClean);
    }

    if (!isMatch) {
      return res.status(401).json({ erro: 'Credenciais inválidas' });
    }

    const token = generateToken(user);
    setAuthCookie(res, token);

    return res.status(200).json({
      ok: true,
      token,
      user: {
        id: user.id,
        nome: user.nome,
        email: user.email,
        nivel: user.nivel
      }
    });
  } catch (err: any) {
    console.error('Login error:', err);
    return res.status(500).json({ erro: 'Erro interno ao autenticar: ' + err.message });
  }
}
