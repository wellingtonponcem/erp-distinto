import { GetServerSideProps } from 'next';
import Head from 'next/head';
import { query, queryOne } from '@/lib/db';
import { ADMIN_CSS } from '@/lib/propostas/wizard';
import { buildEditarWizard, WizardEditarData } from '@/lib/propostas/wizard-editar';
import DOMPurify from 'isomorphic-dompurify';

interface EditarPageProps {
  wizard: ReturnType<typeof buildEditarWizard>;
}

export default function PropostaEditarPage({ wizard }: EditarPageProps) {
  if (!wizard) return <div className="p-8 text-gray-500 font-sans">Carregando formulário...</div>;

  return (
    <>
      <Head>
        <meta charSet="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{wizard.title || 'Editar Proposta'} — ERP Distinto</title>

        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="apple-touch-icon" sizes="180x180" href="/favicon_io/apple-touch-icon.png" />
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon_io/favicon-32x32.png" />
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon_io/favicon-16x16.png" />
        <link rel="manifest" href="/favicon_io/site.webmanifest" />

        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
        <link
          href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap"
          rel="stylesheet"
        />

        <link href="/assets/css/tailwind.css" rel="stylesheet" />
        <style dangerouslySetInnerHTML={{ __html: ADMIN_CSS + (wizard.style || '') }} />

        <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
        <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/dark.css" />
        <script src="https://cdn.jsdelivr.net/npm/flatpickr" />
        <script src="https://npmcdn.com/flatpickr/dist/l10n/pt.js" />
      </Head>

      {(wizard.scripts || []).map((s, i) => (
        <script key={i} dangerouslySetInnerHTML={{ __html: s }} />
      ))}

      <script src="/assets/js/alpine.min.js" />
      <script src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js" />

      <script
        dangerouslySetInnerHTML={{
          __html: `
            setTimeout(function() {
              document.querySelectorAll('[x-cloak]').forEach(function(el) {
                el.removeAttribute('x-cloak');
              });
            }, 300);
          `,
        }}
      />

      <div dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(wizard.html || '') }} suppressHydrationWarning />
    </>
  );
}

export const getServerSideProps: GetServerSideProps<EditarPageProps> = async (context) => {
  try {
    const id = String(context.query?.id ?? '');
    const isModal = (context.query?.layout ?? '') === 'modal';

    const [proposta, clientes, oportunidades, fornecedores, servicos] = await Promise.all([
      id ? queryOne(`SELECT * FROM propostas WHERE id = $1 LIMIT 1`, [id]).catch(() => null) : null,
      query(`SELECT id, nome FROM clientes ORDER BY nome ASC`).catch(() => []),
      query(`SELECT id, nome, cliente_id FROM oportunidades ORDER BY previsao ASC`).catch(() => []),
      query(`SELECT id, nome, categoria FROM fornecedores ORDER BY nome ASC`).catch(() => []),
      query(
        `SELECT id, nome, descricao, preco_venda, preco_venda_pontual, periodicidade, categoria, tipo, subtitulo, beneficios_json, condicoes_comerciais FROM servicos WHERE ativo = 1 ORDER BY nome ASC`
      ).catch(() => []),
    ]);

    let dadosJson: any = {};
    if (proposta && proposta.dados_json) {
      try {
        const parsed = JSON.parse(proposta.dados_json);
        if (parsed && typeof parsed === 'object') dadosJson = parsed;
      } catch (e) {}
    }

    const wizardData: WizardEditarData = {
      isModal,
      id,
      proposta: proposta || {},
      dadosJson,
      clientes: (clientes as any[]) || [],
      oportunidades: (oportunidades as any[]) || [],
      fornecedores: (fornecedores as any[]) || [],
      servicos: (servicos as any[]) || [],
    };

    return {
      props: {
        wizard: buildEditarWizard(wizardData),
      },
    };
  } catch (e) {
    return {
      props: {
        wizard: buildEditarWizard({
          isModal: false,
          id: '',
          proposta: {},
          dadosJson: {},
          clientes: [],
          oportunidades: [],
          fornecedores: [],
          servicos: [],
        }),
      },
    };
  }
};