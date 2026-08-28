import { NextApiRequest, NextApiResponse } from 'next';
import { queryOne } from '@/lib/db';
import PDFDocument from 'pdfkit';
import path from 'path';

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method !== 'GET') {
    return res.status(405).json({ erro: 'Método não permitido' });
  }

  try {
    const slug = (req.query.slug as string) || '';

    if (!slug) {
      return res.status(422).json({ erro: 'Slug não informado.' });
    }

    const orcamento = await queryOne('SELECT * FROM orcamentos_b2b WHERE slug = ?', [slug]);
    if (!orcamento) {
      return res.status(404).json({ erro: 'Orçamento não encontrado.' });
    }

    let dados: any = {};
    try {
      dados = typeof orcamento.dados_json === 'string'
        ? JSON.parse(orcamento.dados_json)
        : (orcamento.dados_json || {});
    } catch (e) {}

    const doc = new PDFDocument({
      size: 'A4',
      margins: { top: 30, bottom: 15, left: 30, right: 30 },
      bufferPages: true,
    });

    // Desativar margem inferior excessiva para evitar quebras automáticas de página
    doc.page.margins.bottom = 10;

    res.setHeader('Content-Type', 'application/pdf');
    res.setHeader('Content-Disposition', `attachment; filename="orcamento-${slug}.pdf"`);
    res.setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');

    doc.pipe(res);

    // Registrar fontes com suporte a acentos
    const fontDir = path.join(process.cwd(), 'vendor', 'dompdf', 'dompdf', 'lib', 'fonts');
    doc.registerFont('DejaVu', path.join(fontDir, 'DejaVuSans.ttf'));
    doc.registerFont('DejaVuBold', path.join(fontDir, 'DejaVuSans-Bold.ttf'));

    const gold = '#c5a880';
    const textPrimary = '#111111';
    const textSecondary = '#6B7280';
    const dividerColor = '#E5E7EB';
    const pageW = doc.page.width;
    const pageHeight = doc.page.height;
    const margin = 30;
    const contentW = pageW - margin * 2;

    // ========== BARRA DOURADA NO TOPO ==========
    doc.rect(0, 0, pageW, 4).fill(gold);

    // ========== LOGO ==========
    const logoPath = path.join(process.cwd(), 'assets', 'logo-contrato.png');
    try {
      doc.image(logoPath, margin, 18, { width: 105, fit: [105, 30] });
    } catch (e) {
      // Se o logo nao carregar, apenas ignora
    }

    // ========== HEADER ==========
    const headerY = 56;
    doc.fontSize(14).font('DejaVuBold').fillColor(textPrimary)
      .text(orcamento.titulo, margin, headerY, { width: contentW });

    doc.moveDown(0.12);
    doc.fontSize(8).font('DejaVu').fillColor(textSecondary)
      .text(`Preparado Para: ${orcamento.cliente_nome}${orcamento.cliente_empresa ? ', ' + orcamento.cliente_empresa : ''}`, margin);

    // Divider
    doc.moveTo(margin, doc.y + 3).lineTo(pageW - margin, doc.y + 3).strokeColor(gold).lineWidth(1.2).stroke();
    doc.moveDown(0.4);

    // ========== VISAO GERAL ==========
    if (dados.overview) {
      doc.fontSize(9).font('DejaVuBold').fillColor(gold)
        .text('VISÃO GERAL', margin);
      doc.moveDown(0.15);
      doc.fontSize(7.5).font('DejaVu').fillColor(textPrimary)
        .text(dados.overview, margin, doc.y, { width: contentW, lineGap: 0.8 });
      doc.moveDown(0.2);
    }

    // ========== INVESTIMENTO ==========
    if (dados.custo) {
      if (doc.y > 40) {
        doc.moveTo(margin, doc.y).lineTo(pageW - margin, doc.y).strokeColor(dividerColor).lineWidth(0.5).stroke();
        doc.moveDown(0.15);
      }

      doc.fontSize(9).font('DejaVuBold').fillColor(gold)
        .text('INVESTIMENTO', margin);
      doc.moveDown(0.08);

      if (dados.custo.descricao) {
        doc.fontSize(7.5).font('DejaVu').fillColor(textPrimary)
          .text(dados.custo.descricao, margin, doc.y, { width: contentW, lineGap: 0.8 });
        doc.moveDown(0.12);
      }

      if (dados.custo.entregaveis?.length) {
        for (const item of dados.custo.entregaveis) {
          doc.fontSize(7.5).font('DejaVu').fillColor(textPrimary)
            .text(`  ✓  ${item}`, margin, doc.y, { width: contentW });
          doc.moveDown(0.05);
        }
        doc.moveDown(0.08);
      }

      const valorFormatado = orcamento.valor_total.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
      doc.fontSize(9.5).font('DejaVuBold').fillColor(gold)
        .text(`Total: R$ ${valorFormatado}`, margin, doc.y, { align: 'right', width: contentW });
      doc.moveDown(0.2);
    }

    // ========== CRONOGRAMA ==========
    if (dados.timeline) {
      if (doc.y > 40) {
        doc.moveTo(margin, doc.y).lineTo(pageW - margin, doc.y).strokeColor(dividerColor).lineWidth(0.5).stroke();
        doc.moveDown(0.15);
      }

      doc.fontSize(9).font('DejaVuBold').fillColor(gold)
        .text('CRONOGRAMA', margin);
      doc.moveDown(0.08);

      if (dados.timeline.duracao) {
        doc.fontSize(7.5).font('DejaVuBold').fillColor(textPrimary)
          .text(dados.timeline.duracao, margin);
        doc.moveDown(0.08);
      }

      if (dados.timeline.marcos?.length) {
        for (const marco of dados.timeline.marcos) {
          doc.fontSize(7.5).font('DejaVuBold').fillColor(textPrimary)
            .text(`• ${marco.fase}`, margin + 6, doc.y, { width: contentW - 6 });
          doc.moveDown(0.03);
          doc.fontSize(7.5).font('DejaVu').fillColor(textSecondary)
            .text(`  ${marco.descricao}`, margin + 6, doc.y, { width: contentW - 6, lineGap: 0.5 });
          doc.moveDown(0.08);
        }
      }
      doc.moveDown(0.2);
    }

    // ========== PROXIMO PASSO ==========
    if (dados.proximo_passo) {
      if (doc.y > 40) {
        doc.moveTo(margin, doc.y).lineTo(pageW - margin, doc.y).strokeColor(dividerColor).lineWidth(0.5).stroke();
        doc.moveDown(0.15);
      }

      doc.fontSize(9).font('DejaVuBold').fillColor(gold)
        .text('PRÓXIMO PASSO', margin);
      doc.moveDown(0.08);
      doc.fontSize(7.5).font('DejaVu').fillColor(textPrimary)
        .text(dados.proximo_passo, margin, doc.y, { width: contentW, lineGap: 0.8 });
      doc.moveDown(0.2);
    }

    // ========== TERMOS E CONDICOES ==========
    if (dados.termos?.length) {
      if (doc.y > 40) {
        doc.moveTo(margin, doc.y).lineTo(pageW - margin, doc.y).strokeColor(dividerColor).lineWidth(0.5).stroke();
        doc.moveDown(0.15);
      }

      doc.fontSize(9).font('DejaVuBold').fillColor(gold)
        .text('TERMOS E CONDIÇÕES', margin);
      doc.moveDown(0.08);

      dados.termos.forEach((termo: string, idx: number) => {
        doc.fontSize(7.5).font('DejaVu').fillColor(textPrimary)
          .text(`${idx + 1}. ${termo}`, margin + 6, doc.y, { width: contentW - 6, lineGap: 0.5 });
        doc.moveDown(0.06);
      });
      doc.moveDown(0.15);
    }

    // ========== FOOTER POSICIONADO LOGO ABAIXO DO CONTEÚDO ==========
    const footerY = Math.min(doc.y + 12, pageHeight - 20);

    // Linha dourada no footer
    doc.moveTo(margin, footerY).lineTo(pageW - margin, footerY).strokeColor(gold).lineWidth(1).stroke();

    const criadoEm = orcamento.criado_em
      ? new Date(orcamento.criado_em).toLocaleDateString('pt-BR')
      : new Date().toLocaleDateString('pt-BR');

    // Footer em uma unica linha
    doc.fontSize(6.5).font('DejaVu').fillColor(textSecondary)
      .text(`Proposta criada em: ${criadoEm}  |  Poncem Studio LTDA  |  CNPJ: 50.768.732/0001-63  |  contato@wedistinto.com  |  wedistinto.com`, margin, footerY + 4, { width: contentW, lineBreak: false });

    doc.end();
  } catch (err: any) {
    console.error('Erro ao gerar PDF B2B:', err);
    if (!res.headersSent) {
      return res.status(500).json({ erro: err.message });
    }
  }
}
