import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth } from '@/lib/helpers';
import { query } from '@/lib/db';

export const config = {
  api: {
    bodyParser: {
      sizeLimit: '10mb',
    },
  },
};

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const { fileContent } = req.body || {};

    if (!fileContent) {
      return res.status(422).json({ erro: 'Conteúdo do arquivo OFX não fornecido.' });
    }

    const parts = fileContent.split(/<STMTTRN>/i);
    parts.shift(); // Remove header

    const parsedTxns: any[] = [];
    const fitidsToCheck: string[] = [];

    for (const txn of parts) {
      const typeMatch = txn.match(/<TRNTYPE>\s*(.*?)(?:\r|\n|<)/i);
      const dateMatch = txn.match(/<DTPOSTED>\s*(.*?)(?:\r|\n|<)/i);
      const amtMatch = txn.match(/<TRNAMT>\s*(.*?)(?:\r|\n|<)/i);
      const idMatch = txn.match(/<FITID>\s*(.*?)(?:\r|\n|<)/i);
      const memoMatch = txn.match(/<MEMO>\s*(.*?)(?:\r|\n|<)/i);

      const rawDate = (dateMatch?.[1] || '').trim().substring(0, 8);
      let date = '';
      if (rawDate.length === 8) {
        date = `${rawDate.substring(0, 4)}-${rawDate.substring(4, 6)}-${rawDate.substring(6, 8)}`;
      }

      const rawAmt = parseFloat((amtMatch?.[1] || '0').trim());
      const tipo = rawAmt >= 0 ? 'receber' : 'pagar';
      const valor = Math.abs(rawAmt);
      const fitid = (idMatch?.[1] || '').trim();
      const descricao = (memoMatch?.[1] || '').trim() || 'Lançamento OFX';

      if (date && valor > 0) {
        parsedTxns.push({
          fitid,
          tipo,
          data: date,
          valor,
          descricao,
          categoria: 'Outros',
        });
        if (fitid) fitidsToCheck.push(fitid);
      }
    }

    if (parsedTxns.length === 0) {
      return res.status(400).json({ erro: 'Nenhuma transação válida encontrada no arquivo OFX.' });
    }

    // Checar transações já importadas no Neon DB
    let fitidsExistentes: string[] = [];
    if (fitidsToCheck.length > 0) {
      const rows = await query(
        `SELECT ofx_fitid FROM lancamentos WHERE ofx_fitid = ANY($1::text[])`,
        [fitidsToCheck]
      );
      fitidsExistentes = rows.map((r: any) => r.ofx_fitid);
    }

    const transacoesNovas = parsedTxns.filter((t) => !fitidsExistentes.includes(t.fitid));

    if (transacoesNovas.length === 0) {
      return res.status(400).json({
        erro: 'Todas as transações deste arquivo OFX já foram importadas anteriormente.',
      });
    }

    return res.status(200).json({ ok: true, transacoes: transacoesNovas, totalIgnoradas: parsedTxns.length - transacoesNovas.length });
  } catch (err: any) {
    return res.status(500).json({ erro: err.message });
  }
});
