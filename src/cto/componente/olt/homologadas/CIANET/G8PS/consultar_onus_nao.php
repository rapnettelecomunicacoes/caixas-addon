<?php
require '../../../vendor/autoload.php';
use phpseclib3\Net\SSH2;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Filtragem dos dados de entrada
    $host = filter_input(INPUT_POST, 'host', FILTER_SANITIZE_STRING);
    $port = filter_input(INPUT_POST, 'port', FILTER_VALIDATE_INT, [
        'options' => ['default' => 22, 'min_range' => 1, 'max_range' => 65535]
    ]);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $sn = filter_input(INPUT_POST, 'sn', FILTER_SANITIZE_STRING);

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

        // Executa os comandos para buscar ONUs
        $commands = [
            "enable",
            "config",
            "interface gpon 0/0",
            "show ont autofind all"
        ];

        foreach ($commands as $cmd) {
            $ssh->write("$cmd\n");
            usleep(300000);
        }

        // Captura a saída
        $output = $ssh->read('/[#>]/');

        // Processa as linhas de saída
        $lines = explode("\n", trim($output));
        $onus = [];
        $startIndex = -1;

        // Encontra o início dos dados relevantes
        foreach ($lines as $index => $line) {
            if (strpos($line, "gpon-olt") !== false) {
                $startIndex = $index;
                break;
            }
        }

        if ($startIndex === -1) {
            throw new Exception('Nenhuma ONU encontrada na busca!');
        }

        // Processa as ONUs encontradas
        $filteredLines = array_slice($lines, $startIndex + 1);
        foreach ($filteredLines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, "----") !== false) {
                continue;
            }

            $columns = preg_split('/\s+/', $line);
            if (count($columns) >= 4) {
                $onu = [
                    'frame' => $columns[0],
                    'slot' => $columns[1],
                    'pon' => $columns[2],
                    'model' => 'unKnown',
                    'sn' => $columns[3]
                ];

                // Verifica se é a ONU específica procurada
                if (!empty($sn) && strcasecmp($onu['sn'], $sn) === 0) {
                    echo json_encode([
                        'success' => true,
                        'onus' => [$onu]
                    ], JSON_PRETTY_PRINT);
                    exit;
                }

                $onus[] = $onu;
            }
        }

        // Retorna os resultados
        echo json_encode([
            'success' => true,
            'onus' => $onus,
            'count' => count($onus)
        ], JSON_PRETTY_PRINT);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'output' => $e->getMessage()
        ], JSON_PRETTY_PRINT);
    }
}