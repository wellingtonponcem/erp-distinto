/**
 * Port TS de includes/propostas/template-casamento.php (19 páginas/slides).
 */

import { SlideCtx, raizUrl, esc, number_format, mbUpper, itensParaArray } from '@/lib/propostas/common';

/** Emula is_numeric($v). */
function isNumeric(v: any): boolean {
  if (v === null || v === undefined) return false;
  const s = String(v).trim();
  if (s === '') return false;
  return isFinite(Number(v));
}

/** Emula empty($x). */
function vazio(x: any): boolean {
  return !x || (Array.isArray(x) && x.length === 0) || x === '';
}

/** Emula nl2br($s). */
function nl2br(s: any): string {
  return String(s).replace(/\r\n|\r|\n/g, '<br />');
}

/** Port fiel de valorMonetarioCasamento() do template-casamento.php. */
function valorMonetarioCasamento(valor: any, fallback = 0): number {
  if (valor === null || valor === '') {
    return fallback;
  }

  if (isNumeric(valor)) {
    const numero = Number(valor);
    return numero > 0 ? numero : fallback;
  }

  let normalizado = String(valor).replace(/R\$/g, '').replace(/ /g, '');
  if (normalizado.includes(',') && normalizado.includes('.')) {
    normalizado = normalizado.replace(/\./g, '');
    normalizado = normalizado.replace(/,/g, '.');
  } else if (normalizado.includes(',')) {
    normalizado = normalizado.replace(/,/g, '.');
  }

  const numero = isNumeric(normalizado) ? Number(normalizado) : 0;
  return numero > 0 ? numero : fallback;
}

/** Port fiel de fmt() do template-casamento.php. */
function fmt(valor: any): string {
  if (vazio(valor)) return 'Sob consulta';
  if (isNumeric(valor)) return 'R$ ' + number_format(valor, 2);
  return String(valor);
}

/** Port fiel de renderItensPersonalizadosCasamento() do template-casamento.php (retorna string). */
function renderItensPersonalizadosCasamento(itens: any): string {
  let out = '';
  for (const item of itens ?? []) {
    if (vazio(item && item.nome) || (item && (item.incluido ?? '1')) === '0') {
      continue;
    }
    const descricao = !vazio(item && item.descricao) ? ': ' + item.descricao : '';
    out += '<li style="margin-bottom: 10px; position: relative; padding-left: 20px;">';
    out += '<span style="position: absolute; left: 0; color: #1a1a1a;">•</span>';
    out += '<strong>' + esc(item.nome) + '</strong>' + esc(descricao);
    out += '</li>';
  }
  return out;
}

