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
    <div className="space-y-6 font-sans">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-xl font-bold text-gray-900 tracking-tight">Gestão de Clientes</h2>
          <p className="text-xs text-gray-500 mt-1">Base de dados de clientes, documentos e identificadores Asaas</p>
        </div>

        <button
          onClick={() => setModalAberta(true)}
          className="px-4 py-2 bg-black text-white hover:bg-gray-800 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-sm"
        >
          <span className="material-symbols-outlined text-sm leading-none">add</span>
          <span>Novo Cliente</span>
        </button>
      </div>

      {loading ? (
        <div className="p-8 text-center text-xs text-gray-400">Carregando clientes...</div>
      ) : clientes.length === 0 ? (
        <div className="bg-white p-12 rounded-2xl border border-gray-200/80 text-center">
          <span className="material-symbols-outlined text-4xl text-gray-300 mb-2 leading-none">group</span>
          <p className="text-sm font-bold text-gray-800">Nenhum cliente cadastrado.</p>
          <button
            onClick={() => setModalAberta(true)}
            className="mt-4 px-4 py-2 bg-black text-white rounded-xl text-xs font-bold hover:bg-gray-800 transition"
          >
            + Cadastrar Cliente
          </button>
        </div>
      ) : (
        <div className="bg-white border border-gray-200/80 rounded-2xl overflow-hidden shadow-2xs">
          <table className="w-full text-left text-xs font-sans">
            <thead className="bg-gray-50 text-gray-500 uppercase tracking-wider font-bold text-[10px] border-b border-gray-100">
              <tr>
                <th className="py-3 px-4">Nome do Cliente</th>
                <th className="py-3 px-4">E-mail</th>
                <th className="py-3 px-4">Telefone / WhatsApp</th>
                <th className="py-3 px-4">CPF / CNPJ</th>
                <th className="py-3 px-4">ID Asaas</th>
                <th className="py-3 px-4 text-right">Ação</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {clientes.map((c) => (
                <tr key={c.id} className="hover:bg-gray-50/80 transition">
                  <td className="py-3 px-4 font-bold text-gray-900">{c.nome}</td>
                  <td className="py-3 px-4 text-gray-600">{c.email || '—'}</td>
                  <td className="py-3 px-4 text-gray-600 font-mono">{c.telefone || '—'}</td>
                  <td className="py-3 px-4 text-gray-600 font-mono">{c.cpf_cnpj || '—'}</td>
                  <td className="py-3 px-4">
                    {c.asaas_customer_id ? (
                      <span className="px-2 py-0.5 bg-emerald-50 text-emerald-700 font-mono text-[10px] font-bold rounded">
                        {c.asaas_customer_id}
                      </span>
                    ) : (
                      <span className="text-gray-400">—</span>
                    )}
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

      {/* Modal Novo Cliente */}
      {modalAberta && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
            <div className="flex items-center justify-between border-b border-gray-100 pb-3">
              <h3 className="font-bold text-gray-900 text-base">Novo Cliente</h3>
              <button onClick={() => setModalAberta(false)} className="text-gray-400 hover:text-gray-600">
                <span className="material-symbols-outlined leading-none">close</span>
              </button>
            </div>

            <form onSubmit={handleCriar} className="space-y-3">
              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Nome Completo / Razão Social</label>
                <input
                  type="text"
                  required
                  value={nome}
                  onChange={(e) => setNome(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none"
                  placeholder="Ex: Estúdio Fotográfico Casamentos"
                />
              </div>

              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">E-mail</label>
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none"
                  placeholder="contato@cliente.com.br"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Telefone / WhatsApp</label>
                  <input
                    type="text"
                    value={telefone}
                    onChange={(e) => setTelefone(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none font-mono"
                    placeholder="(11) 99999-9999"
                  />
                </div>

                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">CPF ou CNPJ</label>
                  <input
                    type="text"
                    value={cpfCnpj}
                    onChange={(e) => setCpfCnpj(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none font-mono"
                    placeholder="000.000.000-00"
                  />
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
