<?php
/**
 * Gerenciador de Configuração do Servidor
 * Detecta e armazena URL do servidor mk-auth
 */

class ServerConfig {
    private $configFile = '/var/tmp/server_config.json';
    
    /**
     * Obter URL do mk-auth admin
     */
    public function getAdminUrl() {
        // Tentar ler do arquivo de config
        if (file_exists($this->configFile)) {
            $conteudo = file_get_contents($this->configFile);
            $dados = json_decode($conteudo, true);
            if (isset($dados['admin_url'])) {
                return rtrim($dados['admin_url'], '/') . '/';
            }
        }
        
        // Fallback: detectar da requisição atual
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        
        // Remover /admin/addons/caixas e voltar para /admin/
        return $protocol . '://' . $host . '/admin/';
    }
    
    /**
     * Salvar URL do servidor
     */
    public function saveAdminUrl($url) {
        $dados = [
            'admin_url' => rtrim($url, '/') . '/',
            'created_at' => date('c'),
            'server_name' => gethostname()
        ];
        
        $json = json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if (file_put_contents($this->configFile, $json)) {
            chmod($this->configFile, 0644);
            @chown($this->configFile, 'www-data');
            @chgrp($this->configFile, 'www-data');
            return true;
        }
        
        return false;
    }
}
?>
