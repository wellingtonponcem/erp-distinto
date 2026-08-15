const { Client } = require('pg');

const connectionString = 'postgresql://neondb_owner:npg_y9elwYoUx3nz@ep-patient-moon-acgcdcuv-pooler.sa-east-1.aws.neon.tech/neondb?sslmode=require';

async function initErpTables() {
  const client = new Client({ connectionString });
  await client.connect();
  console.log('✅ Conectado ao novo banco Neon ERP!');

  const sqls = [
    `CREATE TABLE IF NOT EXISTS users (
      id TEXT PRIMARY KEY,
      nome TEXT NOT NULL,
      email TEXT UNIQUE NOT NULL,
      senha TEXT NOT NULL,
      nivel INTEGER DEFAULT 0,
      criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );`,

    `CREATE TABLE IF NOT EXISTS clientes (
      id TEXT PRIMARY KEY,
      nome TEXT NOT NULL,
      cpf_cnpj TEXT NULL,
      contato TEXT NULL,
      email TEXT NULL,
      telefone TEXT NULL,
      asaas_customer_id TEXT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );`,

    `CREATE TABLE IF NOT EXISTS fornecedores (
      id TEXT PRIMARY KEY,
      nome TEXT NOT NULL,
      cpf_cnpj TEXT NULL,
      contato TEXT NULL,
      email TEXT NULL,
      telefone TEXT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );`,

    `CREATE TABLE IF NOT EXISTS custos_fixos (
      id TEXT PRIMARY KEY,
      nome TEXT NOT NULL,
      valor NUMERIC(10,2) NOT NULL,
      categoria TEXT DEFAULT 'outros',
      recorrencia TEXT DEFAULT 'mensal',
      dia_vencimento INTEGER DEFAULT 5,
      forma_pagamento TEXT DEFAULT 'pix',
      ativo INTEGER DEFAULT 1,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );`,

    `CREATE TABLE IF NOT EXISTS contas (
      id TEXT PRIMARY KEY,
      nome TEXT NOT NULL,
      tipo TEXT DEFAULT 'corrente',
      saldo_inicial NUMERIC(10,2) DEFAULT 0.00,
      cor TEXT DEFAULT '#000000',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );`,

    `CREATE TABLE IF NOT EXISTS configuracao_empresa (
      id TEXT PRIMARY KEY DEFAULT 'principal',
      nome_empresa TEXT NULL,
      cnpj TEXT NULL,
      asaas_api_key TEXT NULL,
      asaas_mode TEXT DEFAULT 'test',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );`,

    `CREATE TABLE IF NOT EXISTS lancamentos (
      id TEXT PRIMARY KEY,
      tipo TEXT NOT NULL,
      descricao TEXT NOT NULL,
      valor NUMERIC(10,2) NOT NULL,
      valor_pago NUMERIC(10,2) DEFAULT 0.00,
      categoria TEXT DEFAULT 'outros',
      cliente_fornecedor TEXT NULL,
      cliente_id TEXT NULL,
      fornecedor_id TEXT NULL,
      conta_id TEXT NULL,
      custo_fixo_id TEXT NULL,
      vencimento DATE NOT NULL,
      data_pagamento DATE NULL,
      status TEXT DEFAULT 'pendente',
      modalidade TEXT DEFAULT 'avista',
      total_parcelas INTEGER NULL,
      parcela_atual INTEGER DEFAULT 1,
      lancamento_pai_id TEXT NULL,
      frequencia TEXT NULL,
      data_termino DATE NULL,
      forma_pagamento TEXT NULL,
      observacao TEXT NULL,
      ofx_fitid TEXT NULL,
      asaas_id TEXT NULL,
      conciliado INTEGER DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );`,

    `CREATE TABLE IF NOT EXISTS contratos (
      id TEXT PRIMARY KEY,
      proposta_id TEXT NULL,
      cliente_id TEXT NULL,
      cliente_nome TEXT NOT NULL,
      titulo TEXT NOT NULL,
      valor_total NUMERIC(10,2) NOT NULL,
      condicoes_pagamento TEXT NULL,
      data_contrato DATE NULL,
      local_contrato TEXT NULL,
      status TEXT DEFAULT 'rascunho',
      documento_assinatura_id TEXT NULL,
      link_assinatura TEXT NULL,
      dados_json TEXT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );`,

    `CREATE TABLE IF NOT EXISTS orcamentos (
      id TEXT PRIMARY KEY,
      cliente_id TEXT NULL,
      cliente_nome TEXT NOT NULL,
      tipo TEXT DEFAULT 'albuns_15anos',
      slug TEXT UNIQUE NOT NULL,
      titulo TEXT NOT NULL,
      subtitulo TEXT NULL,
      validade DATE NULL,
      dados_json TEXT NOT NULL,
      valor_total NUMERIC(10,2) NULL,
      status TEXT DEFAULT 'pendente',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );`,

    `CREATE TABLE IF NOT EXISTS propostas_historico (
      id SERIAL PRIMARY KEY,
      proposta_id TEXT NOT NULL,
      user_id TEXT NOT NULL,
      tipo TEXT DEFAULT 'nota',
      conteudo TEXT NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );`
  ];

  for (const sql of sqls) {
    await client.query(sql);
  }

  console.log('✅ Estrutura de todas as tabelas do ERP verificada e pronta no novo banco!');
  await client.end();
}

initErpTables();
