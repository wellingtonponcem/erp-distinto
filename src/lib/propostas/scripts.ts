/**
 * Scripts JS da apresentação pública de propostas (port fiel dos <script> do
 * template-casamento.php e do p.php). Retornam strings de JavaScript prontas
 * para serem injetadas como elementos <script> no Next.js.
 *
 * Atenção: os strings abaixo usam template literals; crases internas (\`) e
 * interpolações literais (\${) do JavaScript são escapadas para aparecerem
 * literalmente no output.
 */
import { esc } from '@/lib/propostas/common';

// Escapa para interpolação segura dentro de innerHTML gerado via JS
function escJs(v: unknown): string { return esc(v); }

export interface InvestimentoCtx {
  slug: string;
  dados: Record<string, any>;
  servicosWedding: Record<string, any>;
  planosWedding: any[];
  condHC: string;
  condE: string;
}

/**
 * Port do <script> da PÁGINA 11 (Escolha seu Pacote — Interativo) do
 * template-casamento.php (linhas 1807-2043). Define selectPlan, renderServicesList,
 * renderRow, toggleDynamicService, atualizarResumo, renderResumoLinha e sendWhatsApp,
 * e seleciona o primeiro plano disponível por padrão.
 */
export function investimentoScript(ctx: InvestimentoCtx): string {
  const { slug, dados, servicosWedding, planosWedding, condHC, condE } = ctx;

  const allServices = JSON.stringify(servicosWedding ?? {});

  const presets: any = {};
  for (const p of planosWedding || []) {
    const cond = p.id === 'heritage' || p.id === 'cinematic' ? condHC : condE;
    let servicos: any = {};
    if (p.itens_json && typeof p.itens_json === 'object') {
      servicos = p.itens_json;
    } else if (typeof p.itens_json === 'string' && p.itens_json) {
      try { servicos = JSON.parse(p.itens_json); } catch (e) { servicos = {}; }
    }
    presets[p.id] = {
      nome: p.nome,
      valorBase: Number(p.preco_venda) || 0,
      condicoes: cond || (p.prazo_minimo > 0 ? `Saldo parcelado em até ${p.prazo_minimo}x` : 'Condições sob consulta'),
      servicos,
      showBoudoir: !!p.show_boudoir,
      showPrewedding: !!p.show_prewedding,
      valorBoudoir: Number(dados.valor_boudoir || 500),
      valorPrewedding: Number(dados.valor_prewedding || 1100),
      extraUpgrades: p.extra_upgrades || [],
      customItems: p.custom_items || [],
    };
  }
  const planPresetsJson = JSON.stringify(presets);

  return `(function () {
                const allServices = ${allServices};
                const planPresets = ${planPresetsJson};

                let selectedPlan = null;
                let activeUpgrades = {};

                const fmt = (v) => 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                const escHtml = (s) => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');

                window.selectPlan = function (id) {
                    selectedPlan = id;
                    activeUpgrades = {}; 

                    document.querySelectorAll('.plan-card').forEach(c => {
                        c.style.borderColor = 'rgba(255,255,255,0.1)';
                        c.style.background = 'rgba(255,255,255,0.03)';
                        c.querySelector('.badge-selecionado').style.display = 'none';
                        c.querySelector('.plan-radio').style.borderColor = 'rgba(255,255,255,0.25)';
                        c.querySelector('.plan-radio-dot').style.opacity = '0';
                    });
                    const card = document.getElementById('plan-' + id);
                    if (card) {
                        card.style.borderColor = 'var(--wedding-gold)';
                        card.style.background = 'rgba(197,168,128,0.07)';
                        card.querySelector('.badge-selecionado').style.display = 'block';
                        card.querySelector('.plan-radio').style.borderColor = 'var(--wedding-gold)';
                        card.querySelector('.plan-radio-dot').style.opacity = '1';
                    }

                    renderServicesList();
                    atualizarResumo();
                };

                function renderServicesList() {
                    const container = document.getElementById('servicos-dinamicos-container');
                    const plan = planPresets[selectedPlan];
                    if (!plan) return;
                    container.innerHTML = '';

                    // 1. Upgrades Estaticos (Boudoir e Prewedding)
                    if (plan.showBoudoir) {
                        renderRow(container, 'boudoir_static', 'Boudoir da Noiva', plan.valorBoudoir, true);
                    }
                    if (plan.showPrewedding) {
                        renderRow(container, 'prewedding_static', 'Ensaio Pré-Wedding', plan.valorPrewedding, true);
                    }

                    // 2. Upgrades Dinamicos selecionados no Admin
                    if (plan.extraUpgrades) {
                        Object.entries(plan.extraUpgrades).forEach(([upgId, active]) => {
                            if (active) {
                                const s = allServices[upgId];
                                if (s) {
                                    renderRow(container, upgId, s.nome, s.valor, true);
                                }
                            }
                        });
                    }

                    // 3. Outros Servicos Dinamicos do Plano (do banco)
                    if (Array.isArray(plan.customItems)) {
                        plan.customItems.forEach((item, idx) => {
                            if (!item || !item.nome || String(item.incluido ?? '1') === '0') return;
                            renderRow(container, \`custom_\${idx}\`, item.nome, Number(item.valor || 0), false, item.descricao || '');
                        });
                    }

                    Object.entries(plan.servicos).forEach(([sId, status]) => {
                        const s = allServices[sId];
                        if (!s) return;
                        renderRow(container, sId, s.nome, s.valor, status === 'opcional');
                    });
                }

                function renderRow(container, id, nome, valor, isOptional, descricao = '') {
                    const div = document.createElement('div');
                    div.className = 'service-item-row';
                    div.style = \`display: flex; align-items: center; gap: 14px; padding: 12px 16px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.07); background: rgba(255,255,255,0.02); margin-bottom: 10px; transition: all 0.3s; \${!isOptional ? 'opacity: 0.8;' : ''}\`;
                    
                    div.innerHTML = \`
                        <div style="flex: 1;">
                            <p style="font-size: 0.75rem; font-weight: 500; color: rgba(255,255,255,0.82); margin: 0 0 1px; text-transform: uppercase; letter-spacing: 0.04em;">\${escHtml(nome)}</p>
                            <p style="font-size: 0.72rem; font-weight: 300; color: rgba(255,255,255,0.45); margin: 0;">\${isOptional ? fmt(valor) : 'Já incluso no pacote'}</p>
                        </div>
                        \${isOptional ? \`
                            <span style="font-size: 0.55rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--wedding-gold); border: 1px solid var(--wedding-gold); padding: 2px 8px; border-radius: 20px; opacity: 0.8;">Opcional</span>
                            <div class="toggle-track \${activeUpgrades[id] ? 'on' : ''}" onclick="toggleDynamicService('\${id}')">
                                <div class="toggle-thumb"></div>
                            </div>
                        \` : \`
                            <span style="font-size: 0.55rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #10b981; border: 1px solid #10b981; padding: 2px 8px; border-radius: 20px;">✓ Incluso</span>
                        \`}
                    \`;
                    container.appendChild(div);
                }

                window.toggleDynamicService = function(sId) {
                    activeUpgrades[sId] = !activeUpgrades[sId];
                    renderServicesList(); 
                    atualizarResumo();
                };

                function atualizarResumo() {
                    if (!selectedPlan) return;
                    const plan = planPresets[selectedPlan];
                    const resumoCont = document.getElementById('resumo-linhas');
                    resumoCont.innerHTML = '';

                    let total = plan.valorBase;

                    // 1. Plano Base
                    renderResumoLinha(resumoCont, plan.nome, plan.valorBase);

                    // 2. Upgrades Estaticos
                    if (activeUpgrades['boudoir_static']) {
                        renderResumoLinha(resumoCont, '+ Boudoir da Noiva', plan.valorBoudoir);
                        total += plan.valorBoudoir;
                    }
                    if (activeUpgrades['prewedding_static']) {
                        renderResumoLinha(resumoCont, '+ Ensaio Pré-Wedding', plan.valorPrewedding);
                        total += plan.valorPrewedding;
                    }

                    // 3. Upgrades Dinamicos
                    Object.entries(activeUpgrades).forEach(([sId, active]) => {
                        if (active && !sId.endsWith('_static')) {
                            const s = allServices[sId];
                            if (s) {
                                total += s.valor;
                                renderResumoLinha(resumoCont, '+ ' + s.nome, s.valor);
                            }
                        }
                    });

                    const totalText = fmt(total);
                    document.getElementById('total-display').textContent = totalText;
                    document.getElementById('total-display-mobile').textContent = totalText;

                    document.getElementById('condicoes-display').textContent = plan.condicoes;
                    document.getElementById('condicoes-display').style.color = 'rgba(255,255,255,0.7)';

                    const btnWA = document.getElementById('whatsapp-btn');
                    btnWA.style.opacity = '1';
                    btnWA.style.pointerEvents = 'auto';
                }

                function renderResumoLinha(container, nome, valor) {
                    const div = document.createElement('div');
                    div.className = 'linha-upgrade';
                    div.style = "display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.06);";
                    div.innerHTML = \`<span style="font-size: 0.78rem; color: rgba(255,255,255,0.7);">\${escHtml(nome)}</span><span style="font-size: 0.78rem; color: rgba(255,255,255,0.7);">\${escHtml(fmt(valor))}</span>\`;
                    container.appendChild(div);
                }

                window.sendWhatsApp = async function () {
                    if (!selectedPlan) return;

                    const plan = planPresets[selectedPlan];
                    let total = plan.valorBase;
                    let msg = \`Olá! Gostaria de confirmar meu interesse na proposta de casamento:\\n\\n\`;
                    msg += \`*PLANO BASE:* \${plan.nome} (\${fmt(plan.valorBase)})\\n\`;

                    // 1. Upgrades Estaticos
                    if (activeUpgrades['boudoir_static']) {
                        msg += \`*+ Boudoir da Noiva* (\${fmt(plan.valorBoudoir)})\\n\`;
                        total += plan.valorBoudoir;
                    }
                    if (activeUpgrades['prewedding_static']) {
                        msg += \`*+ Ensaio Pré-Wedding* (\${fmt(plan.valorPrewedding)})\\n\`;
                        total += plan.valorPrewedding;
                    }

                    // 2. Upgrades Dinamicos
                    Object.entries(activeUpgrades).forEach(([sId, active]) => {
                        if (active && !sId.endsWith('_static')) {
                            const s = allServices[sId];
                            if (s) {
                                msg += \`*+ \${s.nome}* (\${fmt(s.valor)})\\n\`;
                                total += s.valor;
                            }
                        }
                    });

                    msg += \`\\n*INVESTIMENTO TOTAL:* \${fmt(total)}\\n\`;
                    msg += \`\\nAguardo o retorno para os próximos passos!\`;

                    try {
                        await fetch('/api/propostas/publica', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                slug: ${JSON.stringify(slug)},
                                plano_id: selectedPlan,
                                extras: Object.entries(activeUpgrades).filter(([, active]) => active).map(([id]) => id),
                                condicoes: plan.condicoes
                            })
                        });
                    } catch (error) {
                        console.warn('Nao foi possivel registrar a escolha automaticamente.', error);
                    }

                    const encodedMsg = encodeURIComponent(msg);
                    const url = \`https://wa.me/5527988586935?text=\${encodedMsg}\`;
                    window.open(url, '_blank');
                };

                // Selecionar o primeiro plano disponivel por padrao
                const availablePlans = Object.keys(planPresets);
                if (availablePlans.length > 0) {
                    window.selectPlan(availablePlans[0]);
                }
            })();
`;
}

