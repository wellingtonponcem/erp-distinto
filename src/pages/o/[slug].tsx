import React, { useEffect, useState, useRef } from 'react';
import { useRouter } from 'next/router';
import {
  Sparkles, User, Calendar, CheckCircle2, Crown, Layers, FileText,
  PackageCheck, Image, Box, ShoppingBag, MessageCircle, X, Printer,
  Share2, CircleDot, Check, Maximize2, BookOpen,
} from 'lucide-react';

interface Colecao {
  id: string;
  nome_comercial: string;
  categoria_original: string;
  descricao: string;
  acabamento_detalhado: Record<string, string>;
  acabamentos_lista_fotos: any[];
  estojo: any;
  custo_base_fullcolor: number;
  investimento_cliente: number;
  valor_lamina_extra: number;
  imagens: any;
  acabamento_json?: any;
  estojo_json?: any;
  imagens_json?: any;
}

interface OrcamentoData {
  orcamento: {
    id: string;
    cliente_nome: string;
    titulo: string;
    subtitulo: string;
    tipo: string;
    slug: string;
    validade: string | null;
    valor_total: number;
    status: string;
    created_at: string;
  };
  dados: {
    configuracao_geral: any;
    colecao_albuns: Colecao[];
    galeria_acabamentos: any[];
    aprovacao?: any;
  };
  whatsapp_empresa: string;
}

