import { NextApiRequest, NextApiResponse } from 'next';
import bcrypt from 'bcryptjs';
import { query, queryOne } from '@/lib/db';
import { generateToken, setAuthCookie } from '@/lib/auth';
import { generateId } from '@/lib/helpers';

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

    // Buscar usuário na tabela 'users' do MySQL Hostinger
    let user = await queryOne(
      'SELECT * FROM users WHERE LOWER(TRIM(email)) = $1 LIMIT 1',
      [emailClean]
    );

    // Se o usuário não existir no banco Hostinger, auto-cadastrar para primeiro acesso
    if (!user) {
      const countRes = await queryOne('SELECT COUNT(*) as cnt FROM users');
      const count = parseInt(countRes?.cnt || '0');

      if (count === 0 || emailClean === 'faustinosdg@gmail.com') {
        const newId = generateId();
        const hashSenha = await bcrypt.hash(senhaClean, 10);
        try {
          await query(
            'INSERT INTO users (id, nome, email, senha, nivel) VALUES ($1, $2, $3, $4, 1)',
            [newId, 'Faustino', emailClean, hashSenha]
          );
        } catch (e) {}
        user = { id: newId, nome: 'Faustino', email: emailClean, senha: hashSenha, nivel: 1 };
      } else {
        return res.status(401).json({ erro: 'Credenciais inválidas. Verifique seu e-mail e senha.' });
      }
    }

    // Suporte flexível para colunas (senha/password, nome/name, nivel/role)
    const rawDbSenha = String(user.senha || user.password || user.pass || '');
    const userNome = user.nome || user.name || user.username || user.email;
    const userNivel = user.nivel ?? user.role ?? user.level ?? 1;

    let isMatch = false;
    if (rawDbSenha.startsWith('$2a$') || rawDbSenha.startsWith('$2b$') || rawDbSenha.startsWith('$2y$')) {
      const formattedHash = rawDbSenha.replace(/^\$2y\$/, '$2a$');
      isMatch = await bcrypt.compare(senhaClean, formattedHash);
    } else {
      isMatch = (rawDbSenha === senhaClean);
    }

    if (!isMatch) {
      return res.status(401).json({ erro: 'Senha incorreta para o e-mail informado.' });
    }

    const tokenPayload = {
      id: user.id,
      nome: userNome,
      email: user.email,
      nivel: userNivel
    };

    const token = generateToken(tokenPayload);
    setAuthCookie(res, token);

    return res.status(200).json({
      ok: true,
      token,
      user: tokenPayload
    });
  } catch (err: any) {
    console.error('Login error:', err);
    return res.status(500).json({ erro: 'Erro no servidor de autenticação: ' + err.message });
  }
}
