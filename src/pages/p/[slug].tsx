import React, { useEffect, useRef } from 'react';
import Head from 'next/head';
import { GetServerSideProps } from 'next';
import { queryOne, query } from '@/lib/db';
import { raizUrl, esc, number_format, mbUpper, SlideCtx } from '@/lib/propostas/common';
import { buildServicosWedding, buildPlanosWedding, buildCondicoesCasamento } from '@/lib/propostas/planos';
import {
  investimentoScript,
  casamentoClosingScript,
  planModalScript,
  publicInlineScript,
} from '@/lib/propostas/scripts';
import { render as renderCasamento } from '@/lib/propostas/templates/casamento';
import { render as renderMarketing } from '@/lib/propostas/templates/marketing';
import { render as renderFilmmaker } from '@/lib/propostas/templates/filmmaker';
import { render as renderQuinze } from '@/lib/propostas/templates/quinze';
import { LgpdConsent } from '@/components/LgpdConsent';

const MESES_PT: Record<string, string> = {
  '1': 'JANEIRO', '2': 'FEVEREIRO', '3': 'MARÇO', '4': 'ABRIL', '5': 'MAIO', '6': 'JUNHO',
  '7': 'JULHO', '8': 'AGOSTO', '9': 'SETEMBRO', '10': 'OUTUBRO', '11': 'NOVEMBRO', '12': 'DEZEMBRO',
};

function textoResponsavel(dados: any): string {
  if (!dados || !dados.responsavel) return '';
  const partesBrutas = String(dados.responsavel).split(/\s+[eE]\s+|[,;]\s*/);
  const nomesFinais: string[] = [];
  for (const p of partesBrutas) {
    const t = p.trim();
    if (!t) continue;
    nomesFinais.push(t.split(' ')[0]);
  }
  const total = nomesFinais.length;
  if (total === 1) return nomesFinais[0];
  if (total === 2) return nomesFinais[0] + ' e ' + nomesFinais[1];
  if (total > 2) {
    const ultimo = nomesFinais.pop()!;
    return nomesFinais.join(', ') + ' e ' + ultimo;
  }
  return '';
}

function isNumericLike(v: any): boolean {
  if (v === null || v === undefined) return false;
  const s = String(v).trim();
  if (s === '') return false;
  return isFinite(Number(v));
}

/**
 * Port fiel de IAPropostas::gerarMensagemWhatsApp() (includes/ia_propostas.php).
 * Usa o Gemini se a chave estiver configurada; caso contrário cai no fallback.
 */
async function gerarMensagemWhatsApp(
  nomeNoivo: string,
  nomeNoiva: string,
  nomeCasal: string,
  apiKey: string
): Promise<string> {
  const nomeNoivaSimples = (nomeNoiva || '').trim().split(' ')[0] || '';
  const nomeNoivoSimples = (nomeNoivo || '').trim().split(' ')[0] || '';
  const nomes = (nomeNoivaSimples && nomeNoivoSimples)
    ? `${nomeNoivaSimples} e ${nomeNoivoSimples}`
    : nomeCasal;
  const fallback = `Olá Wellington! Ficamos encantados com a proposta do nosso casamento (${nomes}). Gostaríamos de conversar para alinhar os detalhes e dar o próximo passo! ✨`;

  if (!apiKey || apiKey.startsWith('SUA_')) return fallback;

  const prompt = `Você é um assistente simpático e caloroso de um estúdio de fotografia e filmmaking de luxo para casamentos chamado Distinto.\nGere uma mensagem curta, calorosa e engajadora que os noivos (${nomes}) enviariam pelo WhatsApp para o estúdio para demonstrar interesse em fechar a proposta do casamento deles.\nA mensagem deve ser escrita na perspectiva dos noivos enviando para o estúdio.\nExemplo de tom: 'Olá Wellington! Amamos a proposta comercial e a forma como vocês enxergam nosso casamento. Queremos conversar sobre os próximos passos! ✨'\nRetorne APENAS a mensagem direta, sem aspas, sem explicações e sem introduções.`;

  try {
    const res = await fetch(
      `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=${encodeURIComponent(apiKey)}`,
      {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          contents: [{ parts: [{ text: prompt }] }],
          generationConfig: { temperature: 0.3, maxOutputTokens: 8192 },
        }),
        signal: AbortSignal.timeout(90000),
      }
    );
    if (!res.ok) return fallback;
    const data = await res.json();
    const texto = data?.candidates?.[0]?.content?.parts?.[0]?.text;
    const limpo = String(texto || '').replace(/^["']|["']$/g, '').trim();
    return limpo || fallback;
  } catch (e) {
    return fallback;
  }
}

