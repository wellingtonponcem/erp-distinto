export type FieldType = 'text' | 'tel' | 'email' | 'date' | 'number' | 'radio' | 'checkbox' | 'select' | 'textarea';

export interface FormField {
  id: string;
  label: string;
  type: FieldType;
  required: boolean;
  options?: string[];
}

export interface FormSection {
  section_name: string;
  fields: FormField[];
}

export interface QuoteFormConfig {
  form_title: string;
  form_description: string;
  sections: FormSection[];
}

export const quoteForm: QuoteFormConfig = {
  form_title: 'Solicitação de Orçamento - Fotografia de Casamento',
  form_description: 'Preencha os dados abaixo para receber uma proposta personalizada para o seu grande dia.',
  sections: [
    {
      section_name: 'Dados de Contato',
      fields: [
        {
          id: 'nome_contato',
          label: 'Qual é o seu nome e o nome do(a) seu/sua noivo(a)?',
          type: 'text',
          required: true,
        },
        {
          id: 'telefone_whatsapp',
          label: 'Seu WhatsApp (com DDD)',
          type: 'tel',
          required: true,
        },
        {
          id: 'email',
          label: 'Seu melhor e-mail',
          type: 'email',
          required: true,
        },
      ],
    },
    {
      section_name: 'Detalhes do Evento',
      fields: [
        {
          id: 'data_casamento',
          label: 'Qual a data prevista do casamento?',
          type: 'date',
          required: true,
        },
        {
          id: 'cidade_estado',
          label: 'Em qual cidade e estado será o casamento?',
          type: 'text',
          required: true,
        },
        {
          id: 'locais_definidos',
          label: 'Já definiram os locais da cerimônia e recepção?',
          type: 'radio',
          options: ['Sim', 'Ainda estamos buscando'],
          required: true,
        },
        {
          id: 'nome_locais',
          label: 'Se sim, quais são os locais?',
          type: 'text',
          required: false,
        },
        {
          id: 'numero_convidados',
          label: 'Quantos convidados são esperados (aproximadamente)?',
          type: 'number',
          required: false,
        },
        {
          id: 'cerimonialista',
          label: 'Vocês já têm cerimonialista/assessoria? Se sim, qual o nome?',
          type: 'text',
          required: false,
        },
      ],
    },
    {
      section_name: 'Interesses e Perfil',
      fields: [
        {
          id: 'servicos_interesse',
          label: 'Quais serviços vocês têm interesse?',
          type: 'checkbox',
          options: [
            'Cobertura do Casamento',
            'Ensaio Pré-Wedding',
            'Ensaio Pós-Wedding',
            'Álbum Impresso',
          ],
          required: true,
        },
        {
          id: 'importancia_fotografia',
          label: 'Qual a importância da fotografia para o grande dia de vocês?',
          type: 'textarea',
          required: false,
        },
        {
          id: 'orcamento_previsto',
          label: 'Vocês têm uma estimativa de orçamento para a fotografia?',
          type: 'select',
          options: [
            'Ainda não temos ideia',
            'Até R$ 3.000',
            'De R$ 3.000 a R$ 6.000',
            'Acima de R$ 6.000',
          ],
          required: false,
        },
        {
          id: 'como_conheceu',
          label: 'Como conheceram o meu trabalho?',
          type: 'select',
          options: [
            'Instagram',
            'Indicação de amigo/casal',
            'Indicação de fornecedor',
            'Pesquisa no Google',
            'Outro',
          ],
          required: true,
        },
        {
          id: 'observacoes',
          label: 'Alguma observação, dúvida ou detalhe que queiram compartilhar?',
          type: 'textarea',
          required: false,
        },
      ],
    },
  ],
};

export interface FieldError {
  id: string;
  message: string;
}

/**
 * Valida os valores do formulário contra o schema.
 * `values` mapeia field.id -> valor (string | string[]).
 * Retorna lista de erros (vazio = válido).
 */
export function validateQuoteForm(
  values: Record<string, string | string[] | undefined>,
  config: QuoteFormConfig = quoteForm
): FieldError[] {
  const errors: FieldError[] = [];

  for (const section of config.sections) {
    for (const field of section.fields) {
      const raw = values[field.id];

      const empty =
        raw === undefined ||
        raw === null ||
        (Array.isArray(raw) && raw.length === 0) ||
        (typeof raw === 'string' && raw.trim() === '');

      if (field.required && empty) {
        errors.push({ id: field.id, message: 'Campo obrigatório.' });
        continue;
      }

      if (empty) continue;

      const value = Array.isArray(raw) ? raw : String(raw).trim();

      if (field.type === 'email' && typeof value === 'string') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
          errors.push({ id: field.id, message: 'Informe um e-mail válido.' });
        }
      }

      if (field.type === 'tel' && typeof value === 'string') {
        const digits = value.replace(/\D/g, '');
        if (digits.length < 10) {
          errors.push({ id: field.id, message: 'Informe um WhatsApp válido com DDD.' });
        }
      }

      if (field.type === 'number' && typeof value === 'string') {
        if (value !== '' && Number.isNaN(Number(value))) {
          errors.push({ id: field.id, message: 'Informe um número válido.' });
        }
      }
    }
  }

  return errors;
}

/**
 * Converte os valores em um mapa limpo (string | string[]) já com trim.
 */
export function normalizeValues(
  values: Record<string, string | string[] | undefined>
): Record<string, string | string[]> {
  const out: Record<string, string | string[]> = {};
  for (const [key, val] of Object.entries(values)) {
    if (val === undefined || val === null) continue;
    if (Array.isArray(val)) {
      out[key] = val.map((v) => String(v).trim());
    } else {
      const t = String(val).trim();
      if (t !== '') out[key] = t;
    }
  }
  return out;
}