export function render(ctx: SlideCtx): string {
  const { proposta, dados: d, tipo, cliente, empresa, mesNome, ano, categoriaProjeto, slug } = ctx;

  const nomeNoivo = d.nome_noivo ?? '';
  const nomeNoiva = d.nome_noiva ?? '';
  const nomeCasal = (nomeNoivo && nomeNoiva) ? `${nomeNoivo} & ${nomeNoiva}` : proposta.cliente_nome;
  const primeiroNomeNoiva = nomeNoiva.trim().split(' ')[0] || '';
  const primeiroNomeNoivo = nomeNoivo.trim().split(' ')[0] || '';
  const saudacaoCasal = "Olá, " + ((primeiroNomeNoivo && primeiroNomeNoiva) ? `${primeiroNomeNoivo} & ${primeiroNomeNoiva}` : proposta.cliente_nome) + "!";

  const mensagemPessoalPadrao = 'A gente sabe que fotografia é muito mais do que só apertar um botão. Nosso trabalho é capturar o que vocês sentem um pelo outro, de um jeito que pareça real e sem poses forçadas.';
  let mensagemPessoal = String(d.mensagem_pessoal ?? '').trim();
  if (mensagemPessoal === '') {
    mensagemPessoal = mensagemPessoalPadrao;
  }
  const prazoPrevias = d.prazo_previas ?? '48 horas';
  const prazoFinal = d.prazo_final ?? '60 dias úteis';
  const validadeProposta = d.validade_proposta ?? '7';
  const instagramHandle = d.instagram_handle ?? '@distintowedding';
  const emailContato = d.email_contato ?? 'contato@wedistinto.com';
  const whatsappNumero = d.whatsapp_numero ?? '+55 27 9 8858-6935';

  const nomeNoivaSimples = nomeNoiva.trim().split(' ')[0] || '';
  const nomeNoivoSimples = nomeNoivo.trim().split(' ')[0] || '';
  const nomesWA = (nomeNoivaSimples && nomeNoivoSimples) ? `${nomeNoivaSimples} e ${nomeNoivoSimples}` : nomeCasal;
  const mensagemWA = ctx.mensagemWA ?? `Olá Wellington! Ficamos encantados com a proposta do nosso casamento (${nomesWA}). Gostaríamos de conversar para alinhar os detalhes e dar o próximo passo! ✨`;

  let depoimento01Texto = 'Foi a melhor escolha que fizemos. Eles capturaram a essência do nosso dia de uma forma que nunca imaginamos.';
  let depoimento01Autor = 'Fernanda & Thiago';
  let depoimento02Texto = 'A sensibilidade da equipe é indescritível. Cada vez que vemos o vídeo, nos emocionamos como se estivéssemos lá de novo.';
  let depoimento02Autor = 'Mariana & Lucas';
  if (d.depoimento01 && d.depoimento01.texto) {
    depoimento01Texto = d.depoimento01.texto;
    depoimento01Autor = d.depoimento01.autor || depoimento01Autor;
  }
  if (d.depoimento02 && d.depoimento02.texto) {
    depoimento02Texto = d.depoimento02.texto;
    depoimento02Autor = d.depoimento02.autor || depoimento02Autor;
  }

  const itensHeritage = d.itens_heritage ?? `Cobertura Documental Completa: Presença ilimitada no evento.
O Álbum Heritage: Álbum luxo panorâmico 25x30cm.
Réplicas para a Família: 02 Mini Álbuns réplicas.
Produção Cinematográfica 4K: Filme completo (8 a 12 min).
Short Film & Teasers: Vídeos curtos para redes sociais.
Uso de Drone Profissional: Imagens aéreas cinematográficas.
Ecossistema Digital: Galeria online vitalícia.`;
  const itensCinematic = d.itens_cinematic ?? `Cobertura Cinematográfica 8h: Foco narrativo e estético.
Sessão Engagement (Pré-Wedding): Ensaio externo com fotos e vídeo.
Short Film: Filme de 4 a 6 minutos.
Social Content Kit: Material otimizado para Instagram.
Making Of Completo: Registro dos preparativos do casal.
Bônus: Pendrive de luxo com arquivos em alta resolução.`;
  const itensEssencial = d.itens_essencial ?? `Cobertura Fotográfica 6h: Foco no essencial do evento.
Galeria Online: Entrega digital em alta resolução.
Edição Especial: Curadoria de fotos com tratamento Distinto.
Entrega em até 45 dias.`;

  const precoUnicoAtivo = false;
  const precoUnicoTitulo = String(d.preco_unico_titulo ?? 'Proposta Consolidada').trim() || 'Proposta Consolidada';
  const precoUnicoValor = d.preco_unico_valor ?? '';
  const precoUnicoItens = itensParaArray(d.preco_unico_itens ?? '');
  const atualizacoesVersao = itensParaArray(d.atualizacoes_versao ?? '');
  const andamentoProposta = itensParaArray(d.andamento_proposta ?? '');
  const mostrarAndamentoCliente = !vazio(d.mostrar_andamento_cliente);
  const versaoProposta = String(d.versao_proposta ?? '').trim();
  const itensPersonalizados: any = {
    heritage: (d.itens_personalizados && d.itens_personalizados.heritage) ?? [],
    cinematic: (d.itens_personalizados && d.itens_personalizados.cinematic) ?? [],
    essencial: (d.itens_personalizados && d.itens_personalizados.essencial) ?? [],
  };

  const condHC_slide = d.condicoes_heritage_cinematic ?? 'Entrada de 20% + Saldo parcelado em até 6x (dependendo do pacote selecionado)';
  const condE_slide = d.condicoes_essencial ?? 'Entrada de 25% + Saldo parcelado em até 5x (dependendo do pacote selecionado)';
  const clausula_slide = d.condicoes_reserva ?? 'A reserva da data é oficializada mediante a assinatura do contrato e o pagamento do sinal (entrada), que pode ser de 20% ou 25% do valor do pacote escolhido.';

  const pHeritage = isNumeric(d.valor_heritage) ? Number(d.valor_heritage) : 7900;
  const pCinematic = isNumeric(d.valor_cinematic) ? Number(d.valor_cinematic) : 4500;
  const pEssencial = isNumeric(d.valor_essencial) ? Number(d.valor_essencial) : 2800;
  const pBoudoir = isNumeric(d.valor_boudoir) ? Number(d.valor_boudoir) : 800;
  const pPrewedding = isNumeric(d.valor_prewedding) ? Number(d.valor_prewedding) : 1200;
  const condHC = d.condicoes_heritage_cinematic ?? 'Entrada de 20% + saldo parcelado em até 6x';
  const condE = d.condicoes_essencial ?? 'Entrada de 25% + saldo parcelado em até 5x';
  const clausula = d.condicoes_reserva ?? 'A reserva da data é oficializada mediante a assinatura do contrato e o pagamento do sinal (entrada). Oferecemos flexibilidade para que o saldo seja quitado de forma equilibrada até a data do evento.';
  const hItem1 = (String(itensHeritage).trim().split('\n')[0] ?? 'Cobertura Documental Completa').trim();
  const cItem1 = (String(itensCinematic).trim().split('\n')[0] ?? 'Cobertura Cinematográfica 8h').trim();
  const eItem1 = (String(itensEssencial).trim().split('\n')[0] ?? 'Cobertura Fotográfica 6h').trim();

  const waClean = String(whatsappNumero).replace(/[ +-]/g, '');
  const waLink = 'https://wa.me/' + waClean + '?text=' + encodeURIComponent(mensagemWA);

  const hasUpgradesH = ((d.include_boudoir_heritage ?? d.include_boudoir ?? false) !== false) ||
    ((d.include_prewedding_heritage ?? d.include_prewedding ?? false) !== false);
  const hasUpgradesC = ((d.include_boudoir_cinematic ?? d.include_boudoir ?? false) !== false) ||
    ((d.include_prewedding_cinematic ?? d.include_prewedding ?? false) !== false);
  const hasUpgradesE = ((d.include_boudoir_essencial ?? d.include_boudoir ?? false) !== false) ||
    ((d.include_prewedding_essencial ?? d.include_prewedding ?? false) !== false);

  const linhasH = String(itensHeritage).trim().split('\n');
  const linhasC = String(itensCinematic).trim().split('\n');
  const linhasE = String(itensEssencial).trim().split('\n');

  const dep01 = ctx.depoimentos?.[0] ?? { texto: 'Foi a melhor escolha que fizemos. Eles capturaram a essência do nosso dia de uma forma que nunca imaginamos.', autor: 'Fernanda & Thiago' };
  const dep02 = ctx.depoimentos?.[1] ?? { texto: 'A sensibilidade da equipe é indescritível. Cada vez que vemos o vídeo, nos emocionamos como se estivéssemos lá de novo.', autor: 'Mariana & Lucas' };

  const parts: string[] = [];

  parts.push(`<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Inter:wght@100..900&family=Dancing+Script:wght@400..700&family=Montserrat:wght@100..900&display=swap"
    rel="stylesheet">

<style>
    :root {
        --wedding-gold: #c5a880;
        --wedding-dark: #1a1a1a;
        --wedding-bg: #fafafa;
        --wedding-serif: "Playfair Display", serif;
        --wedding-sans: "Inter", sans-serif;
        --wedding-script: "Dancing Script", cursive;
        --wedding-montserrat: "Montserrat", sans-serif;
    }

    body {
        background: #111;
    }

    /* Resetar estilos globais para este template */
    html, body {
        height: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
    }

    /* Forçar o wrapper global a ser o único container de scroll */
    .type-casamento .proposal-wrapper {
        height: 100vh !important;
        overflow-y: scroll !important;
        scroll-snap-type: y proximity !important;
        scroll-behavior: smooth !important;
        -webkit-overflow-scrolling: touch;
    }

    /* A div interna não deve ter scroll próprio */
    .wedding-proposal {
        height: auto !important;
        overflow: visible !important;
        background: var(--wedding-bg);
        color: var(--wedding-dark);
        font-family: var(--wedding-sans);
    }

    .slide {
        height: 100vh !important;
        width: 100%;
        scroll-snap-align: start !important;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 80px;
        box-sizing: border-box;
    }

    /* Utilitários */
    .text-serif {
        font-family: var(--wedding-serif);
    }

    .text-gold {
        color: var(--wedding-gold);
    }

    .uppercase {
        text-transform: uppercase;
        letter-spacing: 0.2em;
    }

    .italic {
        font-style: italic;
    }

    h1 {
        font-size: 5rem;
        line-height: 1;
        margin: 0;
    }

    h2 {
        font-size: 3.5rem;
        line-height: 1.1;
        margin: 0;
    }

    h3 {
        font-size: 1.5rem;
        letter-spacing: 0.1em;
        font-weight: 300;
    }

    p {
        font-size: 1.1rem;
        line-height: 1.8;
        color: #444;
    }

    .line {
        width: 60px;
        height: 1px;
        background: var(--wedding-gold);
        margin: 30px 0;
    }

    .line-center {
        margin-left: auto;
        margin-right: auto;
    }

    /* Estilos Específicos */
    .bg-dark {
        background: #0a0a0a;
        color: white;
    }

    .bg-dark p {
        color: #888;
    }

    .img-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 0;
        opacity: 0.6;
        pointer-events: none;
        -webkit-user-drag: none;
        user-select: none;
    }

    /* Proteção Geral de Imagens */
    img {
        -webkit-user-drag: none;
        user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }

    .content-overlay {
        position: relative;
        z-index: 10;
        max-width: 900px;
    }

    .center {
        text-align: center;
        align-items: center;
    }

    /* Animações de Revelação */
    .reveal-item {
        opacity: 0;
        transform: translateY(30px);
        transition: all 1s cubic-bezier(0.21, 1, 0.36, 1);
    }

    .reveal-item.active {
        opacity: 1;
        transform: translateY(0);
    }

    /* Grid de Pacotes */
    .package-card {
        background: white;
        padding: 60px;
        border: 1px solid #eee;
        transition: all 0.5s ease;
    }

    .package-card:hover {
        border-color: var(--wedding-gold);
        transform: translateY(-10px);
    }

    .price-tag {
        font-family: var(--wedding-serif);
        font-size: 2.5rem;
        color: var(--wedding-gold);
        margin-top: 20px;
    }

    .badge {
        display: inline-block;
        padding: 4px 12px;
        background: var(--wedding-gold);
        color: white;
        font-size: 10px;
        font-weight: 800;
        border-radius: 4px;
        margin-bottom: 15px;
    }

    @media (max-width: 768px) {
        .slide {
            padding: 40px 20px;
        }

        h1 {
            font-size: 3rem;
        }

        h2 {
            font-size: 2.2rem;
        }

        /* Ajustes Capa Mobile */
        .capa-content {
            padding: 5vh 6vw !important;
            justify-content: flex-start !important;
            gap: 15px !important;
        }

        .capa-titulo {
            font-size: 3.5rem !important;
        }

        .capa-subtitulo {
            font-size: 0.8rem !important;
            letter-spacing: 0.25em !important;
            margin-top: 0px !important;
        }

        .capa-bottom-box {
            text-align: center !important;
            max-width: 100% !important;
            margin-top: 10px !important;
        }

        .capa-casal {
            font-size: 1.8rem !important;
            margin-bottom: 5px !important;
        }

        .capa-desc {
            font-size: 0.95rem !important;
            margin-bottom: 10px !important;
            max-width: 100% !important;
            line-height: 1.4 !important;
        }

        .capa-line {
            margin: 0 auto 15px auto !important;
        }

        /* Ajustes Boas-vindas Mobile */
        .boas-vindas-slide {
            flex-direction: column !important;
            height: auto !important;
            min-height: 100vh;
        }

        .boas-vindas-img-col {
            flex: none !important;
            height: 45vh !important;
            width: 100% !important;
            padding-right: 0 !important;
            justify-content: center !important;
        }

        .boas-vindas-img-decor {
            width: 30px !important;
        }

        .boas-vindas-text-col {
            flex: none !important;
            padding: 50px 30px !important;
            height: auto !important;
            text-align: left !important;
        }

        .boas-vindas-titulo {
            font-size: 2.2rem !important;
            line-height: 1.1 !important;
            margin-bottom: 25px !important;
        }

        .boas-vindas-logo {
            display: none !important;
        }

        /* Ajustes Manifesto Mobile */
        .manifesto-slide {
            flex-direction: column !important;
            height: auto !important;
            min-height: 100vh;
        }

        .manifesto-img-col {
            flex: none !important;
            height: 40vh !important;
            width: 100% !important;
        }

        .manifesto-text-col {
            flex: none !important;
            padding: 40px 30px !important;
            height: auto !important;
        }

        .manifesto-texto {
            font-size: 1.4rem !important;
            line-height: 1.4 !important;
        }

        .manifesto-titulo {
            font-size: 2.5rem !important;
            line-height: 1.1 !important;
            margin-bottom: 20px !important;
            letter-spacing: 0.05em !important;
        }

        /* Ajustes Visão e Missão Mobile */
        .missao-visao-slide {
            height: auto !important;
        }

        .missao-visao-content {
            padding: 60px 30px !important;
        }

        .missao-visao-titulo {
            font-size: 2rem !important;
            margin-bottom: 10px !important;
            text-align: center;
        }

        .missao-visao-subtitulo {
            font-size: 1rem !important;
            text-align: center;
            margin-bottom: 40px !important;
        }

        .missao-visao-grid {
            flex-direction: column !important;
            gap: 40px !important;
        }

        .missao-visao-decor {
            display: none !important;
        }

        .missao-visao-square {
            display: none !important;
        }

        /* Ajustes Perspectiva Mobile */
        .perspectiva-titulo {
            font-size: 2rem !important;
            margin-bottom: 30px !important;
        }

        .perspectiva-grid {
            flex-direction: column !important;
            gap: 20px !important;
        }

        .perspectiva-box {
            padding: 30px 25px !important;
        }

        .perspectiva-slide {
            height: auto !important;
            min-height: 100vh;
            display: block !important;
        }

        .perspectiva-img-box {
            height: 30vh !important;
            aspect-ratio: auto !important;
        }

        .perspectiva-content-box {
            padding: 40px 30px !important;
            justify-content: flex-start !important;
            height: auto !important;
        }

        /* Ajustes Experiências Mobile */
        .experiencias-slide {
            flex-direction: column !important;
            height: auto !important;
            min-height: 100vh;
        }

        .experiencias-text-col {
            flex: none !important;
            padding: 30px 30px !important;
            height: auto !important;
            order: 2;
        }

        .experiencias-img-col {
            flex: none !important;
            height: 45vh !important;
            width: 100% !important;
            order: 1;
        }

        .experiencias-img-box {
            width: 90% !important;
            height: 90% !important;
        }

        .experiencias-titulo {
            font-size: 2.2rem !important;
            line-height: 1.1 !important;
            margin-bottom: 25px !important;
        }

        .experiencias-decor {
            display: none !important;
        }

        /* Ajustes Pacotes Mobile */
        .package-slide {
            flex-direction: column !important;
            height: auto !important;
            padding: 0 !important;
            overflow: visible !important;
        }

        .package-img-col {
            flex: none !important;
            height: 60vh !important;
            width: 100% !important;
            background: transparent !important;
        }

        .package-text-col {
            flex: none !important;
            padding: 50px 30px !important;
            height: auto !important;
            background: #fff !important;
        }

        .package-img-box {
            width: 100% !important;
            height: 100% !important;
        }

        .package-titulo {
            font-size: 2.2rem !important;
            line-height: 1.1 !important;
            margin-bottom: 25px !important;
        }

        .package-decor {
            display: none !important;
        }

        /* Ajustes Portfolio Capa Mobile */
        .portfolio-capa-slide {
            flex-direction: column !important;
            height: auto !important;
        }

        .portfolio-capa-content {
            flex-direction: column !important;
            height: auto !important;
        }

        .portfolio-capa-text-box {
            padding: 40px 30px !important;
            order: 2;
        }

        .portfolio-capa-title-box {
            padding: 40px 30px !important;
            order: 1;
            text-align: center;
        }

        .portfolio-capa-titulo {
            font-size: 2.5rem !important;
            margin-bottom: 10px !important;
        }

        .portfolio-capa-subtitulo {
            font-size: 1.1rem !important;
        }

        .portfolio-capa-img-box {
            height: 45vh !important;
            order: 3;
        }

        /* Ajustes Portfolio Feed Mobile */
        .portfolio-slide {
            flex-direction: column !important;
            height: auto !important;
            gap: 2px !important;
        }

        .portfolio-left-col,
        .portfolio-right-col {
            flex: none !important;
            width: 100% !important;
            height: auto !important;
            display: flex !important;
            flex-direction: column !important;
        }

        .portfolio-img-item {
            height: 50vh !important;
            flex: none !important;
        }

        .portfolio-img-v {
            height: 75vh !important;
        }

        .portfolio-label {
            top: 20px !important;
            left: 20px !important;
        }

        .portfolio-label p {
            font-size: 0.8rem !important;
        }

        /* Ajustes Equipe Mobile */
        .team-slide {
            height: auto !important;
            padding: 60px 20px !important;
        }

        .team-header {
            margin-bottom: 30px !important;
        }

        .team-title {
            font-size: 2.2rem !important;
            line-height: 1.1 !important;
        }

        .team-decor-bar {
            display: none !important;
        }

        .team-grid {
            flex-direction: row !important;
            flex-wrap: wrap !important;
            gap: 20px !important;
            width: 100% !important;
            justify-content: center !important;
        }

        .team-item {
            flex: none !important;
            width: 45% !important;
        }

        .team-item div {
            width: 100% !important;
            margin: 0 auto 10px !important;
        }

        .team-item h4 {
            font-size: 0.9rem !important;
        }

        .team-item p {
            font-size: 0.75rem !important;
        }

        /* Ajustes Depoimentos Mobile */
        .depo-slide {
            height: auto !important;
            padding: 60px 20px !important;
        }

        .depo-container {
            flex-direction: column !important;
            gap: 50px !important;
            width: 100% !important;
        }

        .depo-col-left,
        .depo-col-right {
            flex: none !important;
            width: 100% !important;
        }

        .depo-title {
            font-size: 2.2rem !important;
            margin-bottom: 30px !important;
        }

        .depo-col-right {
            padding: 40px 30px !important;
        }

        .depo-col-right h2,
        .depo-col-right p {
            font-size: 1.5rem !important;
        }

        /* Ajustes Contato Mobile */
        .contato-slide {
            flex-direction: column !important;
            height: auto !important;
        }

        .contato-col-text {
            flex: none !important;
            width: 100% !important;
            padding: 50px 30px !important;
            order: 2;
        }

        .contato-col-img {
            flex: none !important;
            width: 100% !important;
            height: 45vh !important;
            order: 1;
        }

        .contato-title {
            font-size: 2.2rem !important;
            margin-bottom: 30px !important;
        }

        /* Ajustes Thank You Mobile */
        .thanks-title {
            font-size: 3.5rem !important;
            margin-bottom: 15px !important;
            letter-spacing: 0.05em !important;
        }

        .thanks-subtitle {
            font-size: 0.9rem !important;
            line-height: 1.4 !important;
            padding: 0 10px !important;
        }
    }

    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: scale(0.98);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }
</style>

<div class="wedding-proposal">`);

  parts.push(`
    <!-- PÁGINA 01: CAPA -->
    <section class="slide" style="padding: 0; display: block; background: #eee;">
        <img src="${raizUrl('/imagens-proposta-casamento/bg-section-01.jpg')}" class="img-bg"
            style="opacity: 1; z-index: 1;">

        <div class="content-overlay capa-content"
            style="height: 100%; width: 100%; display: flex; flex-direction: column; justify-content: space-between; padding: 10vh 8vw; box-sizing: border-box; max-width: 100%;">
            <!-- Topo Centro -->
            <div style="text-align: center; width: 100%;">
                <h1 class="capa-titulo"
                    style="font-family: var(--wedding-script); font-size: 8rem; color: #1a1a1a; margin-bottom: 0; font-weight: 400; text-transform: none; letter-spacing: 0;">
                    Casamento</h1>
                <p class="capa-subtitulo"
                    style="font-family: var(--wedding-montserrat); font-size: 1.8rem; letter-spacing: 0.6em; color: #1a1a1a; margin-top: -10px; font-weight: 300;">
                    FOTOGRAFIA E FILMMAKING</p>
            </div>

            <!-- Baixo Esquerda -->
            <div class="capa-bottom-box" style="text-align: left; max-width: 500px; color: #1a1a1a;">
                <h2 class="capa-casal"
                    style="font-family: var(--wedding-montserrat); font-size: 2.2rem; font-weight: 800; letter-spacing: 0.05em; line-height: 1.2; margin-bottom: 20px;">
                    ${mbUpper(primeiroNomeNoivo)} &<br>${mbUpper(primeiroNomeNoiva)}
                </h2>
                <p class="capa-desc"
                    style="font-family: var(--wedding-montserrat); font-size: 1.4rem; line-height: 1.6; font-weight: 400; margin-bottom: 20px; opacity: 0.8;">
                    Toda história tem algo bonito para mostrar. A gente olha para o dia de vocês e busca os momentos que outros deixariam passar.
                </p>
                <div class="capa-line"
                    style="width: 40px; height: 1px; background: #1a1a1a; margin-bottom: 20px; opacity: 0.5;"></div>
                <p
                    style="font-family: var(--wedding-montserrat); font-size: 1rem; font-weight: 400; letter-spacing: 0.05em; opacity: 0.8;">
                    by Distinto wedding</p>
            </div>
        </div>
    </section>

    <!-- PÁGINA 02: BOAS-VINDAS -->
    <section class="slide boas-vindas-slide"
        style="padding: 0; background: #fff; overflow: hidden; display: flex; flex-direction: row; height: 100vh; width: 100%;">
        <!-- Coluna Esquerda: Imagem -->
        <div class="boas-vindas-img-col"
            style="flex: 1; background: #f0f0f0; display: flex; align-items: center; justify-content: flex-end; padding-right: 5vw; position: relative; height: 100%;">
            <!-- Retângulo decorativo cinza (esquerda) -->
            <div class="boas-vindas-img-decor"
                style="position: absolute; left: 0; top: 0; width: 50px; height: 100%; background: #dcdcdc; z-index: 1;">
            </div>

            <div
                style="width: 75%; aspect-ratio: 3/4; position: relative; z-index: 2; overflow: hidden; box-shadow: 20px 20px 0px rgba(0,0,0,0.02);">
                <img src="${raizUrl('/imagens-proposta-casamento/foto-section-02.jpg')}"
                    style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </div>

        <!-- Coluna Direita: Conteúdo -->
        <div class="boas-vindas-text-col"
            style="flex: 1.2; padding: 0 8vw; display: flex; flex-direction: column; justify-content: center; position: relative; background: #fff; height: 100%;">
            <h1 class="boas-vindas-titulo"
                style="font-family: var(--wedding-montserrat); font-size: 3.8rem; font-weight: 700; line-height: 1.1; margin-bottom: 40px; color: #1a1a1a; text-transform: uppercase; letter-spacing: -1px;">
                O REGISTRO<br>DO QUE VOCÊS<br>ESTÃO CONSTRUINDO<br>COMEÇA AQUI
            </h1>

            <div style="max-width: 480px;">
                <p
                    style="font-family: var(--wedding-montserrat); font-size: 1.2rem; font-weight: 700; color: #1a1a1a; margin-bottom: 10px;">
                    ${saudacaoCasal}
                </p>
                <p
                    style="font-family: var(--wedding-montserrat); font-size: 1.1rem; line-height: 1.8; color: #444; font-weight: 400;">
                    ${nl2br(esc(mensagemPessoal))}
                </p>
            </div>

            <!-- Logo Distinto no canto inferior direito -->
            <div class="boas-vindas-logo" style="position: absolute; bottom: 8vh; right: 6vw; width: 120px;">
                <img src="${raizUrl('/assets/distinto_logo.svg')}"
                    style="width: 100%; filter: brightness(0); opacity: 0.8;">
            </div>

            <!-- Elemento decorativo cinza (topo direito) -->
            <div style="position: absolute; top: 10vh; right: 0; width: 50px; height: 35px; background: #dcdcdc;"></div>
        </div>
    </section>

    <!-- PÁGINA 03: ONDE O TEMPO PARA (MANIFESTO) -->
    <section class="slide manifesto-slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: row; align-items: center; justify-content: center; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Lado Esquerdo: Foto com Barra Decorativa -->
        <div class="manifesto-img-col"
            style="flex: 1.2; height: 100%; position: relative; display: flex; align-items: center; justify-content: center;">
            <!-- Barra Cinza -->
            <div
                style="position: absolute; top: 0; left: 0; width: 60%; height: 100%; background: #dcdcdc; z-index: 1;">
            </div>
            <!-- Foto -->
            <div class="reveal-item"
                style="width: 80%; height: 80%; z-index: 2; position: relative; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                <img src="${raizUrl('/imagens-proposta-casamento/foto-section-17.png')}"
                    style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </div>

        <!-- Lado Direito: Texto -->
        <div class="reveal-item manifesto-text-col"
            style="flex: 1; padding: 0 6vw; display: flex; flex-direction: column; justify-content: center; position: relative;">
            <h2 class="manifesto-titulo"
                style="font-family: var(--wedding-montserrat); font-size: 4.5rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase; line-height: 1; margin-bottom: 40px;">
                ONDE O<br>TEMPO PARA
            </h2>

            <div
                style="font-family: var(--wedding-montserrat); font-size: 1.1rem; line-height: 1.8; color: #333; font-weight: 400; max-width: 500px;">
                <p style="margin-bottom: 25px;">
                    Uma foto boa não é aquela que é tecnicamente perfeita. É aquela que te faz lembrar do frio na barriga, do cheiro das flores e do nó na garganta na hora dos votos.
                </p>
                <p style="margin-bottom: 25px;">
                    A gente foca no que é invisível: o sussurro no altar, o jeito que vocês se olham quando acham que ninguém está vendo ou a emoção real dos seus convidados.
                </p>
                <p style="margin-bottom: 25px;">
                    <strong>O arrepio em um clique.</strong> Nossa busca é pela imagem que faz o tempo parar. Queremos que vocês vejam o próprio casamento por um ângulo artístico e sensível, onde a luz e a fé que os une ganham um sentido novo e verdadeiro.
                </p>
            </div>

            <!-- Assinatura -->
            <div style="margin-top: 40px; text-align: right; width: 100%; max-width: 500px;">
                <p
                    style="font-family: var(--wedding-montserrat); font-size: 0.9rem; color: #666; letter-spacing: 0.1em; text-transform: uppercase;">
                    by Wellington Poncem
                </p>
            </div>
        </div>
    </section>

    <!-- PÁGINA 04: VISÃO E MISSÃO -->
    <section class="slide missao-visao-slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: column; overflow: hidden;">
        <!-- Topo: Textos -->
        <div class="missao-visao-content"
            style="flex: 1.2; padding: 10vh 10vw; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative;">
            <div class="missao-visao-square"
                style="position: absolute; top: 0; left: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>

            <h2 class="missao-visao-titulo"
                style="font-family: var(--wedding-montserrat); font-size: 3.5rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase;">
                VISÃO E MISSÃO</h2>
            <p class="missao-visao-subtitulo"
                style="font-family: var(--wedding-montserrat); font-size: 1.4rem; font-weight: 300; color: #444; margin-bottom: 6vh;">
                A meta é arrepiar e eternizar o extraordinário.</p>

            <div class="missao-visao-grid" style="display: flex; gap: 8vw; width: 100%; max-width: 1100px;">
                <!-- Missão -->
                <div style="flex: 1;">
                    <h3
                        style="font-family: var(--wedding-montserrat); font-size: 1.5rem; font-weight: 700; color: #1a1a1a; margin-bottom: 15px; text-transform: uppercase; text-align: center;">
                        O QUE BUSCAMOS</h3>
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; line-height: 1.8; color: #555; text-align: justify;">
                        A gente está aqui para guardar o que é de verdade. Do silêncio da oração à bagunça da pista. Nossa meta é criar uma memória que faça sentido hoje e daqui a cinquenta anos, registrando cada detalhe com a verdade que o momento merece.
                    </p>
                </div>
                <!-- Visão -->
                <div style="flex: 1;">
                    <h3
                        style="font-family: var(--wedding-montserrat); font-size: 1.5rem; font-weight: 700; color: #1a1a1a; margin-bottom: 15px; text-transform: uppercase; text-align: center;">
                        COMO ENXERGAMOS</h3>
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; line-height: 1.8; color: #555; text-align: justify;">
                        Queremos que as fotos e o filme sejam o caminho mais curto para vocês reviverem a emoção do "sim". Enxergamos nosso trabalho como uma ferramenta para que vocês vejam o casamento por uma perspectiva nova, criando uma herança visual que só cresce de valor com o tempo.
                    </p>
                </div>
            </div>
        </div>

        <!-- Base: Imagem -->
        <div style="width: 100%; aspect-ratio: 343/68; position: relative; overflow: hidden; background: #eee;">
            <img src="${raizUrl('/imagens-proposta-casamento/foto-section-03.jpg')}"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>

        <!-- Elemento decorativo cinza (lateral ocupando 100% da altura) -->
        <div class="missao-visao-decor"
            style="position: absolute; right: 0; top: 0; width: 50px; height: 100%; background: #959595ff; z-index: 5; opacity: 0.8;">
        </div>
    </section>

    <!-- PÁGINA 05: PERSPECTIVA -->
    <section class="slide perspectiva-slide"
        style="padding: 0; background: #fff; display: flex; flex-direction: column; overflow: hidden; position: relative;">
        <!-- Topo: Imagem -->
        <div class="perspectiva-img-box"
            style="width: 100%; aspect-ratio: 343/68; position: relative; overflow: hidden; background: #eee;">
            <img src="${raizUrl('/imagens-proposta-casamento/foto-section-04.jpg')}"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>

        <!-- Base: Textos -->
        <div class="perspectiva-content-box"
            style="flex: 1; padding: 8vh 10vw; background: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <h2 class="perspectiva-titulo"
                style="font-family: var(--wedding-montserrat); font-size: 3.5rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase; margin-bottom: 6vh; text-align: center; line-height: 1.1;">
                MAIS QUE UM ESTÚDIO,<br>UMA PERSPECTIVA
            </h2>

            <div class="perspectiva-grid" style="display: flex; gap: 20px; width: 100%; max-width: 1100px;">
                <div class="perspectiva-box"
                    style="flex: 1; background: #d9d9d9; padding: 40px; box-sizing: border-box;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; line-height: 1.8; color: #333; text-align: left;">
                        A gente não trabalha com fórmulas prontas. Olhamos para cada casal com um olhar novo, tentando entender o que torna vocês únicos. 
                        Não começamos com ideias soltas, mas com clareza sobre o que vocês querem sentir ao ver essas imagens no futuro. Nossa meta é arrepiar, entregando uma versão da história de vocês que torne o casamento uma lembrança viva e pulsante.
                    </p>
                </div>
                <div class="perspectiva-box"
                    style="flex: 1; background: #d9d9d9; padding: 40px; box-sizing: border-box;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; line-height: 1.8; color: #333; text-align: left;">
                        Acreditamos que a beleza ganha força quando tem propósito. Por isso, nosso olhar vai além do "bonito" estético. 
                        Criamos narrativas com intenção e foco no sentir, capturando desde o sussurro no altar até as lágrimas que ninguém viu cair. É um trabalho feito por pessoas que acreditam que toda história tem algo único para ser contado.
                    </p>
                </div>
            </div>
        </div>

    </section>
`);

  parts.push(`
    <!-- PÁGINA 06: EXPERIÊNCIAS DISTINTAS -->
    <section class="slide experiencias-slide"
        style="padding: 0; background: #fff; display: flex; flex-direction: row; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Lado Esquerdo: Textos -->
        <div class="experiencias-text-col"
            style="flex: 1; padding: 0 8vw; display: flex; flex-direction: column; justify-content: center; position: relative; height: 100%;">
            <!-- Decorativo Superior Esquerdo -->
            <div class="experiencias-decor"
                style="position: absolute; top: 0; left: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>

            <h2 class="experiencias-titulo"
                style="font-family: var(--wedding-montserrat); font-size: 4rem; font-weight: 300; letter-spacing: 0.05em; color: #1a1a1a; text-transform: uppercase; line-height: 1.1; margin-bottom: 40px;">
                EXPERIÊNCIAS<br>DISTINTAS
            </h2>

            <div
                style="max-width: 700px; font-family: var(--wedding-montserrat); font-size: 1.1rem; line-height: 1.8; color: #444;">
                <p style="margin-bottom: 20px;">Na Distinto, não começamos com ideias soltas. Começamos com clareza.</p>

                <p style="margin-bottom: 20px;">
                    Desenhamos três caminhos estratégicos para que a história de <strong>${esc(nomeCasal)}</strong> seja
                    preservada com a força e a verdade que merecem.
                </p>

                <p style="margin-bottom: 20px;">
                    Apresentamos nossas propostas de investimento. Cada uma delas foi pensada para transformar o seu
                    casamento em uma experiência totalmente nova, onde a nossa perspectiva artística garante que todas
                    as variáveis do dia ganhem o mais bonito sentido.
                </p>

                <p style="margin-bottom: 20px;">Escolham o caminho que melhor se conecta com o sonho de vocês.</p>

                <p style="font-weight: 700; color: #1a1a1a;">Nossa meta é uma só: arrepiar.</p>
            </div>

            <!-- Decorativo Inferior Esquerdo -->
            <div class="experiencias-decor"
                style="position: absolute; bottom: 10vh; left: 8vw; width: 180px; height: 60px; background: #dcdcdc;">
            </div>
        </div>

        <!-- Lado Direito: Imagem -->
        <div class="experiencias-img-col"
            style="flex: 1; position: relative; display: flex; align-items: center; justify-content: center; height: 100%;">
            <!-- Fundo Cinza Decorativo na Direita -->
            <div class="experiencias-decor"
                style="position: absolute; top: 0; right: 0; width: 35%; height: 80%; background: #dcdcdc; z-index: 1;">
            </div>

            <div class="package-img-box" style="width: 80%; height: 80%; position: relative; z-index: 2;">
                <img src="${raizUrl('/imagens-proposta-casamento/foto-section-05.png')}"
                    style="width: 100%; height: 100%; object-fit: contain;">
            </div>
        </div>
    </section>
`);

  parts.push(`
    <!-- PÁGINA 07: FULL IMAGE -->
    <section class="slide" style="padding: 0; background: #000;">
        <img src="${raizUrl('/imagens-proposta-casamento/foto-section-06.jpg')}" class="img-bg"
            style="opacity: 1; z-index: 1;">
    </section>
`);

  parts.push(`${(!vazio(atualizacoesVersao) || (mostrarAndamentoCliente && !vazio(andamentoProposta))) ? `

    <section class="slide" style="padding: 0; background: #f7f6f4; display: flex; flex-direction: row; overflow: hidden; height: 100vh; width: 100%;">
        <div style="flex: 0.9; background: #1a1a1a; color: white; padding: 0 7vw; display: flex; flex-direction: column; justify-content: center;">
            <p style="font-family: var(--wedding-montserrat); font-size: 0.75rem; letter-spacing: 0.25em; color: var(--wedding-gold); text-transform: uppercase; margin-bottom: 22px;">
                ${esc(versaoProposta || 'Proposta revisada')}
            </p>
            <h2 style="font-family: var(--wedding-montserrat); font-size: 3.2rem; font-weight: 300; letter-spacing: 0.06em; color: white; text-transform: uppercase; line-height: 1.1; margin-bottom: 28px;">
                AJUSTES APOS<br>ALINHAMENTO
            </h2>
            <p style="font-family: var(--wedding-montserrat); font-size: 0.98rem; line-height: 1.8; color: rgba(255,255,255,0.72); margin: 0;">
                Esta versao consolida o escopo negociado e substitui as condicoes comerciais apresentadas anteriormente.
            </p>
        </div>
        <div style="flex: 1.1; padding: 0 7vw; display: flex; flex-direction: column; justify-content: center;">
${!vazio(atualizacoesVersao) ? `
            <h3 style="font-family: var(--wedding-montserrat); font-size: 0.85rem; font-weight: 800; letter-spacing: 0.18em; color: #1a1a1a; text-transform: uppercase; margin-bottom: 18px;">Atualizacoes desta versao</h3>
            <ul style="list-style: none; padding: 0; margin: 0 0 34px;">
${atualizacoesVersao.map((linha) => `                <li style="font-family: var(--wedding-montserrat); font-size: 1rem; line-height: 1.7; color: #333; margin-bottom: 12px; padding-left: 22px; position: relative;">
                    <span style="position: absolute; left: 0; color: var(--wedding-gold);">•</span>${esc(linha)}
                </li>`).join('\n')}
            </ul>
` : ''}

${(mostrarAndamentoCliente && !vazio(andamentoProposta)) ? `
            <h3 style="font-family: var(--wedding-montserrat); font-size: 0.85rem; font-weight: 800; letter-spacing: 0.18em; color: #1a1a1a; text-transform: uppercase; margin-bottom: 18px;">Andamento da proposta</h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
${andamentoProposta.map((linha) => `                <div style="background: #fff; border-left: 3px solid var(--wedding-gold); padding: 12px 16px; font-family: var(--wedding-montserrat); font-size: 0.82rem; line-height: 1.6; color: #444;">
                    ${esc(linha)}
                </div>`).join('\n')}
            </div>
` : ''}
        </div>
    </section>
` : ''}`);

  parts.push(`\n\n\n`);
  if (precoUnicoAtivo) {
    parts.push(`
    <section class="slide" style="padding: 0; background: #fff; display: flex; flex-direction: row; overflow: hidden; height: 100vh; width: 100%;">
        <div style="flex: 1; height: 100%;">
            <img src="${raizUrl('/imagens-proposta-casamento/foto-section-07.png')}" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div style="flex: 1.1; padding: 0 7vw; display: flex; flex-direction: column; justify-content: center; background: #f9f9f9; position: relative;">
            <h2 style="font-family: var(--wedding-montserrat); font-size: 3.3rem; font-weight: 300; letter-spacing: 0.05em; color: #1a1a1a; text-transform: uppercase; line-height: 1.1; margin-bottom: 26px;">
                ${esc(precoUnicoTitulo)}
            </h2>
            <ul style="list-style: none; padding: 0; margin: 0 0 34px;">
${precoUnicoItens.map((linha) => `                <li style="font-family: var(--wedding-montserrat); font-size: 1rem; line-height: 1.65; color: #444; margin-bottom: 11px; padding-left: 22px; position: relative;">
                    <span style="position: absolute; left: 0; color: #1a1a1a;">•</span>${esc(linha)}
                </li>`).join('\n')}
            </ul>
            <div style="border-top: 1px solid #dcdcdc; padding-top: 24px;">
                <p style="font-family: var(--wedding-montserrat); font-size: 0.8rem; font-weight: 800; letter-spacing: 0.2em; color: #888; text-transform: uppercase; margin-bottom: 8px;">Investimento unico</p>
                <p style="font-family: var(--wedding-serif); font-size: 3rem; line-height: 1; color: #1a1a1a; margin: 0;">
                    ${fmt(precoUnicoValor)}
                </p>
            </div>
        </div>
    </section>
`);
  }
  parts.push(`\n\n\n`);

  parts.push(`
${(!precoUnicoAtivo && (d.show_heritage ?? true) !== false) ? `
    <!-- PÁGINA 08: EXPERIÊNCIA HERITAGE -->
    <section class="slide package-slide"
        style="padding: 0; background: #fff; display: flex; flex-direction: row; overflow: hidden; position: relative; height: 100vh; width: 100%;">
        <!-- Lado Esquerdo: Imagem -->
        <div class="package-img-col" style="flex: 1; height: 100%;">
            <picture>
                <source media="(max-width: 768px)"
                    srcset="${raizUrl('/imagens-proposta-casamento/foto-section-07-mobile.png')}">
                <img src="${raizUrl('/imagens-proposta-casamento/foto-section-07.png')}"
                    style="width: 100%; height: 100%; object-fit: cover;">
            </picture>
        </div>
        <!-- Lado Direito: Detalhes -->
        <div class="package-text-col"
            style="flex: 1.2; padding: 0 6vw; display: flex; flex-direction: column; justify-content: center; position: relative; height: 100%; background: #f9f9f9;">
            <!-- Decorativos -->
            <div class="package-decor"
                style="position: absolute; top: 0; right: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div class="package-decor"
                style="position: absolute; bottom: 0; right: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div class="package-decor"
                style="position: absolute; top: 0; left: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div class="package-decor"
                style="position: absolute; bottom: 0; left: 0; width: 140px; height: 80px; background: #dcdcdc;"></div>
            <h2 class="package-titulo"
                style="font-family: var(--wedding-montserrat); font-size: 3.5rem; font-weight: 300; letter-spacing: 0.05em; color: #1a1a1a; text-transform: uppercase; line-height: 1.1; margin-bottom: 30px;">
                EXPERIÊNCIA<br>HERITAGE
            </h2>
            <div style="font-family: var(--wedding-montserrat); font-size: 0.95rem; line-height: 1.6; color: #444;">
                <p style="margin-bottom: 25px; font-weight: 400;">
                    Para quem quer o registro mais completo possível. A gente não deixa passar nada, cuidando de cada detalhe para que a história da família de vocês comece com o registro que ela merece.
                </p>
                <ul style="list-style: none; padding: 0; margin-bottom: 30px;">
${linhasH.filter((l) => !vazio(l)).map((linha) => `                    <li style="margin-bottom: 12px; position: relative; padding-left: 20px;">
                        <span style="position: absolute; left: 0; color: #1a1a1a;">•</span>
                        ${esc(linha)}
                    </li>`).join('\n')}
${renderItensPersonalizadosCasamento(itensPersonalizados.heritage)}
                </ul>
                <p style="font-style: italic; color: #333; font-size: 1.1rem; margin-bottom: 25px;">
                    Investimento: ${d.valor_heritage ? fmt(d.valor_heritage) : 'R$ 7.900,00'}
                </p>

${hasUpgradesH ? `
                <div style="margin-top: 20px; border-top: 1px solid #dcdcdc; padding-top: 20px;">
                    <p style="font-weight: 700; color: #1a1a1a; margin-bottom: 15px; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 0.05em;">
                        Upgrades que fazem toda diferença:
                    </p>
                    <ul style="list-style: none; padding: 0;">
${((d.include_boudoir_heritage ?? d.include_boudoir ?? false) !== false) ? `                        <li style="margin-bottom: 10px; position: relative; padding-left: 20px;">
                            <span style="position: absolute; left: 0; color: #1a1a1a;">•</span>
                            <strong>Boudoir da Noiva:</strong> ${fmt(d.valor_boudoir || 500)}
                        </li>
` : ''}${((d.include_prewedding_heritage ?? d.include_prewedding ?? false) !== false) ? `                        <li style="margin-bottom: 10px; position: relative; padding-left: 20px;">
                            <span style="position: absolute; left: 0; color: #1a1a1a;">•</span>
                            <strong>Ensaio Pré-Wedding:</strong> ${fmt(d.valor_prewedding || 1100)}
                        </li>
` : ''}                    </ul>
                </div>
` : ''}
            </div>
        </div>
    </section>
` : ''}
`);

  parts.push(`
${(!precoUnicoAtivo && (d.show_cinematic ?? true) !== false) ? `
    <!-- PÁGINA 09: EXPERIÊNCIA CINEMATIC -->
    <section class="slide package-slide"
        style="padding: 0; background: #fff; display: flex; flex-direction: row; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Lado Esquerdo: Detalhes -->
        <div class="package-text-col"
            style="flex: 1; padding: 0 8vw; display: flex; flex-direction: column; justify-content: center; position: relative; height: 100%;">
            <!-- Decorativo Superior Esquerdo -->
            <div class="package-decor"
                style="position: absolute; top: 0; left: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>

            <h2 class="package-titulo"
                style="font-family: var(--wedding-montserrat); font-size: 3.5rem; font-weight: 300; letter-spacing: 0.05em; color: #1a1a1a; text-transform: uppercase; line-height: 1.1; margin-bottom: 25px;">
                EXPERIÊNCIA<br>CINEMATIC
            </h2>

            <div style="font-family: var(--wedding-montserrat); font-size: 0.95rem; line-height: 1.6; color: #444;">
                <p style="margin-bottom: 20px; font-weight: 400;">
                    O melhor da fotografia com a energia do vídeo atual. É um registro dinâmico, pensado para quem quer reviver o dia com a intensidade de um filme e compartilhar cada momento.
                </p>

                <ul style="list-style: none; padding: 0; margin-bottom: 20px;">
${linhasC.filter((l) => !vazio(l)).map((linha) => `                    <li style="margin-bottom: 10px; position: relative; padding-left: 20px;">
                        <span style="position: absolute; left: 0; color: #1a1a1a;">•</span>
                        ${esc(linha)}
                    </li>`).join('\n')}
${renderItensPersonalizadosCasamento(itensPersonalizados.cinematic)}
                </ul>

                <p style="font-style: italic; color: #333; font-size: 1.1rem; margin-bottom: 25px;">
                    Investimento: ${d.valor_cinematic ? fmt(d.valor_cinematic) : 'R$ 4.500,00'}
                </p>

${hasUpgradesC ? `
                <div style="margin-top: 20px; border-top: 1px solid #dcdcdc; padding-top: 20px;">
                    <p style="font-weight: 700; color: #1a1a1a; margin-bottom: 15px; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 0.05em;">
                        Upgrades que fazem toda diferença:
                    </p>
                    <ul style="list-style: none; padding: 0;">
${((d.include_boudoir_cinematic ?? d.include_boudoir ?? false) !== false) ? `                        <li style="margin-bottom: 10px; position: relative; padding-left: 20px;">
                            <span style="position: absolute; left: 0; color: #1a1a1a;">•</span>
                            <strong>Boudoir da Noiva:</strong> ${fmt(d.valor_boudoir || 500)}
                        </li>
` : ''}${((d.include_prewedding_cinematic ?? d.include_prewedding ?? false) !== false) ? `                        <li style="margin-bottom: 10px; position: relative; padding-left: 20px;">
                            <span style="position: absolute; left: 0; color: #1a1a1a;">•</span>
                            <strong>Ensaio Pré-Wedding:</strong> ${fmt(d.valor_prewedding || 1100)}
                        </li>
` : ''}                        <li style="margin-bottom: 10px; position: relative; padding-left: 20px;">
                            <span style="position: absolute; left: 0; color: #1a1a1a;">•</span>
                            <strong>Upgrade Família:</strong> Adicione o Álbum Master por apenas R$ 950,00.
                        </li>
                    </ul>
                </div>
` : ''}
            </div>

            <!-- Decorativo Inferior Esquerdo -->
            <div class="package-decor"
                style="position: absolute; bottom: 0; left: 8vw; width: 180px; height: 60px; background: #dcdcdc;">
            </div>
        </div>

        <!-- Lado Direito: Imagem -->
        <div class="package-img-col"
            style="flex: 1.2; position: relative; display: flex; align-items: center; justify-content: center; background: #f0f0f0;">
            <!-- Decorativo Superior Direito -->
            <div class="package-decor"
                style="position: absolute; top: 0; right: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div class="package-decor"
                style="position: absolute; bottom: 0; left: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>

            <div class="package-img-box" style="width: 80%; height: 80%; position: relative; z-index: 2;">
                <img src="${raizUrl('/imagens-proposta-casamento/foto-section-08.png')}"
                    style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </div>
    </section>
` : ''}
`);

  parts.push(`
${(!precoUnicoAtivo && (d.show_essencial ?? true) !== false) ? `
    <!-- PÁGINA 10: REGISTRO ESSENCIAL -->
    <section class="slide package-slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: row; overflow: hidden; position: relative; height: 100vh; width: 100%;">
        <!-- Lado Esquerdo: Imagem -->
        <div class="package-img-col"
            style="flex: 1; height: 100%; display: flex; align-items: center; justify-content: center;">
            <img src="${raizUrl('/imagens-proposta-casamento/foto-section-09.png')}"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <!-- Lado Direito: Detalhes -->
        <div class="package-text-col"
            style="flex: 1.2; padding: 0 6vw; display: flex; flex-direction: column; justify-content: center; position: relative; height: 100%;">
            <!-- Decorativos -->
            <div class="package-decor"
                style="position: absolute; top: 0; right: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div class="package-decor"
                style="position: absolute; bottom: 0; right: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div class="package-decor"
                style="position: absolute; top: 0; left: 0; width: 60px; height: 80px; background: #dcdcdc;"></div>
            <div class="package-decor"
                style="position: absolute; bottom: 0; left: 0; width: 140px; height: 80px; background: #dcdcdc;"></div>
            <h2 class="package-titulo"
                style="font-family: var(--wedding-montserrat); font-size: 3.5rem; font-weight: 300; letter-spacing: 0.05em; color: #1a1a1a; text-transform: uppercase; line-height: 1.1; margin-bottom: 25px;">
                REGISTRO<br>ESSENCIAL
            </h2>
            <div style="font-family: var(--wedding-montserrat); font-size: 0.95rem; line-height: 1.5; color: #444;">
                <p style="margin-bottom: 20px; font-weight: 400;">
                    Um registro focado estritamente no protocolo, ideal para cerimônias curtas e objetivas que exigem um
                    olhar profissional sobre os momentos principais.
                </p>
                <ul style="list-style: none; padding: 0; margin-bottom: 20px;">
${linhasE.filter((l) => !vazio(l)).map((linha) => `                    <li style="margin-bottom: 10px; position: relative; padding-left: 20px;">
                        <span style="position: absolute; left: 0; color: #1a1a1a;">•</span>
                        ${esc(linha)}
                    </li>`).join('\n')}
${renderItensPersonalizadosCasamento(itensPersonalizados.essencial)}
                </ul>
                <p style="font-style: italic; color: #333; font-size: 1.1rem; margin-bottom: 25px;">
                    Investimento: ${d.valor_essencial ? fmt(d.valor_essencial) : 'R$ 2.800,00'}
                </p>

${hasUpgradesE ? `
                <div style="margin-top: 10px; border-top: 1px solid #dcdcdc; padding-top: 20px;">
                    <p style="font-weight: 700; color: #1a1a1a; margin-bottom: 15px; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 0.05em;">
                        Upgrades que fazem toda diferença:
                    </p>
                    <ul style="list-style: none; padding: 0;">
${((d.include_boudoir_essencial ?? d.include_boudoir ?? false) !== false) ? `                        <li style="margin-bottom: 10px; position: relative; padding-left: 20px;">
                            <span style="position: absolute; left: 0; color: #1a1a1a;">•</span>
                            <strong>Boudoir da Noiva:</strong> ${fmt(d.valor_boudoir || 500)}
                        </li>
` : ''}${((d.include_prewedding_essencial ?? d.include_prewedding ?? false) !== false) ? `                        <li style="margin-bottom: 10px; position: relative; padding-left: 20px;">
                            <span style="position: absolute; left: 0; color: #1a1a1a;">•</span>
                            <strong>Ensaio Pré-Wedding:</strong> ${fmt(d.valor_prewedding || 1100)}
                        </li>
` : ''}                    </ul>
                </div>
` : ''}
            </div>
        </div>
    </section>
` : ''}
`);

  parts.push(`
    <!-- PÁGINA 10.5: INVESTIMENTO E PLANEJAMENTO -->
    <section class="slide slide-investimento"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: column; overflow: hidden; height: 100vh; width: 100%;">

        <!-- Topo: Imagem com Overlay -->
        <div class="investimento-img" style="height: 30%; width: 100%; position: relative; overflow: hidden; flex-shrink: 0;">
            <img src="${raizUrl('/imagens-proposta-casamento/foto-section-10.png')}"
                style="width: 100%; height: 100%; object-fit: cover; object-position: center 40%; display: block;">
            <div style="position: absolute; inset: 0; background: linear-gradient(to bottom, transparent 40%, #f4f4f4 100%);"></div>
        </div>

        <!-- Conteúdo -->
        <div class="investimento-content" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 0 8vw; text-align: center;">
            <h2 class="reveal-item investimento-title"
                style="font-family: var(--wedding-montserrat); font-size: 3rem; font-weight: 300; letter-spacing: 0.08em; color: #1a1a1a; text-transform: uppercase; margin-bottom: 5px;">
                INVESTIMENTO E PLANEJAMENTO
            </h2>
            <p class="reveal-item investimento-subtitle"
                style="font-family: var(--wedding-montserrat); font-size: 1rem; font-weight: 400; letter-spacing: 0.25em; color: #888; text-transform: uppercase; margin-bottom: 40px;">
                FORMAS DE RESERVA E PAGAMENTO
            </p>

            <!-- Cláusula de Reserva -->
            <div class="reveal-item"
                style="max-width: 800px; width: 100%; background: #fff; border-left: 3px solid var(--wedding-gold); padding: 25px 30px; margin-bottom: 30px; text-align: left;">
                <p style="font-family: var(--wedding-montserrat); font-size: 0.9rem; line-height: 1.8; color: #444; margin: 0;">
                    ${esc(clausula_slide)}
                </p>
            </div>

${!precoUnicoAtivo ? `
            <!-- Cards de Condições -->
            <div class="reveal-item investimento-cards" style="display: flex; gap: 20px; width: 100%; max-width: 800px;">
                <!-- Heritage & Cinematic -->
                <div style="flex: 1; background: #e8e6e3; padding: 25px 30px; text-align: left; border-radius: 2px;">
                    <p style="font-family: var(--wedding-montserrat); font-size: 0.75rem; font-weight: 800; letter-spacing: 0.05em; color: #1a1a1a; text-transform: uppercase; margin-bottom: 10px;">
                        Para Experiência Heritage & Experiência Cinematic
                    </p>
                    <p style="font-family: var(--wedding-montserrat); font-size: 0.85rem; line-height: 1.6; color: #555; margin: 0;">
                        ${esc(condHC_slide)}
                    </p>
                </div>

                <!-- Essencial -->
                <div style="flex: 1; background: #e8e6e3; padding: 25px 30px; text-align: left; border-radius: 2px;">
                    <p style="font-family: var(--wedding-montserrat); font-size: 0.75rem; font-weight: 800; letter-spacing: 0.05em; color: #1a1a1a; text-transform: uppercase; margin-bottom: 10px;">
                        Para o Registro Essencial
                    </p>
                    <p style="font-family: var(--wedding-montserrat); font-size: 0.85rem; line-height: 1.6; color: #555; margin: 0;">
                        ${esc(condE_slide)}
                    </p>
                </div>
            </div>
` : ''}
        </div>
    </section>
`);

  parts.push(`
${!precoUnicoAtivo ? `
    <!-- PÁGINA 11: ESCOLHA SEU PACOTE — INTERATIVO -->
    <div id="slide-pacote"
        style="display: none; position: fixed; inset: 0; z-index: 10000; background: #1a1a1a; overflow: hidden; flex-direction: row; font-family: var(--wedding-montserrat); animation: modalFadeIn 0.3s ease;">

        <!-- Botão Fechar -->
        <button onclick="window.closeInteractiveModal()"
            style="position: absolute; top: 30px; right: 30px; z-index: 10001; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #fff; transition: all 0.3s;">
            <i data-lucide="x"></i>
        </button>

        <style>
            #slide-pacote .plan-card {
                transition: border-color 0.2s, background 0.2s;
            }

            #slide-pacote .plan-card:hover {
                background: rgba(255, 255, 255, 0.05) !important;
            }

            #slide-pacote .toggle-track {
                width: 36px;
                height: 20px;
                border-radius: 20px;
                background: rgba(255, 255, 255, 0.12);
                cursor: pointer;
                flex-shrink: 0;
                position: relative;
                transition: background 0.2s;
            }

            #slide-pacote .toggle-track.on {
                background: var(--wedding-gold);
            }

            #slide-pacote .toggle-thumb {
                width: 14px;
                height: 14px;
                border-radius: 50%;
                background: #fff;
                position: absolute;
                top: 3px;
                left: 3px;
                transition: left 0.2s;
            }

            #slide-pacote .toggle-track.on .toggle-thumb {
                left: 19px;
            }

            #slide-pacote .linha-upgrade { transition: opacity 0.2s; }

            /* Mobile UI Overhaul */
            @media (max-width: 768px) {
                .wedding-proposal-modal {
                    flex-direction: column !important;
                    background: rgba(26, 26, 26, 0.98) !important;
                    backdrop-filter: blur(15px) !important;
                    overflow-y: auto !important;
                    display: none !important; /* Começar escondido */
                }
                .modal-selection-col, .modal-summary-col {
                    flex: none !important;
                    width: 100% !important;
                    height: auto !important;
                    padding: 30px 20px !important;
                    background: transparent !important;
                }
                .modal-selection-col {
                    padding-top: 80px !important;
                    border-right: none !important;
                    border-bottom: 1px solid rgba(255,255,255,0.05) !important;
                }
                .modal-summary-col {
                    padding-bottom: 120px !important; /* Espaço para o rodapé fixo */
                }
                #slide-pacote .plan-card {
                    padding: 18px 20px !important;
                    margin-bottom: 5px !important;
                }
                #slide-pacote .plan-card p {
                    font-size: 0.75rem !important;
                }
                #total-display {
                    font-size: 1.6rem !important;
                }
                #whatsapp-btn {
                    display: none !important;
                }
                /* Rodapé fixo para mobile - SÓ aparece quando o modal está aberto */
                .modal-mobile-footer {
                    display: none !important;
                }
                #slide-pacote[style*="display: flex"] .modal-mobile-footer,
                #slide-pacote.modal-open .modal-mobile-footer {
                    display: flex !important;
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    width: 100%;
                    background: #1a1a1a;
                    padding: 15px 20px;
                    border-top: 1px solid rgba(255,255,255,0.1);
                    z-index: 10005;
                    box-shadow: 0 -10px 30px rgba(0,0,0,0.5);
                    flex-direction: column;
                    gap: 10px;
                }
                .modal-mobile-total-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: baseline;
                }

                /* Slide de Condições de Pagamento - Mobile */
                .slide-investimento {
                    height: auto !important;
                    min-height: 100vh;
                }
                .slide-investimento .investimento-img {
                    height: 25vh !important;
                }
                .slide-investimento .investimento-content {
                    padding: 30px 25px !important;
                }
                .slide-investimento .investimento-title {
                    font-size: 1.8rem !important;
                    margin-bottom: 3px !important;
                }
                .slide-investimento .investimento-subtitle {
                    font-size: 0.7rem !important;
                    margin-bottom: 25px !important;
                }
                .slide-investimento .investimento-cards {
                    flex-direction: column !important;
                    gap: 12px !important;
                }
            }
            @media (min-width: 769px) {
                .modal-mobile-footer { display: none !important; }
            }
        </style>

        <!-- Coluna Esquerda: Seleção de Plano + Upgrades -->
        <div class="modal-selection-col"
            style="flex: 1.4; padding: 5vh 5vw; display: flex; flex-direction: column; justify-content: center; gap: 0; border-right: 1px solid rgba(255,255,255,0.06); overflow-y: auto;">

            <p
                style="font-size: 0.6rem; font-weight: 700; letter-spacing: 0.28em; text-transform: uppercase; color: var(--wedding-gold); margin: 0 0 16px;">
                ESCOLHA SEU PACOTE</p>

            <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 25px;">