interface CasamentoProps {
  mPHeritage: number;
  mPCinematic: number;
  mPEssencial: number;
  mPBoudoir: number;
  mPPrewedding: number;
  valorBoudoir: number;
  valorPrewedding: number;
  condHC: string;
  condE: string;
  mNomeCasal: string;
  servicosWedding: Record<string, any>;
  planosWedding: any[];
}

interface PageProps {
  slug: string;
  tipo: string;
  titulo: string;
  cliente: string;
  slides: string;
  categoriaProjeto: string;
  mesNome: string;
  ano: string;
  frameCliente: string;
  telefone: string;
  dados: Record<string, any>;
  casamento?: CasamentoProps;
}

/** HTML do modal de escolha de pacote do p.php (linhas 291-372), verbatim. */
function planModalHtml(c: CasamentoProps): string {
  const planos = [
    { key: 'heritage', label: 'Experiência Heritage', valor: c.mPHeritage, cond: c.condHC },
    { key: 'cinematic', label: 'Experiência Cinematic', valor: c.mPCinematic, cond: c.condHC },
    { key: 'essencial', label: 'Registro Essencial', valor: c.mPEssencial, cond: c.condE },
  ];
  const upgrades = [
    { key: 'boudoir', label: 'Boudoir da Noiva', valor: c.mPBoudoir },
    { key: 'prewedding', label: 'Ensaio Pré-Wedding', valor: c.mPPrewedding },
  ];

  const cardsHtml = planos
    .map(
      (p) => `
                    <div class="m-plan-card" data-plan="${p.key}" data-value="${p.valor}" data-cond="${esc(p.cond)}"
                        onclick="window.mSelectPlan('${p.key}')"
                        style="padding:14px 18px; border-radius:8px; border:1.5px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.03); cursor:pointer; display:flex; align-items:center; gap:14px; transition:border-color 0.2s, background 0.2s;">
                        <div class="m-radio" style="width:17px; height:17px; border-radius:50%; border:2px solid rgba(255,255,255,0.25); flex-shrink:0; display:flex; align-items:center; justify-content:center; transition:border-color 0.2s;">
                            <div class="m-radio-dot" style="width:7px; height:7px; border-radius:50%; background:#c5a880; opacity:0; transition:opacity 0.2s;"></div>
                        </div>
                        <p style="font-family:'Montserrat',sans-serif; font-size:0.82rem; font-weight:600; color:rgba(255,255,255,0.85); margin:0; flex:1; letter-spacing:0.02em;">${esc(p.label)}</p>
                        <p style="font-family:'Montserrat',sans-serif; font-size:0.9rem; font-weight:300; color:rgba(255,255,255,0.85); margin:0; flex-shrink:0; white-space:nowrap;">
                            R$ ${number_format(p.valor, 0)}
                        </p>
                    </div>`
    )
    .join('');

  const upgradesHtml = upgrades
    .map(
      (u) => `
                    <div class="m-up-card" data-up="${u.key}" data-value="${u.valor}"
                        onclick="window.mToggleUp('${u.key}')"
                        style="padding:12px 18px; border-radius:8px; border:1.5px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.02); cursor:pointer; display:flex; align-items:center; gap:14px; transition:border-color 0.2s, background 0.2s;">
                        <div class="m-check" style="width:17px; height:17px; border-radius:4px; border:2px solid rgba(255,255,255,0.25); flex-shrink:0; display:flex; align-items:center; justify-content:center; transition:border-color 0.2s;">
                            <svg class="m-check-icon" width="10" height="8" viewBox="0 0 10 8" fill="none" style="opacity:0; transition:opacity 0.2s;">
                                <path d="M1 4L3.5 6.5L9 1" stroke="#c5a880" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <p style="font-family:'Montserrat',sans-serif; font-size:0.82rem; font-weight:400; color:rgba(255,255,255,0.75); margin:0; flex:1;">${esc(u.label)}</p>
                        <p style="font-family:'Montserrat',sans-serif; font-size:0.85rem; font-weight:300; color:rgba(255,255,255,0.65); margin:0; flex-shrink:0; white-space:nowrap;">
                            + R$ ${number_format(u.valor, 0)}
                        </p>
                    </div>`
    )
    .join('');

  return `
    <div id="modal-planos" style="position:fixed; inset:0; z-index:999; background:rgba(0,0,0,0.85); backdrop-filter:blur(12px); display:none; align-items:center; justify-content:center; padding:20px;">
        <div style="background:#111; border:1px solid rgba(255,255,255,0.12); border-radius:16px; width:100%; max-width:480px; max-height:90vh; overflow-y:auto; box-shadow:0 32px 64px rgba(0,0,0,0.8);">
            
            <div style="padding:24px 32px 20px; border-bottom:1px solid rgba(255,255,255,0.07); display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <h3 style="font-family:'Playfair Display',serif; font-size:1.15rem; font-weight:400; color:#fff; margin:0 0 4px;">Escolha seu Pacote</h3>
                    <p style="font-family:'Montserrat',sans-serif; font-size:0.75rem; font-weight:300; color:rgba(255,255,255,0.45); margin:0;">${esc(c.mNomeCasal)}</p>
                </div>
                <button onclick="window.mFecharModal()" style="background:none; border:none; color:rgba(255,255,255,0.4); cursor:pointer; padding:4px; font-size:1.2rem; line-height:1; transition:color 0.2s;">✕</button>
            </div>

            <div style="padding:24px 32px;">

                <p style="font-family:'Montserrat',sans-serif; font-size:0.58rem; font-weight:700; letter-spacing:0.25em; text-transform:uppercase; color:rgba(255,255,255,0.35); margin:0 0 12px;">PLANOS</p>
                <div id="m-plan-cards" style="display:flex; flex-direction:column; gap:8px; margin-bottom:24px;">
${cardsHtml}
                </div>

                <p style="font-family:'Montserrat',sans-serif; font-size:0.58rem; font-weight:700; letter-spacing:0.25em; text-transform:uppercase; color:rgba(255,255,255,0.35); margin:0 0 12px;">UPGRADES OPCIONAIS</p>
                <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:28px;">
${upgradesHtml}
                </div>

                <div style="background:rgba(255,255,255,0.04); border-radius:8px; padding:16px 20px; display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                    <span style="font-family:'Montserrat',sans-serif; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.12em; color:rgba(255,255,255,0.45);">Total estimado</span>
                    <span id="m-total" style="font-family:'Montserrat',sans-serif; font-size:1.5rem; font-weight:300; color:#fff;">—</span>
                </div>

                <p id="m-cond" style="font-family:'Montserrat',sans-serif; font-size:0.75rem; font-weight:300; color:rgba(255,255,255,0.38); line-height:1.6; margin:0 0 28px; min-height:1.2em;"></p>

                <button id="m-send-btn" onclick="window.mEnviar()" disabled
                    style="width:100%; padding:16px; border-radius:8px; background:#c5a880; border:none; cursor:not-allowed; font-family:'Montserrat',sans-serif; font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:0.15em; color:#1a1a1a; display:flex; align-items:center; justify-content:center; gap:10px; opacity:0.5; transition:opacity 0.2s;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.999 2C6.477 2 2 6.477 2 12c0 1.89.525 3.656 1.438 5.168L2 22l4.948-1.42A9.96 9.96 0 0 0 12 22c5.523 0 10-4.477 10-10S17.522 2 12 2z"/></svg>
                    Confirmar e enviar no WhatsApp
                </button>
                <p style="font-family:'Montserrat',sans-serif; font-size:0.65rem; color:rgba(255,255,255,0.25); text-align:center; margin:12px 0 0;">Selecione um plano para continuar</p>
            </div>
        </div>
    </div>`;
}

