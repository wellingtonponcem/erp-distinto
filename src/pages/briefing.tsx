import { useEffect, useState } from 'react';
import Head from 'next/head';
import { useRouter } from 'next/router';
import { briefingLogisticoConfig, BriefingSection, BriefingField } from '@/lib/propostas/form-briefing';

const HERO_IMG = '/imagens-proposta-casamento/bg-section-01.jpg';
const LOGO = '/assets/distinto_logo.svg';

export default function BriefingPage() {
  const router = useRouter();
  const [values, setValues] = useState<Record<string, string>>({});
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [enviando, setEnviando] = useState(false);
  const [enviado, setEnviado] = useState(false);
  const [erroEnvio, setErroEnvio] = useState('');
  const [nomeClienteUrl, setNomeClienteUrl] = useState('');

  useEffect(() => {
    if (router.isReady) {
      const rawVal = router.query.cliente || router.query.noivos || router.query.casal || '';
      const clienteQuery = Array.isArray(rawVal) ? rawVal[0] : String(rawVal || '');
      if (clienteQuery) {
        setNomeClienteUrl(clienteQuery);
        setValues((prev) => ({ ...prev, nome_noivos: clienteQuery }));
      }
    }
  }, [router.isReady, router.query]);

  const getVal = (id: string): string => values[id] || '';

  const setVal = (id: string, v: string) => {
    setValues((prev) => ({ ...prev, [id]: v }));
    setErrors((prev) => {
      const next = { ...prev };
      delete next[id];
      return next;
    });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErroEnvio('');

    // Validação de campos obrigatórios
    const errMap: Record<string, string> = {};
    for (const section of briefingLogisticoConfig.sections) {
      for (const field of section.fields) {
        if (field.required && !getVal(field.id).trim()) {
          errMap[field.id] = 'Este campo é de preenchimento obrigatório.';
        }
      }
    }

    if (Object.keys(errMap).length > 0) {
      setErrors(errMap);
      const firstId = Object.keys(errMap)[0];
      document.getElementById(firstId)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    setEnviando(true);
    try {
      const res = await fetch('/api/briefings/enviar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(values),
      });
      const data = await res.json();
      if (res.ok && data.ok) {
        setEnviado(true);
      } else {
        setErroEnvio(data.erro || 'Não foi possível enviar o briefing. Tente novamente.');
      }
    } catch (err: any) {
      setErroEnvio(err.message || 'Erro de conexão com o servidor. Tente novamente.');
    } finally {
      setEnviando(false);
    }
  };

  const renderField = (field: BriefingField) => {
    const val = getVal(field.id);

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

    if (field.type === 'textarea') {
      return (
        <textarea
          id={field.id}
          className="orc-input orc-textarea"
          value={val}
          onChange={(e) => setVal(field.id, e.target.value)}
          placeholder={field.placeholder || 'Digite aqui com o máximo de detalhes...'}
          rows={3}
        />
      );
    }

    return (
      <input
        id={field.id}
        type={field.type === 'time' ? 'time' : field.type === 'date' ? 'date' : field.type === 'tel' ? 'tel' : 'text'}
        className="orc-input"
        value={val}
        onChange={(e) => setVal(field.id, e.target.value)}
        placeholder={field.placeholder || ''}
      />
    );
  };

  if (enviado) {
    return (
      <>
        <Head>
          <title>Briefing Recebido com Sucesso | Distinto</title>
          <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        </Head>
        <div className="orc-page">
          <div className="orc-success">
            <div className="orc-success-badge">📸</div>
            <h1>Briefing Logístico Recebido!</h1>
            <p>
              Muito obrigado, <strong>{getVal('nome_noivos') || nomeClienteUrl || 'queridos noivos'}</strong>!
              Com essas informações, nossa equipe irá organizar todos os detalhes do seu grande dia para garantir registros perfeitos e inesquecíveis.
            </p>
            <p className="mt-4 text-xs text-amber-300 font-mono">
              Qualquer alteração ou dúvida, nossa equipe está inteiramente à disposição! ✨
            </p>
          </div>
          <style jsx global>{globalStyles}</style>
        </div>
      </>
    );
  }

  return (
    <>
      <Head>
        <title>{briefingLogisticoConfig.form_title} | Distinto</title>
        <meta name="description" content={briefingLogisticoConfig.form_description} />
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
            <p className="orc-kicker">FOTOGRAFIA & CINEMA DE CASAMENTO</p>

            {nomeClienteUrl ? (
              <div className="mb-4">
                <span className="orc-greeting">Olá, {nomeClienteUrl}! 🥂</span>
                <h1 className="orc-title mt-2">
                  Briefing Logístico do <em>Seu Casamento</em>
                </h1>
              </div>
            ) : (
              <h1 className="orc-title">
                Briefing Logístico de <em>Casamento</em>
              </h1>
            )}

            <p className="orc-subtitle">{briefingLogisticoConfig.form_description}</p>
          </div>
        </header>

        {/* Form */}
        <main className="orc-main">
          <form onSubmit={handleSubmit} noValidate>
            {briefingLogisticoConfig.sections.map((section: BriefingSection) => (
              <section key={section.section_name} className="orc-section">
                <div className="orc-section-header">
                  <span className="orc-section-line" />
                  <h2>{section.section_name}</h2>
                </div>

                {section.description && (
                  <p className="text-xs text-amber-200/80 mb-4 font-light italic">{section.description}</p>
                )}

                <div className="orc-section-body">
                  {section.fields.map((field) => {
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
                {enviando ? 'Enviando Briefing...' : 'Enviar Briefing Logístico'}
              </button>
              <p className="orc-privacy">
                Suas respostas serão salvas com segurança para a organização da equipe de fotografia e vídeo do dia.
              </p>
            </div>
          </form>
        </main>

        <footer className="orc-footer">
          <span>DISTINTO | PONCEM STUDIO</span>
          <span>© {new Date().getFullYear()} — Fotografia & Cinema de Casamento</span>
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
    height: 55vh;
    min-height: 420px;
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
    background: linear-gradient(to bottom, rgba(10,10,10,0.45) 0%, rgba(10,10,10,0.65) 55%, #0a0a0a 100%);
  }
  .orc-hero-content {
    position: relative;
    z-index: 2;
    max-width: 820px;
    margin: 0 auto;
    width: 100%;
    padding: 44px 28px 48px;
    text-align: center;
  }
  .orc-logo { height: 34px; margin: 0 auto 16px; opacity: 0.92; display: block; }
  .orc-kicker {
    font-size: 11px;
    letter-spacing: 0.42em;
    color: #c5a880;
    font-weight: 600;
    margin: 0 0 12px;
    text-transform: uppercase;
  }
  .orc-greeting {
    font-family: 'Dancing Script', cursive;
    font-size: 2rem;
    color: #c5a880;
    display: inline-block;
  }
  .orc-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(2rem, 5.5vw, 3.2rem);
    font-weight: 400;
    line-height: 1.15;
    margin: 0 0 14px;
    color: #fff;
  }
  .orc-title em {
    font-family: 'Dancing Script', cursive;
    color: #c5a880;
    font-style: normal;
    font-weight: 600;
  }
  .orc-subtitle {
    font-size: 14px;
    font-weight: 300;
    line-height: 1.6;
    color: rgba(255,255,255,0.78);
    max-width: 580px;
    margin: 0 auto;
  }
  .orc-main {
    max-width: 780px;
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
  .orc-textarea { resize: vertical; min-height: 88px; line-height: 1.6; }
  input[type="date"].orc-input::-webkit-calendar-picker-indicator,
  input[type="time"].orc-input::-webkit-calendar-picker-indicator { filter: invert(0.7); }
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
    max-width: 400px;
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
    max-width: 580px;
    margin: 0 auto;
    padding: 18vh 24px;
    text-align: center;
  }
  .orc-success-badge {
    width: 76px;
    height: 76px;
    border-radius: 50%;
    border: 1px solid #c5a880;
    color: #c5a880;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
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

  @media (max-width: 640px) {
    .orc-hero { height: 50vh; min-height: 380px; }
    .orc-hero-content { padding: 32px 18px 36px; }
    .orc-main { padding: 8px 18px 30px; }
  }
`;
