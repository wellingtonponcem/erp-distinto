import { SlideCtx, formatarMoeda } from '@/lib/propostas/common';

export function render(ctx: SlideCtx): string {
  const { proposta, dados: d, tipo, cliente, empresa, mesNome, ano, categoriaProjeto, slug } = ctx;

  const hoje = new Date().toISOString().split('T')[0];
  const vencida = proposta.validade < hoje;
  const validadeFormatada =
    String(proposta.validade || '')
      .split('T')[0]
      .split('-')
      .reverse()
      .join('/') || '01/01/1970';

  const validadePill = vencida
    ? `    <i data-lucide="alert-circle" style="width: 14px; height: 14px;"></i>
                    PROPOSTA VENCIDA EM ${validadeFormatada}`
    : `    PROPOSTA VÁLIDA ATÉ: ${validadeFormatada}`;

  return `    <!-- Slide 1: Hero -->
    <section class="proposal-page">
        <div class="page-content">
            <h1>IT'S HER <br><span>TIME</span>.</h1>
            <p style="font-size: 24px; font-weight: 700; margin-top: 20px; color: var(--accent);">${cliente}</p>
        </div>
    </section>

    <!-- Slide 2: The Concept -->
    <section class="proposal-page">
        <div class="page-content">
            <div style="font-size: 14px; text-transform: uppercase; letter-spacing: 4px; color: #666; margin-bottom: 20px;">The Concept</div>
            <div style="font-size: 48px; line-height: 1.1; font-weight: 600; max-width: 800px;">
                ${d.secoes?.intro ?? 'Transformando o sonho dos 15 anos em uma experiência audiovisual cinematográfica.'}
            </div>
        </div>
    </section>

    <!-- Slide 3: Grid Details -->
    <section class="proposal-page">
        <div class="page-content">
            <div class="grid">
                <div class="grid-item">
                    <h3>Visuals</h3>
                    <p>${d.secoes?.visuals ?? 'Captação 4K com estética de cinema e edição dinâmica.'}</p>
                </div>
                <div class="grid-item">
                    <h3>Experience</h3>
                    <p>${d.secoes?.experiencia ?? 'Imersão total no evento com cobertura em tempo real.'}</p>
                </div>
                <div class="grid-item">
                    <h3>Deliverables</h3>
                    <p>${d.secoes?.entregaveis ?? 'Filme principal, teaser para redes sociais e fotos tratadas.'}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Slide 4: Investimento -->
    <section class="proposal-page">
        <div class="page-content" style="align-items: center; text-align: center;">
            <div style="color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px;">O Investimento</div>
            <div class="price-tag">${formatarMoeda(proposta.valor_total)}</div>
            <div style="margin-top: 30px; padding: 10px 20px; border: 1px solid ${vencida ? 'var(--accent)' : '#333'}; border-radius: 50px; font-size: 10px; text-transform: uppercase; letter-spacing: 2px; color: ${vencida ? 'var(--accent)' : '#666'}; display: flex; align-items: center; justify-content: center; gap: 8px;">
                ${validadePill}
            </div>

            <p style="color: #666; margin-top: 20px; font-size: 12px; letter-spacing: 2px; text-transform: uppercase;">Digital Presence by DISTINTO</p>
        </div>
    </section>
`;
}