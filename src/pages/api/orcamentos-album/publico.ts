import { NextApiRequest, NextApiResponse } from 'next';
import { query, queryOne } from '@/lib/db';

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method !== 'GET') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const slug = (req.query.slug as string) || '';

    if (!slug) {
      return res.status(422).json({ erro: 'Slug não informado.' });
    }

    const orcamento = await queryOne('SELECT * FROM orcamentos WHERE slug = ?', [slug]);
    if (!orcamento) {
      return res.status(404).json({ erro: 'Orçamento não encontrado ou expirado.' });
    }

    let dados: any = {};
    try {
      dados = typeof orcamento.dados_json === 'string'
        ? JSON.parse(orcamento.dados_json)
        : (orcamento.dados_json || {});
    } catch (e) {}

    // Enriquecer coleções com dados atualizados da tabela servicos
    let colecoes = dados.colecao_albuns || [];
    try {
      const servicosLive = await query('SELECT * FROM servicos WHERE ativo = 1');
      const liveMap: Record<string, any> = {};

      for (const sl of servicosLive) {
        liveMap[sl.id] = sl;
        liveMap[`srv_${sl.id}`] = sl;
        const cleanName = String(sl.nome || '').toLowerCase().trim();
        liveMap[cleanName] = sl;
      }

      for (const col of colecoes) {
        const colId = col.id || '';
        const colNomeKey = String(col.nome_comercial || col.nome || '').toLowerCase().trim();
        const sl = liveMap[colId] || liveMap[`srv_${colId}`] || liveMap[colNomeKey] || null;

        if (sl) {
          col.nome_comercial = sl.nome;
          if (sl.descricao) col.descricao = sl.descricao;
          if (sl.preco_venda && parseFloat(sl.preco_venda) > 0) {
            col.investimento_cliente = parseFloat(sl.preco_venda);
          }
          if (sl.valor_lamina_extra !== undefined && sl.valor_lamina_extra !== '') {
            col.valor_lamina_extra = parseFloat(sl.valor_lamina_extra);
          }
          if (sl.acabamento_json) {
            col.acabamento_json = sl.acabamento_json;
            col.acabamento_detalhado = typeof sl.acabamento_json === 'string' ? JSON.parse(sl.acabamento_json) : sl.acabamento_json;
            col.acabamentos_lista_fotos = col.acabamento_detalhado;
          }
          if (sl.estojo_json) {
            col.estojo_json = sl.estojo_json;
            col.estojo = typeof sl.estojo_json === 'string' ? JSON.parse(sl.estojo_json) : sl.estojo_json;
          }
          if (sl.imagens_json) {
            col.imagens_json = sl.imagens_json;
            col.imagens = typeof sl.imagens_json === 'string' ? JSON.parse(sl.imagens_json) : sl.imagens_json;
          }
          if (sl.categoria_original) {
            col.categoria_original = sl.categoria_original;
          }
        }
      }
    } catch (e) {}

    // Configurações da empresa para WhatsApp
    let configEmpresa: any = {};
    try {
      configEmpresa = await queryOne('SELECT * FROM configuracao_empresa WHERE id = ? LIMIT 1', ['principal']) || {};
    } catch (e) {}

    let telEmpresa = String(configEmpresa.telefone || '5527988586935').replace(/\D/g, '');
    if (!telEmpresa.startsWith('55') && telEmpresa.length >= 10) {
      telEmpresa = '55' + telEmpresa;
    }

    return res.status(200).json({
      orcamento: {
        id: orcamento.id,
        cliente_nome: orcamento.cliente_nome,
        titulo: orcamento.titulo,
        subtitulo: orcamento.subtitulo || '',
        tipo: orcamento.tipo,
        slug: orcamento.slug,
        validade: orcamento.validade,
        valor_total: orcamento.valor_total || 0,
        status: orcamento.status,
        created_at: orcamento.created_at,
      },
      dados: {
        ...dados,
        colecao_albuns: colecoes,
        galeria_acabamentos: dados.galeria_acabamentos || [],
        configuracao_geral: dados.configuracao_geral || {},
      },
      whatsapp_empresa: telEmpresa,
    });
  } catch (err: any) {
    console.error('Erro ao buscar orçamento público:', err);
    return res.status(500).json({ erro: err.message });
  }
}
