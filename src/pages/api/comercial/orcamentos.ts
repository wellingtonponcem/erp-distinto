import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  const method = req.method;

  if (method === 'GET') {
    try {
      const rows = await query(`
        SELECT id, nome_contato, email, telefone, data_casamento, dados_json, status, criado_em
        FROM solicitacoes_orcamento
        ORDER BY criado_em DESC
      `);

      const formatadas = rows.map((r: any) => {
        let dadosParsed: any = {};
        try {
          dadosParsed = typeof r.dados_json === 'string' ? JSON.parse(r.dados_json) : (r.dados_json || {});
        } catch (e) {}

        let dataCasamento = r.data_casamento || '';
        if (dataCasamento && /^\d{4}-\d{2}-\d{2}$/.test(dataCasamento)) {
          const [y, m, d] = dataCasamento.split('-');
          dataCasamento = `${d}/${m}/${y}`;
        }

        return {
          id: r.id,
          nome_contato: r.nome_contato || '',
          email: r.email || '',
          telefone: r.telefone || '',
          data_casamento: dataCasamento,
          status: r.status || 'novo',
          criado_em: r.criado_em,
          dados: dadosParsed,
        };
      });

      return res.status(200).json(formatadas);
    } catch (err: any) {
      console.error('Erro ao buscar solicitações de orçamento:', err);
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'PATCH') {
    try {
      const { id, status } = req.body || {};
      if (!id) return res.status(422).json({ erro: 'Id é obrigatório' });

      const atual = await queryOne(`SELECT id FROM solicitacoes_orcamento WHERE id = $1 LIMIT 1`, [String(id)]);
      if (!atual) return res.status(404).json({ erro: 'Solicitação não encontrada' });

      const novoStatus = status === 'lido' ? 'lido' : status === 'arquivado' ? 'arquivado' : 'lido';
      await query(`UPDATE solicitacoes_orcamento SET status = $1 WHERE id = $2`, [novoStatus, String(id)]);
      return res.status(200).json({ ok: true, status: novoStatus });
    } catch (err: any) {
      console.error('Erro ao atualizar solicitação de orçamento:', err);
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'DELETE') {
    try {
      const { id } = req.query;
      if (!id) return res.status(422).json({ erro: 'Id é obrigatório' });
      await query(`DELETE FROM solicitacoes_orcamento WHERE id = $1`, [String(id)]);
      return res.status(200).json({ ok: true });
    } catch (err: any) {
      console.error('Erro ao excluir solicitação de orçamento:', err);
      return res.status(500).json({ erro: err.message });
    }
  }

  return res.status(405).json({ erro: 'Método não permitido' });
});
