import { NextApiRequest, NextApiResponse } from 'next';
import { queryOne, query } from '@/lib/db';
import { formatarMoeda } from '@/lib/propostas/common';

function pad(n: number): string {
  return String(n).padStart(2, '0');
}

function dateBR(): string {
  const d = new Date();
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function dateBRFull(): string {
  const d = new Date();
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

const MAPA_VALOR: Record<string, string> = {
  heritage: 'valor_heritage',
  cinematic: 'valor_cinematic',
  essencial: 'valor_essencial',
};

const MAPA_SHOW: Record<string, string> = {
  heritage: 'show_heritage',
  cinematic: 'show_cinematic',
  essencial: 'show_essencial',
};

const NOME_PLANO: Record<string, string> = {
  heritage: 'Experiência Heritage',
  cinematic: 'Experiência Cinematic',
  essencial: 'Registro Essencial',
};

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  const method = req.method;

  if (method === 'GET') {
    const { slug } = req.query;
    if (!slug) return res.status(422).json({ erro: 'Slug da proposta é obrigatório' });

    try {
      const slugStr = String(slug);
      const proposta = await queryOne(
        `SELECT * FROM propostas WHERE slug = $1 OR id = $2 LIMIT 1`,
        [slugStr, slugStr]
      );

      if (!proposta) {
        return res.status(404).json({ erro: 'Proposta não encontrada ou link expirado.' });
      }

      let dadosParsed: any = {};
      try {
        dadosParsed = typeof proposta.dados_json === 'string' ? JSON.parse(proposta.dados_json) : (proposta.dados_json || {});
      } catch (e) {}

      const configEmpresa = await queryOne(
        `SELECT * FROM configuracao_empresa WHERE id = 'principal' LIMIT 1`
      );

      const valorNum = parseFloat(proposta.valor_total || proposta.valor || 0);

      return res.status(200).json({
        ok: true,
        proposta: {
          id: proposta.id,
          slug: proposta.slug || proposta.id,
          titulo: proposta.titulo,
          subtitulo: proposta.subtitulo || '',
          tipo: proposta.tipo || 'casamento',
          cliente: proposta.cliente_nome || 'Cliente Especial',
          validade: proposta.validade,
          valor: valorNum,
          valor_total: valorNum,
          status: proposta.status || 'enviada',
          criado_em: proposta.created_at || proposta.criado_em,
          dados: dadosParsed,
        },
        empresa: {
          nome: configEmpresa?.nome_empresa || 'ERP Distinto',
          whatsapp: process.env.WHATSAPP_NUMERO || '5527988586935',
        }
      });
    } catch (err: any) {
      console.error('Erro na consulta publica da proposta:', err);
      return res.status(500).json({ erro: err.message });
    }
  }

  // Escolha de plano pelo cliente (port fiel de api/propostas/escolher-plano.php)
  if (method === 'POST') {
    const { slug, plano_id, extras: extrasRaw, condicoes } = req.body || {};
    const extras = Array.isArray(extrasRaw) ? extrasRaw.map((e: any) => String(e)) : [];

    if (!slug || !['heritage', 'cinematic', 'essencial'].includes(String(plano_id))) {
      return res.status(422).json({ erro: 'Dados invalidos' });
    }

    try {
      const slugStr = String(slug);
      const planoId = String(plano_id);

      const proposta = await queryOne(`SELECT * FROM propostas WHERE slug = $1 LIMIT 1`, [slugStr]);
      if (!proposta) {
        return res.status(404).json({ erro: 'Proposta nao encontrada' });
      }

      let dados: any = {};
      try {
        dados = typeof proposta.dados_json === 'string' ? JSON.parse(proposta.dados_json) : (proposta.dados_json || {});
      } catch (e) {}

      let valorBase = Number(dados[MAPA_VALOR[planoId]] ?? 0) || 0;
      if (valorBase <= 0) {
        const like = planoId === 'essencial' ? '%essencial%' : (planoId === 'cinematic' ? '%cinematic%' : '%heritage%');
        const pkgRow = await queryOne(
          `SELECT preco_venda FROM servicos WHERE categoria = 'wedding' AND tipo = 'plano' AND LOWER(nome) LIKE $1 AND ativo = 1 LIMIT 1`,
          [like]
        );
        valorBase = Number(pkgRow?.preco_venda ?? 0) || 0;
      }

      let total = valorBase;
      const itensSelecionados: string[] = [];

      if (extras.includes('boudoir_static')) {
        total += Number(dados['valor_boudoir'] ?? 500) || 0;
        itensSelecionados.push('Boudoir da Noiva');
      }
      if (extras.includes('prewedding_static')) {
        total += Number(dados['valor_prewedding'] ?? 1100) || 0;
        itensSelecionados.push('Ensaio Pre-Wedding');
      }

      const extrasDinamicos = extras.filter((id) => !id.endsWith('_static'));
      if (extrasDinamicos.length > 0) {
        const placeholders = extrasDinamicos.map(() => '?').join(',');
        const extraRows = await query(
          `SELECT id, nome, preco_venda FROM servicos WHERE id IN (${placeholders}) AND categoria = 'wedding' AND ativo = 1`,
          extrasDinamicos
        );
        for (const extra of extraRows) {
          total += Number(extra.preco_venda ?? 0) || 0;
          itensSelecionados.push(String(extra.nome || ''));
        }
      }

      for (const id of ['heritage', 'cinematic', 'essencial']) {
        dados[MAPA_SHOW[id]] = id === planoId;
      }

      for (const pkg of ['heritage', 'cinematic', 'essencial']) {
        dados[`include_boudoir_${pkg}`] = pkg === planoId && extras.includes('boudoir_static');
        dados[`include_prewedding_${pkg}`] = pkg === planoId && extras.includes('prewedding_static');
      }

      dados['upgrades'] = dados['upgrades'] ?? {};
      dados['upgrades'][planoId] = dados['upgrades'][planoId] ?? [];
      for (const extraId of extrasDinamicos) {
        dados['upgrades'][planoId][extraId] = true;
      }

      dados['cliente_escolha'] = {
        plano_id: planoId,
        extras,
        itens_selecionados: itensSelecionados,
        valor_total: total,
        condicoes: condicoes ?? '',
        selecionado_em: dateBRFull(),
      };

      const novaLinhaAndamento = `${dateBR()} | Cliente selecionou o plano: ${NOME_PLANO[planoId]}`
        + (itensSelecionados.length > 0 ? ` com upgrades (${itensSelecionados.join(', ')})` : '')
        + ` | Investimento: ${formatarMoeda(total)} | Escolha realizada via proposta web`;

      const andamentoAtual = String(dados['andamento_proposta'] ?? '').trim();
      if (andamentoAtual !== '') {
        dados['andamento_proposta'] = andamentoAtual + '\n' + novaLinhaAndamento;
      } else {
        dados['andamento_proposta'] = novaLinhaAndamento;
      }

      await query(
        `UPDATE propostas SET dados_json = $1, valor_total = $2, status = 'pendente' WHERE id = $3`,
        [JSON.stringify(dados), total, proposta.id]
      );

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
        const conteudo = `Cliente escolheu o plano ${planoId} com investimento total de ${formatarMoeda(total)}.`;
        await query(
          `INSERT INTO propostas_historico (proposta_id, user_id, tipo, conteudo) VALUES ($1, $2, $3, $4)`,
          [proposta.id, 'publico', 'escolha_cliente', conteudo]
        );
      } catch (e) {}

      return res.status(200).json({ success: true, valor_total: total });
    } catch (err: any) {
      return res.status(500).json({ erro: err.message });
    }
  }

  return res.status(405).json({ erro: 'Método não permitido' });
}
