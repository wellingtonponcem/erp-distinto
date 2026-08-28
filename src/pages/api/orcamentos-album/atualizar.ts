import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const d = req.body;
    const id = d.id || '';

    if (!id) {
      return res.status(422).json({ erro: 'ID do orçamento é obrigatório.' });
    }

    const orcamento = await queryOne('SELECT * FROM orcamentos WHERE id = ?', [id]);
    if (!orcamento) {
      return res.status(404).json({ erro: 'Orçamento não encontrado.' });
    }

    const clienteNome = trim(d.cliente_nome || orcamento.cliente_nome);
    const titulo = trim(d.titulo || orcamento.titulo);
    const subtitulo = trim(d.subtitulo || orcamento.subtitulo);
    const tipo = trim(d.tipo || orcamento.tipo);
    const validade = d.validade || orcamento.validade;
    const valorTotal = parseFloat(d.valor_total ?? orcamento.valor_total) || 0;
    const status = trim(d.status || orcamento.status);

    const dadosJson = typeof d.dados_json === 'string'
      ? d.dados_json
      : typeof d.dados_json === 'object'
        ? JSON.stringify(d.dados_json, null, 2)
        : orcamento.dados_json;

    await query(
      `UPDATE orcamentos SET cliente_nome = ?, titulo = ?, subtitulo = ?, tipo = ?, validade = ?, valor_total = ?, status = ?, dados_json = ? WHERE id = ?`,
      [clienteNome, titulo, subtitulo, tipo, validade, valorTotal, status, dadosJson, id]
    );

    return res.status(200).json({
      success: true,
      mensagem: 'Orçamento atualizado com sucesso!',
      id,
      slug: orcamento.slug,
    });
  } catch (err: any) {
    console.error('Erro ao atualizar orçamento de álbum:', err);
    return res.status(500).json({ erro: err.message });
  }
});

function trim(v: any): string {
  return typeof v === 'string' ? v.trim() : '';
}
