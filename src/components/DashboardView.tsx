import React, { useEffect, useState } from 'react';

export const DashboardView: React.FC = () => {
  const [lancamentos, setLancamentos] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [saldoAsaas, setSaldoAsaas] = useState<number | null>(null);
  const [asaasConfigurado, setAsaasConfigurado] = useState<boolean>(false);

  // Filtro de período da tabela do Dashboard (Padrão: 'semana')
  const [filtroPeriodo, setFiltroPeriodo] = useState<'semana' | 'mes' | 'todos'>('semana');

  // Modal State
  const [modalAberta, setModalAberta] = useState(false);
  const [tipo, setTipo] = useState<'receber' | 'pagar'>('receber');
  const [descricao, setDescricao] = useState('');
  const [valor, setValor] = useState('');
  const [vencimento, setVencimento] = useState(new Date().toISOString().split('T')[0]);
  const [categoria, setCategoria] = useState('Serviços');
  const [clienteFornecedor, setClienteFornecedor] = useState('');
  const [status, setStatus] = useState<'pendente' | 'pago'>('pago');
  const [salvando, setSalvando] = useState(false);

  const carregarDados = () => {
    setLoading(true);
    fetch('/api/financeiro/lancamentos')
      .then((res) => res.json())
      .then((data) => {
        if (Array.isArray(data)) {
          setLancamentos(data);
        }
      })
      .catch((err) => console.error(err))
      .finally(() => setLoading(false));

    fetch('/api/financeiro/asaas-balance')
      .then((res) => res.json())
      .then((data) => {
        if (data.ok) {
          setSaldoAsaas(data.saldo);
          setAsaasConfigurado(true);
        } else {
          setAsaasConfigurado(false);
        }
      })
      .catch(() => {});
  };

  useEffect(() => {
    carregarDados();
  }, []);

  const handleSeed = async () => {
    setLoading(true);
    try {
      await fetch('/api/financeiro/seed', { method: 'POST' });
      carregarDados();
    } catch (e) {
      console.error(e);
      setLoading(false);
    }
  };

  const handleCriarLancamento = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!descricao || !valor || !vencimento) return;

    setSalvando(true);
    try {
      const valorNum = parseFloat(valor);
      const valorPago = status === 'pago' ? valorNum : 0;

      const res = await fetch('/api/financeiro/lancamentos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          tipo,
          descricao,
          valor: valorNum,
          valor_pago: valorPago,
          categoria,
          cliente_fornecedor: clienteFornecedor,
          vencimento,
          status,
          data_pagamento: status === 'pago' ? vencimento : null,
        }),
      });

      if (res.ok) {
        setModalAberta(false);
        setDescricao('');
        setValor('');
        setClienteFornecedor('');
        carregarDados();
      }
    } catch (err) {
      console.error(err);
    } finally {
      setSalvando(false);
    }
  };

  // Cálculo das datas da Semana Atual (Domingo 00:00 a Sábado 23:59)
  const hoje = new Date();
  const diaDaSemana = hoje.getDay();
  const inicioSemana = new Date(hoje);
  inicioSemana.setDate(hoje.getDate() - diaDaSemana);
  inicioSemana.setHours(0, 0, 0, 0);

  const fimSemana = new Date(inicioSemana);
  fimSemana.setDate(inicioSemana.getDate() + 6);
  fimSemana.setHours(23, 59, 59, 999);

  const inicioSemanaStr = inicioSemana.toLocaleDateString('pt-BR');
  const fimSemanaStr = fimSemana.toLocaleDateString('pt-BR');

  // Filtragem dos Lançamentos da Semana Atual
  const lancamentosExibidos = lancamentos.filter((item) => {
    if (filtroPeriodo === 'todos') return true;

    const dtRefStr = item.vencimento || item.data_pagamento || item.criado_em || item.created_at;
    if (!dtRefStr) return true;

    // Tratar datas em string YYYY-MM-DD
    const partes = String(dtRefStr).split('T')[0].split('-');
    let dtRef: Date;
    if (partes.length === 3) {
      dtRef = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
    } else {
      dtRef = new Date(dtRefStr);
    }

    if (filtroPeriodo === 'semana') {
      return dtRef >= inicioSemana && dtRef <= fimSemana;
    }

    if (filtroPeriodo === 'mes') {
      return dtRef.getMonth() === hoje.getMonth() && dtRef.getFullYear() === hoje.getFullYear();
    }

    return true;
  });

  // Calculations Gerais
  const totalReceber = lancamentos
    .filter((l) => l.tipo === 'receber' && l.status !== 'cancelado')
    .reduce((acc, l) => acc + (parseFloat(l.valor) - parseFloat(l.valor_pago || 0)), 0);

  const totalPagar = lancamentos
    .filter((l) => l.tipo === 'pagar' && l.status !== 'cancelado')
    .reduce((acc, l) => acc + (parseFloat(l.valor) - parseFloat(l.valor_pago || 0)), 0);

  const totalRecebido = lancamentos
    .filter((l) => l.tipo === 'receber')
    .reduce((acc, l) => acc + parseFloat(l.valor_pago || 0), 0);

  const totalPago = lancamentos
    .filter((l) => l.tipo === 'pagar')
    .reduce((acc, l) => acc + parseFloat(l.valor_pago || 0), 0);

  const saldoLocal = totalRecebido - totalPago;
  const exibeSaldo = saldoAsaas !== null ? saldoAsaas : saldoLocal;

  const pendentes = lancamentos.filter((l) => l.status === 'pendente' || l.status === 'atrasado');

  return (
    <div className="space-y-6 font-sans text-white bg-[#050505] min-h-screen">
      {/* Header Actions */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-white tracking-tight">Dashboard Principal</h1>
          <p className="text-xs text-zinc-400 mt-0.5 font-medium">
            Resumo financeiro e acompanhamento de lançamentos da semana corrente ({inicioSemanaStr} a {fimSemanaStr})
          </p>
        </div>

        <div className="flex items-center space-x-3">
          {lancamentos.length === 0 && (
            <button
              onClick={handleSeed}
              className="px-4 py-2 bg-emerald-950/60 text-emerald-400 hover:bg-emerald-900/60 border border-emerald-500/30 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-xs"
            >
              <span className="material-symbols-outlined text-sm leading-none">auto_fix_high</span>
              <span>Popular Dados Iniciais</span>
            </button>
          )}

          <button
            onClick={() => setModalAberta(true)}
            className="px-4 py-2 bg-white text-black hover:bg-zinc-200 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-sm"
          >
            <span className="material-symbols-outlined text-sm leading-none">add</span>
            <span>Novo Lançamento</span>
          </button>
        </div>
      </div>

      {/* Bento Grid Metrics */}
      <div className="grid grid-cols-12 gap-4">
        {/* Main 9 Columns */}
        <div className="col-span-12 lg:col-span-9 space-y-4">
          {/* Top 4 Cards */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {/* Clientes Cadastrados */}
            <div className="bg-[#0c0c0c] border border-white/10 p-5 rounded-2xl flex flex-col justify-between h-32 hover:border-white/20 transition shadow-2xs group">
              <span className="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Clientes Cadastrados</span>
              <div className="flex items-baseline justify-between mt-auto">
                <span className="text-3xl font-extrabold text-white group-hover:scale-105 transition-transform font-sans">
                  3
                </span>
                <span className="material-symbols-outlined text-zinc-600 text-3xl leading-none">groups</span>
              </div>
            </div>

            {/* Fornecedores */}
            <div className="bg-[#0c0c0c] border border-white/10 p-5 rounded-2xl flex flex-col justify-between h-32 hover:border-white/20 transition shadow-2xs group">
              <span className="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Fornecedores</span>
              <div className="flex items-baseline justify-between mt-auto">
                <span className="text-3xl font-extrabold text-white group-hover:scale-105 transition-transform font-sans">
                  2
                </span>
                <span className="material-symbols-outlined text-zinc-600 text-3xl leading-none">inventory_2</span>
              </div>
            </div>

            {/* Oportunidades */}
            <div className="bg-[#0c0c0c] border border-white/10 p-5 rounded-2xl flex flex-col justify-between h-32 hover:border-white/20 transition shadow-2xs group">
              <span className="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Oportunidades</span>
              <div className="flex items-baseline justify-between mt-auto">
                <span className="text-3xl font-extrabold text-amber-400 group-hover:scale-105 transition-transform font-sans">
                  4
                </span>
                <span className="material-symbols-outlined text-amber-500/50 text-3xl leading-none">rocket_launch</span>
              </div>
            </div>

            {/* Total Propostas */}
            <div className="bg-[#0c0c0c] border border-white/10 border-l-4 border-l-white p-5 rounded-2xl flex flex-col justify-between h-32 hover:border-white/20 transition shadow-2xs group">
              <span className="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Total Propostas</span>
              <div className="flex items-baseline justify-between mt-auto">
                <span className="text-3xl font-extrabold text-white group-hover:scale-105 transition-transform font-sans">
                  5
                </span>
                <span className="material-symbols-outlined text-zinc-600 text-3xl leading-none">request_quote</span>
              </div>
            </div>
          </div>

          {/* Cash Flow Card */}
          <div className="bg-[#0c0c0c] border border-white/10 p-6 rounded-2xl shadow-2xs space-y-4">
            <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b border-white/10 pb-4">
              <div>
                <div className="flex items-center space-x-2">
                  <h3 className="text-base font-bold text-white">Fluxo de Caixa Consolidado</h3>
                  <span className="bg-emerald-950/60 text-emerald-400 px-2 py-0.5 rounded text-[10px] font-bold uppercase border border-emerald-500/30">
                    {asaasConfigurado ? 'ASAAS LIVE' : 'CAIXA LOCAL'}
                  </span>
                </div>
                <p className="text-xs text-zinc-400 mt-0.5">Visão consolidada de entradas e liquidações</p>
              </div>

              <div className="text-right">
                <span className="text-[11px] font-bold uppercase tracking-wider text-zinc-400 block">Saldo em Conta</span>
                <span className="text-2xl font-extrabold text-white font-mono">
                  R$ {exibeSaldo.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                </span>
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div className="bg-emerald-950/30 border border-emerald-500/20 p-4 rounded-xl flex items-center justify-between">
                <div>
                  <span className="text-[10px] font-bold uppercase tracking-wider text-emerald-400">Entradas Baixadas</span>
                  <p className="text-lg font-extrabold text-emerald-400 font-mono mt-0.5">
                    + R$ {totalRecebido.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                  </p>
                </div>
                <span className="material-symbols-outlined text-emerald-400 text-2xl leading-none">south_west</span>
              </div>

              <div className="bg-rose-950/30 border border-rose-500/20 p-4 rounded-xl flex items-center justify-between">
                <div>
                  <span className="text-[10px] font-bold uppercase tracking-wider text-rose-400">Saídas Liquidadas</span>
                  <p className="text-lg font-extrabold text-rose-400 font-mono mt-0.5">
                    - R$ {totalPago.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                  </p>
                </div>
                <span className="material-symbols-outlined text-rose-400 text-2xl leading-none">north_east</span>
              </div>
            </div>
          </div>

          {/* Transactions Table - FILTRADO POR PADRÃO APENAS PARA A SEMANA ATUAL */}
          <div className="bg-[#0c0c0c] border border-white/10 rounded-2xl overflow-hidden shadow-2xs">
            <div className="p-5 border-b border-white/10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
              <div>
                <h3 className="font-bold text-white text-sm flex items-center space-x-2">
                  <span className="material-symbols-outlined text-purple-400 text-base">date_range</span>
                  <span>Lançamentos Financeiros da Semana</span>
                </h3>
                <p className="text-xs text-zinc-400 mt-0.5">
                  {filtroPeriodo === 'semana'
                    ? `Exibindo apenas vencimentos da semana corrente (${inicioSemanaStr} a ${fimSemanaStr})`
                    : filtroPeriodo === 'mes'
                    ? `Exibindo lançamentos do mês atual`
                    : 'Exibindo todos os lançamentos cadastrados'}
                </p>
              </div>

              {/* Botões de Filtro de Período */}
              <div className="flex items-center space-x-1.5 bg-zinc-900 p-1 rounded-xl border border-white/5">
                <button
                  onClick={() => setFiltroPeriodo('semana')}
                  className={`px-3 py-1 rounded-lg text-xs font-bold transition ${
                    filtroPeriodo === 'semana' ? 'bg-white text-black shadow-xs' : 'text-zinc-400 hover:text-white'
                  }`}
                >
                  Semana Atual
                </button>
                <button
                  onClick={() => setFiltroPeriodo('mes')}
                  className={`px-3 py-1 rounded-lg text-xs font-bold transition ${
                    filtroPeriodo === 'mes' ? 'bg-white text-black shadow-xs' : 'text-zinc-400 hover:text-white'
                  }`}
                >
                  Mês Atual
                </button>
                <button
                  onClick={() => setFiltroPeriodo('todos')}
                  className={`px-3 py-1 rounded-lg text-xs font-bold transition ${
                    filtroPeriodo === 'todos' ? 'bg-white text-black shadow-xs' : 'text-zinc-400 hover:text-white'
                  }`}
                >
                  Todos
                </button>
              </div>
            </div>

            {loading ? (
              <div className="p-8 text-center text-xs text-zinc-500">Carregando lançamentos...</div>
            ) : lancamentosExibidos.length === 0 ? (
              <div className="p-12 text-center">
                <span className="material-symbols-outlined text-4xl text-zinc-600 mb-2 leading-none">calendar_today</span>
                <p className="text-sm font-bold text-white">
                  Nenhum lançamento financeiro para a semana atual ({inicioSemanaStr} a {fimSemanaStr}).
                </p>
                <p className="text-xs text-zinc-400 mt-1 max-w-sm mx-auto">
                  Você pode alternar o filtro para <strong>Mês Atual</strong> ou <strong>Todos</strong> para visualizar os lançamentos de outras datas!
                </p>
                <div className="mt-4 flex justify-center space-x-2">
                  <button
                    onClick={() => setFiltroPeriodo('todos')}
                    className="px-3.5 py-1.5 bg-zinc-800 hover:bg-zinc-700 text-white rounded-xl text-xs font-bold transition"
                  >
                    Ver Todos os Lançamentos
                  </button>
                  <button
                    onClick={() => setModalAberta(true)}
                    className="px-3.5 py-1.5 bg-white text-black rounded-xl text-xs font-bold hover:bg-zinc-200 transition"
                  >
                    + Novo Lançamento na Semana
                  </button>
                </div>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs font-sans">
                  <thead className="bg-[#121212] text-zinc-400 uppercase tracking-wider font-bold text-[10px] border-b border-white/10">
                    <tr>
                      <th className="py-3 px-4">Tipo</th>
                      <th className="py-3 px-4">Descrição</th>
                      <th className="py-3 px-4">Cliente / Fornecedor</th>
                      <th className="py-3 px-4">Vencimento</th>
                      <th className="py-3 px-4">Data Pagamento</th>
                      <th className="py-3 px-4">Valor</th>
                      <th className="py-3 px-4">Status</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-white/5">
                    {lancamentosExibidos.map((item) => {
                      const isReceber = item.tipo === 'receber';
                      const valorNum = parseFloat(item.valor || 0);

                      return (
                        <tr key={item.id} className="hover:bg-zinc-900/60 transition">
                          <td className="py-3 px-4">
                            <span
                              className={`inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase ${
                                isReceber ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-500/30' : 'bg-rose-950/60 text-rose-400 border border-rose-500/30'
                              }`}
                            >
                              {item.tipo}
                            </span>
                          </td>
                          <td className="py-3 px-4 font-bold text-white">{item.descricao}</td>
                          <td className="py-3 px-4 text-zinc-400">{item.cliente_fornecedor || '—'}</td>
                          <td className="py-3 px-4 text-zinc-400 font-mono">
                            {item.vencimento ? new Date(item.vencimento).toLocaleDateString('pt-BR') : '—'}
                          </td>
                          <td className="py-3 px-4 text-zinc-400 font-mono">
                            {item.data_pagamento ? new Date(item.data_pagamento).toLocaleDateString('pt-BR') : '—'}
                          </td>
                          <td className="py-3 px-4 font-mono font-bold text-white">
                            R$ {valorNum.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                          </td>
                          <td className="py-3 px-4">
                            <span
                              className={`inline-block px-2.5 py-1 rounded-full text-[10px] font-bold uppercase ${
                                item.status === 'pago'
                                  ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-500/30'
                                  : item.status === 'atrasado'
                                  ? 'bg-rose-950/60 text-rose-400 border border-rose-500/30'
                                  : 'bg-amber-950/60 text-amber-400 border border-amber-500/30'
                              }`}
                            >
                              {item.status}
                            </span>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>

        {/* Right 3 Columns */}
        <div className="col-span-12 lg:col-span-3 space-y-4">
          {/* Painel de Pendências */}
          <div className="bg-[#0c0c0c] border border-white/10 p-5 rounded-2xl space-y-4 shadow-2xs">
            <h3 className="text-xs font-bold text-white uppercase tracking-wider">Painel de Pendências</h3>

            <div className="bg-blue-950/30 border border-blue-500/20 p-4 rounded-xl flex flex-col justify-between">
              <span className="text-[10px] font-bold text-blue-400 uppercase">A Receber Pendente</span>
              <p className="text-xl font-extrabold text-blue-400 font-mono mt-1">
                R$ {totalReceber.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
              </p>
            </div>

            <div className="bg-rose-950/30 border border-rose-500/20 p-4 rounded-xl flex flex-col justify-between">
              <span className="text-[10px] font-bold text-rose-400 uppercase">A Pagar Pendente</span>
              <p className="text-xl font-extrabold text-rose-400 font-mono mt-1">
                R$ {totalPagar.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
              </p>
            </div>
          </div>

          {/* Vencimentos Próximos */}
          <div className="bg-[#0c0c0c] border border-white/10 p-5 rounded-2xl space-y-3 shadow-2xs">
            <div className="flex items-center justify-between border-b border-white/10 pb-3">
              <h3 className="text-xs font-bold text-white uppercase tracking-wider">Vencimentos Próximos</h3>
              <span className="bg-rose-950/60 text-rose-400 px-2 py-0.5 rounded text-[9px] font-bold uppercase border border-rose-500/30">
                Atenção
              </span>
            </div>

            {pendentes.length === 0 ? (
              <div className="text-xs text-zinc-500 text-center py-6">Nenhum vencimento pendente</div>
            ) : (
              pendentes.slice(0, 4).map((p) => (
                <div key={p.id} className="p-3 bg-zinc-900 border border-white/5 rounded-xl flex items-center justify-between">
                  <div className="truncate pr-2">
                    <p className="text-xs font-bold text-white truncate">{p.descricao}</p>
                    <p className="text-[10px] text-zinc-400 font-mono">
                      Venc: {p.vencimento ? new Date(p.vencimento).toLocaleDateString('pt-BR') : '—'}
                    </p>
                  </div>
                  <span className="text-xs font-mono font-bold text-white">
                    R$ {parseFloat(p.valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                  </span>
                </div>
              ))
            )}
          </div>
        </div>
      </div>

      {/* Modal Novo Lançamento */}
      {modalAberta && (
        <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-zinc-900 border border-zinc-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 text-white">
            <div className="flex items-center justify-between border-b border-zinc-800 pb-3">
              <h3 className="font-bold text-white text-base">Novo Lançamento Financeiro</h3>
              <button onClick={() => setModalAberta(false)} className="text-zinc-400 hover:text-white">
                <span className="material-symbols-outlined leading-none">close</span>
              </button>
            </div>

            <form onSubmit={handleCriarLancamento} className="space-y-3">
              <div className="flex gap-2">
                <button
                  type="button"
                  onClick={() => setTipo('receber')}
                  className={`flex-1 py-2 rounded-xl text-xs font-bold transition ${
                    tipo === 'receber' ? 'bg-emerald-600 text-white' : 'bg-zinc-800 text-zinc-400'
                  }`}
                >
                  Receita (Entrada)
                </button>
                <button
                  type="button"
                  onClick={() => setTipo('pagar')}
                  className={`flex-1 py-2 rounded-xl text-xs font-bold transition ${
                    tipo === 'pagar' ? 'bg-rose-600 text-white' : 'bg-zinc-800 text-zinc-400'
                  }`}
                >
                  Despesa (Saída)
                </button>
              </div>

              <div>
                <label className="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Descrição</label>
                <input
                  type="text"
                  required
                  value={descricao}
                  onChange={(e) => setDescricao(e.target.value)}
                  className="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-xs text-white placeholder-zinc-500 focus:border-white outline-none"
                  placeholder="Ex: Contrato Fotografia Casamento"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Valor (R$)</label>
                  <input
                    type="number"
                    step="0.01"
                    required
                    value={valor}
                    onChange={(e) => setValor(e.target.value)}
                    className="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-xs text-white placeholder-zinc-500 focus:border-white outline-none font-mono"
                    placeholder="0.00"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Vencimento</label>
                  <input
                    type="date"
                    required
                    value={vencimento}
                    onChange={(e) => setVencimento(e.target.value)}
                    className="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-xs text-white focus:border-white outline-none font-mono"
                  />
                </div>
              </div>

              <div>
                <label className="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Cliente / Fornecedor</label>
                <input
                  type="text"
                  value={clienteFornecedor}
                  onChange={(e) => setClienteFornecedor(e.target.value)}
                  className="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-xs text-white placeholder-zinc-500 focus:border-white outline-none"
                  placeholder="Nome do cliente ou fornecedor"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Categoria</label>
                  <select
                    value={categoria}
                    onChange={(e) => setCategoria(e.target.value)}
                    className="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-xs text-white focus:border-white outline-none"
                  >
                    <option value="Serviços">Serviços</option>
                    <option value="Propostas">Propostas</option>
                    <option value="Álbuns">Álbuns</option>
                    <option value="Custos Fixos">Custos Fixos</option>
                    <option value="Outros">Outros</option>
                  </select>
                </div>

                <div>
                  <label className="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Status</label>
                  <select
                    value={status}
                    onChange={(e) => setStatus(e.target.value as any)}
                    className="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-xs text-white focus:border-white outline-none"
                  >
                    <option value="pago">Pago / Baixado</option>
                    <option value="pendente">Pendente</option>
                  </select>
                </div>
              </div>

              <div className="pt-3 flex justify-end space-x-2 border-t border-zinc-800">
                <button
                  type="button"
                  onClick={() => setModalAberta(false)}
                  className="px-4 py-2 text-xs font-bold text-zinc-400 hover:text-white hover:bg-zinc-800 rounded-xl"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={salvando}
                  className="px-4 py-2 bg-white text-black text-xs font-bold rounded-xl hover:bg-zinc-200 transition"
                >
                  {salvando ? 'Salvando...' : 'Salvar Lançamento'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
