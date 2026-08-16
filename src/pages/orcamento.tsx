import { useMemo, useState } from 'react';
import Head from 'next/head';
import {
  quoteForm,
  validateQuoteForm,
  normalizeValues,
  FormSection,
  FormField,
} from '@/lib/propostas/form-orcamento';

const HERO_IMG = '/imagens-proposta-casamento/bg-section-01.jpg';
const LOGO = '/assets/distinto_logo.svg';

export default function OrcamentoPage() {
  const [values, setValues] = useState<Record<string, string | string[]>>({});
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [enviando, setEnviando] = useState(false);
  const [enviado, setEnviado] = useState(false);
  const [erroEnvio, setErroEnvio] = useState('');

  const flatFields = useMemo(() => {
    const list: FormField[] = [];
    for (const section of quoteForm.sections) {
      for (const field of section.fields) list.push(field);
    }
    return list;
  }, []);

  const fieldById = useMemo(() => {
    const map: Record<string, FormField> = {};
    for (const f of flatFields) map[f.id] = f;
    return map;
  }, [flatFields]);

  const getVal = (id: string): string => {
    const v = values[id];
    return Array.isArray(v) ? '' : (v || '');
  };

  const setVal = (id: string, v: string) => {
    setValues((prev) => ({ ...prev, [id]: v }));
    setErrors((prev) => {
      const next = { ...prev };
      delete next[id];
      return next;
    });
  };

  const setArrayVal = (id: string, option: string, checked: boolean) => {
    setValues((prev) => {
      const cur = Array.isArray(prev[id]) ? (prev[id] as string[]) : [];
      const next = checked ? [...cur, option] : cur.filter((o) => o !== option);
      return { ...prev, [id]: next };
    });
    setErrors((prev) => {
      const next = { ...prev };
      delete next[id];
      return next;
    });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErroEnvio('');

    const validationErrors = validateQuoteForm(values);
    if (validationErrors.length > 0) {
      const errMap: Record<string, string> = {};
      for (const er of validationErrors) errMap[er.id] = er.message;
      setErrors(errMap);
      const first = flatFields.find((f) => errMap[f.id]);
      if (first) {
        document.getElementById(first.id)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      return;
    }

    setEnviando(true);
    try {
      const res = await fetch('/api/orcamentos/solicitar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(normalizeValues(values)),
      });
      const data = await res.json();
      if (res.ok && data.ok) {
        setEnviado(true);
      } else {
        setErroEnvio(data.erro || 'Não foi possível enviar. Tente novamente.');
      }
    } catch (err: any) {
      setErroEnvio(err.message || 'Erro de conexão. Tente novamente.');
    } finally {
      setEnviando(false);
    }
  };

  const renderField = (field: FormField) => {
    const val = getVal(field.id);
    const err = errors[field.id];

    if (field.type === 'radio') {
      return (
        <div className="orc-options">
          {(field.options || []).map((opt) => (
            <label key={opt} className={`orc-option ${val === opt ? 'selected' : ''}`}>
              <input
                type="radio"
                name={field.id}
                value={opt}
                checked={val === opt}
                onChange={() => setVal(field.id, opt)}
              />
              <span>{opt}</span>
            </label>
          ))}
        </div>
      );
    }

    if (field.type === 'checkbox') {
      const checked = Array.isArray(values[field.id]) ? (values[field.id] as string[]) : [];
      return (
        <div className="orc-options">
          {(field.options || []).map((opt) => (
            <label key={opt} className={`orc-option ${checked.includes(opt) ? 'selected' : ''}`}>
              <input
                type="checkbox"
                checked={checked.includes(opt)}
                onChange={(e) => setArrayVal(field.id, opt, e.target.checked)}
              />
              <span>{opt}</span>
            </label>
          ))}
        </div>
      );
    }

    if (field.type === 'select') {
      return (
        <select
          id={field.id}
          className="orc-input"
          value={val}
          onChange={(e) => setVal(field.id, e.target.value)}
        >
          <option value="">Selecione uma opção</option>
          {(field.options || []).map((opt) => (
            <option key={opt} value={opt}>{opt}</option>
          ))}
        </select>
      );
    }

    if (field.type === 'textarea') {
      return (
        <textarea
          id={field.id}
          className="orc-input orc-textarea"
          value={val}
          onChange={(e) => setVal(field.id, e.target.value)}
          rows={4}
        />
      );
    }

    return (
      <input
        id={field.id}
        type={field.type === 'tel' ? 'tel' : field.type === 'email' ? 'email' : field.type === 'date' ? 'date' : field.type === 'number' ? 'number' : 'text'}
        className="orc-input"
        value={val}
        onChange={(e) => setVal(field.id, e.target.value)}
        placeholder={field.type === 'date' ? '' : 'Digite aqui...'}
      />
    );
  };

  if (enviado) {
    return (
      <>
        <Head>
          <title>{quoteForm.form_title}</title>
          <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        </Head>
        <div className="orc-page">
          <div className="orc-success">
            <div className="orc-success-badge">✿</div>
            <h1>Recebemos sua solicitação!</h1>
            <p>
              Obrigado por entrar em contato com a <strong>We Distinto</strong>. Em breve
              enviaremos uma proposta personalizada para o seu grande dia, no e-mail e WhatsApp
              informados.
            </p>
            <a href="/" className="orc-back">← Voltar ao site</a>
          </div>
          <style jsx global>{globalStyles}</style>
        </div>
      </>
    );
  }

  return (
    <>
      <Head>
        <title>{quoteForm.form_title}</title>
        <meta name="description" content={quoteForm.form_description} />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
        <link
          href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Montserrat:wght@300;400;500;600;700&family=Dancing+Script:wght@400;600;700&display=swap"
          rel="stylesheet"
        />
      </Head>

      <div className="orc-page">
        {/* Header */}
        <header className="orc-hero">
          <div className="orc-hero-bg" style={{ backgroundImage: `url(${HERO_IMG})` }} />
          <div className="orc-hero-overlay" />
          <div className="orc-hero-content">
            <img src={LOGO} alt="Distinto" className="orc-logo" />
            <p className="orc-kicker">FOTOGRAFIA DE CASAMENTO</p>
            <h1 className="orc-title">
              Solicitação de <em>Orçamento</em>
            </h1>
            <p className="orc-subtitle">{quoteForm.form_description}</p>
          </div>
        </header>

        {/* Form */}
        <main className="orc-main">
          <form onSubmit={handleSubmit} noValidate>
            {quoteForm.sections.map((section: FormSection) => (
              <section key={section.section_name} className="orc-section">
                <div className="orc-section-header">
                  <span className="orc-section-line" />
                  <h2>{section.section_name}</h2>
                </div>

                <div className="orc-section-body">
                  {section.fields.map((field) => {
                    const showConditional =
                      field.id === 'nome_locais' && getVal('locais_definidos') !== 'Sim';
                    if (showConditional) return null;

                    const err = errors[field.id];
                    return (
                      <div className="orc-field" key={field.id}>
                        <label htmlFor={field.id}>
                          {field.label}
                          {field.required && <span className="orc-required">*</span>}
                        </label>
                        {renderField(field)}
                        {err && <span className="orc-error">{err}</span>}
                      </div>
                    );
                  })}
                </div>
              </section>
            ))}

            {erroEnvio && <div className="orc-submit-error">{erroEnvio}</div>}

            <div className="orc-submit-wrap">
              <button type="submit" className="orc-submit" disabled={enviando}>
                {enviando ? 'Enviando...' : 'Enviar Solicitação'}
              </button>
              <p className="orc-privacy">
                Seus dados estão seguros e serão usados apenas para elaborar sua proposta.
              </p>
            </div>
          </form>
        </main>

        <footer className="orc-footer">
          <span>WE DISTINTO</span>
          <span>© {new Date().getFullYear()} — Fotografia de Casamento</span>
        </footer>
      </div>

      <style jsx global>{globalStyles}</style>
    </>
  );
}

