import { NextApiRequest, NextApiResponse } from 'next';
import { query, queryOne } from '@/lib/db';
import { generateId, requireAdmin } from '@/lib/helpers';

async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method !== 'POST') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    // 1. Cadastrar Clientes de Exemplo se não existirem
    const countCli = await queryOne('SELECT COUNT(*) as cnt FROM clientes');
    const countCliVal = parseInt(countCli?.cnt || '0');

    if (countCliVal === 0) {
      const clientes = [
        { id: generateId(), nome: 'Estúdio Casamentos Premium', email: 'contato@estudiocasamentos.com.br', cpf_cnpj: '12.345.678/0001-90' },
        { id: generateId(), nome: 'Agência Eventos 15 Anos', email: 'financeiro@eventos15anos.com.br', cpf_cnpj: '98.765.432/0001-10' },
        { id: generateId(), nome: 'Fotografia & Produção Distinto', email: 'atendimento@wedistinto.com', cpf_cnpj: '45.123.890/0001-55' }
      ];

      for (const c of clientes) {
        await query(
          'INSERT INTO clientes (id, nome, email, cpf_cnpj) VALUES ($1, $2, $3, $4) ON CONFLICT DO NOTHING',
          [c.id, c.nome, c.email, c.cpf_cnpj]
        );
      }
    }

    // 2. Cadastrar Lançamentos de Exemplo se não existirem
    const countLanc = await queryOne('SELECT COUNT(*) as cnt FROM lancamentos');
    const countLancVal = parseInt(countLanc?.cnt || '0');

    if (countLancVal === 0) {
      const hoje = new Date().toISOString().split('T')[0];
      const mesPassado = new Date(Date.now() - 30 * 86400000).toISOString().split('T')[0];
      const proximoMes = new Date(Date.now() + 15 * 86400000).toISOString().split('T')[0];

      const lancamentosExemplo = [
        {
          id: generateId(),
          tipo: 'receber',
          descricao: 'Contrato de Cobertura Fotográfica 15 Anos',
          valor: 4500.00,
          valor_pago: 4500.00,
          categoria: 'Serviços',
          cliente_fornecedor: 'Agência Eventos 15 Anos',
          vencimento: mesPassado,
          data_pagamento: mesPassado,
          status: 'pago'
        },
        {
          id: generateId(),
          tipo: 'receber',
          descricao: 'Entrada Proposta Casamento VIP',
          valor: 3200.00,
          valor_pago: 3200.00,
          categoria: 'Propostas',
          cliente_fornecedor: 'Estúdio Casamentos Premium',
          vencimento: hoje,
          data_pagamento: hoje,
          status: 'pago'
        },
        {
          id: generateId(),
          tipo: 'receber',
          descricao: 'Parcela 2/3 Álbum Casamento',
          valor: 1800.00,
          valor_pago: 0.00,
          categoria: 'Álbuns',
          cliente_fornecedor: 'Fotografia & Produção Distinto',
          vencimento: proximoMes,
          data_pagamento: null,
          status: 'pendente'
        },
        {
          id: generateId(),
          tipo: 'pagar',
          descricao: 'Aluguel do Estúdio & Software IA',
          valor: 1200.00,
          valor_pago: 1200.00,
          categoria: 'Custos Fixos',
          cliente_fornecedor: 'Imobiliária Central',
          vencimento: mesPassado,
          data_pagamento: mesPassado,
          status: 'pago'
        },
        {
          id: generateId(),
          tipo: 'pagar',
          descricao: 'Assinatura Vercel & Servidores',
          valor: 350.00,
          valor_pago: 0.00,
          categoria: 'Servidores',
          cliente_fornecedor: 'Vercel Inc.',
          vencimento: proximoMes,
          data_pagamento: null,
          status: 'pendente'
        }
      ];

      for (const l of lancamentosExemplo) {
        await query(
          `INSERT INTO lancamentos (
            id, tipo, descricao, valor, valor_pago, categoria, cliente_fornecedor,
            vencimento, data_pagamento, status
          ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)`,
          [l.id, l.tipo, l.descricao, l.valor, l.valor_pago, l.categoria, l.cliente_fornecedor, l.vencimento, l.data_pagamento, l.status]
        );
      }
    }

    return res.status(200).json({ ok: true, mensagem: 'Dados iniciais inseridos com sucesso!' });
  } catch (err: any) {
    console.error('Seed Error:', err);
    return res.status(500).json({ erro: err.message });
  }
}

export default requireAdmin(async (req, res, _user) => {
  await handler(req, res);
});
