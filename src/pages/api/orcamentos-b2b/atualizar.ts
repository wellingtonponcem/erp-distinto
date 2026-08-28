import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';

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
    const id = d.id || '';

    if (!id) {
      return res.status(422).json({ erro: 'ID do orçamento é obrigatório.' });
    }

    const orcamento = await queryOne('SELECT * FROM orcamentos_b2b WHERE id = ?', [id]);
    if (!orcamento) {
      return res.status(404).json({ erro: 'Orçamento não encontrado.' });
    }

    const clienteNome = String(d.cliente_nome ?? orcamento.cliente_nome).trim();
    const clienteEmpresa = String(d.cliente_empresa ?? orcamento.cliente_empresa).trim();
    const titulo = String(d.titulo ?? orcamento.titulo).trim();
    const validade = fixValidadeYear(d.validade ?? orcamento.validade);
    const valorTotal = parseFloat(d.valor_total ?? orcamento.valor_total) || 0;
    const status = String(d.status ?? orcamento.status).trim();

    const dadosJson = typeof d.dados_json === 'string'
      ? d.dados_json
      : typeof d.dados_json === 'object'
        ? JSON.stringify(d.dados_json, null, 2)
        : orcamento.dados_json;

    await query(
      `UPDATE orcamentos_b2b SET cliente_nome = ?, cliente_empresa = ?, titulo = ?, validade = ?, valor_total = ?, status = ?, dados_json = ? WHERE id = ?`,
      [clienteNome, clienteEmpresa, titulo, validade, valorTotal, status, dadosJson, id]
    );

    return res.status(200).json({
      success: true,
      mensagem: 'Orçamento atualizado com sucesso!',
      id,
      slug: orcamento.slug,
    });
  } catch (err: any) {
    console.error('Erro ao atualizar orcamento B2B:', err);
    return res.status(500).json({ erro: err.message });
  }
});
