import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';
import { generateId } from '@/lib/helpers';
import { renderMasterContractHtml } from '@/lib/contract-template';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse, user) => {
  const method = req.method;

  if (method === 'GET') {
    try {
      const rows = await query(`
        SELECT c.*, p.slug as proposta_slug, p.titulo as proposta_titulo
        FROM contratos c
        LEFT JOIN propostas p ON c.proposta_id = p.id
        ORDER BY c.id DESC
      `);

      const formatados = rows.map((r: any) => {
        let dadosParsed: any = {};
        try {
          dadosParsed = typeof r.dados_json === 'string' ? JSON.parse(r.dados_json) : (r.dados_json || {});
        } catch (e) {}

        const clienteNome = r.cliente_nome || r.cliente || 'Cliente Contratante';
        const dataCriacao = r.criado_em || r.created_at;
        const valTotalNum = parseFloat(r.valor_total || r.valor || 0);

        // Se não tiver contrato_texto pronto, gerar com o template master oficial
        if (!dadosParsed.contrato_texto) {
          dadosParsed.contrato_texto = renderMasterContractHtml({
            id: r.id,
            titulo: r.titulo,
            cliente_nome: clienteNome,
            cliente_cpf_cnpj: r.cliente_cpf_cnpj || dadosParsed.signatario_1?.cpf || '',
            cliente_email: r.cliente_email || dadosParsed.signatario_1?.email || '',
            cliente_telefone: r.cliente_telefone || dadosParsed.signatario_1?.telefone || '',
            noivo_nome: dadosParsed.signatario_1?.nome || '',
            noivo_cpf: dadosParsed.signatario_1?.cpf || '',
            noivo_email: dadosParsed.signatario_1?.email || '',
            noivo_telefone: dadosParsed.signatario_1?.telefone || '',
            noiva_nome: dadosParsed.signatario_2?.nome || '',
            noiva_cpf: dadosParsed.signatario_2?.cpf || '',
            noiva_email: dadosParsed.signatario_2?.email || '',
            noiva_telefone: dadosParsed.signatario_2?.telefone || '',
            valor_total: valTotalNum,
            condicoes_pagamento: dadosParsed.forma_pagamento,
            clausulas_personalizadas: dadosParsed.clausulas
          });
        }

        return {
          id: r.id,
          proposta_id: r.proposta_id || null,
          proposta_slug: r.proposta_slug || null,
          proposta_titulo: r.proposta_titulo || null,
          cliente_id: r.cliente_id || null,
          titulo: r.titulo || `Contrato - ${clienteNome}`,
          cliente_nome: clienteNome,
          cliente_cpf_cnpj: r.cliente_cpf_cnpj || dadosParsed.signatario_1?.cpf || '',
          cliente_email: r.cliente_email || dadosParsed.signatario_1?.email || '',
          cliente_telefone: r.cliente_telefone || dadosParsed.signatario_1?.telefone || '',
          valor_total: valTotalNum,
          status: r.status || 'rascunho',
          assinafy_document_id: r.assinafy_document_id || r.documento_assinatura_id || null,
          assinafy_status: r.assinafy_status || null,
          link_assinatura: r.link_assinatura || dadosParsed.link_assinatura || null,
          asaas_cobranca_gerada: r.asaas_cobranca_gerada || 0,
          criado_em: dataCriacao,
          created_at: dataCriacao,
          dados: dadosParsed,
        };
      });

      return res.status(200).json(formatados);
    } catch (err: any) {
      console.error('Erro ao buscar contratos:', err);
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'POST') {
    try {
      const { action } = req.body || {};

      // AÇÃO 1: CLONAR CONTRATO
      if (action === 'clonar') {
        const { id } = req.body;
        if (!id) return res.status(422).json({ erro: 'ID do contrato original é obrigatório' });

        const original = await queryOne('SELECT * FROM contratos WHERE id = $1 LIMIT 1', [id]);
        if (!original) return res.status(404).json({ erro: 'Contrato original não encontrado' });

        const novoId = generateId();
        const novoTitulo = `${original.titulo || 'Contrato'} - Cópia`;

        await query(
          `INSERT INTO contratos (
            id, proposta_id, cliente_id, titulo, cliente_nome, cliente_cpf_cnpj, cliente_email, cliente_telefone, valor_total, status, dados_json
          ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, 'rascunho', $10)`,
          [
            novoId,
            original.proposta_id || null,
            original.cliente_id || null,
            novoTitulo,
            original.cliente_nome || 'Cliente Contratante',
            original.cliente_cpf_cnpj || '',
            original.cliente_email || '',
            original.cliente_telefone || '',
            original.valor_total || 0,
            original.dados_json || '{}'
          ]
        );

        return res.status(201).json({ ok: true, id: novoId, mensagem: 'Contrato clonado com sucesso!' });
      }

      // AÇÃO 2: REVERTER PARA RASCUNHO (RESETAR)
      if (action === 'resetar') {
        const { id } = req.body;
        if (!id) return res.status(422).json({ erro: 'ID do contrato é obrigatório' });

        await query("UPDATE contratos SET status = 'rascunho' WHERE id = $1", [id]);
        return res.status(200).json({ ok: true, mensagem: 'Contrato revertido para Rascunho com sucesso!' });
      }

      // AÇÃO 3: GERAR COBRANÇA ASAAS PARA O CONTRATO
      if (action === 'gerar_asaas') {
        const { id, parcelas, vencimento, formaPagamento } = req.body;
        if (!id) return res.status(422).json({ erro: 'ID do contrato é obrigatório' });

        const c = await queryOne('SELECT * FROM contratos WHERE id = $1 LIMIT 1', [id]);
        if (!c) return res.status(404).json({ erro: 'Contrato não encontrado' });

        const valNum = parseFloat(c.valor_total || 0);
        if (valNum <= 0) return res.status(400).json({ erro: 'Contrato sem valor para gerar cobrança' });

        const clienteNome = c.cliente_nome || 'Cliente Contratante';
        const hojeStr = new Date().toISOString().split('T')[0];
        const venc = vencimento || hojeStr;
        const totalParc = parseInt(parcelas || '1');
        const valorParc = valNum / totalParc;

        for (let i = 1; i <= totalParc; i++) {
          const lancId = `lanc_contrato_${c.id}_p${i}`;
          const desc = totalParc > 1 ? `Contrato (${i}/${totalParc}): ${c.titulo}` : `Contrato: ${c.titulo}`;

          await query(
            `INSERT INTO lancamentos (
              id, tipo, descricao, valor, valor_pago, categoria, cliente_fornecedor, vencimento, status, observacao, total_parcelas, parcela_atual, forma_pagamento
            ) VALUES ($1, 'receber', $2, $3, 0.00, 'Serviços', $4, $5, 'pendente', $6, $7, $8, $9)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)`,
            [lancId, desc, valorParc, clienteNome, venc, `Ref. Contrato: ${c.id}`, totalParc, i, formaPagamento || 'PIX']
          );
        }

        await query('UPDATE contratos SET asaas_cobranca_gerada = 1 WHERE id = $1', [c.id]);

        return res.status(200).json({ ok: true, mensagem: `Cobrança de R$ ${valNum.toLocaleString('pt-BR')} gerada no financeiro (${totalParc}x)!` });
      }

      // AÇÃO PADRÃO: CRIAR NOVO CONTRATO
      const {
        proposta_id,
        cliente_id,
        titulo,
        cliente_nome,
        cliente_cpf_cnpj,
        cliente_email,
        cliente_telefone,
        valor_total,
        status,
        dados
      } = req.body || {};

      if (!titulo && !cliente_nome) {
        return res.status(422).json({ erro: 'Título ou Nome do Cliente é obrigatório' });
      }

      const id = generateId();
      const valNum = parseFloat(valor_total || 0);
      const titleFinal = titulo || `Contrato de Prestação de Serviços - ${cliente_nome || 'Cliente'}`;

      let dadosObj = typeof dados === 'object' ? { ...dados } : {};
      if (!dadosObj.contrato_texto) {
        dadosObj.contrato_texto = renderMasterContractHtml({
          id,
          titulo: titleFinal,
          cliente_nome: cliente_nome || 'Cliente Contratante',
          cliente_cpf_cnpj,
          cliente_email,
          cliente_telefone,
          noivo_nome: dadosObj.signatario_1?.nome || '',
          noivo_cpf: dadosObj.signatario_1?.cpf || '',
          noivo_email: dadosObj.signatario_1?.email || '',
          noivo_telefone: dadosObj.signatario_1?.telefone || '',
          noiva_nome: dadosObj.signatario_2?.nome || '',
          noiva_cpf: dadosObj.signatario_2?.cpf || '',
          noiva_email: dadosObj.signatario_2?.email || '',
          noiva_telefone: dadosObj.signatario_2?.telefone || '',
          valor_total: valNum,
          condicoes_pagamento: dadosObj.forma_pagamento,
          clausulas_personalizadas: dadosObj.clausulas
        });
      }

      const dadosJson = JSON.stringify(dadosObj);

      await query(
        `INSERT INTO contratos (
          id, proposta_id, cliente_id, titulo, cliente_nome, cliente_cpf_cnpj, cliente_email, cliente_telefone, valor_total, status, dados_json
        ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)`,
        [
          id,
          proposta_id || null,
          cliente_id || null,
          titleFinal,
          cliente_nome || 'Cliente Contratante',
          cliente_cpf_cnpj || '',
          cliente_email || '',
          cliente_telefone || '',
          valNum,
          status || 'rascunho',
          dadosJson
        ]
      );

      if (status === 'assinado' || status === 'aceita') {
        if (proposta_id) {
          try {
            await query(`UPDATE propostas SET status = 'aprovada' WHERE id = $1`, [proposta_id]);
          } catch (e) {}
        }
        if (valNum > 0) {
          try {
            const lancId = `lanc_contrato_${id}`;
            const hojeStr = new Date().toISOString().split('T')[0];
            await query(
              `INSERT INTO lancamentos (
                id, tipo, descricao, valor, valor_pago, categoria, cliente_fornecedor, vencimento, status, observacao
              ) VALUES ($1, 'receber', $2, $3, 0.00, 'Serviços', $4, $5, 'pendente', $6)
              ON DUPLICATE KEY UPDATE valor = VALUES(valor)`,
              [lancId, `Contrato: ${titleFinal}`, valNum, cliente_nome || 'Cliente', hojeStr, `Ref. Contrato: ${id}`]
            );
          } catch (e) {}
        }
      }

      return res.status(201).json({ ok: true, id });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'PUT') {
    try {
      const {
        id,
        proposta_id,
        titulo,
        cliente_nome,
        cliente_cpf_cnpj,
        cliente_email,
        cliente_telefone,
        valor_total,
        status,
        dados,
        assinafy_document_id,
        assinafy_status,
      } = req.body || {};

      if (!id) return res.status(422).json({ erro: 'ID do contrato é obrigatório' });

      const valNum = parseFloat(valor_total || 0);
      let dadosObj = typeof dados === 'object' ? { ...dados } : {};
      if (!dadosObj.contrato_texto) {
        dadosObj.contrato_texto = renderMasterContractHtml({
          id,
          titulo,
          cliente_nome,
          cliente_cpf_cnpj,
          cliente_email,
          cliente_telefone,
          noivo_nome: dadosObj.signatario_1?.nome || '',
          noivo_cpf: dadosObj.signatario_1?.cpf || '',
          noivo_email: dadosObj.signatario_1?.email || '',
          noivo_telefone: dadosObj.signatario_1?.telefone || '',
          noiva_nome: dadosObj.signatario_2?.nome || '',
          noiva_cpf: dadosObj.signatario_2?.cpf || '',
          noiva_email: dadosObj.signatario_2?.email || '',
          noiva_telefone: dadosObj.signatario_2?.telefone || '',
          valor_total: valNum,
          condicoes_pagamento: dadosObj.forma_pagamento,
          clausulas_personalizadas: dadosObj.clausulas
        });
      }

      const dadosJson = JSON.stringify(dadosObj);

      await query(
        `UPDATE contratos SET 
          titulo = $1, 
          cliente_nome = $2, 
          cliente_cpf_cnpj = $3, 
          cliente_email = $4, 
          cliente_telefone = $5, 
          valor_total = $6, 
          status = $7, 
          dados_json = $8,
          assinafy_document_id = COALESCE($9, assinafy_document_id),
          assinafy_status = COALESCE($10, assinafy_status)
        WHERE id = $11`,
        [
          titulo,
          cliente_nome,
          cliente_cpf_cnpj || '',
          cliente_email || '',
          cliente_telefone || '',
          valNum,
          status,
          dadosJson,
          assinafy_document_id || null,
          assinafy_status || null,
          id
        ]
      );

      if (status === 'assinado' || status === 'aceita') {
        if (proposta_id) {
          try {
            await query(`UPDATE propostas SET status = 'aprovada' WHERE id = $1`, [proposta_id]);
          } catch (e) {}
        }
        if (valNum > 0) {
          try {
            const lancId = `lanc_contrato_${id}`;
            const hojeStr = new Date().toISOString().split('T')[0];
            await query(
              `INSERT INTO lancamentos (
                id, tipo, descricao, valor, valor_pago, categoria, cliente_fornecedor, vencimento, status, observacao
              ) VALUES ($1, 'receber', $2, $3, 0.00, 'Serviços', $4, $5, 'pendente', $6)
              ON DUPLICATE KEY UPDATE valor = VALUES(valor)`,
              [lancId, `Contrato: ${titulo}`, valNum, cliente_nome || 'Cliente', hojeStr, `Ref. Contrato: ${id}`]
            );
          } catch (e) {}
        }
      }

      return res.status(200).json({ ok: true });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'DELETE') {
    try {
      const { id } = req.query;
      if (!id) return res.status(422).json({ erro: 'ID do contrato é obrigatório' });

      await query('DELETE FROM contratos WHERE id = $1', [id]);
      return res.status(200).json({ ok: true });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  return res.status(405).json({ erro: 'Método não permitido' });
});
