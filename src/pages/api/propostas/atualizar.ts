import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { generateId } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';
import { recomendarProximoPasso } from '@/lib/propostas/ia';
import { contatoResponsavel, normalizarDataFormulario, dateBRFull, dateBR, parseDadosJson, decimalBrasileiro, dataISO } from '@/lib/propostas/admin-helpers';
import { formatarMoeda } from '@/lib/propostas/common';

/**
 * Port de api/propostas/atualizar.php — atualiza os dados editados de uma
 * proposta (incluindo processamento da escolha do casal feita pelo admin) e
 * retorna a recomendação de próximo passo via IA.
 */
export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  const d = req.body || {};
  if (!d.id) {
    return res.status(422).json({ erro: 'O ID da proposta é obrigatório.' });
  }

  try {
    const propostaAtual = await queryOne(`SELECT * FROM propostas WHERE id = $1`, [String(d.id)]);
    if (!propostaAtual) {
      return res.status(404).json({ erro: 'Proposta não encontrada.' });
    }

    const dadosAntigos = parseDadosJson(propostaAtual.dados_json);

    // 2. Processar Serviços
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
            valor_mensal: Number(item.valor_mensal ?? item.valor ?? 0),
            tipo_cobranca: item.tipo_cobranca || 'recorrente',
            frequencia: item.frequencia || '',
          });
        }
      }
    }

    // 3. Montar novo JSON de dados
    const secoes = { ...(dadosAntigos.secoes || {}) };
    if (Array.isArray(d.secoes)) {
      for (const [key, val] of Object.entries(d.secoes)) {
        secoes[key] = val;
      }
    } else if (d.secoes && typeof d.secoes === 'object') {
      Object.assign(secoes, d.secoes);
    }

    const fasesCronograma: any[] = [];
    if (Array.isArray(d.fases)) {
      for (const fase of d.fases) {
        if (!fase?.nome) continue;
        fasesCronograma.push({
          nome: String(fase.nome).trim(),
          dias: Math.max(0, parseInt(fase.dias ?? 0, 10) || 0),
          descricao: String(fase.descricao ?? '').trim(),
        });
      }
    }

    let clienteId = Object.prototype.hasOwnProperty.call(d, 'cliente_id')
      ? d.cliente_id || null
      : propostaAtual.cliente_id || null;
    let oportunidadeId = Object.prototype.hasOwnProperty.call(d, 'oportunidade_id')
      ? d.oportunidade_id || null
      : propostaAtual.oportunidade_id || null;

    if (oportunidadeId) {
      const oportunidade = await queryOne(`SELECT cliente_id FROM oportunidades WHERE id = $1`, [oportunidadeId]);
      if (!oportunidade) {
        return res.status(404).json({ erro: 'Oportunidade não encontrada.' });
      }
      if (oportunidade.cliente_id) clienteId = oportunidade.cliente_id;
    }

    // --- PROCESSAMENTO DA ESCOLHA DO CASAL (ADMIN / WHATSAPP) ---
    let clienteEscolha = dadosAntigos.cliente_escolha ?? null;
    const pacoteDadoAndamento = String(d.pacote_dado_andamento ?? '');
    const tipoProposta = d.tipo || propostaAtual.tipo;
    let valorTotal = d.valor_total ? decimalBrasileiro(d.valor_total) : Number(propostaAtual.valor_total) || 0;
    let andamentoProposta = d.andamento_proposta ?? dadosAntigos.andamento_proposta ?? '';
    const nomeNoivo = String(d.nome_noivo ?? dadosAntigos.nome_noivo ?? '').trim();
    const nomeNoiva = String(d.nome_noiva ?? dadosAntigos.nome_noiva ?? '').trim();
    const responsavelManual = String(d.responsavel ?? dadosAntigos.responsavel_manual ?? dadosAntigos.responsavel ?? '').trim();

    let clienteNomeAtualizado = String(propostaAtual.cliente_nome || '');
    if (tipoProposta === 'casamento') {
      clienteId = null;
      if (nomeNoivo !== '' && nomeNoiva !== '') {
        clienteNomeAtualizado = `${nomeNoivo} & ${nomeNoiva}`;
      } else if (nomeNoivo !== '' || nomeNoiva !== '') {
        clienteNomeAtualizado = nomeNoivo !== '' ? nomeNoivo : nomeNoiva;
      }
    } else {
      clienteNomeAtualizado = responsavelManual !== '' ? responsavelManual : (d.cliente_nome ?? clienteNomeAtualizado);
    }

    // Flags de visibilidade e upgrades estáticos
    let showHeritage = typeof d.show_heritage !== 'undefined';
    let showCinematic = typeof d.show_cinematic !== 'undefined';
    let showEssencial = typeof d.show_essencial !== 'undefined';

    let includeBoudoir = typeof d.include_boudoir !== 'undefined';
    let includePrewedding = typeof d.include_prewedding !== 'undefined';

    let includeBoudoirHeritage = typeof d.include_boudoir_heritage !== 'undefined';
    let includePreweddingHeritage = typeof d.include_prewedding_heritage !== 'undefined';
    let includeBoudoirCinematic = typeof d.include_boudoir_cinematic !== 'undefined';
    let includePreweddingCinematic = typeof d.include_prewedding_cinematic !== 'undefined';
    let includeBoudoirEssencial = typeof d.include_boudoir_essencial !== 'undefined';
    let includePreweddingEssencial = typeof d.include_prewedding_essencial !== 'undefined';

    const upgrades = d.upgrades ?? dadosAntigos.upgrades ?? { heritage: [], cinematic: [], essencial: [] };

    if (tipoProposta === 'casamento' && pacoteDadoAndamento !== '') {
      const planoId = pacoteDadoAndamento;
      const extras: string[] = [];
      const itensSelecionados: string[] = [];

      if (String(d.escolha_boudoir ?? '') === '1') {
        extras.push('boudoir_static');
        itensSelecionados.push('Boudoir da Noiva');
      }
      if (String(d.escolha_prewedding ?? '') === '1') {
        extras.push('prewedding_static');
        itensSelecionados.push('Ensaio Pré-Wedding');
      }

      const upgradesPost = d.escolha_upgrades ?? {};
      const extrasDinamicos: string[] = [];
      for (const [upgId, ativo] of Object.entries(upgradesPost)) {
        if (String(ativo) === '1') {
          extrasDinamicos.push(upgId);
          extras.push(upgId);
        }
      }

      if (extrasDinamicos.length > 0) {
        const placeholders = extrasDinamicos.map(() => '?').join(',');
        const rows = await query(`SELECT id, nome FROM servicos WHERE id IN (${placeholders}) AND categoria = 'wedding' AND ativo = 1`, extrasDinamicos);
        for (const extra of rows) {
          itensSelecionados.push(String(extra.nome));
        }
      }

      const valorTotalEscolha = decimalBrasileiro(d.escolha_valor_total ?? 0);

      // Se o plano mudou ou foi configurado agora, registrar no andamento
      const planoAnterior = dadosAntigos.cliente_escolha?.plano_id ?? '';
      if (planoId !== planoAnterior) {
        const nomePlano =
          planoId === 'heritage' ? 'Experiência Heritage' : planoId === 'cinematic' ? 'Experiência Cinematic' : 'Registro Essencial';
        let novaLinhaAndamento = `${dateBR()} | Administrador registrou fechamento no plano: ${nomePlano}`;
        if (itensSelecionados.length > 0) {
          novaLinhaAndamento += ` com upgrades (${itensSelecionados.join(', ')})`;
        }
        novaLinhaAndamento += ` | Investimento: ${formatarMoeda(valorTotalEscolha)} | Registrado via painel administrativo`;

        andamentoProposta =
          String(andamentoProposta).trim() !== ''
            ? String(andamentoProposta).trim() + '\n' + novaLinhaAndamento
            : novaLinhaAndamento;

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
          const conteudoHist = `Administrador registrou fechamento no plano ${planoId} com investimento total de ${formatarMoeda(valorTotalEscolha)}.`;
          await query(
            `INSERT INTO propostas_historico (proposta_id, user_id, tipo, conteudo) VALUES ($1, $2, 'escolha_admin', $3)`,
            [d.id, user.id || 'admin', conteudoHist]
          );
        } catch (e) {}
      }

      clienteEscolha = {
        plano_id: planoId,
        extras,
        itens_selecionados: itensSelecionados,
        valor_total: valorTotalEscolha,
        condicoes: d.escolha_condicoes ?? '',
        selecionado_em: dadosAntigos.cliente_escolha?.selecionado_em ?? dateBRFull(),
        whatsapp_fechamento: true,
      };

      valorTotal = valorTotalEscolha;

      showHeritage = planoId === 'heritage';
      showCinematic = planoId === 'cinematic';
      showEssencial = planoId === 'essencial';

      includeBoudoir = extras.includes('boudoir_static');
      includePrewedding = extras.includes('prewedding_static');

      includeBoudoirHeritage = planoId === 'heritage' && includeBoudoir;
      includePreweddingHeritage = planoId === 'heritage' && includePrewedding;
      includeBoudoirCinematic = planoId === 'cinematic' && includeBoudoir;
      includePreweddingCinematic = planoId === 'cinematic' && includePrewedding;
      includeBoudoirEssencial = planoId === 'essencial' && includeBoudoir;
      includePreweddingEssencial = planoId === 'essencial' && includePrewedding;

      upgrades[planoId] = {};
      for (const upgId of extrasDinamicos) {
        upgrades[planoId][upgId] = true;
      }
    }

    const dadosJson = JSON.stringify({
      secoes,
      servicos: servicosInclusos,
      fases_cronograma: fasesCronograma.length > 0 ? fasesCronograma : dadosAntigos.fases_cronograma ?? [],
      briefing: d.briefing ?? dadosAntigos.briefing ?? '',
      objetivo_original: d.objetivo ?? dadosAntigos.objetivo_original ?? '',
      data_inicio: d.data_inicio ?? dadosAntigos.data_inicio ?? dataISO(),
      meses_contrato: d.meses_contrato ?? dadosAntigos.meses_contrato ?? 12,
      forma_pagamento: d.forma_pagamento ?? dadosAntigos.forma_pagamento ?? 'boleto_pix',
      adicional: {
        titulo: d.adicional_titulo ?? dadosAntigos.adicional?.titulo ?? '',
        valor: d.adicional_valor ?? dadosAntigos.adicional?.valor ?? 0,
        descricao: d.adicional_descricao ?? dadosAntigos.adicional?.descricao ?? '',
        fornecedor_id: d.adicional_fornecedor_id ?? dadosAntigos.adicional?.fornecedor_id ?? '',
      },
      responsavel: contatoResponsavel({
        tipo: d.tipo || propostaAtual.tipo,
        contato_tipo: d.contato_tipo ?? dadosAntigos.contato_tipo ?? 'noiva',
        nome_noivo: nomeNoivo,
        nome_noiva: nomeNoiva,
        responsavel: responsavelManual,
      }),
      responsavel_manual: responsavelManual,
      whatsapp: d.whatsapp ?? dadosAntigos.whatsapp ?? '',
      is_plural: dadosAntigos.is_plural ?? false,
      etapas_ativas: d.etapas_ativas ?? dadosAntigos.etapas_ativas ?? [],
      etapas_dias: d.etapas_dias ?? dadosAntigos.etapas_dias ?? [],
      nome_noivo: nomeNoivo,
      nome_noiva: nomeNoiva,
      data_casamento: normalizarDataFormulario(d.data_casamento ?? dadosAntigos.data_casamento ?? ''),
      data_limite_desconto: normalizarDataFormulario(d.data_limite_desconto ?? dadosAntigos.data_limite_desconto ?? ''),
      condicao_especial: d.condicao_especial ?? dadosAntigos.condicao_especial ?? '',
      valor_heritage: d.valor_heritage ?? dadosAntigos.valor_heritage ?? '',
      itens_heritage: d.itens_heritage ?? dadosAntigos.itens_heritage ?? '',
      valor_cinematic: d.valor_cinematic ?? dadosAntigos.valor_cinematic ?? '',
      itens_cinematic: d.itens_cinematic ?? dadosAntigos.itens_cinematic ?? '',
      valor_essencial: d.valor_essencial ?? dadosAntigos.valor_essencial ?? '',
      itens_essencial: d.itens_essencial ?? dadosAntigos.itens_essencial ?? '',
      valor_boudoir: d.valor_boudoir ?? dadosAntigos.valor_boudoir ?? '',
      valor_prewedding: d.valor_prewedding ?? dadosAntigos.valor_prewedding ?? '',
      atualizacoes_versao: d.atualizacoes_versao ?? dadosAntigos.atualizacoes_versao ?? '',
      andamento_proposta: andamentoProposta,
      mostrar_andamento_cliente: typeof d.mostrar_andamento_cliente !== 'undefined',
      versao_proposta: d.versao_proposta ?? dadosAntigos.versao_proposta ?? 'v1',
      itens_personalizados: d.itens_personalizados ?? dadosAntigos.itens_personalizados ?? { heritage: [], cinematic: [], essencial: [] },
      mensagem_pessoal: d.mensagem_pessoal ?? dadosAntigos.mensagem_pessoal ?? '',
      prazo_previas: d.prazo_previas ?? dadosAntigos.prazo_previas ?? '',
      prazo_final: d.prazo_final ?? dadosAntigos.prazo_final ?? '',
      validade_proposta: d.validade_proposta ?? dadosAntigos.validade_proposta ?? '',
      instagram_handle: d.instagram_handle ?? dadosAntigos.instagram_handle ?? '',
      email_contato: d.email_contato ?? dadosAntigos.email_contato ?? '',
      whatsapp_numero: d.whatsapp_numero ?? dadosAntigos.whatsapp_numero ?? '',
      show_heritage: showHeritage,
      show_cinematic: showCinematic,
      show_essencial: showEssencial,
      include_boudoir: includeBoudoir,
      include_prewedding: includePrewedding,
      include_boudoir_heritage: includeBoudoirHeritage,
      include_prewedding_heritage: includePreweddingHeritage,
      include_boudoir_cinematic: includeBoudoirCinematic,
      include_prewedding_cinematic: includePreweddingCinematic,
      include_boudoir_essencial: includeBoudoirEssencial,
      include_prewedding_essencial: includePreweddingEssencial,
      condicoes_reserva: d.condicoes_reserva ?? dadosAntigos.condicoes_reserva ?? '',
      condicoes_heritage_cinematic: d.condicoes_heritage_cinematic ?? dadosAntigos.condicoes_heritage_cinematic ?? '',
      condicoes_essencial: d.condicoes_essencial ?? dadosAntigos.condicoes_essencial ?? '',
      contato_tipo: d.contato_tipo ?? dadosAntigos.contato_tipo ?? 'noiva',
      upgrades,
      pacote_dado_andamento: pacoteDadoAndamento,
      cliente_escolha: clienteEscolha,
    });

    // 4. Atualizar no Banco
    let valorTotalFinal =
      tipoProposta === 'casamento' && pacoteDadoAndamento !== ''
        ? valorTotal
        : d.valor_total
        ? decimalBrasileiro(d.valor_total)
        : Number(propostaAtual.valor_total) || 0;
    const status = d.status || propostaAtual.status;

    await query(
      `UPDATE propostas SET
        cliente_nome = $1, titulo = $2, subtitulo = $3, validade = $4, dados_json = $5,
        valor_total = $6, status = $7, cliente_id = $8, oportunidade_id = $9
       WHERE id = $10`,
      [
        clienteNomeAtualizado,
        d.titulo ?? propostaAtual.titulo,
        d.subtitulo ?? propostaAtual.subtitulo,
        d.validade ?? propostaAtual.validade,
        dadosJson,
        valorTotalFinal,
        status,
        clienteId,
        oportunidadeId,
        d.id,
      ]
    );

    // --- ASSOCIAÇÃO AUTOMÁTICA FINANCEIRA ---
    if (status === 'aceita' && clienteId) {
      const checkObs = `Ref. Proposta: ${d.id}`;
      const lancExistente = await queryOne(`SELECT id FROM lancamentos WHERE observacao LIKE $1 LIMIT 1`, [`%${checkObs}%`]);
      if (!lancExistente) {
        const idLancamento = generateId();
        const desc = `Fechamento: ${d.titulo ?? propostaAtual.titulo}`;
        const venc = dataISO();
        const cliente = await queryOne(`SELECT nome FROM clientes WHERE id = $1`, [clienteId]);
        const clienteNome = cliente ? cliente.nome : propostaAtual.cliente_nome;

        await query(
          `INSERT INTO lancamentos (
            id, tipo, descricao, valor, valor_pago, categoria, cliente_fornecedor, cliente_id, vencimento, status, modalidade, observacao, criado_em
          ) VALUES ($1, 'receber', $2, $3, 0, 'serviços', $4, $5, $6, 'pendente', 'avista', $7, NOW())`,
          [idLancamento, desc, valorTotalFinal, clienteNome, clienteId, venc, `Gerado automaticamente. ${checkObs}`]
        );
      }
    }

    // Recomendação de próximo passo via IA
    let recomendacao = '';
    try {
      const historico = await query(
        `SELECT tipo, conteudo, created_at FROM propostas_historico WHERE proposta_id = $1 ORDER BY created_at DESC LIMIT 3`,
        [d.id]
      );
      const propostaParaIA = {
        cliente_nome: propostaAtual.cliente_nome,
        tipo: propostaAtual.tipo,
        status,
        titulo: d.titulo ?? propostaAtual.titulo,
        dados_json: dadosJson,
      };
      recomendacao = await recomendarProximoPasso(propostaParaIA, historico);
    } catch (e) {
      recomendacao = '';
    }

    return res.status(200).json({
      success: true,
      id: d.id,
      slug: propostaAtual.slug,
      recomendacao,
    });
  } catch (err: any) {
    return res.status(500).json({ erro: err.message });
  }
});