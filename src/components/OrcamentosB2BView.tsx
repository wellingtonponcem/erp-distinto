import React, { useEffect, useState, useCallback } from 'react';
import {
  Search, Plus, ExternalLink, Copy, Edit3, Trash2, FileText,
  CheckCircle2, XCircle, ChevronDown, ChevronUp, GripVertical,
  MessageCircle,
} from 'lucide-react';

const safeFetchJson = async (url: string, options?: RequestInit) => {
  try {
    const res = await fetch(url, options);
    const text = await res.text();
    let data: any = null;
    try { data = JSON.parse(text); } catch (e) { data = { erro: text.substring(0, 100) }; }
    return { ok: res.ok, status: res.status, data };
  } catch (err: any) {
    return { ok: false, status: 500, data: { erro: err.message || 'Erro de conexao' } };
  }
};

interface OrcamentoB2B {
  id: string;
  cliente_nome: string;
  cliente_empresa: string;
  titulo: string;
  slug: string;
  valor_total: number;
  validade: string | null;
  status: string;
  criado_em: string;
  dados: any;
}

const statusBadge = (status: string) => {
  if (status === 'aprovado') return { label: 'Aprovado', cls: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' };
  if (status === 'recusado') return { label: 'Recusado', cls: 'bg-rose-500/20 text-rose-400 border-rose-500/30' };
  if (status === 'pendente') return { label: 'Pendente', cls: 'bg-amber-500/20 text-amber-400 border-amber-500/30' };
  return { label: 'Rascunho', cls: 'bg-zinc-500/20 text-zinc-400 border-zinc-500/30' };
};

export const OrcamentosB2BView: React.FC = () => {
  const [items, setItems] = useState<OrcamentoB2B[]>([]);
  const [loading, setLoading] = useState(true);
  const [busca, setBusca] = useState('');
  const [filtroStatus, setFiltroStatus] = useState('');
  const [modo, setModo] = useState<'lista' | 'novo' | 'editar'>('lista');
  const [editandoId, setEditandoId] = useState<string | null>(null);
  const [showModalExcluir, setShowModalExcluir] = useState<OrcamentoB2B | null>(null);

  const carregar = useCallback(async () => {
    setLoading(true);
    const params = new URLSearchParams();
    if (filtroStatus) params.set('status', filtroStatus);
    if (busca) params.set('q', busca);
    const res = await safeFetchJson(`/api/orcamentos-b2b/listar?${params.toString()}`);
    if (res.ok && Array.isArray(res.data)) setItems(res.data);
    setLoading(false);
  }, [filtroStatus, busca]);

  useEffect(() => { carregar(); }, [carregar]);

  const copiarLink = (slug: string) => {
    navigator.clipboard.writeText(`${window.location.origin}/b2b/${slug}`).then(() => alert('Link copiado!'));
  };

  const excluir = async (item: OrcamentoB2B) => {
    await safeFetchJson('/api/orcamentos-b2b/excluir', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: item.id }),
    });
    setShowModalExcluir(null);
    carregar();
  };

  const formatarData = (iso?: string) => {
    if (!iso) return '--';
    const d = new Date(iso);
    return Number.isNaN(d.getTime()) ? iso : d.toLocaleDateString('pt-BR');
  };

  if (modo === 'novo' || modo === 'editar') {
    return (
      <OrcamentoB2BForm
        orcamentoId={editandoId}
        onVoltar={() => { setModo('lista'); setEditandoId(null); carregar(); }}
      />
    );
  }

  return (
    <div className="bg-[#050505] text-white flex flex-col min-h-screen rounded-2xl overflow-hidden p-6 font-sans">
      <style>{`.orc-b2b-card { background: #121212; border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 18px; transition: all 0.2s; } .orc-b2b-card:hover { background: #171717; border-color: rgba(255,255,255,0.12); }`}</style>

      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Orcamentos B2B</h1>
          <p className="text-zinc-500 text-sm mt-1">Orcamentos simples para clientes existentes</p>
        </div>
        <button onClick={() => setModo('novo')} className="flex items-center gap-2 bg-[#c5a880] text-black px-5 py-2.5 rounded-full text-xs font-bold hover:bg-[#d4b78f] transition-all shadow-md">
          <Plus className="w-4 h-4" /> Novo Orcamento
        </button>
      </div>

      {/* Filtros */}
      <div className="orc-b2b-card flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
        <div className="flex flex-wrap items-center gap-3 w-full sm:w-auto">
          <div className="relative min-w-[260px] flex-1">
            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500" />
            <input type="text" value={busca} onChange={(e) => setBusca(e.target.value)} placeholder="Buscar por cliente, titulo, empresa..."
              className="w-full pl-10 pr-4 py-2.5 bg-black/40 border border-white/10 rounded-xl text-sm text-white outline-none focus:border-white/40" />
          </div>
          <select value={filtroStatus} onChange={(e) => setFiltroStatus(e.target.value)} className="bg-black/40 border border-white/10 text-white text-sm rounded-xl px-3 py-2.5 outline-none">
            <option value="">Todos os Status</option>
            <option value="rascunho">Rascunhos</option>
            <option value="pendente">Pendentes</option>
            <option value="aprovado">Aprovados</option>
            <option value="recusado">Recusados</option>
          </select>
          {(busca || filtroStatus) && (
            <button onClick={() => { setBusca(''); setFiltroStatus(''); }} className="text-xs text-zinc-500 hover:text-white">Limpar</button>
          )}
        </div>
        <div className="text-xs text-zinc-500">Total: <strong className="text-white">{items.length}</strong> orcamentos</div>
      </div>

      {/* Tabela */}
      <div className="bg-[#121212] rounded-2xl overflow-hidden border border-white/5">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-white/10 text-[11px] font-bold uppercase tracking-wider text-zinc-500">
                <th className="p-4">Orcamento</th>
                <th className="p-4">Cliente</th>
                <th className="p-4">Empresa</th>
                <th className="p-4">Validade</th>
                <th className="p-4">Valor</th>
                <th className="p-4">Status</th>
                <th className="p-4 text-right">Acoes</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/5 text-sm">
              {loading ? (
                <tr><td colSpan={7} className="p-8 text-center text-zinc-500">Carregando...</td></tr>
              ) : items.length === 0 ? (
                <tr><td colSpan={7} className="p-8 text-center text-zinc-500">
                  Nenhum orcamento encontrado. <button onClick={() => setModo('novo')} className="text-[#c5a880] font-bold hover:underline">Criar o primeiro</button>
                </td></tr>
              ) : items.map((o) => {
                const st = statusBadge(o.status);
                return (
                  <tr key={o.id} className="hover:bg-white/[0.02] transition-colors">
                    <td className="p-4">
                      <div className="font-bold text-white">{o.titulo}</div>
                      <a href={`/b2b/${o.slug}`} target="_blank" className="text-xs text-[#c5a880]/80 hover:text-[#c5a880] font-mono flex items-center gap-1 mt-0.5">
                        /b2b/{o.slug} <ExternalLink className="w-3 h-3" />
                      </a>
                    </td>
                    <td className="p-4 font-semibold text-white">{o.cliente_nome}</td>
                    <td className="p-4 text-zinc-400 text-xs">{o.cliente_empresa || '--'}</td>
                    <td className="p-4 text-xs text-zinc-400">{o.validade ? formatarData(o.validade) : '--'}</td>
                    <td className="p-4 font-bold text-white">R$ {o.valor_total.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
                    <td className="p-4"><span className={`px-2.5 py-1 rounded-full text-xs font-bold uppercase border ${st.cls}`}>{st.label}</span></td>
                    <td className="p-4 text-right">
                      <div className="flex items-center justify-end gap-1.5">
                        <button onClick={() => window.open(`/b2b/${o.slug}`, '_blank')} title="Ver Link" className="p-2 rounded-lg bg-white/5 hover:bg-white/10 text-white transition-colors">
                          <FileText className="w-4 h-4" />
                        </button>
                        <button onClick={() => copiarLink(o.slug)} title="Copiar Link" className="p-2 rounded-lg bg-white/5 hover:bg-white/10 text-white transition-colors">
                          <Copy className="w-4 h-4" />
                        </button>
                        <button onClick={() => { setEditandoId(o.id); setModo('editar'); }} title="Editar" className="p-2 rounded-lg bg-white/5 hover:bg-white/10 text-white transition-colors">
                          <Edit3 className="w-4 h-4" />
                        </button>
                        <button onClick={() => setShowModalExcluir(o)} title="Excluir" className="p-2 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition-colors">
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal Excluir */}
      {showModalExcluir && (
        <div className="fixed inset-0 z-[2100] flex items-center justify-center p-6 bg-black/80 backdrop-blur-md" onClick={() => setShowModalExcluir(null)}>
          <div className="bg-[#0c0c0c] border border-white/10 rounded-2xl max-w-sm w-full p-6" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center gap-3 mb-4">
              <div className="w-10 h-10 rounded-xl bg-rose-500/10 flex items-center justify-center"><Trash2 className="w-5 h-5 text-rose-400" /></div>
              <h3 className="text-base font-bold text-white">Excluir orcamento</h3>
            </div>
            <p className="text-sm text-zinc-400 mb-6">
              Tem certeza que deseja excluir o orcamento de <strong className="text-white">{showModalExcluir.cliente_nome}</strong>?
            </p>
            <div className="flex gap-3 justify-end">
              <button onClick={() => setShowModalExcluir(null)} className="px-4 py-2 rounded-full text-xs font-bold text-zinc-400 hover:text-white transition">Cancelar</button>
              <button onClick={() => excluir(showModalExcluir)} className="px-4 py-2 rounded-full text-xs font-bold bg-rose-500 text-white hover:bg-rose-400 transition">Excluir</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

// ============================================================
// FORMULARIO DE CRIACAO / EDICAO
// ============================================================
interface FormProps {
  orcamentoId: string | null;
  onVoltar: () => void;
}

const defaultDados = () => ({
  prestador: { nome: '', empresa: '', email: '', website: '' },
  cliente: { nome: '', empresa: '' },
  overview: '',
  custo: { descricao: '', entregaveis: [''] as string[], valor_total: 0 },
  timeline: { duracao: '', marcos: [{ fase: '', descricao: '' }] as { fase: string; descricao: string }[] },
  proximo_passo: '',
  termos: [''] as string[],
});

const OrcamentoB2BForm: React.FC<FormProps> = ({ orcamentoId, onVoltar }) => {
  const [loading, setLoading] = useState(!!orcamentoId);
  const [saving, setSaving] = useState(false);
  const [titulo, setTitulo] = useState('');
  const [clienteNome, setClienteNome] = useState('');
  const [clienteEmpresa, setClienteEmpresa] = useState('');
  const [validade, setValidade] = useState(() => {
    const d = new Date(); d.setDate(d.getDate() + 15);
    return d.toISOString().slice(0, 10);
  });
  const [status, setStatus] = useState('rascunho');
  const [slug, setSlug] = useState('');
  const [dados, setDados] = useState(defaultDados());

  // Secoes colapsaveis
  const [secoes, setSecoes] = useState<Record<string, boolean>>({
    prestador: true, overview: true, custo: true, timeline: true, proximo: true, termos: true,
  });
  const toggleSecao = (key: string) => setSecoes((s) => ({ ...s, [key]: !s[key] }));

  // Carregar dados se editando
  useEffect(() => {
    if (!orcamentoId) return;
    const carregar = async () => {
      setLoading(true);
      const res = await safeFetchJson('/api/orcamentos-b2b/listar');
      if (res.ok && Array.isArray(res.data)) {
        const orc = res.data.find((o: any) => o.id === orcamentoId);
        if (orc) {
          setTitulo(orc.titulo);
          setClienteNome(orc.cliente_nome);
          setClienteEmpresa(orc.cliente_empresa || '');
          setValidade(orc.validade || '');
          setStatus(orc.status || 'rascunho');
          setSlug(orc.slug || '');
          const d = orc.dados || {};
          setDados({
            prestador: d.prestador || { nome: '', empresa: '', email: '', website: '' },
            cliente: d.cliente || { nome: orc.cliente_nome, empresa: orc.cliente_empresa || '' },
            overview: d.overview || '',
            custo: {
              descricao: d.custo?.descricao || '',
              entregaveis: d.custo?.entregaveis?.length ? d.custo.entregaveis : [''],
              valor_total: d.custo?.valor_total || 0,
            },
            timeline: {
              duracao: d.timeline?.duracao || '',
              marcos: d.timeline?.marcos?.length ? d.timeline.marcos : [{ fase: '', descricao: '' }],
            },
            proximo_passo: d.proximo_passo || '',
            termos: d.termos?.length ? d.termos : [''],
          });
        }
      }
      setLoading(false);
    };
    carregar();
  }, [orcamentoId]);

  const updatePrestador = (field: string, value: string) => setDados((d) => ({ ...d, prestador: { ...d.prestador, [field]: value } }));
  const updateCusto = (field: string, value: any) => setDados((d) => ({ ...d, custo: { ...d.custo, [field]: value } }));
  const updateTimeline = (field: string, value: any) => setDados((d) => ({ ...d, timeline: { ...d.timeline, [field]: value } }));

  const addEntregavel = () => setDados((d) => ({ ...d, custo: { ...d.custo, entregaveis: [...d.custo.entregaveis, ''] } }));
  const removeEntregavel = (i: number) => setDados((d) => ({ ...d, custo: { ...d.custo, entregaveis: d.custo.entregaveis.filter((_, idx) => idx !== i) } }));
  const updateEntregavel = (i: number, val: string) => setDados((d) => ({ ...d, custo: { ...d.custo, entregaveis: d.custo.entregaveis.map((e, idx) => idx === i ? val : e) } }));

  const addMarco = () => setDados((d) => ({ ...d, timeline: { ...d.timeline, marcos: [...d.timeline.marcos, { fase: '', descricao: '' }] } }));
  const removeMarco = (i: number) => setDados((d) => ({ ...d, timeline: { ...d.timeline, marcos: d.timeline.marcos.filter((_, idx) => idx !== i) } }));
  const updateMarco = (i: number, field: string, val: string) => setDados((d) => ({ ...d, timeline: { ...d.timeline, marcos: d.timeline.marcos.map((m, idx) => idx === i ? { ...m, [field]: val } : m) } }));

  const addTermo = () => setDados((d) => ({ ...d, termos: [...d.termos, ''] }));
  const removeTermo = (i: number) => setDados((d) => ({ ...d, termos: d.termos.filter((_, idx) => idx !== i) }));
  const updateTermo = (i: number, val: string) => setDados((d) => ({ ...d, termos: d.termos.map((t, idx) => idx === i ? val : t) }));

  const salvar = async () => {
    if (!clienteNome.trim() || !titulo.trim()) {
      alert('Nome do cliente e titulo sao obrigatorios.');
      return;
    }
    setSaving(true);

    const payload = {
      ...(orcamentoId ? { id: orcamentoId } : {}),
      cliente_nome: clienteNome,
      cliente_empresa: clienteEmpresa,
      titulo,
      validade,
      valor_total: dados.custo.valor_total,
      status,
      dados_json: JSON.stringify({
        ...dados,
        cliente: { nome: clienteNome, empresa: clienteEmpresa },
      }, null, 2),
    };

    const endpoint = orcamentoId ? '/api/orcamentos-b2b/atualizar' : '/api/orcamentos-b2b/criar';

    try {
      const res = await safeFetchJson(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      if (res.data?.success) {
        alert(orcamentoId ? 'Orcamento atualizado!' : 'Orcamento criado!');
        onVoltar();
      } else {
        alert(res.data?.erro || 'Falha ao salvar.');
      }
    } catch (err) {
      alert('Erro ao conectar ao servidor.');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return <div className="bg-[#050505] text-white flex items-center justify-center min-h-screen rounded-2xl"><div className="text-zinc-400 text-sm">Carregando...</div></div>;
  }

  const inputCls = 'w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none focus:border-white/40';
  const inputClsSmall = 'w-full bg-black/40 border border-white/10 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-white/40';

  const SectionHeader = ({ label, icon, sectionKey }: { label: string; icon: string; sectionKey: string }) => (
    <button type="button" onClick={() => toggleSecao(sectionKey)} className="w-full flex items-center justify-between border-b border-white/5 pb-4">
      <div className="flex items-center gap-3">
        <div className="w-9 h-9 rounded-xl bg-[#c5a880]/20 text-[#c5a880] flex items-center justify-center font-bold text-sm">{icon}</div>
        <h2 className="text-lg font-bold text-white">{label}</h2>
      </div>
      {secoes[sectionKey] ? <ChevronUp className="w-5 h-5 text-zinc-400" /> : <ChevronDown className="w-5 h-5 text-zinc-400" />}
    </button>
  );

  return (
    <div className="bg-[#050505] text-white flex flex-col min-h-screen rounded-2xl overflow-hidden p-6 font-sans">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">{orcamentoId ? 'Editar Orcamento B2B' : 'Novo Orcamento B2B'}</h1>
          <p className="text-zinc-500 text-sm mt-1">Orcamento simples para clientes existentes</p>
        </div>
        <div className="flex items-center gap-3">
          {slug && (
            <a href={`/b2b/${slug}`} target="_blank" className="px-4 py-2.5 rounded-xl bg-purple-500/20 text-purple-300 border border-purple-500/30 hover:bg-purple-500/30 font-bold text-xs flex items-center gap-2">
              <ExternalLink className="w-4 h-4" /> Ver Link Publico
            </a>
          )}
          <button onClick={onVoltar} className="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs">Voltar</button>
        </div>
      </div>

      <div className="space-y-6 max-w-4xl">
        {/* Titulo do Orcamento */}
        <div className="bg-[#121212] border border-white/5 p-6 rounded-2xl space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Titulo do Orcamento *</label>
              <input type="text" value={titulo} onChange={(e) => setTitulo(e.target.value)} required placeholder="Ex: Cobertura Fotografica Casamento" className={inputCls} />
            </div>
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Data de Validade</label>
              <input type="date" value={validade} onChange={(e) => setValidade(e.target.value)} className={inputCls} />
            </div>
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Nome do Cliente *</label>
              <input type="text" value={clienteNome} onChange={(e) => setClienteNome(e.target.value)} required className={inputCls} />
            </div>
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Empresa do Cliente</label>
              <input type="text" value={clienteEmpresa} onChange={(e) => setClienteEmpresa(e.target.value)} className={inputCls} />
            </div>
          </div>
          {orcamentoId && (
            <div className="max-w-xs">
              <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Status</label>
              <select value={status} onChange={(e) => setStatus(e.target.value)} className={inputCls}>
                <option value="rascunho">Rascunho</option>
                <option value="pendente">Pendente</option>
                <option value="aprovado">Aprovado</option>
                <option value="recusado">Recusado</option>
              </select>
            </div>
          )}
        </div>

        {/* Prestador */}
        <div className="bg-[#121212] border border-white/5 p-6 rounded-2xl space-y-4">
          <SectionHeader label="Dados da Empresa (Prestador)" icon="4" sectionKey="prestador" />
          {secoes.prestador && (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-bold text-zinc-400 mb-1">Nome</label>
                <input type="text" value={dados.prestador.nome} onChange={(e) => updatePrestador('nome', e.target.value)} className={inputClsSmall} placeholder="Seu nome" />
              </div>
              <div>
                <label className="block text-xs font-bold text-zinc-400 mb-1">Empresa</label>
                <input type="text" value={dados.prestador.empresa} onChange={(e) => updatePrestador('empresa', e.target.value)} className={inputClsSmall} placeholder="Nome da empresa" />
              </div>
              <div>
                <label className="block text-xs font-bold text-zinc-400 mb-1">Email</label>
                <input type="email" value={dados.prestador.email} onChange={(e) => updatePrestador('email', e.target.value)} className={inputClsSmall} placeholder="contato@empresa.com" />
              </div>
              <div>
                <label className="block text-xs font-bold text-zinc-400 mb-1">Website</label>
                <input type="text" value={dados.prestador.website} onChange={(e) => updatePrestador('website', e.target.value)} className={inputClsSmall} placeholder="empresa.com" />
              </div>
            </div>
          )}
        </div>

        {/* Overview */}
        <div className="bg-[#121212] border border-white/5 p-6 rounded-2xl space-y-4">
          <SectionHeader label="Sobre o Projeto (Overview)" icon="1" sectionKey="overview" />
          {secoes.overview && (
            <textarea value={dados.overview} onChange={(e) => setDados((d) => ({ ...d, overview: e.target.value }))} rows={4}
              className={inputCls} placeholder="Descricao resumida do objetivo do projeto..." />
          )}
        </div>

        {/* Custo */}
        <div className="bg-[#121212] border border-white/5 p-6 rounded-2xl space-y-4">
          <SectionHeader label="Custo / Investimento" icon="2" sectionKey="custo" />
          {secoes.custo && (
            <>
              <div>
                <label className="block text-xs font-bold text-zinc-400 mb-1">Descricao do Pacote</label>
                <input type="text" value={dados.custo.descricao} onChange={(e) => updateCusto('descricao', e.target.value)} className={inputClsSmall} placeholder="Ex: Pacote completo de fotografia" />
              </div>

              <div>
                <label className="block text-xs font-bold text-zinc-400 mb-2">Entregaveis</label>
                <div className="space-y-2">
                  {dados.custo.entregaveis.map((item, i) => (
                    <div key={i} className="flex items-center gap-2">
                      <span className="text-[#c5a880] text-xs font-bold">✓</span>
                      <input type="text" value={item} onChange={(e) => updateEntregavel(i, e.target.value)} className={`${inputClsSmall} flex-1`} placeholder="Item entregavel" />
                      <button type="button" onClick={() => removeEntregavel(i)} className="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition-colors"><XCircle className="w-4 h-4" /></button>
                    </div>
                  ))}
                </div>
                <button type="button" onClick={addEntregavel} className="mt-2 text-xs text-[#c5a880] font-bold hover:underline flex items-center gap-1">
                  <Plus className="w-3 h-3" /> Adicionar entregavel
                </button>
              </div>

              <div className="max-w-xs">
                <label className="block text-xs font-bold text-zinc-400 mb-1">Valor Total (R$)</label>
                <input type="number" step="0.01" value={dados.custo.valor_total} onChange={(e) => updateCusto('valor_total', parseFloat(e.target.value) || 0)} className={`${inputClsSmall} font-bold`} />
              </div>
            </>
          )}
        </div>

        {/* Timeline */}
        <div className="bg-[#121212] border border-white/5 p-6 rounded-2xl space-y-4">
          <SectionHeader label="Cronograma (Timeline)" icon="3" sectionKey="timeline" />
          {secoes.timeline && (
            <>
              <div>
                <label className="block text-xs font-bold text-zinc-400 mb-1">Duracao Estimada</label>
                <input type="text" value={dados.timeline.duracao} onChange={(e) => updateTimeline('duracao', e.target.value)} className={inputClsSmall} placeholder="Ex: 4 semanas (15/09 a 15/10)" />
              </div>

              <div>
                <label className="block text-xs font-bold text-zinc-400 mb-2">Marcos / Fases</label>
                <div className="space-y-3">
                  {dados.timeline.marcos.map((marco, i) => (
                    <div key={i} className="flex items-start gap-2">
                      <GripVertical className="w-4 h-4 text-zinc-600 mt-2.5 shrink-0" />
                      <div className="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <input type="text" value={marco.fase} onChange={(e) => updateMarco(i, 'fase', e.target.value)} className={inputClsSmall} placeholder="Fase (ex: Semana 1-2)" />
                        <input type="text" value={marco.descricao} onChange={(e) => updateMarco(i, 'descricao', e.target.value)} className={inputClsSmall} placeholder="Descricao da atividade" />
                      </div>
                      <button type="button" onClick={() => removeMarco(i)} className="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition-colors mt-0.5"><XCircle className="w-4 h-4" /></button>
                    </div>
                  ))}
                </div>
                <button type="button" onClick={addMarco} className="mt-2 text-xs text-[#c5a880] font-bold hover:underline flex items-center gap-1">
                  <Plus className="w-3 h-3" /> Adicionar fase
                </button>
              </div>
            </>
          )}
        </div>

        {/* Proximo Passo */}
        <div className="bg-[#121212] border border-white/5 p-6 rounded-2xl space-y-4">
          <SectionHeader label="Proximo Passo (What's Next)" icon="5" sectionKey="proximo" />
          {secoes.proximo && (
            <textarea value={dados.proximo_passo} onChange={(e) => setDados((d) => ({ ...d, proximo_passo: e.target.value }))} rows={3}
              className={inputCls} placeholder="Ex: Apos aprovacao, enviaremos o contrato para assinatura..." />
          )}
        </div>

        {/* Termos */}
        <div className="bg-[#121212] border border-white/5 p-6 rounded-2xl space-y-4">
          <SectionHeader label="Termos e Condicoes" icon="6" sectionKey="termos" />
          {secoes.termos && (
            <>
              <div className="space-y-2">
                {dados.termos.map((termo, i) => (
                  <div key={i} className="flex items-center gap-2">
                    <span className="text-zinc-500 text-xs font-bold w-6 text-right">{i + 1}.</span>
                    <input type="text" value={termo} onChange={(e) => updateTermo(i, e.target.value)} className={`${inputClsSmall} flex-1`} placeholder="Condicao ou termo" />
                    <button type="button" onClick={() => removeTermo(i)} className="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition-colors"><XCircle className="w-4 h-4" /></button>
                  </div>
                ))}
              </div>
              <button type="button" onClick={addTermo} className="mt-2 text-xs text-[#c5a880] font-bold hover:underline flex items-center gap-1">
                <Plus className="w-3 h-3" /> Adicionar termo
              </button>
            </>
          )}
        </div>

        {/* Botoes */}
        <div className="flex items-center justify-end gap-4 pt-4 pb-8">
          <button onClick={onVoltar} className="px-6 py-3.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs">Cancelar</button>
          <button onClick={salvar} disabled={saving}
            className="px-8 py-3.5 rounded-xl bg-[#c5a880] hover:bg-[#d4b78f] text-black font-extrabold text-xs uppercase tracking-wider shadow-xl flex items-center gap-2 disabled:opacity-50">
            <CheckCircle2 className="w-4 h-4" />
            {saving ? 'Salvando...' : orcamentoId ? 'Salvar Alteracoes' : 'Criar Orcamento'}
          </button>
        </div>
      </div>
    </div>
  );
};
