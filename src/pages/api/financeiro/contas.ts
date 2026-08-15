import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth, generateId } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';
import { asaasService } from '@/lib/asaas';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse) => {
  const method = req.method;

  if (method === 'GET') {
    // Garante que a Conta Asaas exista na tabela de contas
    const existeAsaas = await queryOne("SELECT id FROM contas WHERE id = 'asaas' OR nome ILIKE '%asaas%'");
    if (!existeAsaas) {
      await query(
        "INSERT INTO contas (id, nome, tipo, saldo_inicial, cor) VALUES ('asaas', 'Asaas (Conta Virtual)', 'pagamento', 0, '#10b981')"
      );
    }

    const rows = await query('SELECT * FROM contas ORDER BY (id = \'asaas\') DESC, created_at DESC');

    // Se o Asaas estiver configurado, podemos ler o saldo real
    let saldoAsaasReal: number | null = null;
    try {
      if (await asaasService.isConfiguredAsync()) {
        const bal = await asaasService.getBalanceAndExtract(5);
        saldoAsaasReal = bal.saldo;
      }
    } catch (e) {}

    const contasFormatadas = rows.map((c: any) => {
      if ((c.id === 'asaas' || c.nome.toLowerCase().includes('asaas')) && saldoAsaasReal !== null) {
        return { ...c, saldo_inicial: saldoAsaasReal, saldo_real: saldoAsaasReal };
      }
      return c;
    });

    return res.status(200).json(contasFormatadas);
  }

  if (method === 'POST') {
    const { nome, tipo, saldo_inicial, cor } = req.body || {};
    if (!nome) return res.status(422).json({ erro: 'Nome da conta é obrigatório' });

    const id = generateId();
    await query(
      'INSERT INTO contas (id, nome, tipo, saldo_inicial, cor) VALUES ($1, $2, $3, $4, $5)',
      [id, nome, tipo || 'corrente', parseFloat(saldo_inicial || 0), cor || '#111827']
    );

    return res.status(201).json({ ok: true, id });
  }

  if (method === 'DELETE') {
    const { id } = req.query;
    if (!id) return res.status(422).json({ erro: 'ID é obrigatório' });
    if (id === 'asaas') return res.status(422).json({ erro: 'A conta padrão do Asaas não pode ser excluída' });

    await query('DELETE FROM contas WHERE id = $1', [id]);
    return res.status(200).json({ ok: true });
  }

  return res.status(405).json({ erro: 'Método não permitido' });
});
