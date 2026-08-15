import { Pool } from '@neondatabase/serverless';
import { Pool as PgPool } from 'pg';

let poolInstance: any = null;

export function getDbPool(): any {
  if (!poolInstance) {
    const connectionString = process.env.DATABASE_URL || process.env.POSTGRES_URL;
    
    if (connectionString && connectionString.includes('neon.tech')) {
      poolInstance = new Pool({ connectionString });
    } else if (connectionString) {
      poolInstance = new PgPool({ connectionString });
    } else {
      const host = process.env.DB_HOST || 'localhost';
      const port = parseInt(process.env.DB_PORT || '5432');
      const database = process.env.DB_NAME || 'erp-distinto';
      const user = process.env.DB_USER || 'postgres';
      const password = process.env.DB_PASS || '';

      poolInstance = new PgPool({
        host,
        port,
        database,
        user,
        password,
        ssl: host.includes('neon.tech') ? { rejectUnauthorized: false } : false
      });
    }
  }

  return poolInstance;
}

export async function query<T = any>(sql: string, params: any[] = []): Promise<T[]> {
  const p = getDbPool();
  const res = await p.query(sql, params);
  return res.rows as T[];
}

export async function queryOne<T = any>(sql: string, params: any[] = []): Promise<T | null> {
  const rows = await query<T>(sql, params);
  return rows.length > 0 ? rows[0] : null;
}
