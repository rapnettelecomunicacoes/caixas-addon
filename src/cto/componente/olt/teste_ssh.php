<?php
header('Content-Type: application/json');

// Recebe os dados via POST e sanitiza
$host = filter_input(INPUT_POST, 'host', FILTER_SANITIZE_STRING);
$port = filter_input(INPUT_POST, 'port', FILTER_VALIDATE_INT, ['options' => ['default' => 22, 'min_range' => 1, 'max_range' => 65535]]);
$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING); 

require __DIR__ . '/vendor/autoload.php';

use phpseclib3\Net\SSH2;

try {
    $ssh = new SSH2($host, $port);

    if ($ssh->login($username, $password)) {
        echo json_encode(['success' => true, 'output' => '✅ Autenticação bem-sucedida.']);
    } else {
        echo json_encode(['success' => false, 'output' => '❌ Falha na autenticação.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'output' => '❌ Erro na conexão: ' . $e->getMessage()]);
}
