<?php

require 'conexao.php'; // Inclui o arquivo de conexão

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = filter_input(INPUT_POST, 'host', FILTER_SANITIZE_STRING);
    $port = filter_input(INPUT_POST, 'port', FILTER_VALIDATE_INT, ['options' => ['default' => 22, 'min_range' => 1, 'max_range' => 65535]]);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $command = filter_input(INPUT_POST, 'command', FILTER_SANITIZE_STRING);
    $sn = filter_input(INPUT_POST, 'sn', FILTER_SANITIZE_STRING); // Número de série (opcional)
    //$sn = "ZTEGD52A9828";

    // Conectar ao servidor via SSH
    $connection = ssh2_connect($host, $port);
    if (!$connection) {
        echo json_encode(['success' => false, 'output' => 'Falha ao conectar à OLT.']);
        exit;
    }

    // Autenticação com usuário e senha
    if (!ssh2_auth_password($connection, $username, $password)) {
        echo json_encode(['success' => false, 'output' => 'Falha na autenticação SSH.']);
        exit;
    }

    // Criar um shell interativo para enviar comandos sequenciais
    $shell = ssh2_shell($connection, 'xterm');

    if (!$shell) {
        echo json_encode(['success' => false, 'output' => 'Falha ao iniciar shell SSH.']);
        exit;
    }

    stream_set_blocking($shell, true); // Definir shell como bloqueante
    usleep(500000); // Pequeno delay para evitar truncamento

    // Enviar comandos para obter a lista de ONUs não configuradas
    fwrite($shell, "enable\r\n");
    usleep(300000);
    fwrite($shell, "show pon onu uncfg\r\n");
    usleep(500000);
    fwrite($shell, "exit\r\n");

    // Capturar saída do shell
    $output = stream_get_contents($shell);
    fclose($shell); // Fechar shell

    // Filtrar a parte relevante da saída após a execução do comando
    // Limpar a parte do login e o prompt de comando
    $lines = explode("\n", trim($output));

    // Encontrar a linha onde começa a saída relevante
    $startIndex = -1;
    foreach ($lines as $index => $line) {
        if (strpos($line, "OltIndex") !== false) {
            $startIndex = $index;
            break;
        }
    }

    // Se não encontrar "OltIndex", não há saída relevante
    if ($startIndex === -1) {
        echo json_encode(['success' => false, 'output' => 'Nenhuma ONU encontrada na busca!']);
        exit;
    }

    // Filtrar a partir da linha do comando "OltIndex"
    $filteredLines = array_slice($lines, $startIndex + 1); // Ignora a linha "OltIndex"
    $filteredLines = array_filter($filteredLines, function ($line) {
        return !empty(trim($line)) && strpos($line, "----") === false; // Ignora separadores
    });

    // Reiniciar o array de ONUs a cada execução
    $onus = [];

    // Processar a saída relevante para extrair os dados das ONUs
    // Processar cada linha e extrair os dados das ONUs
    foreach ($filteredLines as $line) {
        $columns = preg_split('/\s+/', trim($line));

        if (count($columns) >= 4) {
            $onu_data = [
                'olt_index' => trim($columns[0]), // OltIndex
                'model'     => trim($columns[1]), // Model
                'sn'        => trim($columns[2]), // SN
                'pw'        => trim($columns[3])  // PW
            ];

            // Se um SN foi fornecido, retornar apenas a ONU correspondente
            if (!empty($sn) && strtoupper($onu_data['sn']) === strtoupper(trim($sn))) {
                echo json_encode([
                    'success' => true,
                    'onus' => [$onu_data]
                ], JSON_PRETTY_PRINT);
                exit;
            }

            $onus[] = $onu_data;
        }
    }

    // Retornar a lista completa caso nenhum SN específico tenha sido fornecido
    echo json_encode([
        'success' => true,
        'onus' => $onus
    ], JSON_PRETTY_PRINT);
}
