<?php
/**
 * Gerenciador de Conexão com Banco de Dados
 * Database Connection Manager
 */

class DatabaseManager {
    private $connection;
    private $host;
    private $user;
    private $pass;
    private $db_name;
    private $socket;
    
    public function __construct($host, $user, $pass, $db_name, $socket = null) {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->db_name = $db_name;
        $this->socket = $socket;
        $this->connect();
    }
    
    private function connect() {
        try {
            // Conectar com socket se disponível
            if ($this->socket && file_exists($this->socket)) {
                $this->connection = mysqli_connect(
                    $this->host,
                    $this->user,
                    $this->pass,
                    $this->db_name,
                    0,
                    $this->socket
                );
            } else {
                // Fallback para TCP
                $this->connection = mysqli_connect(
                    $this->host,
                    $this->user,
                    $this->pass,
                    $this->db_name
                );
            }
            
            if (!$this->connection) {
                throw new Exception('Erro de conexão: ' . mysqli_connect_error());
            }
            
            // Set charset to UTF-8
            mysqli_set_charset($this->connection, 'utf8mb4');
        } catch (Exception $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            $this->connection = null;
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function isConnected() {
        return $this->connection !== null;
    }
    
    public function close() {
        if ($this->connection) {
            mysqli_close($this->connection);
        }
    }
    
    public function query($sql) {
        if (!$this->connection) {
            return null;
        }
        return mysqli_query($this->connection, $sql);
    }
    
    public function escape($str) {
        if (!$this->connection) {
            return addslashes($str);
        }
        return mysqli_real_escape_string($this->connection, $str);
    }
    
    public function insert_id() {
        if (!$this->connection) {
            return 0;
        }
        return mysqli_insert_id($this->connection);
    }
    
    public function error() {
        if (!$this->connection) {
            return 'No connection';
        }
        return mysqli_error($this->connection);
    }
}

// Criar instância global
require_once __DIR__ . '/../config/database.php';

$db_linker = new DatabaseManager($Host, $user, $pass, $db_name, $socket);

if (!$db_linker->isConnected()) {
    error_log('Failed to connect to database');
}
