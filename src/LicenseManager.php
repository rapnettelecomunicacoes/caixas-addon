<?php
/**
 * SISTEMA DE LICENCIAMENTO - GERENCIADOR FTTH v2.0
 * Gerador de Chaves de Licença
 * Apenas para administradores
 * ⚠️ AGORA COM SUPORTE A BANCO DE DADOS
 */

class LicenseDB {
    private $pdo;
    
    public function __construct() {
        try {
            // Usar credenciais padrão do mk-auth
            $this->pdo = new PDO(
                'mysql:host=127.0.0.1;dbname=mkradius;charset=utf8mb4',
                'root',
                'vertrigo'
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("Erro de conexão ao banco: " . $e->getMessage());
            $this->pdo = null;
        }
    }
    
    public function isConnected() {
        return $this->pdo !== null;
    }
    
    /**
     * Salva/Atualiza licença no banco
     */
    public function saveLicense($chave, $dados) {
        if (!$this->isConnected()) {
            return ['erro' => 'Banco de dados indisponível'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO licenses (chave, cliente, email, provedor, criacao, expiracao, dias, status, instalada_em, servidor) 
                VALUES (:chave, :cliente, :email, :provedor, :criacao, :expiracao, :dias, 'ativa', :instalada_em, :servidor)
                ON DUPLICATE KEY UPDATE 
                    cliente = VALUES(cliente),
                    email = VALUES(email),
                    provedor = VALUES(provedor),
                    expiracao = VALUES(expiracao),
                    dias = VALUES(dias),
                    status = 'ativa',
                    instalada_em = VALUES(instalada_em),
                    servidor = VALUES(servidor),
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([
                ':chave' => $chave,
                ':cliente' => $dados['cliente'],
                ':email' => $dados['email'] ?? '',
                ':provedor' => $dados['provedor'] ?? '',
                ':criacao' => $dados['criacao'],
                ':expiracao' => $dados['expiracao'] ?? null,
                ':dias' => $dados['dias'] ?? 365,
                ':instalada_em' => date('Y-m-d H:i:s'),
                ':servidor' => gethostname()
            ]);
            
            return ['sucesso' => true, 'mensagem' => 'Licença salva com sucesso no banco'];
        } catch (PDOException $e) {
            error_log("Erro ao salvar licença: " . $e->getMessage());
            return ['erro' => 'Erro ao salvar licença: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtém status da licença
     */
    public function getLicenseStatus() {
        if (!$this->isConnected()) {
            return [
                'instalada' => false,
                'mensagem' => 'Banco de dados indisponível'
            ];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM licenses 
                WHERE status = 'ativa' 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute();
            $license = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$license) {
                return [
                    'instalada' => false,
                    'mensagem' => 'Nenhuma licença instalada'
                ];
            }
            
            $status = [
                'instalada' => true,
                'cliente' => $license['cliente'],
                'criacao' => $license['criacao'],
                'expiracao' => $license['expiracao'],
                'servidor' => $license['servidor'] ?? 'N/A',
                'instalado_em' => $license['instalada_em'] ?? 'N/A'
            ];
            
            // Verificar validade
            if ($license['expiracao']) {
                $expiracao_time = strtotime($license['expiracao']);
                $dias_restantes = floor(($expiracao_time - time()) / 86400);
                $status['dias_restantes'] = $dias_restantes;
                $status['expirada'] = $dias_restantes < 0;
                $status['proxima_expiracao'] = $dias_restantes < 30;
            } else {
                $status['dias_restantes'] = 'ILIMITADO';
            }
            
            return $status;
            
        } catch (PDOException $e) {
            error_log("Erro ao obter status: " . $e->getMessage());
            return [
                'instalada' => false,
                'erro' => 'Erro ao ler licença'
            ];
        }
    }
    
    /**
     * Obtém licença por chave
     */
    public function getLicenseByKey($chave) {
        if (!$this->isConnected()) return null;
        
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM licenses WHERE chave = :chave");
            $stmt->execute([':chave' => $chave]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao obter licença: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtém todas as licenças
     */
    public function getAllLicenses() {
        if (!$this->isConnected()) return [];
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM licenses 
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            $licenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Adicionar cálculo de dias restantes
            foreach ($licenses as &$license) {
                if ($license['expiracao']) {
                    $expiracao_time = strtotime($license['expiracao']);
                    if ($expiracao_time !== false) {
                        $dias_restantes = floor(($expiracao_time - time()) / 86400);
                        $license['dias_restantes'] = max(0, $dias_restantes);
                        $license['expirada'] = $dias_restantes < 0;
                    }
                } else {
                    $license['dias_restantes'] = 'ILIMITADO';
                    $license['expirada'] = false;
                }
            }
            
            return $licenses;
        } catch (PDOException $e) {
            error_log("Erro ao obter licenças: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Deleta licença
     */
    public function deleteLicense($chave) {
        if (!$this->isConnected()) {
            return ['erro' => 'Banco de dados indisponível'];
        }
        
        try {
            $stmt = $this->pdo->prepare("DELETE FROM licenses WHERE chave = :chave");
            $result = $stmt->execute([':chave' => $chave]);
            
            if ($stmt->rowCount() > 0) {
                return ['sucesso' => true, 'mensagem' => 'Licença deletada com sucesso'];
            } else {
                return ['erro' => 'Licença não encontrada'];
            }
        } catch (PDOException $e) {
            error_log("Erro ao deletar licença: " . $e->getMessage());
            return ['erro' => 'Erro ao deletar licença'];
        }
    }
    
    /**
     * Atualiza status da licença
     */
    public function updateStatus($chave, $status) {
        if (!$this->isConnected()) return false;
        
        try {
            $stmt = $this->pdo->prepare("UPDATE licenses SET status = :status WHERE chave = :chave");
            return $stmt->execute([':status' => $status, ':chave' => $chave]);
        } catch (PDOException $e) {
            error_log("Erro ao atualizar status: " . $e->getMessage());
            return false;
        }
    }
}

class LicenseGenerator {
    private $app_key = "GERENCIADOR-FTTH-2026";
    private $max_days = 365;
    private $db;
    
    public function __construct() {
        $this->db = new LicenseDB();
    }
    
    /**
     * Gera uma nova chave de licença
     * @param string $cliente - Nome do cliente
     * @param int $dias - Número de dias válidos (default 365)
     * @param bool $forever - Se true, licença nunca expira
     * @param string $email - Email do cliente (opcional)
     * @param string $provedor - Nome do provedor (opcional)
     * @return array - Contém a chave gerada e informações
     */
    public function generateLicense($cliente, $dias = 365, $forever = false, $email = '', $provedor = '') {
        // Validações
        if (empty($cliente)) {
            return ['erro' => 'Nome do cliente obrigatório'];
        }
        
        if (!$forever && ($dias <= 0 || $dias > 3650)) {
            return ['erro' => 'Dias deve estar entre 1 e 3650'];
        }
        
        // Gerar data de expiração
        $data_criacao = time();
        $data_expiracao = $forever ? 0 : $data_criacao + ($dias * 86400);
        
        // Se for vitalícia, dias deve ser 0
        $dias_registro = $forever ? 0 : $dias;
        
        // Montar dados da licença
        $licenseData = [
            'cliente' => $cliente,
            'email' => $email,
            'provedor' => $provedor,
            'criacao' => date('Y-m-d H:i:s', $data_criacao),
            'expiracao' => $data_expiracao > 0 ? date('Y-m-d H:i:s', $data_expiracao) : 'NUNCA',
            'dias' => $dias_registro,
            'versao' => '2.0',
            'app' => 'GERENCIADOR-FTTH'
        ];
        
        // Gerar hash da licença
        $license_string = json_encode($licenseData) . $this->app_key;
        $license_hash = hash('sha256', $license_string);
        
        // Gerar chave formatada (XXXX-XXXX-XXXX-XXXX)
        $chave = substr(strtoupper($license_hash), 0, 32);
        $chave_formatada = substr($chave, 0, 4) . '-' . 
                          substr($chave, 4, 4) . '-' . 
                          substr($chave, 8, 4) . '-' . 
                          substr($chave, 12, 4);
        
        // Salvar no banco
        $this->db->saveLicense($chave_formatada, $licenseData);
        
        return [
            'sucesso' => true,
            'chave' => $chave_formatada,
            'hash' => $license_hash,
            'cliente' => $cliente,
            'email' => $email,
            'provedor' => $provedor,
            'criacao' => $licenseData['criacao'],
            'expiracao' => $licenseData['expiracao'],
            'dias' => $dias_registro,
            'mensagem' => "Licença gerada com sucesso para $cliente"
        ];
    }
    
    /**
     * Valida uma chave de licença
     * @param string $chave - Chave a validar
     * @param string $cliente - Nome do cliente (opcional)
     * @return array - Resultado da validação
     */
    public function validateLicense($chave, $cliente = null) {
        if (empty($chave)) {
            return ['valida' => false, 'erro' => 'Chave vazia'];
        }
        
        // Remover formatação
        $chave_limpa = str_replace('-', '', $chave);
        
        // Procurar licença no banco
        $license = $this->db->getLicenseByKey($chave);
        
        if (!$license) {
            return ['valida' => false, 'erro' => 'Licença não encontrada'];
        }
        
        // Validar cliente se fornecido
        if ($cliente && $license['cliente'] !== $cliente) {
            return ['valida' => false, 'erro' => 'Licença não corresponde ao cliente'];
        }
        
        // Validar expiração
        if ($license['expiracao']) {
            $expiracao_time = strtotime($license['expiracao']);
            if (time() > $expiracao_time) {
                return [
                    'valida' => false,
                    'erro' => 'Licença expirada em ' . $license['expiracao'],
                    'expirada' => true
                ];
            }
            
            // Avisar se está próxima de expirar (30 dias)
            $dias_restantes = floor(($expiracao_time - time()) / 86400);
            if ($dias_restantes < 30) {
                return [
                    'valida' => true,
                    'aviso' => "Licença expira em $dias_restantes dias",
                    'dias_restantes' => $dias_restantes,
                    'cliente' => $license['cliente']
                ];
            }
        }
        
        return [
            'valida' => true,
            'cliente' => $license['cliente'],
            'criacao' => $license['criacao'],
            'expiracao' => $license['expiracao'],
            'versao' => '2.0'
        ];
    }
    
    /**
     * Obtém status da licença
     */
    public function getLicenseStatus() {
        return $this->db->getLicenseStatus();
    }
    
    /**
     * Retorna todas as licenças
     */
    public function getAllLicenses() {
        return $this->db->getAllLicenses();
    }
    
    /**
     * Deleta uma licença
     */
    public function deleteLicense($chave) {
        return $this->db->deleteLicense($chave);
    }
    
    /**
     * Salva licença (mantém para compatibilidade)
     */
    public function saveLicense($chave, $dados) {
        return $this->db->saveLicense($chave, $dados);
    }
}

// Alias para compatibilidade
class LicenseManager extends LicenseGenerator {}

?>