const globalStyles = `
  .orc-page {
    background: #0a0a0a;
    color: #eaeaea;
    min-height: 100vh;
    font-family: 'Montserrat', sans-serif;
    -webkit-font-smoothing: antialiased;
  }
  .orc-hero {
    position: relative;
    height: 62vh;
    min-height: 460px;
    display: flex;
    align-items: flex-end;
    overflow: hidden;
  }
  .orc-hero-bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
  }
  .orc-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, rgba(10,10,10,0.45) 0%, rgba(10,10,10,0.55) 55%, #0a0a0a 100%);
  }
  .orc-hero-content {
    position: relative;
    z-index: 2;
    max-width: 820px;
    margin: 0 auto;
    width: 100%;
    padding: 48px 28px 56px;
    text-align: center;
  }
  .orc-logo { height: 34px; margin: 0 auto 20px; opacity: 0.92; display: block; }
  .orc-kicker {
    font-size: 11px;
    letter-spacing: 0.42em;
    color: #c5a880;
    font-weight: 600;
    margin: 0 0 14px;
    text-transform: uppercase;
  }
  .orc-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(2.2rem, 6vw, 3.6rem);
    font-weight: 400;
    line-height: 1.1;
    margin: 0 0 16px;
    color: #fff;
  }
  .orc-title em {
    font-family: 'Dancing Script', cursive;
    color: #c5a880;
    font-style: normal;
    font-weight: 600;
  }
  .orc-subtitle {
    font-size: 15px;
    font-weight: 300;
    line-height: 1.7;
    color: rgba(255,255,255,0.78);
    max-width: 520px;
    margin: 0 auto;
  }
  .orc-main {
    max-width: 760px;
    margin: 0 auto;
    padding: 8px 24px 40px;
  }
  .orc-section {
    margin-bottom: 44px;
  }
  .orc-section-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 24px;
  }
  .orc-section-line {
    width: 34px;
    height: 1px;
    background: #c5a880;
    flex-shrink: 0;
  }
  .orc-section-header h2 {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    font-weight: 500;
    color: #fff;
    letter-spacing: 0.02em;
    margin: 0;
  }
  .orc-section-body {
    display: flex;
    flex-direction: column;
    gap: 22px;
  }
  .orc-field label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #cfcfcf;
    margin-bottom: 10px;
    line-height: 1.5;
  }
  .orc-required { color: #c5a880; margin-left: 4px; }
  .orc-input {
    width: 100%;
    background: #121212;
    border: 1px solid #2a2a2a;
    border-radius: 12px;
    padding: 14px 16px;
    font-size: 14px;
    font-family: 'Montserrat', sans-serif;
    color: #fff;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
  }
  .orc-input:focus {
    border-color: #c5a880;
    box-shadow: 0 0 0 3px rgba(197,168,128,0.14);
  }
  .orc-input::placeholder { color: #555; }
  .orc-textarea { resize: vertical; min-height: 96px; line-height: 1.6; }
  select.orc-input {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23c5a880' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
    padding-right: 42px;
  }
  select.orc-input option { background: #121212; color: #fff; }
  input[type="date"].orc-input::-webkit-calendar-picker-indicator { filter: invert(0.7); }
  .orc-options { display: flex; flex-wrap: wrap; gap: 10px; }
  .orc-option {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #121212;
    border: 1px solid #2a2a2a;
    border-radius: 999px;
    padding: 10px 18px;
    cursor: pointer;
    font-size: 13px;
    color: #cfcfcf;
    transition: all 0.2s;
    user-select: none;
  }
  .orc-option:hover { border-color: #444; }
  .orc-option.selected {
    background: rgba(197,168,128,0.14);
    border-color: #c5a880;
    color: #f0e6d6;
  }
  .orc-option input { display: none; }
  .orc-error {
    display: block;
    font-size: 12px;
    color: #ff8a80;
    margin-top: 7px;
  }
  .orc-submit-error {
    background: rgba(255,138,128,0.08);
    border: 1px solid rgba(255,138,128,0.35);
    color: #ff8a80;
    font-size: 13px;
    padding: 14px 16px;
    border-radius: 12px;
    margin-bottom: 22px;
  }
  .orc-submit-wrap { text-align: center; padding: 10px 0 30px; }
  .orc-submit {
    background: #c5a880;
    color: #1a1a1a;
    border: none;
    border-radius: 999px;
    padding: 16px 44px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.25s;
  }
  .orc-submit:hover:not(:disabled) { background: #d4b78f; transform: translateY(-2px); }
  .orc-submit:disabled { opacity: 0.55; cursor: not-allowed; }
  .orc-privacy {
    font-size: 11px;
    color: #666;
    margin: 16px auto 0;
    max-width: 360px;
    line-height: 1.6;
  }
  .orc-footer {
    border-top: 1px solid #1c1c1c;
    padding: 26px 20px 36px;
    text-align: center;
    font-size: 11px;
    letter-spacing: 0.24em;
    color: #555;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .orc-footer span:first-child { color: #c5a880; font-weight: 600; }

  .orc-success {
    max-width: 560px;
    margin: 0 auto;
    padding: 18vh 24px;
    text-align: center;
  }
  .orc-success-badge {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    border: 1px solid #c5a880;
    color: #c5a880;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    margin: 0 auto 26px;
  }
  .orc-success h1 {
    font-family: 'Playfair Display', serif;
    font-weight: 500;
    font-size: 28px;
    color: #fff;
    margin: 0 0 16px;
  }
  .orc-success p {
    font-size: 15px;
    line-height: 1.8;
    color: #b5b5b5;
  }
  .orc-success strong { color: #c5a880; }
  .orc-back {
    display: inline-block;
    margin-top: 26px;
    color: #c5a880;
    font-size: 13px;
    text-decoration: none;
    letter-spacing: 0.05em;
  }
  .orc-back:hover { text-decoration: underline; }

  @media (max-width: 640px) {
    .orc-hero { height: 56vh; min-height: 400px; }
    .orc-hero-content { padding: 36px 18px 40px; }
    .orc-main { padding: 8px 18px 30px; }
  }
`;
