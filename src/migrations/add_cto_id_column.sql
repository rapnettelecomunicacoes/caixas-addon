-- Migration: Adicionar coluna cto_id à tabela sis_cliente
-- Descrição: Cria a coluna cto_id para relacionamento com mp_caixa se não existir

-- Verificar e adicionar coluna cto_id
ALTER TABLE `sis_cliente` ADD COLUMN `cto_id` INT(11) NULL DEFAULT NULL AFTER `id`;

-- Adicionar índice na coluna cto_id para melhor performance
ALTER TABLE `sis_cliente` ADD INDEX `idx_cto_id` (`cto_id`);
