<?php
/**
 * SISTEMA DE LICENCIAMENTO - GERENCIADOR FTTH v2.0
 * Gerador de Chaves de Licença - Versão JSON
 * Apenas para administradores
 */

class LicenseManager {
    private $licenseFile;
    private $licenseData;
    
    public function __construct() {
        // Arquivo de licença em /var/tmp
        $this->licenseFile = '/var/tmp/license_caixas.json';
        $this->loadLicense();
    }
    
    /**
     * Carrega dados da licença do arquivo JSON
     */
    private function loadLicense() {
        if (file_exists($this->licenseFile)) {
            $content = file_get_contents($this->licenseFile);
            $this->licenseData = json_decode($content, true);
        } else {
            $this->licenseData = null;
        }
    }
    
    /**
     * Obtém status da licença
     */
    public function getLicenseStatus() {
        if (!$this->licenseData) {
            return [
                'instalada' => false,
                'expirada' => true,
                'mensagem' => 'Nenhuma licença instalada'
            ];
        }
        
        $expiracao = strtotime($this->licenseData['expiracao'] ?? date('Y-m-d'));
        $hoje = strtotime(date('Y-m-d'));
        $expirada = $expiracao < $hoje;
        
        $dias_restantes = ceil(($expiracao - $hoje) / 86400);
        
        return [
            'instalada' => true,
            'expirada' => $expirada,
            'cliente' => $this->licenseData['cliente'] ?? 'N/A',
            'chave' => $this->licenseData['chave'] ?? 'N/A',
            'expiracao' => $this->licenseData['expiracao'] ?? 'N/A',
            'dias_restantes' => max(0, $dias_restantes),
            'proxima_expiracao' => $dias_restantes < 30 && !$expirada,
            'mensagem' => $expirada ? 'Licença expirada' : 'Licença válida'
        ];
    }
    
    /**
     * Salva licença no arquivo JSON
     */
    public function saveLicense($chave, $cliente, $expiracao) {
        $licenseData = [
            'chave' => $chave,
            'cliente' => $cliente,
            'expiracao' => $expiracao,
            'criacao' => date('Y-m-d H:i:s'),
            'instalada_em' => date('Y-m-d H:i:s'),
            'servidor' => gethostname()
        ];
        
        if (file_put_contents($this->licenseFile, json_encode($licenseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            chmod($this->licenseFile, 0644);
            return ['sucesso' => true, 'mensagem' => 'Licença salva com sucesso'];
        } else {
            return ['erro' => true, 'mensagem' => 'Erro ao salvar licença no arquivo JSON'];
        }
    }
    
    /**
     * Remove licença
     */
    public function removeLicense() {
        if (file_exists($this->licenseFile)) {
            unlink($this->licenseFile);
            return ['sucesso' => true, 'mensagem' => 'Licença removida'];
        }
        return ['erro' => true, 'mensagem' => 'Nenhuma licença para remover'];
    }
    
    /**
     * Obtém caminho do arquivo de licença
     */
    public function getLicenseFile() {
        return $this->licenseFile;
    }
    
    /**
     * Verifica se há uma licença válida
     */
    public function isValid() {
        $status = $this->getLicenseStatus();
        return $status['instalada'] && !$status['expirada'];
    }
}
?>
