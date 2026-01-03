<?php
require '../../../vendor/autoload.php';
use phpseclib3\Net\SSH2;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação dos dados de entrada
    $host = filter_input(INPUT_POST, 'host', FILTER_SANITIZE_STRING);
    $port = filter_input(INPUT_POST, 'port', FILTER_VALIDATE_INT, [
        'options' => ['default' => 22, 'min_range' => 1, 'max_range' => 65535]
    ]);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $fsp = filter_input(INPUT_POST, 'interface', FILTER_SANITIZE_STRING);
    $p = substr($fsp, 4, 1);
    $sn = filter_input(INPUT_POST, 'sn', FILTER_SANITIZE_STRING);
    $indice = filter_input(INPUT_POST, 'indice', FILTER_VALIDATE_INT);

    // Verifica se os dados de conexão estão completos
    if (empty($host) || empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'output' => 'Dados de conexão incompletos.']);
        exit;
    }

    try {
        // Inicializa a conexão SSH
        $ssh = new SSH2($host, $port ?: 22);
        $ssh->setTimeout(15);

        // Autenticação interativa se necessário
        $output = $ssh->read('/User name:/');
        if (strpos($output, "User name:") !== false) {
            $ssh->write("$username\n");
            $output = $ssh->read('/User password:/');
            
            if (strpos($output, "User password:") !== false) {
                $ssh->write("$password\n");
                $output = $ssh->read('/[#>]/');
            }
        } else {
            // Autenticação direta
            if (!$ssh->login($username, $password)) {
                throw new Exception('Falha na autenticação SSH');
            }
        }

        // Verifica se a autenticação falhou
        if (strpos($output, "invalid") !== false || strpos($output, "fail") !== false) {
            throw new Exception('Usuário ou senha inválidos!');
        }

        // Executa os comandos para deletar a ONU
        $commands = [
            "enable",
            "config",
            "interface gpon 0/0",
            "ont delete {$p} {$indice}",
            "exit"
        ];

        foreach ($commands as $cmd) {
            $ssh->write("$cmd\n");
            usleep(300000);
        }

        // Captura a saída final
        $output = $ssh->read('/[#>]/');

        // Verifica se o comando foi bem-sucedido
        $lines = explode("\n", trim($output));
        $lastLine = end($lines);

        if (strpos($lastLine, "OLT(config-interface-gpon-0/0)#") !== false || 
            strpos($output, "autosave configuration done!") !== false) {
            echo json_encode([
                'success' => true,
                'output' => 'ONU deletada com sucesso!'
            ]);
        } else {
            throw new Exception('Falha ao deletar ONU. Saída: ' . $output);
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'output' => $e->getMessage()
        ]);
    }
}