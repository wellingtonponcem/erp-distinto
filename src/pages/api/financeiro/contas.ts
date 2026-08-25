import { NextApiRequest, NextApiResponse } from 'next';
import { requireAuth, generateId } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';
import { asaasService } from '@/lib/asaas';

export default requireAuth(async (req: NextApiRequest, res: NextApiResponse) => {
  const method = req.method;

  if (method === 'GET') {
    try {
      // Garante que a Conta Asaas exista na tabela de contas
      const existeAsaas = await queryOne("SELECT id FROM contas_bancarias WHERE id = 'asaas' OR nome LIKE '%asaas%'");
      if (!existeAsaas) {
        await query(
          "INSERT INTO contas_bancarias (id, nome, tipo, saldo_inicial, cor) VALUES ('asaas', 'Asaas (Conta Virtual)', 'pagamento', 0, '#10b981')"
        );
      }

      const rows = await query("SELECT * FROM contas_bancarias ORDER BY (id = 'asaas') DESC, criado_em DESC");

      // Se o Asaas estiver configurado, podemos ler o saldo real
      let saldoAsaasReal: number | null = null;
      try {
        if (await asaasService.isConfiguredAsync()) {
          const bal = await asaasService.getBalanceAndExtract(5);
          saldoAsaasReal = bal.saldo;
        }
      } catch (e) {}

      const contasFormatadas = rows.map((c: any) => {
        if ((c.id === 'asaas' || (c.nome && c.nome.toLowerCase().includes('asaas'))) && saldoAsaasReal !== null) {
          return { ...c, saldo_inicial: saldoAsaasReal, saldo_real: saldoAsaasReal };
        }
        return c;
      });

      return res.status(200).json(contasFormatadas);
    } catch (err: any) {
      console.error('Erro no GET /api/financeiro/contas:', err);
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'POST') {
    try {
      const { nome, tipo, saldo_inicial, cor } = req.body || {};
      if (!nome) return res.status(422).json({ erro: 'Nome da conta é obrigatório' });

      const id = generateId();
      await query(
        'INSERT INTO contas_bancarias (id, nome, tipo, saldo_inicial, cor) VALUES ($1, $2, $3, $4, $5)',
        [id, nome, tipo || 'corrente', parseFloat(saldo_inicial || 0), cor || '#111827']
      );

      return res.status(201).json({ ok: true, id });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  if (method === 'DELETE') {
    try {
      const { id } = req.query;
      if (!id) return res.status(422).json({ erro: 'ID é obrigatório' });
      if (id === 'asaas') return res.status(422).json({ erro: 'A conta padrão do Asaas não pode ser excluída' });

      await query('DELETE FROM contas_bancarias WHERE id = $1', [id]);
      return res.status(200).json({ ok: true });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  return res.status(405).json({ erro: 'Método não permitido' });
});
