import React, { useEffect, useState } from 'react';

export const DashboardView: React.FC = () => {
  const [lancamentos, setLancamentos] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [saldoAsaas, setSaldoAsaas] = useState<number | null>(null);

  // Estado do Modal de Novo Lançamento
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

  // Cálculos do Dashboard Otimizado (Stitch System)
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

  // Lançamentos pendentes e próximos vencimentos
  const pendentes = lancamentos.filter((l) => l.status === 'pendente' || l.status === 'atrasado');

  return (
    <div className="space-y-6 text-[#e4e1e6]">
      {/* Page Header (Design System Stitch) */}
      <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold tracking-tight text-white font-sans">Dashboard Otimizado</h1>
          <p className="text-xs text-[#938ea1] mt-1 font-mono uppercase tracking-wider">
            Resumo financeiro e operacional • Stitch Design System
          </p>
        </div>

        <div className="flex items-center space-x-3">
          {lancamentos.length === 0 && (
            <button
              onClick={handleSeed}
              className="px-4 py-2 bg-[#947dff]/20 text-[#cabeff] border border-[#947dff]/40 rounded-xl text-xs font-bold hover:bg-[#947dff]/30 transition flex items-center space-x-2"
            >
              <span className="material-symbols-outlined text-sm">auto_fix_high</span>
              <span>Popular Dados</span>
            </button>
          )}

          <button
            onClick={() => setModalAberta(true)}
            className="px-4 py-2 bg-[#947dff] text-[#2a0088] rounded-xl text-xs font-bold hover:bg-[#cabeff] transition flex items-center space-x-2 shadow-lg shadow-[#947dff]/20"
          >
            <span className="material-symbols-outlined text-sm">add</span>
            <span>Novo Lançamento</span>
          </button>
        </div>
      </div>

      {/* Grid Bento Style (Stitch Redesign System) */}
      <div className="grid grid-cols-12 gap-4">
        {/* Coluna Principal (9 Colunas) */}
        <div className="col-span-12 lg:col-span-9 space-y-4">
          {/* Top Metric Cards */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {/* Clientes Cadastrados */}
            <div className="bg-[#19191c] border border-[#2d2d39] p-5 rounded-2xl flex flex-col justify-between hover:border-[#947dff]/60 transition group">
              <span className="text-[11px] font-mono uppercase tracking-wider text-[#938ea1]">Clientes Cadastrados</span>
              <div className="flex items-baseline justify-between mt-4">
                <span className="text-3xl font-bold text-[#cabeff] group-hover:scale-105 transition-transform">
                  3
                </span>
                <span className="material-symbols-outlined text-[#947dff]/40 text-3xl">groups</span>
              </div>
            </div>

            {/* Fornecedores */}
            <div className="bg-[#19191c] border border-[#2d2d39] p-5 rounded-2xl flex flex-col justify-between hover:border-[#947dff]/60 transition group">
              <span className="text-[11px] font-mono uppercase tracking-wider text-[#938ea1]">Fornecedores</span>
              <div className="flex items-baseline justify-between mt-4">
                <span className="text-3xl font-bold text-white group-hover:scale-105 transition-transform">
                  2
                </span>
                <span className="material-symbols-outlined text-[#484555] text-3xl">inventory_2</span>
              </div>
            </div>

            {/* Oportunidades CRM */}
            <div className="bg-[#19191c] border border-[#2d2d39] p-5 rounded-2xl flex flex-col justify-between hover:border-[#ffb780]/60 transition group">
              <span className="text-[11px] font-mono uppercase tracking-wider text-[#938ea1]">Oportunidades</span>
              <div className="flex items-baseline justify-between mt-4">
                <span className="text-3xl font-bold text-[#ffb780] group-hover:scale-105 transition-transform">
                  4
                </span>
                <span className="material-symbols-outlined text-[#ffb780]/40 text-3xl">rocket_launch</span>
              </div>
            </div>

            {/* Total Propostas */}
            <div className="bg-[#19191c] border border-[#2d2d39] border-l-4 border-l-[#947dff] p-5 rounded-2xl flex flex-col justify-between hover:border-[#947dff]/60 transition group">
              <span className="text-[11px] font-mono uppercase tracking-wider text-[#938ea1]">Total Propostas</span>
              <div className="flex items-baseline justify-between mt-4">
                <span className="text-3xl font-bold text-white group-hover:scale-105 transition-transform">
                  5
                </span>
                <span className="material-symbols-outlined text-[#947dff]/40 text-3xl">request_quote</span>
              </div>
            </div>
          </div>

          {/* Cash Flow Luminous Gradient Box */}
          <div className="bg-gradient-to-r from-[#19191c] via-[#241f3d] to-[#19191c] border border-[#947dff]/30 p-6 rounded-2xl relative overflow-hidden shadow-xl">
            <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
              <div>
                <div className="flex items-center space-x-2 mb-2">
                  <h3 className="text-lg font-bold text-white">Fluxo de Caixa Mensal</h3>
                  <span className="bg-[#947dff]/20 text-[#cabeff] px-2 py-0.5 rounded text-[10px] font-mono font-bold">
                    LIVE
                  </span>
                </div>
                <p className="text-xs text-[#c9c4d8]">Saldo em tempo real integrado ao sistema</p>
                <div className="mt-4">
                  <span className="text-3xl font-bold text-[#cabeff] font-mono">
                    R$ {exibeSaldo.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                  </span>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4 w-full md:w-auto">
                <div className="bg-[#131316]/80 border border-[#2d2d39] p-4 rounded-xl">
                  <span className="text-[10px] font-mono text-[#938ea1] uppercase">Entradas Baixadas</span>
                  <p className="text-lg font-bold text-[#10b981] mt-1 font-mono">
                    + R$ {totalRecebido.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                  </p>
                </div>
                <div className="bg-[#131316]/80 border border-[#2d2d39] p-4 rounded-xl">
                  <span className="text-[10px] font-mono text-[#938ea1] uppercase">Saídas Liquidadas</span>
                  <p className="text-lg font-bold text-[#ffb4ab] mt-1 font-mono">
                    - R$ {totalPago.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                  </p>
                </div>
              </div>
            </div>
          </div>

          {/* Tabela de Lançamentos */}
          <div className="bg-[#19191c] border border-[#2d2d39] rounded-2xl overflow-hidden shadow-sm">
            <div className="p-5 border-b border-[#2d2d39] flex items-center justify-between">
              <div>
                <h3 className="font-bold text-white text-base">Lançamentos Financeiros</h3>
                <p className="text-xs text-[#938ea1]">Ordenados por data de pagamento real e vencimento</p>
              </div>
              <span className="text-xs font-mono font-bold bg-[#2a2a2d] text-[#c9c4d8] px-3 py-1 rounded-full">
                {lancamentos.length} registros
              </span>
            </div>

            {loading ? (
              <div className="p-8 text-center text-sm text-[#938ea1]">Carregando dados financeiros...</div>
            ) : lancamentos.length === 0 ? (
              <div className="p-12 text-center">
                <span className="material-symbols-outlined text-4xl text-[#484555] mb-2">receipt_long</span>
                <p className="text-sm font-semibold text-white">Nenhum lançamento cadastrado ainda.</p>
                <button
                  onClick={handleSeed}
                  className="mt-4 px-4 py-2 bg-[#947dff] text-[#2a0088] rounded-xl text-xs font-bold hover:bg-[#cabeff] transition"
                >
                  Carregar Dados Iniciais
                </button>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs font-sans">
                  <thead className="bg-[#131316] text-[#938ea1] uppercase font-mono tracking-wider border-b border-[#2d2d39]">
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
                  <tbody className="divide-y divide-[#2d2d39]">
                    {lancamentos.map((item) => {
                      const isReceber = item.tipo === 'receber';
                      const valorNum = parseFloat(item.valor || 0);

                      return (
                        <tr key={item.id} className="hover:bg-[#232328] transition">
                          <td className="py-3 px-4">
                            <span
                              className={`inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase ${
                                isReceber ? 'bg-[#10b981]/15 text-[#10b981]' : 'bg-[#ffb4ab]/15 text-[#ffb4ab]'
                              }`}
                            >
                              {item.tipo}
                            </span>
                          </td>
                          <td className="py-3 px-4 font-semibold text-white">{item.descricao}</td>
                          <td className="py-3 px-4 text-[#c9c4d8]">{item.cliente_fornecedor || '—'}</td>
                          <td className="py-3 px-4 text-[#c9c4d8] font-mono">
                            {item.vencimento ? new Date(item.vencimento).toLocaleDateString('pt-BR') : '—'}
                          </td>
                          <td className="py-3 px-4 text-[#c9c4d8] font-mono">
                            {item.data_pagamento ? new Date(item.data_pagamento).toLocaleDateString('pt-BR') : '—'}
                          </td>
                          <td className="py-3 px-4 font-mono font-bold text-white">
                            R$ {valorNum.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                          </td>
                          <td className="py-3 px-4">
                            <span
                              className={`inline-block px-2.5 py-1 rounded-full text-[10px] font-mono font-bold uppercase ${
                                item.status === 'pago'
                                  ? 'bg-[#10b981]/20 text-[#10b981]'
                                  : item.status === 'atrasado'
                                  ? 'bg-[#ffb4ab]/20 text-[#ffb4ab]'
                                  : 'bg-[#ffb780]/20 text-[#ffb780]'
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

        {/* Coluna Lateral (3 Colunas - Painel de Pendências & Vencimentos) */}
        <div className="col-span-12 lg:col-span-3 space-y-4">
          {/* Painel de Pendências */}
          <div className="bg-[#19191c] border border-[#2d2d39] p-5 rounded-2xl space-y-4">
            <h3 className="text-sm font-bold text-white uppercase tracking-wider font-mono">Painel de Pendências</h3>

            {/* A Receber Pendente */}
            <div className="bg-[#131316] border border-[#2d2d39] p-4 rounded-xl flex flex-col justify-between relative overflow-hidden">
              <span className="text-[10px] font-mono text-[#947dff] uppercase font-bold">A Receber Pendente</span>
              <p className="text-2xl font-bold text-white font-mono mt-2">
                R$ {totalReceber.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
              </p>
            </div>

            {/* A Pagar Pendente */}
            <div className="bg-[#131316] border border-[#2d2d39] p-4 rounded-xl flex flex-col justify-between relative overflow-hidden">
              <span className="text-[10px] font-mono text-[#ffb4ab] uppercase font-bold">A Pagar Pendente</span>
              <p className="text-2xl font-bold text-[#ffb4ab] font-mono mt-2">
                R$ {totalPagar.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
              </p>
            </div>
          </div>

          {/* Vencimentos Próximos */}
          <div className="bg-[#19191c] border border-[#2d2d39] p-5 rounded-2xl space-y-3">
            <div className="flex items-center justify-between mb-2">
              <h3 className="text-sm font-bold text-white uppercase tracking-wider font-mono">Vencimentos</h3>
              <span className="bg-[#ffb4ab]/20 text-[#ffb4ab] px-2 py-0.5 rounded text-[9px] font-mono font-bold">
                ATENÇÃO
              </span>
            </div>

            {pendentes.length === 0 ? (
              <div className="text-xs text-[#938ea1] text-center py-6">Nenhum vencimento pendente</div>
            ) : (
              pendentes.slice(0, 4).map((p) => (
                <div key={p.id} className="p-3 bg-[#131316] border border-[#2d2d39] rounded-xl flex items-center justify-between">
                  <div className="truncate pr-2">
                    <p className="text-xs font-bold text-white truncate">{p.descricao}</p>
                    <p className="text-[10px] text-[#938ea1] font-mono">
                      Venc: {p.vencimento ? new Date(p.vencimento).toLocaleDateString('pt-BR') : '—'}
                    </p>
                  </div>
                  <span className="text-xs font-mono font-bold text-[#cabeff]">
                    R$ {parseFloat(p.valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                  </span>
                </div>
              ))
            )}
          </div>
        </div>
      </div>

      {/* Modal de Novo Lançamento */}
      {modalAberta && (
        <div className="fixed inset-0 bg-black/70 backdrop-blur-md z-50 flex items-center justify-center p-4">
          <div className="bg-[#19191c] border border-[#2d2d39] rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 text-white">
            <div className="flex items-center justify-between border-b border-[#2d2d39] pb-3">
              <h3 className="font-bold text-white text-base">Novo Lançamento Financeiro</h3>
              <button onClick={() => setModalAberta(false)} className="text-[#938ea1] hover:text-white">
                <span className="material-symbols-outlined">close</span>
              </button>
            </div>

            <form onSubmit={handleCriarLancamento} className="space-y-3">
              <div className="flex gap-2">
                <button
                  type="button"
                  onClick={() => setTipo('receber')}
                  className={`flex-1 py-2 rounded-xl text-xs font-bold font-mono transition ${
                    tipo === 'receber' ? 'bg-[#10b981] text-white' : 'bg-[#2a2a2d] text-[#c9c4d8]'
                  }`}
                >
                  Receita (Entrada)
                </button>
                <button
                  type="button"
                  onClick={() => setTipo('pagar')}
                  className={`flex-1 py-2 rounded-xl text-xs font-bold font-mono transition ${
                    tipo === 'pagar' ? 'bg-[#ffb4ab] text-[#690005]' : 'bg-[#2a2a2d] text-[#c9c4d8]'
                  }`}
                >
                  Despesa (Saída)
                </button>
              </div>

              <div>
                <label className="block text-[10px] font-mono uppercase text-[#938ea1] mb-1">Descrição</label>
                <input
                  type="text"
                  required
                  value={descricao}
                  onChange={(e) => setDescricao(e.target.value)}
                  className="w-full px-3 py-2 bg-[#131316] border border-[#2d2d39] rounded-xl text-xs focus:ring-2 focus:ring-[#947dff] outline-none text-white"
                  placeholder="Ex: Contrato Fotografia Casamento"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-mono uppercase text-[#938ea1] mb-1">Valor (R$)</label>
                  <input
                    type="number"
                    step="0.01"
                    required
                    value={valor}
                    onChange={(e) => setValor(e.target.value)}
                    className="w-full px-3 py-2 bg-[#131316] border border-[#2d2d39] rounded-xl text-xs focus:ring-2 focus:ring-[#947dff] outline-none text-white font-mono"
                    placeholder="0.00"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-mono uppercase text-[#938ea1] mb-1">Vencimento</label>
                  <input
                    type="date"
                    required
                    value={vencimento}
                    onChange={(e) => setVencimento(e.target.value)}
                    className="w-full px-3 py-2 bg-[#131316] border border-[#2d2d39] rounded-xl text-xs focus:ring-2 focus:ring-[#947dff] outline-none text-white font-mono"
                  />
                </div>
              </div>

              <div>
                <label className="block text-[10px] font-mono uppercase text-[#938ea1] mb-1">Cliente / Fornecedor</label>
                <input
                  type="text"
                  value={clienteFornecedor}
                  onChange={(e) => setClienteFornecedor(e.target.value)}
                  className="w-full px-3 py-2 bg-[#131316] border border-[#2d2d39] rounded-xl text-xs focus:ring-2 focus:ring-[#947dff] outline-none text-white"
                  placeholder="Nome do cliente ou fornecedor"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-mono uppercase text-[#938ea1] mb-1">Categoria</label>
                  <select
                    value={categoria}
                    onChange={(e) => setCategoria(e.target.value)}
                    className="w-full px-3 py-2 bg-[#131316] border border-[#2d2d39] rounded-xl text-xs focus:ring-2 focus:ring-[#947dff] outline-none text-white"
                  >
                    <option value="Serviços">Serviços</option>
                    <option value="Propostas">Propostas</option>
                    <option value="Álbuns">Álbuns</option>
                    <option value="Custos Fixos">Custos Fixos</option>
                    <option value="Outros">Outros</option>
                  </select>
                </div>

                <div>
                  <label className="block text-[10px] font-mono uppercase text-[#938ea1] mb-1">Status</label>
                  <select
                    value={status}
                    onChange={(e) => setStatus(e.target.value as any)}
                    className="w-full px-3 py-2 bg-[#131316] border border-[#2d2d39] rounded-xl text-xs focus:ring-2 focus:ring-[#947dff] outline-none text-white"
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
                  className="px-4 py-2 text-xs font-bold text-[#938ea1] hover:bg-[#2d2d39] rounded-xl"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={salvando}
                  className="px-4 py-2 bg-[#947dff] text-[#2a0088] text-xs font-bold rounded-xl hover:bg-[#cabeff] transition font-mono"
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
