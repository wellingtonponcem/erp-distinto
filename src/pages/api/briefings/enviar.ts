import { NextApiRequest, NextApiResponse } from 'next';
import { query, queryOne } from '@/lib/db';
import { generateId } from '@/lib/helpers';

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
