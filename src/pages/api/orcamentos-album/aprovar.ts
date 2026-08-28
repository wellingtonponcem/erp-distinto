import { NextApiRequest, NextApiResponse } from 'next';
import { query, queryOne } from '@/lib/db';

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const d = req.body;
    const slug = d.slug || '';

    if (!slug) {
      return res.status(422).json({ erro: 'Slug do orçamento não informado.' });
    }

    const orcamento = await queryOne('SELECT * FROM orcamentos WHERE slug = ?', [slug]);
    if (!orcamento) {
      return res.status(404).json({ erro: 'Orçamento não encontrado.' });
    }

    let dados: any = {};
    try {
      dados = typeof orcamento.dados_json === 'string'
        ? JSON.parse(orcamento.dados_json)
        : (orcamento.dados_json || {});
    } catch (e) {}

    // Registrar escolha do cliente no JSON
    dados.aprovacao = {
      data: new Date().toISOString().slice(0, 19).replace('T', ' '),
      cliente_nome: d.cliente_nome || orcamento.cliente_nome,
      telefone: d.telefone || '',
      colecao_id: d.colecao_id || '',
      colecao_nome: d.colecao_nome || '',
      laminas_extras: d.laminas_extras || 0,
      valor_total: d.valor_total || orcamento.valor_total,
      observacoes: d.observacoes || '',
    };

    const valorTotalFinal = parseFloat(d.valor_total ?? orcamento.valor_total) || 0;

    await query(
      `UPDATE orcamentos SET status = 'aprovado', valor_total = ?, dados_json = ? WHERE id = ?`,
      [valorTotalFinal, JSON.stringify(dados, null, 2), orcamento.id]
    );

    // Buscar telefone da empresa para WhatsApp
    let configEmpresa: any = {};
    try {
      configEmpresa = await queryOne('SELECT * FROM configuracao_empresa WHERE id = ? LIMIT 1', ['principal']) || {};
    } catch (e) {}

    let telEmpresa = String(configEmpresa.telefone || '5527988586935').replace(/\D/g, '');
    if (!telEmpresa.startsWith('55') && telEmpresa.length >= 10) {
      telEmpresa = '55' + telEmpresa;
    }

    const nomeCliente = d.cliente_nome || orcamento.cliente_nome;
    const msgWhats = [
      '🎉 *Aprovação de Orçamento!*',
      '',
      `Cliente: *${nomeCliente}*`,
      `Orçamento: *${orcamento.titulo}*`,
      `Coleção Escolhida: *${d.colecao_nome || 'Coleção Base'}*`,
      `Lâminas Extras: +${d.laminas_extras || 0}`,
      `Investimento Total: *R$ ${valorTotalFinal.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}*`,
      d.observacoes ? `\nObservações: ${d.observacoes}` : '',
      '',
      `Link: ${process.env.NEXT_PUBLIC_APP_URL || ''}/o/${slug}`,
    ].filter(Boolean).join('\n');

    const urlWhats = `https://wa.me/${telEmpresa}?text=${encodeURIComponent(msgWhats)}`;

    return res.status(200).json({
      success: true,
      mensagem: 'Orçamento aprovado com sucesso!',
      whatsapp_url: urlWhats,
    });
  } catch (err: any) {
    console.error('Erro ao processar aprovação de orçamento:', err);
    return res.status(500).json({ erro: err.message });
  }
}