/**
 * Port dos <script> finais do template-casamento.php (linhas 2483-2550):
 * inicialização de ícones, observer de reveal-item, bloqueio de clique direito/arraste
 * e as funções openInteractiveModal/closeInteractiveModal do modal #slide-pacote.
 */
export function casamentoClosingScript(): string {
  return `
    if (window.lucide) lucide.createIcons();

    document.addEventListener('DOMContentLoaded', () => {

        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const items = entry.target.querySelectorAll('.reveal-item');
                    items.forEach((item, index) => {
                        setTimeout(() => {
                            item.classList.add('active');
                        }, index * 150);
                    });
                }
            });
        }, observerOptions);

        document.querySelectorAll('.slide').forEach(slide => {
            observer.observe(slide);
        });

        document.addEventListener('contextmenu', (e) => {
            if (e.target.tagName === 'IMG') {
                e.preventDefault();
                return false;
            }
        }, false);

        document.addEventListener('dragstart', (e) => {
            if (e.target.tagName === 'IMG') {
                e.preventDefault();
                return false;
            }
        }, false);

        window.openInteractiveModal = function () {
            const modal = document.getElementById('slide-pacote');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('modal-open');
                const container = document.querySelector('.wedding-proposal');
                if (container) container.style.overflow = 'hidden';
                if (window.lucide) lucide.createIcons();
            }
        };

        window.closeInteractiveModal = function () {
            const modal = document.getElementById('slide-pacote');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('modal-open');
                const container = document.querySelector('.wedding-proposal');
                if (container) container.style.overflowY = 'scroll';
            }
        };

        // Listener nativo defensivo do botão flutuante (mirror do onclick="window.openInteractiveModal()"
        // do p.php:188). Garante o funcionamento mesmo se o onClick do React não for anexado
        // (ex.: falha de hidratação regenerando a árvore).
        const approveBtn = document.getElementById('btn-approve');
        if (approveBtn && approveBtn.tagName === 'BUTTON') {
            approveBtn.addEventListener('click', function (e) {
                if (typeof window.openInteractiveModal === 'function') {
                    e.preventDefault();
                    window.openInteractiveModal();
                }
            });
        }
    });
`;
}

