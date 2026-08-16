import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';
import { recomendarProximoPasso } from '@/lib/propostas/ia';

/**
 * Port de api/gerenciamento/proposta_historico.php — histórico de interações de
 * uma proposta + recomendação de próximo passo via IA.
 * GET ?id=... → { historico, recomendacao }
 * POST { proposta_id, tipo, conteudo } → { sucesso, debug, recomendacao }
 */
export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  try {
    await query(
      `CREATE TABLE IF NOT EXISTS propostas_historico (
        id INT AUTO_INCREMENT PRIMARY KEY,
        proposta_id TEXT NOT NULL,
        user_id TEXT NOT NULL,
        tipo TEXT DEFAULT 'nota',
        conteudo TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )`
    );
  } catch (e) {}

  const metodo = req.method;

  if (metodo === 'GET') {
    const propostaId = req.query.id ? String(req.query.id) : null;
    if (!propostaId) {
      return res.status(400).json({ erro: 'ID da proposta não informado.' });
    }

    try {
      const rows = await query(
        `SELECT h.*, COALESCE(u.nome, 'Sistema') as usuario_nome
         FROM propostas_historico h
         LEFT JOIN users u ON CAST(h.user_id AS CHAR) = CAST(u.id AS CHAR)
         WHERE h.proposta_id = $1
         ORDER BY h.created_at DESC`,
        [propostaId]
      );

      let recomendacao = '';
      try {
        const propostaDados = await queryOne(
          `SELECT id, cliente_nome, tipo, status, dados_json, titulo FROM propostas WHERE id = $1`,
          [propostaId]
        );
        if (propostaDados) {
          recomendacao = await recomendarProximoPasso(propostaDados, rows.slice(0, 3));
        }
      } catch (e) {
        recomendacao = '';
      }

      return res.status(200).json({ historico: rows, recomendacao });
    } catch (e: any) {
      return res.status(500).json({ erro: `Erro GET: ${e.message}` });
    }
  }

  if (metodo === 'POST') {
    try {
      const data = req.body || {};
      const propostaId = data.proposta_id || null;
      const tipo = data.tipo || 'nota';
      const conteudo = String(data.conteudo || '').trim();
      const userId = user.id || '';

      if (!propostaId || !conteudo) {
        return res.status(400).json({ erro: `Dados incompletos. proposta_id=${propostaId ?? 'null'} conteudo=${conteudo || 'vazio'}` });
      }
      if (!userId) {
        return res.status(400).json({ erro: 'Sessão sem user_id.' });
      }

      await query(
        `INSERT INTO propostas_historico (proposta_id, user_id, tipo, conteudo) VALUES ($1, $2, $3, $4)`,
        [propostaId, userId, tipo, conteudo]
      );

      let rows: any[] = [];
      try {
        rows = await query(
          `SELECT tipo, conteudo, created_at FROM propostas_historico WHERE proposta_id = $1 ORDER BY created_at DESC LIMIT 3`,
          [propostaId]
        );
      } catch (e) {}

      let recomendacao = '';
      try {
        const propostaDados = await queryOne(
          `SELECT id, cliente_nome, tipo, status, dados_json, titulo FROM propostas WHERE id = $1`,
          [propostaId]
        );
        if (propostaDados) {
          recomendacao = await recomendarProximoPasso(propostaDados, rows);
        }
      } catch (e) {
        recomendacao = '';
      }

      return res.status(200).json({
        sucesso: true,
        debug: { proposta_id: propostaId, user_id: userId, tipo },
        recomendacao,
      });
    } catch (e: any) {
      return res.status(500).json({ erro: `Erro POST: ${e.message}` });
    }
  }

  return res.status(405).json({ erro: `Método não suportado: ${metodo}` });
});