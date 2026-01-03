<?php

require 'conexao.php'; // Inclui o arquivo de conexão

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = filter_input(INPUT_POST, 'host', FILTER_SANITIZE_STRING);
    $port = filter_input(INPUT_POST, 'port', FILTER_VALIDATE_INT, ['options' => ['default' => 22, 'min_range' => 1, 'max_range' => 65535]]);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $sn = filter_input(INPUT_POST, 'sn', FILTER_SANITIZE_STRING); // Número de série (opcional)

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
    fwrite($shell, "show running-config\r\n");
    usleep(300000);

    // Capturar saída
    $output_interface = stream_get_contents($shell);
    sleep(1);

    $linhas = explode("\n", $output_interface);
    $interfaces = [];

    foreach ($linhas as $linha) {
        if (preg_match('/add-card rackno (\d+) shelfno (\d+) slotno (\d+) GTGH/', trim($linha), $matches)) {
            $rack = $matches[1];
            $shelf = $matches[2];
            $slot = $matches[3];
            $interfaces[] = "gpon-olt_{$rack}/{$shelf}/{$slot}";
        }
    }

    // Para armazenar os resultados das ONUs
    $onus = [];

    // Iterar sobre as interfaces
    foreach ($interfaces as $interface) {
        // Enviar o comando para a interface atual
        fwrite($shell, "enable\r\n");
        usleep(300000);
        fwrite($shell, "show running-config interface {$interface}\r\n");
        usleep(300000);

        // Capturar a saída do comando
        $output = stream_get_contents($shell);
        sleep(1);

        // Processar a saída para extrair os dados das ONUs
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/onu (\d+) type (\S+) sn (\S+) vport-mode manual/', $line, $matches)) {
                $onu_data = [
                    'onu_index' => $matches[1],
                    'model' => $matches[2],
                    'sn' => $matches[3],
                    'interface' => $interface // Adiciona a interface correspondente
                ];
                
                // Se um SN foi fornecido, filtrar apenas a ONU correspondente
                if (!empty($sn) && $matches[3] === $sn) {
                    echo json_encode([
                        'success' => true,
                        'onus' => [$onu_data]
                    ], JSON_PRETTY_PRINT);
                    exit;
                }
                
                $onus[] = $onu_data;
            }
        }
    }

    // Retornar os resultados das ONUs (ou todas, se SN não for informado)
    echo json_encode([
        'success' => true,
        'onus' => $onus
    ], JSON_PRETTY_PRINT);
}
