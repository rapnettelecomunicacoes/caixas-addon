<?php

require 'conexao.php'; // Inclui o arquivo de conexão

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = filter_input(INPUT_POST, 'host', FILTER_SANITIZE_STRING);
    $port = filter_input(INPUT_POST, 'port', FILTER_VALIDATE_INT, ['options' => ['default' => 22, 'min_range' => 1, 'max_range' => 65535]]);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $fsp = filter_input(INPUT_POST, 'interface', FILTER_SANITIZE_STRING);
    $sn = filter_input(INPUT_POST, 'sn', FILTER_SANITIZE_STRING);
    $modelo = substr(filter_input(INPUT_POST, 'modelo', FILTER_SANITIZE_STRING), 0, 4);
    
    // Conectar ao servidor via SSH
    $connection = ssh2_connect($host, $port);
    if (!$connection) {
        echo json_encode(['success' => false, 'output' => 'Falha ao conectar à OLT.']);
        exit;
    }
    
    // Autenticar usuário e senha
    if (!ssh2_auth_password($connection, $username, $password)) {
        echo json_encode(['success' => false, 'output' => 'Falha na autenticação SSH.']);
        exit;
    }
    
    // Criar shell SSH
    $shell = ssh2_shell($connection, 'xterm');
    if (!$shell) {
        echo json_encode(['success' => false, 'output' => 'Falha ao iniciar shell SSH.']);
        exit;
    }
    
    stream_set_blocking($shell, true);
    stream_set_timeout($shell, 5);
    
    // Enviar comando
    fwrite($shell, "enable\r\n");
    usleep(300000);
    fwrite($shell, "show running-config interface {$fsp}\r\n");
    usleep(300000);
    
    // Capturar saída
    $output = stream_get_contents($shell);
    sleep(1);

    // Expressão regular para capturar o número da última ONU
    preg_match_all('/onu\s+(\d+)\s+type/', $output, $matches);
    
    // Pegando o último ID de ONU encontrado
    $id_onu = !empty($matches[1]) ? end($matches[1]) + 1 : 'Não encontrado';

    // Adicionar ONU**
    fwrite($shell, "config t\r\n");
    usleep(300000);
    fwrite($shell, "interface {$fsp}\r\n");
    usleep(300000);
    fwrite($shell, "onu {$id_onu} type {$modelo} sn {$sn} vport-mode manual\r\n");
    usleep(300000);
    fwrite($shell, "do wr\r\n");
    usleep(300000);
    fwrite($shell, "exit\r\n");
    usleep(300000);
    fwrite($shell, "exit\r\n");
    usleep(300000);
    
    // Capturar saída
    $output_prov = stream_get_contents($shell);
    sleep(1);

    // Listar as onu Autorizadas**
    fwrite($shell, "show running-config interface {$fsp}\r\n");
    usleep(500000);

    // Capturar saída
    $output_final = stream_get_contents($shell);
    sleep(1);

    // Verificar se o SN está na saída
    $success = strpos($output, $sn) !== false;

    echo json_encode([
        'success' => true,
        'output' => str_replace("ZXAN#", "ZXAN#\n", trim($output)),
        'id_onu' => $id_onu,
        'output_prov' => str_replace("ZXAN#", "ZXAN#\n", trim($output_prov)),
        'output_final' => str_replace("ZXAN#", "ZXAN#\n", trim($output_final))
    ], JSON_PRETTY_PRINT);
}
?>
