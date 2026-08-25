import React, { useState, useEffect } from 'react';

interface LgpdConsentProps {
  checkboxChecked?: boolean;
  onCheckboxChange?: (checked: boolean) => void;
  showCheckboxField?: boolean;
  requiredError?: string;
}

export const LgpdConsent: React.FC<LgpdConsentProps> = ({
  checkboxChecked = false,
  onCheckboxChange,
  showCheckboxField = true,
  requiredError,
}) => {
  const [modalOpen, setModalOpen] = useState(false);
  const [cookieAccepted, setCookieAccepted] = useState(true);

  useEffect(() => {
    if (typeof window !== 'undefined') {
      const consent = localStorage.getItem('lgpd_cookie_consent_distinto');
      if (!consent) {
        setCookieAccepted(false);
      }
    }
  }, []);

  const handleAcceptCookies = () => {
    if (typeof window !== 'undefined') {
      localStorage.setItem('lgpd_cookie_consent_distinto', 'accepted');
    }
    setCookieAccepted(true);
  };

  return (
    <>
      {/* 1. CHECKBOX DE CONSENTIMENTO LGPD (PARA FORMULÁRIOS) */}
      {showCheckboxField && (
        <div className="my-6 p-4 rounded-xl bg-zinc-900/80 border border-zinc-800 space-y-2 select-none text-left">
          <label className="flex items-start space-x-3 cursor-pointer">
            <input
              type="checkbox"
              checked={checkboxChecked}
              onChange={(e) => onCheckboxChange && onCheckboxChange(e.target.checked)}
              className="mt-1 w-4 h-4 rounded border-zinc-700 bg-zinc-950 text-[#c5a880] focus:ring-[#c5a880] focus:ring-offset-zinc-900 cursor-pointer"
            />
            <span className="text-xs text-zinc-300 leading-relaxed">
              Li e concordo com os{' '}
              <button
                type="button"
                onClick={() => setModalOpen(true)}
                className="text-[#c5a880] underline font-semibold hover:text-[#d4b78f] transition"
              >
                Termos de Uso e Política de Privacidade (LGPD)
              </button>
              . Autorizo o tratamento dos meus dados pessoais (nome, e-mail, telefone e detalhes do evento) exclusivamente para a elaboração de propostas e atendimento comercial da <strong>Distinto (Poncem Studio LTDA)</strong>, nos termos da Lei nº 13.709/2018. <span className="text-[#c5a880] font-bold">*</span>
            </span>
          </label>

          {requiredError && (
            <span className="block text-xs font-semibold text-red-400 pl-7">
              {requiredError}
            </span>
          )}
        </div>
      )}

      {/* 2. BANNER INFERIOR DE COOKIES & PRIVACIDADE */}
      {!cookieAccepted && (
        <div className="fixed bottom-0 inset-x-0 z-[9999] p-4 bg-zinc-950/95 border-t border-zinc-800 text-white backdrop-blur-md shadow-2xl transition-all">
          <div className="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <div className="flex items-center space-x-3 text-xs text-zinc-300">
              <span className="material-symbols-outlined text-[#c5a880] text-2xl shrink-0">shield</span>
              <p>
                Utilizamos cookies e tecnologias necessárias para garantir a melhor experiência, navegação e segurança dos seus dados em nossas páginas públicas. Ao continuar navegando, você concorda com a nossa{' '}
                <button
                  type="button"
                  onClick={() => setModalOpen(true)}
                  className="text-[#c5a880] underline font-semibold hover:text-[#d4b78f]"
                >
                  Política de Privacidade (LGPD)
                </button>
                .
              </p>
            </div>
            <div className="flex items-center space-x-3 shrink-0">
              <button
                type="button"
                onClick={() => setModalOpen(true)}
                className="px-3.5 py-2 text-xs text-zinc-400 hover:text-white font-semibold transition"
              >
                Saber Mais
              </button>
              <button
                type="button"
                onClick={handleAcceptCookies}
                className="px-5 py-2 bg-[#c5a880] hover:bg-[#d4b78f] text-zinc-950 font-bold rounded-xl text-xs transition shadow-md"
              >
                Aceitar e Continuar
              </button>
            </div>
          </div>
        </div>
      )}

      {/* 3. MODAL COMPLETO DA POLÍTICA DE PRIVACIDADE E DIREITOS LGPD */}
      {modalOpen && (
        <div className="fixed inset-0 z-[10000] bg-black/85 backdrop-blur-md flex items-center justify-center p-4">
          <div className="bg-zinc-900 border border-zinc-800 text-zinc-200 rounded-2xl max-w-2xl w-full max-h-[85vh] flex flex-col shadow-2xl overflow-hidden font-sans">
            {/* Modal Header */}
            <div className="p-5 border-b border-zinc-800 flex items-center justify-between bg-zinc-950/80">
              <div className="flex items-center space-x-2">
                <span className="material-symbols-outlined text-[#c5a880]">gavel</span>
                <h3 className="font-bold text-white text-base">Política de Privacidade & Proteção de Dados (LGPD)</h3>
              </div>
              <button
                type="button"
                onClick={() => setModalOpen(false)}
                className="p-1.5 text-zinc-400 hover:text-white hover:bg-zinc-800 rounded-xl transition"
              >
                <span className="material-symbols-outlined text-lg leading-none">close</span>
              </button>
            </div>

            {/* Modal Content */}
            <div className="p-6 overflow-y-auto space-y-5 text-xs leading-relaxed text-zinc-300 font-sans">
              <div className="p-3 bg-zinc-950 rounded-xl border border-zinc-800/80 text-zinc-400">
                <p className="font-bold text-white mb-1">Empresa Controladora dos Dados:</p>
                <p>Distinto | Poncem Studio (Poncem Studio LTDA)</p>
                <p>CNPJ: 50.768.732/0001-63 | E-mail DPO: <span className="text-[#c5a880]">contato@wedistinto.com</span></p>
                <p>Sede: Rod. do Sol nº 2780, Sala 1307, Praia de Itaparica, Vila Velha - ES</p>
              </div>

              <div>
                <h4 className="font-bold text-white text-sm mb-1 text-[#c5a880]">1. Compromisso com a LGPD</h4>
                <p>
                  A <strong>Distinto (Poncem Studio LTDA)</strong> reitera seu compromisso com a transparência, segurança e privacidade dos dados pessoais de seus clientes, noivos e parceiros comercialmente atendidos, atuando em estrita conformidade com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018 - LGPD).
                </p>
              </div>

              <div>
                <h4 className="font-bold text-white text-sm mb-1 text-[#c5a880]">2. Dados Pessoais Coletados</h4>
                <p>Coletamos exclusivamente os dados necessários para o atendimento comercial e execução de serviços de fotografia e audiovisual:</p>
                <ul className="list-disc pl-5 mt-1 space-y-1 text-zinc-400">
                  <li><strong>Dados de Identificação:</strong> Nome do casal, CPF/CNPJ, e-mail, telefone/WhatsApp e endereço residencial.</li>
                  <li><strong>Dados do Evento:</strong> Data prevista do casamento, locais da cerimônia e festa, cronograma e preferências do registro.</li>
                </ul>
              </div>

              <div>
                <h4 className="font-bold text-white text-sm mb-1 text-[#c5a880]">3. Finalidade e Base Legal</h4>
                <p>Os dados fornecidos são utilizados estritamente para:</p>
                <ul className="list-disc pl-5 mt-1 space-y-1 text-zinc-400">
                  <li>Elaboração de propostas comerciais personalizadas e orçamentos (Art. 7º, V da LGPD).</li>
                  <li>Confecção e assinatura digital de contratos fotográficos (Art. 7º, V da LGPD).</li>
                  <li>Emissão de cobranças, boletos e notas fiscais de serviço.</li>
                  <li>Contato comercial direto via WhatsApp ou e-mail relativo ao evento.</li>
                </ul>
              </div>

              <div>
                <h4 className="font-bold text-white text-sm mb-1 text-[#c5a880]">4. Seus Direitos como Titular dos Dados (Art. 18 LGPD)</h4>
                <p>Você possui o direito de solicitar a qualquer momento:</p>
                <ul className="list-disc pl-5 mt-1 space-y-1 text-zinc-400">
                  <li>Confirmação da existência de tratamento e acesso aos seus dados.</li>
                  <li>Correção de dados incompletos, inexatos ou desatualizados.</li>
                  <li>Eliminação dos dados pessoais ou revogação do consentimento concedido.</li>
                </ul>
                <p className="mt-2">
                  Para exercer seus direitos, basta entrar em contato com nosso encarregado de proteção de dados pelo e-mail <strong>contato@wedistinto.com</strong>.
                </p>
              </div>

              <div>
                <h4 className="font-bold text-white text-sm mb-1 text-[#c5a880]">5. Segurança da Informação</h4>
                <p>
                  Adotamos medidas técnicas e administrativas aptas a proteger seus dados contra acessos não autorizados, perdas ou alterações ilícitas. Não comercializamos nem compartilhamos seus dados com terceiros não envolvidos na prestação dos serviços contratados.
                </p>
              </div>
            </div>

            {/* Modal Footer */}
            <div className="p-4 border-t border-zinc-800 flex justify-end bg-zinc-950/80">
              <button
                type="button"
                onClick={() => setModalOpen(false)}
                className="px-5 py-2 bg-[#c5a880] hover:bg-[#d4b78f] text-zinc-950 font-bold rounded-xl text-xs transition"
              >
                Compreendi e Concordo
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
};
