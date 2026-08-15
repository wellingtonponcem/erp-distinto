import axios from 'axios';

export interface SendEmailOptions {
  to: { email: string; name?: string }[];
  subject: string;
  htmlContent: string;
  senderEmail?: string;
  senderName?: string;
  params?: Record<string, any>;
}

export class BrevoService {
  private apiKey: string;
  private defaultSenderEmail: string;
  private defaultSenderName: string;

  constructor() {
    this.apiKey = process.env.BREVO_API_KEY || process.env.SENDINBLUE_API_KEY || '';
    this.defaultSenderEmail = process.env.BREVO_SENDER_EMAIL || 'atendimento@wedistinto.com';
    this.defaultSenderName = process.env.BREVO_SENDER_NAME || 'ERP Distinto';
  }

  public isConfigured(): boolean {
    return Boolean(this.apiKey);
  }

  public async sendEmail(options: SendEmailOptions) {
    if (!this.isConfigured()) {
      throw new Error('Chave de API da Brevo (BREVO_API_KEY) não foi configurada nas variáveis de ambiente.');
    }

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

    try {
      const response = await axios.post(url, payload, {
        headers: {
          'api-key': this.apiKey,
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        timeout: 10000,
      });

      return { ok: true, messageId: response.data?.messageId || response.data?.messageIds };
    } catch (err: any) {
      const errorMsg = err.response?.data?.message || err.message;
      console.error('Brevo Email Error:', errorMsg);
      throw new Error(`Falha no envio via Brevo: ${errorMsg}`);
    }
  }
}

export const brevoService = new BrevoService();
