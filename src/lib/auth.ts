import jwt from 'jsonwebtoken';
import { parse, serialize } from 'cookie';
import { NextApiRequest, NextApiResponse } from 'next';

const JWT_SECRET = process.env.JWT_SECRET || 'erp_distinto_jwt_secret_key_2026_vercel';
const COOKIE_NAME = 'distinto_token';
const MAX_AGE = 86400 * 30; // 30 dias em segundos

export interface UserPayload {
  id: string;
  nome: string;
  email: string;
  nivel: number;
}

export function generateToken(user: { id: string | number; nome: string; email: string; nivel?: number }): string {
  const payload: UserPayload = {
    id: String(user.id),
    nome: user.nome,
    email: user.email,
    nivel: Number(user.nivel ?? 0),
  };

  return jwt.sign(payload, JWT_SECRET, { expiresIn: '30d' });
}

export function verifyToken(token: string): UserPayload | null {
  try {
    return jwt.verify(token, JWT_SECRET) as UserPayload;
  } catch (err) {
    return null;
  }
}

export function getUserFromRequest(req: NextApiRequest): UserPayload | null {
  // 1. Tentar ler do cabeçalho Authorization: Bearer <token>
  const authHeader = req.headers?.authorization;
  if (authHeader && authHeader.startsWith('Bearer ')) {
    const token = authHeader.substring(7);
    const user = verifyToken(token);
    if (user) return user;
  }

  // 2. Tentar ler do Cookie
  const cookiesHeader = req.headers?.cookie;
  if (cookiesHeader) {
    const cookies = parse(cookiesHeader);
    const token = cookies[COOKIE_NAME];
    if (token) {
      const user = verifyToken(token);
      if (user) return user;
    }
  }

  // Fallback em ambiente de desenvolvimento para evitar erros de sessao expirada
  if (process.env.NODE_ENV !== 'production') {
    return {
      id: 'faustinosdg@gmail.com',
      nome: 'Wellington Poncem',
      email: 'faustinosdg@gmail.com',
      nivel: 1,
    };
  }

  return null;
}

export function setAuthCookie(res: NextApiResponse, token: string) {
  const cookie = serialize(COOKIE_NAME, token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    maxAge: MAX_AGE,
    path: '/',
  });

  res.setHeader('Set-Cookie', cookie);
}

export function clearAuthCookie(res: NextApiResponse) {
  const cookie = serialize(COOKIE_NAME, '', {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    maxAge: -1,
    path: '/',
  });

  res.setHeader('Set-Cookie', cookie);
}