/** Injeta HTML (slides) e re-executa eventuais <script> após o hydration. */
function Injected({ html }: { html: string }) {
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const container = ref.current;
    if (!container) return;
    container.querySelectorAll('script').forEach((old) => {
      if (old.getAttribute('data-executed') === '1') return;
      const s = document.createElement('script');
      for (const attr of Array.from(old.attributes)) {
        s.setAttribute(attr.name, attr.value);
      }
      s.textContent = old.textContent || '';
      s.setAttribute('data-executed', '1');
      old.replaceWith(s);
    });
  }, []);

  return <div ref={ref} suppressHydrationWarning dangerouslySetInnerHTML={{ __html: html }} />;
}

export default function PublicProposalPage(props: PageProps) {
  const { slug, tipo, titulo, cliente, slides, categoriaProjeto, mesNome, ano, frameCliente, telefone, dados, casamento } = props;
  const isCasamento = tipo === 'casamento';

  useEffect(() => {
    document.body.classList.add('type-' + tipo);

    if (isCasamento && casamento) {
      const scriptCode = investimentoScript({
        slug,
        dados: dados || {},
        servicosWedding: casamento.servicosWedding,
        planosWedding: casamento.planosWedding,
        condHC: casamento.condHC,
        condE: casamento.condE,
      });

      const planParams = {
        slug,
        nomeCasal: casamento.mNomeCasal,
        pHeritage: casamento.mPHeritage,
        pCinematic: casamento.mPCinematic,
        pEssencial: casamento.mPEssencial,
        pBoudoir: casamento.mPBoudoir,
        pPrewedding: casamento.mPPrewedding,
        condHC: casamento.condHC,
        condE: casamento.condE,
      };

      const executeDynamicScript = (code: string) => {
        try {
          const fn = new Function(code);
          fn();
        } catch (e) {}
      };

      executeDynamicScript(scriptCode);
      executeDynamicScript(casamentoClosingScript());
      executeDynamicScript(planModalScript(planParams));

      const timer = setTimeout(() => {
        executeDynamicScript(scriptCode);
        executeDynamicScript(casamentoClosingScript());
        executeDynamicScript(planModalScript(planParams));
      }, 300);

      return () => clearTimeout(timer);
    }
  }, [isCasamento, casamento, tipo, telefone, slug, dados]);

  const htmlFinal = slides + (isCasamento && casamento ? planModalHtml(casamento) : '');

  return (
    <>
      <Head>
        <title>{titulo}</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
        <link
          href="https://fonts.googleapis.com/css2?family=Playfair Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Montserrat:wght@200;300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap"
          rel="stylesheet"
        />
        <script dangerouslySetInnerHTML={{ __html: publicInlineScript(slug, isCasamento) }} />
      </Head>

      <Injected html={htmlFinal} />

      {/* LGPD COOKIE & PRIVACY BANNER */}
      <LgpdConsent showCheckboxField={false} />
    </>
  );
}

