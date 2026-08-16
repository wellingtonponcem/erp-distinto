import { SlideCtx, sanitizar, formatarMoeda, number_format, mbUpper } from '@/lib/propostas/common';

/** Emula empty($x). */
function vazio(x: any): boolean {
  return !x || x === '' || (Array.isArray(x) && x.length === 0);
}

/** Emula nl2br($s). */
function nl2br(s: any): string {
  return String(s ?? '').replace(/\r\n|\r|\n/g, '<br />');
}

/** Port fiel de semanaOrdinal() do template-marketing.php. */
function semanaOrdinal(n: number): string {
  const ordinais: Record<number, string> = {
    1: 'PRIMEIRA', 2: 'SEGUNDA', 3: 'TERCEIRA', 4: 'QUARTA', 5: 'QUINTA',
    6: 'SEXTA', 7: 'SÉTIMA', 8: 'OITAVA', 9: 'NONA', 10: 'DÉCIMA',
    11: 'DÉCIMA PRIMEIRA', 12: 'DÉCIMA SEGUNDA', 13: 'DÉCIMA TERCEIRA', 14: 'DÉCIMA QUARTA', 15: 'DÉCIMA QUINTA',
  };
  return ordinais[n] ?? `${n}ª`;
}

const mesesPt: Record<string, string> = {
  '1': 'JANEIRO', '2': 'FEVEREIRO', '3': 'MARÇO', '4': 'ABRIL', '5': 'MAIO', '6': 'JUNHO',
  '7': 'JULHO', '8': 'AGOSTO', '9': 'SETEMBRO', '10': 'OUTUBRO', '11': 'NOVEMBRO', '12': 'DEZEMBRO',
};

