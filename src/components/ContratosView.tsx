import React, { useEffect, useState } from 'react';
import DOMPurify from 'isomorphic-dompurify';

const safeFetchJson = async (url: string, options?: RequestInit) => {
  try {
    const res = await fetch(url, options);
    const text = await res.text();
    let data: any = null;
    try {
      data = JSON.parse(text);
    } catch (e) {
      data = { erro: `Resposta inválida do servidor: ${text.substring(0, 100)}` };
    }
    return { ok: res.ok, status: res.status, data };
  } catch (err: any) {
    return { ok: false, status: 500, data: { erro: err.message || 'Erro de conexão com o servidor' } };
  }
};

export const ContratosView: React.FC = () => {
  const [contratos, setContratos] = useState<any[]>([]);
  const [propostas, setPropostas] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [busca, setBusca] = useState('');
  const [filtroStatus, setFiltroStatus] = useState('TODOS');

  // Modal Novo / Editar Contrato
  const [modalAberta, setModalAberta] = useState(false);
  const [contratoEditandoId, setContratoEditandoId] = useState<string | null>(null);
  const [propostaIdSel, setPropostaIdSel] = useState('');

const [titulo, setTitulo] = useState('');
  const [clienteNome, setClienteNome] = useState('');
  const [clienteCpfCnpj, setClienteCpfCnpj] = useState('');
  const [clienteEmail, setClienteEmail] = useState('');
  const [clienteTelefone, setClienteTelefone] = useState('');
  const [valorTotal, setValorTotal] = useState('');
  const [status, setStatus] = useState('rascunho');
  const [clausulas, setClausulas] = useState('');
  const [formaPagamento, setFormaPagamento] = useState('PIX / Boleto / Cartão de Crédito');
  const [salvando, setSalvando] = useState(false);

  // Noivos / Casal
  const [noivoNome, setNoivoNome] = useState('');
  const [noivoCpf, setNoivoCpf] = useState('');
  const [noivoEmail, setNoivoEmail] = useState('');
  const [noivoTelefone, setNoivoTelefone] = useState('');
  const [noivaNome, setNoivaNome] = useState('');
  const [noivaCpf, setNoivaCpf] = useState('');
  const [noivaEmail, setNoivaEmail] = useState('');
  const [noivaTelefone, setNoivaTelefone] = useState('');
  const [responsavelFinanceiro, setResponsavelFinanceiro] = useState('noivo');

  // Modal Gerar Cobrança Asaas
  const [modalAsaasAberta, setModalAsaasAberta] = useState(false);
  const [contratoAsaas, setContratoAsaas] = useState<any>(null);
  const [asaasParcelas, setAsaasParcelas] = useState('1');
  const [asaasVencimento, setAsaasVencimento] = useState('');
  const [asaasForma, setAsaasForma] = useState('PIX');
  const [gerandoAsaas, setGerandoAsaas] = useState(false);

  // Modal Preview PDF / Impressão Contrato
  const [modalPdfAberta, setModalPdfAberta] = useState(false);
  const [contratoPdf, setContratoPdf] = useState<any>(null);

  const carregarDados = async () => {
    setLoading(true);
    const [resContratos, resPropostas] = await Promise.all([
      safeFetchJson('/api/comercial/contratos'),
      safeFetchJson('/api/comercial/propostas'),
    ]);

    if (resContratos.ok && Array.isArray(resContratos.data)) {
      setContratos(resContratos.data);
    } else {
      setContratos([]);
    }

    if (resPropostas.ok && Array.isArray(resPropostas.data)) {
      setPropostas(resPropostas.data);
    } else {
      setPropostas([]);
    }

    setLoading(false);
  };

  useEffect(() => {
    carregarDados();
  }, []);

  const handlePropostaSelecionada = (pId: string) => {
    setPropostaIdSel(pId);
    if (!pId) return;

    const prop = propostas.find((p) => p.id === pId);
    if (prop) {
      const valProp = parseFloat(prop.valor_total || prop.valor || '0');
      setTitulo(`Contrato de Prestação de Serviços - ${prop.cliente_nome || prop.cliente}`);
      setClienteNome(prop.cliente_nome || prop.cliente || '');
      setNoivoNome(prop.cliente_nome || prop.cliente || '');
      setValorTotal(String(valProp));
      setClausulas(
        `CLÁUSULA 1ª - DO OBJETO: Prestação de serviços de ${prop.tipo?.toUpperCase() || 'PRODUÇÃO AUDIOVISUAL'} conforme proposta "${prop.titulo}".\n\n` +
        `CLÁUSULA 2ª - DOS DIREITOS AUTORAIS E USO DE IMAGEM: A CONTRATADA reserva os direitos autorais com cessão de uso das imagens ao CONTRATANTE.\n\n` +
        `CLÁUSULA 3ª - DA RESCISÃO: O cancelamento sem justa causa com menos de 30 dias do evento acarretará em multa rescisória de 20% do valor total.`
      );
    }
  };

  const handleSalvarContrato = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!titulo.trim() && !clienteNome.trim()) return;

    setSalvando(true);
    try {
      const url = '/api/comercial/contratos';
      const method = contratoEditandoId ? 'PUT' : 'POST';

      const res = await safeFetchJson(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id: contratoEditandoId,
          proposta_id: propostaIdSel || null,
          titulo,
          cliente_nome: clienteNome,
          cliente_cpf_cnpj: clienteCpfCnpj,
          cliente_email: clienteEmail,
          cliente_telefone: clienteTelefone,
          valor_total: parseFloat(valorTotal || '0'),
          status,
          dados: {
            clausulas,
            forma_pagamento: formaPagamento,
            signatario_1: {
              nome: noivoNome || clienteNome,
              cpf: noivoCpf || clienteCpfCnpj,
              email: noivoEmail || clienteEmail,
              telefone: noivoTelefone || clienteTelefone,
            },
            signatario_2: {
              nome: noivaNome,
              cpf: noivaCpf,
              email: noivaEmail,
              telefone: noivaTelefone,
            },
            responsavel_financeiro: responsavelFinanceiro,
          },
        }),
      });

      if (res.ok) {
        setModalAberta(false);
        resetForm();
        carregarDados();
      } else {
        alert(res.data?.erro || 'Erro ao salvar contrato.');
      }
    } catch (err) {
      alert('Erro de conexão ao salvar contrato.');
    } finally {
      setSalvando(false);
    }
  };

  const resetForm = () => {
    setContratoEditandoId(null);
    setPropostaIdSel('');
    setTitulo('');
    setClienteNome('');
    setClienteCpfCnpj('');
    setClienteEmail('');
    setClienteTelefone('');
    setValorTotal('');
    setStatus('rascunho');
    setClausulas('');
    setNoivoNome('');
    setNoivoCpf('');
    setNoivoEmail('');
    setNoivoTelefone('');
    setNoivaNome('');
    setNoivaCpf('');
    setNoivaEmail('');
    setNoivaTelefone('');
    setResponsavelFinanceiro('noivo');
  };

  const handleEditar = (c: any) => {
    setContratoEditandoId(c.id);
    setPropostaIdSel(c.proposta_id || '');
    setTitulo(c.titulo || '');
    setClienteNome(c.cliente_nome || '');
    setClienteCpfCnpj(c.cliente_cpf_cnpj || c.dados?.signatario_1?.cpf || '');
    setClienteEmail(c.cliente_email || c.dados?.signatario_1?.email || '');
    setClienteTelefone(c.cliente_telefone || c.dados?.signatario_1?.telefone || '');
    setValorTotal(String(c.valor_total || '0'));
    setStatus(c.status || 'rascunho');
    setClausulas(c.dados?.clausulas || '');
    setFormaPagamento(c.dados?.forma_pagamento || 'PIX / Boleto / Cartão de Crédito');
    setNoivoNome(c.dados?.signatario_1?.nome || c.cliente_nome || '');
    setNoivoCpf(c.dados?.signatario_1?.cpf || c.cliente_cpf_cnpj || '');
    setNoivoEmail(c.dados?.signatario_1?.email || c.cliente_email || '');
    setNoivoTelefone(c.dados?.signatario_1?.telefone || c.cliente_telefone || '');
    setNoivaNome(c.dados?.signatario_2?.nome || '');
    setNoivaCpf(c.dados?.signatario_2?.cpf || '');
    setNoivaEmail(c.dados?.signatario_2?.email || '');
    setNoivaTelefone(c.dados?.signatario_2?.telefone || '');
    setResponsavelFinanceiro(c.dados?.responsavel_financeiro || 'noivo');
    setModalAberta(true);
  };

  const handleClonar = async (id: string) => {
    if (!confirm('Deseja clonar este contrato? Uma cópia em Rascunho será criada.')) return;
    try {
      const res = await safeFetchJson('/api/comercial/contratos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'clonar', id }),
      });
      if (res.ok) {
        carregarDados();
      } else {
        alert(res.data?.erro || 'Erro ao clonar contrato.');
      }
    } catch (e) {
      alert('Erro ao conectar ao servidor.');
    }
  };

  const handleResetarRascunho = async (id: string) => {
    if (!confirm('Deseja reverter este contrato para RASCUNHO?')) return;
    try {
      const res = await safeFetchJson('/api/comercial/contratos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'resetar', id }),
      });
      if (res.ok) {
        carregarDados();
      } else {
        alert(res.data?.erro || 'Erro ao resetar contrato.');
      }
    } catch (e) {
      alert('Erro ao conectar ao servidor.');
    }
  };

  const handleAbrirModalAsaas = (c: any) => {
    setContratoAsaas(c);
    setAsaasParcelas('1');
    setAsaasVencimento(new Date().toISOString().split('T')[0]);
    setAsaasForma('PIX');
    setModalAsaasAberta(true);
  };

  const handleConfirmarGerarAsaas = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!contratoAsaas) return;

    setGerandoAsaas(true);
    try {
      const res = await safeFetchJson('/api/comercial/contratos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'gerar_asaas',
          id: contratoAsaas.id,
          parcelas: asaasParcelas,
          vencimento: asaasVencimento,
          formaPagamento: asaasForma,
        }),
      });

      if (res.ok) {
        alert(res.data?.mensagem || 'Cobranças geradas com sucesso!');
        setModalAsaasAberta(false);
        carregarDados();
      } else {
        alert(res.data?.erro || 'Erro ao gerar cobrança.');
      }
    } catch (err) {
      alert('Erro de conexão ao gerar cobrança.');
    } finally {
      setGerandoAsaas(false);
    }
  };

  const handleExcluir = async (id: string) => {
    if (!confirm('Deseja realmente excluir este contrato? Essa ação não pode ser desfeita.')) return;
    try {
      const res = await safeFetchJson(`/api/comercial/contratos?id=${id}`, { method: 'DELETE' });
      if (res.ok) {
        carregarDados();
      } else {
        alert(res.data?.erro || 'Erro ao excluir contrato.');
      }
    } catch (e) {
      alert('Erro de conexão ao excluir contrato.');
    }
  };

  const handleToggleStatusAssinado = async (c: any) => {
    const novoStatus = (c.status || '').toLowerCase() === 'assinado' ? 'rascunho' : 'assinado';
    const msg = novoStatus === 'assinado'
      ? 'Marcar este contrato como ASSINADO? (Isso atualizará a proposta vinculada e gerará os lançamentos a receber)'
      : 'Retornar este contrato para RASCUNHO?';

    if (!confirm(msg)) return;

    try {
      const res = await safeFetchJson('/api/comercial/contratos', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          ...c,
          status: novoStatus,
        }),
      });

      if (res.ok) {
        carregarDados();
      } else {
        alert(res.data?.erro || 'Erro ao atualizar status do contrato.');
      }
    } catch (e) {
      alert('Erro ao conectar ao servidor.');
    }
  };

  const handleAbrirPdfModal = (c: any) => {
    setContratoPdf(c);
    setModalPdfAberta(true);
  };

  const contratosFiltrados = contratos.filter((c) => {
    const tit = (c.titulo || '').toLowerCase();
    const cli = (c.cliente_nome || '').toLowerCase();
    const cpf = (c.cliente_cpf_cnpj || '').toLowerCase();

    if (busca.trim()) {
      const termo = busca.toLowerCase();
      if (!tit.includes(termo) && !cli.includes(termo) && !cpf.includes(termo)) return false;
    }

    if (filtroStatus !== 'TODOS') {
      const st = (c.status || '').toLowerCase();
      if (filtroStatus === 'ASSINADO' && st !== 'assinado' && st !== 'aceita') return false;
      if (filtroStatus === 'PENDENTE' && st !== 'pendente' && st !== 'pendente_assinatura') return false;
      if (filtroStatus === 'RASCUNHO' && st !== 'rascunho') return false;
      if (filtroStatus === 'CANCELADO' && st !== 'cancelado') return false;
    }

    return true;
  });

  const totalValor = contratosFiltrados.reduce((acc, c) => acc + parseFloat(c.valor_total || 0), 0);
  const totalAssinados = contratosFiltrados.filter((c) => {
    const s = (c.status || '').toLowerCase();
    return s === 'assinado' || s === 'aceita';
  }).length;

  return (
    <div className="space-y-6 font-sans text-white bg-[#050505] min-h-screen">
      {/* Header Superior com Título e Ações */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-white tracking-tight flex items-center space-x-2">
            <span className="material-symbols-outlined text-zinc-500">scroll</span>
            <span>Contratos Comerciais</span>
          </h1>
          <p className="text-xs text-zinc-400 mt-0.5">Gerencie, envie para assinatura eletrônica e acompanhe a formalização das propostas</p>
        </div>

        <div className="flex items-center space-x-2">
          <button
            onClick={() => {
              resetForm();
              setModalAberta(true);
            }}
            className="px-4 py-2 bg-white hover:bg-zinc-200 text-black font-bold rounded-xl text-xs transition flex items-center space-x-1.5 shadow-sm"
          >
            <span className="material-symbols-outlined text-sm leading-none">add</span>
            <span>Novo Contrato</span>
          </button>
        </div>
      </div>

      {/* Cards de Métricas Superiores */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="bg-[#0c0c0c] border border-white/10 p-5 rounded-2xl shadow-2xs flex items-center justify-between">
          <div>
            <span className="text-[10px] font-bold tracking-wider text-zinc-400 uppercase block">TOTAL DE CONTRATOS</span>
            <div className="text-2xl font-black font-mono text-white mt-1">{contratosFiltrados.length}</div>
            <span className="text-[10px] font-bold text-zinc-500 block uppercase mt-0.5">REGISTRADOS NO BANCO</span>
          </div>
          <div className="w-10 h-10 rounded-xl bg-blue-950/60 text-blue-400 border border-blue-500/30 flex items-center justify-center">
            <span className="material-symbols-outlined text-lg leading-none">history_edu</span>
          </div>
        </div>

        <div className="bg-[#0c0c0c] border border-white/10 p-5 rounded-2xl shadow-2xs flex items-center justify-between">
          <div>
            <span className="text-[10px] font-bold tracking-wider text-zinc-400 uppercase block">CONTRATOS ASSINADOS</span>
            <div className="text-2xl font-black font-mono text-emerald-400 mt-1">{totalAssinados}</div>
            <span className="text-[10px] font-bold text-emerald-400 block uppercase mt-0.5">FORMALIZADOS & ATIVOS</span>
          </div>
          <div className="w-10 h-10 rounded-xl bg-emerald-950/60 text-emerald-400 border border-emerald-500/30 flex items-center justify-center">
            <span className="material-symbols-outlined text-lg leading-none">verified</span>
          </div>
        </div>

        <div className="bg-[#0c0c0c] border border-white/10 p-5 rounded-2xl shadow-2xs flex items-center justify-between">
          <div>
            <span className="text-[10px] font-bold tracking-wider text-zinc-400 uppercase block">VALOR EM CONTRATOS</span>
            <div className="text-2xl font-black font-mono text-purple-400 mt-1">
              R$ {totalValor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
            </div>
            <span className="text-[10px] font-bold text-purple-400 block uppercase mt-0.5">VALOR TOTAL CONTRATADO</span>
          </div>
          <div className="w-10 h-10 rounded-xl bg-purple-950/60 text-purple-400 border border-purple-500/30 flex items-center justify-center">
            <span className="material-symbols-outlined text-lg leading-none">payments</span>
          </div>
        </div>
      </div>

      {/* Barra de Busca e Filtros */}
      <div className="bg-[#0c0c0c] border border-white/10 p-4 rounded-2xl shadow-2xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        <div className="relative flex-1">
          <span className="material-symbols-outlined absolute left-3.5 top-2.5 text-zinc-400 text-sm leading-none">
            search
          </span>
          <input
            type="text"
            value={busca}
            onChange={(e) => setBusca(e.target.value)}
            placeholder="Buscar por título, contratante ou CPF/CNPJ..."
            className="w-full pl-9 pr-4 py-2 bg-zinc-900 border border-zinc-700 rounded-xl text-xs text-white placeholder-zinc-500 focus:border-white outline-none transition font-sans"
          />
        </div>

        <div className="flex items-center space-x-2">
          <select
            value={filtroStatus}
            onChange={(e) => setFiltroStatus(e.target.value)}
            className="px-3 py-2 bg-zinc-900 border border-zinc-700 rounded-xl text-xs font-bold text-white outline-none uppercase"
          >
            <option value="TODOS">TODOS OS STATUS</option>
            <option value="ASSINADO">ASSINADO / ATIVO</option>
            <option value="PENDENTE">PENDENTE DE ASSINATURA</option>
            <option value="RASCUNHO">RASCUNHO</option>
            <option value="CANCELADO">CANCELADO</option>
          </select>
        </div>
      </div>

      {/* Tabela de Contratos em Tema Escuro */}
      <div className="bg-[#0c0c0c] border border-white/10 rounded-2xl overflow-hidden shadow-2xs">
        {loading ? (
          <div className="p-12 text-center text-xs text-zinc-500">Carregando contratos comerciais...</div>
        ) : contratosFiltrados.length === 0 ? (
          <div className="p-16 text-center">
            <span className="material-symbols-outlined text-5xl text-zinc-600 mb-2 leading-none">scroll</span>
            <p className="text-sm font-bold text-white mb-1">Nenhum contrato encontrado no banco de dados.</p>
            <p className="text-xs text-zinc-400 max-w-md mx-auto">
              Clique em <strong>Novo Contrato</strong> no canto superior direito para criar um contrato do zero ou selecione uma proposta web existente para gerar um contrato automaticamente!
            </p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs font-sans">
              <thead className="bg-[#121212] text-zinc-400 uppercase tracking-wider font-bold text-[10px] border-b border-white/10">
                <tr>
                  <th className="py-3 px-4">TÍTULO DO CONTRATO</th>
                  <th className="py-3 px-4">CONTRATANTE</th>
                  <th className="py-3 px-4 text-center">PROPOSTA VINCULADA</th>
                  <th className="py-3 px-4 text-center">DATA EMISSÃO</th>
                  <th className="py-3 px-4 text-right">VALOR TOTAL</th>
                  <th className="py-3 px-4 text-center">STATUS</th>
                  <th className="py-3 px-4 text-right">AÇÕES</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-white/5 font-sans">
                {contratosFiltrados.map((c) => {
                  const tit = c.titulo || 'Contrato Sem Título';
                  const cli = c.cliente_nome || 'Cliente Geral';
                  const val = parseFloat(c.valor_total || 0);
                  const stRaw = (c.status || 'rascunho').toLowerCase();
                  const dtStr = c.criado_em || c.created_at;

                  let badgeBg = 'bg-zinc-800 text-zinc-300';
                  let statusTxt = 'Rascunho';

                  if (stRaw === 'assinado' || stRaw === 'aceita') {
                    badgeBg = c.asaas_cobranca_gerada === 1 ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-500/30' : 'bg-amber-950/60 text-amber-400 border border-amber-500/30';
                    statusTxt = c.asaas_cobranca_gerada === 1 ? '✓ Assinado' : '✓ Assinado • Cobrança Pendente';
                  } else if (stRaw === 'pendente' || stRaw === 'pendente_assinatura') {
                    badgeBg = 'bg-blue-950/60 text-blue-400 border border-blue-500/30';
                    statusTxt = 'Pendente Assinatura';
                  } else if (stRaw === 'cancelado') {
                    badgeBg = 'bg-rose-950/60 text-rose-400 border border-rose-500/30';
                    statusTxt = 'Cancelado';
                  }

                  return (
                    <tr key={c.id} className="hover:bg-zinc-900/60 transition">
                      <td className="py-3.5 px-4 font-bold text-white text-xs">
                        {tit}
                        {c.cliente_cpf_cnpj && <span className="block text-[10px] text-zinc-400 font-mono">CPF/CNPJ: {c.cliente_cpf_cnpj}</span>}
                      </td>
                      <td className="py-3.5 px-4 text-zinc-300 text-xs font-medium">
                        {cli}
                        {c.cliente_email && <span className="block text-[10px] text-zinc-400">{c.cliente_email}</span>}
                      </td>
                      <td className="py-3.5 px-4 text-center">
                        {c.proposta_slug ? (
                          <a
                            href={`/p/${c.proposta_slug}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-block px-2.5 py-1 bg-purple-950/60 text-purple-400 border border-purple-500/30 rounded-lg text-[10px] font-bold transition hover:bg-purple-900/60"
                          >
                            Proposta #{c.proposta_slug.substring(0, 8)}
                          </a>
                        ) : (
                          <span className="text-gray-400 text-[10px]">Manual</span>
                        )}
                      </td>
                      <td className="py-3.5 px-4 text-center font-mono text-gray-500 text-xs">
                        {dtStr ? new Date(dtStr).toLocaleDateString('pt-BR') : '—'}
                      </td>
                      <td className="py-3.5 px-4 text-right font-mono font-bold text-xs text-gray-900">
                        R$ {val.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                      </td>
                      <td className="py-3.5 px-4 text-center">
                        <span className={`inline-block px-3 py-1 rounded-full text-[10px] font-bold ${badgeBg}`}>
                          {statusTxt}
                        </span>
                      </td>
                      <td className="py-3.5 px-4 text-right">
                        <div className="flex items-center justify-end space-x-1">
                          {/* Visualizar PDF em Nova Aba */}
                          <a
                            href={`/api/contratos/pdf?id=${c.id}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="p-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-xs font-bold transition flex items-center"
                            title="Visualizar Contrato Completo em Nova Aba"
                          >
                            <span className="material-symbols-outlined text-sm leading-none">open_in_new</span>
                          </a>

                          {/* Visualizar PDF em Modal */}
                          <button
                            onClick={() => handleAbrirPdfModal(c)}
                            className="p-1.5 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-xs font-bold transition flex items-center"
                            title="Visualizar no Modal"
                          >
                            <span className="material-symbols-outlined text-sm leading-none">visibility</span>
                          </button>

                          {/* Gerar Cobrança Asaas */}
                          <button
                            onClick={() => handleAbrirModalAsaas(c)}
                            className="p-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 rounded-lg text-xs font-bold transition flex items-center"
                            title="Gerar Cobrança Asaas"
                          >
                            <span className="material-symbols-outlined text-sm leading-none">account_balance_wallet</span>
                          </button>

                          {/* Clonar Contrato */}
                          <button
                            onClick={() => handleClonar(c.id)}
                            className="p-1.5 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-xs font-bold transition flex items-center"
                            title="Clonar Contrato"
                          >
                            <span className="material-symbols-outlined text-sm leading-none">content_copy</span>
                          </button>

                          {/* Reverter para Rascunho */}
                          <button
                            onClick={() => handleResetarRascunho(c.id)}
                            className="p-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold transition flex items-center"
                            title="Reverter para Rascunho"
                          >
                            <span className="material-symbols-outlined text-sm leading-none">restart_alt</span>
                          </button>

                          {/* Marcar Assinado */}
                          <button
                            onClick={() => handleToggleStatusAssinado(c)}
                            className={`p-1.5 border rounded-lg text-xs font-bold transition flex items-center ${
                              stRaw === 'assinado'
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                : 'bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200'
                            }`}
                            title="Alternar Status Assinado"
                          >
                            <span className="material-symbols-outlined text-sm leading-none">verified</span>
                          </button>

                          {/* Editar */}
                          <button
                            onClick={() => handleEditar(c)}
                            className="p-1.5 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-xs font-bold transition flex items-center"
                            title="Editar Contrato"
                          >
                            <span className="material-symbols-outlined text-sm leading-none">edit</span>
                          </button>

                          {/* Excluir */}
                          <button
                            onClick={() => handleExcluir(c.id)}
                            className="p-1.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs font-bold transition flex items-center"
                            title="Excluir Contrato"
                          >
                            <span className="material-symbols-outlined text-sm leading-none">delete</span>
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Modal Criar / Editar Contrato */}
      {modalAberta && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
          <div className="bg-white border border-gray-200 rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4 text-gray-900 max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between border-b border-gray-100 pb-3">
              <h3 className="font-bold text-gray-900 text-base">
                {contratoEditandoId ? 'Editar Contrato Comercial' : 'Novo Contrato Comercial'}
              </h3>
              <button onClick={() => setModalAberta(false)} className="text-gray-400 hover:text-gray-600">
                <span className="material-symbols-outlined leading-none">close</span>
              </button>
            </div>

            <form onSubmit={handleSalvarContrato} className="space-y-4">
              {/* Selecionar de Proposta */}
              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">
                  Gerar a partir de Proposta Existente (Opcional)
                </label>
                <select
                  value={propostaIdSel}
                  onChange={(e) => handlePropostaSelecionada(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-bold bg-white"
                >
                  <option value="">-- Seleção Manual sem Proposta --</option>
                  {propostas.map((p) => (
                    <option key={p.id} value={p.id}>
                      Proposta: {p.titulo} ({p.cliente_nome || p.cliente}) - R$ {parseFloat(p.valor_total || p.valor || 0).toLocaleString('pt-BR')}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Título do Contrato</label>
                <input
                  type="text"
                  required
                  value={titulo}
                  onChange={(e) => setTitulo(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-bold"
                  placeholder="Ex: Contrato de Prestação de Serviços Fotográficos"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Nome do Contratante</label>
                  <input
                    type="text"
                    required
                    value={clienteNome}
                    onChange={(e) => setClienteNome(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-bold"
                    placeholder="Nome completo do contratante"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">CPF ou CNPJ</label>
                  <input
                    type="text"
                    value={clienteCpfCnpj}
                    onChange={(e) => setClienteCpfCnpj(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-mono"
                    placeholder="000.000.000-00"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">E-mail de Assinatura</label>
                  <input
                    type="email"
                    value={clienteEmail}
                    onChange={(e) => setClienteEmail(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none"
                    placeholder="email@cliente.com"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">WhatsApp / Telefone</label>
                  <input
                    type="text"
                    value={clienteTelefone}
                    onChange={(e) => setClienteTelefone(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-mono"
                    placeholder="(27) 99999-9999"
                  />
                </div>
              </div>

              {/* Dados do Noivo */}
              <div className="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-3">
                <h4 className="text-xs font-extrabold text-gray-700 uppercase flex items-center space-x-1.5">
                  <span className="material-symbols-outlined text-sm text-purple-600">man</span>
                  <span>Noivo (Contratante 1)</span>
                </h4>
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Nome do Noivo</label>
                    <input
                      type="text"
                      value={noivoNome}
                      onChange={(e) => setNoivoNome(e.target.value)}
                      className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-bold bg-white"
                      placeholder="Nome completo do noivo"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">CPF do Noivo</label>
                    <input
                      type="text"
                      value={noivoCpf}
                      onChange={(e) => setNoivoCpf(e.target.value)}
                      className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-mono bg-white"
                      placeholder="000.000.000-00"
                    />
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">E-mail do Noivo</label>
                    <input
                      type="email"
                      value={noivoEmail}
                      onChange={(e) => setNoivoEmail(e.target.value)}
                      className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none bg-white"
                      placeholder="email@noivo.com"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Telefone do Noivo</label>
                    <input
                      type="text"
                      value={noivoTelefone}
                      onChange={(e) => setNoivoTelefone(e.target.value)}
                      className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-mono bg-white"
                      placeholder="(27) 99999-8888"
                    />
                  </div>
                </div>
              </div>

              {/* Dados da Noiva */}
              <div className="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-3">
                <h4 className="text-xs font-extrabold text-gray-700 uppercase flex items-center space-x-1.5">
                  <span className="material-symbols-outlined text-sm text-pink-600">woman</span>
                  <span>Noiva (Contratante 2)</span>
                </h4>
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Nome da Noiva</label>
                    <input
                      type="text"
                      value={noivaNome}
                      onChange={(e) => setNoivaNome(e.target.value)}
                      className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-bold bg-white"
                      placeholder="Nome completo da noiva"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">CPF da Noiva</label>
                    <input
                      type="text"
                      value={noivaCpf}
                      onChange={(e) => setNoivaCpf(e.target.value)}
                      className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-mono bg-white"
                      placeholder="000.000.000-00"
                    />
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">E-mail da Noiva</label>
                    <input
                      type="email"
                      value={noivaEmail}
                      onChange={(e) => setNoivaEmail(e.target.value)}
                      className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none bg-white"
                      placeholder="email@noiva.com"
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold uppercase text-gray-500 mb-1">Telefone da Noiva</label>
                    <input
                      type="text"
                      value={noivaTelefone}
                      onChange={(e) => setNoivaTelefone(e.target.value)}
                      className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-mono bg-white"
                      placeholder="(27) 88888-7777"
                    />
                  </div>
                </div>
              </div>

              {/* Responsável Financeiro */}
              <div className="bg-purple-50 border border-purple-200 rounded-xl p-4">
                <label className="block text-[10px] font-bold uppercase text-purple-700 mb-2">Quem é o Responsável Financeiro?</label>
                <div className="flex items-center space-x-6">
                  <label className="flex items-center space-x-2 cursor-pointer">
                    <input
                      type="radio"
                      name="responsavel"
                      value="noivo"
                      checked={responsavelFinanceiro === 'noivo'}
                      onChange={(e) => setResponsavelFinanceiro(e.target.value)}
                      className="text-purple-600 focus:ring-purple-500"
                    />
                    <span className="text-xs font-bold text-gray-700">{noivoNome || 'Noivo'}</span>
                  </label>
                  <label className="flex items-center space-x-2 cursor-pointer">
                    <input
                      type="radio"
                      name="responsavel"
                      value="noiva"
                      checked={responsavelFinanceiro === 'noiva'}
                      onChange={(e) => setResponsavelFinanceiro(e.target.value)}
                      className="text-purple-600 focus:ring-purple-500"
                    />
                    <span className="text-xs font-bold text-gray-700">{noivaNome || 'Noiva'}</span>
                  </label>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Valor Total (R$)</label>
                  <input
                    type="number"
                    step="0.01"
                    value={valorTotal}
                    onChange={(e) => setValorTotal(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-mono font-bold"
                    placeholder="0.00"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Status do Contrato</label>
                  <select
                    value={status}
                    onChange={(e) => setStatus(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-bold bg-white"
                  >
                    <option value="rascunho">Rascunho</option>
                    <option value="pendente_assinatura">Pendente de Assinatura</option>
                    <option value="assinado">✓ Assinado / Formalizado</option>
                    <option value="cancelado">Cancelado</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Cláusulas e Objeto do Contrato</label>
                <textarea
                  rows={6}
                  value={clausulas}
                  onChange={(e) => setClausulas(e.target.value)}
                  className="w-full p-3 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none leading-relaxed font-mono"
                  placeholder="Escreva os termos, obrigações e cláusulas do contrato comercial..."
                />
              </div>

              <div className="pt-3 flex justify-end space-x-2 border-t border-gray-100">
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
                  className="px-5 py-2 bg-black text-white text-xs font-bold rounded-xl hover:bg-gray-800 transition shadow-md"
                >
                  {salvando ? 'Salvando...' : 'Salvar Contrato'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal Gerar Cobrança Asaas */}
      {modalAsaasAberta && contratoAsaas && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
          <div className="bg-white border border-gray-200 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 text-gray-900">
            <div className="flex items-center justify-between border-b border-gray-100 pb-3">
              <h3 className="font-bold text-gray-900 text-base flex items-center space-x-2">
                <span className="material-symbols-outlined text-purple-600">account_balance_wallet</span>
                <span>Gerar Cobrança no Asaas</span>
              </h3>
              <button onClick={() => setModalAsaasAberta(false)} className="text-gray-400 hover:text-gray-600">
                <span className="material-symbols-outlined leading-none">close</span>
              </button>
            </div>

            <form onSubmit={handleConfirmarGerarAsaas} className="space-y-4">
              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-500">Contrato</label>
                <div className="text-xs font-bold text-gray-900">{contratoAsaas.titulo}</div>
                <div className="text-xs font-mono text-purple-700 font-bold mt-0.5">
                  R$ {parseFloat(contratoAsaas.valor_total || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Número de Parcelas</label>
                  <input
                    type="number"
                    min="1"
                    max="60"
                    value={asaasParcelas}
                    onChange={(e) => setAsaasParcelas(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 font-bold focus:ring-2 focus:ring-black outline-none"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Forma de Pagamento</label>
                  <select
                    value={asaasForma}
                    onChange={(e) => setAsaasForma(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 font-bold bg-white focus:ring-2 focus:ring-black outline-none"
                  >
                    <option value="PIX">PIX</option>
                    <option value="BOLETO">Boleto Bancário</option>
                    <option value="CREDIT_CARD">Cartão de Crédito</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Vencimento da 1ª Parcela</label>
                <input
                  type="date"
                  required
                  value={asaasVencimento}
                  onChange={(e) => setAsaasVencimento(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 font-bold focus:ring-2 focus:ring-black outline-none"
                />
              </div>

              <div className="pt-3 flex justify-end space-x-2 border-t border-gray-100">
                <button
                  type="button"
                  onClick={() => setModalAsaasAberta(false)}
                  className="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={gerandoAsaas}
                  className="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl transition shadow-md"
                >
                  {gerandoAsaas ? 'Gerando...' : 'Confirmar & Gerar Cobrança'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal Preview do Contrato Formatado em PDF ou HTML Completo */}
      {modalPdfAberta && contratoPdf && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
          <div className="bg-white border border-gray-200 rounded-2xl max-w-4xl w-full p-6 shadow-2xl space-y-4 text-gray-900 max-h-[90vh] flex flex-col">
            <div className="flex items-center justify-between border-b border-gray-200 pb-3">
              <div>
                <span className="text-[10px] font-bold uppercase text-gray-400 block tracking-widest">CONTRATO COMERCIAL</span>
                <h3 className="font-extrabold text-gray-900 text-base">{contratoPdf.titulo}</h3>
              </div>
              <div className="flex items-center space-x-2">
                <a
                  href={`/api/contratos/pdf?id=${contratoPdf.id}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="px-3.5 py-1.5 bg-black text-white rounded-xl text-xs font-bold transition flex items-center space-x-1"
                >
                  <span className="material-symbols-outlined text-sm leading-none">open_in_new</span>
                  <span>Abrir Documento Completo</span>
                </a>
                <button onClick={() => setModalPdfAberta(false)} className="text-gray-400 hover:text-gray-600">
                  <span className="material-symbols-outlined leading-none">close</span>
                </button>
              </div>
            </div>

            {/* Documento Formatado / HTML Completo */}
            <div className="flex-1 overflow-y-auto p-4 border rounded-xl bg-gray-50">
              {contratoPdf.dados?.contrato_texto ? (
                <div
                  className="bg-white p-6 rounded-lg shadow-xs leading-relaxed text-xs text-gray-900 font-sans"
                  dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(contratoPdf.dados.contrato_texto) }}
                />
              ) : (
                <div className="space-y-6 text-xs text-gray-800 leading-relaxed font-sans bg-white p-6 rounded-lg shadow-xs">
                  <div className="text-center space-y-1 border-b pb-4">
                    <h2 className="font-black text-base uppercase tracking-tight text-gray-900">CONTRATO DE PRESTAÇÃO DE SERVIÇOS</h2>
                    <p className="text-[11px] text-gray-500 font-mono">ERP DISTINTO • PONCEM STUDIO LTDA</p>
                  </div>

                  <div className="space-y-2">
                    <p>
                      <strong>CONTRATADA:</strong> PONCEM STUDIO LTDA, empresa especializada em produção audiovisual e gestão de imagem.
                    </p>
                    <p>
                      <strong>CONTRATANTE:</strong> {contratoPdf.cliente_nome}
                      {contratoPdf.cliente_cpf_cnpj ? `, inscrito(a) no CPF/CNPJ sob o nº ${contratoPdf.cliente_cpf_cnpj}` : ''}
                      {contratoPdf.cliente_email ? `, e-mail: ${contratoPdf.cliente_email}` : ''}.
                    </p>
                  </div>

                  <div className="space-y-2">
                    <h4 className="font-bold text-gray-900 uppercase">VALOR E CONDIÇÕES DE PAGAMENTO</h4>
                    <p>
                      O valor total do presente contrato é de <strong>R$ {parseFloat(contratoPdf.valor_total || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</strong>, 
                      referente ao escopo acordado.
                    </p>
                  </div>

                  {contratoPdf.dados?.clausulas && (
                    <div className="space-y-2 pt-2 border-t border-gray-200">
                      <h4 className="font-bold text-gray-900 uppercase">CLÁUSULAS E TERMOS</h4>
                      <div className="whitespace-pre-line leading-relaxed text-gray-700">
                        {contratoPdf.dados.clausulas}
                      </div>
                    </div>
                  )}

                  <div className="pt-8 grid grid-cols-2 gap-8 text-center border-t border-gray-200 mt-8">
                    <div>
                      <div className="border-t border-gray-400 pt-2 font-bold">PONCEM STUDIO LTDA</div>
                      <span className="text-[10px] text-gray-400">CONTRATADA</span>
                    </div>
                    <div>
                      <div className="border-t border-gray-400 pt-2 font-bold">{contratoPdf.cliente_nome}</div>
                      <span className="text-[10px] text-gray-400">CONTRATANTE</span>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
