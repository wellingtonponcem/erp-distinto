import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { query, queryOne, auditLog, requireOwnership } from '@/lib/db';
import { generateId } from '@/lib/helpers';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  const method = req.method;

  if (method === 'GET') {
    try {
      const rows = user.nivel === 1
        ? await query(`SELECT p.* FROM propostas p ORDER BY p.id DESC`)
        : await query(`SELECT p.* FROM propostas p WHERE p.criado_por = $1 ORDER BY p.id DESC`, [user.id]);

      const formatadas = rows.map((r: any) => {
        let dadosParsed: any = {};
        try {
          dadosParsed = typeof r.dados_json === 'string' ? JSON.parse(r.dados_json) : (r.dados_json || {});
        } catch (e) {}

        const clienteNome = r.cliente_nome || r.cliente || 'Cliente';
        const dataCriacao = r.created_at || r.criado_em || r.data_criacao;
        const valorNum = parseFloat(r.valor_total || r.valor || 0);

        return {
          id: r.id,
          slug: r.slug || r.id,
          titulo: r.titulo || 'Proposta Sem Título',
          subtitulo: r.subtitulo || '',
          tipo: r.tipo || 'casamento',
          cliente: clienteNome,
          cliente_nome: clienteNome,
          pasta_id: r.pasta_id || null,
          validade: r.validade || null,
          valor: valorNum,
          valor_total: valorNum,
          status: r.status || 'rascunho',
          criado_em: dataCriacao,
          created_at: dataCriacao,
          dados: dadosParsed,
        };
      });

      return res.status(200).json(formatadas);
    } catch (err: any) {
      console.error('Erro ao buscar propostas:', err);
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'POST') {
    try {
      const { titulo, subtitulo, tipo, cliente_nome, cliente_id, pasta_id, validade, valor_total, status, dados } = req.body || {};
      if (!titulo) return res.status(422).json({ erro: 'Título da proposta é obrigatório' });

      const id = generateId();
      const slug = (titulo || id)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '') + '-' + id.substring(0, 6);

      const dadosJson = typeof dados === 'object' ? JSON.stringify(dados) : (dados || '{}');
      const valTotalNum = parseFloat(valor_total || 0);

      await query(
        `INSERT INTO propostas (
          id, slug, titulo, subtitulo, tipo, cliente_id, cliente_nome, pasta_id, validade, valor_total, status, dados_json, criado_por
        ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13)`,
        [
          id,
          slug,
          titulo,
          subtitulo || '',
          tipo || 'casamento',
          cliente_id || null,
          cliente_nome || 'Cliente',
          pasta_id || null,
          validade || null,
          valTotalNum,
          status || 'enviada',
          dadosJson,
          user.id
        ]
      );
      await auditLog(user.id, 'CREATE', 'propostas', id, req.headers['x-forwarded-for'] as string || req.socket.remoteAddress);

      // Automação financeira ao aprovar proposta
      if ((status === 'aprovada' || status === 'aceita') && valTotalNum > 0) {
        try {
          const lancId = `lanc_prop_${id}`;
          const hojeStr = new Date().toISOString().split('T')[0];
          await query(
            `INSERT INTO lancamentos (
              id, tipo, descricao, valor, valor_pago, categoria, cliente_fornecedor, vencimento, status, observacao
            ) VALUES ($1, 'receber', $2, $3, 0.00, 'Serviços', $4, $5, 'pendente', $6)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)`,
            [lancId, `Fechamento: ${titulo}`, valTotalNum, cliente_nome || 'Cliente', hojeStr, `Ref. Proposta: ${id}`]
          );
        } catch (e) {}
      }

      return res.status(201).json({ ok: true, id, slug });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'PUT') {
    try {
      const { id, titulo, subtitulo, tipo, cliente_nome, cliente_id, validade, valor_total, status, dados } = req.body || {};
      if (!id) return res.status(422).json({ erro: 'ID da proposta é obrigatório' });
      if (!(await requireOwnership('propostas', id, user))) return res.status(403).json({ erro: 'Acesso negado: registro pertence a outro usuário' });

      const dadosJson = typeof dados === 'object' ? JSON.stringify(dados) : (dados || '{}');
      const valTotalNum = parseFloat(valor_total || 0);

      await auditLog(user.id, 'UPDATE', 'propostas', id, req.headers['x-forwarded-for'] as string || req.socket.remoteAddress);
      await query(
        `UPDATE propostas SET 
          titulo = $1, subtitulo = $2, tipo = $3, cliente_nome = $4, validade = $5, valor_total = $6, status = $7, dados_json = $8
        WHERE id = $9`,
        [
          titulo,
          subtitulo || '',
          tipo,
          cliente_nome,
          validade || null,
          valTotalNum,
          status,
          dadosJson,
          id
        ]
      );

      // Automação financeira ao aprovar proposta
      if ((status === 'aprovada' || status === 'aceita') && valTotalNum > 0) {
        try {
          const lancId = `lanc_prop_${id}`;
          const hojeStr = new Date().toISOString().split('T')[0];
          await query(
            `INSERT INTO lancamentos (
              id, tipo, descricao, valor, valor_pago, categoria, cliente_fornecedor, vencimento, status, observacao
            ) VALUES ($1, 'receber', $2, $3, 0.00, 'Serviços', $4, $5, 'pendente', $6)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)`,
            [lancId, `Fechamento: ${titulo}`, valTotalNum, cliente_nome || 'Cliente', hojeStr, `Ref. Proposta: ${id}`]
          );
        } catch (e) {}
      }

      return res.status(200).json({ ok: true });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'DELETE') {
    try {
      const { id } = req.query;
      if (!id) return res.status(422).json({ erro: 'ID da proposta é obrigatório' });
      if (!(await requireOwnership('propostas', String(id), user))) return res.status(403).json({ erro: 'Acesso negado: registro pertence a outro usuário' });
      await auditLog(user.id, 'DELETE', 'propostas', String(id), req.headers['x-forwarded-for'] as string || req.socket.remoteAddress);
      await query('DELETE FROM propostas WHERE id = $1', [id]);
      return res.status(200).json({ ok: true });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  return res.status(405).json({ erro: 'Método não permitido' });
});
