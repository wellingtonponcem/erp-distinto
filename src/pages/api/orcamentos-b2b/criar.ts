import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';

function slugify(text: string): string {
  let t = String(text)
    .replace(/[^\p{L}\d]+/gu, '-')
    .replace(/^-+|-+$/g, '')
    .toLowerCase()
    .replace(/-+/g, '-');
  t = t.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  t = t.replace(/[^-\w]+/g, '');
  return t === '' ? 'n-a' : t;
}

function fixValidadeYear(valStr: any): string | null {
  if (!valStr) return null;
  const str = String(valStr).trim();
  const currentYear = new Date().getFullYear();
  const match = str.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (match) {
    const year = parseInt(match[1], 10);
    if (year < currentYear) {
      return `${currentYear}-${match[2]}-${match[3]}`;
    }
  }
  return str;
}

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const d = req.body;

    const clienteNome = String(d.cliente_nome || '').trim();
    const clienteEmpresa = String(d.cliente_empresa || '').trim();
    const titulo = String(d.titulo || '').trim();
    const validade = fixValidadeYear(d.validade || null);
    const valorTotal = parseFloat(d.valor_total) || 0;

    const dadosJson = typeof d.dados_json === 'string'
      ? d.dados_json
      : JSON.stringify(d.dados_json || {}, null, 2);

    if (!clienteNome || !titulo) {
      return res.status(422).json({ erro: 'Nome do cliente e título são obrigatórios.' });
    }

    const id = 'b2b_' + Math.random().toString(36).substring(2, 14) + Date.now().toString(36).slice(-4);
    const slugBase = slugify(`${titulo}-${clienteNome}`);

    let slug = slugBase;
    let counter = 1;
    while (true) {
      const existing = await queryOne('SELECT id FROM orcamentos_b2b WHERE slug = ?', [slug]);
      if (!existing) break;
      slug = `${slugBase}-${counter}`;
      counter++;
    }

    await query(
      `INSERT INTO orcamentos_b2b (id, cliente_nome, cliente_empresa, titulo, slug, valor_total, validade, dados_json, status, criado_em)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'rascunho', NOW())`,
      [id, clienteNome, clienteEmpresa, titulo, slug, valorTotal, validade, dadosJson]
    );

    return res.status(200).json({
      success: true,
      id,
      slug,
      url_publica: `${process.env.NEXT_PUBLIC_APP_URL || ''}/b2b/${slug}`,
    });
  } catch (err: any) {
    console.error('Erro ao criar orcamento B2B:', err);
    return res.status(500).json({ erro: err.message });
  }
});
