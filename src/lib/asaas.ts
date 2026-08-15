import axios from 'axios';
import { query, queryOne } from '@/lib/db';

export class AsaasService {
  private apiKey: string = '';
  private mode: string = 'test';
  private baseUrl: string = '';

  constructor(apiKey?: string, mode?: string) {
    if (apiKey && mode) {
      this.apiKey = apiKey;
      this.mode = mode;
    } else {
      this.apiKey = process.env.ASAAS_API_KEY || '';
      this.mode = process.env.ASAAS_MODE || 'test';
    }

    this.baseUrl = this.mode === 'prod'
      ? 'https://api.asaas.com/v3'
      : 'https://sandbox.asaas.com/api/v3';
  }

  public async initFromDb() {
    if (!this.apiKey) {
      const config = await queryOne<{ asaas_api_key: string; asaas_mode: string }>(
        "SELECT asaas_api_key, asaas_mode FROM configuracao_empresa WHERE id = 'principal' LIMIT 1"
      );
      if (config && config.asaas_api_key) {
        this.apiKey = config.asaas_api_key;
        this.mode = config.asaas_mode || 'test';
        this.baseUrl = this.mode === 'prod' ? 'https://api.asaas.com/v3' : 'https://sandbox.asaas.com/api/v3';
      }
    }
  }

  public isConfigured(): boolean {
    return Boolean(this.apiKey);
  }

  private async request(endpoint: string, method = 'GET', data: any = null) {
    await this.initFromDb();
    if (!this.apiKey) {
      throw new Error('API Key do Asaas não está configurada no painel ou nas variáveis de ambiente.');
    }

    const url = `${this.baseUrl}/${endpoint.replace(/^\//, '')}`;
    try {
      const response = await axios({
        url,
        method,
        headers: {
          access_token: this.apiKey,
          'Content-Type': 'application/json',
          'User-Agent': 'ERP-Distinto/2.0'
        },
        data: data ? data : undefined,
        timeout: 30000
      });
      return response.data;
    } catch (err: any) {
      const msg = err.response?.data?.errors?.[0]?.description || err.response?.data?.message || err.message;
      throw new Error(`Erro Asaas: ${msg}`);
    }
  }

  public async listarCobrancas(options: { limit?: number } = {}) {
    const limit = options.limit || 50;
    return this.request(`payments?limit=${limit}&order=desc`);
  }

  public async listarExtratoFinanceiro(options: { limit?: number } = {}) {
    const limit = options.limit || 50;
    return this.request(`financialTransactions?limit=${limit}&order=desc`);
  }

  public async getBalanceAndExtract(limite = 50) {
    const saldo = await this.request('finance/balance');
    const cobrancas = await this.request(`payments?limit=${limite}&order=desc`);
    
    let extrato: any[] = [];
    try {
      const trans = await this.request(`financialTransactions?limit=${limite}&order=desc`);
      extrato = trans.data || [];
    } catch (e) {
      // Fallback para extrato simples se financialTransactions requerer escopo adicional
    }

    return {
      saldo: parseFloat(saldo.balance || 0),
      cobrancas: cobrancas.data || [],
      extrato
    };
  }

  public async getOrCreateCustomer(clienteId: string, dados: { nome: string; cpf_cnpj?: string; email?: string; telefone?: string }) {
    const cliente = await queryOne('SELECT asaas_customer_id, nome, cpf_cnpj, contato FROM clientes WHERE id = $1', [clienteId]);
    if (cliente && cliente.asaas_customer_id) {
      return cliente.asaas_customer_id;
    }

    const nome = (dados.nome || cliente?.nome || '').trim();
    const cpfCnpj = (dados.cpf_cnpj || cliente?.cpf_cnpj || '').replace(/\D/g, '');
    const email = (dados.email || cliente?.contato || '').trim();

    if (!nome) throw new Error('Nome do cliente é obrigatório para cadastrar no Asaas.');

    if (cpfCnpj) {
      try {
        const busca = await this.request(`customers?cpfCnpj=${cpfCnpj}`);
        if (busca.data?.[0]?.id) {
          const customerId = busca.data[0].id;
          await query('UPDATE clientes SET asaas_customer_id = $1 WHERE id = $2', [customerId, clienteId]);
          return customerId;
        }
      } catch (e) {
        // Prosseguir para criação se busca falhar
      }
    }

    const res = await this.request('customers', 'POST', {
      name: nome,
      cpfCnpj: cpfCnpj || undefined,
      email: email || undefined,
      mobilePhone: dados.telefone ? dados.telefone.replace(/\D/g, '') : undefined,
      externalReference: clienteId
    });

    if (!res.id) throw new Error('Resposta inválida ao criar cliente no Asaas.');

    await query('UPDATE clientes SET asaas_customer_id = $1 WHERE id = $2', [res.id, clienteId]);
    return res.id;
  }
}

export const asaasService = new AsaasService();
