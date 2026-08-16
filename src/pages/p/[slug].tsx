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
                    <div style="padding:12px 18px; border-radius:8px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.02); display:flex; align-items:center; gap:12px;">
                        <div style="flex:1;">
                            <p style="font-family:'Montserrat',sans-serif; font-size:0.8rem; font-weight:500; color:rgba(255,255,255,0.8); margin:0 0 2px;">${esc(u.label)}</p>
                            <p style="font-family:'Montserrat',sans-serif; font-size:0.75rem; font-weight:300; color:rgba(255,255,255,0.4); margin:0;">R$ ${number_format(u.valor, 0)}</p>
                        </div>
                        <div id="m-toggle-${u.key}" onclick="window.mToggle('${u.key}', ${u.valor})"
                            style="width:38px; height:22px; border-radius:20px; background:rgba(255,255,255,0.12); cursor:pointer; position:relative; flex-shrink:0; transition:background 0.2s;">
                            <div style="width:16px; height:16px; border-radius:50%; background:#fff; position:absolute; top:3px; left:3px; transition:left 0.2s;" class="m-thumb"></div>
                        </div>
                    </div>`
    )
    .join('');

  return `<div id="plan-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.88); backdrop-filter:blur(6px); overflow-y:auto; padding:40px 20px;" onclick="if(event.target===this)window.closePlanModal()">
        <div style="max-width:680px; margin:0 auto; background:#1a1a1a; border-radius:12px; border:1px solid rgba(255,255,255,0.08); overflow:hidden;">

            <div style="padding:28px 32px 20px; border-bottom:1px solid rgba(255,255,255,0.07); display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <p style="font-family:'Montserrat',sans-serif; font-size:0.6rem; font-weight:700; letter-spacing:0.28em; text-transform:uppercase; color:#c5a880; margin:0 0 4px;">DISTINTO WEDDING</p>
                    <h2 style="font-family:'Montserrat',sans-serif; font-size:1.25rem; font-weight:300; letter-spacing:0.06em; text-transform:uppercase; color:#fff; margin:0;">Escolha seu pacote</h2>
                </div>
                <button onclick="window.closePlanModal()" style="background:none; border:none; cursor:pointer; color:rgba(255,255,255,0.4); padding:4px; line-height:1;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
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
  const { slug, tipo, titulo, cliente, slides, categoriaProjeto, mesNome, ano, frameCliente, telefone, casamento } = props;
  const isCasamento = tipo === 'casamento';

  useEffect(() => {
    document.body.classList.add('type-' + tipo);

    if (isCasamento && casamento) {
      const scriptCode = investimentoScript({
        slug,
        dados: { valor_boudoir: casamento.valorBoudoir, valor_prewedding: casamento.valorPrewedding },
        servicosWedding: casamento.servicosWedding,
        planosWedding: casamento.planosWedding,
        condHC: casamento.condHC,
        condE: casamento.condE,
      });

      const s = document.createElement('script');
      s.textContent = scriptCode;
      document.body.appendChild(s);
    }

    const lucide = (window as any).lucide;
    if (lucide && lucide.createIcons) lucide.createIcons();

    return () => {
      document.body.classList.remove('type-' + tipo);
    };
  }, [tipo, isCasamento, casamento, slug]);

  const waNum = String(telefone || '').replace(/\D/g, '');
  const waText = encodeURIComponent(`Olá! Gostaria de aprovar a proposta: ${titulo} (Ref: ${slug})`);
  const waLink = `https://wa.me/${waNum}?text=${waText}`;

  const casamentoMobileCss = `
    @media (max-width: 768px) {
      #btn-approve {
        display: flex !important;
        position: fixed !important;
        bottom: 30px !important;
        left: 50% !important;
        transform: translateX(-50%) translateY(20px) !important;
        width: auto !important;
        min-width: 250px !important;
        justify-content: center !important;
        z-index: 10001 !important;
        opacity: 0 !important;
        pointer-events: none !important;
        transition: opacity 0.8s cubic-bezier(0.4, 0, 0.2, 1), transform 0.8s cubic-bezier(0.4, 0, 0.2, 1) !important;
        background: #a8a8a8 !important;
        color: #fff !important;
        border: none !important;
        padding: 14px 30px !important;
        border-radius: 50px !important;
        font-family: 'Montserrat', sans-serif !important;
        font-size: 0.8rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.1em !important;
        text-transform: uppercase !important;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
        white-space: nowrap !important;
      }
      #btn-approve.visible {
        opacity: 1 !important;
        pointer-events: auto !important;
        transform: translateX(-50%) translateY(0) !important;
      }
      .mobile-action-bar {
        display: none !important;
      }
    }
  `;

  return (
    <>
      <Head>
        <meta charSet="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{titulo} — {cliente}</title>

        <link rel="stylesheet" href="/propostas/css/propostas.css" />
        <link rel="stylesheet" href="/propostas/css/propostas-mobile.css" />
        <link rel="icon" type="image/svg+xml" href={raizUrl('/favicon.svg')} />
        <link rel="apple-touch-icon" sizes="180x180" href={raizUrl('/favicon_io/apple-touch-icon.png')} />
        <link rel="icon" type="image/png" sizes="32x32" href={raizUrl('/favicon_io/favicon-32x32.png')} />
        <link rel="icon" type="image/png" sizes="16x16" href={raizUrl('/favicon_io/favicon-16x16.png')} />
        <link rel="manifest" href={raizUrl('/favicon_io/site.webmanifest')} />

        {isCasamento && <style dangerouslySetInnerHTML={{ __html: casamentoMobileCss }} />}

        <script src="https://unpkg.com/lucide@latest" async></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" async></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" async></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" async></script>
      </Head>

      <header className="mobile-header no-print" style={{ display: 'none' }}>
        <span className="mobile-header-logo">DISTINTO</span>
        <span className="mobile-header-title">{titulo} — {cliente}</span>
      </header>

      <div className="proposal-hud-lines" />
      <div className="proposal-frame">
        <div className="frame-item">
          <div className="frame-top">{categoriaProjeto}</div>
          <div className="frame-bottom logo-container" id="dynamic-logo">
            <img src={raizUrl('/assets/distinto_logo.svg')} alt="Distinto" id="logo-svg" />
            <span className="logo-text">PONCEM STUDIO | DISTINTO</span>
          </div>
        </div>
        <div className="frame-item">
          <div className="frame-top">{mesNome}</div>
          <div className="frame-bottom">{frameCliente}</div>
        </div>
        <div className="frame-item">
          <div className="frame-top">{ano}</div>
          <div className="frame-bottom">PROPOSTA</div>
        </div>
      </div>

      <div className="proposal-wrapper">{[
          !isCasamento && <div className="fixed-section-title" key="title"><h2>ETAPAS DO<br />PROJETO</h2></div>,
          <Injected html={slides} key="slides" />,
        ]}</div>

      <button className="btn-export-top no-print" onClick={() => (window as any).showExportModal?.()}>
        <i data-lucide="file-down" suppressHydrationWarning></i>
        <span>PDF</span>
      </button>

      {isCasamento ? (
        <button onClick={() => (window as any).openInteractiveModal?.()} id="btn-approve" className="btn-floating no-print">
          <span>✿ Escolher Nosso Plano</span>
        </button>
      ) : (
        <a href={waLink} id="btn-approve" className="btn-floating no-print">
          <span>Aprovar Proposta</span>
          <i data-lucide="check-circle" suppressHydrationWarning></i>
        </a>
      )}

      {!isCasamento && (
        <div className="mobile-action-bar no-print">
          <a href={waLink} className="mobile-btn-approve">
            <i data-lucide="check-circle" suppressHydrationWarning></i>
            <span>Aprovar</span>
          </a>
          <button onClick={() => (window as any).showExportModal?.()} className="mobile-btn-pdf">
            <i data-lucide="file-down" suppressHydrationWarning></i>
            <span>PDF</span>
          </button>
        </div>
      )}

      <div id="export-modal" className="export-modal no-print" style={{ display: 'none' }}>
        <div className="export-modal-content">
          <h3>Exportar Proposta</h3>
          <p>Cada seção da proposta será exportada como uma página A4 em paisagem.</p>
          <div className="export-options">
            <button onClick={() => (window as any).exportPDF?.()} className="export-option">
              <div className="option-preview horizontal">
                <div className="mac-screen"></div>
              </div>
              <span>Exportar em paisagem</span>
            </button>
          </div>
          <button onClick={() => (window as any).hideExportModal?.()} className="btn-cancel-export">Cancelar</button>
        </div>
      </div>

      {isCasamento && casamento && (
        <>
          <div dangerouslySetInnerHTML={{ __html: planModalHtml(casamento) }} />

          <script
            dangerouslySetInnerHTML={{
              __html: planModalScript({
                slug,
                nomeCasal: casamento.mNomeCasal,
                pHeritage: casamento.mPHeritage,
                pCinematic: casamento.mPCinematic,
                pEssencial: casamento.mPEssencial,
                pBoudoir: casamento.mPBoudoir,
                pPrewedding: casamento.mPPrewedding,
                condHC: casamento.condHC,
                condE: casamento.condE,
              }),
            }}
          />
        </>
      )}

      <script dangerouslySetInnerHTML={{ __html: publicInlineScript(slug, isCasamento) }} />
      <script src="/propostas/js/propostas.js?v=pdf-canvas-3" />

      {isCasamento && (
        <script dangerouslySetInnerHTML={{ __html: casamentoClosingScript() }} />
      )}
    </>
  );
}

