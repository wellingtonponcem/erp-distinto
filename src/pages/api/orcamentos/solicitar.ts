import { NextApiRequest, NextApiResponse } from 'next';
import { query } from '@/lib/db';
import { generateId } from '@/lib/helpers';
import { brevoService } from '@/lib/brevo';
import {
  quoteForm,
  validateQuoteForm,
  normalizeValues,
  FormSection,
  FormField,
} from '@/lib/propostas/form-orcamento';

const EMAIL_DESTINO = process.env.ORCAMENTO_EMAIL_DESTINO || 'ola@wedistinto.com';

function esc(v: string): string {
  return String(v ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function formatarValor(v: string | string[]): string {
  if (Array.isArray(v)) return v.join(', ');
  return String(v ?? '');
}

function buildEmailHtml(dados: Record<string, string | string[]>): string {
  let sectionsHtml = '';
  for (const section of quoteForm.sections) {
    let rows = '';
    for (const field of section.fields) {
      const val = dados[field.id];
      if (val === undefined || val === '') continue;
      rows += `
        <tr>
          <td style="padding:10px 14px;border-bottom:1px solid #2a2a2a;font-family:Arial,sans-serif;font-size:12px;color:#b9b9b9;vertical-align:top;width:42%;"><strong style="color:#c5a880;">${esc(field.label)}</strong></td>
          <td style="padding:10px 14px;border-bottom:1px solid #2a2a2a;font-family:Arial,sans-serif;font-size:12px;color:#ffffff;vertical-align:top;">${esc(formatarValor(val))}</td>
        </tr>`;
    }
    if (!rows) continue;
    sectionsHtml += `
      <h3 style="font-family:Arial,sans-serif;font-size:14px;color:#c5a880;letter-spacing:1px;text-transform:uppercase;margin:28px 0 6px;">${esc(section.section_name)}</h3>
      <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:#1a1a1a;border:1px solid #2a2a2a;border-radius:8px;">${rows}</table>`;
  }

  return `
    <div style="background:#0d0d0d;padding:32px 16px;font-family:Arial,sans-serif;">
      <div style="max-width:640px;margin:0 auto;background:#111;border:1px solid #2a2a2a;border-radius:14px;overflow:hidden;">
        <div style="padding:24px 28px;background:linear-gradient(135deg,#181818,#0f0f0f);border-bottom:2px solid #c5a880;">
          <h1 style="margin:0;font-family:Georgia,serif;font-size:20px;color:#ffffff;">Nova Solicitação de Orçamento</h1>
          <p style="margin:6px 0 0;font-size:12px;color:#b9b9b9;">Fotografia de Casamento — We Distinto</p>
        </div>
        <div style="padding:8px 28px 28px;">
          ${sectionsHtml}
          <p style="font-family:Arial,sans-serif;font-size:11px;color:#777;margin-top:24px;">Recebido automaticamente pelo site wedistinto.com/orcamento</p>
        </div>
      </div>
    </div>`;
}

function buildConfirmationHtml(nome: string): string {
  return `
    <div style="background:#0d0d0d;padding:32px 16px;font-family:Arial,sans-serif;">
      <div style="max-width:560px;margin:0 auto;background:#111;border:1px solid #2a2a2a;border-radius:14px;overflow:hidden;">
        <div style="padding:24px 28px;background:linear-gradient(135deg,#181818,#0f0f0f);border-bottom:2px solid #c5a880;text-align:center;">
          <h1 style="margin:0;font-family:Georgia,serif;font-size:20px;color:#ffffff;">Recebemos sua solicitação!</h1>
        </div>
        <div style="padding:24px 28px;text-align:center;">
          <p style="font-family:Arial,sans-serif;font-size:14px;color:#e5e5e5;line-height:1.7;">Olá${nome ? `, ${esc(nome)}` : ''}! Obrigado por entrar em contato com a <strong style="color:#c5a880;">We Distinto</strong>.</p>
          <p style="font-family:Arial,sans-serif;font-size:14px;color:#b9b9b9;line-height:1.7;">Recebemos seus dados e em breve enviaremos uma proposta personalizada para o seu grande dia. Fique de olho no seu e-mail e WhatsApp.</p>
          <p style="font-family:Arial,sans-serif;font-size:12px;color:#777;margin-top:24px;">Com carinho,<br/>Equipe Distinto</p>
        </div>
      </div>
    </div>`;
}

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method === 'OPTIONS') {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    return res.status(200).end();
  }

  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const body = (req.body && typeof req.body === 'object' ? req.body : {}) as Record<string, any>;
    const values: Record<string, string | string[] | undefined> = {};

    for (const section of quoteForm.sections) {
      for (const field of section.fields) {
        const raw = body[field.id];
        if (field.type === 'checkbox') {
          values[field.id] = Array.isArray(raw) ? raw.map((v: any) => String(v)) : raw ? [String(raw)] : [];
        } else {
          values[field.id] = raw === undefined || raw === null ? undefined : String(raw);
        }
      }
    }

    const errors = validateQuoteForm(values);
    if (errors.length > 0) {
      return res.status(422).json({ erro: 'Dados inválidos.', errors });
    }

    const dados = normalizeValues(values);

    // Campos-chave para a listagem no admin
    const nomeContato = typeof dados['nome_contato'] === 'string' ? dados['nome_contato'] : '';
    const email = typeof dados['email'] === 'string' ? dados['email'] : '';
    const telefone = typeof dados['telefone_whatsapp'] === 'string' ? dados['telefone_whatsapp'] : '';
    const dataCasamento = typeof dados['data_casamento'] === 'string' ? dados['data_casamento'] : '';

    const id = generateId();
    await query(
      `INSERT INTO solicitacoes_orcamento (id, nome_contato, email, telefone, data_casamento, dados_json, status)
       VALUES ($1, $2, $3, $4, $5, $6, 'novo')`,
      [id, nomeContato, email, telefone, dataCasamento, JSON.stringify(dados)]
    );

    // E-mail 1: notificação para a equipe
    try {
      await brevoService.sendEmail({
        to: [{ email: EMAIL_DESTINO }],
        subject: `Nova Solicitação de Orçamento — ${nomeContato || 'Casamento'}`,
        htmlContent: buildEmailHtml(dados),
      });
    } catch (err: any) {
      console.error('Erro ao enviar e-mail de notificação de orçamento:', err.message);
    }

    // E-mail 2: confirmação para o cliente
    if (email) {
      try {
        await brevoService.sendEmail({
          to: [{ email }],
          subject: 'Recebemos sua solicitação de orçamento — We Distinto',
          htmlContent: buildConfirmationHtml(nomeContato),
        });
      } catch (err: any) {
        console.error('Erro ao enviar e-mail de confirmação ao cliente:', err.message);
      }
    }

    return res.status(200).json({ ok: true, mensagem: 'Solicitação recebida com sucesso.' });
  } catch (err: any) {
    console.error('Erro ao processar solicitação de orçamento:', err);
    return res.status(500).json({ erro: err.message || 'Erro interno ao processar solicitação.' });
  }
}
