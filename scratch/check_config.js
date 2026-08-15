const { Client } = require('pg');
const connectionString = 'postgresql://neondb_owner:npg_y9elwYoUx3nz@ep-patient-moon-acgcdcuv-pooler.sa-east-1.aws.neon.tech/neondb?sslmode=require';

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
