const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, '..', 'contrato_jeanenuneswellingtonponcemdocumentoc.html');
let html = fs.readFileSync(filePath, 'utf-8');

// 1. Substituir a imagem quebrada src="images/image5.png" por uma logo limpa embutida ou marcador selecionavel
const logoBase64 = `data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="120" height="80" viewBox="0 0 120 80"><rect width="120" height="80" fill="%23000" rx="8"/><text x="60" y="45" font-family="Arial" font-size="16" font-weight="bold" fill="%23fff" text-anchor="middle">PONCEM</text><text x="60" y="62" font-family="Arial" font-size="9" fill="%23aaa" text-anchor="middle">STUDIO</text></svg>`;

html = html.replace(/src="images\/image5\.png"/g, `src="${logoBase64}" alt="Logo Poncem Studio"`);

// 2. Substituir o número fixo do contrato pela variável {{NUMERO_CONTRATO}}
html = html.replace(/2026\/830b/g, '{{NUMERO_CONTRATO}}');

// 3. Corrigir mojibakes de codificação no texto
const correcoes = [
  [/presta9ao/gi, 'prestação'],
  [/servi9os/gi, 'serviços'],
  [/condi96es/gi, 'condições'],
  [/clausulas/gi, 'cláusulas'],
  [/execu9Ao/gi, 'execução'],
  [/dura9ao/gi, 'duração'],
  [/realiza9ao/gi, 'realização'],
  [/especifica96es/gi, 'especificações'],
  [/sele'tao/gi, 'seleção'],
  [/disposi9ao/gi, 'disposição'],
  [/autoriza9ao/gi, 'autorização'],
  [/divulga9ao/gi, 'divulgação'],
  [/reprodu9ao/gi, 'reprodução'],
  [/exibi9ao/gi, 'exibição'],
  [/publica9ao/gi, 'publicação'],
  [/obriga&lt;;:OES|obriga96es/gi, 'obrigações'],
  [/capta9ao/gi, 'captação'],
  [/altera9ao/gi, 'alteração'],
  [/confian9a/gi, 'confiança'],
  [/rescisao/gi, 'rescisão'],
  [/multa rescis6ria/gi, 'multa rescisória'],
  [/vigencia/gi, 'vigência'],
];

for (const [pattern, replacement] of correcoes) {
  html = html.replace(pattern, replacement);
}

fs.writeFileSync(filePath, html);
console.log('✓ Arquivo contrato_jeanenuneswellingtonponcemdocumentoc.html corrigido e limpo com sucesso!');
