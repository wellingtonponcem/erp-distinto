/**
 * Helpers compartilhados do módulo de Propostas (port de includes/helpers.php).
 * Usados pelos templates TS que replicam os includes/propostas/template-*.php.
 */

/** Contexto exposto aos templates (espelho das variáveis que p.php injeta via include). */
export interface SlideCtx {
  proposta: Record<string, any>;
  dados: Record<string, any>;
  tipo: string;
  cliente: string;
  mesNome: string;
  ano: string;
  categoriaProjeto: string;
  empresa: Record<string, any>;
  slug: string;

  // Dados de apoio pré-computados (espelham variáveis que template-casamento.php monta no topo)
  servicosWedding?: Record<string, any>;
  planosWedding?: any[];
  condHC?: string;
  condE?: string;
  depoimentos?: { texto: string; autor: string }[];
  mensagemWA?: string;
}

/** Emula htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'). */
export function esc(v: unknown): string {
  const s = v === null || v === undefined ? '' : String(v);
  return s
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/** Emula sanitizar(): htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8'). */
export function sanitizar(v: unknown): string {
  return esc(String(v === null || v === undefined ? '' : v).trim());
}

/**
 * Emula raizUrl(). No PHP pode prefixar com base (ex: /sistema).
 * No Next.js os assets estão na raiz pública, então retorna o caminho.
 */
export function raizUrl(caminho = ''): string {
  return '/' + String(caminho).replace(/^\/+/, '');
}

/** Emula formatarMoeda(): 'R$ ' + number_format($valor, 2, ',', '.'). */
export function formatarMoeda(v: number | string): string {
  return 'R$ ' + number_format(Number(v) || 0, 2);
}

/** Emula number_format($valor, $dec). */
export function number_format(v: number | string, dec = 0): string {
  const n = Number(v);
  if (!isFinite(n)) return Number(0).toFixed(dec).replace('.', ',');
  const [intPart, decPart] = n.toFixed(dec).split('.');
  const intFmt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  return dec > 0 ? `${intFmt},${decPart}` : intFmt;
}

/** Emula mb_strtoupper / strtoupper. */
export function mbUpper(s: string): string {
  return s.toUpperCase();
}

/** Valor monetário do casamento (port fiel de valorMonetarioCasamento do template-casamento.php). */
export function valorMonetarioCasamento(
  valor: unknown,
  fallback = 0
): number {
  if (valor === null || valor === undefined || valor === '') return fallback;

  const bruto = String(valor);
  if (isFinite(Number(bruto)) && bruto.trim() !== '') {
    const n = Number(bruto);
    return n > 0 ? n : fallback;
  }

  let normalizado = bruto.replace(/R\$/g, '').replace(/ /g, '');
  if (normalizado.includes(',') && normalizado.includes('.')) {
    normalizado = normalizado.replace(/\./g, '').replace(/,/g, '.');
  } else if (normalizado.includes(',')) {
    normalizado = normalizado.replace(/,/g, '.');
  }

  const n = Number(normalizado);
  return isFinite(n) && n > 0 ? n : fallback;
}

/**
 * Gera o HTML de itens personalizados de um pacote (port de renderItensPersonalizadosCasamento do
 * template-casamento.php). Recebe a lista de itens já estruturada (nome/descricao) e devolve string.
 * Se receber array de strings, trata como itens simples.
 */
export function renderItensPersonalizadosCasamento(itens: unknown): string {
  if (!Array.isArray(itens) || itens.length === 0) return '';
  const li = itens
    .map((it: any) => {
      if (it && typeof it === 'object') {
        const nome = String(it.nome ?? '');
        const desc = it.descricao ? String(it.descricao) : '';
        return `<li><strong>${esc(nome)}</strong>${desc ? ` — ${esc(desc)}` : ''}</li>`;
      }
      return `<li>${esc(String(it))}</li>`;
    })
    .join('\n');
  return `<ul class="itens-personalizados">\n${li}\n</ul>`;
}

/** Quebra de lista de itens em array (ex: itens_heritage multiline/por \n). */
export function itensParaArray(itens: unknown): string[] {
  if (Array.isArray(itens)) return itens.map((i) => String(i));
  if (typeof itens === 'string') {
    return itens
      .split(/\r?\n/)
      .map((l) => l.trim())
      .filter((l) => l !== '');
  }
  return [];
}