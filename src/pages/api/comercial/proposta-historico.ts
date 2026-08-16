import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';
import { generateId } from '@/lib/helpers';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  const method = req.method;

  // Garantir existência da tabela propostas_historico no MySQL Hostinger
  try {
    await query(`
      CREATE TABLE IF NOT EXISTS propostas_historico (
        id VARCHAR(64) PRIMARY KEY,
        proposta_id VARCHAR(64) NOT NULL,
        user_id VARCHAR(64) NOT NULL,
        tipo VARCHAR(32) DEFAULT 'nota',
        conteudo TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);
  } catch (e) {}

  if (method === 'GET') {
    const { proposta_id } = req.query;
    if (!proposta_id) return res.status(422).json({ erro: 'ID da proposta é obrigatório' });

    try {
      const historico = await query(
        `SELECT h.*, COALESCE(u.nome, 'Sistema') as usuario_nome
         FROM propostas_historico h
         LEFT JOIN users u ON h.user_id = u.id
         WHERE h.proposta_id = $1
         ORDER BY h.id DESC`,
        [String(proposta_id)]
      );

      const proposta = await queryOne(`SELECT * FROM propostas WHERE id = $1 LIMIT 1`, [String(proposta_id)]);

      // Recomendador de Próximo Passo da IA
      let recomendacao = 'Revise as informações e compartilhe o link público da proposta com o cliente.';
      if (proposta) {
        const st = (proposta.status || 'rascunho').toLowerCase();
        const tipo = (proposta.tipo || 'casamento').toLowerCase();
        const val = parseFloat(proposta.valor_total || proposta.valor || 0);

        if (st === 'aprovada' || st === 'aceita') {
          if (val > 0) {
            recomendacao = '🎉 Proposta Aprovada! O próximo passo recomendado é verificar o contrato e gerar a cobrança financeira no Asaas.';
          } else {
            recomendacao = '⚠️ Proposta Aprovada, porém sem valor total. Defina o valor final dos pacotes no fechamento.';
          }
        } else if (st === 'pendente' || st === 'enviada') {
          recomendacao = '📩 Proposta Enviada ao cliente. Acompanhe a decisão pelo WhatsApp e registre os feedbacks no histórico.';
        } else if (st === 'recusada') {
          recomendacao = '❌ Proposta Recusada pelo cliente. Entre em contato para entender as objeções e ajustar a condição comercial.';
        } else {
          recomendacao = '✍️ Proposta em Rascunho. Preencha os detalhes, pacotes e texto persuasivo via IA antes de enviar o link.';
        }
      }

      return res.status(200).json({ ok: true, historico, recomendacao });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'POST') {
    try {
      const { proposta_id, conteudo, tipo } = req.body || {};
      if (!proposta_id || !conteudo) {
        return res.status(422).json({ erro: 'Proposta ID e conteúdo da nota são obrigatórios' });
      }

      const id = generateId();
      await query(
        `INSERT INTO propostas_historico (id, proposta_id, user_id, tipo, conteudo) VALUES ($1, $2, $3, $4, $5)`,
        [id, proposta_id, user.id, tipo || 'nota', conteudo]
      );

      return res.status(201).json({ ok: true, id });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  return res.status(405).json({ erro: 'Método não permitido' });
});
