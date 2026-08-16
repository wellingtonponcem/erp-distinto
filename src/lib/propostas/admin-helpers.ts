/** Helpers do módulo administrativo de Propostas (port de includes/helpers.php e api/propostas/gerar.php). */

export function slugify(text: string): string {
  let t = String(text)
    .replace(/[^\p{L}\d]+/gu, '-')
    .replace(/^-+|-+$/g, '')
    .toLowerCase()
    .replace(/-+/g, '-');

  const map: Record<string, string> = {
    á: 'a', à: 'a', ã: 'a', â: 'a', é: 'e', ê: 'e', í: 'i', ó: 'o', ô: 'o', õ: 'o',
    ú: 'u', ü: 'u', ç: 'c',
    Á: 'a', À: 'a', Ã: 'a', Â: 'a', É: 'e', Ê: 'e', Í: 'i', Ó: 'o', Ô: 'o', Õ: 'o',
    Ú: 'u', Ü: 'u', Ç: 'c',
  };
  t = t
    .split('')
    .map((c) => map[c] || c)
    .join('');
  t = t.replace(/[^-\w]+/g, '');

  return t === '' ? 'n-a' : t;
}

export function contatoResponsavel(dados: {
  tipo?: string;
  contato_tipo?: string;
  nome_noivo?: string;
  nome_noiva?: string;
  responsavel?: string;
}): string {
  const contatoTipo = dados.contato_tipo || '';
  const nomeNoivo = String(dados.nome_noivo || '').trim();
  const nomeNoiva = String(dados.nome_noiva || '').trim();
  const responsavel = String(dados.responsavel || '').trim();

  if (contatoTipo === 'noivo' && nomeNoivo !== '') return nomeNoivo;
  if (contatoTipo === 'noiva' && nomeNoiva !== '') return nomeNoiva;

  if (contatoTipo === 'casal') {
    if (nomeNoivo !== '' && nomeNoiva !== '') return `${nomeNoivo} & ${nomeNoiva}`;
    return nomeNoiva !== '' ? nomeNoiva : nomeNoivo;
  }

  return responsavel !== '' ? responsavel : nomeNoiva !== '' ? nomeNoiva : nomeNoivo;
}

export function normalizarDataFormulario(valor: any): string {
  const v = String(valor ?? '').trim();
  if (v === '') return '';
  if (/^\d{4}-\d{2}-\d{2}$/.test(v)) return v;
  const m = v.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
  if (m) return `${m[3]}-${m[2]}-${m[1]}`;
  return v;
}

export function decimalBrasileiro(valor: any): number {
  let texto = String(valor ?? '').trim();
  if (texto === '') return 0;
  texto = texto.replace(/R\$/g, '').replace(/ /g, '');
  if (texto.includes(',') && texto.includes('.')) {
    texto = texto.replace(/\./g, '').replace(/,/g, '.');
  } else if (texto.includes(',')) {
    texto = texto.replace(/,/g, '.');
  }
  return Number(texto) || 0;
}

export function dateBRFull(): string {
  const d = new Date();
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

export function dateBR(): string {
  const d = new Date();
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

export function dataMaisDias(dias: number): string {
  const d = new Date();
  d.setDate(d.getDate() + dias);
  return d.toISOString().slice(0, 10);
}

export function dataISO(): string {
  const d = new Date();
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

export function parseDadosJson(json: any): any {
  if (!json) return {};
  try {
    return typeof json === 'string' ? JSON.parse(json) : json || {};
  } catch (e) {
    return {};
  }
}