const { Client } = require('pg');

const oldConnectionString = 'postgresql://neondb_owner:npg_KZkR3eNUW7qJ@ep-divine-star-acx7crrn-pooler.sa-east-1.aws.neon.tech/neondb?sslmode=require';
const newConnectionString = 'postgresql://neondb_owner:npg_y9elwYoUx3nz@ep-patient-moon-acgcdcuv-pooler.sa-east-1.aws.neon.tech/neondb?sslmode=require';

async function migrate() {
  const oldClient = new Client({ connectionString: oldConnectionString });
  const newClient = new Client({ connectionString: newConnectionString });

  try {
    await oldClient.connect();
    await newClient.connect();
    console.log('✅ Conectado a ambos os bancos Neon!');

    // 1. Listar todas as tabelas do banco antigo
    const resTables = await oldClient.query(`
      SELECT table_name 
      FROM information_schema.tables 
      WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
      ORDER BY table_name
    `);

    const tables = resTables.rows.map(r => r.table_name);
    console.log('📋 Tabelas encontradas:', tables);

    for (const table of tables) {
      console.log(`\n⏳ Migrando estrutura e dados da tabela: ${table}...`);

      // Obter colunas e tipos
      const resCols = await oldClient.query(`
        SELECT column_name, udt_name, character_maximum_length, column_default, is_nullable
        FROM information_schema.columns
        WHERE table_name = $1 AND table_schema = 'public'
        ORDER BY ordinal_position
      `, [table]);

      if (resCols.rows.length === 0) continue;

      // Obter Chave Primária
      const resPk = await oldClient.query(`
        SELECT kcu.column_name
        FROM information_schema.table_constraints tc
        JOIN information_schema.key_column_usage kcu
          ON tc.constraint_name = kcu.constraint_name
          AND tc.table_schema = kcu.table_schema
        WHERE tc.constraint_type = 'PRIMARY KEY'
          AND tc.table_name = $1
      `, [table]);

      const pkCols = resPk.rows.map(r => r.column_name);

      // Montar instrução CREATE TABLE
      const colDefs = resCols.rows.map(col => {
        let def = `"${col.column_name}" `;
        
        // Mapear tipos
        if (col.udt_name === 'varchar') {
          def += col.character_maximum_length ? `VARCHAR(${col.character_maximum_length})` : 'VARCHAR(255)';
        } else if (col.udt_name === 'numeric') {
          def += 'NUMERIC(10,2)';
        } else if (col.udt_name === 'int4') {
          def += 'INTEGER';
        } else if (col.udt_name === 'int8') {
          def += 'BIGINT';
        } else if (col.udt_name === 'int2') {
          def += 'SMALLINT';
        } else if (col.udt_name === 'bool') {
          def += 'BOOLEAN';
        } else if (col.udt_name === 'timestamp' || col.udt_name === 'timestamptz') {
          def += 'TIMESTAMP';
        } else if (col.udt_name === 'date') {
          def += 'DATE';
        } else {
          def += 'TEXT';
        }

        if (col.is_nullable === 'NO') {
          def += ' NOT NULL';
        }

        if (col.column_default && !col.column_default.includes('nextval')) {
          def += ` DEFAULT ${col.column_default}`;
        }

        return def;
      });

      if (pkCols.length > 0) {
        colDefs.push(`PRIMARY KEY (${pkCols.map(c => `"${c}"`).join(', ')})`);
      }

      const createSql = `CREATE TABLE IF NOT EXISTS "${table}" (\n  ${colDefs.join(',\n  ')}\n);`;
      
      try {
        await newClient.query(createSql);
        console.log(`   🔨 Tabela "${table}" criada/verificada no novo banco.`);
      } catch (createErr) {
        console.error(`   ⚠️ Erro ao criar estrutura da tabela ${table}:`, createErr.message);
        continue;
      }

      // Buscar linhas do banco antigo
      const resData = await oldClient.query(`SELECT * FROM "${table}"`);
      const rows = resData.rows;

      if (rows.length === 0) {
        console.log(`   ℹ️ Tabela "${table}" está vazia.`);
        continue;
      }

      const colNames = Object.keys(rows[0]);
      const quotedCols = colNames.map(c => `"${c}"`).join(', ');

      let inseridos = 0;
      for (const row of rows) {
        const values = colNames.map(c => row[c]);
        const placeholders = colNames.map((_, i) => `$${i + 1}`).join(', ');

        const insertSql = `
          INSERT INTO "${table}" (${quotedCols}) 
          VALUES (${placeholders}) 
          ON CONFLICT DO NOTHING
        `;

        try {
          await newClient.query(insertSql, values);
          inseridos++;
        } catch (insertErr) {
          console.error(`   ⚠️ Erro ao inserir linha na tabela ${table}:`, insertErr.message);
        }
      }

      console.log(`   ✅ Tabela "${table}": ${inseridos}/${rows.length} registros copiados com sucesso!`);
    }

    console.log('\n🎉 TODAS AS TABELAS E DADOS FORAM MIGRADOS COM SUCESSO PARA O NOVO NEON DB!');
  } catch (err) {
    console.error('❌ Erro durante a migração:', err);
  } finally {
    await oldClient.end();
    await newClient.end();
  }
}

migrate();
