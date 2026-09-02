import { NextApiRequest, NextApiResponse } from 'next';
import { query, queryOne } from '@/lib/db';
import { generateId } from '@/lib/helpers';
import { brevoService } from '@/lib/brevo';
import { briefingLogisticoConfig } from '@/lib/propostas/form-briefing';

const EMAIL_DESTINO = process.env.BRIEFING_EMAIL_DESTINO || process.env.ORCAMENTO_EMAIL_DESTINO || 'ola@wedistinto.com';

function esc(v: string): string {
  return String(v ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function buildBriefingEmailHtml(dados: Record<string, any>, clienteNome: string, dataCasamento: string | null): string {
  let sectionsHtml = '';
  for (const section of briefingLogisticoConfig.sections) {
    let rows = '';
    for (const field of section.fields) {
      const val = dados[field.id];
      if (val === undefined || val === null || String(val).trim() === '') continue;
      rows += `
        <tr>
          <td style="padding:10px 14px;border-bottom:1px solid #2a2a2a;font-family:Arial,sans-serif;font-size:12px;color:#b9b9b9;vertical-align:top;width:42%;"><strong style="color:#c5a880;">${esc(field.label)}</strong></td>
          <td style="padding:10px 14px;border-bottom:1px solid #2a2a2a;font-family:Arial,sans-serif;font-size:12px;color:#ffffff;vertical-align:top;white-space:pre-wrap;">${esc(String(val))}</td>
        </tr>`;
    }
    if (!rows) continue;
    sectionsHtml += `
      <h3 style="font-family:Arial,sans-serif;font-size:13px;color:#c5a880;letter-spacing:1px;text-transform:uppercase;margin:28px 0 6px;">${esc(section.section_name)}</h3>
      <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:#1a1a1a;border:1px solid #2a2a2a;border-radius:8px;">${rows}</table>`;
  }

  // Campos extras (LGPD etc) que não estão no form-briefing mas vieram no payload
  const extras = Object.entries(dados).filter(([k]) => !briefingLogisticoConfig.sections.some((s) => s.fields.some((f) => f.id === k)));
  let extrasRows = '';
  for (const [k, v] of extras) {
    if (k === 'lgpd_aceito' || k === 'lgpd_data_aceite') continue;
    if (v === undefined || v === null || String(v).trim() === '') continue;
    extrasRows += `<tr><td style="padding:8px 14px;border-bottom:1px solid #2a2a2a;font-size:11px;color:#888;">${esc(k)}</td><td style="padding:8px 14px;border-bottom:1px solid #2a2a2a;font-size:11px;color:#fff;white-space:pre-wrap;">${esc(String(v))}</td></tr>`;
  }
  if (extrasRows) {
    sectionsHtml += `<h3 style="font-family:Arial,sans-serif;font-size:11px;color:#777;margin:28px 0 6px;">Outros dados</h3><table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:#1a1a1a;border:1px solid #2a2a2a;">${extrasRows}</table>`;
  }

  return `
    <div style="background:#0d0d0d;padding:32px 16px;font-family:Arial,sans-serif;">
      <div style="max-width:640px;margin:0 auto;background:#111;border:1px solid #2a2a2a;border-radius:14px;overflow:hidden;">
        <div style="padding:24px 28px;background:linear-gradient(135deg,#181818,#0f0f0f);border-bottom:2px solid #c5a880;">
          <h1 style="margin:0;font-family:Georgia,serif;font-size:19px;color:#ffffff;">Novo Briefing Logístico Recebido</h1>
          <p style="margin:6px 0 0;font-size:12px;color:#b9b9b9;">${esc(clienteNome)}${dataCasamento ? ` — Data: ${esc(dataCasamento)}` : ''} — We Distinto</p>
        </div>
        <div style="padding:8px 28px 28px;">
          ${sectionsHtml || '<p style="color:#888;font-size:13px;">Nenhum campo preenchido além do nome.</p>'}
          <p style="font-family:Arial,sans-serif;font-size:11px;color:#777;margin-top:24px;">Recebido automaticamente pelo link <code>wedistinto.com/briefing</code> e salvo no ERP em <code>briefings_resposta</code>.</p>
        </div>
      </div>
    </div>`;
}

function normalizeDataCasamento(v: any): string | null {
  if (!v || typeof v !== 'string') return null;
  const t = v.trim();
  if (!t) return null;
  // já no formato YYYY-MM-DD
  if (/^\d{4}-\d{2}-\d{2}$/.test(t)) {
    const d = new Date(t + 'T00:00:00');
    return isNaN(d.getTime()) ? null : t;
  }
  // converte DD/MM/YYYY ou DD-MM-YYYY
  const m = t.match(/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/);
  if (m) {
    const d = m[1].padStart(2, '0');
    const mo = m[2].padStart(2, '0');
    const y = m[3];
    const iso = `${y}-${mo}-${d}`;
    const dt = new Date(iso + 'T00:00:00');
    if (!isNaN(dt.getTime())) return iso;
  }
  // tenta Date parse genérico
  const dt = new Date(t);
  if (!isNaN(dt.getTime())) {
    const y = dt.getFullYear();
    const mo = String(dt.getMonth() + 1).padStart(2, '0');
    const d = String(dt.getDate()).padStart(2, '0');
    return `${y}-${mo}-${d}`;
  }
  return null;
}

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method === 'GET') {
    try {
      const rows = await query('SELECT * FROM briefings_resposta ORDER BY criado_em DESC');
      const briefings = rows.map((r: any) => {
        let dados: any = {};
        try {
          dados = typeof r.dados_json === 'string' ? JSON.parse(r.dados_json || '{}') : (r.dados_json || {});
        } catch (e: any) {
          console.warn('dados_json corrompido id=', r.id, e?.message);
          dados = { _erro_parse: true, raw: String(r.dados_json || '').slice(0, 500) };
        }
        return { ...r, dados };
      });
      return res.status(200).json(briefings);
    } catch (err: any) {
      console.error('Erro ao listar briefings:', err);
      return res.status(500).json({ erro: 'Erro interno ao carregar briefings', detail: err.message });
    }
  }

  if (req.method === 'POST') {
    try {
      const payload = req.body || {};
      let clienteNome = String(payload.nome_noivos || payload.cliente || payload.nome_casal || 'Casal de Noivos').trim();
      clienteNome = clienteNome.replace(/[\r\n\t\x00-\x1F]/g, ' ').slice(0, 255) || 'Casal de Noivos';
      const dataCasamento = normalizeDataCasamento(payload.data_casamento);

      if (!payload.nome_noivos && !payload.cliente) {
        return res.status(422).json({ erro: 'O nome do casal / cliente é obrigatório.' });
      }

      // sanitiza payload: limita tamanho de cada campo string
      const sanitizedPayload: any = {};
      for (const [k, v] of Object.entries(payload)) {
        if (typeof v === 'string') sanitizedPayload[k] = v.slice(0, 5000);
        else sanitizedPayload[k] = v;
      }
      // garante dados essenciais normalizados
      sanitizedPayload.nome_noivos = clienteNome;
      if (dataCasamento) sanitizedPayload.data_casamento = dataCasamento;

      const id = generateId();
      const dadosJsonStr = JSON.stringify(sanitizedPayload);

      await query(
        'INSERT INTO briefings_resposta (id, cliente_nome, data_casamento, dados_json, status) VALUES ($1, $2, $3, $4, $5)',
        [id, clienteNome, dataCasamento, dadosJsonStr, 'novo']
      );

      console.log('[briefing] salvo', { id, clienteNome, dataCasamento });

      // E-mail para a equipe (best-effort, não bloqueia resposta ao cliente)
      try {
        await brevoService.sendEmail({
          to: [{ email: EMAIL_DESTINO }],
          subject: `Novo Briefing Logístico — ${clienteNome}${dataCasamento ? ` — ${dataCasamento}` : ''}`,
          htmlContent: buildBriefingEmailHtml(sanitizedPayload, clienteNome, dataCasamento),
        });
        console.log('[briefing] e-mail enviado para', EMAIL_DESTINO);
      } catch (err: any) {
        console.error('[briefing] falha ao enviar e-mail para', EMAIL_DESTINO, err?.message || err);
      }

      return res.status(201).json({
        ok: true,
        id,
        mensagem: 'Briefing logístico enviado com sucesso!',
      });
    } catch (err: any) {
      console.error('Erro ao salvar briefing:', err?.message, err?.stack);
      // mapeia erro de charset/pattern para mensagem amigável
      let msg = err?.message || 'Erro interno';
      if (/Incorrect string value|Incorrect date value|Data too long/i.test(msg)) {
        msg = 'Dados com caracteres inválidos. Tente remover emojis ou caracteres especiais e use data AAAA-MM-DD.';
      }
      if (/did not match the expected pattern/i.test(msg)) {
        msg = 'Formato de data/horário inválido. Use HH:MM para horários e AAAA-MM-DD para data.';
      }
      return res.status(500).json({ erro: 'Erro ao processar envio do briefing logístico: ' + msg, detail: err?.message });
    }
  }

  return res.status(405).json({ erro: 'Método não permitido' });
}
