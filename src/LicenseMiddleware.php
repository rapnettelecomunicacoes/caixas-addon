<?php
/**
 * VERIFICADOR DE LICENÇA - Middleware
 * Verifica se há licença válida antes de carregar o addon
 * ⚠️ AGORA COM SUPORTE A BANCO DE DADOS
 */

require_once dirname(__FILE__) . '/LicenseManager.php';

class LicenseMiddleware {
    private $licenseManager;
    private $licenseStatus;
    
    public function __construct() {
        $this->licenseManager = new LicenseManager();
        $this->licenseStatus = $this->licenseManager->getLicenseStatus();
    }
    
    /**
     * Verifica se a licença é válida
     * @return bool
     */
    public function isValid() {
        return $this->licenseStatus['instalada'] && 
               !isset($this->licenseStatus['expirada']) || 
               !$this->licenseStatus['expirada'];
    }
    
    /**
     * Obtém o status da licença
     * @return array
     */
    public function getStatus() {
        return $this->licenseStatus;
    }
    
    /**
     * Verifica se a licença está próxima de expirar
     * @return bool
     */
    public function isNearExpiration() {
        return isset($this->licenseStatus['proxima_expiracao']) && 
               $this->licenseStatus['proxima_expiracao'];
    }
    
    /**
     * Obtém mensagem de aviso se houver
     * @return string|null
     */
    public function getWarningMessage() {
        if (!$this->licenseStatus['instalada']) {
            return null;
        }
        
        if (isset($this->licenseStatus['expirada']) && $this->licenseStatus['expirada']) {
            return "⚠️ LICENÇA EXPIRADA em {$this->licenseStatus['expiracao']}";
        }
        
        if (isset($this->licenseStatus['dias_restantes']) && $this->licenseStatus['dias_restantes'] < 30) {
            return "⚠️ Licença expira em {$this->licenseStatus['dias_restantes']} dias";
        }
        
        return null;
    }
    
    /**
     * Renderiza um aviso na página se necessário
     */
    public function renderWarning() {
        $warning = $this->getWarningMessage();
        
        if (!$warning) return;
        
        echo <<<HTML
        <div style="position: fixed; top: 0; left: 0; right: 0; background: #fff3cd; 
                    color: #856404; padding: 15px; text-align: center; z-index: 9999; 
                    border-bottom: 1px solid #ffeaa7;">
            {$warning}
        </div>
        <script>
            // Deslocar conteúdo para baixo do aviso
            document.body.style.paddingTop = '45px';
        </script>
        HTML;
    }
}

?>
