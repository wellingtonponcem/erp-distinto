import type { NextApiRequest, NextApiResponse } from 'next';

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  const envCheck: any = {
    JWT_SECRET: !!process.env.JWT_SECRET,
    MYSQL_HOST: !!process.env.MYSQL_HOST,
    MYSQL_DATABASE: !!process.env.MYSQL_DATABASE,
    MYSQL_USER: !!process.env.MYSQL_USER,
    MYSQL_PASSWORD: !!process.env.MYSQL_PASSWORD,
    BREVO_API_KEY: !!process.env.BREVO_API_KEY,
    BREVO_SENDER_EMAIL: process.env.BREVO_SENDER_EMAIL || null,
    NEXT_PUBLIC_APP_URL: process.env.NEXT_PUBLIC_APP_URL || null,
    VERCEL_URL: process.env.VERCEL_URL || null,
    NODE_ENV: process.env.NODE_ENV,
    buildId: process.env.VERCEL_GIT_COMMIT_SHA || null,
  };
  return res.status(200).json({ ok: true, env: envCheck, host: req.headers.host, url: req.url });
}
