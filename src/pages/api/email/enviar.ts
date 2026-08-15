import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { brevoService } from '@/lib/brevo';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  const { to, subject, htmlContent } = req.body || {};

  if (!to || !subject || !htmlContent) {
    return res.status(422).json({ erro: 'Destinatário (to), assunto (subject) e conteúdo (htmlContent) são obrigatórios.' });
  }

  try {
    const recipients = Array.isArray(to) ? to : [{ email: to }];
    const result = await brevoService.sendEmail({
      to: recipients,
      subject,
      htmlContent,
    });

    return res.status(200).json({ ok: true, mensagem: 'E-mail enviado com sucesso via Brevo', result });
  } catch (err: any) {
    return res.status(500).json({ erro: err.message });
  }
});
