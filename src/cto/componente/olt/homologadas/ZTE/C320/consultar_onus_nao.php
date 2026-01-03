<?php

require '../../../vendor/autoload.php'; // Ajuste o caminho conforme sua estrutura

use phpseclib3\Net\SSH2;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = filter_input(INPUT_POST, 'host', FILTER_SANITIZE_STRING);
    $port = filter_input(INPUT_POST, 'port', FILTER_VALIDATE_INT, ['options' => ['default' => 22, 'min_range' => 1, 'max_range' => 65535]]);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $sn = filter_input(INPUT_POST, 'sn', FILTER_SANITIZE_STRING);
    $idCliente = filter_input(INPUT_POST, 'idCliente', FILTER_VALIDATE_INT);

    try {
        $ssh = new SSH2($host, $port);

        if (!$ssh->login($username, $password)) {
            echo json_encode(['success' => false, 'output' => '❌ Falha na autenticação SSH.']);
            exit;
        }

        // Envia comandos em sequência
        $ssh->write("enable\n");
        $ssh->write("show pon onu uncfg\n");
        $ssh->write("exit\n");

        // Captura a saída
        $output = $ssh->read('[#>]', SSH2::READ_REGEX);

        // Filtrar linhas
        $lines = explode("\n", trim($output));
        $startIndex = -1;

        foreach ($lines as $index => $line) {
            if (strpos($line, "OltIndex") !== false) {
                $startIndex = $index;
                break;
            }
        }

        if ($startIndex === -1) {
            echo json_encode(['success' => false, 'output' => 'Nenhuma ONU encontrada na busca!']);
            exit;
        }

        $filteredLines = array_slice($lines, $startIndex + 1);
        $filteredLines = array_filter($filteredLines, function ($line) {
            return !empty(trim($line)) && strpos($line, "----") === false;
        });

        $onus = [];

        foreach ($filteredLines as $line) {
            $columns = preg_split('/\s+/', trim($line));

            if (count($columns) >= 4) {
                $onu_data = [
                    'olt_index' => trim($columns[0]),
                    'model'     => trim($columns[1]),
                    'sn'        => trim($columns[2]),
                    'pw'        => trim($columns[3])
                ];

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

        echo json_encode([
            'success' => true,
            'onus' => $onus
        ], JSON_PRETTY_PRINT);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'output' => '❌ Erro de conexão: ' . $e->getMessage()]);
    }
}
