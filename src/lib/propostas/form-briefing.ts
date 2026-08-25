export interface BriefingField {
  id: string;
  label: string;
  type: 'text' | 'tel' | 'email' | 'date' | 'time' | 'textarea' | 'radio' | 'checkbox' | 'select';
  required?: boolean;
  options?: string[];
  placeholder?: string;
  helpText?: string;
}

export interface BriefingSection {
  section_name: string;
  description?: string;
  fields: BriefingField[];
}

export interface BriefingFormConfig {
  form_title: string;
  form_description: string;
  sections: BriefingSection[];
}

export const briefingLogisticoConfig: BriefingFormConfig = {
  form_title: 'Briefing Logístico do Casamento',
  form_description: 'Nosso objetivo é garantir que tudo flua com tranquilidade no seu grande dia. Por favor, respondam com o máximo de detalhes possível.',
  sections: [
    {
      section_name: '1. Identificação dos Noivos & Contato',
      fields: [
        {
          id: 'nome_noivos',
          label: 'Nome do Casal (Noiva e Noivo)',
          type: 'text',
          required: true,
          placeholder: 'Ex: Isabely & Kevin',
        },
        {
          id: 'data_casamento',
          label: 'Data do Casamento',
          type: 'date',
          required: true,
        },
        {
          id: 'whatsapp_emergencia_noiva',
          label: 'WhatsApp / Telefone da Noiva',
          type: 'tel',
          required: true,
          placeholder: '(00) 00000-0000',
        },
        {
          id: 'whatsapp_emergencia_noivo',
          label: 'WhatsApp / Telefone do Noivo',
          type: 'tel',
          required: true,
          placeholder: '(00) 00000-0000',
        },
      ],
    },
    {
      section_name: '2. Cronograma do Dia',
      description: 'Horários previstos para alinhamento da equipe de fotografia e vídeo',
      fields: [
        {
          id: 'horario_preparacao_noiva',
          label: 'Início da preparação da Noiva (Making Of)',
          type: 'time',
          required: true,
        },
        {
          id: 'horario_preparacao_noivo',
          label: 'Início da preparação do Noivo (Making Of)',
          type: 'time',
          required: true,
        },
        {
          id: 'horario_chegada_convidados',
          label: 'Chegada dos padrinhos/convidados na cerimônia',
          type: 'time',
        },
        {
          id: 'horario_inicio_cerimonia',
          label: 'Início da Cerimônia',
          type: 'time',
          required: true,
        },
        {
          id: 'horario_fim_cerimonia',
          label: 'Fim previsto da Cerimônia',
          type: 'time',
        },
        {
          id: 'horario_sessao_fotos',
          label: 'Início da Sessão de Fotos dos Noivos (pós-cerimônia)',
          type: 'time',
        },
        {
          id: 'horario_inicio_festa',
          label: 'Início da Festa / Recepção',
          type: 'time',
        },
        {
          id: 'horario_entrada_noivos_festa',
          label: 'Entrada dos noivos na Festa',
          type: 'time',
        },
        {
          id: 'horario_brindes_discursos',
          label: 'Brindes e Discursos',
          type: 'time',
        },
        {
          id: 'horario_corte_bolo',
          label: 'Corte do Bolo',
          type: 'time',
        },
        {
          id: 'horario_primeira_danca',
          label: 'Primeira Dança dos Noivos',
          type: 'time',
        },
        {
          id: 'horario_fim_festa',
          label: 'Fim previsto da Festa',
          type: 'time',
        },
        {
          id: 'momentos_especiais_fora_cronograma',
          label: 'Há algum momento especial fora desse cronograma? (Ex.: surpresa, apresentação, dança especial, homenagem)',
          type: 'textarea',
          placeholder: 'Descreva rituais ou momentos surpresa planejados...',
        },
      ],
    },
    {
      section_name: '3. Locais e Endereços',
      fields: [
        {
          id: 'local_noiva_endereco',
          label: 'Preparação da Noiva — Endereço completo & Ponto de referência',
          type: 'textarea',
          required: true,
          placeholder: 'Nome do hotel/salão, rua, número, bairro, cidade e referências',
        },
        {
          id: 'horario_chegada_equipe_noiva',
          label: 'Horário de chegada da equipe na preparação da noiva',
          type: 'time',
        },
        {
          id: 'local_noivo_endereco',
          label: 'Preparação do Noivo — Endereço completo & Ponto de referência',
          type: 'textarea',
          required: true,
          placeholder: 'Endereço completo da preparação do noivo',
        },
        {
          id: 'horario_chegada_equipe_noivo',
          label: 'Horário de chegada da equipe na preparação do noivo',
          type: 'time',
        },
        {
          id: 'local_cerimonia_endereco',
          label: 'Cerimônia — Nome do local & Endereço completo',
          type: 'textarea',
          required: true,
          placeholder: 'Igreja/Espaço, endereço completo e cidade',
        },
        {
          id: 'restricoes_cerimonia',
          label: 'Há restrições para fotografia/filmagem no local da cerimônia? (Ex.: proibido flash, restrição de circulação)',
          type: 'radio',
          options: ['Sim', 'Não'],
          required: true,
        },
        {
          id: 'restricoes_cerimonia_detalhes',
          label: 'Se sim, quais são as restrições da igreja/espaço?',
          type: 'textarea',
          placeholder: 'Explique as regras impostas pelo celebrante ou local...',
        },
        {
          id: 'local_festa_endereco',
          label: 'Festa / Recepção — Nome do local & Endereço completo',
          type: 'textarea',
          required: true,
          placeholder: 'Nome do salão/sítio e endereço completo',
        },
        {
          id: 'local_fotos_casal',
          label: 'Há algum local específico escolhido para a sessão de fotos do casal pós-casamento? (Ex.: jardim, varanda, praia)',
          type: 'text',
          placeholder: 'Ex: Jardim do salão, varanda com por do sol, etc.',
        },
      ],
    },
    {
      section_name: '4. Pessoas-Chave e Contatos no Dia',
      fields: [
        {
          id: 'cerimonialista_nome_contato',
          label: 'Cerimonialista / Wedding Planner (Nome e WhatsApp)',
          type: 'text',
          required: true,
          placeholder: 'Ex: Maria Silva - (27) 99999-0000',
        },
        {
          id: 'padrinhos_madrinhas_nomes',
          label: 'Nomes dos Padrinhos e Madrinhas principais (para fotos de grupo)',
          type: 'textarea',
          placeholder: 'Liste os casais/padrinhos para organização das fotos',
        },
        {
          id: 'pais_avos_nomes',
          label: 'Nomes dos Pais e Avós',
          type: 'textarea',
          placeholder: 'Nome dos pais da noiva, pais do noivo e avós presentes',
        },
        {
          id: 'responsavel_ajuda_fotos_grupo',
          label: 'Alguém que ficará responsável por nos ajudar a reunir as pessoas para as fotos de grupo? (Ex.: padrinho, familiar, cerimonialista)',
          type: 'text',
          required: true,
          placeholder: 'Nome e WhatsApp da pessoa responsável',
        },
        {
          id: 'atencao_especial_pessoas',
          label: 'Há alguma pessoa que não deve aparecer nas fotos ou que precisa de atenção especial? (Ex.: familiares separados, mobilidade reduzida)',
          type: 'textarea',
          placeholder: 'Orientações de etiqueta e protocolo importante',
        },
      ],
    },
    {
      section_name: '5. Momentos e Detalhes que não Podem Faltar',
      fields: [
        {
          id: 'momentos_prioritarios',
          label: 'Há algum momento específico que vocês querem que seja registrado com atenção especial?',
          type: 'textarea',
          placeholder: 'Ex: reação do noivo na entrada da noiva, reação dos pais, 1º beijo, brinde, entrada na festa...',
        },
        {
          id: 'objetos_detalhes_fotografar',
          label: 'Há algum detalhe ou objeto especial que desejam fotografar?',
          type: 'textarea',
          placeholder: 'Ex: alianças, buquê, sapatos, convite, lembrancinhas, mesa de doces, abotoaduras...',
        },
        {
          id: 'tradicoes_rituais',
          label: 'Vocês farão alguma tradição ou ritual especial? (Ex.: dança típica, entrega de homenagens, algo cultural)',
          type: 'textarea',
          placeholder: 'Descreva a tradição ou ritual planejado...',
        },
      ],
    },
    {
      section_name: '6. Imprevistos, Plano B & Expectativas',
      fields: [
        {
          id: 'plano_b_chuva',
          label: 'Há algum plano B em caso de chuva ou imprevisto?',
          type: 'textarea',
          placeholder: 'Ex: área coberta do espaço, tenda, alteração de local das fotos...',
        },
        {
          id: 'contato_emergencia_dia',
          label: 'Contato de emergência no dia (Nome e Telefone)',
          type: 'text',
          required: true,
          placeholder: 'Nome e telefone do parente ou amigo de confiança',
        },
        {
          id: 'expectativas_registro',
          label: 'O que mais importa para vocês no registro do dia?',
          type: 'textarea',
          placeholder: 'Ex: espontaneidade, emoção real, fotos de grupo, detalhes da decoração...',
        },
        {
          id: 'preferencias_evitar',
          label: 'Há algo que vocês NÃO querem que aconteça ou que preferem evitar?',
          type: 'textarea',
          placeholder: 'Ex: muitas fotos posadas, interrupções durante a cerimônia...',
        },
      ],
    },
  ],
};
