const { Client } = require('pg');
if (!process.env.NEON_PASSWORD) throw new Error('NEON_PASSWORD missing');
const connectionString = `postgresql://neondb_owner:${process.env.NEON_PASSWORD}@ep-patient-moon-acgcdcuv-pooler.sa-east-1.aws.neon.tech/neondb?sslmode=require`;

async function main() {
  const client = new Client({ connectionString });
  await client.connect();
  const config = await client.query('SELECT * FROM configuracao_empresa');
  const lanc = await client.query('SELECT COUNT(*) FROM lancamentos');
  const cli = await client.query('SELECT COUNT(*) FROM clientes');
  console.log('Configuracao:', config.rows);
  console.log('Total Lancamentos:', lanc.rows[0].count);
  console.log('Total Clientes:', cli.rows[0].count);
  await client.end();
}

main();
