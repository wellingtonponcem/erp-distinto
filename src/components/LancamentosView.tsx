import React, { useEffect, useState, useRef } from 'react';

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

export const LancamentosView: React.FC = () => {
  const [lancamentos, setLancamentos] = useState<any[]>([]);
  const [contas, setContas] = useState<any[]>([]);
  const [categorias, setCategorias] = useState<string[]>([]);
  const [loading, setLoading] = useState(true);

  // Filtros
  const [busca, setBusca] = useState('');
  const [filtroTipo, setFiltroTipo] = useState('TODOS');
  const [filtroStatus, setFiltroStatus] = useState('TODOS');
  const [filtroConta, setFiltroConta] = useState('TODAS');
  const [filtroCategoria, setFiltroCategoria] = useState('TODAS');
  const [filtroConciliacao, setFiltroConciliacao] = useState('TODOS');

  // Filtros Temporais (Mês Atual por padrão)
  const hoje = new Date();
  const [filtroPeriodo, setFiltroPeriodo] = useState<'MES' | 'SEMANA' | 'ANO' | 'TODOS' | 'CUSTOM'>('MES');
  const [anoSel, setAnoSel] = useState<number>(hoje.getFullYear());
  const [mesSel, setMesSel] = useState<number>(hoje.getMonth()); // 0 a 11
  const [dataInicioCustom, setDataInicioCustom] = useState<string>('');
  const [dataFimCustom, setDataFimCustom] = useState<string>('');

  // Seleção Múltipla
  const [selecionados, setSelecionados] = useState<string[]>([]);

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

  // Modal Edição de Lançamento
  const [modalEdicaoAberta, setModalEdicaoAberta] = useState(false);
  const [itemEdicao, setItemEdicao] = useState<any>(null);
  const [editDescricao, setEditDescricao] = useState('');
  const [editCategoria, setEditCategoria] = useState('Serviços');
  const [editValor, setEditValor] = useState('');
  const [editTipo, setEditTipo] = useState<'receber' | 'pagar'>('receber');
  const [editVencimento, setEditVencimento] = useState('');
  const [editClienteFornecedor, setEditClienteFornecedor] = useState('');
  const [editContaId, setEditContaId] = useState('');
  const [editStatus, setEditStatus] = useState('pago');
  const [salvandoEdicao, setSalvandoEdicao] = useState(false);

  // Modal Gerenciar Categorias
  const [modalCategoriasAberta, setModalCategoriasAberta] = useState(false);
  const [novaCategoriaNome, setNovaCategoriaNome] = useState('');
  const [criandoCategoria, setCriandoCategoria] = useState(false);

  // Importação OFX
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [lendoOfx, setLendoOfx] = useState(false);
  const [modalOfxAberta, setModalOfxAberta] = useState(false);
  const [transacoesOfx, setTransacoesOfx] = useState<any[]>([]);
  const [categoriaGlobalOfx, setCategoriaGlobalOfx] = useState('Outros');
  const [totalIgnoradasOfx, setTotalIgnoradasOfx] = useState(0);
  const [bancoAutoDetectado, setBancoAutoDetectado] = useState('');
  const [contaOfxId, setContaOfxId] = useState('');
  const [processandoOfx, setProcessandoOfx] = useState(false);

  const carregarCategorias = async () => {
    const res = await safeFetchJson('/api/financeiro/categorias');
    if (res.ok && Array.isArray(res.data)) {
      const nomes = res.data.map((c: any) => c.nome);
      setCategorias(nomes);
    } else {
      setCategorias([
        'Serviços', 'Fotografia', 'Vídeo', 'Design', 'Desenvolvimento',
        'Marketing', 'Hospedagem & Servidores', 'Impostos', 'Aluguel',
        'Folha de Pagamento', 'Equipamentos', 'Alimentação', 'Transporte',
        'Asaas', 'Transferência Asaas', 'Outros'
      ]);
    }
  };

  const carregarDados = async () => {
    setLoading(true);
    const resLanc = await safeFetchJson('/api/financeiro/lancamentos');
    if (resLanc.ok && Array.isArray(resLanc.data)) {
      setLancamentos(resLanc.data);
    }

    const resContas = await safeFetchJson('/api/financeiro/contas');
    if (resContas.ok && Array.isArray(resContas.data)) {
      setContas(resContas.data);
    }

    await carregarCategorias();
    setLoading(false);
  };

  useEffect(() => {
    carregarDados();
  }, []);

  // Criar Nova Categoria no Banco
  const handleCriarCategoria = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!novaCategoriaNome.trim()) return;

    setCriandoCategoria(true);
    try {
      const res = await safeFetchJson('/api/financeiro/categorias', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nome: novaCategoriaNome.trim() }),
      });
      if (res.ok) {
        setNovaCategoriaNome('');
        carregarCategorias();
      } else {
        alert(res.data?.erro || 'Erro ao criar categoria.');
      }
    } catch (e) {
      alert('Erro de conexão ao criar categoria.');
    } finally {
      setCriandoCategoria(false);
    }
  };

  // Excluir Categoria
  const handleExcluirCategoria = async (nomeCat: string) => {
    if (!confirm(`Deseja excluir a categoria "${nomeCat}"?`)) return;

    try {
      const resCategorias = await safeFetchJson('/api/financeiro/categorias');
      if (resCategorias.ok && Array.isArray(resCategorias.data)) {
        const target = resCategorias.data.find((c: any) => c.nome.toLowerCase() === nomeCat.toLowerCase());

        if (target) {
          await safeFetchJson(`/api/financeiro/categorias?id=${target.id}`, { method: 'DELETE' });
          carregarCategorias();
        }
      }
    } catch (e) {
      alert('Erro ao excluir categoria.');
    }
  };

  const handleCriarLancamento = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!descricao || !valor || !vencimento) return;

    setSalvando(true);
    try {
      const valorNum = parseFloat(valor);
      const valorPago = status === 'pago' ? valorNum : 0;

      const res = await safeFetchJson('/api/financeiro/lancamentos', {
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
      } else {
        alert(res.data?.erro || 'Erro ao criar lançamento.');
      }
    } catch (err) {
      console.error(err);
    } finally {
      setSalvando(false);
    }
  };

  // Abrir Modal de Edição
  const abrirEdicao = (item: any) => {
    setItemEdicao(item);
    setEditDescricao(item.descricao || '');
    setEditCategoria(item.categoria || 'Serviços');
    setEditValor(String(item.valor || '0'));
    setEditTipo(item.tipo === 'pagar' || item.status === 'saida' ? 'pagar' : 'receber');
    setEditVencimento(item.vencimento ? new Date(item.vencimento).toISOString().split('T')[0] : '');
    setEditClienteFornecedor(item.cliente_fornecedor || '');
    setEditContaId(item.conta_id || '');
    setEditStatus(item.status || 'pago');
    setModalEdicaoAberta(true);
  };

  // Salvar Edição de Lançamento
  const handleSalvarEdicao = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!itemEdicao || !editDescricao) return;

    setSalvandoEdicao(true);
    try {
      const isConciliado = Number(itemEdicao.conciliado) === 1 || Boolean(itemEdicao.ofx_fitid) || Boolean(itemEdicao.asaas_id) || Boolean(itemEdicao.asaas_payment_id);

      const payload = isConciliado
        ? {
            id: itemEdicao.id,
            descricao: editDescricao,
            categoria: editCategoria,
            tipo: itemEdicao.tipo,
            valor: itemEdicao.valor,
            vencimento: itemEdicao.vencimento,
          }
        : {
            id: itemEdicao.id,
            descricao: editDescricao,
            categoria: editCategoria,
            tipo: editTipo,
            valor: parseFloat(editValor || '0'),
            vencimento: editVencimento,
            cliente_fornecedor: editClienteFornecedor,
            conta_id: editContaId || null,
            status: editStatus,
          };

      const res = await safeFetchJson('/api/financeiro/lancamentos', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });

      if (res.ok) {
        setModalEdicaoAberta(false);
        setItemEdicao(null);
        carregarDados();
      } else {
        alert(res.data?.erro || 'Erro ao salvar alterações.');
      }
    } catch (err) {
      alert('Erro ao conectar ao servidor.');
    } finally {
      setSalvandoEdicao(false);
    }
  };

  // Excluir Lançamento Manual (Proibido para Conciliados)
  const handleExcluirLancamento = async (item: any) => {
    const isConciliado = Number(item.conciliado) === 1 || Boolean(item.ofx_fitid) || Boolean(item.asaas_id) || Boolean(item.asaas_payment_id);
    if (isConciliado) {
      alert('⚠️ Lançamentos conciliados via OFX ou Asaas não podem ser excluídos.');
      return;
    }

    if (!confirm(`Deseja realmente excluir o lançamento "${item.descricao}"?`)) return;

    try {
      const res = await safeFetchJson(`/api/financeiro/lancamentos?id=${item.id}`, {
        method: 'DELETE',
      });
      if (res.ok) {
        if (modalEdicaoAberta) setModalEdicaoAberta(false);
        carregarDados();
      } else {
        alert(res.data?.erro || 'Erro ao excluir lançamento.');
      }
    } catch (err) {
      alert('Erro de conexão ao excluir.');
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
        const res = await safeFetchJson('/api/financeiro/upload-ofx', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ fileContent }),
        });

        if (res.ok && res.data?.ok && Array.isArray(res.data?.transacoes)) {
          const txnsComCategoria = res.data.transacoes.map((t: any) => ({
            ...t,
            categoria: t.categoria || 'Outros',
          }));

          setTransacoesOfx(txnsComCategoria);
          setTotalIgnoradasOfx(res.data.totalIgnoradas || 0);

          if (res.data.bancoDetectado && res.data.bancoDetectado.id) {
            setContaOfxId(res.data.bancoDetectado.id);
            setBancoAutoDetectado(res.data.bancoDetectado.nome);
          } else {
            setBancoAutoDetectado(res.data.orgDetectada || '');
            setContaOfxId('');
          }

          setModalOfxAberta(true);
        } else {
          alert(res.data?.erro || 'Falha ao ler arquivo OFX.');
        }
      } catch (err: any) {
        alert(err.message || 'Erro ao enviar arquivo OFX.');
      } finally {
        setLendoOfx(false);
        if (fileInputRef.current) fileInputRef.current.value = '';
      }
    };
    reader.readAsText(file, 'ISO-8859-1');
  };

  // Alterar Categoria de uma Transação Específica do OFX
  const handleAlterarCategoriaTransacaoOfx = (index: number, novaCat: string) => {
    const atualizadas = [...transacoesOfx];
    atualizadas[index].categoria = novaCat;
    setTransacoesOfx(atualizadas);
  };

  // Aplicar Categoria Global a Todas as Transações do OFX
  const handleAplicarCategoriaGlobalOfx = (cat: string) => {
    setCategoriaGlobalOfx(cat);
    const atualizadas = transacoesOfx.map((t) => ({ ...t, categoria: cat }));
    setTransacoesOfx(atualizadas);
  };

  // Confirmar Importação OFX
  const handleImportarOfxConfirmado = async () => {
    if (transacoesOfx.length === 0) return;
    setProcessandoOfx(true);

    try {
      const res = await safeFetchJson('/api/financeiro/importar-ofx', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ transacoes: transacoesOfx, contaId: contaOfxId }),
      });

      if (res.ok && res.data?.ok) {
        setModalOfxAberta(false);
        setTransacoesOfx([]);
        carregarDados();
      } else {
        alert(res.data?.erro || 'Erro ao importar transações.');
      }
    } catch (e) {
      alert('Erro de conexão ao conciliar OFX.');
    } finally {
      setProcessandoOfx(false);
    }
  };

  // Navegação de Mês
  const avancarMes = () => {
    if (mesSel === 11) {
      setMesSel(0);
      setAnoSel(anoSel + 1);
    } else {
      setMesSel(mesSel + 1);
    }
  };

  const voltarMes = () => {
    if (mesSel === 0) {
      setMesSel(11);
      setAnoSel(anoSel - 1);
    } else {
      setMesSel(mesSel - 1);
    }
  };

  const nomesMeses = [
    'JANEIRO', 'FEVEREIRO', 'MARÇO', 'ABRIL', 'MAIO', 'JUNHO',
    'JULHO', 'AGOSTO', 'SETEMBRO', 'OUTUBRO', 'NOVEMBRO', 'DEZEMBRO'
  ];

  // Checkbox Selecionar Todos
  const handleSelectAll = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.checked) {
      setSelecionados(lancamentosFiltrados.map((l) => l.id));
    } else {
      setSelecionados([]);
    }
  };

  const handleSelectOne = (id: string) => {
    if (selecionados.includes(id)) {
      setSelecionados(selecionados.filter((i) => i !== id));
    } else {
      setSelecionados([...selecionados, id]);
    }
  };

  // Filtragem dos Lançamentos Consolidados
  const lancamentosFiltrados = lancamentos.filter((l) => {
    const desc = (l.descricao || '').toLowerCase();
    const cli = (l.cliente_fornecedor || '').toLowerCase();
    const cat = (l.categoria || '').toLowerCase();
    const isConciliado = Number(l.conciliado) === 1 || Boolean(l.ofx_fitid) || Boolean(l.asaas_id) || Boolean(l.asaas_payment_id);

    // 1. Busca por Texto
    if (busca.trim()) {
      const termo = busca.toLowerCase();
      if (!desc.includes(termo) && !cli.includes(termo) && !cat.includes(termo)) return false;
    }

    // 2. Filtro de Tipo
    if (filtroTipo === 'RECEBER' && l.tipo !== 'receber') return false;
    if (filtroTipo === 'PAGAR' && l.tipo !== 'pagar' && l.status !== 'saida' && l.status !== 'SAIDA') return false;

    // 3. Filtro de Status
    if (filtroStatus === 'PAGO' && l.status !== 'pago' && l.status !== 'RECEIVED') return false;
    if (filtroStatus === 'PENDENTE' && l.status !== 'pendente' && l.status !== 'PENDING') return false;
    if (filtroStatus === 'ATRASADO' && l.status !== 'atrasado' && l.status !== 'OVERDUE') return false;

    // 4. Filtro por Categoria
    if (filtroCategoria !== 'TODAS' && (l.categoria || '').toLowerCase() !== filtroCategoria.toLowerCase()) return false;

    // 5. Filtro por Conciliação
    if (filtroConciliacao === 'CONCILIADO' && !isConciliado) return false;
    if (filtroConciliacao === 'NAO_CONCILIADO' && isConciliado) return false;

    // 6. Filtro por Conta Bancária
    if (filtroConta !== 'TODAS' && l.conta_id !== filtroConta) return false;

    // 7. Filtro de Data / Período
    const dtStr = l.data_pagamento || l.vencimento;
    if (!dtStr) return true;

    const dt = new Date(dtStr);

    if (filtroPeriodo === 'MES') {
      if (dt.getFullYear() !== anoSel || dt.getMonth() !== mesSel) return false;
    } else if (filtroPeriodo === 'SEMANA') {
      const agora = new Date();
      const seteDiasAtras = new Date(agora.getTime() - 7 * 24 * 60 * 60 * 1000);
      const seteDiasFrente = new Date(agora.getTime() + 7 * 24 * 60 * 60 * 1000);
      if (dt < seteDiasAtras || dt > seteDiasFrente) return false;
    } else if (filtroPeriodo === 'ANO') {
      if (dt.getFullYear() !== anoSel) return false;
    } else if (filtroPeriodo === 'CUSTOM') {
      if (dataInicioCustom && dt < new Date(dataInicioCustom)) return false;
      if (dataFimCustom && dt > new Date(`${dataFimCustom}T23:59:59`)) return false;
    }

    return true;
  });

  const totalAReceber = lancamentosFiltrados
    .filter((l) => l.tipo === 'receber')
    .reduce((acc, l) => acc + parseFloat(l.valor || 0), 0);

  const totalAPagar = lancamentosFiltrados
    .filter((l) => l.tipo === 'pagar' || l.status === 'saida' || l.status === 'SAIDA')
    .reduce((acc, l) => acc + parseFloat(l.valor || 0), 0);

  return (
    <div className="space-y-6 font-sans text-white bg-[#050505] min-h-screen">
      {/* Input de arquivo OFX escondido */}
      <input
        type="file"
        ref={fileInputRef}
        onChange={handleSelecionarArquivoOfx}
        accept=".ofx,.OFX"
        className="hidden"
      />

      {/* Header com os Botões Superiores no Tema Escuro */}
      <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-white tracking-tight">Fluxo de Caixa</h1>
          <p className="text-xs text-zinc-400 mt-0.5">Gestão completa de lançamentos, transferências e conciliação bancária</p>
        </div>

        {/* Botões de Ação Principais no Topo */}
        <div className="flex items-center space-x-2.5 overflow-x-auto pb-1 lg:pb-0">
          <button
            onClick={() => setModalCategoriasAberta(true)}
            className="px-3.5 py-2 bg-purple-950/60 hover:bg-purple-900/60 text-purple-400 border border-purple-500/30 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs"
          >
            <span className="material-symbols-outlined text-sm leading-none">category</span>
            <span>Gerenciar Categorias</span>
          </button>

          <button
            onClick={() => alert('IA Scanner ativada para leitura inteligente de faturas e comprovantes PDF/Imagem.')}
            className="px-3.5 py-2 bg-emerald-950/60 hover:bg-emerald-900/60 text-emerald-400 border border-emerald-500/30 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs"
          >
            <span className="material-symbols-outlined text-sm leading-none">auto_awesome</span>
            <span>Scanner IA</span>
          </button>

          <button
            onClick={() => fileInputRef.current?.click()}
            disabled={lendoOfx}
            className="px-3.5 py-2 bg-blue-950/60 hover:bg-blue-900/60 text-blue-400 border border-blue-500/30 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs"
          >
            <span className="material-symbols-outlined text-sm leading-none">upload_file</span>
            <span>{lendoOfx ? 'Lendo OFX...' : 'Importar OFX'}</span>
          </button>

          <button
            onClick={() => setModalAberta(true)}
            className="px-4 py-2 bg-white hover:bg-zinc-200 text-black font-bold rounded-xl text-xs transition flex items-center space-x-1.5 shadow-sm"
          >
            <span className="material-symbols-outlined text-sm leading-none">add</span>
            <span>Novo Lançamento</span>
          </button>
        </div>
      </div>

      {/* Cards de Métricas Superiores em Tema Escuro */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {/* Card Total a Receber */}
        <div className="bg-[#0c0c0c] border border-white/10 p-5 rounded-2xl shadow-2xs flex items-center justify-between relative overflow-hidden group">
          <div className="space-y-1">
            <span className="text-[10px] font-bold tracking-wider text-zinc-400 uppercase block">TOTAL A RECEBER</span>
            <div className="text-2xl font-black font-mono text-emerald-400 tracking-tight">
              R$ {totalAReceber.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
            </div>
            <span className="text-[10px] font-bold text-zinc-500 block uppercase">SALDO PREVISTO EM CAIXA</span>
          </div>
          <div className="w-10 h-10 rounded-xl bg-emerald-950/60 text-emerald-400 border border-emerald-500/30 flex items-center justify-center">
            <span className="material-symbols-outlined text-lg leading-none">south_west</span>
          </div>
        </div>

        {/* Card Total a Pagar */}
        <div className="bg-[#0c0c0c] border border-white/10 p-5 rounded-2xl shadow-2xs flex items-center justify-between relative overflow-hidden group">
          <div className="space-y-1">
            <span className="text-[10px] font-bold tracking-wider text-zinc-400 uppercase block">TOTAL A PAGAR</span>
            <div className="text-2xl font-black font-mono text-rose-400 tracking-tight">
              R$ {totalAPagar.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
            </div>
            <span className="text-[10px] font-bold text-rose-400 block uppercase">COMPROMISSOS PENDENTES</span>
          </div>
          <div className="w-10 h-10 rounded-xl bg-rose-950/60 text-rose-400 border border-rose-500/30 flex items-center justify-center">
            <span className="material-symbols-outlined text-lg leading-none">north_east</span>
          </div>
        </div>

        {/* Card Ações Rápidas */}
        <div className="bg-[#0c0c0c] border border-white/10 p-5 rounded-2xl shadow-2xs flex items-center justify-between">
          <div className="space-y-1">
            <span className="text-[10px] font-bold tracking-wider text-zinc-400 uppercase block">AÇÕES RÁPIDAS</span>
            <div className="text-base font-bold text-white tracking-tight">Novo Lançamento</div>
            <span className="text-[10px] font-bold text-zinc-500 block uppercase">REGISTRAR ENTRADA OU SAÍDA</span>
          </div>
          <button
            onClick={() => setModalAberta(true)}
            className="w-10 h-10 rounded-xl bg-white hover:bg-zinc-200 text-black flex items-center justify-center transition shadow-sm"
          >
            <span className="material-symbols-outlined text-xl leading-none">add</span>
          </button>
        </div>
      </div>

      {/* Barra de Filtros Completa em Tema Escuro */}
      <div className="bg-[#0c0c0c] border border-white/10 p-4 rounded-2xl shadow-2xs space-y-3">
        {/* Linha 1: Abas de Tipo + Campo de Busca */}
        <div className="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
          <div className="flex items-center space-x-1 bg-zinc-900 p-1 rounded-xl border border-white/5">
            {[
              { id: 'TODOS', label: 'TODOS' },
              { id: 'RECEBER', label: 'A RECEBER' },
              { id: 'PAGAR', label: 'A PAGAR' },
            ].map((t) => (
              <button
                key={t.id}
                onClick={() => setFiltroTipo(t.id)}
                className={`px-4 py-1.5 rounded-lg text-[11px] font-bold transition ${
                  filtroTipo === t.id
                    ? 'bg-white text-black shadow-xs'
                    : 'text-zinc-400 hover:text-white'
                }`}
              >
                {t.label}
              </button>
            ))}
          </div>

          <div className="relative flex-1">
            <span className="material-symbols-outlined absolute left-3.5 top-2.5 text-zinc-400 text-sm leading-none">
              search
            </span>
            <input
              type="text"
              value={busca}
              onChange={(e) => setBusca(e.target.value)}
              placeholder="Buscar por descrição, cliente ou categoria..."
              className="w-full pl-9 pr-4 py-2 bg-zinc-900 border border-zinc-700 rounded-xl text-xs text-white placeholder-zinc-500 focus:border-white outline-none transition font-sans"
            />
          </div>
        </div>

        {/* Linha 2: Dropdowns de Categoria, Conta, Status e Conciliação */}
        <div className="flex flex-wrap items-center gap-2">
          {/* Seletor Categoria */}
          <select
            value={filtroCategoria}
            onChange={(e) => setFiltroCategoria(e.target.value)}
            className="px-3 py-1.5 bg-zinc-900 border border-zinc-700 rounded-xl text-[11px] font-bold text-white outline-none uppercase"
          >
            <option value="TODAS">TODAS AS CATEGORIAS</option>
            {categorias.map((cat) => (
              <option key={cat} value={cat}>
                {cat.toUpperCase()}
              </option>
            ))}
          </select>

          {/* Seletor Conta */}
          <select
            value={filtroConta}
            onChange={(e) => setFiltroConta(e.target.value)}
            className="px-3 py-1.5 bg-zinc-900 border border-zinc-700 rounded-xl text-[11px] font-bold text-white outline-none uppercase"
          >
            <option value="TODAS">TODAS AS CONTAS</option>
            {contas.map((c) => (
              <option key={c.id} value={c.id}>
                {c.nome.toUpperCase()}
              </option>
            ))}
          </select>

          {/* Seletor Status */}
          <select
            value={filtroStatus}
            onChange={(e) => setFiltroStatus(e.target.value)}
            className="px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-xl text-[11px] font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-black uppercase"
          >
            <option value="TODOS">TODOS OS STATUS</option>
            <option value="PAGO">PAGO / RECEBIDO</option>
            <option value="PENDENTE">PENDENTE</option>
            <option value="ATRASADO">ATRASADO</option>
          </select>

          {/* Seletor Conciliação */}
          <select
            value={filtroConciliacao}
            onChange={(e) => setFiltroConciliacao(e.target.value)}
            className="px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-xl text-[11px] font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-black uppercase"
          >
            <option value="TODOS">CONCILIAÇÃO: TODOS</option>
            <option value="CONCILIADO">SOMENTE CONCILIADOS (OFX / ASAAS)</option>
            <option value="NAO_CONCILIADO">MANUAIS</option>
          </select>
        </div>

        {/* Linha 3: Filtro de Período Temporal com Botões Visíveis (Mês, Semana, Ano, Personalizado, Todos) */}
        <div className="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-gray-100">
          <div className="flex items-center space-x-1 bg-gray-100 p-1 rounded-xl border border-gray-200">
            {[
              { id: 'MES', label: 'ESTE MÊS' },
              { id: 'SEMANA', label: 'ESTA SEMANA' },
              { id: 'ANO', label: 'ESTE ANO' },
              { id: 'CUSTOM', label: 'PERSONALIZADO' },
              { id: 'TODOS', label: 'VER TODOS' },
            ].map((p) => (
              <button
                key={p.id}
                type="button"
                onClick={() => setFiltroPeriodo(p.id as any)}
                className={`px-3 py-1.5 rounded-lg text-[10px] font-bold transition ${
                  filtroPeriodo === p.id
                    ? 'bg-black text-white shadow-xs'
                    : 'text-gray-600 hover:text-gray-900'
                }`}
              >
                {p.label}
              </button>
            ))}
          </div>

          {/* Navegador Mensal quando ESTE MÊS está selecionado */}
          {filtroPeriodo === 'MES' && (
            <div className="flex items-center space-x-2 bg-gray-50 border border-gray-200 px-3 py-1.5 rounded-xl">
              <button onClick={voltarMes} className="p-0.5 hover:bg-gray-200 rounded text-gray-600 transition">
                <span className="material-symbols-outlined text-sm leading-none">chevron_left</span>
              </button>
              <span className="text-[11px] font-bold font-mono text-gray-900 px-1 uppercase">
                {nomesMeses[mesSel]} DE {anoSel}
              </span>
              <button onClick={avancarMes} className="p-0.5 hover:bg-gray-200 rounded text-gray-600 transition">
                <span className="material-symbols-outlined text-sm leading-none">chevron_right</span>
              </button>
            </div>
          )}

          {/* Seletores de Data Personalizados quando PERSONALIZADO está selecionado */}
          {filtroPeriodo === 'CUSTOM' && (
            <div className="flex items-center space-x-2 bg-gray-50 border border-gray-200 p-1.5 rounded-xl">
              <span className="text-[10px] font-bold text-gray-500 pl-1 uppercase">De:</span>
              <input
                type="date"
                value={dataInicioCustom}
                onChange={(e) => setDataInicioCustom(e.target.value)}
                className="px-2 py-1 bg-white border border-gray-200 rounded-lg text-xs font-mono font-bold text-gray-900 focus:outline-none"
              />
              <span className="text-[10px] font-bold text-gray-500 uppercase">Até:</span>
              <input
                type="date"
                value={dataFimCustom}
                onChange={(e) => setDataFimCustom(e.target.value)}
                className="px-2 py-1 bg-white border border-gray-200 rounded-lg text-xs font-mono font-bold text-gray-900 focus:outline-none"
              />
            </div>
          )}
        </div>
      </div>

      {/* Tabela de Lançamentos em Tema Escuro */}
      <div className="bg-[#0c0c0c] border border-white/10 rounded-2xl overflow-hidden shadow-2xs">
        {loading ? (
          <div className="p-12 text-center text-xs text-zinc-500">Carregando fluxo de caixa...</div>
        ) : lancamentosFiltrados.length === 0 ? (
          <div className="p-16 text-center">
            <span className="material-symbols-outlined text-5xl text-zinc-600 mb-2 leading-none">receipt_long</span>
            <p className="text-sm font-bold text-white">Nenhum lançamento encontrado para os filtros selecionados.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs font-sans">
              <thead className="bg-[#121212] text-zinc-400 uppercase tracking-wider font-bold text-[10px] border-b border-white/10">
                <tr>
                  <th className="py-3 px-3 w-10 text-center">
                    <input
                      type="checkbox"
                      onChange={handleSelectAll}
                      checked={selecionados.length === lancamentosFiltrados.length && lancamentosFiltrados.length > 0}
                      className="rounded border-zinc-700 bg-zinc-800 text-white focus:ring-0"
                    />
                  </th>
                  <th className="py-3 px-2 w-8"></th>
                  <th className="py-3 px-4">DESCRIÇÃO / CLIENTE</th>
                  <th className="py-3 px-4 text-center">VENCIMENTO</th>
                  <th className="py-3 px-4 text-center">PAGAMENTO</th>
                  <th className="py-3 px-4 text-right">VALOR</th>
                  <th className="py-3 px-4 text-right">VALOR PAGO</th>
                  <th className="py-3 px-4 text-center">STATUS</th>
                  <th className="py-3 px-4 text-right">AÇÕES</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-white/5 font-sans">
                {lancamentosFiltrados.map((item) => {
                  const isReceber = item.tipo === 'receber' && item.status !== 'saida' && item.status !== 'SAIDA';
                  const isConciliado = Number(item.conciliado) === 1 || Boolean(item.ofx_fitid) || Boolean(item.asaas_id) || Boolean(item.asaas_payment_id);
                  const valorNum = parseFloat(item.valor || 0);
                  const valorPagoNum = parseFloat(item.valor_pago || 0);
                  const isSelected = selecionados.includes(item.id);

                  // Definir cor do badge de status
                  const statusStr = (item.status || '').toLowerCase();
                  let badgeBg = 'bg-amber-950/60 text-amber-400 border border-amber-500/30';
                  let statusTexto = 'Pendente';

                  if (statusStr === 'pago' || statusStr === 'received') {
                    badgeBg = 'bg-emerald-950/60 text-emerald-400 border border-emerald-500/30';
                    statusTexto = 'Pago';
                  } else if (statusStr === 'atrasado' || statusStr === 'overdue') {
                    badgeBg = 'bg-rose-950/60 text-rose-400 border border-rose-500/30';
                    statusTexto = 'Atrasado';
                  } else if (statusStr === 'saida' || statusStr === 'saída') {
                    badgeBg = 'bg-rose-950/60 text-rose-400 border border-rose-500/30';
                    statusTexto = 'Saída';
                  }

                  return (
                    <tr key={item.id} className={`hover:bg-zinc-900/60 transition ${isSelected ? 'bg-purple-950/30' : ''}`}>
                      {/* Checkbox */}
                      <td className="py-3.5 px-3 text-center">
                        <input
                          type="checkbox"
                          checked={isSelected}
                          onChange={() => handleSelectOne(item.id)}
                          className="rounded border-zinc-700 bg-zinc-800 text-white focus:ring-0"
                        />
                      </td>

                      {/* Ícone de Direção */}
                      <td className="py-3.5 px-2 text-center">
                        <div className={`w-6 h-6 rounded-full flex items-center justify-center text-xs ${
                          isReceber ? 'bg-emerald-950/80 text-emerald-400 border border-emerald-500/30' : 'bg-rose-950/80 text-rose-400 border border-rose-500/30'
                        }`}>
                          <span className="material-symbols-outlined text-sm leading-none">
                            {isReceber ? 'south_west' : 'north_east'}
                          </span>
                        </div>
                      </td>

                      {/* Descrição / Tags / Cliente */}
                      <td className="py-3.5 px-4">
                        <div className="font-bold text-white text-xs">{item.descricao}</div>
                        <div className="flex items-center space-x-1.5 mt-1">
                          {item.asaas_id || item.asaas_payment_id ? (
                            <span className="px-1.5 py-0.5 bg-emerald-950/80 text-emerald-400 rounded text-[9px] font-mono font-bold border border-emerald-500/30">
                              ASAAS
                            </span>
                          ) : item.ofx_fitid ? (
                            <span className="px-1.5 py-0.5 bg-blue-950/80 text-blue-400 rounded text-[9px] font-mono font-bold border border-blue-500/30">
                              OFX
                            </span>
                          ) : (
                            <span className="px-1.5 py-0.5 bg-zinc-800 text-zinc-300 rounded text-[9px] font-mono font-bold border border-white/5">
                              {item.categoria || 'Outros'}
                            </span>
                          )}

                          {isConciliado && (
                            <span className="px-1.5 py-0.5 bg-emerald-950/60 text-emerald-400 text-[9px] font-bold rounded flex items-center space-x-0.5 border border-emerald-500/30">
                              <span>🔒 CONCILIADO</span>
                            </span>
                          )}

                          <span className="text-[11px] text-zinc-400">
                            {item.cliente_fornecedor || 'Cliente Asaas'}
                          </span>
                        </div>
                      </td>

                      {/* Vencimento */}
                      <td className="py-3.5 px-4 text-center font-mono text-zinc-400 text-xs">
                        {item.vencimento ? new Date(item.vencimento).toLocaleDateString('pt-BR') : '—'}
                      </td>

                      {/* Data de Pagamento */}
                      <td className="py-3.5 px-4 text-center font-mono text-xs">
                        {item.data_pagamento ? (
                          <span className="text-emerald-400 font-bold">
                            {new Date(item.data_pagamento).toLocaleDateString('pt-BR')}
                          </span>
                        ) : (
                          <span className="text-zinc-600">—</span>
                        )}
                      </td>

                      {/* Valor */}
                      <td className="py-3.5 px-4 text-right font-mono font-bold text-xs text-white">
                        R$ {valorNum.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                      </td>

                      {/* Valor Pago */}
                      <td className="py-3.5 px-4 text-right font-mono font-bold text-xs">
                        <span className={valorPagoNum > 0 ? 'text-emerald-400' : 'text-zinc-600'}>
                          R$ {valorPagoNum.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                        </span>
                      </td>

                      {/* Status */}
                      <td className="py-3.5 px-4 text-center">
                        <span className={`inline-block px-3 py-1 rounded-full text-[10px] font-bold ${badgeBg}`}>
                          {statusTexto}
                        </span>
                      </td>

                      {/* AÇÕES (Botões de Ação Visíveis e Funcionais) */}
                      <td className="py-3.5 px-4 text-right">
                        <div className="flex items-center justify-end space-x-1.5">
                          <button
                            onClick={() => abrirEdicao(item)}
                            className="p-1.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 rounded-lg text-xs font-bold transition flex items-center border border-white/5"
                            title="Editar Lançamento"
                          >
                            <span className="material-symbols-outlined text-sm leading-none">edit</span>
                          </button>

                          <button
                            onClick={() => handleExcluirLancamento(item)}
                            disabled={isConciliado}
                            className={`p-1.5 rounded-lg text-xs font-bold transition flex items-center border ${
                              isConciliado
                                ? 'bg-gray-100 text-gray-300 border-gray-200 cursor-not-allowed opacity-40'
                                : 'bg-red-50 hover:bg-red-100 text-red-600 border-red-200'
                            }`}
                            title={isConciliado ? 'Lançamentos conciliados (OFX/Asaas) não podem ser excluídos' : 'Excluir Lançamento'}
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

      {/* Modal Gerenciar Categorias da Empresa */}
      {modalCategoriasAberta && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
          <div className="bg-white border border-gray-200 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 text-gray-900">
            <div className="flex items-center justify-between border-b border-gray-100 pb-3">
              <div className="flex items-center space-x-2">
                <span className="material-symbols-outlined text-purple-600 text-xl leading-none">category</span>
                <h3 className="font-bold text-gray-900 text-base">Gerenciar Categorias da Empresa</h3>
              </div>
              <button onClick={() => setModalCategoriasAberta(false)} className="text-gray-400 hover:text-gray-600">
                <span className="material-symbols-outlined leading-none">close</span>
              </button>
            </div>

            {/* Criar Nova Categoria */}
            <form onSubmit={handleCriarCategoria} className="flex gap-2">
              <input
                type="text"
                value={novaCategoriaNome}
                onChange={(e) => setNovaCategoriaNome(e.target.value)}
                placeholder="Nome da nova categoria (ex: Manutenção, Consultoria)..."
                className="flex-1 px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-bold"
              />
              <button
                type="submit"
                disabled={criandoCategoria}
                className="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl text-xs transition"
              >
                {criandoCategoria ? 'Criando...' : '+ Adicionar'}
              </button>
            </form>

            {/* Lista de Categorias Atuais */}
            <div className="max-h-60 overflow-y-auto space-y-1.5 pt-2">
              <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Categorias Ativas no Banco:</span>
              <div className="grid grid-cols-2 gap-2">
                {categorias.map((cat) => (
                  <div
                    key={cat}
                    className="flex items-center justify-between p-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-800"
                  >
                    <span>{cat}</span>
                    <button
                      type="button"
                      onClick={() => handleExcluirCategoria(cat)}
                      className="text-gray-400 hover:text-red-600 transition"
                      title="Excluir Categoria"
                    >
                      <span className="material-symbols-outlined text-sm leading-none">close</span>
                    </button>
                  </div>
                ))}
              </div>
            </div>

            <div className="pt-2 flex justify-end">
              <button
                onClick={() => setModalCategoriasAberta(false)}
                className="px-4 py-2 bg-black text-white text-xs font-bold rounded-xl"
              >
                Concluir
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal Edição de Lançamento em Tema Claro */}
      {modalEdicaoAberta && itemEdicao && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
          <div className="bg-white border border-gray-200 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 text-gray-900">
            <div className="flex items-center justify-between border-b border-gray-100 pb-3">
              <div className="flex items-center space-x-2">
                <span className="material-symbols-outlined text-black text-xl leading-none">edit_note</span>
                <h3 className="font-bold text-gray-900 text-base">Editar Lançamento</h3>
              </div>
              <button onClick={() => setModalEdicaoAberta(false)} className="text-gray-400 hover:text-gray-600">
                <span className="material-symbols-outlined leading-none">close</span>
              </button>
            </div>

            {(Number(itemEdicao.conciliado) === 1 || Boolean(itemEdicao.ofx_fitid) || Boolean(itemEdicao.asaas_id) || Boolean(itemEdicao.asaas_payment_id)) ? (
              <div className="p-3 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl text-xs flex items-start space-x-2">
                <span className="material-symbols-outlined text-amber-700 text-base leading-none mt-0.5">lock</span>
                <div>
                  <span className="font-bold block">Lançamento Conciliado (OFX / Asaas)</span>
                  <span className="text-[11px] leading-relaxed text-amber-800">
                    O valor, tipo e a data de pagamento estão protegidos pela conciliação bancária. É permitido alterar apenas a <strong>Categoria</strong> e a <strong>Descrição</strong>.
                  </span>
                </div>
              </div>
            ) : null}

            <form onSubmit={handleSalvarEdicao} className="space-y-3">
              {/* Descrição */}
              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Descrição</label>
                <input
                  type="text"
                  required
                  value={editDescricao}
                  onChange={(e) => setEditDescricao(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-bold"
                />
              </div>

              {/* Categoria */}
              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Categoria do Lançamento</label>
                <select
                  value={editCategoria}
                  onChange={(e) => setEditCategoria(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-bold bg-white"
                >
                  {categorias.map((cat) => (
                    <option key={cat} value={cat}>
                      {cat}
                    </option>
                  ))}
                </select>
              </div>

              {/* Valor & Vencimento (Desativados se Conciliado) */}
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">
                    Valor (R$) {(Number(itemEdicao.conciliado) === 1 || itemEdicao.ofx_fitid || itemEdicao.asaas_id || itemEdicao.asaas_payment_id) ? '🔒' : ''}
                  </label>
                  <input
                    type="number"
                    step="0.01"
                    disabled={Number(itemEdicao.conciliado) === 1 || Boolean(itemEdicao.ofx_fitid) || Boolean(itemEdicao.asaas_id) || Boolean(itemEdicao.asaas_payment_id)}
                    value={editValor}
                    onChange={(e) => setEditValor(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs font-mono font-bold text-gray-900 disabled:bg-gray-100 disabled:text-gray-500"
                  />
                </div>

                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">
                    Vencimento {(Number(itemEdicao.conciliado) === 1 || itemEdicao.ofx_fitid || itemEdicao.asaas_id || itemEdicao.asaas_payment_id) ? '🔒' : ''}
                  </label>
                  <input
                    type="date"
                    disabled={Number(itemEdicao.conciliado) === 1 || Boolean(itemEdicao.ofx_fitid) || Boolean(itemEdicao.asaas_id) || Boolean(itemEdicao.asaas_payment_id)}
                    value={editVencimento}
                    onChange={(e) => setEditVencimento(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs font-mono text-gray-900 disabled:bg-gray-100 disabled:text-gray-500"
                  />
                </div>
              </div>

              {/* Cliente / Fornecedor */}
              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Cliente / Fornecedor</label>
                <input
                  type="text"
                  disabled={Number(itemEdicao.conciliado) === 1 || Boolean(itemEdicao.ofx_fitid) || Boolean(itemEdicao.asaas_id) || Boolean(itemEdicao.asaas_payment_id)}
                  value={editClienteFornecedor}
                  onChange={(e) => setEditClienteFornecedor(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 disabled:bg-gray-100 disabled:text-gray-500"
                />
              </div>

              <div className="pt-3 flex justify-between items-center border-t border-gray-100">
                {!(Number(itemEdicao.conciliado) === 1 || itemEdicao.ofx_fitid || itemEdicao.asaas_id || itemEdicao.asaas_payment_id) ? (
                  <button
                    type="button"
                    onClick={() => handleExcluirLancamento(itemEdicao)}
                    className="px-3.5 py-2 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold rounded-xl transition"
                  >
                    Excluir Lançamento
                  </button>
                ) : (
                  <span className="text-[10px] font-bold text-gray-400">🔒 Exclusão Desativada (Conciliado)</span>
                )}

                <div className="flex space-x-2">
                  <button
                    type="button"
                    onClick={() => setModalEdicaoAberta(false)}
                    className="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl"
                  >
                    Cancelar
                  </button>
                  <button
                    type="submit"
                    disabled={salvandoEdicao}
                    className="px-4 py-2 bg-black hover:bg-gray-800 text-white text-xs font-bold rounded-xl transition font-mono"
                  >
                    {salvandoEdicao ? 'Salvando...' : 'Salvar Alterações'}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal Conciliação OFX com Seleção de Categoria Geral e por Item */}
      {modalOfxAberta && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
          <div className="bg-white border border-gray-200 rounded-2xl max-w-4xl w-full p-6 shadow-2xl space-y-4 text-gray-900">
            <div className="flex items-center justify-between border-b border-gray-100 pb-3">
              <div className="flex items-center space-x-2">
                <span className="material-symbols-outlined text-emerald-600 text-xl leading-none">upload_file</span>
                <h3 className="font-bold text-gray-900 text-base">Conciliação Bancária (OFX)</h3>
              </div>
              <button onClick={() => setModalOfxAberta(false)} className="text-gray-400 hover:text-gray-600">
                <span className="material-symbols-outlined leading-none">close</span>
              </button>
            </div>

            {/* Seletor Global de Categoria e Conta */}
            <div className="space-y-2.5">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3 bg-emerald-50 border border-emerald-200 p-3 rounded-xl">
                {/* Seleção de Conta */}
                <div className="space-y-1">
                  <label className="text-xs font-bold text-emerald-900 block">Conta Bancária Vinculada:</label>
                  <select
                    value={contaOfxId}
                    onChange={(e) => setContaOfxId(e.target.value)}
                    className="w-full px-2.5 py-1.5 bg-white border border-emerald-300 rounded-xl text-xs font-bold text-gray-900 shadow-2xs"
                  >
                    <option value="">Selecione a Conta...</option>
                    {contas.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.nome}
                      </option>
                    ))}
                  </select>
                  {bancoAutoDetectado && (
                    <span className="text-[10px] font-bold text-emerald-700 block">
                      ⚡ Detectado: <u>{bancoAutoDetectado}</u>
                    </span>
                  )}
                </div>

                {/* Seleção Global de Categoria */}
                <div className="space-y-1">
                  <label className="text-xs font-bold text-emerald-900 block">Aplicar Categoria a Todas:</label>
                  <div className="flex space-x-1.5">
                    <select
                      value={categoriaGlobalOfx}
                      onChange={(e) => setCategoriaGlobalOfx(e.target.value)}
                      className="flex-1 px-2.5 py-1.5 bg-white border border-emerald-300 rounded-xl text-xs font-bold text-gray-900 shadow-2xs"
                    >
                      {categorias.map((cat) => (
                        <option key={cat} value={cat}>
                          {cat}
                        </option>
                      ))}
                    </select>
                    <button
                      type="button"
                      onClick={() => handleAplicarCategoriaGlobalOfx(categoriaGlobalOfx)}
                      className="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-2xs"
                    >
                      Aplicar
                    </button>
                  </div>
                </div>
              </div>

              {totalIgnoradasOfx > 0 && (
                <div className="p-2.5 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-xs font-bold flex items-center space-x-1.5">
                  <span className="material-symbols-outlined text-amber-600 text-base leading-none">info</span>
                  <span>
                    <strong>{totalIgnoradasOfx} transações duplicadas</strong> já existentes no banco foram ignoradas automaticamente.
                  </span>
                </div>
              )}
            </div>

            {/* Tabela de Transações OFX com Dropdown Individual de Categoria */}
            <div className="max-h-64 overflow-y-auto border border-gray-200 rounded-xl">
              <table className="w-full text-left text-xs font-sans">
                <thead className="bg-gray-50 text-gray-500 uppercase font-bold text-[10px] border-b border-gray-200">
                  <tr>
                    <th className="p-3">Data</th>
                    <th className="p-3">Tipo</th>
                    <th className="p-3">Descrição (OFX)</th>
                    <th className="p-3">Categoria</th>
                    <th className="p-3 text-right">Valor</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {transacoesOfx.map((t, idx) => (
                    <tr key={idx} className="hover:bg-gray-50">
                      <td className="p-3 font-mono text-gray-700">{new Date(t.data).toLocaleDateString('pt-BR')}</td>
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

                      {/* Dropdown Individual de Categoria por Transação */}
                      <td className="p-2">
                        <select
                          value={t.categoria || 'Outros'}
                          onChange={(e) => handleAlterarCategoriaTransacaoOfx(idx, e.target.value)}
                          className="px-2 py-1 bg-gray-50 border border-gray-300 rounded-lg text-xs font-bold text-gray-900 focus:bg-white"
                        >
                          {categorias.map((cat) => (
                            <option key={cat} value={cat}>
                              {cat}
                            </option>
                          ))}
                        </select>
                      </td>

                      <td className="p-3 text-right font-mono font-bold text-gray-900">
                        R$ {parseFloat(t.valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div className="pt-2 flex justify-between items-center">
              <button
                type="button"
                onClick={() => setModalCategoriasAberta(true)}
                className="text-xs font-bold text-purple-700 hover:underline flex items-center space-x-1"
              >
                <span>+ Adicionar Nova Categoria</span>
              </button>

              <div className="flex space-x-2">
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
                  className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition font-mono"
                >
                  {processandoOfx ? 'Conciliando...' : `Confirmar e Importar ${transacoesOfx.length} Transações`}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Modal Novo Lançamento Manual em Tema Claro */}
      {modalAberta && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
          <div className="bg-white border border-gray-200 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 text-gray-900">
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
                <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Descrição</label>
                <input
                  type="text"
                  required
                  value={descricao}
                  onChange={(e) => setDescricao(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-bold"
                  placeholder="Ex: Contrato Fotografia Casamento"
                />
              </div>

              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Categoria</label>
                <select
                  value={categoria}
                  onChange={(e) => setCategoria(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-bold bg-white"
                >
                  {categorias.map((cat) => (
                    <option key={cat} value={cat}>
                      {cat}
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Valor (R$)</label>
                  <input
                    type="number"
                    step="0.01"
                    required
                    value={valor}
                    onChange={(e) => setValor(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-mono font-bold"
                    placeholder="0.00"
                  />
                </div>
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Vencimento</label>
                  <input
                    type="date"
                    required
                    value={vencimento}
                    onChange={(e) => setVencimento(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-mono"
                  />
                </div>
              </div>

              <div>
                <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Cliente / Fornecedor</label>
                <input
                  type="text"
                  value={clienteFornecedor}
                  onChange={(e) => setClienteFornecedor(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none"
                  placeholder="Nome do cliente ou fornecedor"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Conta Bancária</label>
                  <select
                    value={contaId}
                    onChange={(e) => setContaId(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none"
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
                  <label className="block text-[10px] font-bold uppercase text-gray-600 mb-1">Status</label>
                  <select
                    value={status}
                    onChange={(e) => setStatus(e.target.value as any)}
                    className="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-black outline-none font-bold"
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
                  className="px-4 py-2 bg-black text-white text-xs font-bold rounded-xl hover:bg-gray-800 transition font-mono shadow-sm"
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
