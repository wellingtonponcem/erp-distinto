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

interface VariableItem {
  tag: string;
  descricao: string;
  exemplo: string;
  categoria: string;
}

const LISTA_VARIAVEIS: VariableItem[] = [
  // 📜 IDENTIFICAÇÃO DO CONTRATO
  { tag: '{{NUMERO_CONTRATO}}', descricao: 'Número / Código único gerado para o contrato', exemplo: '2026/830b', categoria: '📜 Identificação do Contrato' },
  { tag: '{{TITULO_CONTRATO}}', descricao: 'Título comercial do contrato', exemplo: 'Contrato de Prestação de Serviços - Casamento', categoria: '📜 Identificação do Contrato' },

  // 👤 DADOS DO CLIENTE / CONTRATANTE
  { tag: '{{CLIENTE_NOME}}', descricao: 'Nome completo do contratante ou casal', exemplo: 'Wellington Poncem & Jeane Nunes', categoria: '👤 Cliente / Contratante' },
  { tag: '{{CLIENTE_CPF_CNPJ}}', descricao: 'CPF ou CNPJ do contratante', exemplo: '070.523.506-88', categoria: '👤 Cliente / Contratante' },
  { tag: '{{CLIENTE_EMAIL}}', descricao: 'E-mail de contato e assinatura', exemplo: 'contato@cliente.com', categoria: '👤 Cliente / Contratante' },
  { tag: '{{CLIENTE_TELEFONE}}', descricao: 'Telefone ou WhatsApp', exemplo: '(27) 99999-8888', categoria: '👤 Cliente / Contratante' },
  { tag: '{{CLIENTE_ENDERECO}}', descricao: 'Endereço completo do contratante', exemplo: 'Rua das Flores, 123, Vitória-ES', categoria: '👤 Cliente / Contratante' },

  // 🏢 DADOS DA EMPRESA / CONTRATADA
  { tag: '{{EMPRESA_NOME}}', descricao: 'Razão social / Nome fantasia da contratada', exemplo: 'Distinto | Poncem Studio LTDA', categoria: '🏢 Empresa / Contratada' },
  { tag: '{{EMPRESA_CNPJ}}', descricao: 'CNPJ oficial da contratada', exemplo: '50.768.732/0001-63', categoria: '🏢 Empresa / Contratada' },
  { tag: '{{EMPRESA_ENDERECO}}', descricao: 'Endereço da sede da empresa', exemplo: 'Rod. do Sol nº 2780, Sala 1307, Vila Velha-ES', categoria: '🏢 Empresa / Contratada' },
  { tag: '{{EMPRESA_EMAIL}}', descricao: 'E-mail oficial da empresa', exemplo: 'contato@wedistinto.com', categoria: '🏢 Empresa / Contratada' },

  // 💑 DADOS DOS NOIVOS / CASAL
  { tag: '{{NOIVO_NOME}}', descricao: 'Nome completo do noivo', exemplo: 'Wellington Poncem', categoria: '💑 Noivos / Casal' },
  { tag: '{{NOIVA_NOME}}', descricao: 'Nome completo da noiva', exemplo: 'Jeane Nunes', categoria: '💑 Noivos / Casal' },
  { tag: '{{NOIVO_CPF}}', descricao: 'CPF do noivo', exemplo: '070.523.506-88', categoria: '💑 Noivos / Casal' },
  { tag: '{{NOIVA_CPF}}', descricao: 'CPF da noiva', exemplo: '123.456.789-00', categoria: '💑 Noivos / Casal' },
  { tag: '{{NOIVO_EMAIL}}', descricao: 'E-mail do noivo', exemplo: 'wellington@email.com', categoria: '💑 Noivos / Casal' },
  { tag: '{{NOIVA_EMAIL}}', descricao: 'E-mail da noiva', exemplo: 'jeane@email.com', categoria: '💑 Noivos / Casal' },
  { tag: '{{NOIVO_TELEFONE}}', descricao: 'Telefone do noivo', exemplo: '(27) 99999-8888', categoria: '💑 Noivos / Casal' },
  { tag: '{{NOIVA_TELEFONE}}', descricao: 'Telefone da noiva', exemplo: '(27) 88888-7777', categoria: '💑 Noivos / Casal' },

  // 💰 VALORES & PAGAMENTO
  { tag: '{{VALOR_TOTAL}}', descricao: 'Valor total do contrato formatado em R$', exemplo: 'R$ 12.500,00', categoria: '💰 Valores & Pagamento' },
  { tag: '{{CONDICOES_PAGAMENTO}}', descricao: 'Forma e condições de parcelamento', exemplo: 'Entrada de 20% + Saldo em até 6x no cartão/PIX', categoria: '💰 Valores & Pagamento' },
  { tag: '{{PARCELAS_DETALHE}}', descricao: 'Detalhamento das parcelas e vencimentos', exemplo: '6 parcelas de R$ 2.083,33', categoria: '💰 Valores & Pagamento' },

  // 📅 DATAS & LOCAIS
  { tag: '{{DATA_EVENTO}}', descricao: 'Data prevista para a cerimônia/evento', exemplo: '24 de dezembro de 2026', categoria: '📅 Datas & Locais' },
  { tag: '{{LOCAL_EVENTO}}', descricao: 'Local da cerimônia / recepção', exemplo: 'Sítio Recanto dos Sonhos, Vila Velha-ES', categoria: '📅 Datas & Locais' },
  { tag: '{{PREWEDDING_DATA_LOCAL}}', descricao: 'Data e local do ensaio Pre-Wedding', exemplo: 'A definir em comum acordo entre as partes', categoria: '📅 Datas & Locais' },
  { tag: '{{PRAZO_ENTREGA_PREVIAS}}', descricao: 'Prazo para entrega das prévias de foto', exemplo: '10 dias úteis após a seleção das fotos pelo casal', categoria: '📅 Datas & Locais' },
  { tag: '{{PRAZO_ENTREGA_FINAL}}', descricao: 'Prazo para entrega do material completo', exemplo: '60 dias úteis após o evento', categoria: '📅 Datas & Locais' },

  // 📜 CLÁUSULAS & ANEXOS
  { tag: '{{CLAUSULAS_PERSONALIZADAS}}', descricao: 'Cláusulas adicionais ou termos específicos', exemplo: 'Cláusula de confidencialidade e regras de voo com drone', categoria: '📜 Cláusulas & Anexos' },
  { tag: '{{ANEXO_ESCOPO}}', descricao: 'Detalhamento de itens do pacote (Anexo I)', exemplo: '2 Fotógrafos + 2 Cinegrafistas + Filme Teaser 3min + Galeria Digital', categoria: '📜 Cláusulas & Anexos' },
];

