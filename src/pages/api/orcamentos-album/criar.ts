import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';

function slugify(text: string): string {
  let t = String(text)
    .replace(/[^\p{L}\d]+/gu, '-')
    .replace(/^-+|-+$/g, '')
    .toLowerCase()
    .replace(/-+/g, '-');
  const map: Record<string, string> = {
    á: 'a', à: 'a', ã: 'a', â: 'a', é: 'e', ê: 'e', í: 'i', ó: 'o', ô: 'o', õ: 'o',
    ú: 'u', ü: 'u', ç: 'c',
  };
  t = t.split('').map((c) => map[c] || c).join('');
  t = t.replace(/[^-\w]+/g, '');
  return t === '' ? 'n-a' : t;
}

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const d = req.body;

    const clienteNome = trim(d.cliente_nome || '');
    const titulo = trim(d.titulo || '');
    const subtitulo = trim(d.subtitulo || '');
    const tipo = trim(d.tipo || 'albuns_15anos');
    const validade = d.validade || null;
    const valorTotal = parseFloat(d.valor_total) || 0;
    const dadosJson = typeof d.dados_json === 'string'
      ? d.dados_json
      : JSON.stringify(d.dados_json || {}, null, 2);

    if (!clienteNome || !titulo) {
      return res.status(422).json({ erro: 'Nome do cliente e título são obrigatórios.' });
    }

    const id = 'orc_' + Math.random().toString(36).substring(2, 14) + Date.now().toString(36).slice(-4);
    const slugBase = slugify(`${titulo}-${clienteNome}`);

    let slug = slugBase;
    let counter = 1;
    while (true) {
      const existing = await queryOne('SELECT id FROM orcamentos WHERE slug = ?', [slug]);
      if (!existing) break;
      slug = `${slugBase}-${counter}`;
      counter++;
    }

    await query(
      `INSERT INTO orcamentos (id, cliente_id, cliente_nome, tipo, slug, titulo, subtitulo, validade, dados_json, valor_total, status, created_at)
       VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente', NOW())`,
      [id, clienteNome, tipo, slug, titulo, subtitulo, validade, dadosJson, valorTotal]
    );

    return res.status(200).json({
      success: true,
      id,
      slug,
      url_publica: `${process.env.NEXT_PUBLIC_APP_URL || ''}/o/${slug}`,
    });
  } catch (err: any) {
    console.error('Erro ao criar orçamento de álbum:', err);
    return res.status(500).json({ erro: err.message });
  }
});

function trim(v: any): string {
  return typeof v === 'string' ? v.trim() : '';
}