export const getServerSideProps: GetServerSideProps<PageProps> = async (context) => {
  const slug = String(context.params?.slug || '');
  if (!slug) return { notFound: true };

  const proposta = await queryOne(`SELECT * FROM propostas WHERE slug = $1 LIMIT 1`, [slug]);
  if (!proposta) return { notFound: true };

  const config = await queryOne(`SELECT * FROM configuracao_empresa WHERE id = 'principal' LIMIT 1`);
  const empresa = config || {};

  let dados: any = {};
  try {
    dados = typeof proposta.dados_json === 'string' ? JSON.parse(proposta.dados_json) : (proposta.dados_json || {});
  } catch (e) {}

  const tipo = proposta.tipo || 'casamento';
  const cliente = proposta.cliente_nome || '';
  const titulo = proposta.titulo || 'Proposta';

  const dataCriacao = new Date(proposta.created_at || proposta.criado_em || Date.now());
  const mesNome = MESES_PT[String(dataCriacao.getMonth() + 1)] || 'JUNHO';
  const ano = String(dataCriacao.getFullYear());
  const categoriaProjeto = dados.categoria_projeto ?? 'PROJETO DE ESTRATÉGIA';

  const resp = textoResponsavel(dados);
  const frameCliente = mbUpper(cliente) + (resp ? ` | ${mbUpper(resp)}` : '');

  let casamento: CasamentoProps | undefined;
  let mensagemWA: string | undefined;

  if (tipo === 'casamento') {
    const servRows = await query(`SELECT id, nome, preco_venda AS valor, tipo FROM servicos WHERE categoria = 'wedding' AND ativo = 1`);
    const planRows = await query(`SELECT * FROM servicos WHERE categoria = 'wedding' AND tipo = 'plano' AND ativo = 1 ORDER BY preco_venda DESC`);
    const servicosWedding = buildServicosWedding(servRows);
    const planosWedding = buildPlanosWedding(planRows, dados);
    const conds = buildCondicoesCasamento(dados);

    const nomeNoivo = dados.nome_noivo ?? '';
    const nomeNoiva = dados.nome_noiva ?? '';
    const nomeCasal = (nomeNoivo && nomeNoiva) ? `${nomeNoivo} & ${nomeNoiva}` : cliente;

    let mNomeCasal = '';
    if (String(nomeNoivo).trim() && String(nomeNoiva).trim()) {
      mNomeCasal = String(nomeNoivo).trim().split(' ')[0] + ' & ' + String(nomeNoiva).trim().split(' ')[0];
    } else {
      mNomeCasal = cliente;
    }

    const apiKey = empresa.gemini_api_key || process.env.GEMINI_API_KEY || '';
    mensagemWA = await gerarMensagemWhatsApp(nomeNoivo, nomeNoiva, nomeCasal, apiKey);

    casamento = {
      mPHeritage: isNumericLike(dados.valor_heritage) ? Number(dados.valor_heritage) : 7900,
      mPCinematic: isNumericLike(dados.valor_cinematic) ? Number(dados.valor_cinematic) : 4500,
      mPEssencial: isNumericLike(dados.valor_essencial) ? Number(dados.valor_essencial) : 2800,
      mPBoudoir: isNumericLike(dados.valor_boudoir) ? Number(dados.valor_boudoir) : 800,
      mPPrewedding: isNumericLike(dados.valor_prewedding) ? Number(dados.valor_prewedding) : 1200,
      condHC: conds.condHC,
      condE: conds.condE,
      mNomeCasal,
      servicosWedding,
      planosWedding,
      valorBoudoir: isNumericLike(dados.valor_boudoir) ? Number(dados.valor_boudoir) : 500,
      valorPrewedding: isNumericLike(dados.valor_prewedding) ? Number(dados.valor_prewedding) : 1100,
    };
  } else if (tipo === 'marketing') {
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
      casamento: casamento || null,
    } as any,
  };
};