import React, { useEffect, useState } from 'react';

export const ClientesView: React.FC = () => {
  const [clientes, setClientes] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalAberta, setModalAberta] = useState(false);

  const [nome, setNome] = useState('');
  const [email, setEmail] = useState('');
  const [telefone, setTelefone] = useState('');
  const [cpfCnpj, setCpfCnpj] = useState('');
  const [salvando, setSalvando] = useState(false);

  const carregarClientes = () => {
    setLoading(true);
    fetch('/api/gerenciamento/clientes')
      .then((res) => res.json())
      .then((data) => {
        if (Array.isArray(data)) setClientes(data);
      })
      .catch((err) => console.error(err))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    carregarClientes();
  }, []);

  const handleCriar = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!nome) return;
    setSalvando(true);

    try {
      const res = await fetch('/api/gerenciamento/clientes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nome, email, telefone, cpf_cnpj: cpfCnpj }),
      });

      if (res.ok) {
        setModalAberta(false);
        setNome('');
        setEmail('');
        setTelefone('');
        setCpfCnpj('');
        carregarClientes();
      }
    } catch (e) {
      console.error(e);
    } finally {
      setSalvando(false);
    }
  };

  const handleExcluir = async (id: string) => {
    if (!confirm('Deseja excluir este cliente?')) return;
    await fetch(`/api/gerenciamento/clientes?id=${id}`, { method: 'DELETE' });
    carregarClientes();
  };

  return (
    <div className="space-y-6 font-sans text-white bg-[#050505] min-h-screen">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-xl font-bold text-white tracking-tight">Gestão de Clientes</h2>
          <p className="text-xs text-zinc-400 mt-1">Base de dados de clientes, documentos e identificadores Asaas</p>
        </div>

        <button
          onClick={() => setModalAberta(true)}
          className="px-4 py-2 bg-white text-black hover:bg-zinc-200 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-sm"
        >
          <span className="material-symbols-outlined text-sm leading-none">add</span>
          <span>Novo Cliente</span>
        </button>
      </div>

      {loading ? (
        <div className="p-8 text-center text-xs text-zinc-500">Carregando clientes...</div>
      ) : clientes.length === 0 ? (
        <div className="bg-[#0c0c0c] p-12 rounded-2xl border border-white/10 text-center">
          <span className="material-symbols-outlined text-4xl text-zinc-600 mb-2 leading-none">group</span>
          <p className="text-sm font-bold text-white">Nenhum cliente cadastrado.</p>
          <button
            onClick={() => setModalAberta(true)}
            className="mt-4 px-4 py-2 bg-white text-black rounded-xl text-xs font-bold hover:bg-zinc-200 transition"
          >
            + Cadastrar Cliente
          </button>
        </div>
      ) : (
        <div className="bg-[#0c0c0c] border border-white/10 rounded-2xl overflow-hidden shadow-2xs">
          <table className="w-full text-left text-xs font-sans">
            <thead className="bg-[#121212] text-zinc-400 uppercase tracking-wider font-bold text-[10px] border-b border-white/10">
              <tr>
                <th className="py-3 px-4">Nome do Cliente</th>
                <th className="py-3 px-4">E-mail</th>
                <th className="py-3 px-4">Telefone / WhatsApp</th>
                <th className="py-3 px-4">CPF / CNPJ</th>
                <th className="py-3 px-4">ID Asaas</th>
                <th className="py-3 px-4 text-right">Ação</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/5">
              {clientes.map((c) => (
                <tr key={c.id} className="hover:bg-zinc-900/60 transition">
                  <td className="py-3 px-4 font-bold text-white">{c.nome}</td>
                  <td className="py-3 px-4 text-zinc-400">{c.email || '—'}</td>
                  <td className="py-3 px-4 text-zinc-400 font-mono">{c.telefone || '—'}</td>
                  <td className="py-3 px-4 text-zinc-400 font-mono">{c.cpf_cnpj || '—'}</td>
                  <td className="py-3 px-4">
                    {c.asaas_customer_id ? (
                      <span className="px-2 py-0.5 bg-emerald-950/80 text-emerald-400 border border-emerald-500/30 font-mono text-[10px] font-bold rounded">
                        {c.asaas_customer_id}
                      </span>
                    ) : (
                      <span className="text-zinc-600">—</span>
                    )}
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

      {/* Modal Novo Cliente */}
      {modalAberta && (
        <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-zinc-900 border border-zinc-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 text-white">
            <div className="flex items-center justify-between border-b border-zinc-800 pb-3">
              <h3 className="font-bold text-white text-base">Novo Cliente</h3>
              <button onClick={() => setModalAberta(false)} className="text-zinc-400 hover:text-white">
                <span className="material-symbols-outlined leading-none">close</span>
              </button>
            </div>

            <form onSubmit={handleCriar} className="space-y-3">
              <div>
                <label className="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Nome Completo / Razão Social</label>
                <input
                  type="text"
                  required
                  value={nome}
                  onChange={(e) => setNome(e.target.value)}
                  className="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-xs text-white placeholder-zinc-500 focus:border-white outline-none"
                  placeholder="Ex: Estúdio Fotográfico Casamentos"
                />
              </div>

              <div>
                <label className="block text-[10px] font-bold uppercase text-zinc-400 mb-1">E-mail</label>
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-xs text-white placeholder-zinc-500 focus:border-white outline-none"
                  placeholder="contato@cliente.com.br"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Telefone / WhatsApp</label>
                  <input
                    type="text"
                    value={telefone}
                    onChange={(e) => setTelefone(e.target.value)}
                    className="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-xs text-white placeholder-zinc-500 focus:border-white outline-none font-mono"
                    placeholder="(11) 99999-9999"
                  />
                </div>

                <div>
                  <label className="block text-[10px] font-bold uppercase text-zinc-400 mb-1">CPF ou CNPJ</label>
                  <input
                    type="text"
                    value={cpfCnpj}
                    onChange={(e) => setCpfCnpj(e.target.value)}
                    className="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-xs text-white placeholder-zinc-500 focus:border-white outline-none font-mono"
                    placeholder="000.000.000-00"
                  />
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
                  {salvando ? 'Salvando...' : 'Cadastrar Cliente'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
