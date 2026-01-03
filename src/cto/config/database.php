<?php
/**
 * Configuração de Banco de Dados
 * Database Configuration
 * Suporta múltiplos servidores com configuração dinâmica
 */

// Configuração padrão (fallback)
$Host = 'localhost';
$user = 'root';
$pass = 'vertrigo';
$db_name = 'mkradius';
$table_name = 'mp_caixa';

// Socket Unix para conexão local (MariaDB/MySQL)
$socket = '/var/run/mysqld/mysqld.sock';

// Tentar carregar configuração local (por servidor)
$config_dir = dirname(__FILE__);
$local_config = $config_dir . '/database.local.php';

if (file_exists($local_config)) {
    // Se existe arquivo de configuração local, usa aquele
    require_once $local_config;
}

// Verifica se o socket existe, caso contrário usa TCP
if (!file_exists($socket)) {
    $socket = null;
}

// Log da configuração carregada (apenas em desenvolvimento)
if (defined('DEBUG_DATABASE_CONFIG') && DEBUG_DATABASE_CONFIG) {
    error_log("[DATABASE] Host: $Host | DB: $db_name | Config: " . (file_exists($local_config) ? 'LOCAL' : 'DEFAULT'));
}
