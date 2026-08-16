import React, { useEffect, useRef, useState, useCallback } from 'react';
import {
  Search, Plus, Folder, FolderOpen, Folders, FolderPlus, FileText, Megaphone, Video,
  MoreHorizontal, X, User, Phone, Trash2, MessageCircle, Edit3, Scroll, ExternalLink,
  Copy, Archive, FileX, FolderInput, ChevronRight, FilePlus, Edit2, Zap, AlertTriangle,
  CheckCircle2, FileDown,
} from 'lucide-react';

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

const APP_URL = process.env.NEXT_PUBLIC_APP_URL || '';

interface ContextMenuState {
  show: boolean;
  x: number;
  y: number;
  type: 'root' | 'folder' | 'proposal';
  item: any;
}

interface FolderModal {
  show: boolean;
  id: string | null;
  nome: string;
  mode: 'create' | 'rename';
}

interface DeleteModal {
  show: boolean;
  id: string | null;
  type: 'proposta' | 'pasta';
  message: string;
}

function parseDados(p: any): any {
  if (!p) return {};
  const raw = p.dados ?? p.dados_json;
  if (!raw) return {};
  try {
    return typeof raw === 'string' ? JSON.parse(raw) : raw || {};
  } catch (e) {
    return {};
  }
}

function getResponsavel(dados: any, proposta: any): string {
  if (!dados) return proposta?.cliente_nome || '';
  if (dados.contato_tipo === 'noivo' && dados.nome_noivo) return dados.nome_noivo;
  if (dados.contato_tipo === 'noiva' && dados.nome_noiva) return dados.nome_noiva;
  return dados.responsavel || proposta?.cliente_nome || '';
}

function numeroFechamento(valor: any): number {
  return (
    parseFloat(
      String(valor || '0')
        .replace(/[R$\s]/g, '')
        .replace(/\./g, '')
        .replace(',', '.')
    ) || 0
  );
}

function formatarValorFechamento(valor: any): string {
  const numero = numeroFechamento(valor);
  return numero > 0 ? numero.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
}

function addMesesIso(dataIso: string, meses: number): string {
  if (!dataIso) return '';
  const d = new Date(dataIso + 'T00:00:00');
  if (Number.isNaN(d.getTime())) return '';
  d.setMonth(d.getMonth() + meses);
  return d.toISOString().slice(0, 10);
}

