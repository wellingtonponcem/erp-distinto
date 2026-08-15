import React, { useEffect, useState } from 'react';

export const DashboardView: React.FC = () => {
  const [lancamentos, setLancamentos] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [saldoAsaas, setSaldoAsaas] = useState<number | null>(null);
  const [asaasConfigurado, setAsaasConfigurado] = useState<boolean>(false);

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

  // Calculations
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
    <div className="space-y-6 font-sans text-gray-900">
      {/* Header Actions */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 tracking-tight">Dashboard</h1>
          <p className="text-xs text-gray-500 mt-1 font-medium">Resumo financeiro e operacional da sua agência</p>
        </div>

        <div className="flex items-center space-x-3">
          {lancamentos.length === 0 && (
            <button
              onClick={handleSeed}
              className="px-4 py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200/60 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-xs"
            >
              <span className="material-symbols-outlined text-sm leading-none">auto_fix_high</span>
              <span>Popular Dados Iniciais</span>
            </button>
          )}

          <button
            onClick={() => setModalAberta(true)}
            className="px-4 py-2 bg-black text-white hover:bg-gray-800 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-sm"
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
            <div className="bg-white border border-gray-200/80 p-5 rounded-2xl flex flex-col justify-between h-32 hover:border-black/30 transition shadow-2xs group">
              <span className="text-[11px] font-bold uppercase tracking-wider text-gray-400">Clientes Cadastrados</span>
              <div className="flex items-baseline justify-between mt-auto">
                <span className="text-3xl font-extrabold text-black group-hover:scale-105 transition-transform font-sans">
                  3
                </span>
                <span className="material-symbols-outlined text-gray-300 text-3xl leading-none">groups</span>
              </div>
            </div>

            {/* Fornecedores */}
            <div className="bg-white border border-gray-200/80 p-5 rounded-2xl flex flex-col justify-between h-32 hover:border-black/30 transition shadow-2xs group">
              <span className="text-[11px] font-bold uppercase tracking-wider text-gray-400">Fornecedores</span>
              <div className="flex items-baseline justify-between mt-auto">
                <span className="text-3xl font-extrabold text-gray-900 group-hover:scale-105 transition-transform font-sans">
                  2
                </span>
                <span className="material-symbols-outlined text-gray-300 text-3xl leading-none">inventory_2</span>
              </div>
            </div>

            {/* Oportunidades */}
            <div className="bg-white border border-gray-200/80 p-5 rounded-2xl flex flex-col justify-between h-32 hover:border-black/30 transition shadow-2xs group">
              <span className="text-[11px] font-bold uppercase tracking-wider text-gray-400">Oportunidades</span>
              <div className="flex items-baseline justify-between mt-auto">
                <span className="text-3xl font-extrabold text-amber-600 group-hover:scale-105 transition-transform font-sans">
                  4
                </span>
                <span className="material-symbols-outlined text-amber-200 text-3xl leading-none">rocket_launch</span>
              </div>
            </div>

            {/* Total Propostas */}
            <div className="bg-white border border-gray-200/80 border-l-4 border-l-black p-5 rounded-2xl flex flex-col justify-between h-32 hover:border-black/30 transition shadow-2xs group">
              <span className="text-[11px] font-bold uppercase tracking-wider text-gray-400">Total Propostas</span>
              <div className="flex items-baseline justify-between mt-auto">
                <span className="text-3xl font-extrabold text-gray-900 group-hover:scale-105 transition-transform font-sans">
                  5
                </span>
                <span className="material-symbols-outlined text-gray-300 text-3xl leading-none">request_quote</span>
              </div>
            </div>
          </div>

          {/* Cash Flow Card */}
          <div className="bg-white border border-gray-200/80 p-6 rounded-2xl shadow-2xs space-y-4">
            <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b border-gray-100 pb-4">
              <div>
                <div className="flex items-center space-x-2">
                  <h3 className="text-base font-bold text-gray-900">Fluxo de Caixa Mensal</h3>
                  <span className="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded text-[10px] font-bold uppercase border border-emerald-200/60">
                    {asaasConfigurado ? 'ASAAS LIVE' : 'CAIXA LOCAL'}
                  </span>
                </div>
                <p className="text-xs text-gray-400 mt-0.5">Visão consolidada de entradas e liquidações</p>
              </div>

              <div className="text-right">
                <span className="text-[11px] font-bold uppercase tracking-wider text-gray-400 block">Saldo em Conta</span>
                <span className="text-2xl font-extrabold text-gray-900 font-mono">
                  R$ {exibeSaldo.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                </span>
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div className="bg-emerald-50/50 border border-emerald-100 p-4 rounded-xl flex items-center justify-between">
                <div>
                  <span className="text-[10px] font-bold uppercase tracking-wider text-emerald-800">Entradas Baixadas</span>
                  <p className="text-lg font-extrabold text-emerald-600 font-mono mt-0.5">
                    + R$ {totalRecebido.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                  </p>
                </div>
                <span className="material-symbols-outlined text-emerald-500 text-2xl leading-none">south_west</span>
              </div>

              <div className="bg-red-50/50 border border-red-100 p-4 rounded-xl flex items-center justify-between">
                <div>
                  <span className="text-[10px] font-bold uppercase tracking-wider text-red-800">Saídas Liquidadas</span>
                  <p className="text-lg font-extrabold text-red-600 font-mono mt-0.5">
                    - R$ {totalPago.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                  </p>
                </div>
                <span className="material-symbols-outlined text-red-500 text-2xl leading-none">north_east</span>
              </div>
            </div>
          </div>

          {/* Transactions Table */}
          <div className="bg-white border border-gray-200/80 rounded-2xl overflow-hidden shadow-2xs">
            <div className="p-5 border-b border-gray-100 flex items-center justify-between">
              <div>
                <h3 className="font-bold text-gray-900 text-sm">Lançamentos Financeiros</h3>
                <p className="text-xs text-gray-400">Ordenados por data de pagamento real e vencimento</p>
              </div>
              <span className="text-xs font-bold bg-gray-100 text-gray-600 px-3 py-1 rounded-full font-mono">
                {lancamentos.length} registros
              </span>
            </div>

            {loading ? (
              <div className="p-8 text-center text-xs text-gray-400">Carregando lançamentos...</div>
            ) : lancamentos.length === 0 ? (
              <div className="p-12 text-center">
                <span className="material-symbols-outlined text-4xl text-gray-300 mb-2 leading-none">receipt_long</span>
                <p className="text-sm font-bold text-gray-800">Nenhum lançamento cadastrado ainda.</p>
                <button
                  onClick={handleSeed}
                  className="mt-4 px-4 py-2 bg-black text-white rounded-xl text-xs font-bold hover:bg-gray-800 transition"
                >
                  Carregar Dados Iniciais
                </button>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs font-sans">
                  <thead className="bg-gray-50 text-gray-500 uppercase tracking-wider font-bold text-[10px] border-b border-gray-100">
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
                  <tbody className="divide-y divide-gray-100">
                    {lancamentos.map((item) => {
                      const isReceber = item.tipo === 'receber';
                      const valorNum = parseFloat(item.valor || 0);

                      return (
                        <tr key={item.id} className="hover:bg-gray-50/80 transition">
                          <td className="py-3 px-4">
                            <span
                              className={`inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase ${
                                isReceber ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'
                              }`}
                            >
                              {item.tipo}
                            </span>
                          </td>
                          <td className="py-3 px-4 font-bold text-gray-900">{item.descricao}</td>
                          <td className="py-3 px-4 text-gray-600">{item.cliente_fornecedor || '—'}</td>
                          <td className="py-3 px-4 text-gray-600 font-mono">
                            {item.vencimento ? new Date(item.vencimento).toLocaleDateString('pt-BR') : '—'}
                          </td>
                          <td className="py-3 px-4 text-gray-600 font-mono">
                            {item.data_pagamento ? new Date(item.data_pagamento).toLocaleDateString('pt-BR') : '—'}
                          </td>
                          <td className="py-3 px-4 font-mono font-bold text-gray-900">
                            R$ {valorNum.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                          </td>
                          <td className="py-3 px-4">
                            <span
                              className={`inline-block px-2.5 py-1 rounded-full text-[10px] font-bold uppercase ${
                                item.status === 'pago'
                                  ? 'bg-emerald-100 text-emerald-800'
                                  : item.status === 'atrasado'
                                  ? 'bg-red-100 text-red-800'
                                  : 'bg-amber-100 text-amber-800'
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
          <div className="bg-white border border-gray-200/80 p-5 rounded-2xl space-y-4 shadow-2xs">
            <h3 className="text-xs font-bold text-gray-900 uppercase tracking-wider">Painel de Pendências</h3>

            <div className="bg-blue-50/50 border border-blue-100 p-4 rounded-xl flex flex-col justify-between">
              <span className="text-[10px] font-bold text-blue-700 uppercase">A Receber Pendente</span>
              <p className="text-xl font-extrabold text-blue-900 font-mono mt-1">
                R$ {totalReceber.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
              </p>
            </div>

            <div className="bg-red-50/50 border border-red-100 p-4 rounded-xl flex flex-col justify-between">
              <span className="text-[10px] font-bold text-red-700 uppercase">A Pagar Pendente</span>
              <p className="text-xl font-extrabold text-red-900 font-mono mt-1">
                R$ {totalPagar.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
              </p>
            </div>
          </div>

          {/* Vencimentos Próximos */}
          <div className="bg-white border border-gray-200/80 p-5 rounded-2xl space-y-3 shadow-2xs">
            <div className="flex items-center justify-between border-b border-gray-100 pb-3">
              <h3 className="text-xs font-bold text-gray-900 uppercase tracking-wider">Vencimentos Próximos</h3>
              <span className="bg-red-50 text-red-600 px-2 py-0.5 rounded text-[9px] font-bold uppercase">
                Atenção
              </span>
            </div>

            {pendentes.length === 0 ? (
              <div className="text-xs text-gray-400 text-center py-6">Nenhum vencimento pendente</div>
            ) : (
              pendentes.slice(0, 4).map((p) => (
                <div key={p.id} className="p-3 bg-gray-50 border border-gray-100 rounded-xl flex items-center justify-between">
                  <div className="truncate pr-2">
                    <p className="text-xs font-bold text-gray-900 truncate">{p.descricao}</p>
                    <p className="text-[10px] text-gray-400 font-mono">
                      Venc: {p.vencimento ? new Date(p.vencimento).toLocaleDateString('pt-BR') : '—'}
                    </p>
                  </div>
                  <span className="text-xs font-mono font-bold text-gray-900">
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
        <div className="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
            <div className="flex items-center justify-between border-b border-gray-100 pb-3">
              <h3 className="font-bold text-gray-900 text-base">Novo Lançamento Financeiro</h3>
              <button onClick={() => setModalAberta(false)} className="text-gray-400 hover:text-gray-600">
                <span className="material-symbols-outlined leading-none">close</span>
              </button>
            </div>

            <form onSubmit={handleCriarLancamento} className="space-y-3">
              <div className="flex gap-2">
                <button
                  type="button"
                  onClick={() => setTipo('receber')}
                  className={`flex-1 py-2 rounded-xl text-xs font-bold transition ${
                    tipo === 'receber' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600'
                  }`}
                >
                  Receita (Entrada)
                </button>
                <button
                  type="button"
                  onClick={() => setTipo('pagar')}
                  className={`flex-1 py-2 rounded-xl text-xs font-bold transition ${
                    tipo === 'pagar' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600'
                  }`}
                >
                  Despesa (Saída)
                </button>
              </div>

              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Descrição</label>
                <input
                  type="text"
                  required
                  value={descricao}
                  onChange={(e) => setDescricao(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none"
                  placeholder="Ex: Contrato Fotografia Casamento"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Valor (R$)</label>
                  <input
                    type="number"
                    step="0.01"
                    required
                    value={valor}
                    onChange={(e) => setValor(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none font-mono"
                    placeholder="0.00"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Vencimento</label>
                  <input
                    type="date"
                    required
                    value={vencimento}
                    onChange={(e) => setVencimento(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none font-mono"
                  />
                </div>
              </div>

              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Cliente / Fornecedor</label>
                <input
                  type="text"
                  value={clienteFornecedor}
                  onChange={(e) => setClienteFornecedor(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none"
                  placeholder="Nome do cliente ou fornecedor"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Categoria</label>
                  <select
                    value={categoria}
                    onChange={(e) => setCategoria(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none"
                  >
                    <option value="Serviços">Serviços</option>
                    <option value="Propostas">Propostas</option>
                    <option value="Álbuns">Álbuns</option>
                    <option value="Custos Fixos">Custos Fixos</option>
                    <option value="Outros">Outros</option>
                  </select>
                </div>

                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Status</label>
                  <select
                    value={status}
                    onChange={(e) => setStatus(e.target.value as any)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none"
                  >
                    <option value="pago">Pago / Baixado</option>
                    <option value="pendente">Pendente</option>
                  </select>
                </div>
              </div>

              <div className="pt-3 flex justify-end space-x-2">
                <button
                  type="button"
                  onClick={() => setModalAberta(false)}
                  className="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={salvando}
                  className="px-4 py-2 bg-black text-white text-xs font-bold rounded-xl hover:bg-gray-800 transition"
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
