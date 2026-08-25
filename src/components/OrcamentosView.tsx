import React, { useEffect, useState } from 'react';
import {
  Search, Mail, Phone, Calendar, MapPin, X, Trash2, Inbox, Users,
  FileText, Check, Archive, RefreshCw, Heart, ClipboardList, Copy, Link as LinkIcon,
} from 'lucide-react';

const safeFetchJson = async (url: string, options?: RequestInit) => {
  try {
    const res = await fetch(url, options);
    const text = await res.text();
    let data: any = null;
    try {
      data = JSON.parse(text);
    } catch (e) {
      data = { erro: `Resposta inválida: ${text.substring(0, 100)}` };
    }
    return { ok: res.ok, status: res.status, data };
  } catch (err: any) {
    return { ok: false, status: 500, data: { erro: err.message || 'Erro de conexão' } };
  }
};

interface Orcamento {
  id: string;
  nome_contato: string;
  email: string;
  telefone: string;
  data_casamento: string;
  status: string;
  criado_em: string;
  dados: Record<string, string | string[]>;
}

interface BriefingItem {
  id: string;
  cliente_nome: string;
  data_casamento?: string;
  dados: Record<string, string>;
  status: string;
  criado_em: string;
}

function formatDataBr(iso?: string): string {
  if (!iso) return '';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  return d.toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatValor(v: string | string[]): string {
  if (Array.isArray(v)) return v.join(', ');
  return String(v ?? '');
}

export const OrcamentosView: React.FC = () => {
  const [abaAtiva, setAbaAtiva] = useState<'orcamentos' | 'briefings'>('orcamentos');
  
  const [items, setItems] = useState<Orcamento[]>([]);
  const [briefings, setBriefings] = useState<BriefingItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [query, setQuery] = useState('');
  
  // Gerador de Link de Briefing no ERP
  const [nomeCasalGerador, setNomeCasalGerador] = useState('Kevin & Isabely');
  const [copiadoLink, setCopiadoLink] = useState(false);

  const [selected, setSelected] = useState<Orcamento | null>(null);
  const [selectedBriefing, setSelectedBriefing] = useState<BriefingItem | null>(null);
  const [confirmDelete, setConfirmDelete] = useState<Orcamento | null>(null);

  const carregar = async () => {
    setLoading(true);
    const rOrc = await safeFetchJson('/api/comercial/orcamentos');
    if (rOrc.ok && Array.isArray(rOrc.data)) {
      setItems(rOrc.data as Orcamento[]);
    }

    const rBrief = await safeFetchJson('/api/briefings/enviar');
    if (rBrief.ok && Array.isArray(rBrief.data)) {
      setBriefings(rBrief.data as BriefingItem[]);
    }
    setLoading(false);
  };

  useEffect(() => {
    carregar();
  }, []);

  const marcar = async (item: Orcamento, status: string) => {
    await safeFetchJson('/api/comercial/orcamentos', {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: item.id, status }),
    });
    carregar();
  };

  const excluir = async (item: Orcamento) => {
    await safeFetchJson(`/api/comercial/orcamentos?id=${encodeURIComponent(item.id)}`, { method: 'DELETE' });
    setConfirmDelete(null);
    if (selected && selected.id === item.id) setSelected(null);
    carregar();
  };

  const copiarLinkBriefing = () => {
    const origin = typeof window !== 'undefined' ? window.location.origin : 'https://wedistinto.com';
    const link = `${origin}/briefing?cliente=${encodeURIComponent(nomeCasalGerador.trim() || 'Noivos')}`;
    navigator.clipboard.writeText(link);
    setCopiadoLink(true);
    setTimeout(() => setCopiadoLink(false), 2500);
  };

  const filteredOrcamentos = items.filter((it) => {
    const q = query.trim().toLowerCase();
    if (!q) return true;
    return (
      (it.nome_contato || '').toLowerCase().includes(q) ||
      (it.email || '').toLowerCase().includes(q) ||
      (it.telefone || '').toLowerCase().includes(q) ||
      (it.data_casamento || '').toLowerCase().includes(q)
    );
  });

  const filteredBriefings = briefings.filter((b) => {
    const q = query.trim().toLowerCase();
    if (!q) return true;
    return (
      (b.cliente_nome || '').toLowerCase().includes(q) ||
      (b.data_casamento || '').toLowerCase().includes(q)
    );
  });

  const novosOrcamentos = items.filter((i) => i.status === 'novo').length;

  return (
    <div className="bg-[#050505] text-white flex flex-col min-h-screen rounded-2xl overflow-hidden p-6 font-sans">
      <style>{`
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #333; border-radius: 8px; }
        .orc-card { background: #121212; border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 18px; transition: all 0.2s; }
        .orc-card:hover { background: #171717; border-color: rgba(255,255,255,0.12); }
        .orc-badge { font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; padding: 4px 10px; border-radius: 999px; }
      `}</style>

      {/* Header com Abas */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Solicitações & Briefings</h1>
          <p className="text-zinc-500 text-sm mt-1">Formulários públicos — Orçamentos (`/orcamento`) & Briefings Logísticos (`/briefing`)</p>
        </div>

        <div className="flex items-center space-x-3">
          <div className="flex bg-zinc-900 p-1 rounded-xl border border-white/10">
            <button
              onClick={() => setAbaAtiva('orcamentos')}
              className={`px-4 py-2 rounded-lg text-xs font-bold transition flex items-center space-x-2 ${
                abaAtiva === 'orcamentos' ? 'bg-[#c5a880] text-black shadow-md' : 'text-zinc-400 hover:text-white'
              }`}
            >
              <Inbox className="w-4 h-4" />
              <span>Orçamentos ({items.length})</span>
            </button>
            <button
              onClick={() => setAbaAtiva('briefings')}
              className={`px-4 py-2 rounded-lg text-xs font-bold transition flex items-center space-x-2 ${
                abaAtiva === 'briefings' ? 'bg-[#c5a880] text-black shadow-md' : 'text-zinc-400 hover:text-white'
              }`}
            >
              <ClipboardList className="w-4 h-4" />
              <span>Briefings Logísticos ({briefings.length})</span>
            </button>
          </div>

          <button
            onClick={carregar}
            className="flex items-center gap-2 p-2.5 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white"
            title="Atualizar"
          >
            <RefreshCw className="w-5 h-5" />
          </button>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div className="orc-card flex items-center gap-4">
          <div className="w-11 h-11 rounded-xl bg-white/5 flex items-center justify-center">
            <Inbox className="w-5 h-5 text-[#c5a880]" />
          </div>
          <div>
            <div className="text-2xl font-bold">{items.length}</div>
            <div className="text-xs text-zinc-500">Total de Orçamentos</div>
          </div>
        </div>
        <div className="orc-card flex items-center gap-4">
          <div className="w-11 h-11 rounded-xl bg-white/5 flex items-center justify-center">
            <ClipboardList className="w-5 h-5 text-[#c5a880]" />
          </div>
          <div>
            <div className="text-2xl font-bold">{briefings.length}</div>
            <div className="text-xs text-zinc-500">Briefings Recebidos</div>
          </div>
        </div>
        <div className="orc-card flex items-center gap-4">
          <div className="w-11 h-11 rounded-xl bg-white/5 flex items-center justify-center">
            <Heart className="w-5 h-5 text-[#c5a880]" />
          </div>
          <div>
            <div className="text-2xl font-bold">{novosOrcamentos}</div>
            <div className="text-xs text-zinc-500">Novas Solicitações</div>
          </div>
        </div>
      </div>

      {/* GERADOR DE LINK PERSONALIZADO PARA O CASAL */}
      {abaAtiva === 'briefings' && (
        <div className="mb-6 bg-purple-950/40 border border-purple-500/30 p-4 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div className="space-y-1">
            <div className="flex items-center space-x-2 text-purple-300 font-bold text-xs">
              <LinkIcon className="w-4 h-4" />
              <span>Gerador de Link Personalizado de Briefing</span>
            </div>
            <p className="text-xs text-zinc-400">
              Digite o nome dos noivos abaixo e copie o link direto para enviar pelo WhatsApp!
            </p>
          </div>

          <div className="flex items-center space-x-2">
            <input
              type="text"
              value={nomeCasalGerador}
              onChange={(e) => setNomeCasalGerador(e.target.value)}
              placeholder="Ex: Kevin & Isabely"
              className="bg-black/60 border border-purple-500/40 rounded-xl px-3 py-2 text-xs text-white outline-none focus:ring-2 focus:ring-purple-500 w-48 font-bold"
            />
            <button
              onClick={copiarLinkBriefing}
              className="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shrink-0 shadow-md"
            >
              <Copy className="w-3.5 h-3.5" />
              <span>{copiadoLink ? 'Link Copiado! 🥂' : 'Copiar Link Personalizado'}</span>
            </button>
          </div>
        </div>
      )}

      {/* Search */}
      <div className="mb-6 relative max-w-md">
        <Search className="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500" />
        <input
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder={abaAtiva === 'orcamentos' ? "Buscar orçamentos por nome, e-mail..." : "Buscar briefings por nome do casal..."}
          className="w-full bg-black/40 border border-white/10 rounded-xl pl-11 pr-4 py-3 text-sm text-white outline-none focus:border-white/40"
        />
      </div>

      {/* ABA 1: SOLICITAÇÕES DE ORÇAMENTO */}
      {abaAtiva === 'orcamentos' && (
        <>
          {loading ? (
            <div className="text-zinc-500 text-sm py-10 text-center">Carregando...</div>
          ) : filteredOrcamentos.length === 0 ? (
            <div className="text-center py-20 text-zinc-600">
              <Inbox className="w-12 h-12 mx-auto mb-4 opacity-40" />
              <p className="text-sm">Nenhuma solicitação de orçamento encontrada.</p>
            </div>
          ) : (
            <div className="space-y-3">
              {filteredOrcamentos.map((it) => (
                <div
                  key={it.id}
                  className={`orc-card flex items-center justify-between gap-4 cursor-pointer ${
                    it.status === 'novo' ? 'border-[#c5a880]/30' : ''
                  }`}
                  onClick={() => {
                    setSelected(it);
                    if (it.status === 'novo') marcar(it, 'lido');
                  }}
                >
                  <div className="flex items-center gap-4 min-w-0">
                    <div className="w-11 h-11 rounded-full bg-white/5 flex items-center justify-center text-lg shrink-0">
                      {it.status === 'novo' ? (
                        <span className="w-2.5 h-2.5 rounded-full bg-[#c5a880]" />
                      ) : (
                        <Users className="w-5 h-5 text-zinc-500" />
                      )}
                    </div>
                    <div className="min-w-0">
                      <p className="font-semibold truncate">
                        {it.nome_contato || 'Sem nome'}
                        {it.status === 'novo' && <span className="orc-badge ml-2 bg-[#c5a880]/20 text-[#e6cd9f]">Novo</span>}
                      </p>
                      <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-500 mt-1">
                        {it.email && <span className="flex items-center gap-1.5"><Mail className="w-3 h-3" />{it.email}</span>}
                        {it.telefone && <span className="flex items-center gap-1.5"><Phone className="w-3 h-3" />{it.telefone}</span>}
                        {it.data_casamento && <span className="flex items-center gap-1.5"><Calendar className="w-3 h-3" />{it.data_casamento}</span>}
                      </div>
                    </div>
                  </div>
                  <div className="flex items-center gap-2 shrink-0">
                    {it.status !== 'lido' && it.status !== 'arquivado' && (
                      <button
                        onClick={(e) => { e.stopPropagation(); marcar(it, 'lido'); }}
                        className="p-2 hover:bg-zinc-800 rounded-full text-zinc-400 hover:text-[#c5a880] transition"
                        title="Marcar como lido"
                      >
                        <Check className="w-4 h-4" />
                      </button>
                    )}
                    {it.status === 'lido' && (
                      <button
                        onClick={(e) => { e.stopPropagation(); marcar(it, 'arquivado'); }}
                        className="p-2 hover:bg-zinc-800 rounded-full text-zinc-400 hover:text-white transition"
                        title="Arquivar"
                      >
                        <Archive className="w-4 h-4" />
                      </button>
                    )}
                    <button
                      onClick={(e) => { e.stopPropagation(); setConfirmDelete(it); }}
                      className="p-2 hover:bg-red-500/10 rounded-full text-zinc-400 hover:text-red-400 transition"
                      title="Excluir"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </>
      )}

      {/* ABA 2: BRIEFINGS LOGÍSTICOS RECEBIDOS */}
      {abaAtiva === 'briefings' && (
        <>
          {loading ? (
            <div className="text-zinc-500 text-sm py-10 text-center">Carregando briefings...</div>
          ) : filteredBriefings.length === 0 ? (
            <div className="text-center py-20 text-zinc-600">
              <ClipboardList className="w-12 h-12 mx-auto mb-4 opacity-40" />
              <p className="text-sm">Nenhum briefing logístico recebido ainda.</p>
              <p className="text-xs text-zinc-500 mt-1">
                Use o gerador acima para copiar o link personalizado do casal e enviar pelo WhatsApp!
              </p>
            </div>
          ) : (
            <div className="space-y-3">
              {filteredBriefings.map((b) => (
                <div
                  key={b.id}
                  className="orc-card flex items-center justify-between gap-4 cursor-pointer hover:border-purple-500/40"
                  onClick={() => setSelectedBriefing(b)}
                >
                  <div className="flex items-center gap-4 min-w-0">
                    <div className="w-11 h-11 rounded-full bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-lg shrink-0">
                      <ClipboardList className="w-5 h-5 text-purple-400" />
                    </div>
                    <div className="min-w-0">
                      <p className="font-semibold text-white truncate">
                        {b.cliente_nome || 'Briefing Casamento'}
                        <span className="orc-badge ml-2 bg-purple-500/20 text-purple-300">Briefing Logístico</span>
                      </p>
                      <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-400 mt-1">
                        {b.data_casamento && (
                          <span className="flex items-center gap-1.5"><Calendar className="w-3 h-3 text-purple-400" /> Data: {b.data_casamento}</span>
                        )}
                        <span className="text-zinc-500">Enviado em {formatDataBr(b.criado_em)}</span>
                      </div>
                    </div>
                  </div>
                  <div className="shrink-0">
                    <button
                      onClick={() => setSelectedBriefing(b)}
                      className="px-4 py-2 bg-purple-500/10 hover:bg-purple-500/20 border border-purple-500/30 text-purple-300 rounded-xl text-xs font-bold transition"
                    >
                      Ver Detalhes do Briefing
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </>
      )}

      {/* Modal Detalhes do Orçamento */}
      {selected && (
        <div className="fixed inset-0 z-[2000] flex items-center justify-center p-6 bg-black/80 backdrop-blur-md" onClick={() => setSelected(null)}>
          <div
            className="bg-[#0c0c0c] border border-white/10 rounded-[1.5rem] max-w-2xl w-full max-h-[90vh] overflow-y-auto custom-scrollbar"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between p-6 border-b border-white/10 sticky top-0 bg-[#0c0c0c] z-10">
              <div>
                <h3 className="text-lg font-bold">{selected.nome_contato || 'Sem nome'}</h3>
                <p className="text-xs text-zinc-500 mt-0.5">Recebido em {formatDataBr(selected.criado_em)}</p>
              </div>
              <button onClick={() => setSelected(null)} className="p-2 hover:bg-white/10 rounded-full transition">
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="p-6 space-y-6">
              {(['nome_contato', 'email', 'telefone_whatsapp', 'data_casamento'] as const).map((k) => {
                const val = selected.dados[k];
                if (!val) return null;
                return (
                  <div key={k}>
                    <p className="text-[11px] uppercase tracking-wider text-zinc-500 mb-1.5">
                      {k === 'nome_contato' ? 'Contato' : k === 'email' ? 'E-mail' : k === 'telefone_whatsapp' ? 'WhatsApp' : 'Data do casamento'}
                    </p>
                    <p className="text-sm text-white">{formatValor(val)}</p>
                  </div>
                );
              })}

              <div className="h-px bg-white/5" />

              <div className="space-y-5">
                {selected.dados && Object.entries(selected.dados).filter(
                  ([k]) => !['nome_contato', 'email', 'telefone_whatsapp', 'data_casamento'].includes(k)
                ).map(([key, val]) => {
                  const pretty: Record<string, string> = {
                    cidade_estado: 'Cidade / Estado',
                    locais_definidos: 'Locais definidos',
                    nome_locais: 'Nome dos locais',
                    numero_convidados: 'Nº de convidados',
                    cerimonialista: 'Cerimonialista',
                    servicos_interesse: 'Serviços de interesse',
                    importancia_fotografia: 'Importância da fotografia',
                    orcamento_previsto: 'Orçamento previsto',
                    como_conheceu: 'Como conheceu',
                    observacoes: 'Observações',
                  };
                  return (
                    <div key={key}>
                      <p className="text-[11px] uppercase tracking-wider text-zinc-500 mb-1.5">{pretty[key] || key.replace(/_/g, ' ')}</p>
                      <p className="text-sm text-white whitespace-pre-wrap">{formatValor(val)}</p>
                    </div>
                  );
                })}
              </div>

              <div className="flex gap-3 pt-2">
                <button
                  onClick={() => { marcar(selected, 'lido'); setSelected(null); }}
                  className="flex items-center gap-2 bg-[#c5a880] text-black px-5 py-2.5 rounded-full text-xs font-bold hover:bg-[#d4b78f] transition"
                >
                  <Check className="w-4 h-4" /> Marcar como lido
                </button>
                <button
                  onClick={() => setConfirmDelete(selected)}
                  className="flex items-center gap-2 px-5 py-2.5 rounded-full text-xs font-bold border border-white/10 text-zinc-300 hover:border-red-500/50 hover:text-red-400 transition"
                >
                  <Trash2 className="w-4 h-4" /> Excluir
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Modal Detalhes do Briefing Logístico */}
      {selectedBriefing && (
        <div className="fixed inset-0 z-[2000] flex items-center justify-center p-6 bg-black/80 backdrop-blur-md" onClick={() => setSelectedBriefing(null)}>
          <div
            className="bg-[#0c0c0c] border border-purple-500/30 rounded-[1.5rem] max-w-3xl w-full max-h-[90vh] overflow-y-auto custom-scrollbar"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between p-6 border-b border-white/10 sticky top-0 bg-[#0c0c0c] z-10">
              <div>
                <span className="text-[10px] font-bold uppercase tracking-widest text-purple-400">Briefing Logístico de Casamento</span>
                <h3 className="text-xl font-bold text-white mt-0.5">{selectedBriefing.cliente_nome}</h3>
                <p className="text-xs text-zinc-400 mt-0.5">Preenchido em {formatDataBr(selectedBriefing.criado_em)}</p>
              </div>
              <button onClick={() => setSelectedBriefing(null)} className="p-2 hover:bg-white/10 rounded-full transition">
                <X className="w-5 h-5 text-zinc-400" />
              </button>
            </div>

            <div className="p-6 space-y-6">
              {selectedBriefing.dados && Object.entries(selectedBriefing.dados).map(([key, val]) => {
                if (!val) return null;
                const fieldLabels: Record<string, string> = {
                  nome_noivos: 'Noivos',
                  data_casamento: 'Data do Casamento',
                  whatsapp_emergencia_noiva: 'WhatsApp Noiva',
                  whatsapp_emergencia_noivo: 'WhatsApp Noivo',
                  horario_preparacao_noiva: 'Making of Noiva',
                  horario_preparacao_noivo: 'Making of Noivo',
                  horario_chegada_convidados: 'Chegada Convidados',
                  horario_inicio_cerimonia: 'Início Cerimônia',
                  horario_fim_cerimonia: 'Fim Cerimônia',
                  horario_sessao_fotos: 'Sessão Fotos Casal',
                  horario_inicio_festa: 'Início Festa',
                  horario_entrada_noivos_festa: 'Entrada dos Noivos',
                  horario_brindes_discursos: 'Brindes / Discursos',
                  horario_corte_bolo: 'Corte do Bolo',
                  horario_primeira_danca: 'Primeira Dança',
                  horario_fim_festa: 'Fim da Festa',
                  momentos_especiais_fora_cronograma: 'Momentos Especiais / Surpresas',
                  local_noiva_endereco: 'Local Making Of Noiva',
                  horario_chegada_equipe_noiva: 'Chegada Equipe Noiva',
                  local_noivo_endereco: 'Local Making Of Noivo',
                  horario_chegada_equipe_noivo: 'Chegada Equipe Noivo',
                  local_cerimonia_endereco: 'Local Cerimônia',
                  restricoes_cerimonia: 'Restrições Fotografia Cerimônia',
                  restricoes_cerimonia_detalhes: 'Detalhes das Restrições',
                  local_festa_endereco: 'Local Festa',
                  local_fotos_casal: 'Ambiente Fotos Casal',
                  cerimonialista_nome_contato: 'Cerimonialista / Planner',
                  padrinhos_madrinhas_nomes: 'Padrinhos / Madrinhas',
                  pais_avos_nomes: 'Pais / Avós',
                  responsavel_ajuda_fotos_grupo: 'Responsável Fotos de Grupo',
                  atencao_especial_pessoas: 'Atenção / Etiqueta Especial',
                  momentos_prioritarios: 'Momentos Prioritários',
                  objetos_detalhes_fotografar: 'Objetos para Fotografar',
                  tradicoes_rituais: 'Tradições / Rituais',
                  plano_b_chuva: 'Plano B (Chuva / Imprevistos)',
                  contato_emergencia_dia: 'Contato Emergência Dia',
                  expectativas_registro: 'Expectativas Principais',
                  preferencias_evitar: 'O que Evitar',
                };

                return (
                  <div key={key} className="border-b border-white/5 pb-3">
                    <p className="text-[11px] font-bold uppercase tracking-wider text-purple-400 mb-1">
                      {fieldLabels[key] || key.replace(/_/g, ' ')}
                    </p>
                    <p className="text-sm text-white whitespace-pre-wrap">{formatValor(val)}</p>
                  </div>
                );
              })}
            </div>
          </div>
        </div>
      )}

      {/* Delete confirm */}
      {confirmDelete && (
        <div className="fixed inset-0 z-[2100] flex items-center justify-center p-6 bg-black/80 backdrop-blur-md" onClick={() => setConfirmDelete(null)}>
          <div
            className="bg-[#0c0c0c] border border-white/10 rounded-2xl max-w-sm w-full p-6"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center gap-3 mb-4">
              <div className="w-10 h-10 rounded-xl bg-red-500/10 flex items-center justify-center">
                <Trash2 className="w-5 h-5 text-red-400" />
              </div>
              <h3 className="text-base font-bold">Excluir solicitação</h3>
            </div>
            <p className="text-sm text-zinc-400 mb-6">
              Tem certeza que deseja excluir a solicitação de <strong className="text-white">{confirmDelete.nome_contato || 'contato'}</strong>? Esta ação não pode ser desfeita.
            </p>
            <div className="flex gap-3 justify-end">
              <button onClick={() => setConfirmDelete(null)} className="px-4 py-2 rounded-full text-xs font-bold text-zinc-400 hover:text-white transition">
                Cancelar
              </button>
              <button
                onClick={() => excluir(confirmDelete)}
                className="px-4 py-2 rounded-full text-xs font-bold bg-red-500 text-white hover:bg-red-400 transition"
              >
                Excluir
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
