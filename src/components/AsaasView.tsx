import React, { useEffect, useState } from 'react';

export const AsaasView: React.FC = () => {
  const [saldo, setSaldo] = useState<number | null>(null);
  const [configurada, setConfigurada] = useState<boolean>(false);
  const [cobrancas, setCobrancas] = useState<any[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [maskedKey, setMaskedKey] = useState<string>('');

  // Filtros
  const [busca, setBusca] = useState<string>('');
  const [filtroStatus, setFiltroStatus] = useState<string>('TODOS');
  const [filtroForma, setFiltroForma] = useState<string>('TODAS');

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

    // 3. Carregar Transações e Movimentações do Asaas
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

  // Mapeamento Amigável de Status para Português
  const traduzirStatus = (statusRaw: string, isSaida: boolean): { texto: string; classe: string } => {
    const s = (statusRaw || '').toUpperCase();

    if (isSaida || s === 'SAIDA' || s === 'REFUNDED' || s === 'REFUND_REQUESTED' || s === 'DELETED') {
      return { texto: 'SAÍDA', classe: 'bg-red-100 text-red-800' };
    }
    if (s === 'RECEIVED' || s === 'CONFIRMED' || s === 'PAGO') {
      return { texto: 'RECEBIDO', classe: 'bg-emerald-100 text-emerald-800' };
    }
    if (s === 'PENDING' || s === 'PENDENTE') {
      return { texto: 'PENDENTE', classe: 'bg-amber-100 text-amber-800' };
    }
    if (s === 'OVERDUE' || s === 'ATRASADO') {
      return { texto: 'ATRASADO', classe: 'bg-rose-100 text-rose-800' };
    }
    return { texto: s || 'PENDENTE', classe: 'bg-gray-100 text-gray-700' };
  };

  // Filtragem de Transações
  const cobrancasFiltradas = cobrancas.filter((item) => {
    const statusStr = (item.status || '').toUpperCase();
    const isSaida = item.tipoMovimento === 'saida' || ['SAIDA', 'REFUNDED', 'REFUND_REQUESTED', 'DELETED'].includes(statusStr);
    const formaStr = (item.billingType || item.forma_pagamento || '').toUpperCase();
    const clienteStr = (item.customerName || item.cliente_fornecedor || item.descricao || '').toLowerCase();
    const idStr = (item.id || '').toLowerCase();

    // Busca por Texto
    if (busca.trim()) {
      const termo = busca.toLowerCase();
      if (!clienteStr.includes(termo) && !idStr.includes(termo)) {
        return false;
      }
    }

    // Filtro por Status em Português
    if (filtroStatus === 'RECEBIDO' && !['RECEIVED', 'CONFIRMED', 'PAGO'].includes(statusStr)) {
      return false;
    }
    if (filtroStatus === 'SAIDA' && !isSaida) {
      return false;
    }
    if (filtroStatus === 'PENDENTE' && !['PENDING', 'PENDENTE'].includes(statusStr)) {
      return false;
    }
    if (filtroStatus === 'ATRASADO' && !['OVERDUE', 'ATRASADO'].includes(statusStr)) {
      return false;
    }

    // Filtro por Forma de Pagamento
    if (filtroForma !== 'TODAS') {
      if (filtroForma === 'PIX' && !formaStr.includes('PIX')) return false;
      if (filtroForma === 'BOLETO' && !formaStr.includes('BOLETO')) return false;
      if (filtroForma === 'CARTAO' && !formaStr.includes('CREDIT') && !formaStr.includes('CARTAO')) return false;
    }

    return true;
  });

  // Cálculos em Português
  const totalEntradas = cobrancas
    .filter((c) => ['RECEIVED', 'CONFIRMED', 'PAGO', 'pago'].includes((c.status || '').toUpperCase()) && c.tipoMovimento !== 'saida')
    .reduce((acc, c) => acc + parseFloat(c.value || c.valor || 0), 0);

  const totalSaidas = cobrancas
    .filter((c) => ['SAIDA', 'REFUNDED', 'REFUND_REQUESTED', 'DELETED', 'CANCELADO'].includes((c.status || '').toUpperCase()) || c.tipoMovimento === 'saida')
    .reduce((acc, c) => acc + parseFloat(c.value || c.valor || 0), 0);

  const totalPendente = cobrancas
    .filter((c) => ['PENDING', 'OVERDUE', 'PENDENTE', 'atrasado'].includes((c.status || '').toUpperCase()))
    .reduce((acc, c) => acc + parseFloat(c.value || c.valor || 0), 0);

  return (
    <div className="space-y-6 font-sans text-white bg-[#050505] min-h-screen">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-xl font-bold text-white tracking-tight">Gestão de Pagamentos Asaas</h2>
          <p className="text-xs text-zinc-400 mt-1">
            Painel de entradas, saídas e extrato consolidado da conta Asaas
          </p>
        </div>

        <div className="flex items-center space-x-3">
          <button
            onClick={() => setModalConfigAberta(true)}
            className="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-white rounded-xl text-xs font-bold transition flex items-center space-x-2 border border-white/5"
          >
            <span className="material-symbols-outlined text-sm leading-none">key</span>
            <span>{configurada ? 'Alterar Chave API' : 'Configurar Chave API'}</span>
          </button>

          <button
            onClick={carregarDadosAsaas}
            className="px-4 py-2 bg-white text-black hover:bg-zinc-200 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-sm"
          >
            <span className="material-symbols-outlined text-sm leading-none">sync</span>
            <span>Sincronizar Agora</span>
          </button>
        </div>
      </div>

      {/* Hero Card: Saldo Atual no Asaas */}
      <div className="bg-[#0c0c0c] border border-white/10 p-6 rounded-2xl text-white shadow-md space-y-4">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div className="flex items-center space-x-2">
              <span className="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Saldo Líquido Atual no Asaas</span>
              {configurada ? (
                <span className="bg-emerald-950/80 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded text-[10px] font-bold flex items-center space-x-1">
                  <span className="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
                  <span>Conectado via API Asaas</span>
                </span>
              ) : (
                <button
                  onClick={() => setModalConfigAberta(true)}
                  className="bg-amber-950/80 hover:bg-amber-900/80 text-amber-400 border border-amber-500/30 px-2 py-0.5 rounded text-[10px] font-bold transition"
                >
                  ⚡ Cadastrar Chave de API para Sincronizar
                </button>
              )}
            </div>

            <div className="mt-2 flex items-baseline space-x-2">
              <span className="text-4xl font-extrabold font-mono tracking-tight text-white">
                R$ {saldo !== null ? saldo.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) : '0,00'}
              </span>
            </div>

            {configurada && maskedKey && (
              <p className="text-[11px] text-zinc-400 mt-2 font-mono">
                Chave: <span className="text-zinc-300">{maskedKey}</span> ({novoModo === 'prod' ? 'Produção' : 'Sandbox'})
              </p>
            )}
          </div>

          <div className="grid grid-cols-3 gap-3">
            <div className="bg-zinc-900/80 p-3.5 rounded-xl border border-white/5">
              <span className="text-[9px] font-bold uppercase tracking-wider text-zinc-400 block">Total Recebido (Entradas)</span>
              <span className="text-base font-bold font-mono text-emerald-400 mt-1 block">
                + R$ {totalEntradas.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
              </span>
            </div>
            <div className="bg-zinc-900/80 p-3.5 rounded-xl border border-white/5">
              <span className="text-[9px] font-bold uppercase tracking-wider text-zinc-400 block">Total Saídas</span>
              <span className="text-base font-bold font-mono text-rose-400 mt-1 block">
                - R$ {totalSaidas.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
              </span>
            </div>
            <div className="bg-zinc-900/80 p-3.5 rounded-xl border border-white/5">
              <span className="text-[9px] font-bold uppercase tracking-wider text-zinc-400 block">Pendente de Recebimento</span>
              <span className="text-base font-bold font-mono text-amber-400 mt-1 block">
                R$ {totalPendente.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Barra de Filtros em Português */}
      <div className="bg-[#0c0c0c] border border-white/10 p-4 rounded-2xl shadow-2xs space-y-3">
        <div className="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
          {/* Campo de Busca */}
          <div className="relative flex-1">
            <span className="material-symbols-outlined absolute left-3 top-2.5 text-zinc-400 text-sm leading-none">
              search
            </span>
            <input
              type="text"
              value={busca}
              onChange={(e) => setBusca(e.target.value)}
              placeholder="Buscar por cliente ou ID da cobrança..."
              className="w-full pl-9 pr-4 py-2 bg-zinc-900 border border-zinc-700 rounded-xl text-xs text-white placeholder-zinc-500 focus:border-white outline-none transition font-sans"
            />
          </div>

          {/* Filtros de Status em Português */}
          <div className="flex items-center space-x-1 overflow-x-auto pb-1 md:pb-0">
            {[
              { id: 'TODOS', label: 'Todos' },
              { id: 'RECEBIDO', label: 'Recebidos' },
              { id: 'SAIDA', label: 'Saídas' },
              { id: 'PENDENTE', label: 'Pendentes' },
              { id: 'ATRASADO', label: 'Atrasados' },
            ].map((f) => (
              <button
                key={f.id}
                onClick={() => setFiltroStatus(f.id)}
                className={`px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap ${
                  filtroStatus === f.id
                    ? 'bg-white text-black shadow-xs'
                    : 'bg-zinc-900 text-zinc-400 hover:text-white border border-white/5'
                }`}
              >
                {f.label}
              </button>
            ))}
          </div>

          {/* Filtro por Forma de Pagamento */}
          <select
            value={filtroForma}
            onChange={(e) => setFiltroForma(e.target.value)}
            className="px-3 py-2 bg-zinc-900 border border-zinc-700 rounded-xl text-xs font-bold text-white outline-none"
          >
            <option value="TODAS">Todas as Formas</option>
            <option value="PIX">Somente PIX</option>
            <option value="BOLETO">Somente Boleto</option>
            <option value="CARTAO">Somente Cartão de Crédito</option>
          </select>
        </div>
      </div>

      {/* Tabela Exclusiva de Transações do Asaas em Português */}
      <div className="bg-[#0c0c0c] border border-white/10 rounded-2xl overflow-hidden shadow-2xs">
        <div className="p-5 border-b border-white/10 flex items-center justify-between">
          <div>
            <h3 className="font-bold text-white text-sm">Extrato de Movimentações Asaas</h3>
            <p className="text-xs text-zinc-400">Classificação de entradas, saídas e movimentações pendentes</p>
          </div>
          <span className="text-xs font-bold bg-zinc-900 text-zinc-300 px-3 py-1 rounded-full font-mono border border-white/5">
            {cobrancasFiltradas.length} de {cobrancas.length} movimentações
          </span>
        </div>

        {loading ? (
          <div className="p-8 text-center text-xs text-zinc-500">Carregando movimentações do Asaas...</div>
        ) : cobrancasFiltradas.length === 0 ? (
          <div className="p-12 text-center">
            <span className="material-symbols-outlined text-4xl text-zinc-600 mb-2 leading-none">filter_alt_off</span>
            <p className="text-sm font-bold text-white">Nenhuma movimentação encontrada com os filtros selecionados.</p>
            <button
              onClick={() => {
                setBusca('');
                setFiltroStatus('TODOS');
                setFiltroForma('TODAS');
              }}
              className="mt-4 px-4 py-2 bg-zinc-800 text-white rounded-xl text-xs font-bold hover:bg-zinc-700 transition"
            >
              Limpar Filtros
            </button>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs font-sans">
              <thead className="bg-[#121212] text-zinc-400 uppercase tracking-wider font-bold text-[10px] border-b border-white/10">
                <tr>
                  <th className="py-3 px-4">Tipo Movimento</th>
                  <th className="py-3 px-4">ID Cobrança</th>
                  <th className="py-3 px-4">Cliente / Pagador</th>
                  <th className="py-3 px-4">Forma Pagamento</th>
                  <th className="py-3 px-4">Vencimento</th>
                  <th className="py-3 px-4">Valor</th>
                  <th className="py-3 px-4">Status</th>
                  <th className="py-3 px-4 text-right">Fatura / Ação</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-white/5">
                {cobrancasFiltradas.map((item) => {
                  const valorNum = parseFloat(item.value || item.valor || 0);
                  const statusRaw = item.status || 'PENDING';
                  const isSaida = item.tipoMovimento === 'saida' || ['SAIDA', 'REFUNDED', 'REFUND_REQUESTED', 'DELETED'].includes((statusRaw).toUpperCase());
                  const statusTraduzido = traduzirStatus(statusRaw, isSaida);

                  return (
                    <tr key={item.id} className="hover:bg-zinc-900/60 transition">
                      <td className="py-3 px-4">
                        <span
                          className={`inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase ${
                            isSaida ? 'bg-rose-950/80 text-rose-400 border border-rose-500/30' : 'bg-emerald-950/80 text-emerald-400 border border-emerald-500/30'
                          }`}
                        >
                          {isSaida ? 'SAÍDA' : 'ENTRADA'}
                        </span>
                      </td>
                      <td className="py-3 px-4 font-mono text-zinc-400">{item.id}</td>
                      <td className="py-3 px-4 font-bold text-white">
                        {item.customerName || item.cliente_fornecedor || item.descricao || 'Cliente Asaas'}
                      </td>
                      <td className="py-3 px-4 font-mono uppercase text-zinc-400">
                        {item.billingType || item.forma_pagamento || 'PIX'}
                      </td>
                      <td className="py-3 px-4 text-zinc-400 font-mono">
                        {item.dueDate || item.vencimento ? new Date(item.dueDate || item.vencimento).toLocaleDateString('pt-BR') : '—'}
                      </td>
                      <td className="py-3 px-4 font-mono font-bold">
                        <span className={isSaida ? 'text-red-600' : 'text-emerald-700'}>
                          {isSaida ? '-' : '+'} R$ {valorNum.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                        </span>
                      </td>
                      <td className="py-3 px-4">
                        <span className={`inline-block px-2.5 py-1 rounded-full text-[10px] font-bold ${statusTraduzido.classe}`}>
                          {statusTraduzido.texto}
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
                          <span className="text-gray-400 text-[10px]">—</span>
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
                A chave de API é salva com criptografia no banco de dados Neon. Assim que salva, a sincronização das movimentações funcionará de forma automática.
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
                  {salvandoConfig ? 'Salvando...' : 'Salvar e Sincronizar'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
