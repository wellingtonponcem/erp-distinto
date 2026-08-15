import React, { useEffect, useState } from 'react';

export const DashboardView: React.FC = () => {
  const [lancamentos, setLancamentos] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch('/api/financeiro/lancamentos')
      .then((res) => res.json())
      .then((data) => {
        if (Array.isArray(data)) {
          setLancamentos(data);
        }
      })
      .catch((err) => console.error(err))
      .finally(() => setLoading(false));
  }, []);

  // Cálculos dos KPIs
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

  const saldoAtual = totalRecebido - totalPago;

  return (
    <div className="space-y-6">
      {/* Bento Grid Metrics Header */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Saldo Atual */}
        <div className="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
          <div className="flex items-center justify-between text-gray-500">
            <span className="text-xs font-semibold uppercase tracking-wider">Saldo Atual</span>
            <span className="material-symbols-outlined text-emerald-600 bg-emerald-50 p-2 rounded-xl text-lg">
              account_balance_wallet
            </span>
          </div>
          <div className="mt-4">
            <h3 className="text-2xl font-bold text-gray-900">
              R$ {saldoAtual.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
            </h3>
            <p className="text-xs text-gray-400 mt-1">Saldo em caixa e bancos</p>
          </div>
        </div>

        {/* A Receber */}
        <div className="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
          <div className="flex items-center justify-between text-gray-500">
            <span className="text-xs font-semibold uppercase tracking-wider">A Receber</span>
            <span className="material-symbols-outlined text-blue-600 bg-blue-50 p-2 rounded-xl text-lg">
              south_west
            </span>
          </div>
          <div className="mt-4">
            <h3 className="text-2xl font-bold text-blue-600">
              R$ {totalReceber.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
            </h3>
            <p className="text-xs text-gray-400 mt-1">Pendências de entrada</p>
          </div>
        </div>

        {/* A Pagar */}
        <div className="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
          <div className="flex items-center justify-between text-gray-500">
            <span className="text-xs font-semibold uppercase tracking-wider">A Pagar</span>
            <span className="material-symbols-outlined text-red-600 bg-red-50 p-2 rounded-xl text-lg">
              north_east
            </span>
          </div>
          <div className="mt-4">
            <h3 className="text-2xl font-bold text-red-600">
              R$ {totalPagar.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
            </h3>
            <p className="text-xs text-gray-400 mt-1">Pendências de saída</p>
          </div>
        </div>

        {/* Receitas Realizadas */}
        <div className="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
          <div className="flex items-center justify-between text-gray-500">
            <span className="text-xs font-semibold uppercase tracking-wider">Total Entradas</span>
            <span className="material-symbols-outlined text-emerald-600 bg-emerald-50 p-2 rounded-xl text-lg">
              trending_up
            </span>
          </div>
          <div className="mt-4">
            <h3 className="text-2xl font-bold text-emerald-600">
              R$ {totalRecebido.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
            </h3>
            <p className="text-xs text-gray-400 mt-1">Baixados e liquidados</p>
          </div>
        </div>
      </div>

      {/* Main Transactions Table */}
      <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div className="p-5 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h3 className="font-bold text-gray-900 text-base">Lançamentos Financeiros</h3>
            <p className="text-xs text-gray-400">Ordenados por data de pagamento real e vencimento</p>
          </div>
          <span className="text-xs font-bold bg-gray-100 text-gray-600 px-3 py-1 rounded-full">
            {lancamentos.length} registros
          </span>
        </div>

        {loading ? (
          <div className="p-8 text-center text-sm text-gray-400">Carregando dados financeiros...</div>
        ) : lancamentos.length === 0 ? (
          <div className="p-8 text-center text-sm text-gray-400">Nenhum lançamento cadastrado ainda.</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs font-sans">
              <thead className="bg-gray-50 text-gray-500 uppercase tracking-wider font-semibold border-b border-gray-100">
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
                {lancamentos.slice(0, 15).map((item) => {
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
                      <td className="py-3 px-4 font-semibold text-gray-900">{item.descricao}</td>
                      <td className="py-3 px-4 text-gray-600">{item.cliente_fornecedor || '—'}</td>
                      <td className="py-3 px-4 text-gray-600">
                        {item.vencimento ? new Date(item.vencimento).toLocaleDateString('pt-BR') : '—'}
                      </td>
                      <td className="py-3 px-4 text-gray-600">
                        {item.data_pagamento ? new Date(item.data_pagamento).toLocaleDateString('pt-BR') : '—'}
                      </td>
                      <td className="py-3 px-4 font-bold text-gray-900">
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
  );
};
