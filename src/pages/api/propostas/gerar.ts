import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { generateId } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';
import { gerarTextoSecao, melhorarObjetivo } from '@/lib/propostas/ia';
import { slugify, contatoResponsavel, parseDadosJson, dataMaisDias, dataISO } from '@/lib/propostas/admin-helpers';

/**
 * Port de api/propostas/gerar.php — cria uma nova proposta (com cliente/lead,
 * slug único, conteúdo via IA) e registra uma oportunidade no CRM.
 */
export default requireAuth(async (req: NextApiRequest, res: NextApiResponse) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  const d = req.body || {};
  if (!d.tipo) {
    return res.status(422).json({ erro: 'O tipo de serviço é obrigatório.' });
  }

  try {
    const tipo = String(d.tipo);
    const modoCliente = String(d.modo_cliente || 'cadastrado');
    let clienteId: string | null = null;
    let clienteNome = '';
    let responsavel = '';
    let isPlural = false;
    const oportunidadeId = d.oportunidade_id ? String(d.oportunidade_id) : null;

    let oportunidade: any = null;
    if (oportunidadeId) {
      oportunidade = await queryOne(`SELECT id, cliente_id, etapa FROM oportunidades WHERE id = $1`, [oportunidadeId]);
      if (!oportunidade) {
        return res.status(404).json({ erro: 'Oportunidade não encontrada.' });
      }
      if (oportunidade.cliente_id) clienteId = oportunidade.cliente_id;
    }

    // 1. Identificar ou Criar Cliente / Lead
    if (modoCliente === 'cadastrado') {
      if (!d.cliente_id && !clienteId) {
        return res.status(422).json({ erro: 'Selecione um cliente ou oportunidade.' });
      }
      if (d.cliente_id) clienteId = String(d.cliente_id);

      const cliente = await queryOne(`SELECT nome FROM clientes WHERE id = $1`, [clienteId]);
      if (!cliente) return res.status(404).json({ erro: 'Cliente não encontrado.' });
      clienteNome = cliente.nome;
      responsavel = '';
    } else {
      // MODO NOVO LEAD: criar pré-cadastro na tabela de clientes
      if (tipo !== 'casamento') {
        if (!d.empresa_nome || !d.responsavel) {
          return res.status(422).json({ erro: 'Nome da empresa e responsável são obrigatórios para novos leads.' });
        }
        clienteNome = String(d.empresa_nome);
        responsavel = String(d.responsavel);
      } else {
        clienteNome =
          d.nome_noivo && d.nome_noiva ? `${d.nome_noivo} & ${d.nome_noiva}` : 'Novo Casamento';

        const contatoTipo = d.contato_tipo || 'noiva';
        responsavel = contatoTipo === 'noivo' ? String(d.nome_noivo || '') : String(d.nome_noiva || '');
      }

      clienteId = generateId();
      const whatsappLead = d.whatsapp || '';
      const segmentoLead = tipo === 'casamento' ? 'Casamento' : tipo === 'marketing' ? 'Marketing' : 'Filmmaker';

      await query(
        `INSERT INTO clientes (id, nome, contato, segmento, criado_em) VALUES ($1, $2, $3, $4, CURRENT_TIMESTAMP)`,
        [clienteId, clienteNome, whatsappLead, segmentoLead]
      );

      if (responsavel.includes(',') || / e /i.test(responsavel) || tipo === 'casamento') {
        isPlural = true;
      }
    }

    if (oportunidadeId && oportunidade && ['novo', 'qualificado'].includes(oportunidade.etapa)) {
      await query(`UPDATE oportunidades SET etapa = 'proposta', atualizado_em = CURRENT_TIMESTAMP WHERE id = $1`, [
        oportunidadeId,
      ]);
    }

    // 2. Buscar Serviços (se houver)
    const servicosInclusos: any[] = [];
    if (Array.isArray(d.servicos)) {
      for (const item of d.servicos) {
        if (!item?.id) continue;
        const s = await queryOne(`SELECT id, nome, descricao FROM servicos WHERE id = $1`, [item.id]);
        if (s) {
          servicosInclusos.push({
            id: item.id,
            nome: s.nome,
            descricao: s.descricao,
            valor_individual: Number(item.valor ?? 0),
            tipo_cobranca: item.tipo_cobranca || 'recorrente',
            frequencia: item.frequencia || '',
          });
        }
      }
    }

    // 3. Gerar Slug Único
    const baseSlug = slugify(`${clienteNome}-${tipo}-${new Date().toISOString().slice(0, 10).replace(/-/g, '')}`);
    let slug = baseSlug;
    let i = 1;
    for (;;) {
      const existe = await queryOne(`SELECT id FROM propostas WHERE slug = $1 LIMIT 1`, [slug]);
      if (!existe) break;
      slug = `${baseSlug}-${i++}`;
    }

    // 4. Gerar Conteúdo via IA
    const secoes: Record<string, any> = {};
    const servicosStr = servicosInclusos.map((s) => s.nome).join(', ');

    try {
      const termoResponsavel = isPlural ? 'os responsáveis' : 'o responsável';
      const contexto = {
        cliente: clienteNome,
        responsavel,
        termo_responsavel: termoResponsavel,
        detalhes: d.briefing || '',
        servicos: servicosStr,
      };

      if (tipo === 'marketing') {
        secoes['desafio'] = await gerarTextoSecao('marketing', 'desafio', contexto);
        if (d.objetivo) {
          secoes['objetivo'] = await melhorarObjetivo(String(d.objetivo), contexto);
        }
      } else if (tipo === 'casamento') {
        secoes['visao'] = await gerarTextoSecao('casamento', 'visao', contexto);
      } else if (tipo === 'filmmaker') {
        secoes['visao'] = await gerarTextoSecao('filmmaker', 'visao', contexto);
      }
    } catch (e: any) {
      secoes['error'] = `IA Indisponível: ${e.message}`;
    }

    // 5. Salvar no Banco
    let mensagemPessoal = String(d.mensagem_pessoal || '').trim();
    if (tipo === 'casamento' && String(d.briefing || '').trim() !== '' && secoes['visao'] && !secoes['visao'].startsWith('Erro')) {
      const mensagemPadrao = 'Na Distinto, entendemos que o nosso papel';
      if (mensagemPessoal === '' || mensagemPessoal.startsWith(mensagemPadrao)) {
        mensagemPessoal = String(secoes['visao']).trim();
      }
    }
    if (tipo === 'casamento' && mensagemPessoal === '') {
      mensagemPessoal =
        'A gente sabe que fotografia é muito mais do que só apertar um botão. Nosso trabalho é capturar o que vocês sentem um pelo outro, de um jeito que pareça real e sem poses forçadas.';
    }

    const responsavelFinal = contatoResponsavel({
      tipo,
      contato_tipo: d.contato_tipo || 'noiva',
      nome_noivo: d.nome_noivo || '',
      nome_noiva: d.nome_noiva || '',
      responsavel,
    });

    const id = generateId();
    const dadosJson = JSON.stringify({
      secoes,
      servicos: servicosInclusos,
      briefing: d.briefing || '',
      objetivo_original: d.objetivo || '',
      data_inicio: d.data_inicio || dataISO(),
      meses_contrato: d.meses_contrato || 12,
      forma_pagamento: d.forma_pagamento || 'boleto_pix',
      adicional: {
        titulo: d.adicional_titulo || '',
        valor: d.adicional_valor || 0,
        descricao: d.adicional_descricao || '',
        fornecedor_id: d.adicional_fornecedor_id || '',
      },
      responsavel: responsavelFinal,
      whatsapp: d.whatsapp || '',
      is_plural: isPlural,
      nome_noivo: d.nome_noivo || '',
      nome_noiva: d.nome_noiva || '',
      data_casamento: d.data_casamento || '',
      data_limite_desconto: d.data_limite_desconto || '',
      condicao_especial: d.condicao_especial || '',
      valor_heritage: d.valor_heritage || '',
      itens_heritage: d.itens_heritage || '',
      valor_cinematic: d.valor_cinematic || '',
      itens_cinematic: d.itens_cinematic || '',
      valor_essencial: d.valor_essencial || '',
      itens_essencial: d.itens_essencial || '',
      valor_boudoir: d.valor_boudoir || '',
      valor_prewedding: d.valor_prewedding || '',
      atualizacoes_versao: d.atualizacoes_versao || '',
      andamento_proposta: d.andamento_proposta || '',
      mostrar_andamento_cliente: typeof d.mostrar_andamento_cliente !== 'undefined',
      versao_proposta: d.versao_proposta || 'v1',
      itens_personalizados:
        d.itens_personalizados || { heritage: [], cinematic: [], essencial: [] },
      mensagem_pessoal: mensagemPessoal,
      prazo_previas: d.prazo_previas || '',
      prazo_final: d.prazo_final || '',
      validade_proposta: d.validade_proposta || '',
      instagram_handle: d.instagram_handle || '',
      email_contato: d.email_contato || '',
      whatsapp_numero: d.whatsapp_numero || '',
      show_heritage: typeof d.show_heritage !== 'undefined',
      show_cinematic: typeof d.show_cinematic !== 'undefined',
      show_essencial: typeof d.show_essencial !== 'undefined',
      include_boudoir: typeof d.include_boudoir !== 'undefined',
      include_prewedding: typeof d.include_prewedding !== 'undefined',
      include_boudoir_heritage: typeof d.include_boudoir_heritage !== 'undefined',
      include_prewedding_heritage: typeof d.include_prewedding_heritage !== 'undefined',
      include_boudoir_cinematic: typeof d.include_boudoir_cinematic !== 'undefined',
      include_prewedding_cinematic: typeof d.include_prewedding_cinematic !== 'undefined',
      include_boudoir_essencial: typeof d.include_boudoir_essencial !== 'undefined',
      include_prewedding_essencial: typeof d.include_prewedding_essencial !== 'undefined',
      condicoes_reserva: d.condicoes_reserva || '',
      condicoes_heritage_cinematic: d.condicoes_heritage_cinematic || '',
      condicoes_essencial: d.condicoes_essencial || '',
      contato_tipo: d.contato_tipo || 'noiva',
      upgrades: d.upgrades || { heritage: [], cinematic: [], essencial: [] },
      pacote_dado_andamento: d.pacote_dado_andamento || '',
    });

    const validade = d.validade ? String(d.validade) : dataMaisDias(15);
    const tituloOriginal = d.titulo ? String(d.titulo) : `Proposta Comercial - ${clienteNome}`;
    const titulo = tituloOriginal;

    const valorTotal = decimalBrasileiro(d.valor_total ?? 0);

    await query(
      `INSERT INTO propostas (id, cliente_id, cliente_nome, tipo, slug, titulo, subtitulo, validade, dados_json, valor_total, status, oportunidade_id, pasta_id)
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, 'rascunho', $11, $12)`,
      [
        id,
        clienteId,
        clienteNome,
        tipo,
        slug,
        titulo,
        d.subtitulo || '',
        validade,
        dadosJson,
        valorTotal,
        oportunidadeId,
        d.pasta_id ? String(d.pasta_id) : null,
      ]
    );

    // --- CRIAÇÃO AUTOMÁTICA DE OPORTUNIDADE (CRM) ---
    if (!oportunidadeId) {
      const novaOportunidadeId = generateId();
      const previsao = dataMaisDias(tipo === 'casamento' ? 30 : 15);
      await query(
        `INSERT INTO oportunidades (id, cliente_id, nome, valor_estimado, etapa, previsao, responsavel, descricao, criado_em, atualizado_em)
         VALUES ($1, $2, $3, $4, 'proposta', $5, $6, $7, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)`,
        [
          novaOportunidadeId,
          clienteId,
          titulo,
          valorTotal,
          previsao,
          responsavelFinal || clienteNome,
          `Oportunidade criada automaticamente a partir da proposta ${id}`,
        ]
      );
      await query(`UPDATE propostas SET oportunidade_id = $1 WHERE id = $2`, [novaOportunidadeId, id]);
    }

    return res.status(201).json({ success: true, id, slug });
  } catch (err: any) {
    return res.status(500).json({ erro: err.message });
  }
});

function decimalBrasileiro(valor: any): number {
  let texto = String(valor ?? '').trim();
  if (texto === '') return 0;
  texto = texto.replace(/R\$/g, '').replace(/ /g, '');
  if (texto.includes(',') && texto.includes('.')) {
    texto = texto.replace(/\./g, '').replace(/,/g, '.');
  } else if (texto.includes(',')) {
    texto = texto.replace(/,/g, '.');
  }
  return Number(texto) || 0;
}