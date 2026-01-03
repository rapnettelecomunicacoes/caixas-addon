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

// Tentar conectar usando novo mysqlnd driver (devolvendo object)
try {
    if ($socket) {
        // Usar mysqli com sintaxe orientada a objetos
        $connection = new mysqli('localhost', $user, $pass, $db_name, 0, $socket);
    } else {
        $connection = new mysqli($Host, $user, $pass, $db_name);
    }
    
    // Verificar conexão
    if ($connection->connect_error) {
        error_log("Erro de conexão ao banco: " . $connection->connect_error);
        $connection = null;
    } else {
        // Set charset
        $connection->set_charset("utf8mb4");
    }
} catch (Exception $e) {
    error_log("Exceção ao conectar ao banco: " . $e->getMessage());
    $connection = null;
}
?>
