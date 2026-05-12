<?php
/**
 * Configurações do SaaS de Roteiros
 * Apenas constantes não-sensíveis.
 * Preços, API keys e URLs de checkout ficam no banco (configuracao_empresa).
 */

// Trial
define('TRIAL_DIAS',            35);

// Limites diários DO TRIAL (assinante pago: sem limite)
define('TRIAL_LIMITE_DIA_1',    35);   // Dia 0 desde cadastro
define('TRIAL_LIMITE_DIA_2',    10);   // Dia 1
define('TRIAL_LIMITE_DIA_N',     4);   // Dia 2 até 34

// Base de conhecimento
define('CONHECIMENTO_LIMITE',   50);   // arquivos ativos por usuário

// Limites de IA por usuário/mês (0 = sem limite — tier gratuito atual)
define('IA_CHAMADAS_MENSAIS_LIMITE', 0);

// Preços padrão (usados como fallback se não configurado no banco)
define('PLANO_MENSAL_PRECO',  15.00);
define('PLANO_ANUAL_PRECO',  158.00);
