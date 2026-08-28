import mysql from 'mysql2/promise';

let poolInstance: mysql.Pool | null = null;

export function getDbPool(): mysql.Pool {
  if (!poolInstance) {
    const host = process.env.MYSQL_HOST || 'srv952.hstgr.io';
    const port = parseInt(process.env.MYSQL_PORT || '3306');
    const database = process.env.MYSQL_DATABASE || 'u306254544_distinto';
    const user = process.env.MYSQL_USER || 'u306254544_poncem';
    const password = process.env.MYSQL_PASSWORD || '!@Jeane&w#1';

    poolInstance = mysql.createPool({
      host,
      port,
      database,
      user,
      password,
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0,
      connectTimeout: 10000,
      enableKeepAlive: true,
      keepAliveInitialDelay: 0,
    });
  }

  return poolInstance;
}

export async function initTables() {
  const p = getDbPool();
  try {
    // 1. Users
    await p.query(`
      CREATE TABLE IF NOT EXISTS users (
        id VARCHAR(64) PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        senha VARCHAR(255) NOT NULL,
        nivel INT DEFAULT 0,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // 2. Contas Bancarias
    await p.query(`
      CREATE TABLE IF NOT EXISTS contas_bancarias (
        id VARCHAR(64) PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        banco VARCHAR(100),
        tipo VARCHAR(50) DEFAULT 'corrente',
        saldo DECIMAL(15,2) DEFAULT 0.00,
        saldo_inicial DECIMAL(15,2) DEFAULT 0.00,
        agencia VARCHAR(32),
        conta VARCHAR(32),
        cor VARCHAR(32) DEFAULT '#3b82f6',
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    try { await p.query(`ALTER TABLE contas_bancarias ADD COLUMN saldo_inicial DECIMAL(15,2) DEFAULT 0.00;`); } catch (e) {}
    try { await p.query(`ALTER TABLE contas_bancarias ADD COLUMN cor VARCHAR(32) DEFAULT '#3b82f6';`); } catch (e) {}

    // 3. Configuracao Empresa
    await p.query(`
      CREATE TABLE IF NOT EXISTS configuracao_empresa (
        id VARCHAR(64) PRIMARY KEY,
        nome_empresa VARCHAR(255) DEFAULT 'ERP Distinto',
        asaas_api_key TEXT,
        asaas_mode VARCHAR(32) DEFAULT 'prod',
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    try { await p.query(`ALTER TABLE configuracao_empresa ADD COLUMN nome_empresa VARCHAR(255) DEFAULT 'ERP Distinto';`); } catch (e) {}
    try { await p.query(`ALTER TABLE configuracao_empresa ADD COLUMN asaas_api_key TEXT;`); } catch (e) {}
    try { await p.query(`ALTER TABLE configuracao_empresa ADD COLUMN asaas_mode VARCHAR(32) DEFAULT 'prod';`); } catch (e) {}

    // 4. Clientes
    await p.query(`
      CREATE TABLE IF NOT EXISTS clientes (
        id VARCHAR(64) PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        email VARCHAR(255),
        cpf_cnpj VARCHAR(32),
        telefone VARCHAR(32),
        endereco TEXT,
        cidade VARCHAR(100),
        estado VARCHAR(10),
        cep VARCHAR(20),
        observacoes TEXT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // 5. Categorias
    await p.query(`
      CREATE TABLE IF NOT EXISTS categorias (
        id VARCHAR(64) PRIMARY KEY,
        nome VARCHAR(255) UNIQUE NOT NULL,
        cor VARCHAR(32) DEFAULT '#3b82f6',
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // 6. Lancamentos
    await p.query(`
      CREATE TABLE IF NOT EXISTS lancamentos (
        id VARCHAR(64) PRIMARY KEY,
        tipo VARCHAR(20) NOT NULL,
        descricao VARCHAR(255) NOT NULL,
        valor DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        valor_pago DECIMAL(15,2) DEFAULT 0.00,
        categoria VARCHAR(100) DEFAULT 'Outros',
        cliente_fornecedor VARCHAR(255),
        vencimento DATE,
        data_pagamento DATE,
        status VARCHAR(32) DEFAULT 'pendente',
        modalidade VARCHAR(32) DEFAULT 'avista',
        total_parcelas INT DEFAULT 1,
        parcela_atual INT DEFAULT 1,
        lancamento_pai_id VARCHAR(64),
        frequencia VARCHAR(32),
        data_termino DATE,
        observacao TEXT,
        forma_pagamento VARCHAR(50),
        conta_id VARCHAR(64),
        conciliado INT DEFAULT 0,
        asaas_id VARCHAR(100),
        ofx_fitid VARCHAR(100),
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    try { await p.query(`ALTER TABLE lancamentos ADD COLUMN valor_pago DECIMAL(15,2) DEFAULT 0.00;`); } catch (e) {}
    try { await p.query(`ALTER TABLE lancamentos ADD COLUMN categoria VARCHAR(100) DEFAULT 'Outros';`); } catch (e) {}
    try { await p.query(`ALTER TABLE lancamentos ADD COLUMN cliente_fornecedor VARCHAR(255);`); } catch (e) {}
    try { await p.query(`ALTER TABLE lancamentos ADD COLUMN vencimento DATE;`); } catch (e) {}
    try { await p.query(`ALTER TABLE lancamentos ADD COLUMN data_pagamento DATE;`); } catch (e) {}
    try { await p.query(`ALTER TABLE lancamentos ADD COLUMN status VARCHAR(32) DEFAULT 'pendente';`); } catch (e) {}
    try { await p.query(`ALTER TABLE lancamentos ADD COLUMN conta_id VARCHAR(64);`); } catch (e) {}
    try { await p.query(`ALTER TABLE lancamentos ADD COLUMN conciliado INT DEFAULT 0;`); } catch (e) {}
    try { await p.query(`ALTER TABLE lancamentos ADD COLUMN asaas_id VARCHAR(100);`); } catch (e) {}
    try { await p.query(`ALTER TABLE lancamentos ADD COLUMN ofx_fitid VARCHAR(100);`); } catch (e) {}

    // 7. Pastas de Propostas
    await p.query(`
      CREATE TABLE IF NOT EXISTS pastas_propostas (
        id VARCHAR(64) PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        cor VARCHAR(32) DEFAULT '#3b82f6',
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // 8. Propostas Comerciais
    await p.query(`
      CREATE TABLE IF NOT EXISTS propostas (
        id VARCHAR(64) PRIMARY KEY,
        slug VARCHAR(255) UNIQUE,
        titulo VARCHAR(255) NOT NULL,
        tipo VARCHAR(50) DEFAULT 'casamento',
        cliente_id VARCHAR(64),
        cliente_nome VARCHAR(255),
        pasta_id VARCHAR(64),
        valor DECIMAL(15,2) DEFAULT 0.00,
        valor_total DECIMAL(15,2) DEFAULT 0.00,
        status VARCHAR(32) DEFAULT 'rascunho',
        dados_json LONGTEXT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    try { await p.query(`ALTER TABLE propostas ADD COLUMN slug VARCHAR(255);`); } catch (e) {}
    try { await p.query(`ALTER TABLE propostas ADD COLUMN tipo VARCHAR(50) DEFAULT 'casamento';`); } catch (e) {}
    try { await p.query(`ALTER TABLE propostas ADD COLUMN cliente_id VARCHAR(64);`); } catch (e) {}
    try { await p.query(`ALTER TABLE propostas ADD COLUMN cliente_nome VARCHAR(255);`); } catch (e) {}
    try { await p.query(`ALTER TABLE propostas ADD COLUMN pasta_id VARCHAR(64);`); } catch (e) {}
    try { await p.query(`ALTER TABLE propostas ADD COLUMN dados_json LONGTEXT;`); } catch (e) {}
    try { await p.query(`ALTER TABLE propostas ADD COLUMN criado_em DATETIME DEFAULT CURRENT_TIMESTAMP;`); } catch (e) {}
    try { await p.query(`ALTER TABLE propostas ADD COLUMN valor_total DECIMAL(15,2) DEFAULT 0.00;`); } catch (e) {}

    // 9. Contratos Comerciais
    await p.query(`
      CREATE TABLE IF NOT EXISTS contratos (
        id VARCHAR(64) PRIMARY KEY,
        proposta_id VARCHAR(64),
        cliente_id VARCHAR(64),
        titulo VARCHAR(255),
        descricao VARCHAR(500),
        cliente_nome VARCHAR(255),
        cliente_cpf_cnpj VARCHAR(32),
        cliente_email VARCHAR(255),
        cliente_telefone VARCHAR(32),
        valor DECIMAL(15,2) DEFAULT 0.00,
        valor_total DECIMAL(15,2) DEFAULT 0.00,
        status VARCHAR(32) DEFAULT 'rascunho',
        dados_json LONGTEXT,
        assinafy_document_id VARCHAR(100),
        assinafy_status VARCHAR(50),
        link_assinatura VARCHAR(255),
        asaas_cobranca_gerada INT DEFAULT 0,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    try { await p.query(`ALTER TABLE contratos ADD COLUMN proposta_id VARCHAR(64);`); } catch (e) {}
    try { await p.query(`ALTER TABLE contratos ADD COLUMN cliente_id VARCHAR(64);`); } catch (e) {}
    try { await p.query(`ALTER TABLE contratos ADD COLUMN titulo VARCHAR(255);`); } catch (e) {}
    try { await p.query(`ALTER TABLE contratos ADD COLUMN descricao VARCHAR(500);`); } catch (e) {}
    try { await p.query(`ALTER TABLE contratos ADD COLUMN cliente_nome VARCHAR(255);`); } catch (e) {}
    try { await p.query(`ALTER TABLE contratos ADD COLUMN cliente_cpf_cnpj VARCHAR(32);`); } catch (e) {}
    try { await p.query(`ALTER TABLE contratos ADD COLUMN cliente_email VARCHAR(255);`); } catch (e) {}
    try { await p.query(`ALTER TABLE contratos ADD COLUMN cliente_telefone VARCHAR(32);`); } catch (e) {}
    try { await p.query(`ALTER TABLE contratos ADD COLUMN valor_total DECIMAL(15,2) DEFAULT 0.00;`); } catch (e) {}
    try { await p.query(`ALTER TABLE contratos ADD COLUMN status VARCHAR(32) DEFAULT 'rascunho';`); } catch (e) {}
    try { await p.query(`ALTER TABLE contratos ADD COLUMN dados_json LONGTEXT;`); } catch (e) {}
    try { await p.query(`ALTER TABLE contratos ADD COLUMN assinafy_document_id VARCHAR(100);`); } catch (e) {}
    try { await p.query(`ALTER TABLE contratos ADD COLUMN assinafy_status VARCHAR(50);`); } catch (e) {}
    try { await p.query(`ALTER TABLE contratos ADD COLUMN link_assinatura VARCHAR(255);`); } catch (e) {}
    try { await p.query(`ALTER TABLE contratos ADD COLUMN asaas_cobranca_gerada INT DEFAULT 0;`); } catch (e) {}
    try { await p.query(`ALTER TABLE contratos ADD COLUMN criado_em DATETIME DEFAULT CURRENT_TIMESTAMP;`); } catch (e) {}
    try { await p.query(`ALTER TABLE contratos ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP;`); } catch (e) {}

    // 10. Modelos de Contrato (Templates de Documentos com Variaveis)
    await p.query(`
      CREATE TABLE IF NOT EXISTS modelos_contrato (
        id VARCHAR(64) PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        tipo VARCHAR(50) DEFAULT 'casamento',
        conteudo_html LONGTEXT NOT NULL,
        padrao INT DEFAULT 0,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // 11. Servicos / Tabela de Precos
    await p.query(`
      CREATE TABLE IF NOT EXISTS servicos (
        id VARCHAR(64) PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        descricao TEXT,
        preco_venda DECIMAL(15,2) DEFAULT 0.00,
        preco_venda_pontual DECIMAL(15,2) DEFAULT 0.00,
        periodicidade VARCHAR(32) DEFAULT 'pontual',
        categoria VARCHAR(100) DEFAULT 'wedding',
        tipo VARCHAR(32) DEFAULT 'plano',
        subtitulo VARCHAR(255),
        beneficios_json LONGTEXT,
        condicoes_comerciais TEXT,
        ativo INT DEFAULT 1,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // 12. Briefings Respostas (Respostas do Formulario Publico de Briefing Logistico)
    await p.query(`
      CREATE TABLE IF NOT EXISTS briefings_resposta (
        id VARCHAR(64) PRIMARY KEY,
        cliente_nome VARCHAR(255) NOT NULL,
        data_casamento DATE,
        dados_json LONGTEXT NOT NULL,
        status VARCHAR(32) DEFAULT 'novo',
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // 13. Orcamentos B2B (Orcamentos simples para clientes existentes)
    await p.query(`
      CREATE TABLE IF NOT EXISTS orcamentos_b2b (
        id VARCHAR(64) PRIMARY KEY,
        cliente_nome VARCHAR(255) NOT NULL,
        cliente_empresa VARCHAR(255),
        titulo VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        status VARCHAR(32) DEFAULT 'rascunho',
        valor_total DECIMAL(15,2) DEFAULT 0.00,
        validade DATE,
        dados_json LONGTEXT NOT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

  } catch (err: any) {
    console.error('Erro ao inicializar tabelas MySQL:', err.message);
  }
}

let tablesInitialized = false;

function convertPgToMysql(sql: string, params: any[]): { mysqlSql: string; mysqlParams: any[] } {
  let mysqlSql = sql;
  let mysqlParams = [...params];

  mysqlSql = mysqlSql.replace(/\bFROM contas\b/gi, 'FROM contas_bancarias');
  mysqlSql = mysqlSql.replace(/\bJOIN contas\b/gi, 'JOIN contas_bancarias');
  mysqlSql = mysqlSql.replace(/\bINTO contas\b/gi, 'INTO contas_bancarias');
  mysqlSql = mysqlSql.replace(/\bUPDATE contas\b/gi, 'UPDATE contas_bancarias');
  mysqlSql = mysqlSql.replace(/\bDELETE FROM contas\b/gi, 'DELETE FROM contas_bancarias');

  mysqlSql = mysqlSql.replace(/\bFROM usuarios\b/gi, 'FROM users');
  mysqlSql = mysqlSql.replace(/\bJOIN usuarios\b/gi, 'JOIN users');
  mysqlSql = mysqlSql.replace(/\bINTO usuarios\b/gi, 'INTO users');
  mysqlSql = mysqlSql.replace(/\bUPDATE usuarios\b/gi, 'UPDATE users');
  mysqlSql = mysqlSql.replace(/\bDELETE FROM usuarios\b/gi, 'DELETE FROM users');

  mysqlSql = mysqlSql.replace(/\bILIKE\b/gi, 'LIKE');

  if (mysqlSql.includes('ON CONFLICT (nome) DO UPDATE SET cor = $3')) {
    mysqlSql = mysqlSql.replace('ON CONFLICT (nome) DO UPDATE SET cor = $3', 'ON DUPLICATE KEY UPDATE cor = VALUES(cor)');
  } else if (mysqlSql.includes('ON CONFLICT DO NOTHING') || mysqlSql.includes('ON CONFLICT (nome) DO NOTHING')) {
    mysqlSql = mysqlSql.replace(/ON CONFLICT.*DO NOTHING/gi, '');
    if (mysqlSql.trim().toUpperCase().startsWith('INSERT INTO')) {
      mysqlSql = mysqlSql.replace(/INSERT INTO/i, 'INSERT IGNORE INTO');
    }
  }

  if (mysqlSql.includes('ANY($1::text[])')) {
    mysqlSql = mysqlSql.replace('= ANY($1::text[])', 'IN (?)');
  }

  if (mysqlSql.includes("LIKE $1 || '%'")) {
    mysqlSql = mysqlSql.replace("LIKE $1 || '%'", 'LIKE ?');
    if (mysqlParams.length > 0 && typeof mysqlParams[0] === 'string' && !mysqlParams[0].endsWith('%')) {
      mysqlParams[0] = `${mysqlParams[0]}%`;
    }
  }

  mysqlSql = mysqlSql.replace(/\bcreated_at\b/gi, 'criado_em');
  mysqlSql = mysqlSql.replace(/::text/gi, '');
  mysqlSql = mysqlSql.replace(/::numeric/gi, '');
  mysqlSql = mysqlSql.replace(/\$\d+/g, '?');

  return { mysqlSql, mysqlParams };
}

export async function query<T = any>(sql: string, params: any[] = []): Promise<T[]> {
  if (!tablesInitialized) {
    tablesInitialized = true;
    await initTables();
  }

  try {
    const p = getDbPool();
    const { mysqlSql, mysqlParams } = convertPgToMysql(sql, params);
    const [rows] = await p.query(mysqlSql, mysqlParams);

    if (Array.isArray(rows)) {
      return rows as T[];
    }
    return [] as T[];
  } catch (err: any) {
    if (!err.message?.includes('Duplicate column name')) {
      console.error('MySQL Query Error:', err.message, 'SQL:', sql);
    }
    throw err;
  }
}

export async function queryOne<T = any>(sql: string, params: any[] = []): Promise<T | null> {
  const rows = await query<T>(sql, params);
  return rows.length > 0 ? rows[0] : null;
}