${(ctx.planosWedding ?? []).map((p) => `                <div id="plan-${p.id}" class="plan-card" onclick="selectPlan('${p.id}')"
                    style="display: flex; align-items: center; gap: 15px; padding: 20px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.07); background: rgba(255,255,255,0.02); cursor: pointer; position: relative;">
                    <div class="plan-radio"
                        style="width: 18px; height: 18px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.25); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <div class="plan-radio-dot"
                            style="width: 10px; height: 10px; border-radius: 50%; background: var(--wedding-gold); opacity: 0; transition: opacity 0.2s;">
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <p
                            style="font-size: 0.75rem; font-weight: 500; color: rgba(255,255,255,0.85); margin: 0 0 2px; text-transform: uppercase; letter-spacing: 0.05em;">
                            ${esc(p.nome)}
                        </p>
                        <p style="font-size: 0.7rem; font-weight: 300; color: rgba(255,255,255,0.4); margin: 0;">
                            ${esc(p.descricao || 'Veja os itens inclusos abaixo')}
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <p style="font-size: 0.85rem; font-weight: 400; color: #fff; margin: 0;">
                            R$ ${number_format(p.preco_venda, 0)}
                        </p>
                    </div>
                    <span class="badge-selecionado"
                        style="display: none; position: absolute; top: 10px; right: 10px; font-size: 0.58rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--wedding-gold); border: 1px solid var(--wedding-gold); padding: 2px 8px; border-radius: 20px; white-space: nowrap; flex-shrink: 0;">Selecionado</span>
                </div>`).join('\n')}
            </div>

            <!-- Divisor -->
            <div style="border-top: 1px solid rgba(255,255,255,0.07); margin-bottom: 20px;"></div>

            <!-- Serviços Dinâmicos -->
            <p
                style="font-size: 0.6rem; font-weight: 700; letter-spacing: 0.28em; text-transform: uppercase; color: rgba(255,255,255,0.35); margin: 0 0 14px;">
                ITENS DO PLANO E ADICIONAIS</p>
            
            <div id="servicos-dinamicos-container" style="display: flex; flex-direction: column; gap: 0;">
                <p style="font-size: 0.75rem; color: rgba(255,255,255,0.3); font-style: italic; text-align: center; padding: 20px;">Selecione um plano acima para configurar os itens.</p>
            </div>
        </div>

        <!-- Coluna Direita: Resumo + Condições + Cláusula -->
        <div class="modal-summary-col"
            style="flex: 1; padding: 5vh 4vw; display: flex; flex-direction: column; justify-content: center; gap: 0; overflow-y: auto;">

            <!-- Resumo -->
            <p
                style="font-size: 0.6rem; font-weight: 700; letter-spacing: 0.28em; text-transform: uppercase; color: rgba(255,255,255,0.35); margin: 0 0 14px;">
                RESUMO</p>

            <div id="resumo-linhas" style="display: flex; flex-direction: column; gap: 0; margin-bottom: 6px;">
                <div id="linha-plano" class="linha-upgrade"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.06); opacity: 0.35;">
                    <span id="linha-plano-nome" style="font-size: 0.78rem; color: rgba(255,255,255,0.7);">Nenhum plano
                        selecionado</span>
                    <span id="linha-plano-valor" style="font-size: 0.78rem; color: rgba(255,255,255,0.7);">—</span>
                </div>
                <div id="linha-boudoir" class="linha-upgrade"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.06); opacity: 0.35;">
                    <span style="font-size: 0.78rem; color: rgba(255,255,255,0.7);">Upgrade Boudoir</span>
                    <span id="linha-boudoir-valor"
                        style="font-size: 0.78rem; color: rgba(255,255,255,0.4); text-decoration: line-through;">R$ ${number_format(pBoudoir, 0)}</span>
                </div>
                <div id="linha-prewedding" class="linha-upgrade"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.06); opacity: 0.35;">
                    <span style="font-size: 0.78rem; color: rgba(255,255,255,0.7);">Upgrade Pré-Wedding</span>
                    <span id="linha-prewedding-valor"
                        style="font-size: 0.78rem; color: rgba(255,255,255,0.4); text-decoration: line-through;">R$ ${number_format(pPrewedding, 0)}</span>
                </div>
            </div>

            <div
                style="display: flex; justify-content: space-between; align-items: baseline; padding: 14px 0; margin-bottom: 22px;">
                <span
                    style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.5);">Total
                    do pacote</span>
                <span id="total-display"
                    style="font-size: 1.8rem; font-weight: 300; letter-spacing: -0.02em; color: #fff;">—</span>
            </div>

            <!-- Divisor -->
            <div style="border-top: 1px solid rgba(255,255,255,0.07); margin-bottom: 18px;"></div>

            <p
                style="font-size: 0.6rem; font-weight: 700; letter-spacing: 0.28em; text-transform: uppercase; color: var(--wedding-gold); margin: 0 0 8px;">
                CONDIÇÕES DE PAGAMENTO</p>
            <p id="condicoes-display"
                style="font-size: 0.82rem; font-weight: 300; line-height: 1.65; color: rgba(255,255,255,0.45); margin: 0 0 20px;">
                Selecione um plano para ver as condições.</p>

            <!-- Botão WhatsApp -->
            <button id="whatsapp-btn" onclick="sendWhatsApp()"
                style="width: 100%; padding: 18px; background: #25d366; color: #fff; border: none; border-radius: 6px; font-family: var(--wedding-montserrat); font-weight: 700; font-size: 0.85rem; letter-spacing: 0.05em; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 25px; transition: all 0.3s; opacity: 0.3; pointer-events: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                    </path>
                </svg>
                Confirmar e enviar WhatsApp
            </button>

            <!-- Divisor -->
            <div style="border-top: 1px solid rgba(255,255,255,0.07); margin-bottom: 18px;"></div>

            <!-- Cláusula de Reserva -->
            <p
                style="font-size: 0.6rem; font-weight: 700; letter-spacing: 0.28em; text-transform: uppercase; color: rgba(255,255,255,0.35); margin: 0 0 10px;">
                CLÁUSULA DE RESERVA</p>
            <div style="border-left: 2px solid rgba(197,168,128,0.4); padding-left: 14px;">
                <p
                    style="font-size: 0.78rem; font-weight: 300; line-height: 1.7; color: rgba(255,255,255,0.45); margin: 0;">
                    ${esc(clausula)}
                </p>
            </div>
            </div>
        </div>

        <!-- Rodapé Mobile para Conversão -->
        <div class="modal-mobile-footer">
            <div class="modal-mobile-total-row">
                <span style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: rgba(255,255,255,0.5);">Total do pacote</span>
                <span id="total-display-mobile" style="font-size: 1.4rem; font-weight: 300; color: #fff;">—</span>
            </div>
            <button onclick="sendWhatsApp()" style="width: 100%; padding: 15px; background: #25d366; color: #fff; border: none; border-radius: 6px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase; display: flex; align-items: center; justify-content: center; gap: 8px;">
                Confirmar via WhatsApp
            </button>
        </div>

        <!-- Rodapé duplicado removido -->
