<?php
/**
 * Model - Cliente/CTO
 * Responsável pelas operações com CTOs no banco de dados
 */

class Client {
    private $db_linker;
    private $table_name = 'mp_caixa';
    
    public function __construct($db_linker) {
        $this->db_linker = $db_linker;
    }
    
    /**
     * Obter todas as caixas cadastradas
     * Get all registered boxes
     */
    public function tabelaCaixa() {
        try {
            $sql = "SELECT * FROM {$this->table_name} ORDER BY id DESC";
            $result = $this->db_linker->query($sql);
            
            if (!$result) {
                error_log('Query Error: ' . $sql);
                return array();
            }
            
            $caixas = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $caixas[] = $row;
            }
            
            return $caixas;
        } catch (Exception $e) {
            error_log('Error in tabelaCaixa: ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Verificar se a tabela existe
     */
    public function verificaTabelaExiste() {
        try {
            $result = $this->db_linker->query(
                "SHOW TABLES LIKE '{$this->table_name}'"
            );
            return $result && mysqli_num_rows($result) > 0;
        } catch (Exception $e) {
            error_log('Error checking table: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Criar tabela se não existir
     */
    public function criarTabelaMp_Caixas() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                localizacao VARCHAR(500),
                portas INT DEFAULT 0,
                portas_livres INT DEFAULT 0,
                tipo VARCHAR(100),
                status VARCHAR(50) DEFAULT 'Ativo',
                observacoes TEXT,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $result = $this->db_linker->query($sql);
            return $result ? true : false;
        } catch (Exception $e) {
            error_log('Error creating table: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Adicionar nova caixa
     */
    public function adicionarCaixa($nome, $localizacao, $portas = 0, $tipo = '', $observacoes = '') {
        try {
            $nome = $this->db_linker->escape($nome);
            $localizacao = $this->db_linker->escape($localizacao);
            $tipo = $this->db_linker->escape($tipo);
            $observacoes = $this->db_linker->escape($observacoes);
            
            $sql = "INSERT INTO {$this->table_name} 
                    (nome, localizacao, portas, portas_livres, tipo, observacoes, status) 
                    VALUES 
                    ('$nome', '$localizacao', $portas, $portas, '$tipo', '$observacoes', 'Ativo')";
            
            $result = $this->db_linker->query($sql);
            return $result ? true : false;
        } catch (Exception $e) {
            error_log('Error adding box: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Editar caixa existente
     */
    public function editarCaixa($id, $nome, $localizacao, $portas = 0, $tipo = '', $observacoes = '') {
        try {
            $id = intval($id);
            $nome = $this->db_linker->escape($nome);
            $localizacao = $this->db_linker->escape($localizacao);
            $tipo = $this->db_linker->escape($tipo);
            $observacoes = $this->db_linker->escape($observacoes);
            
            $sql = "UPDATE {$this->table_name} 
                    SET nome='$nome', 
                        localizacao='$localizacao', 
                        portas=$portas,
                        portas_livres=$portas, 
                        tipo='$tipo', 
                        observacoes='$observacoes' 
                    WHERE id=$id";
            
            $result = $this->db_linker->query($sql);
            return $result ? true : false;
        } catch (Exception $e) {
            error_log('Error updating box: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Deletar caixa
     */
    public function deletarCaixa($id) {
        try {
            $id = intval($id);
            $sql = "DELETE FROM {$this->table_name} WHERE id=$id";
            $result = $this->db_linker->query($sql);
            return $result ? true : false;
        } catch (Exception $e) {
            error_log('Error deleting box: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter uma caixa por ID
     */
    public function obterCaixa($id) {
        try {
            $id = intval($id);
            $sql = "SELECT * FROM {$this->table_name} WHERE id=$id LIMIT 1";
            $result = $this->db_linker->query($sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                return mysqli_fetch_assoc($result);
            }
            return null;
        } catch (Exception $e) {
            error_log('Error fetching box: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Contar total de caixas
     */
    public function contarCaixas() {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->table_name}";
            $result = $this->db_linker->query($sql);
            
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                return $row['total'];
            }
            return 0;
        } catch (Exception $e) {
            error_log('Error counting boxes: ' . $e->getMessage());
            return 0;
        }
    }
}

// Criar instância se houver conexão
if (isset($db_linker) && $db_linker->isConnected()) {
    $client = new Client($db_linker);
}
