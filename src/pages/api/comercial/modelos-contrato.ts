import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth, generateId } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';
import fs from 'fs';
import path from 'path';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  const method = req.method;

  if (method === 'GET') {
    try {
      let rows = await query('SELECT * FROM modelos_contrato ORDER BY padrao DESC, criado_em DESC');

      // Se o banco estiver vazio, alimentar com o modelo base oficial inicial
      if (rows.length === 0) {
        const templatePath = path.join(process.cwd(), 'contrato_jeanenuneswellingtonponcemdocumentoc.html');
        let initialContent = '';
        if (fs.existsSync(templatePath)) {
          initialContent = fs.readFileSync(templatePath, 'utf-8');
        }

        if (initialContent) {
          const defaultId = 'modelo_oficial_casamento';
          await query(
            `INSERT INTO modelos_contrato (id, nome, tipo, conteudo_html, padrao)
             VALUES ($1, $2, $3, $4, 1)`,
            [defaultId, 'Modelo Oficial Casamento - Poncem Studio', 'casamento', initialContent]
          );

          rows = await query('SELECT * FROM modelos_contrato ORDER BY padrao DESC, criado_em DESC');
        }
      }

      return res.status(200).json(rows);
    } catch (err: any) {
      console.error('Erro ao buscar modelos de contrato:', err);
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'POST') {
    try {
      const { nome, tipo, conteudo_html, padrao } = req.body || {};

      if (!nome || !conteudo_html) {
        return res.status(422).json({ erro: 'Nome e Conteúdo HTML do modelo são obrigatórios' });
      }

      const id = generateId();
      const isPadrao = padrao ? 1 : 0;

      if (isPadrao === 1) {
        await query('UPDATE modelos_contrato SET padrao = 0');
      }

      await query(
        `INSERT INTO modelos_contrato (id, nome, tipo, conteudo_html, padrao)
         VALUES ($1, $2, $3, $4, $5)`,
        [id, nome, tipo || 'casamento', conteudo_html, isPadrao]
      );

      return res.status(201).json({ ok: true, id });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'PUT') {
    try {
      const { id, nome, tipo, conteudo_html, padrao } = req.body || {};

      if (!id) return res.status(422).json({ erro: 'ID do modelo é obrigatório' });

      const isPadrao = padrao ? 1 : 0;

      if (isPadrao === 1) {
        await query('UPDATE modelos_contrato SET padrao = 0');
      }

      await query(
        `UPDATE modelos_contrato SET
          nome = $1,
          tipo = $2,
          conteudo_html = $3,
          padrao = $4
         WHERE id = $5`,
        [nome, tipo || 'casamento', conteudo_html, isPadrao, id]
      );

      return res.status(200).json({ ok: true });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'DELETE') {
    try {
      const { id } = req.query;
      if (!id) return res.status(422).json({ erro: 'ID do modelo é obrigatório' });

      await query('DELETE FROM modelos_contrato WHERE id = $1', [id]);
      return res.status(200).json({ ok: true });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  return res.status(405).json({ erro: 'Método não permitido' });
});