` : ''}
`);

  parts.push(`
    <!-- PÁGINA 12: WEDDING PORTFOLIO CAPA -->
    <section id="wedding-portfolio" class="slide portfolio-capa-slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: column; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Topo: Textos -->
        <div class="portfolio-capa-content" style="height: 50%; display: flex; flex-direction: row; width: 100%;">
            <!-- Lado Esquerdo: Manifesto -->
            <div class="portfolio-capa-text-box"
                style="flex: 2; padding: 6vh 6vw; display: flex; flex-direction: column; justify-content: center;">
                <div
                    style="font-family: var(--wedding-montserrat); font-size: 1.15rem; line-height: 1.6; color: #333; font-weight: 400;">
                    <p style="margin-bottom: 20px;">
                        Daqui a vinte anos, as fotos e o filme serão o caminho mais curto para vocês voltarem no tempo e sentirem exatamente o que foi o dia do casamento.
                    </p>
                    <p style="margin-bottom: 20px;">
                        A gente busca registrar a essência desse 'sim' e a fé que une vocês, focando na beleza real que acontece nos intervalos entre um protocolo e outro.
                    </p>
                    <p style="font-size: 1rem; color: #444;">
                        Mais do que um registro, é um olhar que busca o que está nas entrelinhas. Queremos que vocês vejam essas memórias e se reconheçam em cada frame.
                    </p>
                </div>
            </div>

            <!-- Lado Direito: Título -->
            <div class="portfolio-capa-title-box"
                style="flex: 1.5; padding: 6vh 4vw; display: flex; flex-direction: column; justify-content: center; position: relative;">
                <!-- Decorativo Superior Direito -->
                <div class="package-decor"
                    style="position: absolute; top: 0; right: 0; width: 60px; height: 80px; background: #dcdcdc;">
                </div>

                <h2 class="portfolio-capa-titulo"
                    style="font-family: var(--wedding-montserrat); font-size: 4.5rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase; line-height: 1; margin-bottom: 5px;">
                    WEDDING<br>PORTFOLIO</h2>
                <p class="portfolio-capa-subtitulo"
                    style="font-family: var(--wedding-montserrat); font-size: 1.4rem; font-weight: 400; color: #444; letter-spacing: 0.2em; text-transform: uppercase;">
                    VERSÕES DA HISTÓRIA</p>
            </div>
        </div>

        <!-- Base: Grid de Fotos -->
        <div class="portfolio-capa-img-box" style="height: 50%; width: 100%;">
            <img src="${raizUrl('/imagens-proposta-casamento/foto-section-11.png')}"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>
    </section>
`);

  parts.push(`
    <!-- PÁGINA 13: PORTFÓLIO PEDRO E VANESSA -->
    <section class="slide portfolio-slide"
        style="padding: 0; background: #000; display: flex; flex-direction: row; gap: 2px; overflow: hidden; height: 100vh; width: 100%;">

        <!-- Coluna Esquerda (Duas fotos empilhadas) -->
        <div class="portfolio-left-col" style="flex: 2; display: flex; flex-direction: column; gap: 2px; height: 100%;">
            <!-- Foto Cima -->
            <div class="reveal-item portfolio-img-item" style="flex: 1; position: relative; overflow: hidden;">
                <img src="${raizUrl('/imagens-proposta-casamento/foto-section-cima-12.png')}"
                    style="width: 100%; height: 100%; object-fit: cover;">
                <div class="portfolio-label" style="position: absolute; top: 40px; left: 40px; z-index: 10;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; color: #fff; letter-spacing: 0.15em; text-transform: uppercase; font-weight: 300; opacity: 0.8;">
                        PEDRO E VANESSA - BEFORE THE BLOOM
                    </p>
                </div>
            </div>
            <!-- Foto Baixo -->
            <div class="reveal-item portfolio-img-item" style="flex: 1; overflow: hidden;">
                <img src="${raizUrl('/imagens-proposta-casamento/foto-section-baixo-12.png')}"
                    style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </div>

        <!-- Coluna Direita (Foto inteira) -->
        <div class="reveal-item portfolio-right-col portfolio-img-item portfolio-img-v"
            style="flex: 1.1; overflow: hidden; height: 100%;">
            <img src="${raizUrl('/imagens-proposta-casamento/foto-section-direita-12.png')}"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>
    </section>

    <!-- PÁGINA 14: PORTFÓLIO GABRIEL E JULIA -->
    <section class="slide portfolio-slide"
        style="padding: 0; background: #000; display: flex; flex-direction: row; gap: 2px; overflow: hidden; height: 100vh; width: 100%;">

        <!-- Coluna Esquerda (Duas fotos empilhadas) -->
        <div class="portfolio-left-col"
            style="flex: 1.8; display: flex; flex-direction: column; gap: 2px; height: 100%;">
            <!-- Foto Cima -->
            <div class="reveal-item portfolio-img-item" style="flex: 1; overflow: hidden;">
                <img src="${raizUrl('/imagens-proposta-casamento/foto-section-cima-13.png')}"
                    style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <!-- Foto Baixo -->
            <div class="reveal-item portfolio-img-item" style="flex: 1.1; position: relative; overflow: hidden;">
                <img src="${raizUrl('/imagens-proposta-casamento/foto-section-baixo-13.png')}"
                    style="width: 100%; height: 100%; object-fit: cover;">
                <div class="portfolio-label" style="position: absolute; bottom: 40px; left: 40px; z-index: 10;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; color: #fff; letter-spacing: 0.15em; text-transform: uppercase; font-weight: 300; opacity: 0.8;">
                        GABRIEL E JULIA - PRÉ-WEDDING
                    </p>
                </div>
            </div>
        </div>

        <!-- Coluna Direita (Foto inteira) -->
        <div class="reveal-item portfolio-right-col portfolio-img-item"
            style="flex: 1; overflow: hidden; height: 100%;">
            <img src="${raizUrl('/imagens-proposta-casamento/foto-section-direita-13.png')}"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>
    </section>

    <!-- PÁGINA 15: PORTFÓLIO BRUNA E ROBSON -->
    <section class="slide portfolio-slide"
        style="padding: 0; background: #000; display: flex; flex-direction: row; gap: 2px; overflow: hidden; height: 100vh; width: 100%;">

        <!-- Coluna Esquerda (Duas fotos empilhadas) -->
        <div style="flex: 1.8; display: flex; flex-direction: column; gap: 2px; height: 100%;">
            <!-- Foto Cima -->
            <div class="reveal-item" style="flex: 1.1; overflow: hidden;">
                <img src="${raizUrl('/imagens-proposta-casamento/foto-section-14-cima.png')}"
                    style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <!-- Foto Baixo -->
            <div class="reveal-item" style="flex: 1; position: relative; overflow: hidden;">
                <img src="${raizUrl('/imagens-proposta-casamento/foto-section-14-baixo.png')}"
                    style="width: 100%; height: 100%; object-fit: cover;">
                <div style="position: absolute; bottom: 40px; left: 40px; z-index: 10;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; color: #fff; letter-spacing: 0.15em; text-transform: uppercase; font-weight: 300; opacity: 0.8;">
                        BRUNA E ROBSON - CASAMENTO CARTÓRIO
                    </p>
                </div>
            </div>
        </div>

        <!-- Coluna Direita (Foto inteira) -->
        <div class="reveal-item" style="flex: 1; overflow: hidden; height: 100%;">
            <img src="${raizUrl('/imagens-proposta-casamento/foto-section-14-direita.png')}"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>
    </section>

    <!-- PÁGINA 16: PORTFÓLIO CHRISTIAN E ALINE -->
    <section class="slide portfolio-slide"
        style="padding: 0; background: #000; display: flex; flex-direction: row; gap: 2px; overflow: hidden; height: 100vh; width: 100%;">

        <!-- Coluna Esquerda (Foto Vertical) -->
        <div class="reveal-item" style="flex: 1; height: 100%; overflow: hidden;">
            <img src="${raizUrl('/imagens-proposta-casamento/foto-section-15-esquerda.png')}"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>

        <!-- Coluna Direita (Composto) -->
        <div style="flex: 2.2; display: flex; flex-direction: column; gap: 2px; height: 100%;">
            <!-- Topo Direita -->
            <div class="reveal-item" style="flex: 1.2; position: relative; overflow: hidden;">
                <img src="${raizUrl('/imagens-proposta-casamento/foto-section-15-cima.png')}"
                    style="width: 100%; height: 100%; object-fit: cover;">
                <div style="position: absolute; bottom: 40px; right: 40px; z-index: 10;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; color: #fff; letter-spacing: 0.15em; text-transform: uppercase; font-weight: 300; opacity: 0.8;">
                        CHRISTIAN E ALINE - WEDDING DAY
                    </p>
                </div>
            </div>
            <!-- Base Direita (Duas fotos) -->
            <div style="flex: 1; display: flex; flex-direction: row; gap: 2px; height: 100%;">
                <div class="reveal-item" style="flex: 1; overflow: hidden;">
                    <img src="${raizUrl('/imagens-proposta-casamento/foto-section-15-baixo-esquerda.png')}"
                        style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div class="reveal-item" style="flex: 1; overflow: hidden;">
                    <img src="${raizUrl('/imagens-proposta-casamento/foto-section-15-baixo-direita.png')}"
                        style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            </div>
        </div>
    </section>
`);

  parts.push(`
    <!-- PÁGINA 17: OS OLHARES POR TRÁS DAS LENTES (EQUIPE) -->
    <section class="slide team-slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <!-- Cabeçalho -->
        <div class="reveal-item team-header" style="text-align: center; margin-bottom: 60px; z-index: 10;">
            <h2 class="team-title"
                style="font-family: var(--wedding-montserrat); font-size: 4rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase; margin-bottom: 10px;">
                OS OLHARES POR TRÁS DAS LENTES
            </h2>
            <p style="font-family: var(--wedding-montserrat); font-size: 1.2rem; color: #888; font-weight: 400;">
                A gente não está aqui só para operar câmeras. Estamos aqui para contar a história de vocês.
            </p>
        </div>

        <!-- Barra Decorativa Cinza -->
        <div class="team-decor-bar"
            style="position: absolute; top: 50%; left: 0; width: 100%; height: 120px; background: #dcdcdc; transform: translateY(-50%); z-index: 1;">
        </div>

        <!-- Grid da Equipe -->
        <div class="team-grid"
            style="display: flex; flex-direction: row; gap: 40px; z-index: 10; width: 90%; max-width: 1400px; justify-content: center;">

            <!-- Jeane -->
            <div class="reveal-item team-item" style="flex: 1; text-align: center;">
                <div style="width: 100%; aspect-ratio: 1/1; overflow: hidden; margin-bottom: 20px; border-radius: 2px;">
                    <img src="${raizUrl('/imagens-proposta-casamento/foto-section-16-jeane.png')}"
                        style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <h4
                    style="font-family: var(--wedding-montserrat); font-size: 1.1rem; font-weight: 700; color: #1a1a1a; margin-bottom: 5px;">
                    Jeane Poncem</h4>
                <p style="font-family: var(--wedding-montserrat); font-size: 0.85rem; color: #666; font-style: italic;">
                    Curadora de Sonhos &<br>Guardiã da Narrativa</p>
            </div>

            <!-- Wellington -->
            <div class="reveal-item team-item" style="flex: 1; text-align: center;">
                <div style="width: 100%; aspect-ratio: 1/1; overflow: hidden; margin-bottom: 20px; border-radius: 2px;">
                    <img src="${raizUrl('/imagens-proposta-casamento/foto-section-16-wellington.png')}"
                        style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <h4
                    style="font-family: var(--wedding-montserrat); font-size: 1.1rem; font-weight: 700; color: #1a1a1a; margin-bottom: 5px;">
                    Wellington Poncem</h4>
                <p style="font-family: var(--wedding-montserrat); font-size: 0.85rem; color: #666; font-style: italic;">
                    O Arquiteto de Emoções</p>
            </div>

            <!-- Isabelly -->
            <div class="reveal-item team-item" style="flex: 1; text-align: center;">
                <div style="width: 100%; aspect-ratio: 1/1; overflow: hidden; margin-bottom: 20px; border-radius: 2px;">
                    <img src="${raizUrl('/imagens-proposta-casamento/foto-section-16-isabelly.png')}"
                        style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <h4
                    style="font-family: var(--wedding-montserrat); font-size: 1.1rem; font-weight: 700; color: #1a1a1a; margin-bottom: 5px;">
                    Isabelly Gomes</h4>
                <p style="font-family: var(--wedding-montserrat); font-size: 0.85rem; color: #666; font-style: italic;">
                    A Curadora da Verdade</p>
            </div>

            <!-- Gabryel -->
            <div class="reveal-item team-item" style="flex: 1; text-align: center;">
                <div style="width: 100%; aspect-ratio: 1/1; overflow: hidden; margin-bottom: 20px; border-radius: 2px;">
                    <img src="${raizUrl('/imagens-proposta-casamento/foto-section-16-gabriel.png')}"
                        style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <h4
                    style="font-family: var(--wedding-montserrat); font-size: 1.1rem; font-weight: 700; color: #1a1a1a; margin-bottom: 5px;">
                    Gabryel Oliveira</h4>
                <p style="font-family: var(--wedding-montserrat); font-size: 0.85rem; color: #666; font-style: italic;">
                    O Contador de Instantes</p>
            </div>

        </div>
    </section>
`);

  parts.push(`
    <!-- PÁGINA 18: PROVA SOCIAL & COMPROMISSO -->
    <section class="slide depo-slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden; position: relative; height: 100vh; width: 100%;">

        <div class="depo-container"
            style="display: flex; flex-direction: row; width: 90%; max-width: 1200px; gap: 80px; z-index: 10;">
            <!-- Lado Esquerdo: Depoimentos -->
            <div class="depo-col-left" style="flex: 1.5;">
                <h2 class="depo-title"
                    style="font-family: var(--wedding-montserrat); font-size: 3rem; font-weight: 300; letter-spacing: 0.1em; text-transform: uppercase; color: #1a1a1a; line-height: 1.1; margin-bottom: 50px;">
                    O QUE DIZEM<br><span style="color: var(--wedding-gold);">NOSSOS CASAIS</span>
                </h2>

                <div class="reveal-item"
                    style="margin-bottom: 40px; border-left: 2px solid var(--wedding-gold); padding-left: 30px;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; font-weight: 300; line-height: 1.7; font-style: italic; margin-bottom: 15px; color: #444;">
                        "${dep01.texto}"
                    </p>
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em; color: var(--wedding-gold);">
                        — ${dep01.autor}
                    </p>
                </div>

                <div class="reveal-item" style="border-left: 2px solid var(--wedding-gold); padding-left: 30px;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 1rem; font-weight: 300; line-height: 1.7; font-style: italic; margin-bottom: 15px; color: #444;">
                        "${dep02.texto}"
                    </p>
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em; color: var(--wedding-gold);">
                        — ${dep02.autor}
                    </p>
                </div>
            </div>

            <!-- Lado Direito: Compromisso -->
            <div class="depo-col-right"
                style="flex: 1; background: #fff; padding: 50px; border-radius: 4px; display: flex; flex-direction: column; justify-content: center;">
                <h3
                    style="font-family: var(--wedding-montserrat); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.25em; text-transform: uppercase; color: var(--wedding-gold); margin-bottom: 40px; border-bottom: 1px solid #e5e5e5; padding-bottom: 20px;">
                    NOSSO COMPROMISSO
                </h3>

                <div class="reveal-item" style="margin-bottom: 30px;">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 0.7rem; font-weight: 400; text-transform: uppercase; letter-spacing: 0.15em; color: #888; margin-bottom: 8px;">
                        Prévias do Casamento</p>
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 2rem; font-weight: 300; letter-spacing: 0.05em; color: #1a1a1a;">
                        ${prazoPrevias}
                    </p>
                </div>

                <div class="reveal-item">
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 0.7rem; font-weight: 400; text-transform: uppercase; letter-spacing: 0.15em; color: #888; margin-bottom: 8px;">
                        Material Final</p>
                    <p
                        style="font-family: var(--wedding-montserrat); font-size: 2rem; font-weight: 300; letter-spacing: 0.05em; color: #1a1a1a;">
                        ${prazoFinal}
                    </p>
                </div>
            </div>
        </div>

        <!-- Fundo Decorativo -->
        <div
            style="position: absolute; top: 0; right: 0; width: 30%; height: 100%; background: linear-gradient(to right, transparent, rgba(197, 168, 128, 0.06));">
        </div>
    </section>
`);

  parts.push(`
    <!-- PÁGINA 19: VAMOS DAR O PRÓXIMO PASSO? -->
    <section class="slide contato-slide"
        style="padding: 0; background: #fff; display: flex; flex-direction: row; overflow: hidden; height: 100vh; width: 100%;">

        <!-- Lado Esquerdo: Conteúdo -->
        <div class="contato-col-text"
            style="flex: 1; padding: 8vh 6vw; display: flex; flex-direction: column; justify-content: center; background: #f4f4f4; position: relative;">
            <!-- Decorativos -->
            <div class="package-decor"
                style="position: absolute; top: 0; left: 0; width: 80px; height: 40px; background: #dcdcdc;"></div>
            <div class="package-decor"
                style="position: absolute; bottom: 0; left: 0; width: 40px; height: 80px; background: #dcdcdc;"></div>

            <h2 class="reveal-item contato-title"
                style="font-family: var(--wedding-montserrat); font-size: 4rem; font-weight: 300; letter-spacing: 0.05em; color: #1a1a1a; text-transform: uppercase; line-height: 1.1; margin-bottom: 40px;">
                VAMOS DAR O<br>PRÓXIMO PASSO?
            </h2>

            <!-- Contatos -->
            <div class="reveal-item"
                style="margin-bottom: 40px; font-family: var(--wedding-montserrat); font-size: 1.2rem; line-height: 2; color: #1a1a1a;">
                <a href="${waLink}" target="_blank"
                    style="display: block; text-decoration: none; color: #1a1a1a; transition: all 0.3s;">
                    <span style="border-bottom: 1px solid #ccc;">${whatsappNumero}</span>
                </a>
                <a href="mailto:${emailContato}"
                    style="display: block; text-decoration: none; color: #1a1a1a; transition: all 0.3s;">
                    ${emailContato}
                </a>
                <a href="https://instagram.com/${instagramHandle.replace('@', '')}" target="_blank"
                    style="display: block; text-decoration: none; color: #1a1a1a; transition: all 0.3s;">
                    ${instagramHandle}
                </a>
            </div>

            <!-- Texto de Apoio -->
            <div class="reveal-item"
                style="font-family: var(--wedding-montserrat); font-size: 0.95rem; line-height: 1.8; color: #555; font-style: italic; max-width: 480px;">
                <p style="margin-bottom: 20px;">
                    Se algo aqui ainda não bateu com o que vocês imaginaram, vamos conversar. Estamos prontos para ajustar o que for preciso para que esse registro seja tão único quanto a história de vocês.
                </p>
                <p style="margin-bottom: 20px;">
                    Este é o primeiro capítulo oficial de <strong>${nomeCasal}</strong>. Nossa meta é garantir que o arrepio do 'sim' dure para sempre, guardado com o carinho e o olhar que o momento pede.
                </p>
                <p
                    style="font-size: 0.8rem; color: var(--wedding-gold); font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 10px;">
                    * Proposta válida por ${validadeProposta} dias.
                </p>
            </div>

            <!-- Footer -->
            <div
                style="margin-top: 60px; opacity: 0.3; font-family: var(--wedding-montserrat); font-size: 0.7rem; letter-spacing: 0.3em; text-transform: uppercase;">
                Distinto Wedding © ${ano}
            </div>
        </div>

        <!-- Lado Direito: Imagem -->
        <div class="reveal-item contato-col-img" style="flex: 1.2; height: 100%; position: relative;">
            <img src="${raizUrl('/imagens-proposta-casamento/foto-section-18.png')}"
                style="width: 100%; height: 100%; object-fit: cover;">
            <!-- Elemento decorativo topo direito -->
            <div class="package-decor"
                style="position: absolute; top: 0; right: 0; width: 60px; height: 100px; background: #dcdcdc; opacity: 0.8;">
            </div>
        </div>
    </section>

    <!-- PÁGINA 19: THANK YOU -->
    <section class="slide thanks-slide"
        style="padding: 0; background: #f4f4f4; display: flex; flex-direction: column; overflow: hidden; height: 100vh; width: 100%;">

        <!-- Topo: Mensagem de Agradecimento -->
        <div class="thanks-header"
            style="height: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 0 4vw;">
            <h2 class="reveal-item thanks-title"
                style="font-family: var(--wedding-montserrat); font-size: 6rem; font-weight: 300; letter-spacing: 0.1em; color: #1a1a1a; text-transform: uppercase; margin-bottom: 20px;">
                THANK YOU
            </h2>
            <p class="reveal-item thanks-subtitle"
                style="font-family: var(--wedding-montserrat); font-size: 1.4rem; color: #333; font-weight: 400; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 10px;">
                GUARDANDO CADA DETALHE DO JEITO QUE ELE ACONTECEU
            </p>
            <p class="reveal-item"
                style="font-family: var(--wedding-montserrat); font-size: 0.9rem; color: #888; letter-spacing: 0.1em;">
                by Distinto
            </p>
        </div>

        <!-- Base: Imagem Panorâmica -->
        <div class="reveal-item" style="height: 50%; width: 100%; overflow: hidden;">
            <img src="${raizUrl('/imagens-proposta-casamento/foto-section-19.png')}"
                style="width: 100%; height: 100%; object-fit: cover;">
        </div>
    </section>

</div>
`);

  return parts.join('');
}