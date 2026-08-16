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

    // Garantir estrutura da coluna ofx_fitid no banco Neon
    try {
      await query(`ALTER TABLE lancamentos ADD COLUMN IF NOT EXISTS ofx_fitid TEXT`);
    } catch (e) {}

    // 1. Identificar Banco do Arquivo OFX
    const bankIdMatch = fileContent.match(/<BANKID>\s*(.*?)(?:\r|\n|<)/i);
    const orgMatch = fileContent.match(/<ORG>\s*(.*?)(?:\r|\n|<)/i);
    const acctIdMatch = fileContent.match(/<ACCTID>\s*(.*?)(?:\r|\n|<)/i);

    const bankId = (bankIdMatch?.[1] || '').trim();
    const org = (orgMatch?.[1] || '').trim();
    const acctId = (acctIdMatch?.[1] || '').trim();

    // Buscar lista de contas no banco para cruzamento inteligente
    let contas: any[] = [];
    try {
      const resContas = await query(`SELECT id, nome FROM contas`);
      if (Array.isArray(resContas)) contas = resContas;
    } catch (e) {}

    let bancoDetectado: { id: string; nome: string } | null = null;

    const orgUpper = org.toUpperCase();
    const fullHeaderStr = `${bankId} ${orgUpper} ${acctId}`.toUpperCase();

    // Mapeamento de Bancos Brasileiros
    for (const c of contas) {
      const nomeContaUpper = (c.nome || '').toUpperCase();
      if (
        (orgUpper && (nomeContaUpper.includes(orgUpper) || orgUpper.includes(nomeContaUpper))) ||
        (fullHeaderStr.includes('C6') && nomeContaUpper.includes('C6')) ||
        (bankId === '336' && nomeContaUpper.includes('C6')) ||
        (bankId === '341' && (nomeContaUpper.includes('ITAÚ') || nomeContaUpper.includes('ITAU'))) ||
        (bankId === '237' && nomeContaUpper.includes('BRADESCO')) ||
        (bankId === '033' && (nomeContaUpper.includes('SANTANDER') || nomeContaUpper.includes('SAN'))) ||
        (bankId === '260' && (nomeContaUpper.includes('NUBANK') || nomeContaUpper.includes('NU'))) ||
        (bankId === '001' && (nomeContaUpper.includes('BRASIL') || nomeContaUpper.includes('BB'))) ||
        (bankId === '104' && (nomeContaUpper.includes('CAIXA') || nomeContaUpper.includes('CEF'))) ||
        (bankId === '077' && nomeContaUpper.includes('INTER'))
      ) {
        bancoDetectado = { id: c.id, nome: c.nome };
        break;
      }
    }

    // 2. Extrair Transações do OFX
    const parts = fileContent.split(/<STMTTRN>/i);
    parts.shift(); // Remove cabeçalho

    const parsedTxns: any[] = [];
    const fitidsToCheck: string[] = [];

    for (const txn of parts) {
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
      const fitid = (idMatch?.[1] || '').trim() || `fit_${date}_${valor}_${(memoMatch?.[1] || '').trim()}`;
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

    // 3. Desduplicação Inteligente e Segura no Banco Neon
    let fitidsExistentes: string[] = [];
    if (fitidsToCheck.length > 0) {
      try {
        const rows = await query(
          `SELECT ofx_fitid FROM lancamentos WHERE ofx_fitid = ANY($1::text[]) AND ofx_fitid IS NOT NULL AND ofx_fitid != ''`,
          [fitidsToCheck]
        );
        if (Array.isArray(rows)) {
          fitidsExistentes = rows.map((r: any) => r.ofx_fitid).filter(Boolean);
        }
      } catch (errDb: any) {
        console.warn('Busca de FITID em lancamentos ignorada:', errDb.message);
      }
    }

    // Checar duplicatas de forma ultra segura (sem operadores numéricos frágeis no SQL)
    const transacoesNovas: any[] = [];
    let ignoradasCount = 0;

    for (const t of parsedTxns) {
      if (fitidsExistentes.includes(t.fitid)) {
        ignoradasCount++;
        continue;
      }

      try {
        const duplicado = await query(
          `SELECT id FROM lancamentos WHERE vencimento::text LIKE $1 || '%' AND LOWER(descricao) = LOWER($2) LIMIT 1`,
          [t.data, t.descricao]
        );

        if (Array.isArray(duplicado) && duplicado.length > 0) {
          ignoradasCount++;
          continue;
        }
      } catch (e) {}

      transacoesNovas.push(t);
    }

    if (transacoesNovas.length === 0) {
      return res.status(400).json({
        erro: 'Todas as transações deste arquivo OFX já foram importadas anteriormente.',
      });
    }

    return res.status(200).json({
      ok: true,
      transacoes: transacoesNovas,
      totalIgnoradas: ignoradasCount,
      bancoDetectado,
      orgDetectada: org || bankId || 'Banco não especificado',
    });
  } catch (err: any) {
    console.error('Erro no upload-ofx:', err);
    return res.status(500).json({ erro: `Erro ao processar OFX: ${err.message}` });
  }
});
