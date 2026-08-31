const fs = require('fs');
const path = require('path');
const mysql = require('mysql2/promise');

function renderMasterContractHtml(data) {
  const templatePath = path.join(__dirname, '..', 'contrato_jeanenuneswellingtonponcemdocumentoc.html');
  let rawHtml = fs.readFileSync(templatePath, 'utf-8');

  const numContrato = data.numero_contrato || `2026/${(data.id || '830b').substring(0, 6)}`;
  const clienteNome = data.cliente_nome || 'CONTRATANTE';
  const clienteCpfCnpj = data.cliente_cpf_cnpj || '000.000.000-00';
  const valorTotalNum = parseFloat(String(data.valor_total || 0));
  const valorTotalStr = `R$ ${valorTotalNum.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;

  let html = rawHtml;

  // 1. Número do Contrato
  html = html.replace(/CASAMENTO N&deg; 2026\/830b|CASAMENTO N° 2026\/830b/g, `CASAMENTO N° ${numContrato}`);

  // 2. Contratantes
  const contratanteBlock = `<span class="c16 c12 c11">${clienteNome}</span>, <span class="c12 c11">portador(a) do CPF/CNPJ nº </span><span class="c73 c12">${clienteCpfCnpj}</span>${data.cliente_email ? `, e-mail <span class="c12 c11">${data.cliente_email}</span>` : ''}, <span class="c12 c11">doravante denominado(a) simplesmente </span><span class="c16 c12 c11">CONTRATANTE</span>.`;
  
  html = html.replace(
    /<p class="c164"><span class="c16 c12 c11">Jeane Nunes,<\/span>[\s\S]*?<\/p>/,
    `<p class="c164">${contratanteBlock}</p>`
  );

  // 3. Valor e Pagamento (Cláusula Terceira)
  const condicoesPagamentoStr = data.condicoes_pagamento || 'Entrada de 20% + Saldo parcelado em até 6x (dependendo do pacote selecionado).';
  const clausulaValorBlock = `3.1. Pela prestação dos serviços contratados, os <span class="c24 c16 c12 c11">CONTRATANTES</span> pagarão à <span class="c24 c16 c12 c11">CONTRATADA</span> a quantia total de <span class="c24 c16 c127 c11">${valorTotalStr}</span>, nas seguintes condições: ${condicoesPagamentoStr}`;

  html = html.replace(
    /<p class="c75"><span class="c6 c11">3\.7\. Pela prestac;ao[\s\S]*?<\/p>/,
    `<p class="c75"><span class="c6 c11">${clausulaValorBlock}</span></p>`
  );

  if (data.data_evento) {
    html = html.replace(
      /24<\/span> <span class="c24 c16 c12 c11">de dezembro de<\/span> <span class="c24 c16 c11 c127">2026/g,
      `${data.data_evento}`
    );
  }

  return html;
}

async function updateAllContracts() {
  if (!process.env.MYSQL_HOST || !process.env.MYSQL_DATABASE || !process.env.MYSQL_USER || !process.env.MYSQL_PASSWORD) throw new Error('MYSQL_* env vars missing');
  const p = mysql.createPool({
    host: process.env.MYSQL_HOST,
    port: parseInt(process.env.MYSQL_PORT || '3306'),
    database: process.env.MYSQL_DATABASE,
    user: process.env.MYSQL_USER,
    password: process.env.MYSQL_PASSWORD
  });

  const [rows] = await p.query('SELECT * FROM contratos');
  console.log(`Atualizando ${rows.length} contratos no MySQL Hostinger com o modelo base oficial...`);

  for (const c of rows) {
    let dados = {};
    try {
      dados = typeof c.dados_json === 'string' ? JSON.parse(c.dados_json) : (c.dados_json || {});
    } catch (e) {}

    const clienteNome = c.cliente_nome || 'Cliente Contratante';
    const valTotalNum = parseFloat(c.valor_total || c.valor || 0);

    const masterHtml = renderMasterContractHtml({
      id: c.id,
      titulo: c.titulo,
      cliente_nome: clienteNome,
      cliente_cpf_cnpj: c.cliente_cpf_cnpj || dados.signatario_1?.cpf || '',
      cliente_email: c.cliente_email || dados.signatario_1?.email || '',
      cliente_telefone: c.cliente_telefone || dados.signatario_1?.telefone || '',
      valor_total: valTotalNum,
      condicoes_pagamento: dados.forma_pagamento,
      clausulas_personalizadas: dados.clausulas
    });

    dados.contrato_texto = masterHtml;
    const novosDadosJson = JSON.stringify(dados);

    await p.query('UPDATE contratos SET dados_json = ? WHERE id = ?', [novosDadosJson, c.id]);
    console.log(`✓ Contrato '${c.titulo}' (${c.id}) atualizado com o modelo oficial!`);
  }

  await p.end();
  console.log('Finalizado com sucesso!');
}

updateAllContracts().catch(console.error);
