/**
 * Suporte compartilhado dos wizards administrativos de Propostas
 * (port de includes/layout/head.php + helpers dos gerenciamento/proposta_*.php).
 */

/** Emula htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'). */
export function esc(v: unknown): string {
  const s = v === null || v === undefined ? '' : String(v);
  return s
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/** Emula sanitizar(): htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8'). */
export function sanitizar(v: unknown): string {
  return esc(String(v === null || v === undefined ? '' : v).trim());
}

/**
 * Emula jsonParaJs(): json_encode com JSON_UNESCAPED_UNICODE | JSON_HEX_TAG |
 * JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT e fallback para 'null'.
 */
export function jsonParaJs(valor: unknown): string {
  let json: string;
  try {
    json = JSON.stringify(valor);
  } catch (e) {
    return 'null';
  }
  if (json === undefined) return 'null';
  return json
    .replace(/</g, '\\u003c')
    .replace(/>/g, '\\u003e')
    .replace(/&/g, '\\u0026')
    .replace(/'/g, '\\u0027')
    .replace(/"/g, '\\u0022');
}

/** Emula obterBeneficiosTexto(): benefícios JSON -> texto multilinha. */
export function obterBeneficiosTexto(pkg: any): string {
  if (!pkg || !pkg.beneficios_json) return '';
  try {
    const arr = JSON.parse(pkg.beneficios_json);
    if (Array.isArray(arr)) return arr.join('\n');
    return String(pkg.beneficios_json);
  } catch (e) {
    return String(pkg.beneficios_json);
  }
}

/** Mapeia o nome de um pacote wedding para chave (heritage/cinematic/essencial). */
export function pkgIdFromNome(nome: string): string {
  const slug = String(nome || '').toLowerCase();
  if (slug.includes('heritage')) return 'heritage';
  if (slug.includes('cinematic')) return 'cinematic';
  return 'essencial';
}

/** Sufixo flag (Heritage/Cinematic/Essencial) a partir do nome do pacote. */
export function pkgSuffix(nome: string): 'Heritage' | 'Cinematic' | 'Essencial' {
  const slug = String(nome || '').toLowerCase();
  if (slug.includes('heritage')) return 'Heritage';
  if (slug.includes('cinematic')) return 'Cinematic';
  return 'Essencial';
}

/**
 * CSS global do admin (port do bloco <style> de includes/layout/head.php,
 * limitado ao necessário para os wizards + layout de modal).
 */
export const ADMIN_CSS = `
* { box-sizing: border-box; }
html { background: #050505; }
body {
  min-height: 100vh;
  margin: 0;
  background: #050505;
  color: #eaeaea;
  font-family: 'Hanken Grotesk', Arial, sans-serif;
  overflow-x: hidden;
}
#app-wrapper {
  position: relative;
  z-index: 1;
  display: flex;
  min-height: 100vh;
  background: #050505;
  padding: 16px;
  gap: 16px;
}
#app-wrapper.is-modal-layout {
  padding: 0;
  background: #050505;
}
#main-content,
.content-sheet {
  flex: 1;
  min-width: 0;
  min-height: calc(100vh - 32px);
  margin: 0;
  padding: 30px 34px !important;
  overflow-y: auto;
  max-width: none !important;
  background: #050505;
  color: #eaeaea;
  border: 0;
  box-shadow: none;
}
.is-modal-layout #main-content,
.is-modal-layout .content-sheet {
  min-height: 100vh;
  border-radius: 0;
  background: #050505;
}
.app-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  margin: -6px 0 26px;
  padding: 0 0 18px;
  border-bottom: 1px solid #eeeeee;
}
.top-nav {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
  overflow-x: auto;
}
.top-nav a {
  flex: 0 0 auto;
  color: #222222;
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  padding: 8px 10px;
  border-radius: 999px;
}
.top-nav a:hover { background: #f4f4f4; }
.page-title {
  font-size: 25px;
  font-weight: 800;
  letter-spacing: -0.04em;
  color: #111111;
}
.page-subtitle {
  margin-top: 4px;
  color: #8a8a8a;
  font-size: 13px;
  font-weight: 500;
}
.card {
  background: #ffffff;
  border: 1px solid #ececec;
  border-radius: 12px;
  box-shadow: 0 1px 0 rgba(0,0,0,0.02);
  transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
}
.card:hover {
  border-color: #dddddd;
  box-shadow: 0 16px 30px rgba(0,0,0,0.05);
}
.btn-primary,
.btn-secondary {
  min-height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 9px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 800;
  text-decoration: none;
  border: 1px solid transparent;
  cursor: pointer;
  transition: transform 0.18s ease, background 0.18s ease, border-color 0.18s ease;
}
.btn-primary {
  background: #111111;
  color: #ffffff;
  border-color: #111111;
}
.btn-primary:hover { background: #000000; transform: translateY(-1px); }
.btn-secondary {
  background: #ffffff;
  color: #111111;
  border-color: #eeeeee;
}
.btn-secondary:hover { background: #f7f7f7; border-color: #dddddd; }
.input,
.select {
  width: 100%;
  min-height: 40px;
  padding: 9px 12px;
  border: 1px solid #e5e5e5;
  border-radius: 9px;
  background: #ffffff;
  color: #111111;
  font-size: 13px;
  outline: none;
}
.input:focus,
.select:focus {
  border-color: #111111;
  box-shadow: 0 0 0 3px rgba(0,0,0,0.06);
}
.label {
  display: block;
  margin-bottom: 6px;
  color: #555555;
  font-size: 12px;
  font-weight: 800;
}
[x-cloak] { opacity: 0; }
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.18); border-radius: 999px; }
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(148, 125, 255, 0.25) transparent;
}
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.15); border-radius: 10px; }
.material-symbols-outlined {
  font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
  vertical-align: middle;
  line-height: 1;
}
@media (max-width: 760px) {
  #app-wrapper { display: block; padding: 0; }
  #main-content,
  .content-sheet { min-height: auto; margin: 10px; border-radius: 22px; }
  .app-topbar { align-items: flex-start; flex-direction: column; }
}
/* Estilos Premium Distinto */
.label-premium {
  display: block;
  margin-bottom: 8px;
  color: rgba(0, 0, 0, 0.7);
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.input-readonly {
  background: rgba(0, 0, 0, 0.02) !important;
  border: 1px dashed rgba(0, 0, 0, 0.1) !important;
  cursor: not-allowed;
  color: #666 !important;
}
.card-plan {
  border: 2px solid transparent;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.card-plan-active {
  border-color: #d4af37 !important;
  background: rgba(212, 175, 55, 0.03) !important;
  transform: scale(1.01);
  box-shadow: 0 10px 30px rgba(212, 175, 55, 0.1) !important;
}
.section-header-premium {
  font-size: 18px;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: #111;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.contract-block {
  padding: 16px;
  background: #fcfcfc;
  border: 1px solid #eee;
  border-radius: 12px;
  font-size: 13px;
  line-height: 1.6;
  color: #555;
}
.switch {
  position: relative;
  display: inline-block;
  width: 38px;
  height: 20px;
}
.switch input { opacity: 0; width: 0; height: 0; }
.slider {
  position: absolute;
  cursor: pointer;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: rgba(255,255,255,0.1);
  transition: .4s;
  border-radius: 20px;
  border: 1px solid rgba(255,255,255,0.1);
}
.slider:before {
  position: absolute;
  content: "";
  height: 14px;
  width: 14px;
  left: 2px;
  bottom: 2px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
input:checked + .slider { background-color: #d4af37; border-color: #d4af37; }
input:checked + .slider:before { transform: translateX(18px); }
.upgrade-card {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
}
.upgrade-card:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(212, 175, 55, 0.3);
}
/* Layout modal (iframe dentro do PropostasView) */
.is-modal-layout #main-content {
  margin-left: 0 !important;
  padding-top: 0 !important;
  background: transparent !important;
  color: white !important;
}
.is-modal-layout .page-title {
  font-size: 1.5rem;
  color: white !important;
}
.is-modal-layout .card {
  background: rgba(255, 255, 255, 0.03) !important;
  border: 1px solid rgba(255, 255, 255, 0.05) !important;
  backdrop-filter: blur(10px);
}
.is-modal-layout .label {
  color: rgba(255, 255, 255, 0.5) !important;
}
.is-modal-layout .input {
  background: rgba(255, 255, 255, 0.05) !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
  color: white !important;
}
.is-modal-layout .input::placeholder {
  color: rgba(255, 255, 255, 0.2) !important;
}
.is-modal-layout .label-premium {
  color: rgba(255, 255, 255, 0.55) !important;
}
.is-modal-layout .section-header-premium {
  color: white !important;
}
.is-modal-layout select option,
.is-modal-layout .input option {
  background-color: #111111 !important;
  color: #ffffff !important;
}
`;

/** Scripts de terceiros usados pelos wizards (Alpine, collapse, lucide, flatpickr). */
export const WIZARD_HEAD_SCRIPTS = `
<script defer src="/assets/js/alpine.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/pt.js"></script>
<script>
if (localStorage.getItem('dark-mode') === 'true' || (!localStorage.getItem('dark-mode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
  document.documentElement.classList.add('dark');
}
</script>
`;