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

    const orcamento = await queryOne('SELECT * FROM orcamentos_b2b WHERE slug = ?', [slug]);
    if (!orcamento) {
      return res.status(404).json({ erro: 'Orçamento não encontrado.' });
    }

    let dados: any = {};
    try {
      dados = typeof orcamento.dados_json === 'string'
        ? JSON.parse(orcamento.dados_json)
        : (orcamento.dados_json || {});
    } catch (e) {}

    // Registrar aprovacao
    dados.aprovacao = {
      data: new Date().toISOString().slice(0, 19).replace('T', ' '),
      cliente_nome: d.cliente_nome || orcamento.cliente_nome,
      telefone: d.telefone || '',
      observacoes: d.observacoes || '',
    };

    await query(
      `UPDATE orcamentos_b2b SET status = 'aprovado', dados_json = ? WHERE id = ?`,
      [JSON.stringify(dados, null, 2), orcamento.id]
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
      '*Aprovacao de Orcamento B2B!*',
      '',
      `Cliente: *${nomeCliente}*`,
      orcamento.cliente_empresa ? `Empresa: *${orcamento.cliente_empresa}*` : '',
      `Orcamento: *${orcamento.titulo}*`,
      `Investimento: *R$ ${orcamento.valor_total.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}*`,
      d.observacoes ? `\nObservacoes: ${d.observacoes}` : '',
      '',
      `Link: ${process.env.NEXT_PUBLIC_APP_URL || ''}/b2b/${slug}`,
    ].filter(Boolean).join('\n');

    const urlWhats = `https://wa.me/${telEmpresa}?text=${encodeURIComponent(msgWhats)}`;

    return res.status(200).json({
      success: true,
      mensagem: 'Orçamento aprovado com sucesso!',
      whatsapp_url: urlWhats,
    });
  } catch (err: any) {
    console.error('Erro ao processar aprovacao B2B:', err);
    return res.status(500).json({ erro: err.message });
  }
}
