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
        
        // Se não está marcada como instalada, retornar não instalada
        if (isset($this->licenseData['instalada']) && !$this->licenseData['instalada']) {
            return [
                'instalada' => false,
                'expirada' => true,
                'mensagem' => 'Licença aguardando ativação'
            ];
        }
        
        // Se não tem chave, não está instalada
        if (empty($this->licenseData['chave'])) {
            return [
                'instalada' => false,
                'expirada' => true,
                'mensagem' => 'Licença não ativada'
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
            'instalada' => true,
            'chave' => $chave,
            'cliente' => $cliente,
            'expiracao' => $expiracao,
            'criacao' => date('Y-m-d H:i:s'),
            'instalada_em' => date('Y-m-d H:i:s'),
            'servidor' => gethostname()
        ];
        
        if (file_put_contents($this->licenseFile, json_encode($licenseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            chmod($this->licenseFile, 0666);
            @chown($this->licenseFile, 'www-data');
            @chgrp($this->licenseFile, 'www-data');
            $this->licenseData = $licenseData;
            return ['sucesso' => true, 'mensagem' => 'Licença ativada com sucesso'];
        } else {
            return ['erro' => true, 'mensagem' => 'Erro ao salvar licença no arquivo JSON'];
        }
    }
    
    /**
     * Ativa uma licença com validação
     */
    public function activateLicense($chave, $cliente, $dias = 365) {
        // Validar chave (formato básico: XXXX-XXXX-XXXX-XXXX)
        if (!preg_match('/^[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}$/', $chave)) {
            return ['erro' => true, 'mensagem' => 'Formato de chave inválido'];
        }
        
        // Calcular data de expiração
        $expiracao = date('Y-m-d', strtotime("+$dias days"));
        
        // Salvar licença
        return $this->saveLicense($chave, $cliente, $expiracao);
    }
    
    /**
     * Gera uma nova chave de licença aleatória
     */
    public function generateLicense($cliente, $dias = 365, $forever = false, $email = '', $provedor = '') {
        // Gerar chave aleatória no formato XXXX-XXXX-XXXX-XXXX
        $chave = $this->generateRandomKey();
        
        // Calcular expiração
        if ($forever) {
            $expiracao = date('Y-m-d', strtotime('+10 years'));
        } else {
            $expiracao = date('Y-m-d', strtotime("+$dias days"));
        }
        
        // Dados da licença
        $licenseData = [
            'instalada' => true,
            'chave' => $chave,
            'cliente' => $cliente,
            'email' => $email,
            'provedor' => $provedor,
            'expiracao' => $expiracao,
            'dias' => $dias,
            'criacao' => date('Y-m-d H:i:s'),
            'instalada_em' => date('Y-m-d H:i:s'),
            'servidor' => gethostname()
        ];
        
        // Carregar arquivo existente para manter histórico
        $historico = [];
        if (file_exists($this->licenseFile)) {
            $conteudo = file_get_contents($this->licenseFile);
            $dadosExistentes = json_decode($conteudo, true);
            if (isset($dadosExistentes['historico'])) {
                $historico = $dadosExistentes['historico'];
            }
        }
        
        // Adicionar nova licença ao histórico
        $historico[] = $licenseData;
        
        // Dados completos com histórico
        $dadosCompletos = array_merge($licenseData, ['historico' => $historico]);
        
        // Salvar no arquivo
        if (file_put_contents($this->licenseFile, json_encode($dadosCompletos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            chmod($this->licenseFile, 0666);
            @chown($this->licenseFile, 'www-data');
            @chgrp($this->licenseFile, 'www-data');
            $this->licenseData = $licenseData;
            return [
                'sucesso' => true, 
                'mensagem' => 'Licença gerada com sucesso',
                'chave' => $chave,
                'cliente' => $cliente,
                'email' => $email,
                'provedor' => $provedor,
                'criacao' => date('Y-m-d H:i:s'),
                'expiracao' => $expiracao,
                'dias' => $dias
            ];
        } else {
            return ['erro' => true, 'mensagem' => 'Erro ao gerar licença'];
        }
    }
    
    /**
     * Gera uma chave aleatória no formato XXXX-XXXX-XXXX-XXXX
     */
    private function generateRandomKey() {
        $chars = 'ABCDEF0123456789';
        $key = '';
        for ($i = 0; $i < 4; $i++) {
            for ($j = 0; $j < 4; $j++) {
                $key .= $chars[rand(0, strlen($chars) - 1)];
            }
            if ($i < 3) $key .= '-';
        }
        return $key;
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
    
    /**
     * Retorna array com todas as licenças geradas (histórico)
     */
    public function getAllLicenses() {
        if (!file_exists($this->licenseFile)) {
            return [];
        }
        
        $conteudo = file_get_contents($this->licenseFile);
        $dados = json_decode($conteudo, true);
        
        if (!isset($dados['historico'])) {
            return [];
        }
        
        // Normalizar dados para adicionar chaves padrão
        $licencas = [];
        foreach ($dados['historico'] as $lic) {
            $licenca = [
                'cliente' => $lic['cliente'] ?? 'N/A',
                'chave' => $lic['chave'] ?? '',
                'email' => $lic['email'] ?? '',
                'provedor' => $lic['provedor'] ?? '',
                'criacao' => $lic['criacao'] ?? $lic['created_at'] ?? '',
                'expiracao' => $lic['expiracao'] ?? '',
                'status' => 'ativa',
                'expirada' => false,
                'dias_restantes' => 'ILIMITADO'
            ];
            
            // Verificar se expirada
            if (!empty($lic['expiracao'])) {
                $dataExp = strtotime($lic['expiracao']);
                $agora = time();
                
                if ($agora > $dataExp) {
                    $licenca['expirada'] = true;
                    $licenca['status'] = 'expirada';
                    $licenca['dias_restantes'] = 0;
                } else {
                    $dias = intval(($dataExp - $agora) / 86400);
                    $licenca['dias_restantes'] = $dias;
                }
            }
            
            $licencas[] = $licenca;
        }
        
        return $licencas;
    }
}
?>
