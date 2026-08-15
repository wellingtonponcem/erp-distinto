import React, { useEffect, useState } from 'react';

export const ContasBancariasView: React.FC = () => {
  const [contas, setContas] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalAberta, setModalAberta] = useState(false);

  const [nome, setNome] = useState('');
  const [tipo, setTipo] = useState('corrente');
  const [saldoInicial, setSaldoInicial] = useState('0.00');
  const [cor, setCor] = useState('#111827');
  const [salvando, setSalvando] = useState(false);

  const carregarContas = () => {
    setLoading(true);
    fetch('/api/financeiro/contas')
      .then((res) => res.json())
      .then((data) => {
        if (Array.isArray(data)) setContas(data);
      })
      .catch((err) => console.error(err))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    carregarContas();
  }, []);

  const handleCriar = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!nome) return;
    setSalvando(true);

    try {
      const res = await fetch('/api/financeiro/contas', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nome, tipo, saldo_inicial: saldoInicial, cor }),
      });

      if (res.ok) {
        setModalAberta(false);
        setNome('');
        setSaldoInicial('0.00');
        carregarContas();
      }
    } catch (e) {
      console.error(e);
    } finally {
      setSalvando(false);
    }
  };

  const handleExcluir = async (id: string) => {
    if (!confirm('Deseja realmente remover esta conta?')) return;
    await fetch(`/api/financeiro/contas?id=${id}`, { method: 'DELETE' });
    carregarContas();
  };

  return (
    <div className="space-y-6 font-sans">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-xl font-bold text-gray-900 tracking-tight">Contas Bancárias & Caixas</h2>
          <p className="text-xs text-gray-500 mt-1">Gestão de contas para liquidação de lançamentos e fluxo de caixa</p>
        </div>

        <button
          onClick={() => setModalAberta(true)}
          className="px-4 py-2 bg-black text-white hover:bg-gray-800 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-sm"
        >
          <span className="material-symbols-outlined text-sm leading-none">add</span>
          <span>Nova Conta Bancária</span>
        </button>
      </div>

      {loading ? (
        <div className="p-8 text-center text-xs text-gray-400">Carregando contas...</div>
      ) : contas.length === 0 ? (
        <div className="bg-white p-12 rounded-2xl border border-gray-200/80 text-center">
          <span className="material-symbols-outlined text-4xl text-gray-300 mb-2 leading-none">account_balance</span>
          <p className="text-sm font-bold text-gray-800">Nenhuma conta bancária cadastrada.</p>
          <p className="text-xs text-gray-400 mt-1 mb-4">Cadastre suas contas (Itaú, Bradesco, Asaas, Nubank) para conciliação.</p>
          <button
            onClick={() => setModalAberta(true)}
            className="px-4 py-2 bg-black text-white rounded-xl text-xs font-bold hover:bg-gray-800 transition"
          >
            + Cadastrar Conta
          </button>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {contas.map((c) => (
            <div key={c.id} className="bg-white border border-gray-200/80 p-5 rounded-2xl shadow-2xs space-y-4 relative group">
              <div className="flex items-center justify-between">
                <div className="flex items-center space-x-3">
                  <div
                    className="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold"
                    style={{ backgroundColor: c.cor || '#111827' }}
                  >
                    <span className="material-symbols-outlined text-lg leading-none">account_balance</span>
                  </div>
                  <div>
                    <h3 className="font-bold text-gray-900 text-sm">{c.nome}</h3>
                    <span className="text-[10px] text-gray-400 font-bold uppercase tracking-wider">{c.tipo}</span>
                  </div>
                </div>
                <button
                  onClick={() => handleExcluir(c.id)}
                  className="text-gray-300 hover:text-red-600 transition p-1"
                >
                  <span className="material-symbols-outlined text-sm leading-none">delete</span>
                </button>
              </div>

              <div className="pt-2 border-t border-gray-100 flex items-center justify-between">
                <span className="text-[10px] font-bold text-gray-400 uppercase">Saldo Inicial</span>
                <span className="text-base font-extrabold text-gray-900 font-mono">
                  R$ {parseFloat(c.saldo_inicial || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                </span>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Modal Nova Conta */}
      {modalAberta && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
            <div className="flex items-center justify-between border-b border-gray-100 pb-3">
              <h3 className="font-bold text-gray-900 text-base">Nova Conta Bancária</h3>
              <button onClick={() => setModalAberta(false)} className="text-gray-400 hover:text-gray-600">
                <span className="material-symbols-outlined leading-none">close</span>
              </button>
            </div>

            <form onSubmit={handleCriar} className="space-y-3">
              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Nome da Conta / Banco</label>
                <input
                  type="text"
                  required
                  value={nome}
                  onChange={(e) => setNome(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none"
                  placeholder="Ex: Itaú Unibanco, Asaas, Caixa Físico"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Tipo de Conta</label>
                  <select
                    value={tipo}
                    onChange={(e) => setTipo(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none"
                  >
                    <option value="corrente">Conta Corrente</option>
                    <option value="poupanca">Poupança</option>
                    <option value="pagamento">Pagamento (Asaas/Stripe)</option>
                    <option value="caixa">Caixa Físico</option>
                  </select>
                </div>

                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Saldo Inicial (R$)</label>
                  <input
                    type="number"
                    step="0.01"
                    value={saldoInicial}
                    onChange={(e) => setSaldoInicial(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none font-mono"
                  />
                </div>
              </div>

              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Cor do Card</label>
                <input
                  type="color"
                  value={cor}
                  onChange={(e) => setCor(e.target.value)}
                  className="w-full h-9 p-1 border border-gray-200 rounded-xl cursor-pointer"
                />
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
                  {salvando ? 'Salvando...' : 'Cadastrar Conta'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