export default function OrcamentoPublicoPage() {
  const router = useRouter();
  const { slug } = router.query;

  const [data, setData] = useState<OrcamentoData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [colecaoSelecionada, setColecaoSelecionada] = useState<Colecao | null>(null);
  const [laminasExtras, setLaminasExtras] = useState(0);
  const [showModalAprovacao, setShowModalAprovacao] = useState(false);
  const [showModalFoto, setShowModalFoto] = useState<{ src: string; titulo: string } | null>(null);
  const [aprovando, setAprovando] = useState(false);

  // Formulário de aprovação
  const [apNome, setApNome] = useState('');
  const [apTelefone, setApTelefone] = useState('');
  const [apObs, setApObs] = useState('');

  useEffect(() => {
    if (!slug) return;
    const carregar = async () => {
      try {
        const res = await fetch(`/api/orcamentos-album/publico?slug=${encodeURIComponent(slug as string)}`);
        if (!res.ok) {
          setError('Orçamento não encontrado ou expirado.');
          setLoading(false);
          return;
        }
        const json = await res.json();
        setData(json);
        setApNome(json.orcamento?.cliente_nome || '');
      } catch (e) {
        setError('Erro ao carregar orçamento.');
      } finally {
        setLoading(false);
      }
    };
    carregar();
  }, [slug]);

  // Selecionar primeira coleção automaticamente
  useEffect(() => {
    if (data?.dados?.colecao_albuns?.length && !colecaoSelecionada) {
      const primeira = data.dados.colecao_albuns[0];
      selecionarColecao(primeira);
    }
  }, [data]);

  const selecionarColecao = (col: Colecao) => {
    setColecaoSelecionada(col);
  };

  const precoFinal = colecaoSelecionada
    ? colecaoSelecionada.investimento_cliente + (laminasExtras * colecaoSelecionada.valor_lamina_extra)
    : 0;

  const enviarAprovacao = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!colecaoSelecionada || !data) return;

    setAprovando(true);
    try {
      const res = await fetch('/api/orcamentos-album/aprovar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          slug: data.orcamento.slug,
          cliente_nome: apNome,
          telefone: apTelefone,
          colecao_id: colecaoSelecionada.id,
          colecao_nome: colecaoSelecionada.nome_comercial,
          laminas_extras: laminasExtras,
          valor_total: precoFinal,
          observacoes: apObs,
        }),
      });
      const result = await res.json();

      if (result.success) {
        if (result.whatsapp_url) {
          window.location.href = result.whatsapp_url;
        } else {
          alert('Orçamento aprovado com sucesso! Entraremos em contato em breve.');
          setShowModalAprovacao(false);
        }
      } else {
        alert(result.erro || 'Falha ao processar aprovação.');
      }
    } catch (err) {
      alert('Erro de conexão.');
    } finally {
      setAprovando(false);
    }
  };

  const copiarLink = () => {
    navigator.clipboard.writeText(window.location.href).then(() => alert('Link copiado!'));
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center" style={{ background: '#09090b', color: '#f4f4f5' }}>
        <div className="text-zinc-400 text-sm">Carregando orçamento...</div>
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="min-h-screen flex items-center justify-center" style={{ background: '#09090b', color: '#f4f4f5' }}>
        <div className="text-center">
          <h1 className="text-2xl font-bold mb-2">Ops!</h1>
          <p className="text-zinc-400">{error || 'Orçamento não encontrado.'}</p>
        </div>
      </div>
    );
  }

  const { orcamento, dados, whatsapp_empresa } = data;
  const configGeral = dados.configuracao_geral || {};
  const colecoes = dados.colecao_albuns || [];
  const galeriaAcabamentos = dados.galeria_acabamentos || [];

  const validadeFormatada = orcamento.validade
    ? new Date(orcamento.validade + 'T00:00:00').toLocaleDateString('pt-BR')
    : null;
  const isVencido = orcamento.validade ? orcamento.validade < new Date().toISOString().slice(0, 10) : false;

  const msgWhats = colecaoSelecionada
    ? `Olá! Tenho interesse no Orçamento: ${orcamento.titulo}. Escolhi a ${colecaoSelecionada.nome_comercial} (+${laminasExtras} lâminas extras, Total: R$ ${precoFinal.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}). Podem me ajudar?`
    : '';

  return (
    <>
      <style jsx global>{`
        body {
          background-color: #09090b;
          color: #f4f4f5;
          font-family: 'Inter', sans-serif;
          background-image:
            radial-gradient(circle at 20% 15%, rgba(124, 58, 237, 0.12) 0%, transparent 40%),
            radial-gradient(circle at 80% 60%, rgba(217, 119, 6, 0.08) 0%, transparent 40%);
          background-attachment: fixed;
        }
        .glass-panel {
          background: rgba(24, 24, 27, 0.7);
          backdrop-filter: blur(16px);
          -webkit-backdrop-filter: blur(16px);
          border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glass-card {
          background: rgba(39, 39, 42, 0.5);
          backdrop-filter: blur(12px);
          border: 1px solid rgba(255, 255, 255, 0.07);
          transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover {
          border-color: rgba(168, 85, 247, 0.4);
          box-shadow: 0 10px 30px -10px rgba(124, 58, 237, 0.25);
          transform: translateY(-2px);
        }
        .glass-card.selected {
          border-color: #a855f7;
          background: rgba(124, 58, 237, 0.12);
          box-shadow: 0 0 30px rgba(168, 85, 247, 0.3);
        }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #09090b; }
        ::-webkit-scrollbar-thumb { background: #27272a; border-radius: 4px; }
        @media print {
          .no-print { display: none !important; }
          body { background: white !important; color: black !important; }
        }
      `}</style>

      <div className="min-h-screen flex flex-col antialiased pb-48 sm:pb-36">
        {/* Header */}
        <header className="w-full glass-panel sticky top-0 z-40 border-b border-white/10 no-print">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between">
            <div className="flex items-center space-x-2.5 sm:space-x-3">
              <div className="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-br from-purple-600 to-purple-900 flex items-center justify-center font-bold text-white shadow-lg text-sm sm:text-base">
                D
              </div>
              <div>
                <h1 className="font-extrabold tracking-wider text-base sm:text-lg text-white" style={{ fontFamily: 'Montserrat, sans-serif' }}>DISTINTO</h1>
                <p className="text-[9px] sm:text-[10px] tracking-widest uppercase text-zinc-400 font-semibold hidden sm:block">Propostas & Orçamentos Premium</p>
              </div>
            </div>
            <div className="flex items-center space-x-2 sm:space-x-3 no-print">
              <button onClick={() => window.print()} className="hidden sm:inline-flex items-center space-x-2 px-3 py-2 rounded-lg bg-zinc-800/80 hover:bg-zinc-700 text-xs font-semibold text-zinc-300 transition-colors">
                <Printer className="w-4 h-4" />
                <span>Imprimir / PDF</span>
              </button>
              <button onClick={copiarLink} className="inline-flex items-center space-x-1.5 px-3 py-2 rounded-lg bg-zinc-800/80 hover:bg-zinc-700 text-xs font-semibold text-zinc-300 transition-colors">
                <Share2 className="w-3.5 h-3.5" />
                <span>Compartilhar</span>
              </button>
            </div>
          </div>
        </header>

        {/* Main Content */}
        <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 sm:pt-8 flex-1 w-full space-y-6 sm:space-y-10">
          {/* Hero */}
          <section className="glass-panel p-5 sm:p-12 rounded-2xl sm:rounded-3xl relative overflow-hidden">
            <div className="absolute top-0 right-0 -mt-12 -mr-12 w-64 h-64 bg-purple-600/20 rounded-full blur-3xl pointer-events-none" />
            <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5 sm:gap-6">
              <div className="space-y-2.5 sm:space-y-3 max-w-3xl">
                <div className="inline-flex items-center space-x-2 px-2.5 py-1 rounded-full bg-purple-500/10 border border-purple-500/30 text-purple-300 text-[10px] sm:text-xs font-bold uppercase tracking-wider">
                  <Sparkles className="w-3 h-3 sm:w-3.5 sm:h-3.5" />
                  <span>Orçamento Comercial</span>
                </div>
                <h2 className="text-2xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight" style={{ fontFamily: 'Montserrat, sans-serif' }}>
                  {orcamento.titulo}
                </h2>
                <p className="text-sm sm:text-lg text-purple-200/80 font-medium">{orcamento.subtitulo}</p>
                <div className="flex flex-wrap items-center gap-2 sm:gap-4 pt-1 sm:pt-2 text-xs text-zinc-400">
                  <span className="flex items-center space-x-1.5 bg-zinc-900/60 px-2.5 py-1.5 rounded-lg border border-white/5">
                    <User className="w-3.5 h-3.5 sm:w-4 sm:h-4 text-purple-400" />
                    <span className="text-zinc-200 font-semibold">{orcamento.cliente_nome}</span>
                  </span>
                  {validadeFormatada && (
                    <span className="flex items-center space-x-1.5 bg-zinc-900/60 px-2.5 py-1.5 rounded-lg border border-white/5">
                      <Calendar className={`w-3.5 h-3.5 sm:w-4 sm:h-4 ${isVencido ? 'text-rose-400' : 'text-emerald-400'}`} />
                      <span>Validade: <strong className={isVencido ? 'text-rose-300' : 'text-zinc-200'}>{validadeFormatada}</strong></span>
                    </span>
                  )}
                  <span className="flex items-center space-x-1.5 bg-zinc-900/60 px-2.5 py-1.5 rounded-lg border border-white/5">
                    <CheckCircle2 className="w-3.5 h-3.5 sm:w-4 sm:h-4 text-amber-400" />
                    <span>Status: <strong className="uppercase text-amber-300">{orcamento.status}</strong></span>
                  </span>
                </div>
              </div>

              <div className="glass-card p-4 sm:p-6 rounded-2xl flex flex-col items-center justify-center text-center space-y-2.5 sm:space-y-3 min-w-full sm:min-w-[240px] border-purple-500/20">
                <span className="text-[10px] sm:text-xs uppercase font-bold tracking-widest text-zinc-400">Investimento A Partir De</span>
                <div className="text-2xl sm:text-4xl font-extrabold text-white tracking-tight" style={{ fontFamily: 'Montserrat, sans-serif' }}>
                  R$ {orcamento.valor_total.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                </div>
                <p className="text-[10px] sm:text-[11px] text-zinc-400">Em até 6x sem juros ou desconto à vista</p>
                <a href="#colecoes-section" className="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-lg text-center">
                  Ver Coleções
                </a>
              </div>
            </div>
          </section>

          {/* Especificações Técnicas */}
          {Object.keys(configGeral).length > 0 && (
            <section className="space-y-4">
              <div className="flex items-center space-x-3">
                <div className="w-8 h-8 rounded-lg bg-purple-500/20 flex items-center justify-center text-purple-400">
                  <Layers className="w-4 h-4" />
                </div>
                <h3 className="text-lg sm:text-xl font-bold text-white tracking-wide" style={{ fontFamily: 'Montserrat, sans-serif' }}>Especificações Técnicas da Linha</h3>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                <div className="glass-panel p-4 sm:p-5 rounded-2xl flex items-start space-x-3.5 sm:space-x-4">
                  <div className="p-2.5 sm:p-3 rounded-xl bg-purple-500/10 text-purple-400 shrink-0">
                    <Maximize2 className="w-5 h-5" />
                  </div>
                  <div>
                    <span className="text-[10px] sm:text-[11px] uppercase font-bold tracking-wider text-zinc-400 block">Dimensões</span>
                    <h4 className="font-bold text-zinc-100 text-xs sm:text-sm mt-0.5">{configGeral.tamanho_fechado || '30x30 cm'} (Fechado)</h4>
                    <p className="text-[11px] sm:text-xs text-zinc-400">{configGeral.tamanho_aberto || '30x60 cm'} panorâmico</p>
                  </div>
                </div>
                <div className="glass-panel p-4 sm:p-5 rounded-2xl flex items-start space-x-3.5 sm:space-x-4">
                  <div className="p-2.5 sm:p-3 rounded-xl bg-amber-500/10 text-amber-400 shrink-0">
                    <BookOpen className="w-5 h-5" />
                  </div>
                  <div>
                    <span className="text-[10px] sm:text-[11px] uppercase font-bold tracking-wider text-zinc-400 block">Encadernação</span>
                    <h4 className="font-bold text-zinc-100 text-xs sm:text-sm mt-0.5">Panorâmica 180°</h4>
                    <p className="text-[11px] sm:text-xs text-zinc-400">Lâminas rígidas de 800g</p>
                  </div>
                </div>
                <div className="glass-panel p-4 sm:p-5 rounded-2xl flex items-start space-x-3.5 sm:space-x-4">
                  <div className="p-2.5 sm:p-3 rounded-xl bg-emerald-500/10 text-emerald-400 shrink-0">
                    <FileText className="w-5 h-5" />
                  </div>
                  <div>
                    <span className="text-[10px] sm:text-[11px] uppercase font-bold tracking-wider text-zinc-400 block">Capacidade Base</span>
                    <h4 className="font-bold text-zinc-100 text-xs sm:text-sm mt-0.5">{configGeral.paginas_base || 20} Páginas (10 Lâminas)</h4>
                    <p className="text-[11px] sm:text-xs text-zinc-400">Expansível com extras</p>
                  </div>
                </div>
                <div className="glass-panel p-4 sm:p-5 rounded-2xl flex items-start space-x-3.5 sm:space-x-4">
                  <div className="p-2.5 sm:p-3 rounded-xl bg-blue-500/10 text-blue-400 shrink-0">
                    <PackageCheck className="w-5 h-5" />
                  </div>
                  <div>
                    <span className="text-[10px] sm:text-[11px] uppercase font-bold tracking-wider text-zinc-400 block">Entrega</span>
                    <h4 className="font-bold text-zinc-100 text-xs sm:text-sm mt-0.5">{configGeral.retirada || 'Presencial'}</h4>
                    <p className="text-[11px] sm:text-xs text-zinc-400">Com embalagem especial</p>
                  </div>
                </div>
              </div>

              {configGeral.servicos_inclusos?.length > 0 && (
                <div className="glass-panel p-4 sm:p-6 rounded-2xl border border-white/5 mt-3 sm:mt-4">
                  <span className="text-[10px] sm:text-xs uppercase font-bold tracking-widest text-purple-400 block mb-2.5">Serviços Premium Inclusos</span>
                  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
                    {configGeral.servicos_inclusos.map((serv: string, idx: number) => (
                      <div key={idx} className="flex items-center space-x-2 text-xs font-semibold text-zinc-200 bg-zinc-900/50 p-2.5 rounded-xl border border-white/5">
                        <Check className="w-4 h-4 text-emerald-400 shrink-0" />
                        <span>{serv}</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </section>
          )}

          {/* Coleções */}
          <section id="colecoes-section" className="space-y-4 sm:space-y-6">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
              <div>
                <div className="flex items-center space-x-2.5 sm:space-x-3">
                  <div className="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-400">
                    <Crown className="w-4 h-4" />
                  </div>
                  <h3 className="text-xl sm:text-2xl font-extrabold text-white tracking-wide" style={{ fontFamily: 'Montserrat, sans-serif' }}>Opções de Coleção & Acabamentos</h3>
                </div>
                <p className="text-xs text-zinc-400 mt-1">Selecione a coleção ideal para visualizar os detalhes completos e simular o investimento.</p>
              </div>

              <div className="glass-panel p-2.5 sm:p-3 rounded-2xl flex items-center justify-between sm:justify-start space-x-2 sm:space-x-3 w-full sm:w-auto border border-purple-500/30">
                <span className="text-xs font-bold text-zinc-300 pl-1">Lâminas Extras:</span>
                <select
                  value={laminasExtras}
                  onChange={(e) => setLaminasExtras(parseInt(e.target.value))}
                  className="bg-zinc-900 text-purple-300 font-bold text-xs rounded-xl px-2.5 py-1.5 border border-purple-500/30 focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
                  <option value={0}>+0 Lâminas (20 Páginas)</option>
                  <option value={2}>+2 Lâminas (24 Páginas)</option>
                  <option value={4}>+4 Lâminas (28 Páginas)</option>
                  <option value={6}>+6 Lâminas (32 Páginas)</option>
                  <option value={8}>+8 Lâminas (36 Páginas)</option>
                  <option value={10}>+10 Lâminas (40 Páginas)</option>
                </select>
              </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
              {colecoes.map((col, index) => {
                const catOrig = col.categoria_original || '';
                const isTop = catOrig === 'Top Master' || catOrig === 'Prestige';
                const isMid = catOrig === 'Intermediário' || catOrig === 'Classic';
                const isSimple = catOrig === 'Simples' || catOrig === 'Essencial';
                const isSelected = colecaoSelecionada?.id === col.id;
                const imgCapa = col.imagens?.capa || col.estojo?.imagem_referencia || '';

                const precoColecao = col.investimento_cliente + (laminasExtras * col.valor_lamina_extra);

                return (
                  <div
                    key={col.id}
                    onClick={() => selecionarColecao(col)}
                    className={`glass-card rounded-3xl p-6 flex flex-col justify-between cursor-pointer relative overflow-hidden group border-2 ${
                      isSelected ? 'selected' : 'border-transparent'
                    }`}
                  >
                    <div className="space-y-5">
                      {imgCapa && (
                        <div className="w-full rounded-2xl overflow-hidden bg-zinc-950 relative group-hover:scale-[1.02] transition-transform border border-white/10 shadow-inner">
                          <img src={imgCapa} alt={col.nome_comercial} className="w-full h-auto block relative z-10 opacity-95 group-hover:opacity-100 transition-opacity" />
                          <div className="absolute inset-0 bg-gradient-to-t from-zinc-950/60 via-transparent to-transparent z-20 pointer-events-none" />
                          {isTop && (
                            <div className="absolute top-3 right-3 z-30 bg-gradient-to-r from-amber-500 to-yellow-400 text-zinc-950 font-black text-[10px] uppercase tracking-widest px-3 py-1 rounded-full shadow-2xl border border-yellow-300/40">
                              TOP MASTER LUX
                            </div>
                          )}
                          {isMid && (
                            <div className="absolute top-3 right-3 z-30 bg-purple-950/90 text-purple-200 border border-purple-400/60 font-bold text-[10px] uppercase tracking-widest px-3 py-1 rounded-full backdrop-blur-md shadow-lg">
                              MAIS PROCURADO
                            </div>
                          )}
                          {isSimple && (
                            <div className="absolute top-3 right-3 z-30 bg-zinc-900/90 text-zinc-300 border border-zinc-700 font-bold text-[10px] uppercase tracking-widest px-3 py-1 rounded-full backdrop-blur-md shadow-lg">
                              COLEÇÃO ESSENCIAL
                            </div>
                          )}
                        </div>
                      )}

                      <div>
                        <span className="text-[10px] font-bold uppercase tracking-widest text-purple-400 block mb-1">{col.categoria_original || 'Coleção Premium'}</span>
                        <h4 className="text-2xl font-extrabold text-white tracking-tight" style={{ fontFamily: 'Montserrat, sans-serif' }}>{col.nome_comercial}</h4>
                        <p className="text-xs text-zinc-400 mt-2 leading-relaxed">{col.descricao}</p>
                      </div>

                      {col.acabamentos_lista_fotos?.length > 0 && (
                        <div className="space-y-2 pt-2 border-t border-white/5 text-xs">
                          <span className="text-[10px] font-bold uppercase text-zinc-400 block tracking-wider">Acabamentos:</span>
                          {col.acabamentos_lista_fotos.map((acab: any, idx: number) => (
                            <div key={idx} className="flex items-center space-x-2.5 text-zinc-300 bg-zinc-900/60 p-2 rounded-xl border border-white/5">
                              {acab.imagem ? (
                                <img
                                  src={acab.imagem}
                                  alt={acab.item || ''}
                                  onClick={(e) => { e.stopPropagation(); setShowModalFoto({ src: acab.imagem, titulo: `${acab.item || ''} - ${acab.texto || ''}` }); }}
                                  className="w-8 h-8 rounded-lg object-cover border border-purple-500/40 shrink-0 cursor-pointer hover:scale-105 transition-transform"
                                />
                              ) : (
                                <Check className="w-4 h-4 text-purple-400 shrink-0" />
                              )}
                              <span className="text-[11px] leading-snug">
                                <strong className="text-zinc-100">{acab.item || ''}:</strong> {acab.texto || ''}
                              </span>
                            </div>
                          ))}
                        </div>
                      )}

                      {col.estojo && (
                        <div className="bg-zinc-900/80 p-3.5 rounded-2xl border border-white/5 space-y-2">
                          <div className="flex items-center space-x-2 text-xs font-bold text-amber-300">
                            <Box className="w-4 h-4" />
                            <span>{col.estojo.tipo || 'Estojo Nobre'}</span>
                          </div>
                          {col.estojo.imagem_referencia && (
                            <img src={col.estojo.imagem_referencia} alt={col.estojo.tipo || 'Estojo'} className="w-full rounded-xl" />
                          )}
                          <p className="text-[11px] text-zinc-400 leading-tight">{col.estojo.descricao || ''}</p>
                        </div>
                      )}
                    </div>

                    <div className="pt-6 mt-6 border-t border-white/10 flex flex-col space-y-3">
                      <div className="flex items-baseline justify-between">
                        <span className="text-xs text-zinc-400 font-medium">Investimento:</span>
                        <div className="text-right">
                          <div className="text-2xl font-extrabold text-white tracking-tight" style={{ fontFamily: 'Montserrat, sans-serif' }}>
                            R$ {precoColecao.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                          </div>
                          <span className="text-[10px] text-purple-300 font-semibold block">
                            + R$ {col.valor_lamina_extra.toLocaleString('pt-BR', { minimumFractionDigits: 2 })} / lâmina extra
                          </span>
                        </div>
                      </div>
                      <button className="w-full py-3 rounded-xl bg-zinc-800 hover:bg-purple-600 text-white font-bold text-xs uppercase tracking-wider transition-colors flex items-center justify-center space-x-2 group-hover:bg-purple-600">
                        <CircleDot className="w-4 h-4" />
                        <span>Selecionar Coleção</span>
                      </button>
                    </div>
                  </div>
                );
              })}
            </div>
          </section>

          {/* Galeria de Acabamentos */}
          {galeriaAcabamentos.length > 0 && (
            <section className="space-y-6 pt-6">
              <div className="flex items-center space-x-3">
                <div className="w-8 h-8 rounded-lg bg-purple-500/20 flex items-center justify-center text-purple-400">
                  <Image className="w-4 h-4" />
                </div>
                <h3 className="text-2xl font-extrabold text-white tracking-wide" style={{ fontFamily: 'Montserrat, sans-serif' }}>Galeria de Detalhes & Acabamentos</h3>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {galeriaAcabamentos.map((item: any, idx: number) => (
                  <div key={idx} className="glass-card rounded-2xl overflow-hidden group flex flex-col justify-between">
                    <div className="h-44 bg-zinc-900 relative overflow-hidden">
                      <img src={item.imagem_exemplo} alt={item.item} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                      <div className="absolute inset-0 bg-gradient-to-t from-zinc-950 via-transparent to-transparent opacity-80" />
                    </div>
                    <div className="p-4 space-y-1 flex-1">
                      <h4 className="font-bold text-sm text-white" style={{ fontFamily: 'Montserrat, sans-serif' }}>{item.item}</h4>
                      <p className="text-xs text-zinc-400 leading-relaxed">{item.descricao}</p>
                    </div>
                  </div>
                ))}
              </div>
            </section>
          )}
        </main>

        {/* Footer Flutuante */}
        <div className="fixed bottom-0 left-0 right-0 z-50 glass-panel border-t border-purple-500/30 px-3 py-2.5 sm:p-5 no-print shadow-2xl backdrop-blur-2xl">
          <div className="max-w-7xl mx-auto">
            {!colecaoSelecionada ? (
              <div className="flex items-center justify-between gap-3 py-1">
                <div className="flex items-center space-x-2.5 text-amber-300">
                  <div className="w-8 h-8 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center shrink-0">
                    <Sparkles className="w-4 h-4 text-amber-400 animate-bounce" />
                  </div>
                  <div>
                    <span className="text-[9px] sm:text-xs font-bold uppercase tracking-wider text-amber-400 block">Escolha uma coleção:</span>
                    <p className="text-xs sm:text-sm font-semibold text-zinc-100">Selecione um dos álbuns acima para ver os botões de confirmação</p>
                  </div>
                </div>
                <a href="#colecoes-section" className="px-3.5 py-2 sm:px-5 sm:py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg transition-all shrink-0">
                  Escolher Álbum
                </a>
              </div>
            ) : (
              <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-1.5 sm:gap-4">
                <div className="flex items-center justify-between sm:justify-start space-x-3">
                  <div className="hidden sm:flex w-12 h-12 rounded-2xl bg-purple-600/20 border border-purple-500/40 items-center justify-center text-purple-400 shrink-0">
                    <ShoppingBag className="w-6 h-6" />
                  </div>
                  <div className="flex items-center space-x-1.5 sm:block">
                    <span className="text-[9px] font-bold uppercase tracking-wider text-purple-400 sm:text-zinc-400">Selecionada:</span>
                    <h5 className="font-extrabold text-white text-xs sm:text-base truncate max-w-[200px] sm:max-w-none" style={{ fontFamily: 'Montserrat, sans-serif' }}>
                      {colecaoSelecionada.nome_comercial}
                    </h5>
                  </div>
                </div>

                <div className="flex items-center space-x-2 sm:space-x-4 w-full sm:w-auto justify-between sm:justify-end">
                  <div className="text-left sm:text-right">
                    <span className="text-[8px] sm:text-[10px] uppercase font-bold tracking-wider text-zinc-400 block">Investimento Total</span>
                    <div className="text-lg sm:text-3xl font-black text-white tracking-tight leading-tight" style={{ fontFamily: 'Montserrat, sans-serif' }}>
                      R$ {precoFinal.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                    </div>
                  </div>
                  <div className="flex items-center space-x-1.5 sm:space-x-2 shrink-0">
                    <a
                      href={`https://wa.me/${whatsapp_empresa}?text=${encodeURIComponent(msgWhats)}`}
                      target="_blank"
                      className="p-2.5 sm:px-4 sm:py-3.5 rounded-xl sm:rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs uppercase tracking-wider transition-all flex items-center justify-center space-x-1.5 shadow-lg shadow-emerald-900/30"
                    >
                      <MessageCircle className="w-4 h-4" />
                      <span className="hidden md:inline">WhatsApp</span>
                    </a>
                    <button
                      onClick={() => setShowModalAprovacao(true)}
                      className="px-3.5 py-2.5 sm:px-6 sm:py-3.5 rounded-xl sm:rounded-2xl bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-extrabold text-xs uppercase tracking-wider transition-all shadow-xl flex items-center space-x-1.5"
                    >
                      <CheckCircle2 className="w-4 h-4" />
                      <span>Aprovar Orçamento</span>
                    </button>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Modal de Aprovação */}
        {showModalAprovacao && (
          <div className="fixed inset-0 z-[50] flex items-center justify-center bg-black/80 backdrop-blur-md p-4">
            <div className="glass-panel w-full max-w-lg rounded-3xl p-6 sm:p-8 space-y-6 border border-purple-500/40 relative">
              <button onClick={() => setShowModalAprovacao(false)} className="absolute top-5 right-5 text-zinc-400 hover:text-white">
                <X className="w-6 h-6" />
              </button>

              <div className="space-y-2">
                <div className="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold uppercase">
                  <Check className="w-3.5 h-3.5" />
                  <span>Confirmar Escolha</span>
                </div>
                <h3 className="text-2xl font-extrabold text-white" style={{ fontFamily: 'Montserrat, sans-serif' }}>Aprovação do Orçamento</h3>
                <p className="text-xs text-zinc-400">Preencha seus dados de contato para finalizarmos a confirmação.</p>
              </div>

              <form onSubmit={enviarAprovacao} className="space-y-4">
                <div>
                  <label className="block text-xs font-bold text-zinc-300 mb-1">Nome Completo</label>
                  <input type="text" value={apNome} onChange={(e) => setApNome(e.target.value)} required className="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-purple-500" />
                </div>
                <div>
                  <label className="block text-xs font-bold text-zinc-300 mb-1">WhatsApp / Telefone</label>
                  <input type="text" value={apTelefone} onChange={(e) => setApTelefone(e.target.value)} required placeholder="(27) 99999-9999" className="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-purple-500" />
                </div>

                <div className="bg-zinc-900/90 p-4 rounded-2xl border border-white/5 space-y-2">
                  <div className="flex justify-between text-xs text-zinc-400">
                    <span>Coleção Escolhida:</span>
                    <strong className="text-purple-300 font-bold">{colecaoSelecionada?.nome_comercial || '--'}</strong>
                  </div>
                  <div className="flex justify-between text-xs text-zinc-400">
                    <span>Lâminas Extras:</span>
                    <strong className="text-zinc-200 font-bold">+{laminasExtras} Lâminas</strong>
                  </div>
                  <div className="flex justify-between text-sm font-bold text-white pt-2 border-t border-white/10">
                    <span>Investimento Final:</span>
                    <strong className="text-emerald-400 text-lg">R$ {precoFinal.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</strong>
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold text-zinc-300 mb-1">Observações</label>
                  <textarea value={apObs} onChange={(e) => setApObs(e.target.value)} rows={3} placeholder="Preferências de acabamento..." className="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white outline-none focus:border-purple-500" />
                </div>

                <button type="submit" disabled={aprovando} className="w-full py-3.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white font-extrabold text-xs uppercase tracking-wider transition-all shadow-lg shadow-emerald-900/40 disabled:opacity-50">
                  {aprovando ? 'Enviando...' : 'Confirmar & Enviar Aprovação'}
                </button>
              </form>
            </div>
          </div>
        )}

        {/* Modal Foto */}
        {showModalFoto && (
          <div className="fixed inset-0 bg-black/90 backdrop-blur-md z-50 flex items-center justify-center p-4" onClick={() => setShowModalFoto(null)}>
            <div className="relative max-w-3xl w-full bg-zinc-900 border border-white/10 rounded-3xl overflow-hidden shadow-2xl space-y-4 p-4 sm:p-6" onClick={(e) => e.stopPropagation()}>
              <div className="flex items-center justify-between pb-2 border-b border-white/10">
                <h3 className="text-sm font-bold text-white tracking-wide truncate">{showModalFoto.titulo}</h3>
                <button onClick={() => setShowModalFoto(null)} className="p-1 rounded-full text-zinc-400 hover:text-white bg-zinc-800">
                  <X className="w-5 h-5" />
                </button>
              </div>
              <div className="max-h-[75vh] overflow-hidden rounded-2xl bg-zinc-950 flex items-center justify-center p-2">
                <img src={showModalFoto.src} alt={showModalFoto.titulo} className="max-h-[70vh] w-auto object-contain rounded-xl" />
              </div>
            </div>
          </div>
        )}
      </div>
    </>
  );
}
