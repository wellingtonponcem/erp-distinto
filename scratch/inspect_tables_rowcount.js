const { Client } = require('pg');

if (!process.env.NEON_PASSWORD) throw new Error('NEON_PASSWORD missing');
const oldConnectionString = `postgresql://neondb_owner:${process.env.NEON_PASSWORD}@ep-divine-star-acx7crrn-pooler.sa-east-1.aws.neon.tech/neondb?sslmode=require`;
const newConnectionString = `postgresql://neondb_owner:${process.env.NEON_PASSWORD}@ep-patient-moon-acgcdcuv-pooler.sa-east-1.aws.neon.tech/neondb?sslmode=require`;

async function check() {
  const oldClient = new Client({ connectionString: oldConnectionString });
  const newClient = new Client({ connectionString: newConnectionString });

  await oldClient.connect();
  await newClient.connect();

  console.log('--- BANCO ANTIGO (ep-divine-star-acx7crrn) ---');
  const resOld = await oldClient.query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
  for (const row of resOld.rows) {
    const table = row.table_name;
    const countRes = await oldClient.query(`SELECT COUNT(*) FROM "${table}"`);
    console.log(`Tabela: ${table} -> Linhas: ${countRes.rows[0].count}`);
  }

  console.log('\n--- BANCO NOVO (ep-patient-moon-acgcdcuv) ---');
  const resNew = await newClient.query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
  for (const row of resNew.rows) {
    const table = row.table_name;
    const countRes = await newClient.query(`SELECT COUNT(*) FROM "${table}"`);
    console.log(`Tabela: ${table} -> Linhas: ${countRes.rows[0].count}`);
  }

  await oldClient.end();
  await newClient.end();
}

check();