export interface PlanModalParams {
  slug: string;
  nomeCasal: string;
  pHeritage: number;
  pCinematic: number;
  pEssencial: number;
  pBoudoir: number;
  pPrewedding: number;
  condHC: string;
  condE: string;
}

/**
 * Port do script do modal de escolha de pacote do p.php (linhas 374-494).
 * Define openPlanModal (e o alias openInteractiveModal), closePlanModal, mSelectPlan,
 * mToggle, mRefresh e mEnviar.
 */
export function planModalScript(params: PlanModalParams): string {
  const {
    slug, nomeCasal, pHeritage, pCinematic, pEssencial,
    pBoudoir, pPrewedding, condHC, condE,
  } = params;

  return `(function () {
        const mNomeCasal = ${JSON.stringify(nomeCasal)};
        const mSlug      = ${JSON.stringify(slug)};
        const WA_NUMBER  = '5527988586935';

        const mPlanData = {
            heritage:  { nome: 'Experiência Heritage',  valor: ${pHeritage},  cond: ${JSON.stringify(condHC)} },
            cinematic: { nome: 'Experiência Cinematic', valor: ${pCinematic}, cond: ${JSON.stringify(condHC)} },
            essencial: { nome: 'Registro Essencial',    valor: ${pEssencial}, cond: ${JSON.stringify(condE)}  },
        };
        const mUpgradeData = { boudoir: ${pBoudoir}, prewedding: ${pPrewedding} };
        let mSelected = null;
        const mUpgrades = { boudoir: false, prewedding: false };

        function fmtBRL(v) {
            return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }

        window.openPlanModal = function () {
            document.getElementById('plan-modal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        };
        window.openInteractiveModal = window.openPlanModal; // alias usado pelo botão flutuante
        window.closePlanModal = function () {
            document.getElementById('plan-modal').style.display = 'none';
            document.body.style.overflow = '';
        };

        window.mSelectPlan = function (key) {
            mSelected = key;
            document.querySelectorAll('.m-plan-card').forEach(card => {
                const on = card.dataset.plan === key;
                card.style.borderColor = on ? '#c5a880' : 'rgba(255,255,255,0.1)';
                card.style.background  = on ? 'rgba(197,168,128,0.08)' : 'rgba(255,255,255,0.03)';
                card.querySelector('.m-radio').style.borderColor     = on ? '#c5a880' : 'rgba(255,255,255,0.25)';
                card.querySelector('.m-radio-dot').style.opacity     = on ? '1' : '0';
            });
            mRefresh();
        };

        window.mToggle = function (key) {
            mUpgrades[key] = !mUpgrades[key];
            const track = document.getElementById('m-toggle-' + key);
            const thumb = track.querySelector('.m-thumb');
            if (mUpgrades[key]) {
                track.style.background = '#c5a880';
                thumb.style.left = '19px';
            } else {
                track.style.background = 'rgba(255,255,255,0.12)';
                thumb.style.left = '3px';
            }
            mRefresh();
        };

        function mRefresh() {
            let total = mSelected ? mPlanData[mSelected].valor : 0;
            if (mUpgrades.boudoir)    total += mUpgradeData.boudoir;
            if (mUpgrades.prewedding) total += mUpgradeData.prewedding;

            document.getElementById('m-total').textContent = mSelected ? fmtBRL(total) : '—';
            document.getElementById('m-cond').textContent  = mSelected ? mPlanData[mSelected].cond : '';

            const btn = document.getElementById('m-send-btn');
            btn.disabled = !mSelected;
            btn.style.opacity = mSelected ? '1' : '0.5';
            btn.style.cursor  = mSelected ? 'pointer' : 'not-allowed';

            const hint = btn.nextElementSibling;
            if (hint) hint.style.display = mSelected ? 'none' : 'block';
        }

        window.mEnviar = async function () {
            if (!mSelected) return;
            const p = mPlanData[mSelected];
            let total = p.valor;
            let linhas = 'Plano: ' + p.nome + ' — ' + fmtBRL(p.valor);
            const extrasEnviados = [];
            if (mUpgrades.boudoir) {
                total += mUpgradeData.boudoir;
                linhas += '\\nUpgrade Boudoir — ' + fmtBRL(mUpgradeData.boudoir);
                extrasEnviados.push('boudoir_static');
            }
            if (mUpgrades.prewedding) {
                total += mUpgradeData.prewedding;
                linhas += '\\nUpgrade Pré-Wedding — ' + fmtBRL(mUpgradeData.prewedding);
                extrasEnviados.push('prewedding_static');
            }

            const btnSend = document.getElementById('m-send-btn');
            const originalBtnText = btnSend ? btnSend.innerHTML : '';
            if (btnSend) {
                btnSend.disabled = true;
                btnSend.innerHTML = 'Gravando escolha...';
            }

            try {
                await fetch('/api/propostas/publica', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        slug: mSlug,
                        plano_id: mSelected,
                        extras: extrasEnviados,
                        condicoes: p.cond
                    })
                });
            } catch (error) {
                console.warn('Não foi possível gravar a escolha automaticamente.', error);
            } finally {
                if (btnSend) {
                    btnSend.disabled = false;
                    btnSend.innerHTML = originalBtnText;
                }
            }

            const msg = 'Olá! Somos ' + mNomeCasal + ' e gostaríamos de confirmar nosso interesse na proposta da Distinto.\\n\\n' + linhas + '\\n\\nTotal: ' + fmtBRL(total) + '\\n\\nRef: ' + mSlug;
            window.open('https://wa.me/' + WA_NUMBER + '?text=' + encodeURIComponent(msg), '_blank');
        };
    })();
    `;
}

/**
 * Port do script inline do p.php (linhas 233-267): expõe PROPOSTA_SLUG e a lógica do
 * botão flutuante (casamento). O lucide.createIcons() é executado via useEffect no React.
 */
export function publicInlineScript(slug: string, isCasamento: boolean): string {
  return `
        window.PROPOSTA_SLUG = ${JSON.stringify(slug)};

        ${isCasamento ? `
        // Lógica do Botão Flutuante Dinâmico
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('btn-approve');
            const scrollContainer = document.querySelector('.wedding-proposal');
            let scrollTimeout;

            function showFloatingButton() {
                if (!btn) return;
                
                btn.classList.add('visible');
                
                // Limpa o timeout anterior se o usuário continuar scrollando
                clearTimeout(scrollTimeout);
                
                // Define o timeout de 2 segundos para desaparecer
                scrollTimeout = setTimeout(() => {
                    btn.classList.remove('visible');
                }, 2000);
            }

            // Ouvir scroll tanto na janela quanto no container de snap (se existir)
            window.addEventListener('scroll', showFloatingButton, true);
            if (scrollContainer) {
                scrollContainer.addEventListener('scroll', showFloatingButton, true);
            }
        });
        ` : ''}
    `;
}