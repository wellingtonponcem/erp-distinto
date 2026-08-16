import axios from 'axios';
import { query, queryOne } from '@/lib/db';

export class AsaasService {
  private apiKey: string = '';
  private mode: string = 'prod';
  private baseUrl: string = '';

  constructor(apiKey?: string, mode?: string) {
    if (apiKey && mode) {
      this.apiKey = apiKey;
      this.mode = mode;
    } else {
      this.apiKey = process.env.ASAAS_API_KEY || '';
      this.mode = process.env.ASAAS_MODE || 'prod';
    }

    this.baseUrl = this.mode === 'prod'
      ? 'https://api.asaas.com/v3'
      : 'https://sandbox.asaas.com/api/v3';
  }

  public setApiKey(apiKey: string, mode: string = 'prod') {
    this.apiKey = apiKey.trim();
    this.mode = mode.trim();
    this.baseUrl = this.mode === 'prod' ? 'https://api.asaas.com/v3' : 'https://sandbox.asaas.com/api/v3';
  }

  public async initFromDb(force = false) {
    if (!this.apiKey || force) {
      try {
        const config = await queryOne<{ asaas_api_key: string; asaas_mode: string }>(
          "SELECT asaas_api_key, asaas_mode FROM configuracao_empresa ORDER BY (id = 'principal') DESC LIMIT 1"
        );
        if (config && config.asaas_api_key) {
          this.apiKey = config.asaas_api_key.trim();
          this.mode = config.asaas_mode ? config.asaas_mode.trim() : 'prod';
        }
      } catch (e) {}
    }
    this.baseUrl = this.mode === 'prod' ? 'https://api.asaas.com/v3' : 'https://sandbox.asaas.com/api/v3';
  }

  public async isConfiguredAsync(): Promise<boolean> {
    await this.initFromDb(true);
    return Boolean(this.apiKey);
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
    const limit = options.limit || 100;
    return this.request(`payments?limit=${limit}&order=desc`);
  }

  public async listarExtratoFinanceiro(options: { limit?: number } = {}) {
    const limit = options.limit || 100;
    return this.request(`financialTransactions?limit=${limit}&order=desc`);
  }

  public async listarTransferencias(options: { limit?: number } = {}) {
    const limit = options.limit || 100;
    return this.request(`transfers?limit=${limit}&order=desc`);
  }

  public async getTransferDetails(transferId: string) {
    try {
      return await this.request(`transfers/${transferId}`);
    } catch (e) {
      return null;
    }
  }

  public async getBalanceAndExtract(limite = 100) {
    const saldo = await this.request('finance/balance');
    const cobrancas = await this.request(`payments?limit=${limite}&order=desc`);

    let extrato: any[] = [];
    try {
      const trans = await this.request(`financialTransactions?limit=${limite}&order=desc`);
      extrato = trans.data || [];
    } catch (e) {}

    let transferencias: any[] = [];
    try {
      const trfs = await this.request(`transfers?limit=${limite}&order=desc`);
      transferencias = trfs.data || [];
    } catch (e) {}

    return {
      saldo: parseFloat(saldo.balance || 0),
      cobrancas: cobrancas.data || [],
      extrato,
      transferencias
    };
  }

