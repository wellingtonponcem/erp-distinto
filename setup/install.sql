-- Sistema DISTINTO — Schema do banco de dados
-- Executar no MySQL Hostinger via phpMyAdmin

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Usuários
CREATE TABLE IF NOT EXISTS `users` (
  `id` VARCHAR(32) NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `senha` VARCHAR(255) NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Configuração da empresa
CREATE TABLE IF NOT EXISTS `configuracao_empresa` (
  `id` VARCHAR(32) NOT NULL DEFAULT 'principal',
  `nome` VARCHAR(255) NOT NULL DEFAULT 'Minha Agência',
  `logo` VARCHAR(500),
  `cnpj` VARCHAR(20),
  `telefone` VARCHAR(30),
  `email` VARCHAR(255),
  `endereco` TEXT,
  `validade_proposta` INT DEFAULT 15,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Custos fixos mensais
CREATE TABLE IF NOT EXISTS `custos_fixos` (
  `id` VARCHAR(32) NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `valor` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `categoria` VARCHAR(100) NOT NULL DEFAULT 'outros',
  `recorrencia` ENUM('mensal','anual') NOT NULL DEFAULT 'mensal',
  `dia_vencimento` INT NOT NULL DEFAULT 5,
  `forma_pagamento` VARCHAR(50) DEFAULT 'pix',
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lançamentos financeiros (contas a pagar e receber)
CREATE TABLE IF NOT EXISTS `lancamentos` (
  `id` VARCHAR(32) NOT NULL,
  `tipo` ENUM('receber','pagar') NOT NULL,
  `descricao` VARCHAR(500) NOT NULL,
  `valor` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `valor_pago` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `categoria` VARCHAR(100) NOT NULL DEFAULT 'outros',
  `cliente_fornecedor` VARCHAR(255),
  `cliente_id` VARCHAR(32) NULL,
  `fornecedor_id` VARCHAR(32) NULL,
  `vencimento` DATE NOT NULL,
  `status` ENUM('pendente','pago_parcial','pago','cancelado','atrasado') NOT NULL DEFAULT 'pendente',
  `modalidade` ENUM('avista','parcelado','recorrente') NOT NULL DEFAULT 'avista',
  `total_parcelas` INT,
  `parcela_atual` INT,
  `lancamento_pai_id` VARCHAR(32),
  `frequencia` ENUM('semanal','mensal','anual'),
  `data_termino` DATE,
  `observacao` TEXT,
  `forma_pagamento` VARCHAR(50),
  `custo_fixo_id` VARCHAR(32),
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_status` (`status`),
  KEY `idx_vencimento` (`vencimento`),
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_fornecedor_id` (`fornecedor_id`),
  KEY `idx_custo_fixo` (`custo_fixo_id`),
  CONSTRAINT `fk_lancamento_pai` FOREIGN KEY (`lancamento_pai_id`) REFERENCES `lancamentos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Serviços da agência
CREATE TABLE IF NOT EXISTS `servicos` (
  `id` VARCHAR(32) NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `descricao` TEXT,
  `horas_estimadas` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `custo_producao` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `custos_variaveis` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `markup` DECIMAL(5,2) NOT NULL DEFAULT 30.00,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sprint 2: estrutura preparada (sem interface ainda)
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` VARCHAR(32) NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `cpf_cnpj` VARCHAR(20),
  `contato` VARCHAR(255),
  `segmento` VARCHAR(100),
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fornecedores` (
  `id` VARCHAR(32) NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `contato` VARCHAR(255),
  `telefone` VARCHAR(50),
  `email` VARCHAR(255),
  `categoria` VARCHAR(100),
  `observacao` TEXT,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `oportunidades` (
  `id` VARCHAR(32) NOT NULL,
  `cliente_id` VARCHAR(32) NULL,
  `nome` VARCHAR(255) NOT NULL,
  `valor_estimado` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `etapa` ENUM('novo','qualificado','proposta','negociacao','ganha','perdida') NOT NULL DEFAULT 'novo',
  `previsao` DATE NULL,
  `responsavel` VARCHAR(255),
  `descricao` TEXT,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_etapa` (`etapa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `contratos` (
  `id` VARCHAR(32) NOT NULL,
  `cliente_id` VARCHAR(32),
  `descricao` VARCHAR(500),
  `valor` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `inicio` DATE NOT NULL,
  `fim` DATE,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_contrato_cliente` (`cliente_id`),
  CONSTRAINT `fk_contrato_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- Inserir configuração padrão
INSERT IGNORE INTO `configuracao_empresa` (`id`, `nome`) VALUES ('principal', 'Minha Agência');