export function render(ctx: SlideCtx): string {
  const { proposta, dados: d, tipo, cliente, empresa, mesNome, ano, categoriaProjeto, slug } = ctx;

  const hoje = new Date();
  const hojeStr = `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}-${String(hoje.getDate()).padStart(2, '0')}`;
  const vencida = String(proposta.validade ?? '') < hojeStr;
  const partesValidade = String(proposta.validade ?? '').split('-');
  const validadeFormatada = /^\d{4}-\d{2}-\d{2}$/.test(String(proposta.validade ?? ''))
    ? `${partesValidade[2]}/${partesValidade[1]}/${partesValidade[0]}`
    : '01/01/1970';

  const tipoProj = String(proposta.tipo_projeto ?? proposta.titulo ?? '').toLowerCase();
  let tituloImpacto: string;
  if (tipoProj.includes('vídeo') || tipoProj.includes('video') || tipoProj.includes('filmmaker')) {
    tituloImpacto = 'CINEMATIC NARRATIVES THAT SELL.';
  } else if (tipoProj.includes('design')) {
    tituloImpacto = 'VISUAL IDENTITY THAT COMMANDS RESPECT.';
  } else {
    tituloImpacto = 'STRATEGIC PLANNING THAT MAKES SENSE.';
  }

  let saudacao = 'CLIENTE';
  if (!vazio(d.responsavel)) {
    const nomesBrutos = String(d.responsavel).split(/(?:\s+[eE]\s+|[,;]\s*)/).filter((n) => n !== '');
    const primeirosNomes = nomesBrutos.map((n) => n.trim().split(' ')[0]);
    const total = primeirosNomes.length;
    if (total === 1) {
      saudacao = primeirosNomes[0];
    } else if (total === 2) {
      saudacao = primeirosNomes[0] + ' e ' + primeirosNomes[1];
    } else if (total > 2) {
      const ultimo = primeirosNomes.pop();
      saudacao = primeirosNomes.join(', ') + ' e ' + ultimo;
    }
  } else {
    saudacao = String(proposta.cliente_nome ?? 'CLIENTE').trim().split(' ')[0];
  }

  const etapasAtivas: string[] = d.etapas_ativas ?? ['imersao', 'diagnostico', 'planejamento', 'linguagem_visual', 'entrega', 'gestao'];
  const etapasDias: Record<string, number> = d.etapas_dias ?? {
    imersao: 14,
    diagnostico: 7,
    planejamento: 28,
    linguagem_visual: 14,
    entrega: 7,
    gestao: 0,
  };

  const cronogramaEtapas: Record<string, string> = {};
  let diaAtual = 0;
  const etapasPossiveis = ['imersao', 'diagnostico', 'planejamento', 'linguagem_visual', 'entrega', 'gestao'];
  for (const eid of etapasPossiveis) {
    if (etapasAtivas.includes(eid)) {
      const dias = Math.trunc(Number(etapasDias[eid] ?? 0));
      let texto: string;
      if (eid === 'gestao') {
        const semanaInicio = Math.ceil((diaAtual + 1) / 7);
        texto = 'A PARTIR DA ' + semanaOrdinal(semanaInicio) + ' SEMANA';
      } else {
        const diaInicio = diaAtual + 1;
        const diaFim = diaAtual + dias;
        const semanaInicio = Math.max(1, Math.ceil(diaInicio / 7));
        const semanaFim = Math.max(1, Math.ceil(diaFim / 7));
        if (semanaInicio === semanaFim) {
          texto = semanaOrdinal(semanaInicio) + ' SEMANA';
        } else if (semanaFim === semanaInicio + 1) {
          texto = semanaOrdinal(semanaInicio) + ' E ' + semanaOrdinal(semanaFim) + ' SEMANA';
        } else {
          texto = semanaOrdinal(semanaInicio) + ' À ' + semanaOrdinal(semanaFim) + ' SEMANA';
        }
        if (eid === 'imersao') {
          texto += '<br>E PONTUALMENTE DURANTE O PROCESSO';
        }
        diaAtual += dias;
      }
      cronogramaEtapas[eid] = texto;
    }
  }

  const fasesCron = d.fases_cronograma ?? [];
  const totalDias = (fasesCron as any[]).reduce((acc: number, f: any) => acc + Number(f?.dias ?? 0), 0);

  const dataInicioRaw = String(d.data_inicio ?? hojeStr);
  const partesData = dataInicioRaw.split('-');
  const diaIni = partesData[2] ?? '';
  const mesIni = mesesPt[String(Math.trunc(Number(partesData[1] ?? 0)))] ?? 'JUNHO';

  const validadePill = `            <div style="padding: 12px 25px; border-radius: 3.125rem; border: 1px solid ${vencida ? '#ff4d4d' : 'rgba(0,0,0,0.3)'}; font-size: 10px; font-weight: 700; text-transform: uppercase; text-align: center; color: ${vencida ? '#ff4d4d' : '#000'}; letter-spacing: 1px; width: fit-content; display: flex; align-items: center; gap: 8px;">
                ${vencida ? `\n                    <i data-lucide="alert-circle" style="width: 0.8em; height: 0.8em;"></i>\n                    PROPOSTA VENCIDA EM ${validadeFormatada}\n                ` : `\n                    ESTA PROPOSTA É VÁLIDA ATÉ ${validadeFormatada}\n                `}
            </div>`;

  const mesesContrato = Math.max(1, Math.trunc(Number(d.meses_contrato ?? 12)));
  const valorMensalFinal = Math.round((Number(proposta.valor_total) / mesesContrato) * 100) / 100;

  const catalogoPrecos: Record<string, any> = {};
  for (const sv of d.servicos ?? []) {
    if (sv && sv.id && sv.preco_venda !== undefined) {
      catalogoPrecos[String(sv.id)] = sv;
    }
  }

  const servicosComCalculo: any[] = [];
  let subtotalMensal = 0;
  for (const sv of d.servicos ?? []) {
    const tipo = sv.tipo_cobranca ?? 'recorrente';
    const freq = Math.max(1, Math.trunc(Number(sv.frequencia ?? 1)));
    const valIndividual = Number(sv.valor_individual ?? 0);
    const cat = catalogoPrecos[sv.id ?? ''] ?? null;

    let vmCalculado: number;
    if (sv.valor_mensal !== undefined && Number(sv.valor_mensal) > 0) {
      vmCalculado = Number(sv.valor_mensal);
    } else if (tipo === 'pontual') {
      if (freq > 1) {
        const precoRecorrente = cat ? Number(cat.preco_venda) : valIndividual;
        vmCalculado = Math.round(precoRecorrente * freq * 100) / 100;
      } else {
        vmCalculado = Math.round((valIndividual / mesesContrato) * 100) / 100;
      }
    } else {
      vmCalculado = valIndividual;
    }

    subtotalMensal += vmCalculado;
    servicosComCalculo.push({ ...sv, _vm: vmCalculado, _tipo: tipo, _freq: freq, _val_unico: valIndividual });
  }
  subtotalMensal = Math.round(subtotalMensal * 100) / 100;

  const percentDesconto =
    subtotalMensal > 0.01 && valorMensalFinal < subtotalMensal - 0.01
      ? Math.round((1 - valorMensalFinal / subtotalMensal) * 100 * 10) / 10
      : 0;

  const isCartao = (d.forma_pagamento ?? 'boleto_pix') === 'cartao';
  const valorMensalExibido = isCartao ? Math.round(valorMensalFinal * 1.0213 * 100) / 100 : valorMensalFinal;

  const fasesHtml = (fasesCron as any[]).map((fase: any, i: number) => {
    const diasFase = Math.trunc(Number(fase?.dias ?? 0));
    const isUltima = i === (fasesCron as any[]).length - 1;
    const isSim = diasFase === 0;
    return `            <div style="display: flex; align-items: stretch; gap: 0;">
                <!-- Linha vertical + círculo -->
                <div style="display: flex; flex-direction: column; align-items: center; width: 32px; flex-shrink: 0;">
                    <div style="width: 28px; height: 28px; border-radius: 50%; background: ${isSim ? '#e5e7eb' : '#000'}; border: 2px solid #000; display: flex; align-items: center; justify-content: center; flex-shrink: 0; z-index: 1;">
                        <span style="color: ${isSim ? '#000' : '#fff'}; font-size: 10px; font-weight: 800;">${i + 1}</span>
                    </div>
                    ${!isUltima ? `\n                    <div style="width: 2px; flex: 1; background: rgba(0,0,0,0.15); margin: 4px 0; min-height: 40px;"></div>\n                    ` : ''}
                </div>

                <!-- Conteúdo da fase -->
                <div style="padding: 0 0 ${isUltima ? '0' : '28px'} 16px; flex: 1;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px; flex-wrap: wrap;">
                        <span style="font-family: var(--font-heading); font-size: 0.85rem; font-weight: 800; color: #000; text-transform: uppercase; letter-spacing: 0.5px;">
                            ${sanitizar(fase?.nome)}
                        </span>
                        ${isSim
                          ? `\n                            <span style="font-size: 9px; font-weight: 700; color: #6b7280; background: #f3f4f6; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">simultâneo</span>\n                            `
                          : `\n                            <span style="font-size: 9px; font-weight: 700; color: #fff; background: #000; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">+${diasFase} dias</span>\n                            `}
                    </div>
                    ${!vazio(fase?.descricao)
                      ? `\n                    <p style="font-size: 0.78rem; color: #6b7280; line-height: 1.5; margin: 0;">
                        ${sanitizar(fase?.descricao)}
                    </p>\n                    `
                      : ''}
                </div>
            </div>
`;
  }).join('\n');

  const servicosHtml = servicosComCalculo.map((sv: any) => {
    const pontos = String(sv.descricao ?? '').split(/[.;\n]+/);
    const itensLi = pontos
      .map((ponto: string) => {
        const p = ponto.trim();
        if (!p) return '';
        return `\n                                <li style="font-size: 12px; color: #444; margin-bottom: 8px; position: relative;">
                                    <span style="position: absolute; left: -15px; color: #000;">•</span>
                                    ${p}
                                </li>`;
      })
      .join('');
    return `                    <div style="width: 100%;">
                        <div style="padding: 8px 1.25rem; border-radius: 3.125rem; background: #333; color: #fff; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; width: fit-content; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 8px;">
                            <span>${sanitizar(sv.nome)}</span>
                            ${sv._tipo === 'pontual'
                              ? `\n                                <span style="background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 4px; font-size: 8px;">
                                    ${sv._freq > 1 ? sv._freq + 'X/MÊS' : 'PONTUAL'}
                                </span>\n                            `
                              : ''}
                        </div>
                        <div style="font-size: 13px; font-weight: 700; color: #000; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            ${formatarMoeda(sv._vm)}/mês
                            ${percentDesconto > 0
                              ? `\n                                <span style="font-size: 10px; font-weight: 700; color: #fff; background: #222; padding: 2px 8px; border-radius: 20px; letter-spacing: 0.5px;">
                                    desconto de ${number_format(percentDesconto, 0)}%
                                </span>\n                            `
                              : ''}
                        </div>
                        ${sv._tipo === 'pontual' && sv._freq <= 1
                          ? `\n                        <div style="font-size: 11px; color: #888; margin-bottom: 0.9375rem; font-style: italic;">
                            (valor único ${formatarMoeda(sv._val_unico)} ÷ ${mesesContrato} meses)
                        </div>\n                        `
                          : `\n                        <div style="margin-bottom: 0.6rem;"></div>\n                        `}
                        <div style="font-size: 12px; color: #555; margin-bottom: 0.5rem; font-weight: 600;">Inclui:</div>
                        <ul style="margin: 0; padding-left: 15px; list-style-type: none;">${itensLi}
                        </ul>
                    </div>`;
  }).join('\n');

  const adicionalHtml = !vazio(d.adicional?.titulo)
    ? `\n                <!-- Opção Adicional -->
                <div style="width: 100%; padding: 1.875rem; background: rgba(0,0,0,0.05); border-radius: 20px; border: 1px solid rgba(0,0,0,0.1);">
                    <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: #000; margin-bottom: 10px;">
                        OPÇÃO ADICIONAL MENSAL – ${d.adicional?.titulo} + ${formatarMoeda(d.adicional?.valor ?? 0)}/mês
                    </div>
                    <p style="font-size: 12px; color: #444; line-height: 1.5; margin: 0;">
                        ${nl2br(d.adicional?.descricao ?? '')}
                    </p>
                </div>\n`
    : '';

  const etapasSlidesHtml = (() => {
    const defs: Array<{ id: string; num: number; rotulo: string; texto: string; capsula: string; capsulaFallback: string }> = [
      {
        id: 'imersao',
        num: 4,
        rotulo: '',
        texto: `<p style="font-size: 1rem; line-height: 1.5; color: #333; margin-bottom: 1.5625rem;">
                    A primeira etapa do projeto é uma imersão sobre o seu negócio. Serão dois momentos - presenciais ou online - que aplicamos juntos a nossa metodologia, para definir pontos importantes sobre seu negócio.
                </p>
                <p style="font-size: 1rem; line-height: 1.5; color: #333; margin-bottom: 35px;">
                    Esses momentos serão importantes para reunir informações necessárias para este projeto, para servir como um guia de como expressar a marca na criação da autoridade no mercado off-line e on-line.
                </p>
                `,
        capsula: `                <!-- Cápsula de Tempo -->
                <div style="padding: 15px 25px; border-radius: 3.125rem; border: 1px solid rgba(0,0,0,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: center; line-height: 1.3; color: #000; letter-spacing: 0.5px; width: fit-content; margin-top: 1.5rem;">
                    ${cronogramaEtapas['imersao'] ?? 'PRIMEIRA SEMANA'}
                </div>`,
        capsulaFallback: '',
      },
      {
        id: 'diagnostico',
        num: 5,
        rotulo: ' (DIAGNÓSTICO)',
        texto: `<p style="font-size: 1rem; line-height: 1.5; color: #333; margin-bottom: 1.25rem;">
                    Depois da imersão concluída, é o momento de desenvolver o diagnóstico do negócio. Através desses resultados, teremos a definição da plataforma da marca com as seguintes entregas:
                </p>
                <ul style="list-style: none; padding: 0; margin: 0 0 35px 0; font-size: 1rem; color: #000; line-height: 1.8; font-weight: 500;">
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> Proposta de Marca</li>
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> Diferenciais estratégicos</li>
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> Entregas funcionais e emocionais</li>
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> Personalidade de marca</li>
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> Tom de voz</li>
                    <li style="display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 4px; background: #000; border-radius: 50%;"></span> *Arquétipo</li>
                </ul>
                `,
        capsula: `                <!-- Cápsula de Tempo -->
                <div style="padding: 15px 25px; border-radius: 3.125rem; border: 1px solid rgba(0,0,0,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: center; line-height: 1.3; color: #000; letter-spacing: 0.5px; width: fit-content; margin: 0 auto;">
                    ${cronogramaEtapas['diagnostico'] ?? 'TERCEIRA SEMANA'}
                </div>`,
        capsulaFallback: '',
      },
      {
        id: 'planejamento',
        num: 6,
        rotulo: ' (PLANEJAMENTO)',
        texto: `<p style="font-size: 1rem; line-height: 1.4; color: #333; margin-bottom: 0.9375rem;">
                    A fase de planejamento é o núcleo estratégico do projeto, onde estruturamos o "como" e o "onde" para garantir que cada ação tenha um propósito claro e mensurável. Nosso planejamento 360º abrange:
                </p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px;">
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.8rem; color: #000; line-height: 1.5; font-weight: 500;">
                        <li style="margin-bottom: 4px;">• DNA do Conteúdo da Marca</li>
                        <li style="margin-bottom: 4px;">• Definição de Personalidade e Voz</li>
                        <li style="margin-bottom: 4px;">• Canais de Atuação Estratégica</li>
                        <li style="margin-bottom: 4px;">• Análise de Concorrência</li>
                        <li style="margin-bottom: 4px;">• Criação de Personas</li>
                        <li style="margin-bottom: 4px;">• Pesquisa de Palavras-chave</li>
                        <li style="margin-bottom: 4px;">• Jornada de Compra</li>
                    </ul>
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.8rem; color: #000; line-height: 1.5; font-weight: 500;">
                        <li style="margin-bottom: 4px;">• Definição de Linguagem Visual</li>
                        <li style="margin-bottom: 4px;">• Projeto Estrutural do Site</li>
                        <li style="margin-bottom: 4px;">• Linhas Editoriais</li>
                        <li style="margin-bottom: 4px;">• Calendário Trimestral</li>
                        <li style="margin-bottom: 4px;">• Estratégias por Canal</li>
                        <li style="margin-bottom: 4px;">• Fluxos de Automação</li>
                        <li style="margin-bottom: 4px;">• Planejamento de Tráfego</li>
                    </ul>
                </div>
                `,
        capsula: `                <!-- Cápsula de Tempo -->
                <div style="margin-top: 25px; padding: 12px 20px; border-radius: 3.125rem; border: 1px solid rgba(0,0,0,0.3); font-size: 10px; font-weight: 700; text-transform: uppercase; text-align: center; color: #000; letter-spacing: 0.5px; width: fit-content;">
                    ${cronogramaEtapas['planejamento'] ?? 'QUARTA À SÉTIMA SEMANA'}
                </div>`,
        capsulaFallback: '',
      },
      {
        id: 'linguagem_visual',
        num: 7,
        rotulo: ' (LINGUAGEM VISUAL)',
        texto: `<p style="font-size: 1rem; line-height: 1.5; color: #333; margin-bottom: 1.25rem;">
                    A materialização da estratégia ocorre através da <strong>Linguagem Visual</strong>. Definimos um padrão estético de alta autoridade que reflete o posicionamento do seu negócio em cada ponto de contato.
                </p>
                <p style="font-size: 0.8em; color: #000; margin-bottom: 10px; font-weight: 600;">O resultado é consolidado no Manual de Identidade Visual, contemplando:</p>
                <ul style="list-style: none; padding: 0; margin: 0 0 25px 0; font-size: 0.8em; color: #444; line-height: 1.8;">
                    <li>• Tipografia Estratégica</li>
                    <li>• Paleta de Cores com Psicologia Aplicada</li>
                    <li>• Estilo de Elementos Gráficos</li>
                    <li>• Referências de Aplicação</li>
                    <li>• Modelos de Posts e Guidelines para Redes Sociais</li>
                </ul>
                <p style="font-size: 0.8em; line-height: 1.5; color: #666; margin-bottom: 1.875rem;">
                    Garantimos uma presença digital forte, coerente e pronta para escalar sua comunicação com profissionalismo.
                </p>
                `,
        capsula: `                <!-- Cápsula de Tempo -->
                <div style="padding: 15px 25px; border-radius: 3.125rem; border: 1px solid rgba(0,0,0,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: center; line-height: 1.3; color: #000; letter-spacing: 0.5px; width: fit-content; margin-top: 1.5rem;">
                    ${cronogramaEtapas['linguagem_visual'] ?? 'QUARTA À QUINTA SEMANA'}
                </div>`,
        capsulaFallback: '',
      },
      {
        id: 'entrega',
        num: 8,
        rotulo: ' (ENTREGA)',
        texto: `<p style="font-size: 1rem; line-height: 1.5; color: #333; margin-bottom: 1.5625rem;">
                    A culminância do nosso trabalho estratégico. O <strong>Planejamento</strong> e a <strong>Identidade Visual</strong> são apresentados em uma reunião executiva, garantindo o alinhamento total de cada decisão tomada.
                </p>
                <p style="font-size: 1rem; line-height: 1.6; color: #333; margin-bottom: 35px;">
                    Após a validação, todo o ecossistema do projeto é disponibilizado em uma <strong>plataforma web exclusiva</strong>. Este hub serve como guia central para sua equipe e parceiros, garantindo a integridade da marca em qualquer futura expansão.
                </p>
                `,
        capsula: `                <!-- Cápsula de Tempo -->
                <div style="padding: 15px 25px; border-radius: 3.125rem; border: 1px solid rgba(0,0,0,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: center; line-height: 1.3; color: #000; letter-spacing: 0.5px; width: fit-content; margin-top: 1.5rem;">
                    ${cronogramaEtapas['entrega'] ?? 'DÉCIMA PRIMEIRA SEMANA'}
                </div>`,
        capsulaFallback: '',
      },
      {
        id: 'gestao',
        num: 9,
        rotulo: ' (GESTÃO)',
        texto: `<p style="font-size: 1rem; line-height: 1.5; color: #333; margin-bottom: 1.25rem;">
                    A transição da estratégia para a alta performance. Após a entrega das diretrizes, iniciamos o processo de <strong>gestão contínua</strong>, onde a teoria se torna execução prática e resultados reais.
                </p>
                <p style="font-size: 0.8em; line-height: 1.6; color: #333; margin-bottom: 1.25rem;">
                    Focamos na ativação dos canais, distribuição de conteúdo e <strong>gestão de tráfego pago</strong>. Nosso objetivo é claro: atrair leads qualificados e converter a autoridade construída em oportunidades de negócio.
                </p>
                <p style="font-size: 0.8em; line-height: 1.6; color: #333; margin-bottom: 1.875rem;">
                    Você receberá <strong>relatórios estratégicos mensais</strong> com análise de impacto, desempenho de campanhas e propostas de otimização constantes, garantindo que sua marca nunca estagne.
                </p>
                `,
        capsula: `                <!-- Cápsula de Tempo -->
                <div style="padding: 15px 25px; border-radius: 3.125rem; border: 1px solid rgba(0,0,0,0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: center; line-height: 1.3; color: #000; letter-spacing: 0.5px; width: fit-content; margin-top: 1.5rem;">
                    ${cronogramaEtapas['gestao'] ?? 'A PARTIR DA DÉCIMA SEGUNDA SEMANA'}
                </div>`,
        capsulaFallback: '',
      },
    ];

    const ordem = ['imersao', 'diagnostico', 'planejamento', 'linguagem_visual', 'entrega', 'gestao'];
    const rotulos: Record<string, string> = {
      imersao: 'IMERSÃO',
      diagnostico: 'DIAGNÓSTICO',
      planejamento: 'PLANEJAMENTO',
      linguagem_visual: 'LINGUAGEM VISUAL',
      entrega: 'ENTREGA',
      gestao: 'GESTÃO',
    };

    let out = '';
    for (const def of defs) {
      if (!etapasAtivas.includes(def.id)) continue;
      const pill = (id: string) => {
        if (id === def.id) {
          return `<div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #000; color: #fff; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px; position: relative;">
                    ${rotulos[id]}
                    <div style="position: absolute; right: -29px; top: 50%; width: 29px; height: 1px; background: rgba(0,0,0,0.2);"></div>
                </div>`;
        }
        return etapasAtivas.includes(id)
          ? `<div style="padding: 12px 1.875rem; border-radius: 3.125rem; border: 1.5px solid #000; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">${rotulos[id]}</div>`
          : '';
      };
      out += `    <!-- Slide ${def.num}: Etapas do Projeto${def.rotulo} -->
    <section class="proposal-page is-etapas">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3.25rem; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 80%; visibility: hidden;">
                ETAPAS DO<br>PROJETO
            </h2>
        </div>

        <!-- Coluna 2 e 3: Conteúdo das Etapas -->
        <div class="page-content" style="grid-column: 2 / span 2; flex-direction: row; align-items: center; padding-left: 0; margin-left: -7rem;">
            <!-- Lista de Pílulas -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem; width: 14rem; flex-shrink: 0; position: relative; z-index: 9999;">
                ${pill('imersao')}
                ${pill('diagnostico')}
                ${pill('planejamento')}
                ${pill('linguagem_visual')}
                ${pill('entrega')}
                ${pill('gestao')}
            </div>

            <!-- Texto Explicativo -->
            <div style="margin-left: 2rem; max-width: 27rem;">
                ${def.texto}
                ${def.capsula}
            </div>
        </div>
    </section>

`;
    }
    return out;
  })();

  const fasesCronogramaHtml = !vazio(fasesCron)
    ? `    <!-- Slide Cronograma de Entrega (dinâmico) -->
    <section class="proposal-page">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center; padding-right: 2rem;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3rem; line-height: 1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000;">
                CRONOGRAMA<br>DE<br>ENTREGA
            </h2>
            ${totalDias > 0
              ? `\n            <div style="margin-top: 1.5rem; padding: 8px 18px; border-radius: 30px; background: #000; color: #fff; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; width: fit-content;">
                ${totalDias} dias até o início
            </div>\n            `
              : ''}
        </div>

        <!-- Coluna 2-3: Timeline das fases -->
        <div class="page-content" style="grid-column: 2 / span 2; flex-direction: column; justify-content: center; padding: 4vh 4vw 4vh 2vw; gap: 0;">
${fasesHtml}
            <p style="font-size: 10px; color: #9ca3af; margin-top: 20px; padding-left: 48px; font-style: italic;">
                * Prazos estimados. Sujeitos a ajustes conforme aprovações e disponibilidade.
            </p>
        </div>
    </section>
`
    : '';

  return `<div class="theme-marketing">
    <!-- Slide 1: Hero (Capa seguindo o modelo exato) -->
    <section class="proposal-page">
        <div class="page-content" style="grid-column: 1; justify-content: center; padding: 0;">
            <h1 style="font-family: var(--font-heading);font-weight: 800;font-size: 2rem;line-height: 1;margin: 0;text-transform: uppercase;letter-spacing: -2px;color: #000; width: 80%;">
                ${!vazio(proposta.titulo_refinado) ? proposta.titulo_refinado : (!vazio(proposta.titulo) ? proposta.titulo : 'PROPOSTA ESTRATÉGICA')}
            </h1>
            ${!vazio(proposta.subtitulo)
              ? `\n            <p style="font-size: 0.8em; text-transform: uppercase; letter-spacing: 3px; color: rgba(0,0,0,0.4); font-weight: 700; margin-top: 2.5rem; line-height: 1.4;">
                ${proposta.subtitulo}
            </p>\n            `
              : ''}
        </div>
    </section>

    <!-- Slide 2: Introdução / Missão -->
    <section class="proposal-page dark-page">
        <!-- Coluna 1: Título de Impacto -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3.25rem; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #fff;width: 60%;">
                ${tituloImpacto}
            </h2>
        </div>

        <!-- Coluna 2: Texto de Boas-vindas -->
        <div class="page-content" style="grid-column: 2; justify-content: center; padding-left: 2.5rem; height: 100vh; padding-top: 0; padding-bottom: 0;">
            <div class="mission-text" style="color: #fff; font-size: clamp(14px, 0.8rem, 28px); line-height: 1.5; opacity: 0.9;">
                <h3 style="font-family: var(--font-heading); font-size: clamp(24px, 1.75rem, 56px); font-weight: 800; margin-bottom: 1rem; text-transform: uppercase; color: #fff;">
                    OLÁ ${mbUpper(saudacao)}!
                </h3>
                <p style="font-weight: 700; margin-bottom: 1.25rem;">Seja bem-vindo à Poncem Studio | Distinto.</p>
                <p style="margin-bottom: 0.9375rem;">Mais do que uma agência, somos uma empresa de posicionamento, estratégia e direção criativa para marcas que desejam crescer com clareza, autoridade e percepção de valor.<br><br>
                    Acreditamos que negócios fortes não se constroem apenas com presença digital.<break><br>
                    Eles se constroem com narrativa, identidade, direção e execução inteligente.<br><br>
                    Por isso, nosso trabalho vai além de produzir conteúdo ou gerenciar redes sociais.<br><br>
                    Desenvolvemos marcas que comunicam com intenção, geram conexão e ocupam um espaço relevante no mercado.<br><br>
                    Unimos estratégia, comunicação e audiovisual para transformar empresas em marcas percebidas, desejadas e lembradas.<br><br>
                    Cada projeto que passa pela Distinto é pensado para transmitir valor de forma autêntica desde o posicionamento até a forma como a marca é vista, sentida e reconhecida pelas pessoas.<br><br>
                    Atuamos com empresas que entenderam que imagem sem estratégia gera apenas movimento. Mas estratégia alinhada à comunicação certa gera autoridade, crescimento e diferenciação.<br><br>
                    Se você chegou até aqui, provavelmente entende que sua marca possui algo valioso demais para parecer comum.</p>
                <p style="font-weight: 700; margin-top: 1.5rem;">Vamos juntos?</p>
            </div>
        </div>

        <!-- Coluna 3: Gradiente Abstrato -->
        <div class="side-gradient-container" style="grid-column: 3; position: relative; height: 100%; overflow: hidden;">
            <div class="abstract-gradient"></div>
        </div>
    </section>

    <!-- Slide 3: Objetivo do Projeto -->
    <section class="proposal-page">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3.25rem; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 90%;">
                PARA ESTE PROJETO, QUAL SERÁ O NOSSO OBJETIVO?
            </h2>
        </div>

        <!-- Coluna 2: Texto Estratégico (IA) -->
        <div class="page-content" style="grid-column: 2; justify-content: center; padding-left: 2.5rem;">
            <div class="objective-text" style="color: #333; font-size: 0.8em; line-height: 1.6; opacity: 0.9;">
                ${!vazio(d.secoes?.objetivo)
                  ? `\n                    ${nl2br(d.secoes?.objetivo)}\n                `
                  : `\n                    Após uma análise do posicionamento estratégico da marca, identificamos uma oportunidade de fortalecer sua percepção de valor e autoridade. Nosso foco é claro: gerar resultados reais e posicionar seu negócio como referência no mercado.\n                `}
            </div>
        </div>
    </section>
    
${etapasSlidesHtml}    <!-- Slide 10: Resumo do Cronograma -->
    <section class="proposal-page dark-page is-etapas">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3.25rem; line-height: 1.1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #fff; width: 80%; visibility: hidden;">
                ETAPAS DO<br>PROJETO
            </h2>
        </div>

        <!-- Coluna 2 e 3: Conteúdo das Etapas -->
        <div class="page-content" style="grid-column: 2 / span 2; flex-direction: row; align-items: center; padding-left: 0; margin-left: -7rem;">
            <!-- Lista de Pílulas Ativas -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem; width: 14rem; flex-shrink: 0; position: relative; z-index: 9999;">
                ${etapasAtivas.includes('imersao') ? '<div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">IMERSÃO</div>' : ''}
                ${etapasAtivas.includes('diagnostico') ? '<div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">DIAGNÓSTICO</div>' : ''}
                ${etapasAtivas.includes('planejamento') ? '<div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">PLANEJAMENTO</div>' : ''}
                ${etapasAtivas.includes('linguagem_visual') ? '<div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">LINGUAGEM VISUAL</div>' : ''}
                ${etapasAtivas.includes('entrega') ? '<div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">ENTREGA</div>' : ''}
                ${etapasAtivas.includes('gestao') ? '<div style="padding: 12px 1.875rem; border-radius: 3.125rem; background: #fff; color: #000; text-align: center; font-weight: 700; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">GESTÃO</div>' : ''}

                <!-- Chave de Conexão (Bracket) -->
                <div style="position: absolute; right: -20px; top: 20px; bottom: 20px; width: 20px; border: 1.5px solid rgba(255,255,255,0.3); border-left: 0;"></div>
            </div>

            <!-- Texto de Cronograma (legado - mantido como resumo) -->
            <div style="margin-left: 2rem; max-width: 27rem;">
                <p style="font-size: 1rem; line-height: 1.5; color: #fff; margin-bottom: 1.5625rem;">
                    O projeto poderá ser iniciado a partir do dia <strong>${diaIni} DE ${mesIni}</strong>${totalDias > 0 ? ', com previsão de <strong>' + totalDias + ' DIAS</strong> até o início das publicações' : ''}.
                </p>
                <p style="font-size: 0.85rem; line-height: 1.5; color: rgba(255,255,255,0.7);">
                    As datas são uma previsão e podem ser ajustadas conforme disponibilidade de agenda, aprovações e alterações de escopo.
                </p>
            </div>
        </div>
    </section>

${fasesCronogramaHtml}
    <!-- Slide 11: Investimento Detalhado -->
    <section class="proposal-page is-investimento">
        <!-- Coluna 1: Título e Validade -->
        <div class="page-content" style="grid-column: 1; flex-direction: column; justify-content: center; gap: 2.5rem; height: 100vh; padding: 0;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3.25rem; line-height: 1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 90%;">
                QUAL SERÁ O INVESTIMENTO PARA ESTE PROJETO
            </h2>

            <!-- Validade -->
            ${validadePill}
        </div>

        <!-- Coluna 2: Detalhamento -->
        <div class="page-content" style="grid-column: 2; flex-direction: column; align-items: center; justify-content: center; height: 100vh; padding: 0;">
            <div style="width: 100%; max-height: 85vh; overflow-y: auto; scrollbar-width: none; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2.5rem 0;">
            <!-- Valor Mensal -->
            <div style="padding: 8px 40px; border-radius: 3.125rem; background: #333; color: #fff; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.75rem;">
                VALOR INVESTIDO NO PROJETO /MÊS
            </div>

            ${percentDesconto > 0
              ? `\n            <div style="font-family: var(--font-heading); font-size: 1.6rem; font-weight: 700; color: #000; opacity: 0.2; text-decoration: line-through; margin-bottom: 4px; letter-spacing: -1px;">
                ${formatarMoeda(subtotalMensal)}
            </div>\n            `
              : ''}

            <div style="font-family: var(--font-heading); font-size: 4rem; font-weight: 800; color: #000; margin-bottom: ${isCartao ? '5px' : '50px'};">
                ${formatarMoeda(valorMensalExibido)}
            </div>
            ${isCartao
              ? `\n                <div style="font-size: 10px; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 3.125rem;">
                    * VALOR COM ACRÉSCIMO DE 2,13% PARA CARTÃO
                </div>\n            `
              : ''}

            <!-- Serviços Inclusos -->
            <div style="width: 100%; display: flex; flex-direction: column; gap: 2.5rem;">
                ${servicosHtml}
                ${adicionalHtml}
            </div>
        </div>
    </section>

    <!-- Slide 12: Condições de Pagamento -->
    <section class="proposal-page">
        <!-- Coluna 1: Título e Validade -->
        <div class="page-content" style="grid-column: 1; flex-direction: column; justify-content: center; gap: 2.5rem; height: 100vh; padding: 0;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 3.25rem; line-height: 1; margin: 0; text-transform: uppercase; letter-spacing: -1px; color: #000; width: 90%;">
                CONDIÇÕES DE<br>PAGAMENTO
            </h2>

            <!-- Validade -->
            ${validadePill}
        </div>

        <!-- Coluna 2: Detalhes do Pagamento -->
        <div class="page-content" style="grid-column: 2; flex-direction: column; align-items: flex-start; justify-content: center; height: 100vh; padding: 0;">
            <div style="width: 100%; padding: 2.5rem 0;">
            <div style="padding: 8px 40px; border-radius: 3.125rem; background: #333; color: #fff; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2.5rem;">
                FORMA DE PAGAMENTO
            </div>
            
            <p style="font-size: 0.8em; line-height: 1.6; color: #333; margin-bottom: 1.875rem;">
                O pagamento referente ao valor mensal do projeto deverá ser realizado no momento da assinatura do contrato, que será enviado para assinatura digital via e-mail.
            </p>
            <p style="font-size: 0.8em; line-height: 1.6; color: #333; margin-bottom: 1.875rem;">
                A partir da confirmação do pagamento, iniciaremos o processo de estruturação e execução do plano mensal, incluindo gestão de redes sociais, tráfego pago e demais serviços contratados.
            </p>

            ${isCartao
              ? `\n                <div style="padding: 20px; background: rgba(0,0,0,0.05); border-radius: 15px; border-left: 4px solid #000; margin-bottom: 1.875rem; width: 100%;">
                    <p style="font-size: 13px; font-weight: 700; color: #000; margin-bottom: 5px;">PAGAMENTO VIA CARTÃO DE CRÉDITO</p>
                    <p style="font-size: 12px; color: #444; margin: 0;">Para esta modalidade, há um acréscimo de <strong>2,13%</strong> referente às taxas operacionais da plataforma de pagamento.</p>
                </div>\n            `
              : ''}

            <div style="margin-top: 20px;">
                <p style="font-size: 12px; font-weight: 700; color: #000; margin-bottom: 10px;">Observação:</p>
                <p style="font-size: 12px; color: #666; line-height: 1.6;">
                    O valor referente ao investimento em mídia (anúncios) é de responsabilidade do cliente, sendo pago diretamente à plataforma de anúncios (Meta/Facebook Ads), via boleto bancário ou cartão de crédito cadastrado na conta de anúncios.
                </p>
            </div>
            </div>
        </div>
    </section>
    <!-- Slide 13: Finalização -->
    <section class="proposal-page dark-page">
        <!-- Coluna 1: Título -->
        <div class="page-content" style="grid-column: 1; flex-direction: column; justify-content: center;">
            <h2 style="font-family: var(--font-heading); font-weight: 800; font-size: 4rem; line-height: 0.9; margin: 0; text-transform: uppercase; letter-spacing: -2px; color: #fff;">
                VAMOS JUNTOS CONSTRUIR<br>ESTE PROJETO?
            </h2>
        </div>

        <!-- Coluna 2: Mensagem e Contato -->
        <div class="page-content" style="grid-column: 2; flex-direction: column; align-items: flex-start; justify-content: center; padding-left: 2.5rem;">
            <p style="font-size: 18px; line-height: 1.6; color: #fff; margin-bottom: 2.5rem; font-weight: 300;">
                Será um imenso prazer entrar com você nesta jornada e desenvolver um projeto para alavancar o seu negócio.
            </p>
            <p style="font-size: 1rem; line-height: 1.6; color: rgba(255,255,255,0.7); margin-bottom: 60px;">
                Qualquer dúvida sobre esta proposta ou o meu trabalho, entre em contato.
            </p>

            <div style="display: flex; flex-direction: column; gap: 15px;">
                <a href="mailto:contato@wedistinto.com" style="color: #fff; text-decoration: none; font-size: 1rem; font-weight: 600;">contato@wedistinto.com</a>
                <a href="https://wa.me/5527988586935" target="_blank" style="color: #fff; text-decoration: none; font-size: 1rem; font-weight: 600;">WhatsApp: (27) 9 8858-6935</a>
            </div>

                Rod. Sol, 2780, SL 1307 - Praia de Itaparica<br>
                Vila Velha - ES
            </div>
        </div>
    </section>
</div>
`;
}