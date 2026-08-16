import { NextApiRequest, NextApiResponse } from 'next';
import { requireAdmin } from '@/lib/helpers';
import { query, queryOne } from '@/lib/db';
import { formatarMoeda } from '@/lib/propostas/common';

/**
 * Port de api/propostas/fechamento.php — salva os dados de fechamento (plano,
 * valor, condições, Asaas) de uma proposta.
 */

function decimalFormulario(valor: any): number {
  let texto = String(valor ?? '').trim();
  if (texto === '') return 0;
  texto = texto.replace(/R\$/g, '').replace(/ /g, '');
  if (texto.includes(',')) {
    texto = texto.replace(/\./g, '');
    texto = texto.replace(/,/g, '.');
  }
  return Number(texto) || 0;
}

function adicionarMesesIso(dataIso: string, meses: number): string {
  if (!dataIso) return '';
  const dt = new Date(dataIso + 'T00:00:00');
  if (Number.isNaN(dt.getTime())) return '';
  dt.setMonth(dt.getMonth() + meses);
  return dt.toISOString().slice(0, 10);
}

function mesesAteEvento(primeiraParcela: string, dataEvento: string): number {
  const inicio = new Date(primeiraParcela + 'T00:00:00');
  const fim = new Date(dataEvento + 'T00:00:00');
  if (Number.isNaN(inicio.getTime()) || Number.isNaN(fim.getTime()) || fim < inicio) return 1;
  return Math.max(1, (fim.getFullYear() - inicio.getFullYear()) * 12 + (fim.getMonth() - inicio.getMonth()) + 1);
}

function dateBRFull(): string {
  const d = new Date();
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

export default requireAdmin(async (req: NextApiRequest, res: NextApiResponse) => {
  if (req.method !== 'POST') {
    return res.status(405).json({ success: false, erro: 'Método não permitido' });
  }

  try {
    const payload = req.body || {};
    const id = payload.id || '';
    if (id === '') {
      return res.status(422).json({ success: false, erro: 'ID da proposta não informado.' });
    }

    const proposta = await queryOne(`SELECT * FROM propostas WHERE id = $1`, [String(id)]);
    if (!proposta) {
      return res.status(404).json({ success: false, erro: 'Proposta não encontrada.' });
    }

    let dados: any = {};
    try {
      dados = typeof proposta.dados_json === 'string' ? JSON.parse(proposta.dados_json) : (proposta.dados_json || {});
    } catch (e) {
      dados = {};
    }

    const plano = String(payload.pacote_dado_andamento ?? '').trim();
    const valorTotal = decimalFormulario(payload.valor_total ?? proposta.valor_total ?? 0);
    const condicoes = String(payload.escolha_condicoes ?? '').trim();
    const pagamentoModo = String(payload.pagamento_modo ?? 'parcelado') === 'avista' ? 'avista' : 'parcelado';
    const permitirPosEvento = !!(payload.permitir_parcela_pos_evento && payload.permitir_parcela_pos_evento !== 'false');

    if (!['', 'heritage', 'cinematic', 'essencial'].includes(plano)) {
      return res.status(422).json({ success: false, erro: 'Plano escolhido inválido.' });
    }

    dados['pacote_dado_andamento'] = plano;
    dados['valor_fechamento'] = valorTotal;
    dados['escolha_condicoes'] = condicoes;
    dados['pagamento_modo'] = pagamentoModo;
    dados['permitir_parcela_pos_evento'] = permitirPosEvento;
    dados['asaas_billing_type'] = payload.asaas_billing_type ?? 'UNDEFINED';
    const percentualEntrada = plano === 'heritage' ? 25 : 20;
    const maxParcelasPlano = plano === 'heritage' ? 6 : 5;
    const parcelas = pagamentoModo === 'avista' ? 1 : Math.max(1, parseInt(payload.asaas_total_parcelas ?? 1, 10) || 1);
    dados['asaas_first_due_date'] = payload.asaas_first_due_date ?? '';
    dados['asaas_valor_sinal'] = decimalFormulario(payload.asaas_valor_sinal ?? 0);
    dados['asaas_sinal_vencimento'] = payload.asaas_sinal_vencimento ?? '';
    dados['prazo_contrato'] = String(payload.prazo_contrato ?? '').trim();

    if (valorTotal > 0 && dados['asaas_valor_sinal'] <= 0) {
      dados['asaas_valor_sinal'] = Math.round(valorTotal * (percentualEntrada / 100) * 100) / 100;
    }

    if (pagamentoModo === 'parcelado') {
      if (dados['asaas_first_due_date'] === '' && dados['asaas_sinal_vencimento'] !== '') {
        dados['asaas_first_due_date'] = adicionarMesesIso(dados['asaas_sinal_vencimento'], 1);
      }
      let limiteEvento = maxParcelasPlano;
      const dataEvento = dados['data_casamento'] || dados['data_evento'] || '';
      if (!permitirPosEvento && dados['asaas_first_due_date'] !== '' && dataEvento !== '') {
        limiteEvento = Math.min(maxParcelasPlano, mesesAteEvento(dados['asaas_first_due_date'], dataEvento));
      }
      dados['asaas_total_parcelas'] = Math.max(1, Math.min(parcelas, limiteEvento));
    } else {
      dados['asaas_total_parcelas'] = 1;
      dados['asaas_first_due_date'] = '';
    }

    let clienteEscolha = dados['cliente_escolha'];
    if (!clienteEscolha || typeof clienteEscolha !== 'object' || Array.isArray(clienteEscolha)) {
      clienteEscolha = {};
    }
    if (plano !== '') clienteEscolha['plano_id'] = plano;
    if (valorTotal > 0) clienteEscolha['valor_total'] = valorTotal;
    if (condicoes !== '') clienteEscolha['condicoes'] = condicoes;
    clienteEscolha['ajustado_admin_em'] = dateBRFull();
    dados['cliente_escolha'] = clienteEscolha;

    const dadosJson = JSON.stringify(dados);
    const valorFinal = valorTotal > 0 ? valorTotal : (Number(proposta.valor_total) || 0);
    await query(`UPDATE propostas SET dados_json = $1, valor_total = $2 WHERE id = $3`, [
      dadosJson,
      valorFinal,
      id,
    ]);

    try {
      await query(
        `CREATE TABLE IF NOT EXISTS propostas_historico (
          id INT AUTO_INCREMENT PRIMARY KEY,
          proposta_id TEXT NOT NULL,
          user_id TEXT NOT NULL,
          tipo TEXT DEFAULT 'nota',
          conteudo TEXT NOT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )`
      );
      const resumoPlano = plano !== '' ? plano : 'não definido';
      const conteudo = `Dados de fechamento atualizados. Plano: ${resumoPlano}. Valor: ${formatarMoeda(valorTotal)}.`;
      await query(
        `INSERT INTO propostas_historico (proposta_id, user_id, tipo, conteudo) VALUES ($1, $2, 'fechamento', $3)`,
        [id, 'admin', conteudo]
      );
    } catch (e) {}

    return res.status(200).json({
      success: true,
      dados_json: dadosJson,
      valor_total: valorTotal > 0 ? valorTotal : Number(proposta.valor_total) || 0,
    });
  } catch (err: any) {
    return res.status(500).json({ success: false, erro: err.message });
  }
});