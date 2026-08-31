import { NextApiRequest, NextApiResponse } from 'next';
import { getUserFromRequest, UserPayload } from './auth';

export function generateId(): string {
  return Math.random().toString(36).substring(2, 10) + Date.now().toString(36);
}

export function requireAuth(
  handler: (req: NextApiRequest, res: NextApiResponse, user: UserPayload) => Promise<void> | void
) {
  return async (req: NextApiRequest, res: NextApiResponse) => {
    // Set JSON headers by default
    res.setHeader('Content-Type', 'application/json');

    // Tratar requisição prévia de CORS OPTIONS — restrito a origens confiáveis
    if (req.method === 'OPTIONS') {
      const origin = (req.headers.origin as string) || '';
      const allowed = ['https://wedistinto.com', 'https://www.wedistinto.com'];
      const isAllowed = allowed.some((o) => origin === o) || origin.endsWith('.vercel.app');
      if (isAllowed) {
        res.setHeader('Access-Control-Allow-Origin', origin);
        res.setHeader('Access-Control-Allow-Credentials', 'true');
      }
      res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE, PATCH');
      res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, asaas-access-token, X-Asaas-Signature');
      return res.status(200).end();
    }

    try {
      const user = getUserFromRequest(req);
      if (!user) {
        return res.status(401).json({ erro: 'Sessão expirada ou não autenticado. Faça login novamente.' });
      }

      await handler(req, res, user);
    } catch (err: any) {
      console.error('API Error:', err);
      return res.status(500).json({ erro: err.message || 'Erro interno do servidor ao processar requisição.' });
    }
  };
}

export function requireAdmin(
  handler: (req: NextApiRequest, res: NextApiResponse, user: UserPayload) => Promise<void> | void
) {
  return requireAuth(async (req, res, user) => {
    if (user.nivel !== 1) {
      return res.status(403).json({ erro: 'Acesso negado: permissão de administrador requerida.' });
    }
    await handler(req, res, user);
  });
}
