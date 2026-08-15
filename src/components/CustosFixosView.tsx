import React, { useEffect, useState } from 'react';

export const CustosFixosView: React.FC = () => {
  const [custos, setCustos] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalAberta, setModalAberta] = useState(false);

  const [nome, setNome] = useState('');
  const [valor, setValor] = useState('');
  const [categoria, setCategoria] = useState('Custos Fixos');
  const [diaVencimento, setDiaVencimento] = useState('5');
  const [formaPagamento, setFormaPagamento] = useState('pix');
  const [salvando, setSalvando] = useState(false);

  const carregarCustos = () => {
    setLoading(true);
    fetch('/api/financeiro/custos-fixos')
      .then((res) => res.json())
      .then((data) => {
        if (Array.isArray(data)) setCustos(data);
      })
      .catch((err) => console.error(err))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    carregarCustos();
  }, []);

  const handleCriar = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!nome || !valor) return;
    setSalvando(true);

    try {
      const res = await fetch('/api/financeiro/custos-fixos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          nome,
          valor: parseFloat(valor),
          categoria,
          recorrencia: 'mensal',
          dia_vencimento: parseInt(diaVencimento),
          forma_pagamento: formaPagamento,
        }),
      });

      if (res.ok) {
        setModalAberta(false);
        setNome('');
        setValor('');
        carregarCustos();
      }
    } catch (e) {
      console.error(e);
    } finally {
      setSalvando(false);
    }
  };

  const handleExcluir = async (id: string) => {
    if (!confirm('Deseja remover este custo fixo?')) return;
    await fetch(`/api/financeiro/custos-fixos?id=${id}`, { method: 'DELETE' });
    carregarCustos();
  };

  const totalCustosFixos = custos.reduce((acc, c) => acc + parseFloat(c.valor || 0), 0);

  return (
    <div className="space-y-6 font-sans">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-xl font-bold text-gray-900 tracking-tight">Custos Fixos Recorrentes</h2>
          <p className="text-xs text-gray-500 mt-1">Despesas mensais recorrentes da empresa (Aluguel, Licenças, Servidores)</p>
        </div>

        <button
          onClick={() => setModalAberta(true)}
          className="px-4 py-2 bg-black text-white hover:bg-gray-800 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-sm"
        >
          <span className="material-symbols-outlined text-sm leading-none">add</span>
          <span>Novo Custo Fixo</span>
        </button>
      </div>

      {/* Header Metric Card */}
      <div className="bg-white border border-gray-200/80 p-5 rounded-2xl shadow-2xs flex items-center justify-between">
        <div>
          <span className="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total de Custos Fixos / Mês</span>
          <h3 className="text-2xl font-extrabold text-red-600 font-mono mt-1">
            R$ {totalCustosFixos.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
          </h3>
        </div>
        <div className="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
          <span className="material-symbols-outlined text-xl leading-none">calculate</span>
        </div>
      </div>

      {loading ? (
        <div className="p-8 text-center text-xs text-gray-400">Carregando custos fixos...</div>
      ) : custos.length === 0 ? (
        <div className="bg-white p-12 rounded-2xl border border-gray-200/80 text-center">
          <span className="material-symbols-outlined text-4xl text-gray-300 mb-2 leading-none">calculate</span>
          <p className="text-sm font-bold text-gray-800">Nenhum custo fixo cadastrado.</p>
          <button
            onClick={() => setModalAberta(true)}
            className="mt-4 px-4 py-2 bg-black text-white rounded-xl text-xs font-bold hover:bg-gray-800 transition"
          >
            + Cadastrar Custo Fixo
          </button>
        </div>
      ) : (
        <div className="bg-white border border-gray-200/80 rounded-2xl overflow-hidden shadow-2xs">
          <table className="w-full text-left text-xs font-sans">
            <thead className="bg-gray-50 text-gray-500 uppercase tracking-wider font-bold text-[10px] border-b border-gray-100">
              <tr>
                <th className="py-3 px-4">Nome do Custo</th>
                <th className="py-3 px-4">Categoria</th>
                <th className="py-3 px-4">Dia Vencimento</th>
                <th className="py-3 px-4">Forma Pagamento</th>
                <th className="py-3 px-4">Valor Mensal</th>
                <th className="py-3 px-4 text-right">Ação</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {custos.map((c) => (
                <tr key={c.id} className="hover:bg-gray-50/80 transition">
                  <td className="py-3 px-4 font-bold text-gray-900">{c.nome}</td>
                  <td className="py-3 px-4 text-gray-600">{c.categoria}</td>
                  <td className="py-3 px-4 text-gray-600 font-mono">Dia {c.dia_vencimento || 5}</td>
                  <td className="py-3 px-4 text-gray-600 uppercase font-mono">{c.forma_pagamento || 'pix'}</td>
                  <td className="py-3 px-4 font-mono font-bold text-red-600">
                    R$ {parseFloat(c.valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                  </td>
                  <td className="py-3 px-4 text-right">
                    <button
                      onClick={() => handleExcluir(c.id)}
                      className="text-gray-300 hover:text-red-600 transition"
                    >
                      <span className="material-symbols-outlined text-sm leading-none">delete</span>
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Modal Novo Custo Fixo */}
      {modalAberta && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
            <div className="flex items-center justify-between border-b border-gray-100 pb-3">
              <h3 className="font-bold text-gray-900 text-base">Novo Custo Fixo Recorrente</h3>
              <button onClick={() => setModalAberta(false)} className="text-gray-400 hover:text-gray-600">
                <span className="material-symbols-outlined leading-none">close</span>
              </button>
            </div>

            <form onSubmit={handleCriar} className="space-y-3">
              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Nome da Despesa</label>
                <input
                  type="text"
                  required
                  value={nome}
                  onChange={(e) => setNome(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none"
                  placeholder="Ex: Aluguel do Estúdio, Licença Adobe, Servidor Neon"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Valor Mensal (R$)</label>
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
                  <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Dia Vencimento</label>
                  <input
                    type="number"
                    min="1"
                    max="31"
                    value={diaVencimento}
                    onChange={(e) => setDiaVencimento(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none font-mono"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Categoria</label>
                  <select
                    value={categoria}
                    onChange={(e) => setCategoria(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none"
                  >
                    <option value="Custos Fixos">Custos Fixos</option>
                    <option value="Infraestrutura">Infraestrutura</option>
                    <option value="Softwares">Softwares</option>
                    <option value="Marketing">Marketing</option>
                  </select>
                </div>

                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Forma Pagamento</label>
                  <select
                    value={formaPagamento}
                    onChange={(e) => setFormaPagamento(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none"
                  >
                    <option value="pix">PIX</option>
                    <option value="boleto">Boleto Bancário</option>
                    <option value="cartao">Cartão de Crédito</option>
                    <option value="debito_automatico">Débito Automático</option>
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
                  {salvando ? 'Salvando...' : 'Salvar Custo Fixo'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
