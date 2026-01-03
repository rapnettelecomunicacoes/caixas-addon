<?php
/**
 * GERENCIADOR DE LICENÇA SIMPLIFICADO
 * Funciona com arquivos JSON, sem dependência de banco de dados
 */

class LicenseManager {
    private $license_dir = '/var/tmp';
    private $license_file = null;
    
    public function __construct() {
        $this->license_file = $this->license_dir . '/license_caixas.json';
        if (!is_dir($this->license_dir)) {
            mkdir($this->license_dir, 0777, true);
        }
    }
    
    /**
     * Salvar licença
     */
    public function saveLicense($code, $cliente = 'Local', $email = 'admin@local') {
        $license_data = [
            'chave' => $code,
            'cliente' => $cliente,
            'email' => $email,
            'data_criacao' => date('Y-m-d H:i:s'),
            'expiracao' => date('Y-m-d', strtotime('+1 year')),
            'status' => 'ativa'
        ];
        
        file_put_contents($this->license_file, json_encode($license_data, JSON_PRETTY_PRINT));
        chmod($this->license_file, 0666);
        
        return $license_data;
    }
    
    /**
     * Verificar licença
     */
    public function getLicense() {
        if (!file_exists($this->license_file)) {
            return null;
        }
        
        $content = file_get_contents($this->license_file);
        $data = json_decode($content, true);
        
        // Verificar expiração
        if (isset($data['expiracao'])) {
            $exp_time = strtotime($data['expiracao']);
            if (time() > $exp_time) {
                return null; // Expirada
            }
        }
        
        return $data;
    }
    
    /**
     * Validar código de licença
     */
    public function validateLicense($code) {
        // Aceita qualquer código no formato XXXX-XXXX-XXXX-XXXX
        if (preg_match('/^[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}$/i', $code)) {
            return $this->saveLicense($code);
        }
        
        return false;
    }
    
    /**
     * Obter status
     */
    public function getStatus() {
        $license = $this->getLicense();
        
        if (!$license) {
            return [
                'instalada' => false,
                'expirada' => false,
                'cliente' => 'N/A'
            ];
        }
        
        $exp_time = strtotime($license['expiracao']);
        $expirada = time() > $exp_time;
        
        return [
            'instalada' => true,
            'expirada' => $expirada,
            'cliente' => $license['cliente'] ?? 'Local',
            'expiracao' => $license['expiracao'],
            'chave' => $license['chave']
        ];
    }
}
