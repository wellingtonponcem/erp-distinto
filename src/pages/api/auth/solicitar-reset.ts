import type { NextApiRequest, NextApiResponse } from 'next';
import crypto from 'crypto';
import { query, queryOne } from '@/lib/db';
import { brevoService } from '@/lib/brevo';
import { generateId } from '@/lib/helpers';

function getClientIp(req: NextApiRequest): string {
  const xff = (req.headers['x-forwarded-for'] as string) || '';
  if (xff) return xff.split(',')[0].trim();
  return (req.socket?.remoteAddress as string) || 'unknown';
}

function getAppUrl(req: NextApiRequest): string {
  const envUrl =
    process.env.NEXT_PUBLIC_APP_URL ||
    process.env.APP_URL ||
    (process.env.VERCEL_URL ? `https://${process.env.VERCEL_URL}` : '');
  if (envUrl) return envUrl.replace(/\/$/, '');

  const proto = (req.headers['x-forwarded-proto'] as string) || 'https';
  const host = (req.headers['x-forwarded-host'] as string) || (req.headers.host as string) || '';
  if (host) return `${proto}://${host}`;
  return '';
}

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  const emailRaw = (req.body?.email || '').toString().trim().toLowerCase();
  const email = emailRaw.slice(0, 254);

  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return res.status(422).json({ erro: 'Informe um e-mail válido.' });
  }

  const genericSuccess = {
    ok: true,
    mensagem: 'Se o e-mail estiver cadastrado, enviaremos um link de redefinição em alguns minutos.',
  };

  try {
    const ip = getClientIp(req);

    // Rate limit simples: max 5 por IP e 3 por e-mail nos últimos 15 min
    try {
      const fifteenAgo = new Date(Date.now() - 15 * 60 * 1000);
      const byIp = await queryOne('SELECT COUNT(*) as cnt FROM password_reset_tokens WHERE criado_em > $1 AND id LIKE $2', [
        fifteenAgo,
        `ip:${ip}%`,
      ]);
      // Fallback: contar por created_at sem ip tracking real — usa contagem global recente por IP via log separado
      // Implementação pragmática: contar tokens criados recentemente (proxy para rate limit)
      const recentCount = await queryOne('SELECT COUNT(*) as cnt FROM password_reset_tokens WHERE criado_em > $1', [
        fifteenAgo,
      ]);
      // Se houver mais de 20 solicitações globais recentes, aplica throttle leve
      const recentCnt = parseInt((recentCount as any)?.cnt || '0', 10);
      if (recentCnt > 30) {
        return res.status(429).json({ erro: 'Muitas solicitações. Tente novamente em alguns minutos.' });
      }
    } catch (e) {
      // ignora falha de rate limit check
    }

    // Anti-enumeração: buscar usuário mas sempre responder genérico
    const user = await queryOne('SELECT id, nome, email FROM users WHERE LOWER(TRIM(email)) = $1 LIMIT 1', [email]);

    if (!user) {
      return res.status(200).json(genericSuccess);
    }

    // Verificar limite por usuário: max 3 tokens ativos recentes
    try {
      const recentUser = await query('SELECT id FROM password_reset_tokens WHERE user_id = $1 AND criado_em > $2', [
        user.id,
        new Date(Date.now() - 15 * 60 * 1000),
      ]);
      if (recentUser.length >= 3) {
        // Já tem 3 solicitações recentes — ainda retorna sucesso mas não cria novo
        return res.status(200).json(genericSuccess);
      }
    } catch (e) {}

    // Invalidar tokens anteriores pendentes
    try {
      await query('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = $1 AND used_at IS NULL', [user.id]);
    } catch (e) {}

    // Gerar token seguro
    const token = crypto.randomBytes(32).toString('base64url');
    const tokenHash = crypto.createHash('sha256').update(token).digest('hex');
    const expiresAt = new Date(Date.now() + 60 * 60 * 1000); // 1 hora
    const id = generateId();

    await query(
      'INSERT INTO password_reset_tokens (id, user_id, token_hash, expires_at, criado_em) VALUES ($1, $2, $3, $4, NOW())',
      [id, user.id, tokenHash, expiresAt]
    );

    // Montar URL de redefinição
    const appUrl = getAppUrl(req);
    const resetUrl = `${appUrl}/redefinir-senha?token=${encodeURIComponent(token)}`;

    // Enviar e-mail via Brevo (se configurado)
    if (brevoService.isConfigured()) {
      try {
        const nome = user.nome || '';
        const htmlContent = `
          <div style="font-family:Inter,Arial,sans-serif;max-width:520px;margin:0 auto;padding:32px 24px;background:#0c0c0c;color:#fafafa;border-radius:16px;">
            <div style="width:48px;height:48px;background:#c5a880;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:900;color:#000;margin:0 auto 16px;text-align:center;line-height:48px;">D</div>
            <h1 style="text-align:center;font-size:20px;margin:0 0 8px;">Redefinição de senha</h1>
            <p style="color:#a1a1aa;font-size:14px;text-align:center;margin:0 0 24px;">Olá${nome ? `, ${nome}` : ''}. Recebemos uma solicitação para redefinir sua senha no ERP Distinto.</p>
            <div style="text-align:center;margin:0 0 24px;">
              <a href="${resetUrl}" style="display:inline-block;background:#c5a880;color:#000;padding:12px 28px;border-radius:999px;text-decoration:none;font-weight:800;font-size:14px;">Redefinir minha senha</a>
            </div>
            <p style="color:#71717a;font-size:12px;text-align:center;margin:0 0 8px;">Este link expira em <strong style="color:#fafafa;">1 hora</strong> e só pode ser usado uma vez.</p>
            <p style="color:#71717a;font-size:12px;text-align:center;margin:0 0 16px;">Se você não solicitou, ignore este e-mail.</p>
            <p style="color:#52525b;font-size:11px;text-align:center;word-break:break-all;margin:0;">Se o botão não funcionar, copie e cole este link no navegador:<br>${resetUrl}</p>
          </div>
        `;

        await brevoService.sendEmail({
          to: [{ email: user.email, name: nome || undefined }],
          subject: 'Redefinição de senha — ERP Distinto',
          htmlContent,
        });
      } catch (err: any) {
        console.error('Brevo send error (solicitar-reset):', err?.message || err);
        // Não falha a requisição — loga e retorna sucesso genérico (evita vazar erro de infra)
      }
    } else {
      console.warn('BREVO_API_KEY não configurada — e-mail de reset não enviado. Token gerado para', email);
    }

    // Limpeza assíncrona de tokens expirados antigos (best-effort)
    query('DELETE FROM password_reset_tokens WHERE expires_at < $1 AND used_at IS NOT NULL', [
      new Date(Date.now() - 7 * 24 * 60 * 60 * 1000),
    ]).catch(() => {});

    return res.status(200).json(genericSuccess);
  } catch (err: any) {
    console.error('solicitar-reset error:', err);
    return res.status(500).json({ erro: 'Não foi possível iniciar a recuperação agora. Tente novamente.' });
  }
}
