import PDFDocument from 'pdfkit';

export interface ContractPdfData {
  titulo: string;
  cliente_nome: string;
  valor_total: number;
  data_contrato?: string;
  condicoes_pagamento?: string;
  conteudo_clausulas?: string;
  status?: string;
}

export function generateContractPdf(data: ContractPdfData): Promise<Buffer> {
  return new Promise((resolve, reject) => {
    try {
      const doc = new PDFDocument({ margin: 50, size: 'A4' });
      const buffers: Buffer[] = [];

      doc.on('data', (chunk) => buffers.push(chunk));
      doc.on('end', () => resolve(Buffer.concat(buffers)));
      doc.on('error', (err) => reject(err));

      // Cabeçalho
      doc
        .fillColor('#111827')
        .fontSize(20)
        .font('Helvetica-Bold')
        .text('CONTRATO DE PRESTAÇÃO DE SERVIÇOS', { align: 'center' });

      doc.moveDown(0.5);

      doc
        .fillColor('#4B5563')
        .fontSize(12)
        .font('Helvetica')
        .text(`Título: ${data.titulo || 'Contrato'}`, { align: 'center' });

      doc.moveDown(1.5);
      doc.strokeColor('#E5E7EB').lineWidth(1).moveTo(50, doc.y).lineTo(545, doc.y).stroke();
      doc.moveDown(1.5);

      // Partes / Dados
      doc
        .fillColor('#111827')
        .fontSize(12)
        .font('Helvetica-Bold')
        .text('1. CONTRATANTE E CONTRATADO');

      doc.moveDown(0.5);

      doc
        .fillColor('#374151')
        .fontSize(10)
        .font('Helvetica')
        .text(`Cliente Contratante: ${data.cliente_nome || 'N/A'}`)
        .text(`Data do Contrato: ${data.data_contrato || new Date().toLocaleDateString('pt-BR')}`)
        .text(`Valor Total: R$ ${data.valor_total ? data.valor_total.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) : '0,00'}`);

      doc.moveDown(1.5);

      // Condições de Pagamento
      if (data.condicoes_pagamento) {
        doc
          .fillColor('#111827')
          .fontSize(12)
          .font('Helvetica-Bold')
          .text('2. CONDIÇÕES DE PAGAMENTO');

        doc.moveDown(0.5);

        doc
          .fillColor('#374151')
          .fontSize(10)
          .font('Helvetica')
          .text(data.condicoes_pagamento);

        doc.moveDown(1.5);
      }

      // Cláusulas Adicionais
      if (data.conteudo_clausulas) {
        doc
          .fillColor('#111827')
          .fontSize(12)
          .font('Helvetica-Bold')
          .text('3. CLÁUSULAS E CONDIÇÕES');

        doc.moveDown(0.5);

        // Remover HTML tags se houver
        const cleanText = data.conteudo_clausulas.replace(/<[^>]*>?/gm, '');
        doc
          .fillColor('#374151')
          .fontSize(10)
          .font('Helvetica')
          .text(cleanText, { align: 'justify' });

        doc.moveDown(2);
      }

      // Assinaturas
      doc.moveDown(3);
      const y = doc.y;

      doc.strokeColor('#9CA3AF').lineWidth(1).moveTo(60, y).lineTo(250, y).stroke();
      doc.strokeColor('#9CA3AF').lineWidth(1).moveTo(300, y).lineTo(490, y).stroke();

      doc
        .fillColor('#374151')
        .fontSize(9)
        .font('Helvetica')
        .text('CONTRATANTE', 60, y + 10, { width: 190, align: 'center' })
        .text('CONTRATADO', 300, y + 10, { width: 190, align: 'center' });

      doc.end();
    } catch (err) {
      reject(err);
    }
  });
}
