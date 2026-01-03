<?php
/**
 * Script de Migração - Criar coluna cto_id
 * Executado durante a instalação do addon
 */

// Carregar configuração de banco
require_once dirname(__FILE__) . '/../cto/config/database.php';

if (!isset($connection) || !$connection) {
    die("Erro: Não foi possível conectar ao banco de dados.\n");
}

// Verificar se coluna já existe
$check_sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME = 'sis_cliente' 
              AND COLUMN_NAME = 'cto_id' 
              AND TABLE_SCHEMA = 'mkradius'";

$result = @mysqli_query($connection, $check_sql);

if ($result && mysqli_num_rows($result) == 0) {
    // Coluna não existe, criar
    $alter_sql = "ALTER TABLE `sis_cliente` ADD COLUMN `cto_id` INT(11) NULL DEFAULT NULL AFTER `id`";
    
    if (@mysqli_query($connection, $alter_sql)) {
        echo "[✅] Coluna cto_id criada com sucesso em sis_cliente\n";
        
        // Adicionar índice
        $index_sql = "ALTER TABLE `sis_cliente` ADD INDEX `idx_cto_id` (`cto_id`)";
        @mysqli_query($connection, $index_sql);
        echo "[✅] Índice idx_cto_id criado com sucesso\n";
    } else {
        echo "[❌] Erro ao criar coluna cto_id: " . mysqli_error($connection) . "\n";
    }
} else {
    echo "[ℹ️] Coluna cto_id já existe em sis_cliente\n";
}

mysqli_close($connection);
?>
