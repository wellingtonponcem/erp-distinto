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
    <div className="space-y-6 font-sans text-white bg-[#050505] min-h-screen">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-xl font-bold text-white tracking-tight">Custos Fixos Recorrentes</h2>
          <p className="text-xs text-zinc-400 mt-1">Despesas mensais recorrentes da empresa (Aluguel, Licenças, Servidores)</p>
        </div>

        <button
          onClick={() => setModalAberta(true)}
          className="px-4 py-2 bg-white text-black hover:bg-zinc-200 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-sm"
        >
          <span className="material-symbols-outlined text-sm leading-none">add</span>
          <span>Novo Custo Fixo</span>
        </button>
      </div>

      {/* Header Metric Card */}
      <div className="bg-[#0c0c0c] border border-white/10 p-5 rounded-2xl shadow-2xs flex items-center justify-between">
        <div>
          <span className="text-[11px] font-bold text-zinc-400 uppercase tracking-wider">Total de Custos Fixos / Mês</span>
          <h3 className="text-2xl font-extrabold text-rose-400 font-mono mt-1">
            R$ {totalCustosFixos.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
          </h3>
        </div>
        <div className="w-10 h-10 rounded-xl bg-rose-950/60 text-rose-400 border border-rose-500/30 flex items-center justify-center">
          <span className="material-symbols-outlined text-xl leading-none">calculate</span>
        </div>
      </div>

      {loading ? (
        <div className="p-8 text-center text-xs text-zinc-500">Carregando custos fixos...</div>
      ) : custos.length === 0 ? (
        <div className="bg-[#0c0c0c] p-12 rounded-2xl border border-white/10 text-center">
          <span className="material-symbols-outlined text-4xl text-zinc-600 mb-2 leading-none">calculate</span>
          <p className="text-sm font-bold text-white">Nenhum custo fixo cadastrado.</p>
          <button
            onClick={() => setModalAberta(true)}
            className="mt-4 px-4 py-2 bg-white text-black rounded-xl text-xs font-bold hover:bg-zinc-200 transition"
          >
            + Cadastrar Custo Fixo
          </button>
        </div>
      ) : (
        <div className="bg-[#0c0c0c] border border-white/10 rounded-2xl overflow-hidden shadow-2xs">
          <table className="w-full text-left text-xs font-sans">
            <thead className="bg-[#121212] text-zinc-400 uppercase tracking-wider font-bold text-[10px] border-b border-white/10">
              <tr>
                <th className="py-3 px-4">Nome do Custo</th>
                <th className="py-3 px-4">Categoria</th>
                <th className="py-3 px-4">Dia Vencimento</th>
                <th className="py-3 px-4">Forma Pagamento</th>
                <th className="py-3 px-4">Valor Mensal</th>
                <th className="py-3 px-4 text-right">Ação</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/5">
              {custos.map((c) => (
                <tr key={c.id} className="hover:bg-zinc-900/60 transition">
                  <td className="py-3 px-4 font-bold text-white">{c.nome}</td>
                  <td className="py-3 px-4 text-zinc-400">{c.categoria}</td>
                  <td className="py-3 px-4 text-zinc-400 font-mono">Dia {c.dia_vencimento || 5}</td>
                  <td className="py-3 px-4 text-zinc-400 uppercase font-mono">{c.forma_pagamento || 'pix'}</td>
                  <td className="py-3 px-4 font-mono font-bold text-rose-400">
                    R$ {parseFloat(c.valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                  </td>
                  <td className="py-3 px-4 text-right">
                    <button
                      onClick={() => handleExcluir(c.id)}
                      className="text-zinc-500 hover:text-rose-400 transition"
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
        <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-zinc-900 border border-zinc-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 text-white">
            <div className="flex items-center justify-between border-b border-zinc-800 pb-3">
              <h3 className="font-bold text-white text-base">Novo Custo Fixo Recorrente</h3>
              <button onClick={() => setModalAberta(false)} className="text-zinc-400 hover:text-white">
                <span className="material-symbols-outlined leading-none">close</span>
              </button>
            </div>

            <form onSubmit={handleCriar} className="space-y-3">
              <div>
                <label className="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Nome da Despesa</label>
                <input
                  type="text"
                  required
                  value={nome}
                  onChange={(e) => setNome(e.target.value)}
                  className="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-xs text-white placeholder-zinc-500 focus:border-white outline-none"
                  placeholder="Ex: Aluguel do Estúdio, Licença Adobe, Servidor Neon"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Valor Mensal (R$)</label>
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
                  <label className="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Dia Vencimento</label>
                  <input
                    type="number"
                    min="1"
                    max="31"
                    value={diaVencimento}
                    onChange={(e) => setDiaVencimento(e.target.value)}
                    className="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-xs text-white focus:border-white outline-none font-mono"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Categoria</label>
                  <select
                    value={categoria}
                    onChange={(e) => setCategoria(e.target.value)}
                    className="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-xs text-white focus:border-white outline-none"
                  >
                    <option value="Custos Fixos">Custos Fixos</option>
                    <option value="Infraestrutura">Infraestrutura</option>
                    <option value="Softwares">Softwares</option>
                    <option value="Marketing">Marketing</option>
                  </select>
                </div>

                <div>
                  <label className="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Forma Pagamento</label>
                  <select
                    value={formaPagamento}
                    onChange={(e) => setFormaPagamento(e.target.value)}
                    className="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-xs text-white focus:border-white outline-none uppercase font-mono"
                  >
                    <option value="pix">PIX</option>
                    <option value="boleto">BOLETO</option>
                    <option value="cartao">CARTÃO</option>
                    <option value="debito_automatico">DÉBITO AUT.</option>
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
                  {salvando ? 'Salvando...' : 'Cadastrar Custo'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
