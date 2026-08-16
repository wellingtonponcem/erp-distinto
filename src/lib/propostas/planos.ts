/**
 * Dados de apoio do template-casamento.php (port fiel das queries/transformações do topo do PHP):
 *   - servicosWedding  (SELECT id, nome, preco_venda AS valor, tipo FROM servicos WHERE categoria='wedding' AND ativo=1)
 *   - planosWedding    (SELECT * FROM servicos WHERE categoria='wedding' AND tipo='plano' AND ativo=1 ORDER BY preco_venda DESC)
 */

import { valorMonetarioCasamento } from '@/lib/propostas/common';

/** Converte linhas da tabela servicos (categoria wedding) no mapa id => {id, nome, valor, tipo}. */
export function buildServicosWedding(rows: any[]): Record<string, any> {
  const map: Record<string, any> = {};
  for (const s of rows || []) {
    map[s.id] = { id: s.id, nome: s.nome, valor: s.valor ?? s.preco_venda ?? 0, tipo: s.tipo };
  }
  return map;
}

/** Port fiel do loop de planos + filtro de visibilidade do template-casamento.php (linhas ~89-127). */
export function buildPlanosWedding(planosRows: any[], dados: any): any[] {
  const planos: any[] = [];

  for (const pkg of planosRows || []) {
    const slug = String(pkg.nome || '')
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9-]+/g, '-');
    const id = slug.includes('heritage') ? 'heritage' : slug.includes('cinematic') ? 'cinematic' : 'essencial';

    planos.push({
      id,
      nome: pkg.nome,
      preco_venda: valorMonetarioCasamento(dados[`valor_${id}`] ?? null, parseFloat(pkg.preco_venda) || 0),
      descricao: pkg.subtitulo ?? pkg.descricao,
      prazo_minimo: pkg.prazo_minimo ?? 6,
      itens_json: pkg.beneficios_json,
      show_boudoir: (dados[`include_boudoir_${id}`] ?? dados.include_boudoir ?? false) !== false,
      show_prewedding: (dados[`include_prewedding_${id}`] ?? dados.include_prewedding ?? false) !== false,
      extra_upgrades: dados.upgrades?.[id] ?? [],
      custom_items: dados.itens_personalizados?.[id] ?? [],
    });
  }

  return planos.filter((p) => {
    if (p.id === 'heritage') return (dados.show_heritage ?? true) !== false;
    if (p.id === 'cinematic') return (dados.show_cinematic ?? true) !== false;
    if (p.id === 'essencial') return (dados.show_essencial ?? true) !== false;
    return true;
  });
}

/** Condições comerciais dos planos (mesmos fallbacks de p.php). */
export function buildCondicoesCasamento(dados: any): { condHC: string; condE: string } {
  return {
    condHC: dados.condicoes_heritage_cinematic ?? 'Entrada de 20% + saldo parcelado em até 6x',
    condE: dados.condicoes_essencial ?? 'Entrada de 25% + saldo parcelado em até 5x',
  };
}