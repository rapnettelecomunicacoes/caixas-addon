<?php
require '../../../vendor/autoload.php';
require '../../../net/ssh2.php'; // Arquivo SSH2 local
require '../../../net/telnet3.php'; // Arquivo Telnet local

header('Content-Type: application/json');

class OLTConnector {
    private $connectionType;
    private $handler;
    private $lastOutput;
    
    public function __construct($type = 'auto') {
        $this->connectionType = $this->determineBestConnectionType($type);
    }
    
    private function determineBestConnectionType($preferred) {
        // Ordem de preferência de conexão
        $priority = ['ssh2', 'telnet', 'phpseclib'];
        
        if ($preferred !== 'auto' && in_array($preferred, $priority)) {
            return $preferred;
        }
        
        // Verificar qual método está disponível
        foreach ($priority as $type) {
            if ($type === 'ssh2' && function_exists('ssh2_connect')) {
                return 'ssh2';
            }
            if ($type === 'telnet' && class_exists('PHPTelnet')) {
                return 'telnet';
            }
        }
        
        return 'phpseclib'; // Fallback padrão
    }
    
    public function connect($host, $port, $username, $password) {
        switch ($this->connectionType) {
            case 'ssh2':
                $this->handler = ssh2_connect($host, $port);
                if (!$this->handler || !ssh2_auth_password($this->handler, $username, $password)) {
                    throw new RuntimeException("SSH2 connection failed");
                }
                return true;
                
            case 'telnet':
                $this->handler = new PHPTelnet();
                $this->handler->show_connect_error = 0;
                $result = $this->handler->Connect($host, $port, $username, $password);
                if ($result !== 0) {
                    throw new RuntimeException("Telnet connection failed with code: $result");
                }
                return true;
                
            case 'phpseclib':
                $this->handler = new \phpseclib3\Net\SSH2($host, $port);
                $this->handler->setTimeout(15);
                if (!$this->handler->login($username, $password)) {
                    throw new RuntimeException("phpseclib authentication failed");
                }
                return true;
        }
    }
    
    public function executeCommands(array $commands, $delay = 500000) {
        $output = '';
        
        foreach ($commands as $cmd) {
            switch ($this->connectionType) {
                case 'ssh2':
                    $stream = ssh2_exec($this->handler, $cmd);
                    stream_set_blocking($stream, true);
                    $output .= stream_get_contents($stream);
                    fclose($stream);
                    break;
                    
                case 'telnet':
                    $this->handler->DoCommand($cmd, $response);
                    $output .= $response;
                    break;
                    
                case 'phpseclib':
                    $this->handler->write($cmd . "\n");
                    $output .= $this->handler->read('/[#>]\s*$/', \phpseclib3\Net\SSH2::READ_REGEX);
                    break;
            }
            
            usleep($delay);
        }
        
        $this->lastOutput = $output;
        return $output;
    }
    
    public function disconnect() {
        switch ($this->connectionType) {
            case 'ssh2':
                // A extensão SSH2 não tem método explícito de desconexão
                $this->handler = null;
                break;
                
            case 'telnet':
                $this->handler->Disconnect();
                break;
                
            case 'phpseclib':
                // phpseclib desconecta automaticamente
                break;
        }
    }
    
    public function getLastOutput() {
        return $this->lastOutput;
    }
    
    public function getConnectionType() {
        return $this->connectionType;
    }
}

// Processamento da requisição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validação dos dados de entrada
        $requiredParams = ['host', 'username', 'password', 'interface', 'sn', 'indice'];
        foreach ($requiredParams as $param) {
            if (empty($_POST[$param])) {
                throw new InvalidArgumentException("Parâmetro '$param' é obrigatório");
            }
        }
        
        $host = filter_var($_POST['host'], FILTER_VALIDATE_IP) ?: $_POST['host'];
        $port = isset($_POST['port']) ? (int)$_POST['port'] : 22;
        $username = $_POST['username'];
        $password = $_POST['password'];
        $fsp = preg_match('/^gpon(-\w+)?_\d+\/\d+\/\d+$/', $_POST['interface']) ? $_POST['interface'] : false;
        $sn = $_POST['sn'];
        $indice = (int)$_POST['indice'];
        
        if (!$fsp) {
            throw new InvalidArgumentException("Formato de interface inválido");
        }
        
        // Criar conexão
        $olt = new OLTConnector('auto'); // Tenta automaticamente ssh2 -> telnet -> phpseclib
        
        try {
            $olt->connect($host, $port, $username, $password);
            
            // Comandos para remoção de ONU
            $commands = [
                "enable",
                "configure terminal",
                "interface $fsp",
                "no onu $indice",
                "do write",
                "exit",
                "exit",
                "show running-config interface $fsp"
            ];
            
            $output = $olt->executeCommands($commands);
            
            // Verificar sucesso
            $removalSuccess = strpos($output, $sn) === false && 
                             !preg_match("/onu\s+$indice\b/i", $output);
            
            // Formatar saída
            $formattedOutput = preg_replace('/(ZXAN[#>])/', "$1\n", trim($output));
            
            echo json_encode([
                'success' => $removalSuccess,
                'connection_type' => $olt->getConnectionType(),
                'indice' => $indice,
                'output' => $formattedOutput,
                'diagnostics' => [
                    'sn_found' => strpos($output, $sn) !== false,
                    'onu_index_found' => preg_match("/onu\s+$indice\b/i", $output),
                    'commands_executed' => count($commands)
                ]
            ], JSON_PRETTY_PRINT);
            
        } finally {
            $olt->disconnect();
        }
        
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } catch (RuntimeException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro inesperado: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
}