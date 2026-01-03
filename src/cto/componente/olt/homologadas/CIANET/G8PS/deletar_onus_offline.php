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

    // Verifica se os dados de conexão estão completos
    if (empty($host) || empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'output' => 'Dados de conexão incompletos.']);
        exit;
    }

    try {
        // Inicializa a conexão SSH
        $ssh = new SSH2($host, $port ?: 22);
        $ssh->setTimeout(30);

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

        // Executa os comandos para deletar ONUs offline
        $commands = [
            "enable",
            "config",
            "interface gpon 0/0"
        ];

        foreach ($commands as $cmd) {
            $ssh->write("$cmd\n");
            usleep(300000);
        }

        // Loop para deletar ONUs offline em todas as interfaces
        for ($p = 1; $p <= 8; $p++) {
            $ssh->write("ont delete {$p} offline-list all\n");
            usleep(500000);
            $output = $ssh->read('/[#>]/');
            
            if (strpos($output, "success") === false) {
                throw new Exception("Erro ao deletar ONUs na interface {$p}");
            }
        }

        // Retornar sucesso
        echo json_encode([
            'success' => true,
            'output' => 'ONUs deletadas com sucesso!'
        ], JSON_PRETTY_PRINT);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'output' => $e->getMessage()
        ], JSON_PRETTY_PRINT);
    }
}