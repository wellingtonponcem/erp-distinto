import { NextApiRequest, NextApiResponse } from 'next';
import { queryOne } from '@/lib/db';
import { generateContractPdf } from '@/lib/pdf-generator';

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  const { id } = req.query;

  if (!id || typeof id !== 'string') {
    return res.status(400).json({ erro: 'ID do contrato é obrigatório' });
  }

  try {
    const contrato = await queryOne('SELECT * FROM contratos WHERE id = $1 LIMIT 1', [id]);

    if (!contrato) {
      return res.status(404).json({ erro: 'Contrato não encontrado' });
    }

    const pdfBuffer = await generateContractPdf({
      titulo: contrato.titulo,
      cliente_nome: contrato.cliente_nome,
      valor_total: parseFloat(contrato.valor_total || 0),
      data_contrato: contrato.data_contrato,
      condicoes_pagamento: contrato.condicoes_pagamento,
      conteudo_clausulas: contrato.dados_json ? JSON.parse(contrato.dados_json).clausulas : ''
    });

    res.setHeader('Content-Type', 'application/pdf');
    res.setHeader('Content-Disposition', `inline; filename="contrato_${id}.pdf"`);
    res.setHeader('Content-Length', pdfBuffer.length);
    return res.status(200).send(pdfBuffer);
  } catch (err: any) {
    console.error('Erro ao gerar PDF do contrato:', err);
    return res.status(500).json({ erro: 'Erro interno ao gerar PDF: ' + err.message });
  }
}
