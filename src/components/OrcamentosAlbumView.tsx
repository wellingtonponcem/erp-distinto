import React, { useEffect, useState, useCallback } from 'react';
import {
  Search, Plus, ExternalLink, Copy, MessageCircle, Edit3, Trash2,
  CheckCircle2, XCircle, Clock, Eye, RefreshCw,
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

interface OrcamentoAlbum {
  id: string;
  cliente_nome: string;
  tipo: string;
  slug: string;
  titulo: string;
  subtitulo: string;
  validade: string | null;
  valor_total: number;
  status: string;
  created_at: string;
  dados: any;
}

export const OrcamentosAlbumView: React.FC = () => {
  const [items, setItems] = useState<OrcamentoAlbum[]>([]);
  const [loading, setLoading] = useState(true);
  const [busca, setBusca] = useState('');
  const [filtroStatus, setFiltroStatus] = useState('');
  const [whatsappLoading, setWhatsappLoading] = useState<string | null>(null);
  const [showModalWhatsApp, setShowModalWhatsApp] = useState(false);
  const [mensagemWhatsApp, setMensagemWhatsApp] = useState('');
  const [showModalExcluir, setShowModalExcluir] = useState<OrcamentoAlbum | null>(null);

  // Modo criação/edição
  const [modo, setModo] = useState<'lista' | 'novo' | 'editar'>('lista');
  const [editandoId, setEditandoId] = useState<string | null>(null);

  const carregar = useCallback(async () => {
    setLoading(true);
    const params = new URLSearchParams();
    if (filtroStatus) params.set('status', filtroStatus);
    if (busca) params.set('q', busca);

    const res = await safeFetchJson(`/api/orcamentos-album/listar?${params.toString()}`);
    if (res.ok && Array.isArray(res.data)) {
      setItems(res.data);
    }
    setLoading(false);
  }, [filtroStatus, busca]);

  useEffect(() => {
    carregar();
  }, [carregar]);

  const copiarLink = (slug: string) => {
    const link = `${window.location.origin}/o/${slug}`;
    navigator.clipboard.writeText(link).then(() => alert('Link copiado!'));
  };

  const gerarWhatsApp = async (id: string) => {
    setWhatsappLoading(id);
    setShowModalWhatsApp(true);
    setMensagemWhatsApp('');

    try {
      const res = await safeFetchJson('/api/orcamentos-album/mensagem-whatsapp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id }),
      });
      if (res.data?.mensagem) {
        setMensagemWhatsApp(res.data.mensagem);
      } else {
        setMensagemWhatsApp(res.data?.erro || 'Falha ao gerar mensagem.');
        setShowModalWhatsApp(false);
      }
    } catch (err) {
      setMensagemWhatsApp('Erro ao conectar à API.');
      setShowModalWhatsApp(false);
    } finally {
      setWhatsappLoading(null);
    }
  };

  const copiarMensagem = () => {
    navigator.clipboard.writeText(mensagemWhatsApp).then(() => alert('Mensagem copiada!'));
  };

  const abrirWhatsAppLink = () => {
    window.open(`https://wa.me/?text=${encodeURIComponent(mensagemWhatsApp)}`, '_blank');
  };

  const excluir = async (item: OrcamentoAlbum) => {
    await safeFetchJson('/api/orcamentos-album/excluir', {
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
    if (Number.isNaN(d.getTime())) return iso;
    return d.toLocaleDateString('pt-BR');
  };

  const formatarValor = (v: number) => {
    return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };

  const statusBadge = (status: string) => {
    if (status === 'aprovado') return { label: 'Aprovado', cls: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' };
    if (status === 'recusado') return { label: 'Recusado', cls: 'bg-rose-500/20 text-rose-400 border-rose-500/30' };
    return { label: 'Pendente', cls: 'bg-amber-500/20 text-amber-400 border-amber-500/30' };
  };

  const vencido = (validade?: string | null) => {
    if (!validade) return false;
    return validade < new Date().toISOString().slice(0, 10);
  };

  // Se está em modo criação ou edição, renderiza o construtor
  if (modo === 'novo' || modo === 'editar') {
    return (
      <OrcamentoAlbumForm
        orcamentoId={editandoId}
        onVoltar={() => { setModo('lista'); setEditandoId(null); carregar(); }}
      />
    );
  }

  return (
    <div className="bg-[#050505] text-white flex flex-col min-h-screen rounded-2xl overflow-hidden p-6 font-sans">
      <style>{`
        .album-card { background: #121212; border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 18px; transition: all 0.2s; }
        .album-card:hover { background: #171717; border-color: rgba(255,255,255,0.12); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #333; border-radius: 8px; }
      `}</style>

      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Orçamentos Comercial</h1>
          <p className="text-zinc-500 text-sm mt-1">Gerencie e envie orçamentos de álbuns interativos para seus clientes</p>
        </div>

        <button
          onClick={() => setModo('novo')}
          className="flex items-center gap-2 bg-[#c5a880] text-black px-5 py-2.5 rounded-full text-xs font-bold hover:bg-[#d4b78f] transition-all shadow-md"
        >
          <Plus className="w-4 h-4" />
          Novo Orçamento
        </button>
      </div>

      {/* Filtros */}
      <div className="album-card flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
        <div className="flex flex-wrap items-center gap-3 w-full sm:w-auto">
          <div className="relative min-w-[260px] flex-1">
            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500" />
            <input
              type="text"
              value={busca}
              onChange={(e) => setBusca(e.target.value)}
              placeholder="Buscar por cliente, título..."
              className="w-full pl-10 pr-4 py-2.5 bg-black/40 border border-white/10 rounded-xl text-sm text-white outline-none focus:border-white/40"
            />
          </div>

          <select
            value={filtroStatus}
            onChange={(e) => setFiltroStatus(e.target.value)}
            className="bg-black/40 border border-white/10 text-white text-sm rounded-xl px-3 py-2.5 outline-none focus:border-white/40"
          >
            <option value="">Todos os Status</option>
            <option value="pendente">Pendentes</option>
            <option value="aprovado">Aprovados</option>
            <option value="recusado">Recusados</option>
          </select>

          {(busca || filtroStatus) && (
            <button onClick={() => { setBusca(''); setFiltroStatus(''); }} className="text-xs text-zinc-500 hover:text-white">
              Limpar
            </button>
          )}
        </div>

        <div className="text-xs text-zinc-500">
          Total: <strong className="text-white">{items.length}</strong> orçamentos
        </div>
      </div>

      {/* Tabela */}
      <div className="bg-[#121212] rounded-2xl overflow-hidden border border-white/5">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-white/10 text-[11px] font-bold uppercase tracking-wider text-zinc-500">
                <th className="p-4">Orçamento / Título</th>
                <th className="p-4">Cliente</th>
                <th className="p-4">Tipo</th>
                <th className="p-4">Validade</th>
                <th className="p-4">Investimento</th>
                <th className="p-4">Status</th>
                <th className="p-4 text-right">Ações</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/5 text-sm">
              {loading ? (
                <tr>
                  <td colSpan={7} className="p-8 text-center text-zinc-500">Carregando...</td>
                </tr>
              ) : items.length === 0 ? (
                <tr>
                  <td colSpan={7} className="p-8 text-center text-zinc-500">
                    Nenhum orçamento encontrado.{' '}
                    <button onClick={() => setModo('novo')} className="text-[#c5a880] font-bold hover:underline">
                      Criar o primeiro orçamento
                    </button>
                  </td>
                </tr>
              ) : (
                items.map((o) => {
                  const st = statusBadge(o.status);
                  const isVencido = vencido(o.validade);
                  return (
                    <tr key={o.id} className="hover:bg-white/[0.02] transition-colors">
                      <td className="p-4">
                        <div className="font-bold text-white">{o.titulo}</div>
                        <a
                          href={`/o/${o.slug}`}
                          target="_blank"
                          className="text-xs text-[#c5a880]/80 hover:text-[#c5a880] font-mono flex items-center gap-1 mt-0.5"
                        >
                          /o/{o.slug}
                          <ExternalLink className="w-3 h-3" />
                        </a>
                      </td>

                      <td className="p-4 font-semibold text-white">{o.cliente_nome}</td>

                      <td className="p-4">
                        <span className="px-2.5 py-1 rounded-lg bg-white/5 text-xs font-medium text-zinc-300 capitalize">
                          {o.tipo.replace(/_/g, ' ')}
                        </span>
                      </td>

                      <td className="p-4 text-xs font-medium text-zinc-400">
                        {o.validade ? (
                          <span className={isVencido ? 'text-rose-400 font-bold' : ''}>
                            {formatarData(o.validade)}
                            {isVencido ? ' (Expirado)' : ''}
                          </span>
                        ) : '--'}
                      </td>

                      <td className="p-4 font-bold text-white">
                        R$ {formatarValor(o.valor_total)}
                      </td>

                      <td className="p-4">
                        <span className={`px-2.5 py-1 rounded-full text-xs font-bold uppercase border ${st.cls}`}>
                          {st.label}
                        </span>
                      </td>

                      <td className="p-4 text-right">
                        <div className="flex items-center justify-end gap-1.5">
                          <button
                            onClick={() => window.open(`/o/${o.slug}`, '_blank')}
                            title="Ver Link Público"
                            className="p-2 rounded-lg bg-white/5 hover:bg-white/10 text-white transition-colors"
                          >
                            <Eye className="w-4 h-4" />
                          </button>

                          <button
                            onClick={() => copiarLink(o.slug)}
                            title="Copiar Link"
                            className="p-2 rounded-lg bg-white/5 hover:bg-white/10 text-white transition-colors"
                          >
                            <Copy className="w-4 h-4" />
                          </button>

                          <button
                            onClick={() => gerarWhatsApp(o.id)}
                            disabled={whatsappLoading === o.id}
                            title="Gerar Mensagem WhatsApp"
                            className="p-2 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-400 transition-colors disabled:opacity-50"
                          >
                            <MessageCircle className="w-4 h-4" />
                          </button>

                          <button
                            onClick={() => { setEditandoId(o.id); setModo('editar'); }}
                            title="Editar"
                            className="p-2 rounded-lg bg-white/5 hover:bg-white/10 text-white transition-colors"
                          >
                            <Edit3 className="w-4 h-4" />
                          </button>

                          <button
                            onClick={() => setShowModalExcluir(o)}
                            title="Excluir"
                            className="p-2 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition-colors"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal WhatsApp */}
      {showModalWhatsApp && (
        <div className="fixed inset-0 z-[2000] flex items-center justify-center p-6 bg-black/80 backdrop-blur-md" onClick={() => setShowModalWhatsApp(false)}>
          <div className="bg-[#0c0c0c] border border-white/10 w-full max-w-lg rounded-3xl p-6 space-y-4 relative" onClick={(e) => e.stopPropagation()}>
            <button onClick={() => setShowModalWhatsApp(false)} className="absolute top-5 right-5 text-zinc-400 hover:text-white">
              <XCircle className="w-6 h-6" />
            </button>

            <h3 className="text-lg font-bold text-white flex items-center gap-2">
              <MessageCircle className="w-5 h-5 text-emerald-400" />
              Mensagem de Envio do Orçamento
            </h3>

            {!mensagemWhatsApp ? (
              <div className="py-8 text-center text-zinc-400 font-semibold text-sm">
                Gerando mensagem com IA...
              </div>
            ) : (
              <>
                <textarea
                  value={mensagemWhatsApp}
                  onChange={(e) => setMensagemWhatsApp(e.target.value)}
                  rows={7}
                  className="w-full bg-black/40 border border-white/10 rounded-xl p-3 text-sm text-white outline-none focus:border-white/40"
                />

                <div className="flex items-center justify-end gap-3">
                  <button onClick={copiarMensagem} className="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs">
                    Copiar Texto
                  </button>
                  <button onClick={abrirWhatsAppLink} className="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs uppercase flex items-center gap-2">
                    <MessageCircle className="w-4 h-4" />
                    Abrir no WhatsApp
                  </button>
                </div>
              </>
            )}
          </div>
        </div>
      )}

      {/* Modal Excluir */}
      {showModalExcluir && (
        <div className="fixed inset-0 z-[2100] flex items-center justify-center p-6 bg-black/80 backdrop-blur-md" onClick={() => setShowModalExcluir(null)}>
          <div className="bg-[#0c0c0c] border border-white/10 rounded-2xl max-w-sm w-full p-6" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center gap-3 mb-4">
              <div className="w-10 h-10 rounded-xl bg-rose-500/10 flex items-center justify-center">
                <Trash2 className="w-5 h-5 text-rose-400" />
              </div>
              <h3 className="text-base font-bold text-white">Excluir orçamento</h3>
            </div>
            <p className="text-sm text-zinc-400 mb-6">
              Tem certeza que deseja excluir o orçamento de <strong className="text-white">{showModalExcluir.cliente_nome}</strong>? Esta ação não pode ser desfeita.
            </p>
            <div className="flex gap-3 justify-end">
              <button onClick={() => setShowModalExcluir(null)} className="px-4 py-2 rounded-full text-xs font-bold text-zinc-400 hover:text-white transition">
                Cancelar
              </button>
              <button onClick={() => excluir(showModalExcluir)} className="px-4 py-2 rounded-full text-xs font-bold bg-rose-500 text-white hover:bg-rose-400 transition">
                Excluir
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

// ============================================================
// Componente de Formulário (Novo / Editar)
// ============================================================
const OrcamentoAlbumForm: React.FC<{
  orcamentoId: string | null;
  onVoltar: () => void;
}> = ({ orcamentoId, onVoltar }) => {
  const [loading, setLoading] = useState(!!orcamentoId);
  const [saving, setSaving] = useState(false);
  const [servicos, setServicos] = useState<any[]>([]);

  const [clienteNome, setClienteNome] = useState('');
  const [titulo, setTitulo] = useState('Orçamento de Álbuns Premium — 15 Anos');
  const [subtitulo, setSubtitulo] = useState('15 Anos - Debutante Premium (Vitória/ES)');
  const [categoriaFiltro, setCategoriaFiltro] = useState('15anos');
  const [validade, setValidade] = useState(() => {
    const d = new Date();
    d.setDate(d.getDate() + 30);
    return d.toISOString().slice(0, 10);
  });
  const [valorTotal, setValorTotal] = useState('1250.00');
  const [status, setStatus] = useState('pendente');

  const [specTamanhoFechado, setSpecTamanhoFechado] = useState('30x30 cm');
  const [specTamanhoAberto, setSpecTamanhoAberto] = useState('30x60 cm');
  const [specAbertura, setSpecAbertura] = useState('Panorâmica 180° (Lâminas Rígidas de 800g)');
  const [specRetirada, setSpecRetirada] = useState('Presencial (Vitória/ES)');
  const [specServicosInclusos, setSpecServicosInclusos] = useState('Diagramação Profissional Personalizada, Curadoria de Acabamentos Temáticos, Tratamento de Imagem para Impressão Premium, Garantia de Fidelidade de Cor e Durabilidade');

  const [colecoesSelecionadas, setColecoesSelecionadas] = useState<string[]>([]);
  const [slug, setSlug] = useState('');

  // Buscar serviços (catálogo de coleções)
  useEffect(() => {
    const carregarServicos = async () => {
      const res = await safeFetchJson('/api/orcamentos-album/servicos');
      if (res.ok && Array.isArray(res.data)) {
        setServicos(res.data);
      }
    };
    carregarServicos();
  }, []);

  // Buscar dados do orçamento se editando
  useEffect(() => {
    if (!orcamentoId) return;
    const carregar = async () => {
      setLoading(true);
      const res = await safeFetchJson(`/api/orcamentos-album/listar`);
      if (res.ok && Array.isArray(res.data)) {
        const orc = res.data.find((o: any) => o.id === orcamentoId);
        if (orc) {
          setClienteNome(orc.cliente_nome);
          setTitulo(orc.titulo);
          setSubtitulo(orc.subtitulo || '');
          setCategoriaFiltro(orc.tipo || '15anos');
          setValidade(orc.validade || '');
          setValorTotal(String(orc.valor_total || 0));
          setStatus(orc.status || 'pendente');
          setSlug(orc.slug || '');

          const dados = orc.dados || {};
          const config = dados.configuracao_geral || {};
          setSpecTamanhoFechado(config.tamanho_fechado || '30x30 cm');
          setSpecTamanhoAberto(config.tamanho_aberto || '30x60 cm');
          setSpecAbertura(config.abertura || 'Panorâmica 180° (Lâminas Rígidas de 800g)');
          setSpecRetirada(config.retirada || 'Presencial (Vitória/ES)');
          setSpecServicosInclusos((config.servicos_inclusos || []).join(', '));

          const colecoes = (dados.colecao_albuns || []).map((c: any) => c.id || c.nome_comercial);
          setColecoesSelecionadas(colecoes);
        }
      }
      setLoading(false);
    };
    carregar();
  }, [orcamentoId]);

  const parseJsonSeguro = (str: any): any => {
    if (!str) return null;
    try { return typeof str === 'object' ? str : JSON.parse(str); } catch (e) { return null; }
  };

  const servicosFiltrados = servicos.filter((s: any) => {
    const cat = categoriaFiltro;
    if (cat === 'todos') return true;
    return s.categoria === cat;
  });

  const colecoes = servicosFiltrados.filter((s: any) => s.tipo === 'colecao' || s.tipo === 'plano');

  const toggleColecao = (id: string) => {
    setColecoesSelecionadas((prev) =>
      prev.includes(id) ? prev.filter((c) => c !== id) : [...prev, id]
    );
  };

  const compilarJson = (): any => {
    const servicosInclusos = specServicosInclusos.split(',').map((s) => s.trim()).filter(Boolean);

    const colecoesCompiladas = colecoesSelecionadas.map((id) => {
      const srv = servicos.find((s: any) => s.id === id);
      if (!srv) return null;

      const acabArray = parseJsonSeguro(srv.acabamento_json) || [];
      const estojo = parseJsonSeguro(srv.estojo_json) || {};
      const imagens = parseJsonSeguro(srv.imagens_json) || {};

      return {
        id: srv.id,
        nome_comercial: srv.nome,
        categoria_original: srv.categoria_original || 'Coleção Premium',
        descricao: srv.descricao || '',
        acabamento_detalhado: acabArray,
        estojo,
        custo_base_fullcolor: parseFloat(srv.custo_producao || 445),
        investimento_cliente: parseFloat(srv.preco_venda || 1250),
        valor_lamina_extra: parseFloat(srv.valor_lamina_extra || 35),
        imagens,
      };
    }).filter(Boolean);

    return {
      evento: subtitulo || titulo,
      localidade: 'Vitória/ES',
      data_geracao: new Date().toISOString().slice(0, 10),
      configuracao_geral: {
        tamanho_fechado: specTamanhoFechado,
        tamanho_aberto: specTamanhoAberto,
        abertura: specAbertura,
        paginas_base: 20,
        retirada: specRetirada,
        servicos_inclusos: servicosInclusos,
      },
      colecao_albuns: colecoesCompiladas,
      galeria_acabamentos: [
        { item: 'Papel Linho Silk', descricao: 'Textura acetinada anti-digital.', imagem_exemplo: '' },
        { item: 'Abertura Panorâmica 180°', descricao: 'Abertura total 180° sem vinco central.', imagem_exemplo: '' },
        { item: 'Corte Lateral Ouro', descricao: 'Bordas douradas metálicas reluzentes.', imagem_exemplo: '' },
      ],
    };
  };

  const salvar = async () => {
    if (!clienteNome.trim() || !titulo.trim()) {
      alert('Nome do cliente e título são obrigatórios.');
      return;
    }

    setSaving(true);
    const dadosJson = compilarJson();

    const payload = {
      ...(orcamentoId ? { id: orcamentoId } : {}),
      cliente_nome: clienteNome,
      titulo,
      subtitulo,
      tipo: categoriaFiltro,
      validade,
      valor_total: parseFloat(valorTotal) || 0,
      status,
      dados_json: JSON.stringify(dadosJson, null, 2),
    };

    const endpoint = orcamentoId ? '/api/orcamentos-album/atualizar' : '/api/orcamentos-album/criar';

    try {
      const res = await safeFetchJson(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });

      if (res.data?.success) {
        alert(orcamentoId ? 'Orçamento atualizado com sucesso!' : 'Orçamento criado com sucesso!');
        onVoltar();
      } else {
        alert(res.data?.erro || 'Falha ao salvar orçamento.');
      }
    } catch (err) {
      alert('Erro ao conectar ao servidor.');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="bg-[#050505] text-white flex items-center justify-center min-h-screen rounded-2xl">
        <div className="text-zinc-400 text-sm">Carregando...</div>
      </div>
    );
  }

  const inputCls = 'w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none focus:border-white/40';
  const inputClsSmall = 'w-full bg-black/40 border border-white/10 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-white/40';

  return (
    <div className="bg-[#050505] text-white flex flex-col min-h-screen rounded-2xl overflow-hidden p-6 font-sans">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">{orcamentoId ? 'Editar Orçamento' : 'Construtor de Orçamento de Álbuns'}</h1>
          <p className="text-zinc-500 text-sm mt-1">Selecione os produtos de álbuns e acabamentos do catálogo</p>
        </div>

        <div className="flex items-center gap-3">
          {slug && (
            <a href={`/o/${slug}`} target="_blank" className="px-4 py-2.5 rounded-xl bg-purple-500/20 text-purple-300 border border-purple-500/30 hover:bg-purple-500/30 font-bold text-xs flex items-center gap-2">
              <ExternalLink className="w-4 h-4" />
              Ver Link Público
            </a>
          )}
          <button onClick={onVoltar} className="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs flex items-center gap-2">
            Voltar
          </button>
        </div>
      </div>

      <div className="space-y-8">
        {/* 1. Dados Básicos */}
        <div className="bg-[#121212] border border-white/5 p-6 rounded-2xl space-y-6">
          <div className="flex items-center gap-3 border-b border-white/5 pb-4">
            <div className="w-9 h-9 rounded-xl bg-[#c5a880]/20 text-[#c5a880] flex items-center justify-center font-bold text-sm">1</div>
            <div>
              <h2 className="text-lg font-bold text-white">Informações do Cliente e Projeto</h2>
              <p className="text-xs text-zinc-500">Identificação do cliente e prazos de validade</p>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Nome do Cliente / Projeto *</label>
              <input type="text" value={clienteNome} onChange={(e) => setClienteNome(e.target.value)} required placeholder="Ex: Debutante Premium / Maria Silva" className={inputCls} />
            </div>
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Título do Orçamento *</label>
              <input type="text" value={titulo} onChange={(e) => setTitulo(e.target.value)} required className={inputCls} />
            </div>
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Subtítulo / Localidade</label>
              <input type="text" value={subtitulo} onChange={(e) => setSubtitulo(e.target.value)} className={inputCls} />
            </div>
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Categoria</label>
              <select value={categoriaFiltro} onChange={(e) => setCategoriaFiltro(e.target.value)} className={inputCls}>
                <option value="todos">Todas as Categorias</option>
                <option value="casamento">Casamento</option>
                <option value="15anos">15 Anos</option>
                <option value="familia">Família</option>
                <option value="book">Book Fotográfico</option>
              </select>
            </div>
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Data de Validade</label>
              <input type="date" value={validade} onChange={(e) => setValidade(e.target.value)} className={inputCls} />
            </div>
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Valor Base (R$)</label>
              <input type="number" step="0.01" value={valorTotal} onChange={(e) => setValorTotal(e.target.value)} className={`${inputCls} font-bold`} />
            </div>
            {orcamentoId && (
              <div>
                <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Status</label>
                <select value={status} onChange={(e) => setStatus(e.target.value)} className={inputCls}>
                  <option value="pendente">Pendente</option>
                  <option value="aprovado">Aprovado</option>
                  <option value="recusado">Recusado</option>
                </select>
              </div>
            )}
          </div>
        </div>

        {/* 2. Especificações Técnicas */}
        <div className="bg-[#121212] border border-white/5 p-6 rounded-2xl space-y-6">
          <div className="flex items-center gap-3 border-b border-white/5 pb-4">
            <div className="w-9 h-9 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center font-bold text-sm">2</div>
            <div>
              <h2 className="text-lg font-bold text-white">Especificações Técnicas da Linha</h2>
              <p className="text-xs text-zinc-500">Formatos, encadernação e serviços inclusos</p>
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <label className="block text-xs font-bold text-zinc-400 mb-1">Tamanho Fechado</label>
              <input type="text" value={specTamanhoFechado} onChange={(e) => setSpecTamanhoFechado(e.target.value)} className={inputClsSmall} />
            </div>
            <div>
              <label className="block text-xs font-bold text-zinc-400 mb-1">Tamanho Aberto</label>
              <input type="text" value={specTamanhoAberto} onChange={(e) => setSpecTamanhoAberto(e.target.value)} className={inputClsSmall} />
            </div>
            <div>
              <label className="block text-xs font-bold text-zinc-400 mb-1">Encadernação / Abertura</label>
              <input type="text" value={specAbertura} onChange={(e) => setSpecAbertura(e.target.value)} className={inputClsSmall} />
            </div>
            <div>
              <label className="block text-xs font-bold text-zinc-400 mb-1">Retirada / Entrega</label>
              <input type="text" value={specRetirada} onChange={(e) => setSpecRetirada(e.target.value)} className={inputClsSmall} />
            </div>
          </div>

          <div>
            <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Serviços Premium Inclusos (separados por vírgula)</label>
            <input type="text" value={specServicosInclusos} onChange={(e) => setSpecServicosInclusos(e.target.value)} className={inputCls} />
          </div>
        </div>

        {/* 3. Coleções */}
        <div className="bg-[#121212] border border-white/5 p-6 rounded-2xl space-y-6">
          <div className="flex items-center justify-between border-b border-white/5 pb-4">
            <div className="flex items-center gap-3">
              <div className="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-sm">3</div>
              <div>
                <h2 className="text-lg font-bold text-white">Produtos & Coleções de Álbuns</h2>
                <p className="text-xs text-zinc-500">Selecione quais produtos estarão disponíveis no orçamento</p>
              </div>
            </div>
          </div>

          {colecoes.length === 0 ? (
            <div className="text-center py-8 text-zinc-500 text-sm">
              Nenhuma coleção encontrada para esta categoria.
              <p className="text-xs text-zinc-600 mt-1">Cadastre coleções na Tabela de Preços primeiro.</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {colecoes.map((srv: any) => {
                const isSelected = colecoesSelecionadas.includes(srv.id);
                const imagens = parseJsonSeguro(srv.imagens_json) || {};
                const estojo = parseJsonSeguro(srv.estojo_json) || {};
                const fotoCapa = imagens.capa || estojo.imagem_referencia || '';

                return (
                  <div
                    key={srv.id}
                    className={`bg-[#181818] p-5 rounded-2xl border-2 relative flex flex-col justify-between space-y-4 transition-all cursor-pointer ${
                      isSelected ? 'border-[#c5a880]' : 'border-white/5 hover:border-white/20'
                    }`}
                    onClick={() => toggleColecao(srv.id)}
                  >
                    <div className="flex items-start justify-between">
                      <label className="flex items-center gap-2.5 cursor-pointer">
                        <input
                          type="checkbox"
                          checked={isSelected}
                          onChange={() => toggleColecao(srv.id)}
                          className="w-4 h-4 rounded text-[#c5a880]"
                        />
                        <span className="font-bold text-sm text-white">{srv.nome}</span>
                      </label>
                      <span className="px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 font-bold text-[10px] uppercase">
                        {srv.categoria_original || 'Produto'}
                      </span>
                    </div>

                    {fotoCapa && (
                      <div className="w-full h-36 rounded-xl overflow-hidden bg-zinc-900 relative">
                        <img src={fotoCapa} alt={srv.nome} className="w-full h-full object-cover" />
                      </div>
                    )}

                    <p className="text-xs text-zinc-400 leading-relaxed line-clamp-2">{srv.descricao || ''}</p>

                    <div className="space-y-1 text-xs bg-black/40 p-3 rounded-xl border border-white/5">
                      <div className="flex justify-between font-bold text-white">
                        <span>Investimento:</span>
                        <span className="text-[#c5a880]">R$ {parseFloat(srv.preco_venda || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
                      </div>
                      <div className="flex justify-between text-zinc-400">
                        <span>Lâmina Extra:</span>
                        <span>R$ {parseFloat(srv.valor_lamina_extra || 35).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>

        {/* Botões */}
        <div className="flex items-center justify-end gap-4 pt-4">
          <button onClick={onVoltar} className="px-6 py-3.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs">
            Cancelar
          </button>
          <button
            onClick={salvar}
            disabled={saving}
            className="px-8 py-3.5 rounded-xl bg-[#c5a880] hover:bg-[#d4b78f] text-black font-extrabold text-xs uppercase tracking-wider shadow-xl flex items-center gap-2 disabled:opacity-50"
          >
            <CheckCircle2 className="w-4 h-4" />
            {saving ? 'Salvando...' : orcamentoId ? 'Salvar Alterações' : 'Gerar & Salvar Orçamento'}
          </button>
        </div>
      </div>
    </div>
  );
};
