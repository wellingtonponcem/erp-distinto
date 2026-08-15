import React, { useEffect, useState, useRef } from 'react';

export const LancamentosView: React.FC = () => {
  const [lancamentos, setLancamentos] = useState<any[]>([]);
  const [contas, setContas] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  // Filtros
  const [busca, setBusca] = useState('');
  const [filtroTipo, setFiltroTipo] = useState('TODOS');
  const [filtroStatus, setFiltroStatus] = useState('TODOS');
  const [filtroConta, setFiltroConta] = useState('TODAS');

  // Modal Novo Lançamento
  const [modalAberta, setModalAberta] = useState(false);
  const [tipo, setTipo] = useState<'receber' | 'pagar'>('receber');
  const [descricao, setDescricao] = useState('');
  const [valor, setValor] = useState('');
  const [vencimento, setVencimento] = useState(new Date().toISOString().split('T')[0]);
  const [categoria, setCategoria] = useState('Serviços');
  const [clienteFornecedor, setClienteFornecedor] = useState('');
  const [contaId, setContaId] = useState('');
  const [status, setStatus] = useState<'pendente' | 'pago'>('pago');
  const [salvando, setSalvando] = useState(false);

  // Importação OFX
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [lendoOfx, setLendoOfx] = useState(false);
  const [modalOfxAberta, setModalOfxAberta] = useState(false);
  const [transacoesOfx, setTransacoesOfx] = useState<any[]>([]);
  const [contaOfxId, setContaOfxId] = useState('');
  const [processandoOfx, setProcessandoOfx] = useState(false);

  const carregarDados = () => {
    setLoading(true);
    fetch('/api/financeiro/lancamentos')
      .then((res) => res.json())
      .then((data) => {
        if (Array.isArray(data)) setLancamentos(data);
      })
      .catch((err) => console.error(err))
      .finally(() => setLoading(false));

    fetch('/api/financeiro/contas')
      .then((res) => res.json())
      .then((data) => {
        if (Array.isArray(data)) setContas(data);
      })
      .catch(() => {});
  };

  useEffect(() => {
    carregarDados();
  }, []);

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
          conta_id: contaId || null,
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

  // Upload e leitura do arquivo OFX
  const handleSelecionarArquivoOfx = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    setLendoOfx(true);
    const reader = new FileReader();
    reader.onload = async (evt) => {
      const fileContent = evt.target?.result as string;
      try {
        const res = await fetch('/api/financeiro/upload-ofx', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ fileContent }),
        });
        const data = await res.json();

        if (res.ok && data.ok && Array.isArray(data.transacoes)) {
          setTransacoesOfx(data.transacoes);
          setModalOfxAberta(true);
        } else {
          alert(data.erro || 'Falha ao ler arquivo OFX.');
        }
      } catch (err: any) {
        alert('Erro ao enviar arquivo OFX.');
      } finally {
        setLendoOfx(false);
        if (fileInputRef.current) fileInputRef.current.value = '';
      }
    };
    reader.readAsText(file, 'ISO-8859-1');
  };

  // Confirmar Importação OFX
  const handleImportarOfxConfirmado = async () => {
    if (transacoesOfx.length === 0) return;
    setProcessandoOfx(true);

    try {
      const res = await fetch('/api/financeiro/importar-ofx', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ transacoes: transacoesOfx, contaId: contaOfxId }),
      });

      const data = await res.json();
      if (res.ok && data.ok) {
        setModalOfxAberta(false);
        setTransacoesOfx([]);
        carregarDados();
      } else {
        alert(data.erro || 'Erro ao importar transações.');
      }
    } catch (e) {
      alert('Erro de conexão ao conciliar OFX.');
    } finally {
      setProcessandoOfx(false);
    }
  };

  // Filtragem dos Lançamentos Consolidados de Todas as Contas
  const lancamentosFiltrados = lancamentos.filter((l) => {
    const desc = (l.descricao || '').toLowerCase();
    const cli = (l.cliente_fornecedor || '').toLowerCase();

    if (busca.trim()) {
      const termo = busca.toLowerCase();
      if (!desc.includes(termo) && !cli.includes(termo)) return false;
    }

    if (filtroTipo === 'RECEBER' && l.tipo !== 'receber') return false;
    if (filtroTipo === 'PAGAR' && l.tipo !== 'pagar') return false;

    if (filtroStatus === 'PAGO' && l.status !== 'pago') return false;
    if (filtroStatus === 'PENDENTE' && l.status !== 'pendente') return false;
    if (filtroStatus === 'ATRASADO' && l.status !== 'atrasado') return false;

    if (filtroConta !== 'TODAS' && l.conta_id !== filtroConta) return false;

    return true;
  });

  const totalEntradas = lancamentosFiltrados
    .filter((l) => l.tipo === 'receber' && l.status === 'pago')
    .reduce((acc, l) => acc + parseFloat(l.valor_pago || l.valor || 0), 0);

  const totalSaidas = lancamentosFiltrados
    .filter((l) => l.tipo === 'pagar' && l.status === 'pago')
    .reduce((acc, l) => acc + parseFloat(l.valor_pago || l.valor || 0), 0);

  return (
    <div className="space-y-6 font-sans text-gray-900">
      {/* Input de arquivo OFX escondido */}
      <input
        type="file"
        ref={fileInputRef}
        onChange={handleSelecionarArquivoOfx}
        accept=".ofx,.OFX"
        className="hidden"
      />

      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-xl font-bold text-gray-900 tracking-tight">Lançamentos Financeiros & Extrato Consolidado</h2>
          <p className="text-xs text-gray-500 mt-1">
            Extrato completo de todas as contas bancárias, caixa físico e cartões com conciliação OFX
          </p>
        </div>

        <div className="flex items-center space-x-3">
          <button
            onClick={() => fileInputRef.current?.click()}
            disabled={lendoOfx}
            className="px-4 py-2 bg-emerald-600 text-white hover:bg-emerald-700 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-xs"
          >
            <span className="material-symbols-outlined text-sm leading-none">upload_file</span>
            <span>{lendoOfx ? 'Lendo OFX...' : 'Importar OFX'}</span>
          </button>

          <button
            onClick={() => setModalAberta(true)}
            className="px-4 py-2 bg-black text-white hover:bg-gray-800 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-sm"
          >
            <span className="material-symbols-outlined text-sm leading-none">add</span>
            <span>Novo Lançamento</span>
          </button>
        </div>
      </div>

      {/* Cards de Resumo */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div className="bg-white border border-gray-200/80 p-4 rounded-2xl shadow-2xs">
          <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Total Entradas Baixadas</span>
          <span className="text-xl font-extrabold text-emerald-600 font-mono mt-1 block">
            + R$ {totalEntradas.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
          </span>
        </div>
        <div className="bg-white border border-gray-200/80 p-4 rounded-2xl shadow-2xs">
          <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Total Saídas Liquidadas</span>
          <span className="text-xl font-extrabold text-red-600 font-mono mt-1 block">
            - R$ {totalSaidas.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
          </span>
        </div>
        <div className="bg-white border border-gray-200/80 p-4 rounded-2xl shadow-2xs">
          <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Resultado do Período</span>
          <span
            className={`text-xl font-extrabold font-mono mt-1 block ${
              totalEntradas - totalSaidas >= 0 ? 'text-gray-900' : 'text-red-600'
            }`}
          >
            R$ {(totalEntradas - totalSaidas).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
          </span>
        </div>
      </div>

      {/* Barra de Filtros */}
      <div className="bg-white border border-gray-200/80 p-4 rounded-2xl shadow-2xs space-y-3">
        <div className="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
          {/* Busca por Texto */}
          <div className="relative flex-1">
            <span className="material-symbols-outlined absolute left-3 top-2.5 text-gray-400 text-sm leading-none">
              search
            </span>
            <input
              type="text"
              value={busca}
              onChange={(e) => setBusca(e.target.value)}
              placeholder="Buscar por cliente, fornecedor ou descrição..."
              className="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-black transition font-sans"
            />
          </div>

          {/* Filtro de Tipo */}
          <div className="flex items-center space-x-1">
            {[
              { id: 'TODOS', label: 'Todos os Tipos' },
              { id: 'RECEBER', label: 'Entradas' },
              { id: 'PAGAR', label: 'Saídas' },
            ].map((t) => (
              <button
                key={t.id}
                onClick={() => setFiltroTipo(t.id)}
                className={`px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap ${
                  filtroTipo === t.id ? 'bg-black text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                }`}
              >
                {t.label}
              </button>
            ))}
          </div>

          {/* Filtro por Conta Bancária */}
          <select
            value={filtroConta}
            onChange={(e) => setFiltroConta(e.target.value)}
            className="px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-black"
          >
            <option value="TODAS">Todas as Contas</option>
            {contas.map((c) => (
              <option key={c.id} value={c.id}>
                {c.nome}
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* Tabela Consolidada de Lançamentos */}
      <div className="bg-white border border-gray-200/80 rounded-2xl overflow-hidden shadow-2xs">
        <div className="p-5 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h3 className="font-bold text-gray-900 text-sm">Extrato Consolidado</h3>
            <p className="text-xs text-gray-400">Ordenados por data de pagamento e vencimento</p>
          </div>
          <span className="text-xs font-bold bg-gray-100 text-gray-600 px-3 py-1 rounded-full font-mono">
            {lancamentosFiltrados.length} de {lancamentos.length} lançamentos
          </span>
        </div>

        {loading ? (
          <div className="p-8 text-center text-xs text-gray-400">Carregando lançamentos...</div>
        ) : lancamentosFiltrados.length === 0 ? (
          <div className="p-12 text-center">
            <span className="material-symbols-outlined text-4xl text-gray-300 mb-2 leading-none">receipt_long</span>
            <p className="text-sm font-bold text-gray-800">Nenhum lançamento encontrado.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs font-sans">
              <thead className="bg-gray-50 text-gray-500 uppercase tracking-wider font-bold text-[10px] border-b border-gray-100">
                <tr>
                  <th className="py-3 px-4">Tipo</th>
                  <th className="py-3 px-4">Descrição</th>
                  <th className="py-3 px-4">Cliente / Fornecedor</th>
                  <th className="py-3 px-4">Origem / OFX</th>
                  <th className="py-3 px-4">Vencimento</th>
                  <th className="py-3 px-4">Data Pagamento</th>
                  <th className="py-3 px-4">Valor</th>
                  <th className="py-3 px-4">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {lancamentosFiltrados.map((item) => {
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
                          {isReceber ? 'ENTRADA' : 'SAÍDA'}
                        </span>
                      </td>
                      <td className="py-3 px-4 font-bold text-gray-900">{item.descricao}</td>
                      <td className="py-3 px-4 text-gray-600">{item.cliente_fornecedor || '—'}</td>
                      <td className="py-3 px-4 text-gray-500 font-mono text-[10px]">
                        {item.ofx_fitid ? (
                          <span className="px-2 py-0.5 bg-blue-50 text-blue-700 rounded font-bold">OFX</span>
                        ) : item.asaas_payment_id ? (
                          <span className="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded font-bold">Asaas</span>
                        ) : (
                          <span className="text-gray-400">Manual</span>
                        )}
                      </td>
                      <td className="py-3 px-4 text-gray-600 font-mono">
                        {item.vencimento ? new Date(item.vencimento).toLocaleDateString('pt-BR') : '—'}
                      </td>
                      <td className="py-3 px-4 text-gray-600 font-mono">
                        {item.data_pagamento ? new Date(item.data_pagamento).toLocaleDateString('pt-BR') : '—'}
                      </td>
                      <td className="py-3 px-4 font-mono font-bold text-gray-900">
                        <span className={isReceber ? 'text-emerald-700' : 'text-red-600'}>
                          {isReceber ? '+' : '-'} R$ {valorNum.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                        </span>
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
                          {item.status === 'pago' ? 'RECEBIDO / PAGO' : item.status}
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

      {/* Modal Conciliação OFX */}
      {modalOfxAberta && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-3xl w-full p-6 shadow-2xl space-y-4 text-gray-900">
            <div className="flex items-center justify-between border-b border-gray-100 pb-3">
              <div className="flex items-center space-x-2">
                <span className="material-symbols-outlined text-emerald-600 text-xl leading-none">upload_file</span>
                <h3 className="font-bold text-gray-900 text-base">Conciliação Bancária (OFX)</h3>
              </div>
              <button onClick={() => setModalOfxAberta(false)} className="text-gray-400 hover:text-gray-600">
                <span className="material-symbols-outlined leading-none">close</span>
              </button>
            </div>

            <div className="flex items-center justify-between bg-emerald-50 border border-emerald-200 p-3 rounded-xl">
              <span className="text-xs font-bold text-emerald-800">
                {transacoesOfx.length} transações novas lidas do arquivo OFX
              </span>
              <div className="flex items-center space-x-2">
                <label className="text-xs font-bold text-emerald-900">Vincular à Conta:</label>
                <select
                  value={contaOfxId}
                  onChange={(e) => setContaOfxId(e.target.value)}
                  className="px-2 py-1 bg-white border border-emerald-300 rounded-lg text-xs font-bold"
                >
                  <option value="">Selecione a Conta...</option>
                  {contas.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.nome}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="max-h-64 overflow-y-auto border border-gray-200 rounded-xl">
              <table className="w-full text-left text-xs font-sans">
                <thead className="bg-gray-50 text-gray-500 uppercase font-bold text-[10px] border-b border-gray-200">
                  <tr>
                    <th className="p-3">Data</th>
                    <th className="p-3">Tipo</th>
                    <th className="p-3">Descrição (OFX)</th>
                    <th className="p-3">Valor</th>
                    <th className="p-3">FITID Unique</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {transacoesOfx.map((t, idx) => (
                    <tr key={idx} className="hover:bg-gray-50">
                      <td className="p-3 font-mono">{new Date(t.data).toLocaleDateString('pt-BR')}</td>
                      <td className="p-3">
                        <span
                          className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase ${
                            t.tipo === 'receber' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'
                          }`}
                        >
                          {t.tipo === 'receber' ? 'ENTRADA' : 'SAÍDA'}
                        </span>
                      </td>
                      <td className="p-3 font-bold text-gray-900">{t.descricao}</td>
                      <td className="p-3 font-mono font-bold">
                        R$ {parseFloat(t.valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                      </td>
                      <td className="p-3 font-mono text-gray-400 text-[10px]">{t.fitid}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div className="pt-2 flex justify-end space-x-2">
              <button
                type="button"
                onClick={() => setModalOfxAberta(false)}
                className="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl"
              >
                Cancelar
              </button>
              <button
                type="button"
                onClick={handleImportarOfxConfirmado}
                disabled={processandoOfx}
                className="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-700 transition font-mono"
              >
                {processandoOfx ? 'Conciliando...' : `Confirmar e Importar ${transacoesOfx.length} Transações`}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal Novo Lançamento Manual */}
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
                  <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Conta Bancária</label>
                  <select
                    value={contaId}
                    onChange={(e) => setContaId(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-black outline-none"
                  >
                    <option value="">Nenhuma / Geral</option>
                    {contas.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.nome}
                      </option>
                    ))}
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
