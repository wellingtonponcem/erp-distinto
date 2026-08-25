const fs = require('fs');
const path = require('path');
const mysql = require('mysql2/promise');

async function importContracts() {
  const p = mysql.createPool({
    host: process.env.MYSQL_HOST || 'srv952.hstgr.io',
    port: parseInt(process.env.MYSQL_PORT || '3306'),
    database: process.env.MYSQL_DATABASE || 'u306254544_distinto',
    user: process.env.MYSQL_USER || 'u306254544_poncem',
    password: process.env.MYSQL_PASSWORD || '!@Jeane&w#1'
  });

  const contratoDir = path.join(__dirname, '..', 'contrato');
  
  const seeded = [
    {
      id: 'contrato_pedro_giovana',
      titulo: 'Contrato de Prestação de Serviços - Pedro & Giovana (Casamento)',
      cliente_nome: 'Pedro & Giovana',
      cliente_email: 'pedroegiovana@gmail.com',
      valor_total: 12500.00,
      status: 'assinado',
      arquivo_html: '20260303_CONTRATO_PEDROEGIOVANA_CASAMENTO.html',
      arquivo_pdf: '20260303_CONTRATO_PEDRO-E-GIOVANA_CASAMENTO.pdf'
    },
    {
      id: 'contrato_melissa_15anos',
      titulo: 'Contrato de Prestação de Serviços - Melissa T. (15 Anos)',
      cliente_nome: 'Melissa T.',
      cliente_email: 'melissat@gmail.com',
      valor_total: 8900.00,
      status: 'assinado',
      arquivo_html: '20260203_CONTRATO_MELISSAT_15ANOS.html',
      arquivo_pdf: '20260203_CONTRATO_MELISSA-T_15ANOS.pdf'
    }
  ];

  for (const item of seeded) {
    let htmlContent = '';
    const filePath = path.join(contratoDir, item.arquivo_html);
    if (fs.existsSync(filePath)) {
      htmlContent = fs.readFileSync(filePath, 'utf-8');
    }

    const dadosJson = JSON.stringify({
      arquivo_html: item.arquivo_html,
      arquivo_pdf: item.arquivo_pdf,
      contrato_texto: htmlContent,
      clausulas: 'Prestação de serviços de fotografia e cinematografia conforme contrato impresso/digital assinados pelas partes.'
    });

    await p.query(
      `INSERT INTO contratos (
        id, titulo, cliente_nome, cliente_email, valor_total, status, dados_json
      ) VALUES (?, ?, ?, ?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE
        titulo = VALUES(titulo),
        cliente_nome = VALUES(cliente_nome),
        valor_total = VALUES(valor_total),
        status = VALUES(status),
        dados_json = VALUES(dados_json)`,
      [item.id, item.titulo, item.cliente_nome, item.cliente_email, item.valor_total, item.status, dadosJson]
    );

    console.log('✓ Contrato gravado no MySQL Hostinger:', item.titulo);
  }

  const [all] = await p.query('SELECT id, titulo, cliente_nome, valor_total, status FROM contratos');
  console.log('TOTAL DE CONTRATOS NO BANCO:', all.length);
  console.log(all);

  await p.end();
}

importContracts().catch(console.error);
