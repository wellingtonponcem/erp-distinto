const mysql = require('mysql2/promise');

async function run() {
  const p = mysql.createPool({
    host: process.env.MYSQL_HOST || 'srv952.hstgr.io',
    port: parseInt(process.env.MYSQL_PORT || '3306'),
    database: process.env.MYSQL_DATABASE || 'u306254544_distinto',
    user: process.env.MYSQL_USER || 'u306254544_poncem',
    password: process.env.MYSQL_PASSWORD || '!@Jeane&w#1'
  });

  const [props] = await p.query('SELECT * FROM propostas WHERE slug LIKE ? OR cliente_nome LIKE ?', ['%igor%', '%Igor%']);
  console.log('PROPOSTA IGOR & GABRIELA ENCONTRADA:', props[0]?.titulo);

  if (props[0]) {
    const prop = props[0];
    let propDados = {};
    try {
      propDados = JSON.parse(prop.dados_json || '{}');
    } catch (e) {}

    const contractId = 'contrato_igor_gabriela';
    const contractTitle = 'Contrato de Prestação de Serviços - Igor Onofre & Gabriela Vianna (Casamento)';

    const fullContractHtml = `
      <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1a1a1a; padding: 24px; background: #fff; border-radius: 12px; max-width: 800px; margin: 0 auto;">
        <div style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px;">
          <h2 style="margin: 0; text-transform: uppercase; letter-spacing: 1px; color: #000;">CONTRATO DE PRESTAÇÃO DE SERVIÇOS AUDIOVISUAIS</h2>
          <p style="margin: 5px 0 0 0; font-size: 12px; font-weight: bold; color: #666;">ERP DISTINTO • PONCEM STUDIO LTDA</p>
        </div>

        <p><strong>CONTRATADA:</strong> PONCEM STUDIO LTDA, empresa especializada em fotografia e filmmaking de casamentos, com sede em Vitória/ES.</p>
        <p><strong>CONTRATANTES:</strong> IGOR ONOFRE E GABRIELA VIANNA, noivos contratantes para a realização de cobertura de casamento.</p>

        <h3 style="border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 25px;">CLÁUSULA 1ª — DO OBJETO E ESCOPO</h3>
        <p>O presente contrato tem por objeto a prestação de serviços de cobertura de Fotografia e Filmmaking Cinema para o casamento dos CONTRATANTES a ser realizado na data acordada de <strong>10 de Maio de 2026</strong>.</p>
        <p>O pacote contratado inclui a entrega da galeria digital em altíssima resolução, filme teaser cinematic, vídeo na íntegra editado com sonorização profissional e cobertura completa da cerimônia e recepção.</p>

        <h3 style="border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 25px;">CLÁUSULA 2ª — DO INVESTIMENTO E CONDIÇÕES</h3>
        <p>Pela prestação dos serviços objeto deste instrumento, os CONTRATANTES pagarão à CONTRATADA o valor total acordado, parcelado mensalmente via boleto/PIX ou cartão de crédito no sistema Asaas.</p>

        <h3 style="border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 25px;">CLÁUSULA 3ª — DIREITOS AUTORAIS E CESSÃO DE USO</h3>
        <p>Os CONTRATANTES declaram estar cientes e autorizam o uso de imagem exclusivamente para composição do portfólio artístico da CONTRATADA.</p>

        <div style="margin-top: 60px; display: flex; justify-content: space-between; text-align: center; font-size: 12px;">
          <div style="border-top: 1px solid #000; width: 45%; padding-top: 8px;">
            <strong>PONCEM STUDIO LTDA</strong><br />
            CONTRATADA
          </div>
          <div style="border-top: 1px solid #000; width: 45%; padding-top: 8px;">
            <strong>IGOR ONOFRE & GABRIELA VIANNA</strong><br />
            CONTRATANTES
          </div>
        </div>
      </div>
    `;

    const dadosJson = JSON.stringify({
      contrato_texto: fullContractHtml,
      clausulas: 'Prestação de serviços de fotografia e filmagem cinema para o casamento dos CONTRATANTES em 10/05/2026.',
      signatario_1: {
        nome: 'Igor Onofre & Gabriela Vianna',
        email: 'igoregabriela@gmail.com',
        cpf: '',
        telefone: propDados.whatsapp || ''
      }
    });

    await p.query(
      `INSERT INTO contratos (
        id, proposta_id, titulo, cliente_nome, cliente_email, valor_total, status, dados_json
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE
        titulo = VALUES(titulo),
        cliente_nome = VALUES(cliente_nome),
        valor_total = VALUES(valor_total),
        status = VALUES(status),
        dados_json = VALUES(dados_json)`,
      [contractId, prop.id, contractTitle, 'Igor Onofre & Gabriela Vianna', 'igoregabriela@gmail.com', parseFloat(prop.valor_total || 0), 'assinado', dadosJson]
    );

    console.log('✓ Contrato de Igor Onofre & Gabriela Vianna gravado no MySQL Hostinger!');
  }

  const [all] = await p.query('SELECT id, titulo, cliente_nome, valor_total, status FROM contratos');
  console.log('TODOS OS CONTRATOS NO MYSQL HOSTINGER (TOTAL ' + all.length + '):');
  console.log(all);

  await p.end();
}

run().catch(console.error);