  // Sincronizar permanentemente cobranças, extratos e NOME DOS DESTINATÁRIOS de transferências no MySQL Hostinger
  public async sincronizarComBancoDados() {
    await this.initFromDb(true);
    if (!this.apiKey) return { inseridos: 0, total: 0 };

    try {
      const { cobrancas, extrato, transferencias } = await this.getBalanceAndExtract(100);
      let inseridos = 0;

      // 1. Transferências Saídas (PIX, TED, Transferências Bancárias) - Prioridade Alta
      const transferenciasMap = new Map<string, any>();

      for (const t of transferencias) {
        const asaasId = t.id;
        const lancId = `asaas_trf_${t.id}`;
        const val = Math.abs(parseFloat(t.value || 0));

        // Extrair nome completo do titular destinatário da transferência
        const nomeTitular = (
          t.bankAccount?.ownerName ||
          t.pixAddressKeyName ||
          t.effectiveAddressKeyName ||
          t.customerName ||
          ''
        ).trim();

        const bancoNome = t.bankAccount?.bank?.name || t.bankAccount?.bankName || '';
        const favName = nomeTitular || 'Destinatário Asaas';

        const desc = nomeTitular
          ? `Transferência PIX para ${nomeTitular}${bancoNome ? ` (${bancoNome})` : ''}`
          : (t.description || `Transferência Asaas (${t.type || 'PIX'})`).trim();

        const dateStr = t.effectiveDate
          ? t.effectiveDate.split('T')[0]
          : (t.dateCreated ? t.dateCreated.split('T')[0] : new Date().toISOString().split('T')[0]);

        const status = t.status === 'CANCELLED' || t.status === 'FAILED' ? 'pendente' : 'saida';

        transferenciasMap.set(asaasId, { nomeTitular, desc, favName, val, dateStr, status });

        try {
          await query(
            `INSERT INTO lancamentos (
              id, tipo, descricao, valor, valor_pago, categoria, cliente_fornecedor,
              vencimento, data_pagamento, status, conta_id, conciliado, asaas_id
            ) VALUES ($1, 'pagar', $2, $3, $4, 'Transferência Asaas', $5, $6, $7, $8, 'asaas', 1, $9)
            ON DUPLICATE KEY UPDATE
              descricao = VALUES(descricao),
              valor = VALUES(valor),
              valor_pago = VALUES(valor_pago),
              cliente_fornecedor = VALUES(cliente_fornecedor),
              vencimento = VALUES(vencimento),
              data_pagamento = VALUES(data_pagamento),
              status = VALUES(status),
              conciliado = 1`,
            [lancId, desc, val, val, favName, dateStr, dateStr, status, asaasId]
          );
          inseridos++;
        } catch (e: any) {
          console.error('Erro ao gravar transferencia Asaas no MySQL:', e.message);
        }
      }

      // 2. Extrato Financeiro (Financial Transactions: ftn_...)
      for (const e of extrato) {
        const valRaw = parseFloat(e.value || 0);
        const isSaida = valRaw < 0 || e.type?.includes('DEBIT') || e.type?.includes('TRANSFER') || e.type?.includes('FEE');
        const asaasId = e.id;
        const lancId = `asaas_ext_${e.id}`;
        const tipo = isSaida ? 'pagar' : 'receber';
        const val = Math.abs(valRaw);
        const dateStr = e.date ? e.date.split('T')[0] : new Date().toISOString().split('T')[0];
        const status = isSaida ? 'saida' : 'pago';

        let favName = 'Movimentação Asaas';
        let desc = (e.description || e.type || 'Movimentação Extrato Asaas').trim();

        if (e.transferId && transferenciasMap.has(e.transferId)) {
          const trfData = transferenciasMap.get(e.transferId);
          favName = trfData.favName;
          desc = trfData.desc;
        } else if (e.transferId) {
          const trfDetail = await this.getTransferDetails(e.transferId);
          if (trfDetail && trfDetail.bankAccount?.ownerName) {
            favName = trfDetail.bankAccount.ownerName;
            const bName = trfDetail.bankAccount?.bank?.name ? ` (${trfDetail.bankAccount.bank.name})` : '';
            desc = `Transferência PIX para ${favName}${bName}`;
          }
        }

        if (favName === 'Movimentação Asaas' && desc) {
          const match = desc.match(/(?:para|destinatário|titular)[\s:]+([A-Za-zÀ-ÖØ-öø-ÿ\s]+)/i);
          if (match && match[1] && match[1].trim().length > 3) {
            favName = match[1].trim();
          }
        }

        try {
          await query(
            `INSERT INTO lancamentos (
              id, tipo, descricao, valor, valor_pago, categoria, cliente_fornecedor,
              vencimento, data_pagamento, status, conta_id, conciliado, asaas_id
            ) VALUES ($1, $2, $3, $4, $5, 'Asaas', $6, $7, $8, $9, 'asaas', 1, $10)
            ON DUPLICATE KEY UPDATE
              descricao = VALUES(descricao),
              valor = VALUES(valor),
              valor_pago = VALUES(valor_pago),
              cliente_fornecedor = VALUES(cliente_fornecedor),
              vencimento = VALUES(vencimento),
              data_pagamento = VALUES(data_pagamento),
              status = VALUES(status),
              conciliado = 1`,
            [lancId, tipo, desc, val, val, favName, dateStr, dateStr, status, asaasId]
          );
          inseridos++;
        } catch (err: any) {
          console.error('Erro ao gravar extrato Asaas no MySQL:', err.message);
        }
      }

      // 3. Cobranças de Entrada / Clientes
      for (const c of cobrancas) {
        const isSaida = c.status === 'REFUNDED' || c.status === 'REFUND_REQUESTED' || c.value < 0;
        const asaasId = c.id;
        const lancId = `asaas_${c.id}`;
        const tipo = isSaida ? 'pagar' : 'receber';
        const desc = (c.description || `Cobrança Asaas (${c.billingType || 'PIX'})`).trim();
        const val = Math.abs(parseFloat(c.value || 0));
        const valPago = c.status === 'RECEIVED' || c.status === 'CONFIRMED' ? Math.abs(parseFloat(c.netValue || c.value || 0)) : 0;
        const venc = c.dueDate ? c.dueDate.split('T')[0] : new Date().toISOString().split('T')[0];
        const dataPag = c.paymentDate ? c.paymentDate.split('T')[0] : (c.clientPaymentDate ? c.clientPaymentDate.split('T')[0] : null);

        let status = 'pendente';
        if (c.status === 'RECEIVED' || c.status === 'CONFIRMED') status = 'pago';
        else if (c.status === 'OVERDUE') status = 'atrasado';
        else if (isSaida) status = 'saida';

        const cliente = c.customerName || 'Cliente Asaas';

        try {
          await query(
            `INSERT INTO lancamentos (
              id, tipo, descricao, valor, valor_pago, categoria, cliente_fornecedor,
              vencimento, data_pagamento, status, conta_id, conciliado, asaas_id
            ) VALUES ($1, $2, $3, $4, $5, 'Asaas', $6, $7, $8, $9, 'asaas', 1, $10)
            ON DUPLICATE KEY UPDATE
              tipo = VALUES(tipo),
              descricao = VALUES(descricao),
              valor = VALUES(valor),
              valor_pago = VALUES(valor_pago),
              cliente_fornecedor = VALUES(cliente_fornecedor),
              vencimento = VALUES(vencimento),
              data_pagamento = VALUES(data_pagamento),
              status = VALUES(status),
              conciliado = 1`,
            [lancId, tipo, desc, val, valPago, cliente, venc, dataPag, status, asaasId]
          );
          inseridos++;
        } catch (e: any) {
          console.error('Erro ao gravar cobrança Asaas no MySQL:', e.message);
        }
      }

      return { inseridos, total: cobrancas.length + transferencias.length + extrato.length };
    } catch (err: any) {
      console.error('Erro na sincronizacao com o Asaas:', err.message);
      return { inseridos: 0, total: 0 };
    }
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
      } catch (e) {}
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
