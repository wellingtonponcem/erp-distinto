import React, { useEffect, useState } from 'react';
import { useRouter } from 'next/router';
import {
  CheckCircle2, X, FileText, MessageCircle,
  Building2, User, Mail, Globe, Calendar,
} from 'lucide-react';

interface OrcamentoData {
  orcamento: {
    id: string;
    cliente_nome: string;
    cliente_empresa: string;
    titulo: string;
    slug: string;
    valor_total: number;
    validade: string | null;
    status: string;
    criado_em: string;
  };
  dados: {
    prestador: { nome: string; empresa: string; email: string; website: string };
    overview: string;
    custo: { descricao: string; entregaveis: string[]; valor_total: number };
    timeline: { duracao: string; marcos: { fase: string; descricao: string }[] };
    proximo_passo: string;
    termos: string[];
    aprovacao?: { data: string; cliente_nome: string; telefone: string; observacoes: string };
  };
  whatsapp_empresa: string;
}

export default function OrcamentoB2BPublicoPage() {
  const router = useRouter();
  const { slug } = router.query;

  const [data, setData] = useState<OrcamentoData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [showModalAprovacao, setShowModalAprovacao] = useState(false);
  const [showModalRejeicao, setShowModalRejeicao] = useState(false);
  const [aprovando, setAprovando] = useState(false);

  useEffect(() => {
    if (!slug || typeof slug !== 'string') return;
    setLoading(true);
    setError('');

    fetch(`/api/orcamentos-b2b/publico?slug=${encodeURIComponent(slug)}`)
      .then(async (res) => {
        const text = await res.text();
        let body: any = null;
        try { body = JSON.parse(text); } catch (e) { body = { erro: text.substring(0, 100) }; }
        if (!res.ok || body.erro) throw new Error(body.erro || 'Orçamento não encontrado');
        setData(body);
      })
      .catch((err: any) => setError(err.message || 'Erro ao carregar orçamento'))
      .finally(() => setLoading(false));
  }, [slug]);

  const obterLinkWhatsApp = (mensagem: string) => {
    if (!data?.whatsapp_empresa) return '#';
    return `https://wa.me/${data.whatsapp_empresa}?text=${encodeURIComponent(mensagem)}`;
  };

  const handleAprovar = async (nomeCliente: string, telefone: string) => {
    setAprovando(true);
    try {
      const res = await fetch('/api/orcamentos-b2b/aprovar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          slug,
          cliente_nome: nomeCliente,
          telefone,
          observacoes: '',
        }),
      });
      const body = await res.json();
      if (body.success) {
        setData((prev) => prev ? { ...prev, orcamento: { ...prev.orcamento, status: 'aprovado' } } : prev);
        setShowModalAprovacao(false);
        if (body.whatsapp_url) window.open(body.whatsapp_url, '_blank');
      } else {
        alert(body.erro || 'Falha ao aprovar orçamento.');
      }
    } catch {
      alert('Erro ao conectar ao servidor.');
    } finally {
      setAprovando(false);
    }
  };

  const handleRejeitar = async () => {
    setAprovando(true);
    try {
      const res = await fetch('/api/orcamentos-b2b/atualizar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id: data?.orcamento.id,
          status: 'recusado',
        }),
      });
      const body = await res.json();
      if (body.success) {
        setData((prev) => prev ? { ...prev, orcamento: { ...prev.orcamento, status: 'recusado' } } : prev);
        setShowModalRejeicao(false);
      } else {
        alert(body.erro || 'Falha ao recusar orçamento.');
      }
    } catch {
      alert('Erro ao conectar ao servidor.');
    } finally {
      setAprovando(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-[#080808] flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-[#c5a880] mx-auto mb-4" />
          <p className="text-zinc-400 text-sm">Carregando orçamento...</p>
        </div>
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="min-h-screen bg-[#080808] flex items-center justify-center p-6">
        <div className="text-center max-w-sm w-full">
          <div className="w-20 h-20 rounded-2xl bg-rose-500/10 flex items-center justify-center mx-auto mb-6">
            <FileText className="w-10 h-10 text-rose-400" />
          </div>
          <h2 className="text-2xl font-bold text-white mb-2">Orçamento não encontrado</h2>
          <p className="text-zinc-500 text-sm mb-6">{error || 'Este orçamento pode ter sido removido ou o link está incorreto.'}</p>
          <button onClick={() => window.history.back()} className="px-6 py-3 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-sm transition">Voltar</button>
        </div>
      </div>
    );
  }

  const { orcamento, dados } = data;
  const statusAprovado = orcamento.status === 'aprovado';
  const statusRecusado = orcamento.status === 'recusado';

  const checkValidadeExpirada = (valStr: any) => {
    if (!valStr) return false;
    const str = String(valStr);
    if (/^\d{4}-\d{2}-\d{2}/.test(str)) {
      const parts = str.substring(0, 10).split('-');
      const endOfDay = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]), 23, 59, 59);
      return endOfDay < new Date();
    }
    return new Date(valStr) < new Date();
  };

  const validadeExpirada = checkValidadeExpirada(orcamento.validade);

  const msgAprovacao = [
    '*Orçamento B2B Aprovado!*',
    `Cliente: *${orcamento.cliente_nome}*`,
    orcamento.cliente_empresa ? `Empresa: *${orcamento.cliente_empresa}*` : '',
    `Orçamento: *${orcamento.titulo}*`,
    `Investimento: *R$ ${orcamento.valor_total.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}*`,
    '',
    `${process.env.NEXT_PUBLIC_APP_URL || ''}/b2b/${orcamento.slug}`,
  ].filter(Boolean).join('\n');

  const msgSolicitarNovo = [
    'Olá! Gostaria de solicitar um novo orçamento para:',
    '',
    `Orçamento: *${orcamento.titulo}*`,
    `Cliente: *${orcamento.cliente_nome}*`,
    orcamento.cliente_empresa ? `Empresa: *${orcamento.cliente_empresa}*` : '',
    '',
    `O orçamento anterior expirou em ${orcamento.validade ? new Date(orcamento.validade).toLocaleDateString('pt-BR') : 'N/A'}.`,
    '',
    'Podemos agendar uma conversa?',
  ].filter(Boolean).join('\n');

  const msgDuvidas = [
    'Olá! Tenho dúvidas sobre este orçamento:',
    '',
    `Orçamento: *${orcamento.titulo}*`,
    `Cliente: *${orcamento.cliente_nome}*`,
    orcamento.cliente_empresa ? `Empresa: *${orcamento.cliente_empresa}*` : '',
    '',
    'Pode me ajudar?',
  ].filter(Boolean).join('\n');

  return (
    <div className="min-h-screen bg-[#080808] text-white">
      {/* Barra dourada no topo */}
      <div className="h-1 bg-[#c5a880]" />

      {/* Banner validade expirada */}
      {validadeExpirada && (
        <div className="bg-amber-500/10 border-b border-amber-500/20 px-6 py-3 text-center">
          <p className="text-amber-400 text-sm font-bold">
            ⚠️ Este orçamento expirou em {orcamento.validade ? new Date(orcamento.validade).toLocaleDateString('pt-BR') : 'N/A'}. Solicite um novo orçamento.
          </p>
        </div>
      )}

      {/* Hero Section */}
      <div className="relative overflow-hidden bg-gradient-to-br from-[#0c0c0c] via-[#111111] to-[#0c0c0c] border-b border-[#c5a880]/20">
        <div className="absolute inset-0 opacity-5">
          <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-[#c5a880] rounded-full blur-[150px] -translate-y-1/2 translate-x-1/3" />
        </div>
        <div className="relative max-w-4xl mx-auto px-6 py-16 text-center">
          {/* Logo Distinto */}
          <div className="mb-8">
            <img
              src="/assets/distinto_logo.svg"
              alt="Distinto"
              className="h-10 mx-auto"
              style={{ filter: 'brightness(0) invert(1)' }}
            />
          </div>

          <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#c5a880]/10 border border-[#c5a880]/20 text-[#c5a880] text-[10px] font-bold tracking-widest uppercase mb-6">
            Proposta Comercial
          </div>
          <h1 className="text-4xl md:text-5xl font-extrabold tracking-tight mb-4 leading-tight">
            {orcamento.titulo}
          </h1>
          <p className="text-zinc-400 text-sm max-w-lg mx-auto mb-4">
            {orcamento.cliente_nome}
            {orcamento.cliente_empresa ? `, ${orcamento.cliente_empresa}` : ''}
          </p>
          <div className="flex items-center justify-center gap-4 text-xs text-zinc-500">
            <span className="flex items-center gap-1"><Calendar className="w-3.5 h-3.5" />{new Date(orcamento.criado_em).toLocaleDateString('pt-BR')}</span>
            {orcamento.validade && (
              <span className="flex items-center gap-1"><Calendar className="w-3.5 h-3.5" />Válido até {new Date(orcamento.validade).toLocaleDateString('pt-BR')}</span>
            )}
          </div>
        </div>
      </div>

      <div className="max-w-4xl mx-auto px-6 py-12 space-y-10">
        {/* Visao Geral */}
        {dados.overview && (
          <div>
            <h2 className="text-xl font-bold text-white mb-4">Visão Geral</h2>
            <div className="bg-[#121212] border border-white/5 rounded-2xl p-6">
              <p className="text-zinc-300 text-sm leading-relaxed whitespace-pre-line">{dados.overview}</p>
            </div>
          </div>
        )}

        {/* Investimento */}
        {dados.custo && (
          <div>
            <h2 className="text-xl font-bold text-white mb-4">Investimento</h2>
            <div className="bg-[#121212] border border-white/5 rounded-2xl p-6">
              {dados.custo.descricao && (
                <p className="text-zinc-300 text-sm mb-4">{dados.custo.descricao}</p>
              )}
              {dados.custo.entregaveis?.length > 0 && (
                <ul className="space-y-2">
                  {dados.custo.entregaveis.map((item, i) => (
                    <li key={i} className="flex items-start gap-2 text-sm text-zinc-300">
                      <CheckCircle2 className="w-4 h-4 text-[#c5a880] mt-0.5 shrink-0" />
                      <span>{item}</span>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </div>
        )}

        {/* Valor Total em Destaque */}
        <div className="bg-[#121212] border border-[#c5a880]/10 rounded-2xl p-8 text-center">
          <p className="text-xs font-bold uppercase tracking-widest text-zinc-500 mb-2">Investimento Total</p>
          <p className="text-4xl font-extrabold text-[#c5a880]">
            R$ {orcamento.valor_total.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
          </p>
          {orcamento.validade && (
            <p className="text-xs text-zinc-500 mt-2">Válido até {new Date(orcamento.validade).toLocaleDateString('pt-BR')}</p>
          )}
        </div>

        {/* Cronograma */}
        {dados.timeline && (
          <div>
            <h2 className="text-xl font-bold text-white mb-4">Cronograma</h2>
            <div className="bg-[#121212] border border-white/5 rounded-2xl p-6">
              {dados.timeline.duracao && (
                <p className="text-sm font-bold text-white mb-4">{dados.timeline.duracao}</p>
              )}
              {dados.timeline.marcos?.length > 0 && (
                <div className="space-y-4">
                  {dados.timeline.marcos.map((marco, i) => (
                    <div key={i} className="flex items-start gap-3">
                      <div className="w-6 h-6 rounded-full bg-[#c5a880]/20 border border-[#c5a880]/30 flex items-center justify-center shrink-0 mt-0.5">
                        <span className="text-[#c5a880] text-[10px] font-bold">{i + 1}</span>
                      </div>
                      <div>
                        <p className="text-sm font-bold text-white">{marco.fase}</p>
                        <p className="text-xs text-zinc-400 mt-0.5">{marco.descricao}</p>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}

        {/* Proximo Passo */}
        {dados.proximo_passo && (
          <div>
            <h2 className="text-xl font-bold text-white mb-4">Próximo Passo</h2>
            <div className="bg-[#121212] border border-white/5 rounded-2xl p-6">
              <p className="text-zinc-300 text-sm leading-relaxed whitespace-pre-line">{dados.proximo_passo}</p>
            </div>
          </div>
        )}

        {/* Termos */}
        {dados.termos?.length > 0 && (
          <div>
            <h2 className="text-xl font-bold text-white mb-4">Termos e Condições</h2>
            <div className="bg-[#121212] border border-white/5 rounded-2xl p-6">
              <ol className="space-y-2">
                {dados.termos.map((termo, i) => (
                  <li key={i} className="flex items-start gap-3 text-sm text-zinc-300">
                    <span className="font-bold text-[#c5a880] shrink-0">{i + 1}.</span>
                    <span>{termo}</span>
                  </li>
                ))}
              </ol>
            </div>
          </div>
        )}

        {/* Dados do Prestador */}
        {dados.prestador && (
          <div className="bg-[#121212] border border-white/5 rounded-2xl p-6">
            <h3 className="text-sm font-bold text-white mb-4">Dados do Prestador</h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
              {dados.prestador.nome && (
                <div className="flex items-center gap-2 text-zinc-300">
                  <User className="w-4 h-4 text-zinc-500" /> {dados.prestador.nome}
                </div>
              )}
              {dados.prestador.empresa && (
                <div className="flex items-center gap-2 text-zinc-300">
                  <Building2 className="w-4 h-4 text-zinc-500" /> {dados.prestador.empresa}
                </div>
              )}
              {dados.prestador.email && (
                <div className="flex items-center gap-2 text-zinc-300">
                  <Mail className="w-4 h-4 text-zinc-500" /> {dados.prestador.email}
                </div>
              )}
              {dados.prestador.website && (
                <div className="flex items-center gap-2 text-zinc-300">
                  <Globe className="w-4 h-4 text-zinc-500" /> {dados.prestador.website}
                </div>
              )}
            </div>
          </div>
        )}

        {/* Status Badge / Aprovacao */}
        {statusAprovado && (
          <div className="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-8 text-center">
            <CheckCircle2 className="w-12 h-12 text-emerald-400 mx-auto mb-4" />
            <h3 className="text-lg font-bold text-emerald-300 mb-2">Orçamento Aprovado</h3>
            <p className="text-sm text-emerald-200/70">Este orçamento foi aprovado com sucesso.</p>
          </div>
        )}

        {statusRecusado && (
          <div className="bg-rose-500/10 border border-rose-500/20 rounded-2xl p-8 text-center">
            <X className="w-12 h-12 text-rose-400 mx-auto mb-4" />
            <h3 className="text-lg font-bold text-rose-300 mb-2">Orçamento Recusado</h3>
            <p className="text-sm text-rose-200/70">Este orçamento foi recusado.</p>
          </div>
        )}

        {/* Botoes de Acao */}
        {!statusAprovado && !statusRecusado && !validadeExpirada && (
          <div className="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4 pb-8">
            <button
              onClick={() => setShowModalAprovacao(true)}
              className="w-full sm:w-auto px-8 py-4 rounded-xl bg-[#c5a880] hover:bg-[#d4b78f] text-black font-extrabold text-sm tracking-wider shadow-xl flex items-center justify-center gap-2 transition"
            >
              <CheckCircle2 className="w-5 h-5" /> Aprovar Orçamento
            </button>
            <button
              onClick={() => setShowModalRejeicao(true)}
              className="w-full sm:w-auto px-8 py-4 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-white font-bold text-sm flex items-center justify-center gap-2 transition"
            >
              <X className="w-5 h-5" /> Recusar
            </button>
          </div>
        )}

        {!statusAprovado && !statusRecusado && validadeExpirada && (
          <div className="flex flex-col items-center gap-4 pt-4 pb-8">
            <a
              href={obterLinkWhatsApp(msgSolicitarNovo)}
              target="_blank"
              rel="noopener noreferrer"
              className="w-full sm:w-auto px-8 py-4 rounded-xl bg-[#c5a880] hover:bg-[#d4b78f] text-black font-extrabold text-sm tracking-wider shadow-xl flex items-center justify-center gap-2 transition"
            >
              <MessageCircle className="w-5 h-5" /> Solicitar Novo Orçamento
            </a>
          </div>
        )}

        {/* Exportar PDF */}
        <div className="text-center pt-4 pb-8">
          <button
            onClick={() => window.open(`/api/orcamentos-b2b/pdf?slug=${orcamento.slug}`, '_blank')}
            className="px-6 py-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-white text-sm font-bold flex items-center gap-2 mx-auto transition"
          >
            <FileText className="w-4 h-4" /> Exportar PDF
          </button>
        </div>

        {/* Footer da Empresa */}
        <div className="text-center pt-8 pb-4 border-t border-white/5">
          <img
            src="/assets/distinto_logo.svg"
            alt="Distinto"
            className="h-6 mx-auto mb-3 opacity-40"
            style={{ filter: 'brightness(0) invert(1)' }}
          />
          <p className="text-[10px] text-zinc-600">Poncem Studio LTDA | CNPJ: 50.768.732/0001-63</p>
          <p className="text-[10px] text-zinc-600">contato@wedistinto.com | wedistinto.com</p>
        </div>

        {/* WhatsApp Flutuante */}
        {data.whatsapp_empresa && (
          <a
            href={obterLinkWhatsApp(msgDuvidas)}
            target="_blank"
            rel="noopener noreferrer"
            className="fixed bottom-6 right-6 w-14 h-14 rounded-full bg-[#25D366] flex items-center justify-center shadow-2xl hover:scale-110 transition-transform z-50"
            title="Tirar dúvidas via WhatsApp"
          >
            <MessageCircle className="w-7 h-7 text-white" fill="white" />
          </a>
        )}

        {/* Modal Aprovacao */}
        {showModalAprovacao && (
          <ModalAprovacao
            orcamento={orcamento}
            onAprovar={handleAprovar}
            onFechar={() => setShowModalAprovacao(false)}
            processando={aprovando}
          />
        )}

        {/* Modal Recusa */}
        {showModalRejeicao && (
          <ModalRecusa
            onConfirmar={handleRejeitar}
            onFechar={() => setShowModalRejeicao(false)}
            processando={aprovando}
          />
        )}
      </div>
    </div>
  );
}

// Modal de Aprovacao
const ModalAprovacao: React.FC<{
  orcamento: any;
  onAprovar: (nome: string, telefone: string) => void;
  onFechar: () => void;
  processando: boolean;
}> = ({ orcamento, onAprovar, onFechar, processando }) => {
  const [nome, setNome] = useState(orcamento.cliente_nome || '');
  const [telefone, setTelefone] = useState('');

  return (
    <div className="fixed inset-0 z-[2000] flex items-center justify-center p-6 bg-black/90 backdrop-blur-md" onClick={onFechar}>
      <div className="bg-[#0c0c0c] border border-white/10 rounded-2xl max-w-sm w-full p-6 shadow-2xl" onClick={(e) => e.stopPropagation()}>
        <div className="flex items-center gap-3 mb-5">
          <div className="w-10 h-10 rounded-xl bg-[#c5a880]/20 flex items-center justify-center">
            <CheckCircle2 className="w-5 h-5 text-[#c5a880]" />
          </div>
          <h3 className="text-lg font-bold text-white">Confirmar Aprovação</h3>
        </div>
        <div className="space-y-3 mb-6">
          <div>
            <label className="block text-[10px] font-bold uppercase tracking-wider text-zinc-500 mb-1">Seu Nome *</label>
            <input type="text" value={nome} onChange={(e) => setNome(e.target.value)} required
              className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-[#c5a880]" />
          </div>
          <div>
            <label className="block text-[10px] font-bold uppercase tracking-wider text-zinc-500 mb-1">Telefone (opcional)</label>
            <input type="tel" value={telefone} onChange={(e) => setTelefone(e.target.value)}
              className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-[#c5a880]" placeholder="(00) 00000-0000" />
          </div>
        </div>
        <div className="flex gap-3">
          <button onClick={onFechar} className="flex-1 px-4 py-3 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-400 font-semibold text-sm transition">Cancelar</button>
          <button onClick={() => { if (nome.trim()) onAprovar(nome.trim(), telefone); }} disabled={processando || !nome.trim()}
            className="flex-1 px-4 py-3 rounded-xl bg-[#c5a880] hover:bg-[#d4b78f] text-black font-extrabold text-sm shadow-lg transition disabled:opacity-50">
            {processando ? 'Enviando...' : 'Aprovar'}
          </button>
        </div>
      </div>
    </div>
  );
};

// Modal de Recusa
const ModalRecusa: React.FC<{
  onConfirmar: () => void;
  onFechar: () => void;
  processando: boolean;
}> = ({ onConfirmar, onFechar, processando }) => (
  <div className="fixed inset-0 z-[2000] flex items-center justify-center p-6 bg-black/90 backdrop-blur-md" onClick={onFechar}>
    <div className="bg-[#0c0c0c] border border-white/10 rounded-2xl max-w-sm w-full p-6 shadow-2xl" onClick={(e) => e.stopPropagation()}>
      <div className="flex items-center gap-3 mb-5">
        <div className="w-10 h-10 rounded-xl bg-rose-500/20 flex items-center justify-center">
          <X className="w-5 h-5 text-rose-400" />
        </div>
        <h3 className="text-lg font-bold text-white">Recusar Orçamento</h3>
      </div>
      <p className="text-sm text-zinc-400 mb-6">Tem certeza que deseja recusar este orçamento? O prestador será notificado.</p>
      <div className="flex gap-3">
        <button onClick={onFechar} className="flex-1 px-4 py-3 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-400 font-semibold text-sm transition">Voltar</button>
        <button onClick={onConfirmar} disabled={processando}
          className="flex-1 px-4 py-3 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 border border-rose-500/30 text-rose-400 font-bold text-sm transition disabled:opacity-50">
          {processando ? 'Enviando...' : 'Confirmar Recusa'}
        </button>
      </div>
    </div>
  </div>
);