export const getServerSideProps: GetServerSideProps = async (context) => {
  const { slug } = context.params || {};
  if (!slug || typeof slug !== 'string') return { notFound: true };

  const proposta = await queryOne('SELECT * FROM propostas WHERE slug = $1 LIMIT 1', [slug]);
  if (!proposta) return { notFound: true };

  let dados: any = {};
  try {
    dados = typeof proposta.dados_json === 'string' ? JSON.parse(proposta.dados_json || '{}') : (proposta.dados_json || {});
  } catch (e) {
    dados = {};
  }

  const cfg = await queryOne('SELECT * FROM configuracao_empresa LIMIT 1');
  const empresa = {
    nome_empresa: cfg?.nome_empresa || 'ERP Distinto',
    telefone: '27999998888',
    gemini_api_key: process.env.GEMINI_API_KEY || '',
  };

  const tipo = proposta.tipo || 'casamento';
  const titulo = proposta.titulo || 'Proposta Comercial';
  const cliente = proposta.cliente_nome || dados.cliente_nome || 'Cliente';

  const criadoEm = proposta.criado_em ? new Date(proposta.criado_em) : new Date();
  const mesNum = String(criadoEm.getMonth() + 1);
  const mesNome = MESES_PT[mesNum] || 'JANEIRO';
  const ano = String(criadoEm.getFullYear());

  const categoriaProjeto = (dados.categoria_projeto || 'Wedding').toUpperCase();
  const frameCliente = mbUpper(cliente);

  let casamento: CasamentoProps | undefined = undefined;

  if (tipo === 'casamento') {
    const nomeNoivo = String(dados.nome_noivo || '');
    const nomeNoiva = String(dados.nome_noiva || '');
    const mNomeCasal = (nomeNoiva && nomeNoivo) ? `${nomeNoiva} & ${nomeNoivo}` : cliente;

    const servicosRows = await query(`SELECT id, nome, preco_venda, tipo FROM servicos WHERE categoria='wedding' AND ativo=1`);
    const servicosWedding = buildServicosWedding(servicosRows);

    const planosRows = await query(`SELECT * FROM servicos WHERE categoria='wedding' AND tipo='plano' AND ativo=1 ORDER BY preco_venda DESC`);
    const planosWedding = buildPlanosWedding(planosRows, dados);
    const cond = buildCondicoesCasamento(dados);

    // Valores padrão dos planos
    const pHeritage = planosWedding.find((p) => p.id === 'heritage')?.preco_venda || 12000;
    const pCinematic = planosWedding.find((p) => p.id === 'cinematic')?.preco_venda || 8500;
    const pEssencial = planosWedding.find((p) => p.id === 'essencial')?.preco_venda || 5500;

    casamento = {
      mPHeritage: pHeritage,
      mPCinematic: pCinematic,
      mPEssencial: pEssencial,
      mPBoudoir: Number(dados.valor_boudoir || 500),
      mPPrewedding: Number(dados.valor_prewedding || 1100),
      valorBoudoir: Number(dados.valor_boudoir || 500),
      valorPrewedding: Number(dados.valor_prewedding || 1100),
      condHC: cond.condHC,
      condE: cond.condE,
      mNomeCasal,
      servicosWedding,
      planosWedding,
    };
  }

  const mensagemWA = await gerarMensagemWhatsApp(
    String(dados.nome_noivo || ''),
    String(dados.nome_noiva || ''),
    cliente,
    empresa.gemini_api_key
  );

  if (dados.servicos && Array.isArray(dados.servicos)) {
    const ids = (dados.servicos || []).map((s: any) => s && s.id).filter(Boolean);
    if (ids.length > 0) {
      const placeholders = ids.map(() => '?').join(',');
      const catRows = await query(`SELECT id, preco_venda, preco_venda_pontual FROM servicos WHERE id IN (${placeholders})`, ids);
      const cat: Record<string, any> = {};
      for (const r of catRows) cat[r.id] = r;
      dados.servicos = (dados.servicos || []).map((sv: any) =>
        cat[sv.id] ? { ...sv, preco_venda: cat[sv.id].preco_venda, preco_venda_pontual: cat[sv.id].preco_venda_pontual } : sv
      );
    }
  }

  const slideCtx: SlideCtx = {
    proposta,
    dados,
    tipo,
    cliente,
    mesNome,
    ano,
    categoriaProjeto,
    empresa,
    slug,
    servicosWedding: casamento?.servicosWedding,
    planosWedding: casamento?.planosWedding,
    condHC: casamento?.condHC,
    condE: casamento?.condE,
    mensagemWA,
  };

  const renderMap: Record<string, (c: SlideCtx) => string> = {
    casamento: renderCasamento,
    marketing: renderMarketing,
    filmmaker: renderFilmmaker,
    '15anos': renderQuinze,
  };
  const renderFn = renderMap[tipo];
  if (!renderFn) return { notFound: true };

  const slides = renderFn(slideCtx);

  return {
    props: {
      slug,
      tipo,
      titulo,
      cliente,
      slides,
      categoriaProjeto,
      mesNome,
      ano,
      frameCliente,
      telefone: String(empresa.telefone || ''),
      dados,
      casamento: casamento || null,
    } as any,
  };
};