export const PropostasView: React.FC = () => {
  const [pastas, setPastas] = useState<any[]>([]);
  const [propostas, setPropostas] = useState<any[]>([]);
  const [currentFolder, setCurrentFolder] = useState<string | null>(null);
  const [showSearch, setShowSearch] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const searchRef = useRef<HTMLInputElement>(null);

  const [contextMenu, setContextMenu] = useState<ContextMenuState>({ show: false, x: 0, y: 0, type: 'root', item: null });
  const [deleteModal, setDeleteModal] = useState<DeleteModal>({ show: false, id: null, type: 'proposta', message: '' });
  const [folderModal, setFolderModal] = useState<FolderModal>({ show: false, id: null, nome: '', mode: 'create' });
  const [draggedItem, setDraggedItem] = useState<any>(null);

  const [modalResumoAberto, setModalResumoAberto] = useState(false);
  const [selectedProposta, setSelectedProposta] = useState<any>(null);
  const [recomendacao, setRecomendacao] = useState('');
  const [historico, setHistorico] = useState<any[]>([]);
  const [novaNota, setNovaNota] = useState('');
  const [carregandoHistorico, setCarregandoHistorico] = useState(false);

  const [fechamentoForm, setFechamentoForm] = useState({
    pacote_dado_andamento: '',
    valor_total: '',
    escolha_condicoes: '',
    asaas_billing_type: 'UNDEFINED',
    asaas_total_parcelas: 1,
    asaas_first_due_date: '',
    asaas_valor_sinal: '',
    asaas_sinal_vencimento: '',
    prazo_contrato: '',
    pagamento_modo: 'parcelado' as 'avista' | 'parcelado',
    permitir_parcela_pos_evento: false,
  });
  const [alertaParcelas, setAlertaParcelas] = useState('');
  const [fechamentoSalvando, setFechamentoSalvando] = useState(false);
  const [whatsappLoading, setWhatsappLoading] = useState(false);

  const [showModalNova, setShowModalNova] = useState(false);
  const [novaUrl, setNovaUrl] = useState('');
  const [showModalEditar, setShowModalEditar] = useState(false);
  const [editUrl, setEditUrl] = useState('');

  const carregarDados = useCallback(async () => {
    const [resProp, resPastas] = await Promise.all([
      safeFetchJson('/api/comercial/propostas'),
      safeFetchJson('/api/comercial/pastas-propostas'),
    ]);
    if (resProp.ok && Array.isArray(resProp.data)) setPropostas(resProp.data);
    if (resPastas.ok && Array.isArray(resPastas.data)) setPastas(resPastas.data);
  }, []);

  useEffect(() => {
    carregarDados();
  }, [carregarDados]);

  useEffect(() => {
    if (showSearch && searchRef.current) searchRef.current.focus();
  }, [showSearch]);

  useEffect(() => {
    if (!currentFolder) {
      window.history.pushState({}, '', window.location.pathname);
    } else {
      const url = new URL(window.location.href);
      url.searchParams.set('folder', currentFolder);
      window.history.pushState({}, '', url);
    }
  }, [currentFolder]);

  useEffect(() => {
    if (!contextMenu.show) return;
    const close = () => setContextMenu((c) => ({ ...c, show: false }));
    window.addEventListener('click', close);
    return () => window.removeEventListener('click', close);
  }, [contextMenu.show]);

  const filteredItems = (() => {
    let list = propostas;
    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase();
      list = list.filter(
        (p) =>
          (p.cliente_nome || '').toLowerCase().includes(q) || (p.titulo || '').toLowerCase().includes(q)
      );
    } else {
      list = list.filter((p) => p.pasta_id === currentFolder);
    }
    return list;
  })();

  const getFolderName = (id: string) => {
    const f = pastas.find((f) => f.id === id);
    return f ? f.nome : 'Pasta';
  };

  const countItemsInFolder = (id: string) => propostas.filter((p) => p.pasta_id === id).length;

  const showContextMenu = (e: React.MouseEvent, type: ContextMenuState['type'], item: any = null) => {
    e.preventDefault();
    setContextMenu({ show: true, x: e.clientX, y: e.clientY, type, item });
  };

  // ----- Drag & Drop -----
  const dragStart = (e: React.DragEvent, item: any) => {
    setDraggedItem(item);
    e.dataTransfer.setData('text/plain', item.id);
  };
  const dragOver = (e: React.DragEvent) => {
    e.preventDefault();
    e.currentTarget.classList.add('drag-over');
  };
  const dragLeave = (e: React.DragEvent) => {
    e.currentTarget.classList.remove('drag-over');
  };
  const dropOnFolder = (e: React.DragEvent, folderId: string) => {
    e.preventDefault();
    e.currentTarget.classList.remove('drag-over');
    if (draggedItem) moverPara(draggedItem.id, folderId);
    setDraggedItem(null);
  };

  // ----- Ações de API -----
  const moverPara = async (propostaId: string, pastaId: string | null) => {
    setPropostas((list) => list.map((p) => (p.id === propostaId ? { ...p, pasta_id: pastaId } : p)));
    setContextMenu((c) => ({ ...c, show: false }));
    await safeFetchJson('/api/propostas/organizar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'move', proposta_id: propostaId, pasta_id: pastaId }),
    });
  };

  const confirmarPasta = async () => {
    const { id, nome, mode } = folderModal;
    if (!nome.trim()) return;

    if (mode === 'create') {
      const newId = crypto.randomUUID();
      setPastas((list) => [...list, { id: newId, nome: nome.trim(), created_at: new Date().toISOString() }]);
      await safeFetchJson('/api/propostas/organizar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'create_folder', id: newId, nome: nome.trim() }),
      });
    } else {
      setPastas((list) => list.map((f) => (f.id === id ? { ...f, nome: nome.trim() } : f)));
      await safeFetchJson('/api/propostas/organizar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'rename_folder', id, nome: nome.trim() }),
      });
    }
    setFolderModal({ show: false, id: null, nome: '', mode: 'create' });
  };

  const confirmarExclusao = async () => {
    const { id, type } = deleteModal;
    if (!id) return;
    if (type === 'proposta') {
      setPropostas((list) => list.filter((p) => p.id !== id));
      await safeFetchJson('/api/propostas/organizar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete_proposal', id }),
      });
    } else if (type === 'pasta') {
      setPastas((list) => list.filter((f) => f.id !== id));
      setPropostas((list) => list.map((p) => (p.pasta_id === id ? { ...p, pasta_id: null } : p)));
      if (currentFolder === id) setCurrentFolder(null);
      await safeFetchJson('/api/propostas/organizar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete_folder', id }),
      });
    }
    setDeleteModal({ show: false, id: null, type: 'proposta', message: '' });
  };

  // ----- Histórico -----
  const fetchHistorico = useCallback(async (id: string) => {
    setCarregandoHistorico(true);
    try {
      const res = await safeFetchJson(`/api/propostas/historico?id=${encodeURIComponent(id)}`);
      if (Array.isArray(res.data)) {
        setHistorico(res.data);
        setRecomendacao('');
      } else if (res.data && typeof res.data === 'object') {
        setHistorico(Array.isArray(res.data.historico) ? res.data.historico : []);
        setRecomendacao(res.data.recomendacao || '');
      } else {
        setHistorico([]);
        setRecomendacao('');
      }
    } catch (e) {
      setHistorico([]);
    } finally {
      setCarregandoHistorico(false);
    }
  }, []);

  const adicionarHistorico = async () => {
    if (!novaNota.trim() || !selectedProposta) return;
    const res = await safeFetchJson('/api/propostas/historico', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ proposta_id: selectedProposta.id, tipo: 'nota', conteudo: novaNota }),
    });
    if (res.data?.sucesso) {
      setNovaNota('');
      if (res.data.recomendacao) setRecomendacao(res.data.recomendacao);
      await fetchHistorico(selectedProposta.id);
    } else {
      alert('Erro ao salvar: ' + (res.data?.erro || JSON.stringify(res.data)));
    }
  };

  // ----- Fechamento -----
  const percentualEntradaPlano = (plano = fechamentoForm.pacote_dado_andamento) => (plano === 'heritage' ? 25 : 20);
  const maxParcelasPlano = (plano = fechamentoForm.pacote_dado_andamento) => (plano === 'heritage' ? 6 : 5);

  const mesesAteEvento = () => {
    const dados = parseDados(selectedProposta);
    const primeira = fechamentoForm.asaas_first_due_date;
    const evento = dados.data_casamento || dados.data_evento || '';
    if (!primeira || !evento) return maxParcelasPlano();
    const inicio = new Date(primeira + 'T00:00:00');
    const fim = new Date(evento + 'T00:00:00');
    if (Number.isNaN(inicio.getTime()) || Number.isNaN(fim.getTime()) || fim < inicio) return 1;
    return Math.max(1, (fim.getFullYear() - inicio.getFullYear()) * 12 + (fim.getMonth() - inicio.getMonth()) + 1);
  };

  const limiteParcelasPermitido = () => {
    const limitePlano = maxParcelasPlano();
    if (fechamentoForm.permitir_parcela_pos_evento) return limitePlano;
    return Math.max(1, Math.min(limitePlano, mesesAteEvento()));
  };

  const ultimaParcelaIso = () => {
    const parcelas = parseInt(String(fechamentoForm.asaas_total_parcelas || 1), 10) || 1;
    return addMesesIso(fechamentoForm.asaas_first_due_date, parcelas - 1);
  };

  const montarCondicoesAutomaticas = () => {
    const modo = fechamentoForm.pagamento_modo;
    const percentual = percentualEntradaPlano();
    const valorFinal = numeroFechamento(fechamentoForm.valor_total);
    const entrada = numeroFechamento(fechamentoForm.asaas_valor_sinal);
    if (modo === 'avista') {
      return `Pagamento à vista no valor total de R$ ${valorFinal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}.`;
    }
    const parcelas = parseInt(String(fechamentoForm.asaas_total_parcelas || 1), 10) || 1;
    return `Entrada de ${percentual}% no valor de R$ ${entrada.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} + saldo parcelado em até ${parcelas}x.`;
  };

  const validarParcelasFechamento = () => {
    let alerta = '';
    if (fechamentoForm.pagamento_modo === 'avista') {
      setFechamentoForm((f) => ({ ...f, asaas_total_parcelas: 1, asaas_first_due_date: '' }));
      setAlertaParcelas('');
      return;
    }

    const limite = limiteParcelasPermitido();
    let parcelas = parseInt(String(fechamentoForm.asaas_total_parcelas || 1), 10) || 1;
    if (parcelas > limite) {
      parcelas = limite;
      setFechamentoForm((f) => ({ ...f, asaas_total_parcelas: limite }));
      alerta = fechamentoForm.permitir_parcela_pos_evento
        ? `Ajustado para o máximo padrão do plano: ${limite} parcelas.`
        : `Ajustado para ${limite} parcela(s), para não passar da data do evento.`;
    }

    const dados = parseDados(selectedProposta);
    const evento = dados.data_casamento || dados.data_evento || '';
    const ultima = ultimaParcelaIso();
    if (evento && ultima && ultima > evento && !fechamentoForm.permitir_parcela_pos_evento) {
      alerta = 'A última parcela passaria da data do evento. Ative a permissão para negociar isso.';
    }
    setAlertaParcelas(alerta);
  };

  const aplicarRegrasPagamento = (forcarCondicoes = false) => {
    const valor = numeroFechamento(fechamentoForm.valor_total);
    const percentual = percentualEntradaPlano();
    const patch: any = {};
    if (valor > 0) {
      patch.asaas_valor_sinal = formatarValorFechamento(valor * (percentual / 100));
    }
    if (!fechamentoForm.asaas_sinal_vencimento) {
      patch.asaas_sinal_vencimento = new Date().toISOString().slice(0, 10);
    }
    if (fechamentoForm.pagamento_modo === 'parcelado' && !fechamentoForm.asaas_first_due_date) {
      patch.asaas_first_due_date = addMesesIso(patch.asaas_sinal_vencimento || fechamentoForm.asaas_sinal_vencimento, 1);
    }
    if (fechamentoForm.pagamento_modo === 'parcelado') {
      const atual = parseInt(String(fechamentoForm.asaas_total_parcelas || 0), 10);
      if (!atual || atual === 1 || forcarCondicoes) {
        patch.asaas_total_parcelas = limiteParcelasPermitido();
      }
    }
    setFechamentoForm((f) => {
      const novo = { ...f, ...patch };
      let alerta = '';
      // Revalidação
      if (novo.pagamento_modo !== 'avista') {
        const limite = maxParcelasPlano();
        let parcelas = parseInt(String(novo.asaas_total_parcelas || 1), 10) || 1;
        const primeira = novo.asaas_first_due_date;
        const dados = parseDados(selectedProposta);
        const evento = dados.data_casamento || dados.data_evento || '';
        let lim = limite;
        if (!novo.permitir_parcela_pos_evento && primeira && evento) {
          const inicio = new Date(primeira + 'T00:00:00');
          const fim = new Date(evento + 'T00:00:00');
          if (!Number.isNaN(inicio.getTime()) && !Number.isNaN(fim.getTime()) && fim >= inicio) {
            lim = Math.min(limite, Math.max(1, (fim.getFullYear() - inicio.getFullYear()) * 12 + (fim.getMonth() - inicio.getMonth()) + 1));
          }
        }
        if (parcelas > lim) {
          parcelas = lim;
          novo.asaas_total_parcelas = lim;
          alerta = novo.permitir_parcela_pos_evento
            ? `Ajustado para o máximo padrão do plano: ${lim} parcelas.`
            : `Ajustado para ${lim} parcela(s), para não passar da data do evento.`;
        }
        const ultima = addMesesIso(primeira, parcelas - 1);
        if (evento && ultima && ultima > evento && !novo.permitir_parcela_pos_evento) {
          alerta = 'A última parcela passaria da data do evento. Ative a permissão para negociar isso.';
        }
      }
      setAlertaParcelas(alerta);
      return novo;
    });

    if (forcarCondicoes || !String(fechamentoForm.escolha_condicoes || '').trim()) {
      setFechamentoForm((f) => ({ ...f, escolha_condicoes: montarCondicoesAutomaticas() }));
    }
  };

  const inicializarFechamento = () => {
    const dados = parseDados(selectedProposta);
    const escolha = dados.cliente_escolha || {};
    const hoje = new Date().toISOString().slice(0, 10);
    const sinalVencimento = dados.asaas_sinal_vencimento || hoje;
    const primeiraParcela = dados.asaas_first_due_date || addMesesIso(sinalVencimento, 1);

    const novoForm = {
      pacote_dado_andamento: dados.pacote_dado_andamento || escolha.plano_id || '',
      valor_total: formatarValorFechamento(dados.valor_fechamento || escolha.valor_total || selectedProposta?.valor_total || ''),
      escolha_condicoes: dados.escolha_condicoes || escolha.condicoes || '',
      asaas_billing_type: dados.asaas_billing_type || 'UNDEFINED',
      asaas_total_parcelas: parseInt(dados.asaas_total_parcelas || 1, 10) || 1,
      asaas_first_due_date: primeiraParcela,
      asaas_valor_sinal: formatarValorFechamento(dados.asaas_valor_sinal || ''),
      asaas_sinal_vencimento: sinalVencimento,
      prazo_contrato: dados.prazo_contrato || '',
      pagamento_modo: 'parcelado' as 'avista' | 'parcelado',
      permitir_parcela_pos_evento: !!dados.permitir_parcela_pos_evento,
    };
    setFechamentoForm(novoForm);
    setAlertaParcelas('');

    const condicoesAtuais = String(novoForm.escolha_condicoes || '');
    const pareceTextoPadrao =
      /^Entrada de \d+%(\s+no valor de R\$ [\d.,]+)? \+ saldo parcelado/i.test(condicoesAtuais) ||
      /^Entrada de \d+% \+ Saldo parcelado/i.test(condicoesAtuais);
    const forcar = !condicoesAtuais.trim() || pareceTextoPadrao;
    setTimeout(() => aplicarRegrasPagamento(forcar), 0);
  };

  const salvarFechamento = async () => {
    if (!selectedProposta) return;
    setFechamentoSalvando(true);
    try {
      const res = await safeFetchJson('/api/propostas/fechamento', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: selectedProposta.id, ...fechamentoForm }),
      });
      if (!res.data?.success) throw new Error(res.data?.erro || 'Falha ao salvar dados do fechamento.');
      setSelectedProposta((p: any) => ({ ...p, dados_json: res.data.dados_json, valor_total: res.data.valor_total }));
      setPropostas((list) => list.map((p) => (p.id === selectedProposta.id ? { ...p, dados_json: res.data.dados_json, valor_total: res.data.valor_total } : p)));
      await fetchHistorico(selectedProposta.id);
      alert('Dados do fechamento salvos.');
    } catch (e: any) {
      alert(e.message);
    } finally {
      setFechamentoSalvando(false);
    }
  };

  // ----- Resumo -----
  const abrirResumo = async (p: any) => {
    setSelectedProposta(p);
    setModalResumoAberto(true);
    setHistorico([]);
    inicializarFechamento();
    await fetchHistorico(p.id);
  };

  const getResumoDados = () => parseDados(selectedProposta);
  const getPlanoEscolhidoLabel = () => {
    const mapa: Record<string, string> = { heritage: 'Heritage', cinematic: 'Cinematic', essencial: 'Essencial' };
    return mapa[fechamentoForm.pacote_dado_andamento] || 'A definir';
  };

  // ----- Outras ações -----
  const gerarContrato = (proposta: any) => {
    if (!proposta) return;
    const dados = parseDados(proposta);
    if (proposta.tipo === 'casamento') {
      const plano = dados?.cliente_escolha?.plano_id || dados?.pacote_dado_andamento || '';
      const valor = parseFloat(dados?.cliente_escolha?.valor_total || proposta.valor_total || 0) || 0;
      const faltas: string[] = [];
      if (!plano) faltas.push('escolher o plano fechado pelo casal');
      if (valor <= 0) faltas.push('conferir o valor final');
      if (!dados.data_casamento) faltas.push('preencher a data do casamento');
      if (!dados.whatsapp) faltas.push('preencher o WhatsApp do cliente');
      if (faltas.length > 0) {
        alert('Antes de gerar o contrato, revise a proposta:\n\n- ' + faltas.join('\n- '));
        return;
      }
    }
    const url = `${APP_URL || ''}/api/contratos/pdf?proposta_id=${proposta.id}`;
    window.open(url, '_blank');
  };

  const copiarLink = (slug: string) => {
    const link = `${window.location.origin}/p/${slug}`;
    navigator.clipboard.writeText(link).then(() => alert('Link copiado!'));
  };

  const enviarWhatsApp = async (proposta: any) => {
    const dados = parseDados(proposta);
    const numero = String(dados.whatsapp || '').replace(/\D/g, '');
    if (!numero) {
      alert('Número de WhatsApp não cadastrado nesta proposta.\nAdicione o WhatsApp do cliente ao editar a proposta.');
      return;
    }
    setWhatsappLoading(true);
    try {
      const res = await safeFetchJson('/api/propostas/mensagem-whatsapp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: proposta.id }),
      });
      if (res.data?.erro) throw new Error(res.data.erro);
      window.open(`https://wa.me/${numero}?text=${encodeURIComponent(res.data.mensagem)}`, '_blank');
    } catch (e) {
      const nomeF = getResponsavel(dados, proposta);
      const primeiroNome = nomeF.split(' ')[0];
      const link = `${window.location.origin}/p/${proposta.slug}`;
      const fallback = `Oi, ${primeiroNome}! Tudo bem?\n\nAcabei de subir o material do ${proposta.titulo} aqui no sistema. Deixei tudo bem visual pra você conseguir enxergar o projeto ganhando forma, exatamente como a gente conversou.\n\nDá uma olhada aqui:\n👉 ${link}`;
      window.open(`https://wa.me/${numero}?text=${encodeURIComponent(fallback)}`, '_blank');
    } finally {
      setWhatsappLoading(false);
    }
  };

  const abrirNova = () => {
    if (window.innerWidth > 1024) {
      setNovaUrl('/gerenciamento/proposta-nova?layout=modal' + (currentFolder ? `&folder=${currentFolder}` : ''));
      setShowModalNova(true);
    } else {
      window.location.href = '/gerenciamento/proposta-nova' + (currentFolder ? `?folder=${currentFolder}` : '');
    }
  };

  const abrirEditar = (proposta: any) => {
    if (window.innerWidth > 1024) {
      setEditUrl(`/gerenciamento/proposta-editar?id=${proposta.id}&layout=modal&t=${Date.now()}`);
      setShowModalEditar(true);
    } else {
      window.location.href = `/gerenciamento/proposta-editar?id=${proposta.id}`;
    }
  };

  const progressoStatus = (status: string) => {
    if (status === 'rascunho') return { texto: 'Rascunho', pct: '10%', cor: 'bg-zinc-600' };
    if (status === 'pendente') return { texto: 'Aguardando', pct: '50%', cor: 'bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.5)]' };
    if (status === 'aceita' || status === 'aprovada') return { texto: 'Fechado', pct: '100%', cor: 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]' };
    return { texto: 'Recusado', pct: '100%', cor: 'bg-red-500' };
  };

  const iconeTipo = (tipo: string) => {
    if (tipo === 'marketing') return <Megaphone className="w-10 h-10 opacity-30" />;
    if (tipo === 'filmmaker') return <Video className="w-10 h-10 opacity-30" />;
    return <FileText className="w-10 h-10 opacity-30" />;
  };

  const inputCls =
    'w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none focus:border-white/40';

  return (
    <div
      className="bg-[#050505] text-white flex flex-col min-h-screen rounded-2xl overflow-hidden"
      onContextMenu={(e) => showContextMenu(e, 'root')}
    >
      <style>{`
        .folder-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 24px; }
        .item-folder { position: relative; background: #121212; border-radius: 20px; padding: 16px; aspect-ratio: 1/0.9; display: flex; flex-direction: column; transition: all 0.3s cubic-bezier(0.4,0,0.2,1); cursor: pointer; border: 1px solid rgba(255,255,255,0.03); }
        .item-folder:hover { transform: translateY(-5px); background: #1a1a1a; border-color: rgba(255,255,255,0.1); }
        .folder-icon-wrapper { flex: 1; display: flex; align-items: center; justify-content: center; }
        .item-doc { position: relative; background: #181818; border-radius: 16px; padding: 12px; aspect-ratio: 1/1.2; display: flex; flex-direction: column; transition: all 0.2s ease; cursor: pointer; border: 1px solid rgba(255,255,255,0.05); }
        .item-doc:hover { background: #222; border-color: rgba(255,255,255,0.15); }
        .doc-visual { flex: 1; background: #fff; border-radius: 8px; margin-bottom: 10px; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .context-menu { position: fixed; background: #1a1a1a; border: 1px solid #333; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); z-index: 1000; padding: 5px; min-width: 160px; }
        .context-menu button { width: 100%; text-align: left; padding: 8px 12px; font-size: 12px; color: #eee; border-radius: 6px; display: flex; align-items: center; gap: 8px; background: transparent; }
        .context-menu button:hover { background: #333; }
        .context-menu button:disabled { opacity: 0.5; }
        .drag-over { background: rgba(255,255,255,0.1) !important; border: 2px dashed #555 !important; }
        .breadcrumb-item:not(:last-child)::after { content: '/'; margin: 0 8px; color: #444; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #333; border-radius: 8px; }
      `}</style>

      {/* Header & Breadcrumbs */}
      <div className="mb-10">
        <div className="flex items-center gap-2 mb-4 text-xs font-bold uppercase tracking-widest text-zinc-600">
          <span className="cursor-pointer hover:text-white transition-colors" onClick={() => setCurrentFolder(null)}>Raiz</span>
          {currentFolder && (
            <span className="breadcrumb-item cursor-default text-zinc-400">{getFolderName(currentFolder)}</span>
          )}
        </div>

        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight text-white">
              {currentFolder ? getFolderName(currentFolder) : 'Propostas Web'}
            </h1>
            <p className="text-zinc-500 text-sm mt-1">
              {currentFolder ? 'Documentos nesta categoria' : 'Categorias e propostas avulsas'}
            </p>
          </div>

          <div className="flex items-center gap-3">
            <button
              onClick={() => setShowSearch(!showSearch)}
              className="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white"
            >
              <Search className="w-5 h-5" />
            </button>

            <button
              onClick={abrirNova}
              className="flex items-center gap-2 bg-white text-black px-4 py-2 rounded-full text-xs font-bold hover:bg-zinc-200 transition-all"
            >
              <Plus className="w-4 h-4" />
              Nova Proposta
            </button>
          </div>
        </div>

        {showSearch && (
          <div className="mt-4">
            <input
              ref={searchRef}
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Buscar em tudo..."
              className="bg-zinc-900 border border-zinc-800 text-white text-sm rounded-xl px-4 py-3 w-full outline-none focus:border-zinc-600"
            />
          </div>
        )}
      </div>

      {/* Grid Layout */}
      <div className="folder-grid flex-1">
        {!currentFolder &&
          pastas.map((f) => {
            const count = countItemsInFolder(f.id);
            const empty = count === 0;
            const cheia = count > 5;
            return (
              <div
                key={f.id}
                className="item-folder group transition-all duration-500"
                style={{ opacity: empty ? 0.4 : 1, filter: empty ? 'grayscale(1)' : undefined, background: cheia ? 'rgba(24,24,24,0.5)' : undefined, borderColor: cheia ? 'rgba(255,255,255,0.1)' : undefined }}
                onClick={() => setCurrentFolder(f.id)}
                onContextMenu={(e) => showContextMenu(e, 'folder', f)}
                onDragOver={dragOver}
                onDragLeave={dragLeave}
                onDrop={(e) => dropOnFolder(e, f.id)}
              >
                <div className="flex items-center justify-between mb-2">
                  <span className={`text-xs font-bold transition-colors ${empty ? 'text-zinc-600' : 'text-zinc-300'}`}>{f.nome}</span>
                  <span className={`text-[9px] font-black tracking-widest uppercase ${empty ? 'text-zinc-700' : 'text-zinc-500'}`}>{count} ITENS</span>
                </div>

                <div className="folder-icon-wrapper relative">
                  {count === 0 ? (
                    <Folder className="w-16 h-16 text-zinc-900 transition-all duration-500" />
                  ) : count > 5 ? (
                    <div className="relative">
                      <Folders className="w-16 h-16 text-white scale-110 drop-shadow-[0_0_15px_rgba(255,255,255,0.1)] transition-all duration-500" />
                      <div className="absolute -top-2 -right-2 w-5 h-5 bg-white text-black text-[10px] font-black rounded-full flex items-center justify-center shadow-lg border-2 border-[#050505] animate-bounce">
                        <Zap className="w-2.5 h-2.5" />
                      </div>
                    </div>
                  ) : (
                    <FolderOpen className="w-16 h-16 text-zinc-700 group-hover:text-zinc-500 transition-all duration-500" />
                  )}
                </div>
              </div>
            );
          })}

        {/* Documentos */}
        {filteredItems.map((p) => {
          const st = progressoStatus(p.status);
          return (
            <div
              key={p.id}
              className="item-doc group"
              draggable
              onDragStart={(e) => dragStart(e, p)}
              onClick={() => abrirResumo(p)}
              onContextMenu={(e) => showContextMenu(e, 'proposal', p)}
            >
              <div className="doc-visual">
                {iconeTipo(p.tipo)}
                <div className="absolute bottom-2 left-2 right-2 flex flex-col gap-1.5">
                  <div className="flex items-center justify-between px-1">
                    <span className="text-[8px] font-black uppercase tracking-widest text-zinc-500">{st.texto}</span>
                    <span className="text-[8px] font-bold text-zinc-600">{st.pct}</span>
                  </div>
                  <div className="h-1 w-full bg-zinc-800 rounded-full overflow-hidden flex">
                    <div className={`h-full transition-all duration-1000 ease-out ${st.cor}`} style={{ width: st.pct }} />
                  </div>
                </div>
              </div>

              <div className="px-1 mt-2">
                <div className="text-[11px] font-bold text-white truncate">{p.cliente_nome}</div>
                <div className="text-[9px] text-zinc-500 truncate">{p.titulo}</div>
              </div>

              <div className="mt-auto flex items-center justify-between pt-2">
                <span className="text-[9px] text-zinc-600">
                  {p.created_at ? new Date(p.created_at).toLocaleDateString('pt-BR') : ''}
                </span>
                <MoreHorizontal className="w-3 h-3 text-zinc-700" />
              </div>
            </div>
          );
        })}

        {/* Criar Primeira Pasta */}
        {!currentFolder && pastas.length === 0 && (
          <div
            className="item-folder border-dashed border-zinc-800 bg-transparent flex items-center justify-center cursor-pointer hover:border-zinc-600 transition-colors"
            onClick={() => setFolderModal({ show: true, id: null, nome: '', mode: 'create' })}
          >
            <div className="text-center">
              <FolderPlus className="w-8 h-8 mx-auto mb-2 text-zinc-800" />
              <p className="text-[10px] font-bold text-zinc-700">Criar Primeira Pasta</p>
            </div>
          </div>
        )}
      </div>

      {/* Empty States */}
      {filteredItems.length === 0 && (currentFolder || searchQuery) && (
        <div className="text-center py-20 flex-1">
          <FileX className="w-12 h-12 mx-auto mb-4 text-zinc-900" />
          <p className="text-zinc-600 font-medium">Nenhum item nesta localização.</p>
        </div>
      )}

      {/* Footer */}
      <div className="mt-auto pt-20 pb-10">
        <div className="text-center mb-6">
          <span className="text-zinc-800 text-[10px] font-bold uppercase tracking-[0.3em]">Gestão de Ativos</span>
        </div>
        <div className="max-w-xl mx-auto bg-zinc-900/30 border border-zinc-800/30 rounded-2xl p-6 flex items-center gap-6">
          <div className="w-14 h-14 rounded-xl bg-zinc-900 flex items-center justify-center text-zinc-500">
            <Archive className="w-7 h-7" />
          </div>
          <div className="flex-1">
            <h3 className="text-sm font-bold text-white mb-1">Mantenha o ambiente organizado</h3>
            <p className="text-xs text-zinc-500 leading-relaxed">
              Coloque cada proposta em sua pasta correspondente. Arraste e solte arquivos para mover entre categorias.
            </p>
          </div>
          <CheckCircle2 className="w-5 h-5 text-zinc-700" />
        </div>
      </div>

      {/* Context Menu */}
      {contextMenu.show && (
        <div
          className="context-menu"
          style={{ top: contextMenu.y, left: contextMenu.x }}
          onClick={(e) => e.stopPropagation()}
        >
          {contextMenu.type === 'root' && (
            <>
              <button onClick={() => setFolderModal({ show: true, id: null, nome: '', mode: 'create' })}>
                <FolderPlus className="w-4 h-4" /> Criar Nova Pasta
              </button>
              <button onClick={() => { setContextMenu((c) => ({ ...c, show: false })); abrirNova(); }}>
                <FilePlus className="w-4 h-4" /> Nova Proposta
              </button>
            </>
          )}

          {contextMenu.type === 'folder' && contextMenu.item && (
            <>
              <button onClick={() => { setCurrentFolder(contextMenu.item.id); setContextMenu((c) => ({ ...c, show: false })); }}>
                <FolderOpen className="w-4 h-4" /> Abrir Pasta
              </button>
              <button onClick={() => setFolderModal({ show: true, id: contextMenu.item.id, nome: contextMenu.item.nome, mode: 'rename' })} className="text-zinc-400">
                <Edit3 className="w-4 h-4" /> Renomear
              </button>
              <div className="h-px bg-zinc-800 my-1" />
              <button
                onClick={() => setDeleteModal({ show: true, id: contextMenu.item.id, type: 'pasta', message: 'Excluir esta pasta? As propostas dentro dela voltarão para a raiz.' })}
                className="text-red-400 hover:bg-red-900/20"
              >
                <Trash2 className="w-4 h-4" /> Excluir Pasta
              </button>
            </>
          )}

          {contextMenu.type === 'proposal' && contextMenu.item && (
            <>
              <button onClick={() => { window.open(`${APP_URL || window.location.origin}/p/${contextMenu.item.slug}`, '_blank'); setContextMenu((c) => ({ ...c, show: false })); }}>
                <ExternalLink className="w-4 h-4" /> Ver Proposta
              </button>
              <button onClick={() => { copiarLink(contextMenu.item.slug); setContextMenu((c) => ({ ...c, show: false })); }}>
                <Copy className="w-4 h-4" /> Copiar Link
              </button>
              <button
                onClick={() => { enviarWhatsApp(contextMenu.item); setContextMenu((c) => ({ ...c, show: false })); }}
                disabled={whatsappLoading}
                className="text-green-400 hover:bg-green-900/20"
              >
                <MessageCircle className="w-4 h-4" />
                <span>{whatsappLoading ? 'Gerando mensagem...' : 'Enviar via WhatsApp'}</span>
              </button>
              <button onClick={() => { setContextMenu((c) => ({ ...c, show: false })); abrirEditar(contextMenu.item); }}>
                <Edit2 className="w-4 h-4" /> Editar Dados
              </button>
              <button onClick={() => { gerarContrato(contextMenu.item); setContextMenu((c) => ({ ...c, show: false })); }}>
                <Scroll className="w-4 h-4" /> Gerar Contrato
              </button>
              <div className="relative group/submenu">
                <button className="justify-between">
                  <span className="flex items-center gap-2"><FolderInput className="w-4 h-4" /> Mover para...</span>
                  <ChevronRight className="w-3 h-3" />
                </button>
                <div className="absolute left-full top-0 ml-1 w-48 bg-zinc-900 border border-zinc-800 rounded-lg shadow-xl p-1 hidden group-hover/submenu:block">
                  <button onClick={() => moverPara(contextMenu.item.id, null)}>Raiz (Nenhuma)</button>
                  {pastas.map((f) => (
                    <button key={f.id} onClick={() => moverPara(contextMenu.item.id, f.id)}>{f.nome}</button>
                  ))}
                </div>
              </div>
              <div className="h-px bg-zinc-800 my-1" />
              <button
                onClick={() => setDeleteModal({ show: true, id: contextMenu.item.id, type: 'proposta', message: 'Deseja realmente excluir esta proposta permanentemente?' })}
                className="text-red-400 hover:bg-red-900/20"
              >
                <Trash2 className="w-4 h-4" /> Excluir
              </button>
            </>
          )}
        </div>
      )}

      {/* Modal Resumo da Proposta */}
      {modalResumoAberto && selectedProposta && (
        <div className="fixed inset-0 z-[2000] flex items-center justify-center p-6 bg-black/80 backdrop-blur-md" onClick={() => setModalResumoAberto(false)}>
          <div
            className="bg-[#0c0c0c] border border-white/10 rounded-[2.5rem] w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[95vh]"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Header */}
            <div className="p-8 border-b border-white/5 flex items-start justify-between">
              <div>
                <div className="flex items-center gap-3 mb-2">
                  <span className={`px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest ${
                    selectedProposta.status === 'rascunho' ? 'bg-zinc-800 text-zinc-400'
                    : selectedProposta.status === 'pendente' ? 'bg-blue-500/10 text-blue-500'
                    : (selectedProposta.status === 'aceita' || selectedProposta.status === 'aprovada') ? 'bg-emerald-500/10 text-emerald-500'
                    : 'bg-red-500/10 text-red-500'
                  }`}>
                    {(selectedProposta.status || '').toUpperCase()}
                  </span>
                  <span className="text-zinc-600 text-[10px] font-bold uppercase tracking-widest">
                    {'Criada em ' + (selectedProposta.created_at ? new Date(selectedProposta.created_at).toLocaleDateString() : '')}
                  </span>
                </div>
                <h2 className="text-3xl font-black text-white tracking-tight">{selectedProposta.cliente_nome}</h2>
                <p className="text-zinc-500 font-medium">{selectedProposta.titulo}</p>
              </div>
              <button onClick={() => setModalResumoAberto(false)} className="text-zinc-500 hover:text-white transition-colors">
                <X className="w-6 h-6" />
              </button>
            </div>

            {/* Content */}
            <div className="p-8 overflow-y-auto max-h-[60vh] custom-scrollbar">
              <div className="grid grid-cols-2 gap-8 mb-8">
                <div>
                  <h3 className="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-4">Dados do Cliente</h3>
                  <div className="space-y-3">
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 rounded-lg bg-zinc-900 flex items-center justify-center text-zinc-500">
                        <User className="w-4 h-4" />
                      </div>
                      <div className="flex flex-col">
                        <span className="text-sm font-bold text-zinc-300">{getResponsavel(getResumoDados(), selectedProposta)}</span>
                        {getResumoDados().contato_tipo && (
                          <span className={`text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full mt-1 inline-block w-fit ${
                            getResumoDados().contato_tipo === 'noiva' ? 'bg-rose-500/10 text-rose-500'
                            : getResumoDados().contato_tipo === 'noivo' ? 'bg-blue-500/10 text-blue-500'
                            : 'bg-zinc-800 text-zinc-500'
                          }`}>
                            {getResumoDados().contato_tipo}
                          </span>
                        )}
                      </div>
                    </div>
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 rounded-lg bg-zinc-900 flex items-center justify-center text-zinc-500">
                        <Phone className="w-4 h-4" />
                      </div>
                      <span className="text-sm font-bold text-zinc-300">{getResumoDados().whatsapp || 'Não informado'}</span>
                    </div>
                  </div>
                </div>
                <div>
                  <h3 className="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-4">Andamento</h3>
                  <div className="bg-zinc-900/50 rounded-2xl p-4 border border-white/5">
                    <div className="flex items-center justify-between mb-3">
                      <span className="text-xs font-bold text-zinc-400">Progresso</span>
                      <span className="text-xs font-black text-white">
                        {selectedProposta.status === 'rascunho' ? '10%' : (selectedProposta.status === 'pendente' ? '50%' : '100%')}
                      </span>
                    </div>
                    <div className="h-1.5 w-full bg-zinc-800 rounded-full overflow-hidden">
                      <div className="h-full bg-emerald-500 transition-all duration-1000" style={{ width: selectedProposta.status === 'rascunho' ? '10%' : (selectedProposta.status === 'pendente' ? '50%' : '100%') }} />
                    </div>
                    <p className="text-[10px] text-zinc-500 mt-3 italic">
                      {selectedProposta.status === 'aceita' || selectedProposta.status === 'aprovada'
                        ? 'Proposta fechada e aprovada pelo cliente.'
                        : 'Aguardando interação do cliente.'}
                    </p>
                  </div>
                </div>
                {recomendacao && (
                  <div>
                    <h3 className="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-4">Próximo passo recomendado</h3>
                    <div className="bg-emerald-500/10 border border-emerald-500/15 rounded-2xl p-4 text-sm text-zinc-100">
                      <p>{recomendacao}</p>
                    </div>
                  </div>
                )}
              </div>

              {/* Histórico / Timeline */}
              <div className="mb-8">
                <h3 className="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-4">Histórico de Interações</h3>

                <div className="bg-zinc-900/30 border border-white/5 rounded-2xl p-4 mb-4">
                  <textarea
                    value={novaNota}
                    onChange={(e) => setNovaNota(e.target.value)}
                    placeholder="O que aconteceu nesta conversa? (ex: cliente vai pensar, objeção de preço...)"
                    className="w-full bg-transparent border-0 focus:ring-0 text-sm text-zinc-300 placeholder:text-zinc-600 resize-none outline-none"
                    rows={2}
                  />
                  <div className="flex justify-end mt-2">
                    <button
                      onClick={adicionarHistorico}
                      disabled={!novaNota.trim()}
                      className="px-4 py-1.5 bg-white text-black rounded-lg text-[10px] font-black uppercase tracking-widest disabled:opacity-50 hover:scale-105 transition-all"
                    >
                      Salvar Registro
                    </button>
                  </div>
                </div>

                <div className="space-y-4 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                  {carregandoHistorico && (
                    <div className="flex justify-center py-4">
                      <div className="w-6 h-6 border-2 border-white/10 border-t-white rounded-full animate-spin" />
                    </div>
                  )}
                  {historico.map((event: any, idx: number) => (
                    <div key={event.id ?? idx} className="flex gap-4 group">
                      <div className="flex flex-col items-center">
                        <div className="w-8 h-8 rounded-full bg-zinc-900 border border-white/5 flex items-center justify-center text-zinc-500 first:bg-white first:text-black">
                          {event.tipo === 'ligacao' ? <Phone className="w-3.5 h-3.5" /> : <MessageCircle className="w-3.5 h-3.5" />}
                        </div>
                        <div className="w-px flex-1 bg-white/5 mt-2" style={{ display: idx === historico.length - 1 ? 'none' : undefined }} />
                      </div>
                      <div className="flex-1 pb-6">
                        <div className="flex items-center justify-between mb-1">
                          <span className="text-[10px] font-bold text-zinc-400">{event.usuario_nome}</span>
                          <span className="text-[9px] text-zinc-600">
                            {event.created_at ? new Date(event.created_at).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }) : ''}
                          </span>
                        </div>
                        <p className="text-xs text-zinc-300 leading-relaxed">{event.conteudo}</p>
                      </div>
                    </div>
                  ))}
                  {!carregandoHistorico && historico.length === 0 && (
                    <div className="text-center py-6 border border-dashed border-white/5 rounded-3xl">
                      <p className="text-[10px] font-bold text-zinc-600 uppercase tracking-widest">Nenhuma interação registrada ainda.</p>
                    </div>
                  )}
                </div>
              </div>

              {/* Fechamento / Contrato / Asaas */}
              <div className="bg-white/5 rounded-3xl p-6 border border-white/5 mb-8">
                <div className="flex items-start justify-between gap-4 mb-6">
                  <div>
                    <h3 className="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-2">Fechamento, contrato e Asaas</h3>
                    <p className="text-xs text-zinc-500">Dados usados para gerar contrato, assinatura e cobrança.</p>
                  </div>
                  <span className="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-[10px] font-black uppercase tracking-widest">
                    {getPlanoEscolhidoLabel()}
                  </span>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Plano escolhido</label>
                    <select
                      value={fechamentoForm.pacote_dado_andamento}
                      onChange={(e) => setFechamentoForm((f) => ({ ...f, pacote_dado_andamento: e.target.value }))}
                      className={inputCls}
                    >
                      <option value="">Ainda não definido</option>
                      <option value="heritage">Experiência Heritage</option>
                      <option value="cinematic">Experiência Cinematic</option>
                      <option value="essencial">Registro Essencial</option>
                    </select>
                  </div>
                  <div>
                    <label className="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Valor final</label>
                    <input value={fechamentoForm.valor_total} onChange={(e) => setFechamentoForm((f) => ({ ...f, valor_total: e.target.value }))} type="text" className={inputCls} placeholder="R$ 0,00" />
                  </div>
                  <div>
                    <label className="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Cobrança Asaas</label>
                    <select value={fechamentoForm.asaas_billing_type} onChange={(e) => setFechamentoForm((f) => ({ ...f, asaas_billing_type: e.target.value }))} className={inputCls}>
                      <option value="UNDEFINED">Cliente escolhe no Asaas</option>
                      <option value="PIX">Pix</option>
                      <option value="BOLETO">Boleto</option>
                      <option value="CREDIT_CARD">Cartão de crédito</option>
                    </select>
                  </div>
                  <div>
                    <label className="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Forma de pagamento</label>
                    <select value={fechamentoForm.pagamento_modo} onChange={(e) => setFechamentoForm((f) => ({ ...f, pagamento_modo: e.target.value as any }))} className={inputCls}>
                      <option value="avista">À vista</option>
                      <option value="parcelado">Parcelado</option>
                    </select>
                  </div>
                  {fechamentoForm.pagamento_modo === 'parcelado' && (
                    <div>
                      <label className="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Parcelas do saldo</label>
                      <input value={fechamentoForm.asaas_total_parcelas} onChange={(e) => setFechamentoForm((f) => ({ ...f, asaas_total_parcelas: parseInt(e.target.value || '1', 10) || 1 }))} type="number" min={1} max={60} className={inputCls} />
                      <p className="text-[10px] text-zinc-500 mt-2">Máximo padrão: {maxParcelasPlano()} parcelas para este plano.</p>
                    </div>
                  )}
                  <div>
                    <label className="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Vencimento do sinal</label>
                    <input value={fechamentoForm.asaas_sinal_vencimento} onChange={(e) => setFechamentoForm((f) => ({ ...f, asaas_sinal_vencimento: e.target.value }))} type="date" className={inputCls} />
                  </div>
                  <div>
                    <label className="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Valor do sinal</label>
                    <input value={fechamentoForm.asaas_valor_sinal} onChange={(e) => setFechamentoForm((f) => ({ ...f, asaas_valor_sinal: e.target.value }))} type="text" className={inputCls} placeholder="R$ 0,00" />
                    <p className="text-[10px] text-zinc-500 mt-2">Entrada sugerida: {percentualEntradaPlano()}% do valor final.</p>
                  </div>
                  {fechamentoForm.pagamento_modo === 'parcelado' && (
                    <div>
                      <label className="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Primeira parcela</label>
                      <input value={fechamentoForm.asaas_first_due_date} onChange={(e) => setFechamentoForm((f) => ({ ...f, asaas_first_due_date: e.target.value }))} type="date" className={inputCls} />
                    </div>
                  )}
                  <div>
                    <label className="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Prazo / observação</label>
                    <input value={fechamentoForm.prazo_contrato} onChange={(e) => setFechamentoForm((f) => ({ ...f, prazo_contrato: e.target.value }))} type="text" className={inputCls} placeholder="Ex: quitar até 15 dias antes do evento" />
                  </div>
                  <div className="md:col-span-2" style={{ display: fechamentoForm.pagamento_modo === 'parcelado' ? undefined : 'none' }}>
                    <label className="flex items-center justify-between gap-4 p-4 rounded-2xl bg-black/30 border border-white/10 cursor-pointer">
                      <div>
                        <span className="block text-xs font-bold text-white">Permitir parcela depois da data do evento</span>
                        <span className="block text-[10px] text-zinc-500 mt-1">Use somente quando isso fizer parte da negociação com o casal.</span>
                      </div>
                      <input type="checkbox" checked={fechamentoForm.permitir_parcela_pos_evento} onChange={(e) => setFechamentoForm((f) => ({ ...f, permitir_parcela_pos_evento: e.target.checked }))} className="w-5 h-5" />
                    </label>
                    <p className="text-[10px] mt-2" style={{ color: alertaParcelas ? '#fbbf24' : '#71717a' }}>{alertaParcelas || resumoParcelas()}</p>
                  </div>
                  <div className="md:col-span-2">
                    <label className="text-[10px] font-black text-zinc-500 uppercase tracking-widest block mb-2">Condições de pagamento para o contrato</label>
                    <textarea value={fechamentoForm.escolha_condicoes} onChange={(e) => setFechamentoForm((f) => ({ ...f, escolha_condicoes: e.target.value }))} rows={3} className={inputCls} placeholder="Ex: Entrada de 20% via Pix e saldo parcelado..." />
                  </div>
                </div>

                <div className="flex justify-end mt-5">
                  <button
                    onClick={salvarFechamento}
                    disabled={fechamentoSalvando}
                    className="px-5 py-2.5 rounded-xl bg-white text-black text-[10px] font-black uppercase tracking-widest disabled:opacity-50 hover:scale-105 transition-all"
                  >
                    {fechamentoSalvando ? 'Salvando...' : 'Salvar dados do fechamento'}
                  </button>
                </div>
              </div>

              {/* Resumo do que foi proposto */}
              <div className="bg-white/5 rounded-3xl p-6 border border-white/5">
                <h3 className="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-6">O que foi proposto</h3>
                <div className="space-y-4">
                  {getResumoDados().pacotes && getResumoDados().pacotes.length > 0 ? (
                    getResumoDados().pacotes.map((pacote: any, i: number) => (
                      <div key={i} className="flex items-center justify-between py-3 border-b border-white/5 last:border-0">
                        <div className="flex items-center gap-4">
                          <div className="w-10 h-10 rounded-xl bg-white text-black flex items-center justify-center font-black text-xs">
                            {String(pacote.nome || '?').charAt(0)}
                          </div>
                          <div>
                            <p className="text-sm font-bold text-white">{pacote.nome}</p>
                            <p className="text-[10px] text-zinc-500">{pacote.itens ? `${pacote.itens.length} itens inclusos` : ''}</p>
                          </div>
                        </div>
                        <p className="text-sm font-black text-white">{'R$ ' + (parseFloat(pacote.valor) || 0).toLocaleString('pt-BR')}</p>
                      </div>
                    ))
                  ) : (
                    <p className="text-sm text-zinc-500 italic py-4 text-center">Nenhum pacote detalhado no resumo.</p>
                  )}
                </div>
              </div>
            </div>

            {/* Footer Actions */}
            <div className="p-8 bg-zinc-900/50 border-t border-white/5 flex items-center justify-between gap-4">
              <div className="flex items-center gap-3">
                <button
                  onClick={() => { setDeleteModal({ show: true, id: selectedProposta.id, type: 'proposta', message: 'Deseja realmente excluir esta proposta permanentemente?' }); setModalResumoAberto(false); }}
                  className="w-12 h-12 rounded-2xl bg-red-500/10 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all"
                >
                  <Trash2 className="w-5 h-5" />
                </button>
                <button onClick={() => enviarWhatsApp(selectedProposta)} className="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center hover:bg-emerald-500 hover:text-white transition-all">
                  <MessageCircle className="w-5 h-5" />
                </button>
                <button
                  onClick={() => { setModalResumoAberto(false); abrirEditar(selectedProposta); }}
                  className="w-12 h-12 rounded-2xl bg-zinc-800 text-zinc-400 flex items-center justify-center hover:bg-white hover:text-black transition-all"
                >
                  <Edit3 className="w-5 h-5" />
                </button>
                <button
                  onClick={() => gerarContrato(selectedProposta)}
                  title="Gerar Contrato"
                  className="w-12 h-12 rounded-2xl bg-zinc-800 text-zinc-400 flex items-center justify-center hover:bg-white hover:text-black transition-all"
                >
                  <Scroll className="w-5 h-5" />
                </button>
              </div>

              <button
                onClick={() => window.open(`${APP_URL || window.location.origin}/p/${selectedProposta.slug}`, '_blank')}
                className="flex-1 bg-white text-black h-12 rounded-2xl font-black text-sm hover:scale-[1.02] active:scale-95 transition-all shadow-xl flex items-center justify-center gap-2"
              >
                <ExternalLink className="w-4 h-4" />
                Abrir Proposta Completa
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal Nova (iframe) */}
      {showModalNova && (
        <div className="fixed inset-0 z-[3000] flex items-center justify-center p-6 bg-black/90 backdrop-blur-md">
          <div className="bg-[#0c0c0c]/90 backdrop-blur-3xl rounded-[2.5rem] w-[85%] h-[90vh] flex flex-col overflow-hidden relative shadow-[0_0_100px_rgba(0,0,0,0.8)] border border-white/5">
            <div className="px-8 py-6 bg-transparent border-b border-white/5 flex items-center justify-between">
              <h2 className="text-[10px] font-black text-zinc-500 uppercase tracking-[0.3em]">Registrar Nova Proposta</h2>
              <button onClick={() => { setShowModalNova(false); window.location.reload(); }} className="p-2 hover:bg-white/5 rounded-full transition-colors text-zinc-500 hover:text-white group flex items-center justify-center">
                <X className="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" />
              </button>
            </div>
            <div className="flex-1 bg-[#fcfcfc] overflow-hidden">
              <iframe src={novaUrl} className="w-full h-full border-0" />
            </div>
          </div>
        </div>
      )}

      {/* Modal Editar (iframe) */}
      {showModalEditar && (
        <div className="fixed inset-0 z-[3000] flex items-center justify-center p-6 bg-black/90 backdrop-blur-md">
          <div className="bg-[#0c0c0c]/90 backdrop-blur-3xl rounded-[2.5rem] w-[85%] h-[90vh] flex flex-col overflow-hidden relative shadow-[0_0_100px_rgba(0,0,0,0.8)] border border-white/5">
            <div className="px-8 py-4 bg-zinc-900 border-b border-zinc-800 flex items-center justify-between">
              <h2 className="text-sm font-bold text-white uppercase tracking-wider">Editar Proposta</h2>
              <button onClick={() => { setShowModalEditar(false); window.location.reload(); }} className="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white group flex items-center justify-center">
                <X className="w-6 h-6 group-hover:rotate-90 transition-transform duration-300" />
              </button>
            </div>
            <div className="flex-1 bg-[#fcfcfc] overflow-hidden">
              <iframe src={editUrl} className="w-full h-full border-0" />
            </div>
          </div>
        </div>
      )}

      {/* Modal Gerenciar Pasta */}
      {folderModal.show && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
          <div className="bg-zinc-900 border border-zinc-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
            <div className="px-6 py-6">
              <h3 className="text-xl font-bold text-white mb-2">{folderModal.mode === 'create' ? 'Nova Pasta' : 'Renomear Pasta'}</h3>
              <p className="text-zinc-400 text-sm mb-6">Digite um nome para organizar suas propostas.</p>
              <div>
                <label className="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-2">Nome da Pasta</label>
                <input
                  type="text"
                  value={folderModal.nome}
                  onChange={(e) => setFolderModal((f) => ({ ...f, nome: e.target.value }))}
                  onKeyDown={(e) => { if (e.key === 'Enter') confirmarPasta(); }}
                  className="w-full bg-zinc-800 border border-zinc-700 text-white focus:border-white transition-all px-4 py-3 text-sm rounded-xl outline-none"
                  placeholder="Ex: Campanhas de Maio"
                  autoFocus
                />
              </div>
            </div>
            <div className="px-6 py-4 bg-zinc-800/50 border-t border-zinc-800 flex justify-end gap-3">
              <button onClick={() => setFolderModal((f) => ({ ...f, show: false }))} className="px-4 py-2 text-zinc-400 hover:text-white font-medium transition-colors">Cancelar</button>
              <button onClick={confirmarPasta} className="px-6 py-2 bg-white text-black font-bold rounded-lg hover:bg-zinc-200 transition-colors">Salvar</button>
            </div>
          </div>
        </div>
      )}

      {/* Modal de Confirmação de Exclusão */}
      {deleteModal.show && (
        <div className="fixed inset-0 z-[1000] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
          <div className="bg-zinc-900 border border-zinc-800 rounded-2xl w-full max-w-sm overflow-hidden shadow-2xl">
            <div className="p-6 text-center">
              <div className="w-16 h-16 bg-red-900/20 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <AlertTriangle className="w-8 h-8" />
              </div>
              <h3 className="text-xl font-bold text-white mb-2">Confirmar Exclusão</h3>
              <p className="text-zinc-400 text-sm">{deleteModal.message}</p>
            </div>
            <div className="px-6 py-4 bg-zinc-800/50 border-t border-zinc-800 flex justify-center gap-3">
              <button onClick={() => setDeleteModal((m) => ({ ...m, show: false }))} className="px-4 py-2 text-zinc-400 hover:text-white font-medium transition-colors">Cancelar</button>
              <button onClick={confirmarExclusao} className="px-6 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition-colors">Excluir</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );

  function resumoParcelas(): string {
    if (fechamentoForm.pagamento_modo === 'avista') return 'Pagamento à vista: sem saldo parcelado.';
    const ultima = ultimaParcelaIso();
    return ultima ? `Última parcela prevista para ${new Date(ultima + 'T00:00:00').toLocaleDateString('pt-BR')}.` : '';
  }
};