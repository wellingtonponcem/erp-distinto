import { NextApiRequest, NextApiResponse } from 'next';
import { queryOne } from '@/lib/db';
import { renderMasterContractHtml } from '@/lib/contract-template';
import { requireAuth } from '@/lib/helpers';

async function handler(req: NextApiRequest, res: NextApiResponse) {
  const { id, proposta_id } = req.query;

  if (!id && !proposta_id) {
    return res.status(400).json({ erro: 'ID ou proposta_id do contrato é obrigatório' });
  }

  try {
    let contrato: any = null;

    if (id) {
      contrato = await queryOne('SELECT * FROM contratos WHERE id = $1 LIMIT 1', [String(id)]);
    } else if (proposta_id) {
      contrato = await queryOne('SELECT * FROM contratos WHERE proposta_id = $1 LIMIT 1', [String(proposta_id)]);
    }

    if (!contrato) {
      return res.status(404).send(`
        <div style="font-family: sans-serif; padding: 40px; text-align: center;">
          <h2>Contrato não encontrado</h2>
          <p style="color: #666;">Não foi localizado nenhum documento de contrato para este identificador.</p>
        </div>
      `);
    }

    let dadosParsed: any = {};
    try {
      dadosParsed = typeof contrato.dados_json === 'string' ? JSON.parse(contrato.dados_json) : (contrato.dados_json || {});
    } catch (e) {}

    const clienteNome = contrato.cliente_nome || 'Cliente Contratante';
    const titulo = contrato.titulo || `Contrato - ${clienteNome}`;
    const valor = parseFloat(contrato.valor_total || contrato.valor || 0);

    // Se o texto salvo não for o modelo master oficial (ou for o modelo simples antigo), forçar renderização com o modelo oficial
    if (!dadosParsed.contrato_texto || dadosParsed.contrato_texto.includes('CONTRATO DE PRESTAÇÃO DE SERVIÇOS AUDIOVISUAIS')) {
      dadosParsed.contrato_texto = renderMasterContractHtml({
        id: contrato.id,
        titulo,
        cliente_nome: clienteNome,
        cliente_cpf_cnpj: contrato.cliente_cpf_cnpj || dadosParsed.signatario_1?.cpf || '',
        cliente_email: contrato.cliente_email || dadosParsed.signatario_1?.email || '',
        cliente_telefone: contrato.cliente_telefone || dadosParsed.signatario_1?.telefone || '',
        noivo_nome: dadosParsed.signatario_1?.nome || '',
        noivo_cpf: dadosParsed.signatario_1?.cpf || '',
        noivo_email: dadosParsed.signatario_1?.email || '',
        noivo_telefone: dadosParsed.signatario_1?.telefone || '',
        noiva_nome: dadosParsed.signatario_2?.nome || '',
        noiva_cpf: dadosParsed.signatario_2?.cpf || '',
        noiva_email: dadosParsed.signatario_2?.email || '',
        noiva_telefone: dadosParsed.signatario_2?.telefone || '',
        valor_total: valor,
        condicoes_pagamento: dadosParsed.forma_pagamento,
        clausulas_personalizadas: dadosParsed.clausulas
      });
    }

    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    return res.status(200).send(dadosParsed.contrato_texto);
  } catch (err: any) {
    console.error('Erro ao servir contrato:', err);
    return res.status(500).send('Erro interno ao carregar o contrato.');
  }
}

export default requireAuth(async (req, res, _user) => {
  // Token efêmero via query ?token=JWT(slug) poderia ser validado aqui; por enquanto exige sessão autenticada
  await handler(req, res);
});
