<?php
/**
 * Configuração de Banco de Dados
 * Database Configuration
 */

// Credenciais do Banco de Dados
$Host = 'localhost';
$user = 'root';
$pass = 'vertrigo';
$db_name = 'mkradius';
$table_name = 'mp_caixa';

// Socket Unix para conexão local (MariaDB/MySQL)
$socket = '/var/run/mysqld/mysqld.sock';

// Verifica se o socket existe, caso contrário usa TCP
if (!file_exists($socket)) {
    $socket = null;
}

// Tentar conectar com tratamento de erro
if ($socket) {
    @$connection = mysqli_connect('localhost', $user, $pass, $db_name, 0, $socket);
} else {
    @$connection = mysqli_connect($Host, $user, $pass, $db_name);
}

// Verificar conexão
if (!$connection) {
    error_log("Erro de conexão ao banco: " . mysqli_connect_error(), 3, dirname(__FILE__) . '/../../database/db_error.log');
}

// Set charset
if ($connection) {
    mysqli_set_charset($connection, "utf8mb4");
}
?>
