import React, { useEffect, useState } from 'react';

export const AsaasView: React.FC = () => {
  const [saldo, setSaldo] = useState<number | null>(null);
  const [configurada, setConfigurada] = useState<boolean>(false);
  const [cobrancas, setCobrancas] = useState<any[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [maskedKey, setMaskedKey] = useState<string>('');

  // Modal de Configuração da Chave
  const [modalConfigAberta, setModalConfigAberta] = useState<boolean>(false);
  const [novaApiKey, setNovaApiKey] = useState<string>('');
  const [novoModo, setNovoModo] = useState<string>('prod');
  const [salvandoConfig, setSalvandoConfig] = useState<boolean>(false);
  const [msgStatus, setMsgStatus] = useState<string>('');

  const carregarDadosAsaas = () => {
    setLoading(true);

    // 1. Checar status da configuração da API no banco
    fetch('/api/configuracoes/asaas')
      .then((res) => res.json())
      .then((data) => {
        if (data.configured) {
          setConfigurada(true);
          setMaskedKey(data.maskedKey || '');
          setNovoModo(data.mode || 'prod');
        } else {
          setConfigurada(false);
        }
      })
      .catch(() => {});

    // 2. Carregar Saldo Oficial do Asaas
    fetch('/api/financeiro/asaas-balance')
      .then((res) => res.json())
      .then((data) => {
        if (data.ok) {
          setSaldo(data.saldo);
          setConfigurada(true);
        }
      })
      .catch(() => {});

    // 3. Carregar Transações do Asaas
    fetch('/api/financeiro/asaas-payments')
      .then((res) => res.json())
      .then((data) => {
        if (data.ok && Array.isArray(data.dados)) {
          setCobrancas(data.dados);
        }
      })
      .catch((err) => console.error(err))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    carregarDadosAsaas();
  }, []);

  const handleSalvarChaveAsaas = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!novaApiKey) return;

    setSalvandoConfig(true);
    setMsgStatus('');

    try {
      const res = await fetch('/api/configuracoes/asaas', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ apiKey: novaApiKey, mode: novoModo }),
      });

      const data = await res.json();
      if (res.ok && data.ok) {
        setMsgStatus('Chave de API salva com sucesso!');
        setModalConfigAberta(false);
        setNovaApiKey('');
        carregarDadosAsaas();
      } else {
        setMsgStatus(data.erro || 'Erro ao salvar chave.');
      }
    } catch (e: any) {
      setMsgStatus('Erro de conexão ao salvar chave.');
    } finally {
      setSalvandoConfig(false);
    }
  };

  const totalRecebido = cobrancas
    .filter((c) => c.status === 'RECEIVED' || c.status === 'CONFIRMED' || c.status === 'pago')
    .reduce((acc, c) => acc + (parseFloat(c.value || c.valor || 0)), 0);

  const totalPendente = cobrancas
    .filter((c) => c.status === 'PENDING' || c.status === 'OVERDUE' || c.status === 'pendente')
    .reduce((acc, c) => acc + (parseFloat(c.value || c.valor || 0)), 0);

  return (
    <div className="space-y-6 font-sans text-gray-900">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-xl font-bold text-gray-900 tracking-tight">Gestão de Pagamentos Asaas</h2>
          <p className="text-xs text-gray-500 mt-1">
            Painel exclusivo de saldo, cobranças PIX, boleto e cartão de crédito processados via Asaas
          </p>
        </div>

        <div className="flex items-center space-x-3">
          <button
            onClick={() => setModalConfigAberta(true)}
            className="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl text-xs font-bold transition flex items-center space-x-2 border border-gray-200"
          >
            <span className="material-symbols-outlined text-sm leading-none">key</span>
            <span>{configurada ? 'Alterar Chave API' : 'Configurar Chave API'}</span>
          </button>

          <button
            onClick={carregarDadosAsaas}
            className="px-4 py-2 bg-black text-white hover:bg-gray-800 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-sm"
          >
            <span className="material-symbols-outlined text-sm leading-none">refresh</span>
            <span>Atualizar Saldo</span>
          </button>
        </div>
      </div>

      {/* Hero Card: Saldo Atual no Asaas */}
      <div className="bg-gradient-to-r from-gray-900 via-gray-800 to-black p-6 rounded-2xl text-white shadow-md space-y-4">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div className="flex items-center space-x-2">
              <span className="text-[11px] font-bold uppercase tracking-wider text-gray-400">Saldo Atual da Conta Asaas</span>
              {configurada ? (
                <span className="bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2 py-0.5 rounded text-[10px] font-bold flex items-center space-x-1">
                  <span className="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
                  <span>Conectado via API Asaas</span>
                </span>
              ) : (
                <button
                  onClick={() => setModalConfigAberta(true)}
                  className="bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 px-2 py-0.5 rounded text-[10px] font-bold transition"
                >
                  ⚡ Clique para cadastrar a chave API
                </button>
              )}
            </div>

            <div className="mt-2 flex items-baseline space-x-2">
              <span className="text-4xl font-extrabold font-mono tracking-tight text-white">
                R$ {saldo !== null ? saldo.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) : '0,00'}
              </span>
            </div>

            {configurada && maskedKey && (
              <p className="text-[11px] text-gray-400 mt-2 font-mono">
                Chave ativa: <span className="text-gray-300">{maskedKey}</span> ({novoModo === 'prod' ? 'Produção' : 'Sandbox'})
              </p>
            )}
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="bg-white/10 backdrop-blur-md p-4 rounded-xl border border-white/10">
              <span className="text-[10px] font-bold uppercase tracking-wider text-gray-300 block">Cobranças Recebidas</span>
              <span className="text-lg font-bold font-mono text-emerald-400 mt-1 block">
                + R$ {totalRecebido.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
              </span>
            </div>
            <div className="bg-white/10 backdrop-blur-md p-4 rounded-xl border border-white/10">
              <span className="text-[10px] font-bold uppercase tracking-wider text-gray-300 block">A Receber no Asaas</span>
              <span className="text-lg font-bold font-mono text-amber-300 mt-1 block">
                R$ {totalPendente.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Tabela Exclusiva de Transações do Asaas */}
      <div className="bg-white border border-gray-200/80 rounded-2xl overflow-hidden shadow-2xs">
        <div className="p-5 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h3 className="font-bold text-gray-900 text-sm">Transações Exclusivas do Asaas</h3>
            <p className="text-xs text-gray-400">Listagem de cobranças, boletos e PIX gerados via plataforma Asaas</p>
          </div>
          <span className="text-xs font-bold bg-gray-100 text-gray-600 px-3 py-1 rounded-full font-mono">
            {cobrancas.length} transações
          </span>
        </div>

        {loading ? (
          <div className="p-8 text-center text-xs text-gray-400">Carregando cobranças do Asaas...</div>
        ) : cobrancas.length === 0 ? (
          <div className="p-12 text-center">
            <span className="material-symbols-outlined text-4xl text-gray-300 mb-2 leading-none">payments</span>
            <p className="text-sm font-bold text-gray-800">Nenhuma cobrança registrada no Asaas.</p>
            <p className="text-xs text-gray-400 mt-1">Configure a chave de API do Asaas acima para sincronizar seu saldo e cobranças.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs font-sans">
              <thead className="bg-gray-50 text-gray-500 uppercase tracking-wider font-bold text-[10px] border-b border-gray-100">
                <tr>
                  <th className="py-3 px-4">ID Cobrança</th>
                  <th className="py-3 px-4">Cliente / Pagador</th>
                  <th className="py-3 px-4">Forma Pagamento</th>
                  <th className="py-3 px-4">Vencimento</th>
                  <th className="py-3 px-4">Valor</th>
                  <th className="py-3 px-4">Status Asaas</th>
                  <th className="py-3 px-4 text-right">Link / Fatura</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {cobrancas.map((item) => {
                  const valorNum = parseFloat(item.value || item.valor || 0);
                  const statusStr = (item.status || 'PENDING').toUpperCase();

                  const statusBadge =
                    statusStr === 'RECEIVED' || statusStr === 'CONFIRMED' || statusStr === 'PAGO'
                      ? 'bg-emerald-100 text-emerald-800'
                      : statusStr === 'OVERDUE' || statusStr === 'ATRASADO'
                      ? 'bg-red-100 text-red-800'
                      : 'bg-amber-100 text-amber-800';

                  return (
                    <tr key={item.id} className="hover:bg-gray-50/80 transition">
                      <td className="py-3 px-4 font-mono text-gray-500">{item.id}</td>
                      <td className="py-3 px-4 font-bold text-gray-900">
                        {item.customerName || item.cliente_fornecedor || item.descricao || 'Cliente Asaas'}
                      </td>
                      <td className="py-3 px-4 font-mono uppercase text-gray-600">
                        {item.billingType || item.forma_pagamento || 'PIX'}
                      </td>
                      <td className="py-3 px-4 text-gray-600 font-mono">
                        {item.dueDate || item.vencimento ? new Date(item.dueDate || item.vencimento).toLocaleDateString('pt-BR') : '—'}
                      </td>
                      <td className="py-3 px-4 font-mono font-bold text-gray-900">
                        R$ {valorNum.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                      </td>
                      <td className="py-3 px-4">
                        <span className={`inline-block px-2.5 py-1 rounded-full text-[10px] font-bold ${statusBadge}`}>
                          {statusStr}
                        </span>
                      </td>
                      <td className="py-3 px-4 text-right">
                        {item.invoiceUrl || item.bankSlipUrl ? (
                          <a
                            href={item.invoiceUrl || item.bankSlipUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="px-2.5 py-1 bg-black text-white hover:bg-gray-800 rounded-lg text-[10px] font-bold transition inline-flex items-center space-x-1"
                          >
                            <span>Fatura</span>
                            <span className="material-symbols-outlined text-xs leading-none">open_in_new</span>
                          </a>
                        ) : (
                          <span className="text-gray-400 text-[10px]">Sem Link</span>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Modal Segura para Configurar Chave API do Asaas */}
      {modalConfigAberta && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div className="flex items-center justify-between border-b border-gray-100 pb-3">
              <div className="flex items-center space-x-2">
                <span className="material-symbols-outlined text-black text-xl leading-none">shield_lock</span>
                <h3 className="font-bold text-gray-900 text-base">Configurar Chave API do Asaas</h3>
              </div>
              <button onClick={() => setModalConfigAberta(false)} className="text-gray-400 hover:text-gray-600">
                <span className="material-symbols-outlined leading-none">close</span>
              </button>
            </div>

            <form onSubmit={handleSalvarChaveAsaas} className="space-y-4">
              <p className="text-xs text-gray-500 leading-relaxed">
                A chave de API é criptografada e salva diretamente no banco de dados seguro do Neon. Somente os endpoints do servidor utilizam essa chave.
              </p>

              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Chave de API do Asaas ($aact_...)</label>
                <input
                  type="password"
                  required
                  value={novaApiKey}
                  onChange={(e) => setNovaApiKey(e.target.value)}
                  className="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-black outline-none"
                  placeholder="Cole aqui a sua chave de API do Asaas"
                />
              </div>

              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Ambiente</label>
                <select
                  value={novoModo}
                  onChange={(e) => setNovoModo(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none font-sans"
                >
                  <option value="prod">Produção (Conta Real do Asaas)</option>
                  <option value="test">Sandbox (Conta de Testes)</option>
                </select>
              </div>

              {msgStatus && (
                <div className="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl font-bold">
                  {msgStatus}
                </div>
              )}

              <div className="pt-2 flex justify-end space-x-2">
                <button
                  type="button"
                  onClick={() => setModalConfigAberta(false)}
                  className="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={salvandoConfig}
                  className="px-4 py-2 bg-black text-white text-xs font-bold rounded-xl hover:bg-gray-800 transition font-mono"
                >
                  {salvandoConfig ? 'Salvando...' : 'Salvar Chave com Segurança'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