export const ModelosContratoView: React.FC = () => {
  const [modelos, setModelos] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [modeloAtivoId, setModeloAtivoId] = useState<string | null>(null);

  const [nomeModelo, setNomeModelo] = useState('');
  const [tipoModelo, setTipoModelo] = useState('casamento');
  const [isPadrao, setIsPadrao] = useState(false);
  const [salvando, setSalvando] = useState(false);

  const [buscaVariavel, setBuscaVariavel] = useState('');
  const [copiadoTag, setCopiadoTag] = useState<string | null>(null);
  
  // Modos de Exibição: 'visual' (WYSIWYG Docs), 'codigo' (HTML Direto), 'preview' (Pré-visualização)
  const [modoExibicao, setModoExibicao] = useState<'visual' | 'codigo' | 'preview'>('visual');
  const [codigoHtmlRaw, setCodigoHtmlRaw] = useState('');

  // Imagem Selecionada / Upload
  const [imagemSelecionada, setImagemSelecionada] = useState<HTMLImageElement | null>(null);
  const [arrastandoImagem, setArrastandoImagem] = useState(false);
  const [offsetX, setOffsetX] = useState(0);
  const [offsetY, setOffsetY] = useState(0);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const editorRef = useRef<HTMLDivElement>(null);

  const carregarModelos = async () => {
    setLoading(true);
    const res = await safeFetchJson('/api/comercial/modelos-contrato');
    if (res.ok && Array.isArray(res.data)) {
      setModelos(res.data);
      if (res.data.length > 0 && !modeloAtivoId) {
        carregarModeloNoEditor(res.data[0]);
      }
    } else {
      setModelos([]);
    }
    setLoading(false);
  };

  useEffect(() => {
    carregarModelos();
  }, []);

  // Restaurar conteúdo no editor visual ao voltar do modo código/preview
  const [htmlPendente, setHtmlPendente] = useState<string | null>(null);
  useEffect(() => {
    if (modoExibicao === 'visual' && htmlPendente !== null && editorRef.current) {
      editorRef.current.innerHTML = htmlPendente;
      setHtmlPendente(null);
      adicionarListenersImagens();
    }
  }, [modoExibicao, htmlPendente]);

  useEffect(() => {
    if (!editorRef.current || modoExibicao !== 'visual') return;
    const editor = editorRef.current;
    const maxHeight = 1123;
    const breakeClass = 'page-break-block';

    const checkAndInsertPageBreak = () => {
      const content = editor.innerHTML;
      const range = document.createRange();
      range.selectNodeContents(editor);
      const node = range.createContextualFragment(content);
      const clone = node.cloneNode(true) as HTMLElement;
      const images = clone.querySelectorAll('img');
      images.forEach((img: HTMLElement) => {
        img.style.maxWidth = 'none';
        img.style.display = 'block';
      });
      const breakelements = clone.querySelectorAll(`.${breakeClass}`) as NodeListOf<ChildNode>;
      const totalBreakeHeight = Array.from(breakelements).reduce((sum, el) => sum + (parseFloat(getComputedStyle(el as Element).height) || 40), 0);

      let scrollHeight = clone.scrollHeight - totalBreakeHeight;
      images.forEach((img: HTMLElement) => {
        scrollHeight += img.offsetHeight;
      });

      if (scrollHeight > maxHeight) {
        const lastChild = editor.lastChild;
        if (!lastChild || !((lastChild as Element).className || '').includes(breakeClass)) {
          const pageBreakHtml = `
            <div class="page-break-block" style="page-break-after: always; break-after: page; height: 40px; border-top: 2px dashed #a855f7; border-bottom: 2px dashed #a855f7; margin: 40px 0; background: #faf5ff; position: relative; font-size: 11px; font-weight: bold; text-align: center; color: #7e22ce; line-height: 40px; user-select: none;" contenteditable="false">
              ✂️ QUEBRA DE PÁGINA A4 — PÁGINA SEGUINTE (CLIQUE AQUI PARA CONTINUAR DIGITANDO)
            </div>
            <p><br /></p>
          `;
          const sel = window.getSelection();
          if (sel && sel.rangeCount > 0) {
            const currentRange = sel.getRangeAt(0);
            const lastNode = currentRange.endContainer;
            if (lastNode && lastNode.nodeType === Node.TEXT_NODE) {
              const text = lastNode.textContent || '';
              if (text.trim()) {
                currentRange.collapse(false);
                currentRange.insertNode(document.createRange().createContextualFragment(pageBreakHtml));
              }
            } else {
              currentRange.collapse(false);
              currentRange.insertNode(document.createRange().createContextualFragment(pageBreakHtml));
            }
          }
        }
      }
    };

    const handleKeyUp = () => {
      checkAndInsertPageBreak();
      if (editorRef.current) setCodigoHtmlRaw(editorRef.current.innerHTML);
    };

    editor.addEventListener('keyup', handleKeyUp);
    checkAndInsertPageBreak();

    return () => {
      editor.removeEventListener('keyup', handleKeyUp);
    };
  }, [modoExibicao, editorRef]);

  const carregarModeloNoEditor = (m: any) => {
    setModeloAtivoId(m.id);
    setNomeModelo(m.nome || '');
    setTipoModelo(m.tipo || 'casamento');
    setIsPadrao(Boolean(m.padrao));
    const htmlContent = m.conteudo_html || '';

    setCodigoHtmlRaw(htmlContent);

    if (editorRef.current) {
      editorRef.current.innerHTML = htmlContent;
      adicionarListenersImagens();
    }
  };

  const adicionarListenersImagens = () => {
    if (!editorRef.current) return;
    const imgs = editorRef.current.querySelectorAll('img');
    imgs.forEach((img) => {
      img.style.cursor = ' grabbing';
      img.style.userSelect = 'none';
      img.style.position = 'relative';
      img.style.userSelect = 'none';
      
      // Remove any existing drag handlers first
      img.onmousedown = null;
      img.onmousemove = null;
      img.onmouseup = null;
      
      img.onmousedown = (e) => {
        setArrastandoImagem(true);
        setOffsetX(e.clientX - img.offsetLeft);
        setOffsetY(e.clientY - img.offsetTop);
        e.preventDefault();
      };
      
      img.onmousemove = (e) => {
        if (!arrastandoImagem) return;
        e.preventDefault();
        const newX = e.clientX - offsetX;
        const newY = e.clientY - offsetY;
        img.style.left = `${newX}px`;
        img.style.top = `${newY}px`;
      };
      
      img.onmouseup = () => {
        setArrastandoImagem(false);
      };
    });
  };

  const handleAlternarModo = (novoModo: 'visual' | 'codigo' | 'preview') => {
    if (modoExibicao === 'visual' && editorRef.current) {
      const htmlAtual = editorRef.current.innerHTML;
      setCodigoHtmlRaw(htmlAtual);
      setHtmlPendente(htmlAtual);
    } else if (modoExibicao === 'codigo' && novoModo === 'visual') {
      setHtmlPendente(codigoHtmlRaw);
    } else if (modoExibicao === 'preview' && novoModo === 'visual') {
      setHtmlPendente(codigoHtmlRaw);
    }
    setModoExibicao(novoModo);
  };

  const handleNovoModelo = () => {
    setModeloAtivoId(null);
    setNomeModelo('Novo Modelo de Contrato');
    setTipoModelo('casamento');
    setIsPadrao(false);

    const initialTemplate = `
      <h2 style="text-align: center; text-transform: uppercase;">CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE {{TITULO_CONTRATO}}</h2>
      <p style="text-align: center; font-size: 12px; font-weight: bold; color: #666;">{{EMPRESA_NOME}} • CNPJ {{EMPRESA_CNPJ}}</p>
      <p style="text-align: center; font-weight: bold;">CONTRATO Nº {{NUMERO_CONTRATO}}</p>
      <hr />
      <p><strong>CONTRATADA:</strong> {{EMPRESA_NOME}}, inscrita no CNPJ/MF sob o nº {{EMPRESA_CNPJ}}, com sede em {{EMPRESA_ENDERECO}}.</p>
      <p><strong>CONTRATANTE:</strong> {{CLIENTE_NOME}}, inscrito(a) no CPF/CNPJ nº {{CLIENTE_CPF_CNPJ}}, e-mail {{CLIENTE_EMAIL}}.</p>

      <h3>CLÁUSULA PRIMEIRA — DO OBJETO</h3>
      <p>A CONTRATADA prestará serviços profissionais de cobertura fotográfica e/ou produção audiovisual para o evento dos CONTRATANTES a ser realizado em {{DATA_EVENTO}} no local {{LOCAL_EVENTO}}.</p>

      <h3>CLÁUSULA SEGUNDA — DO INVESTIMENTO</h3>
      <p>Pela prestação dos serviços objeto deste contrato, os CONTRATANTES pagarão à CONTRATADA o valor total de {{VALOR_TOTAL}}, nas seguintes condições: {{CONDICOES_PAGAMENTO}}.</p>
    `;

    setCodigoHtmlRaw(initialTemplate);
    if (editorRef.current) {
      editorRef.current.innerHTML = initialTemplate;
      adicionarListenersImagens();
    }
  };

  const handleSalvarModelo = async () => {
    if (!nomeModelo.trim()) {
      alert('Por favor, informe o nome do modelo.');
      return;
    }

    const htmlContent = modoExibicao === 'codigo'
      ? codigoHtmlRaw
      : (editorRef.current ? editorRef.current.innerHTML : codigoHtmlRaw);

    setSalvando(true);
    try {
      const isEdit = Boolean(modeloAtivoId);
      const url = '/api/comercial/modelos-contrato';
      const method = isEdit ? 'PUT' : 'POST';

      const res = await safeFetchJson(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id: modeloAtivoId,
          nome: nomeModelo,
          tipo: tipoModelo,
          conteudo_html: htmlContent,
          padrao: isPadrao,
        }),
      });

      if (res.ok) {
        alert('Modelo de contrato salvo com sucesso!');
        carregarModelos();
      } else {
        alert(res.data?.erro || 'Erro ao salvar modelo de contrato.');
      }
    } catch (e) {
      alert('Erro de conexão ao salvar modelo.');
    } finally {
      setSalvando(false);
    }
  };

  const handleExcluirModelo = async (id: string) => {
    if (!confirm('Deseja realmente excluir este modelo de contrato?')) return;
    try {
      const res = await safeFetchJson(`/api/comercial/modelos-contrato?id=${id}`, { method: 'DELETE' });
      if (res.ok) {
        setModeloAtivoId(null);
        carregarModelos();
      } else {
        alert(res.data?.erro || 'Erro ao excluir modelo.');
      }
    } catch (e) {
      alert('Erro ao excluir modelo.');
    }
  };

  // Funções de Formatação Rich-Text (Estilo Google Docs)
  const execCmd = (cmd: string, value: string = '') => {
    document.execCommand(cmd, false, value);
    if (editorRef.current) {
      editorRef.current.focus();
      setCodigoHtmlRaw(editorRef.current.innerHTML);
    }
  };

  const insererQuebraDePagina = () => {
    const pageBreakHtml = `
      <div class="page-break-block" style="page-break-after: always; break-after: page; height: 40px; border-top: 2px dashed #a855f7; border-bottom: 2px dashed #a855f7; margin: 40px 0; background: #faf5ff; position: relative; font-size: 11px; font-weight: bold; text-align: center; color: #7e22ce; line-height: 40px; user-select: none;" contenteditable="false">
        ✂️ QUEBRA DE PÁGINA A4 — PÁGINA SEGUINTE (CLIQUE AQUI PARA CONTINUAR DIGITANDO)
      </div>
      <p><br /></p>
    `;
    const selection = window.getSelection();
    if (selection.rangeCount > 0) {
      const range = selection.getRangeAt(0);
      range.deleteContents();
      range.insertNode(document.createRange().createContextualFragment(pageBreakHtml));
    }
    if (editorRef.current) {
      setCodigoHtmlRaw(editorRef.current.innerHTML);
    }
  };

  // Upload e Seleção de Imagem / Logo
  const handleUploadImagem = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (evt) => {
      const src = evt.target?.result as string;
      if (imagemSelecionada) {
        imagemSelecionada.src = src;
        setImagemSelecionada(null);
      } else {
        const imgHtml = `<img src="${src}" alt="Imagem do Contrato" style="max-width: 220px; height: auto; margin: 12px 0; position: relative;" />`;
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
          const range = selection.getRangeAt(0);
          range.deleteContents();
          range.insertNode(document.createRange().createContextualFragment(imgHtml));
        }
      }
      if (editorRef.current) {
        setCodigoHtmlRaw(editorRef.current.innerHTML);
      }
      setTimeout(adicionarListenersImagens, 100);
    };
    reader.readAsDataURL(file);
  };

  const insererTabela = () => {
    const tabelaHtml = `
      <table style="width: 100%; border-collapse: collapse; margin: 16px 0; font-size: 11px;">
        <thead>
          <tr style="background: #f3f4f6;">
            <th style="border: 1px solid #d1d5db; padding: 8px; text-align: left;">Item / Descrição</th>
            <th style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">Quantidade</th>
            <th style="border: 1px solid #d1d5db; padding: 8px; text-align: right;">Valor</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="border: 1px solid #d1d5db; padding: 8px;">Cobertura Fotográfica Completa</td>
            <td style="border: 1px solid #d1d5db; padding: 8px; text-align: center;">1</td>
            <td style="border: 1px solid #d1d5db; padding: 8px; text-align: right;">R$ {{VALOR_TOTAL}}</td>
          </tr>
        </tbody>
      </table>
      <p><br /></p>
    `;
    const selection = window.getSelection();
    if (selection.rangeCount > 0) {
      const range = selection.getRangeAt(0);
      range.deleteContents();
      range.insertNode(document.createRange().createContextualFragment(tabelaHtml));
    }
    if (editorRef.current) {
      setCodigoHtmlRaw(editorRef.current.innerHTML);
    }
  };

  const insererVariavelNoEditor = (tag: string) => {
    if (modoExibicao === 'codigo') {
      setCodigoHtmlRaw((prev) => prev + ' ' + tag);
    } else if (editorRef.current) {
      editorRef.current.focus();
      const selection = window.getSelection();
      if (selection.rangeCount > 0) {
        const range = selection.getRangeAt(0);
        range.deleteContents();
        const fragment = document.createRange().createContextualFragment(
          `<span style="display:inline; font-family:monospace; background:#f3e8ff; color:#7e22ce; padding:1px 4px; border-radius:3px; font-size:10px;" contenteditable="false">${tag}</span>`
        );
        range.insertNode(fragment);
      }
      setCodigoHtmlRaw(editorRef.current.innerHTML);
    }
  };

  const copiarTag = (tag: string) => {
    navigator.clipboard.writeText(tag);
    setCopiadoTag(tag);
    setTimeout(() => setCopiadoTag(null), 2000);
  };

  const variaveisFiltradas = LISTA_VARIAVEIS.filter((v) => {
    if (!buscaVariavel.trim()) return true;
    const termo = buscaVariavel.toLowerCase();
    return v.tag.toLowerCase().includes(termo) || v.descricao.toLowerCase().includes(termo) || v.categoria.toLowerCase().includes(termo);
  });

  return (
    <div className="space-y-6 font-sans text-gray-900 bg-gray-50 min-h-screen">
      {/* Input oculto para upload de imagem */}
      <input
        type="file"
        ref={fileInputRef}
        onChange={handleUploadImagem}
        accept="image/*"
        className="hidden"
      />

      {/* Header Superior */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-gray-900 tracking-tight flex items-center space-x-2">
            <span className="material-symbols-outlined text-purple-600">article</span>
            <span>Editor de Modelos de Contrato</span>
          </h1>
          <p className="text-xs text-gray-500 mt-0.5">
            Editor estilo Google Docs com Régua, Divisão de Páginas A4, Edição de Código HTML e Guia de Variáveis
          </p>
        </div>

        <div className="flex items-center space-x-2">
          <button
            onClick={handleNovoModelo}
            className="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold rounded-xl text-xs transition flex items-center space-x-1.5"
          >
            <span className="material-symbols-outlined text-sm leading-none">add</span>
            <span>Novo Modelo</span>
          </button>
          <button
            onClick={handleSalvarModelo}
            disabled={salvando}
            className="px-5 py-2 bg-black hover:bg-gray-800 text-white font-bold rounded-xl text-xs transition flex items-center space-x-1.5 shadow-md"
          >
            <span className="material-symbols-outlined text-sm leading-none">save</span>
            <span>{salvando ? 'Salvando...' : 'Salvar Modelo'}</span>
          </button>
        </div>
      </div>

      {/* Seletor de Modelos Existentes */}
      <div className="bg-white border border-gray-200/80 p-4 rounded-2xl shadow-2xs flex flex-col md:flex-row items-center justify-between gap-4">
        <div className="flex items-center space-x-3 w-full md:w-auto">
          <span className="text-xs font-bold uppercase text-gray-400">Modelo Selecionado:</span>
          <select
            value={modeloAtivoId || ''}
            onChange={(e) => {
              const sel = modelos.find((m) => m.id === e.target.value);
              if (sel) carregarModeloNoEditor(sel);
            }}
            className="px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-900 focus:outline-none focus:ring-2 focus:ring-black min-w-[280px]"
          >
            {modelos.map((m) => (
              <option key={m.id} value={m.id}>
                {m.nome} {m.padrao ? ' (Padrão do Sistema ⭐)' : ''}
              </option>
            ))}
          </select>
        </div>

        <div className="flex items-center space-x-3 w-full md:w-auto">
          <input
            type="text"
            value={nomeModelo}
            onChange={(e) => setNomeModelo(e.target.value)}
            placeholder="Nome do Modelo (ex: Modelo Casamento Luxe)"
            className="px-3 py-2 border border-gray-200 rounded-xl text-xs text-gray-900 font-bold focus:outline-none focus:ring-2 focus:ring-black flex-1 md:w-64"
          />

          <label className="flex items-center space-x-1.5 cursor-pointer text-xs font-bold text-gray-700">
            <input
              type="checkbox"
              checked={isPadrao}
              onChange={(e) => setIsPadrao(e.target.checked)}
              className="rounded text-black focus:ring-black"
            />
            <span>Definir como Padrão ⭐</span>
          </label>

          {modeloAtivoId && (
            <button
              onClick={() => handleExcluirModelo(modeloAtivoId)}
              className="p-2 text-red-600 hover:bg-red-50 rounded-xl transition"
              title="Excluir este modelo"
            >
              <span className="material-symbols-outlined leading-none">delete</span>
            </button>
          )}
        </div>
      </div>

      {/* Grid Principal: Barra Editor Docs (Esquerda/Centro) + Guia de Variáveis (Direita) */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {/* Editor Estilo Google Docs (8 Colunas) */}
        <div className="lg:col-span-8 space-y-3">
          {/* Barra de Ferramentas Estilo Google Docs */}
          <div className="bg-white border border-gray-200 rounded-2xl p-2.5 shadow-2xs flex flex-wrap items-center gap-1.5 text-gray-700">
            {/* Alternar Modos: Visual / Código HTML / Preview */}
            <div className="flex items-center space-x-1 bg-gray-100 p-1 rounded-xl">
              <button
                onClick={() => handleAlternarModo('visual')}
                className={`px-3 py-1 rounded-lg text-xs font-bold transition flex items-center space-x-1 ${
                  modoExibicao === 'visual' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-600 hover:text-gray-900'
                }`}
              >
                <span className="material-symbols-outlined text-sm leading-none">edit_document</span>
                <span>Editor Visual</span>
              </button>
              <button
                onClick={() => handleAlternarModo('codigo')}
                className={`px-3 py-1 rounded-lg text-xs font-bold transition flex items-center space-x-1 ${
                  modoExibicao === 'codigo' ? 'bg-purple-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900'
                }`}
              >
                <span className="material-symbols-outlined text-sm leading-none">code</span>
                <span>Ver / Editar HTML</span>
              </button>
              <button
                onClick={() => handleAlternarModo('preview')}
                className={`px-3 py-1 rounded-lg text-xs font-bold transition flex items-center space-x-1 ${
                  modoExibicao === 'preview' ? 'bg-black text-white shadow-xs' : 'text-gray-600 hover:text-gray-900'
                }`}
              >
                <span className="material-symbols-outlined text-sm leading-none">visibility</span>
                <span>Pré-visualizar</span>
              </button>
            </div>

            <div className="w-px h-5 bg-gray-200 my-auto mx-1" />

            {/* Controles de Formatação Visual */}
            {modoExibicao === 'visual' && (
              <>
                <button onClick={() => execCmd('undo')} className="p-1.5 hover:bg-gray-100 rounded-lg text-xs font-bold" title="Desfazer">
                  <span className="material-symbols-outlined text-base leading-none">undo</span>
                </button>
                <button onClick={() => execCmd('redo')} className="p-1.5 hover:bg-gray-100 rounded-lg text-xs font-bold" title="Refazer">
                  <span className="material-symbols-outlined text-base leading-none">redo</span>
                </button>
                <div className="w-px h-5 bg-gray-200 my-auto mx-1" />

                <select
                  onChange={(e) => execCmd('formatBlock', e.target.value)}
                  className="px-2 py-1 bg-gray-50 border border-gray-200 rounded-lg text-xs font-bold"
                >
                  <option value="p">Texto Normal</option>
                  <option value="h1">Título 1 (H1)</option>
                  <option value="h2">Título 2 (H2)</option>
                  <option value="h3">Título 3 (H3)</option>
                  <option value="span">Texto Inline (Span)</option>
                </select>

                <div className="w-px h-5 bg-gray-200 my-auto mx-1" />

                <button onClick={() => execCmd('bold')} className="p-1.5 hover:bg-gray-100 rounded-lg font-bold text-xs" title="Negrito">
                  <strong>B</strong>
                </button>
                <button onClick={() => execCmd('italic')} className="p-1.5 hover:bg-gray-100 rounded-lg italic text-xs" title="Itálico">
                  <em>I</em>
                </button>
                <button onClick={() => execCmd('underline')} className="p-1.5 hover:bg-gray-100 rounded-lg underline text-xs" title="Sublinhado">
                  <u>U</u>
                </button>

                <div className="w-px h-5 bg-gray-200 my-auto mx-1" />

                <button onClick={() => execCmd('justifyLeft')} className="p-1.5 hover:bg-gray-100 rounded-lg text-xs" title="Esquerda">
                  <span className="material-symbols-outlined text-base leading-none">format_align_left</span>
                </button>
                <button onClick={() => execCmd('justifyCenter')} className="p-1.5 hover:bg-gray-100 rounded-lg text-xs" title="Centralizado">
                  <span className="material-symbols-outlined text-base leading-none">format_align_center</span>
                </button>
                <button onClick={() => execCmd('justifyRight')} className="p-1.5 hover:bg-gray-100 rounded-lg text-xs" title="Direita">
                  <span className="material-symbols-outlined text-base leading-none">format_align_right</span>
                </button>
                <button onClick={() => execCmd('justifyFull')} className="p-1.5 hover:bg-gray-100 rounded-lg text-xs" title="Justificado">
                  <span className="material-symbols-outlined text-base leading-none">format_align_justify</span>
                </button>

                <div className="w-px h-5 bg-gray-200 my-auto mx-1" />

                {/* Upload Imagem / Logo */}
                <button
                  onClick={() => fileInputRef.current?.click()}
                  className="px-2 py-1 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 rounded-lg text-xs font-bold transition flex items-center space-x-1"
                  title="Upload de Logo ou Imagem"
                >
                  <span className="material-symbols-outlined text-sm leading-none">image</span>
                  <span>{imagemSelecionada ? 'Trocar Imagem' : 'Inserir Logo'}</span>
                </button>

                {/* Inserir Tabela */}
                <button
                  onClick={insererTabela}
                  className="px-2 py-1 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-xs font-bold transition flex items-center space-x-1"
                  title="Inserir Tabela de Serviços"
                >
                  <span className="material-symbols-outlined text-sm leading-none">table_chart</span>
                  <span>Tabela</span>
                </button>

                {/* Quebra de Página A4 */}
                <button
                  onClick={insererQuebraDePagina}
                  className="px-2 py-1 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 rounded-lg text-xs font-bold transition flex items-center space-x-1"
                  title="Inserir Quebra de Página A4"
                >
                  <span className="material-symbols-outlined text-sm leading-none">content_cut</span>
                  <span>➕ Quebra de Página</span>
                </button>
              </>
            )}
          </div>

          {/* MODO 1: EDITOR VISUAL COM RÉGUA DO GOOGLE DOCS E FOLHAS A4 SEPARADAS */}
          {modoExibicao === 'visual' && (
            <div className="bg-gray-300 p-6 rounded-2xl overflow-y-auto flex flex-col items-center shadow-inner relative">
              {/* Régua Horizontal do Google Docs (A4 21cm) */}
              <div className="w-full max-w-[794px] bg-white border-b border-gray-300 h-6 flex items-center px-12 relative text-[9px] font-mono text-gray-500 select-none shadow-xs rounded-t-lg mb-2">
                <div className="flex justify-between w-full">
                  <span>0 cm</span>
                  <span>2 cm</span>
                  <span>4 cm</span>
                  <span>6 cm</span>
                  <span>8 cm</span>
                  <span>10 cm</span>
                  <span>12 cm</span>
                  <span>14 cm</span>
                  <span>16 cm</span>
                  <span>18 cm</span>
                  <span>21 cm (A4)</span>
                </div>
              </div>

              {/* Folha A4 Principal do Documento */}
              <div
                ref={editorRef}
                contentEditable
                suppressContentEditableWarning
                onKeyUp={() => {
                  adicionarListenersImagens();
                  if (editorRef.current) setCodigoHtmlRaw(editorRef.current.innerHTML);
                }}
                className="bg-white border border-gray-300 rounded-sm shadow-2xl p-12 min-h-[1123px] w-full max-w-[794px] text-gray-900 font-sans text-xs leading-relaxed focus:outline-none focus:ring-2 focus:ring-purple-500 selection:bg-purple-50"
                style={{
                  minHeight: '1123px',
                  boxShadow: '0 12px 48px rgba(0,0,0,0.2)',
                  overflowY: 'auto',
                }}
              />
            </div>
          )}

          {/* MODO 2: EDITOR DE CÓDIGO HTML DIRETO */}
          {modoExibicao === 'codigo' && (
            <div className="bg-gray-900 rounded-2xl p-4 shadow-2xl space-y-2">
              <div className="flex items-center justify-between text-xs text-gray-400 font-mono pb-2 border-b border-gray-800">
                <span className="flex items-center space-x-2">
                  <span className="material-symbols-outlined text-purple-400 text-sm">code</span>
                  <span>Código Fonte HTML do Modelo de Contrato</span>
                </span>
                <span>Edite as tags e o CSS diretamente</span>
              </div>
              <textarea
                value={codigoHtmlRaw}
                onChange={(e) => setCodigoHtmlRaw(e.target.value)}
                rows={24}
                className="w-full bg-gray-950 text-emerald-400 font-mono text-xs p-4 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 leading-relaxed font-mono"
                placeholder="Insira ou edite o código HTML do contrato..."
              />
            </div>
          )}

          {/* MODO 3: PRÉ-VISUALIZAÇÃO COMPLETA */}
          {modoExibicao === 'preview' && (
            <div className="bg-gray-200 p-8 rounded-2xl overflow-y-auto max-h-[78vh] flex justify-center shadow-inner">
              <div
                className="bg-white border border-gray-300 rounded-sm shadow-2xl p-12 min-h-[1123px] w-full max-w-[794px] text-gray-900 font-sans text-xs leading-relaxed"
                dangerouslySetInnerHTML={{ __html: codigoHtmlRaw }}
              />
            </div>
          )}
        </div>

        {/* Guia de Variáveis Lateral (4 Colunas) */}
        <div className="lg:col-span-4 bg-white border border-gray-200/80 rounded-2xl p-5 shadow-2xs space-y-4 max-h-[85vh] overflow-y-auto">
          <div>
            <h3 className="font-extrabold text-gray-900 text-sm flex items-center space-x-1.5">
              <span className="material-symbols-outlined text-purple-600">code</span>
              <span>Guia de Variáveis Dinâmicas</span>
            </h3>
            <p className="text-[11px] text-gray-500 mt-0.5">
              Clique em <strong>+ Inserir</strong> para adicionar a variável diretamente no ponto do cursor!
            </p>
          </div>

          {/* Busca de Variáveis */}
          <div className="relative">
            <span className="material-symbols-outlined absolute left-3 top-2.5 text-gray-400 text-sm leading-none">
              search
            </span>
            <input
              type="text"
              value={buscaVariavel}
              onChange={(e) => setBuscaVariavel(e.target.value)}
              placeholder="Buscar variável (ex: {{NUMERO_CONTRATO}})..."
              className="w-full pl-9 pr-3 py-1.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-black font-sans"
            />
          </div>

          {/* Lista de Variáveis por Categoria */}
          <div className="space-y-3">
            {variaveisFiltradas.map((v) => (
              <div
                key={v.tag}
                className="bg-gray-50 border border-gray-200/80 p-3 rounded-xl hover:border-purple-300 hover:bg-purple-50/40 transition space-y-1.5 group"
              >
                <div className="flex items-center justify-between">
                  <span className="font-mono font-bold text-purple-700 text-xs bg-purple-100 px-2 py-0.5 rounded-lg border border-purple-200">
                    {v.tag}
                  </span>
                  <div className="flex items-center space-x-1">
                    <button
                      onClick={() => copiarTag(v.tag)}
                      className="px-2 py-1 bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-[10px] font-bold transition flex items-center space-x-1"
                      title="Copiar Código"
                    >
                      <span className="material-symbols-outlined text-xs leading-none">
                        {copiadoTag === v.tag ? 'check' : 'content_copy'}
                      </span>
                      <span>{copiadoTag === v.tag ? 'Copiado!' : 'Copiar'}</span>
                    </button>
                    <button
                      onClick={() => insererVariavelNoEditor(v.tag)}
                      className="px-2 py-1 bg-black hover:bg-gray-800 text-white rounded-lg text-[10px] font-bold transition flex items-center space-x-1"
                      title="Inserir no Editor"
                    >
                      <span className="material-symbols-outlined text-xs leading-none">add</span>
                      <span>Inserir</span>
                    </button>
                  </div>
                </div>

                <p className="text-xs text-gray-800 font-medium">{v.descricao}</p>
                <p className="text-[10px] font-mono text-gray-400">Exemplo: {v.exemplo}</p>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};
