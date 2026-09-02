import * as Brevo from '@getbrevo/brevo';
import axios from 'axios';

export interface SendEmailOptions {
  to: { email: string; name?: string }[];
  subject: string;
  htmlContent: string;
  senderEmail?: string;
  senderName?: string;
  params?: Record<string, any>;
}

export interface CreateCampaignOptions {
  name: string;
  subject: string;
  senderEmail?: string;
  senderName?: string;
  htmlContent: string;
  listIds?: number[];
  scheduledAt?: string;
}

export class BrevoService {
  private get apiKey(): string {
    return process.env.BREVO_API_KEY || process.env.SENDINBLUE_API_KEY || '';
  }
  private get defaultSenderEmail(): string {
    return process.env.BREVO_SENDER_EMAIL || 'atendimento@wedistinto.com';
  }
  private get defaultSenderName(): string {
    return process.env.BREVO_SENDER_NAME || 'ERP Distinto';
  }

  constructor() {}

  public isConfigured(): boolean {
    return Boolean(this.apiKey);
  }

  /**
   * Envio de E-mails Transacionais (Propostas, Links de Assinatura, Redefinição de Senha)
   */
  public async sendEmail(options: SendEmailOptions) {
    if (!this.isConfigured()) {
      throw new Error('Chave de API da Brevo (BREVO_API_KEY) não foi configurada nas variáveis de ambiente.');
    }

    try {
      const apiInstance = new Brevo.TransactionalEmailsApi();
      apiInstance.setApiKey(Brevo.TransactionalEmailsApiApiKeys.apiKey, this.apiKey);

      const sendSmtpEmail = new Brevo.SendSmtpEmail();
      sendSmtpEmail.subject = options.subject;
      sendSmtpEmail.htmlContent = options.htmlContent;
      sendSmtpEmail.sender = {
        name: options.senderName || this.defaultSenderName,
        email: options.senderEmail || this.defaultSenderEmail,
      };
      sendSmtpEmail.to = options.to;
      sendSmtpEmail.params = options.params;

      const data = await apiInstance.sendTransacEmail(sendSmtpEmail);
      return { ok: true, messageId: data.body?.messageId || data.response?.statusCode };
    } catch (err: any) {
      return this.sendEmailRestFallback(options);
    }
  }

  private async sendEmailRestFallback(options: SendEmailOptions) {
    const url = 'https://api.brevo.com/v3/smtp/email';
    const payload = {
      sender: {
        name: options.senderName || this.defaultSenderName,
        email: options.senderEmail || this.defaultSenderEmail,
      },
      to: options.to,
      subject: options.subject,
      htmlContent: options.htmlContent,
      params: options.params,
    };

    const response = await axios.post(url, payload, {
      headers: {
        'api-key': this.apiKey,
        'Content-Type': 'application/json',
      },
    });

    return { ok: true, messageId: response.data?.messageId };
  }

  /**
   * Criar e Agendar Campanhas de E-mail Marketing na Brevo
   */
  public async createCampaign(options: CreateCampaignOptions) {
    if (!this.isConfigured()) {
      throw new Error('Chave de API da Brevo (BREVO_API_KEY) não configurada.');
    }

    const url = 'https://api.brevo.com/v3/emailCampaigns';
    const payload: any = {
      name: options.name,
      subject: options.subject,
      sender: {
        name: options.senderName || this.defaultSenderName,
        email: options.senderEmail || this.defaultSenderEmail,
      },
      type: 'classic',
      htmlContent: options.htmlContent,
    };

    if (options.listIds) {
      payload.recipients = { listIds: options.listIds };
    }

    if (options.scheduledAt) {
      payload.scheduledAt = options.scheduledAt;
    }

    const response = await axios.post(url, payload, {
      headers: {
        'api-key': this.apiKey,
        'Content-Type': 'application/json',
      },
    });

    return { ok: true, campaignId: response.data?.id };
  }
}

export const brevoService = new BrevoService();
