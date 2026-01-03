<?php

require '../../../vendor/autoload.php';
use phpseclib3\Net\SSH2;

function readUntilDone($ssh) {
    $output = '';
    while (true) {
        $chunk = $ssh->read();
        $output .= $chunk;

        if (strpos($chunk, '--More--') !== false) {
            $ssh->write(" ");
            usleep(100000); // pequena pausa
        } else {
            break;
        }
    }
    return $output;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = filter_input(INPUT_POST, 'host', FILTER_SANITIZE_STRING);
    $port = filter_input(INPUT_POST, 'port', FILTER_VALIDATE_INT, ['options' => ['default' => 22, 'min_range' => 1, 'max_range' => 65535]]);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $sn_extracted = filter_input(INPUT_POST, 'sn', FILTER_SANITIZE_STRING);

    $ssh = new SSH2($host, $port);
    if (!$ssh->login($username, $password)) {
        echo json_encode(['success' => false, 'output' => 'Falha na autenticação SSH.']);
        exit;
    }

    $totalPorts = 16;
    $ssh->write("config t\n");
    $ssh->setTimeout(1); // Timeout reduzido
    $onus = [];

    for ($i = 1; $i <= $totalPorts; $i++) {
        $interface_full = "gpon_olt-1/1/{$i}";
        $interface_onu = "gpon_onu-1/1/{$i}";

        $ssh->write("show pon onu information $interface_full\n");
        $output = readUntilDone($ssh);

        $lines = explode("\n", $output);
        for ($j = 0; $j < count($lines); $j++) {
            $line = trim($lines[$j]);
            if (preg_match('/^(1\/1\/\d+):(\d+)/', $line, $matches)) {
                $interface = $matches[1];
                $onu_index = $matches[2];

                // Captura SN da linha atual ou próxima
                $sn = null;
                if (preg_match('/SN\(([A-Z0-9]+)\)/', $line, $snMatch)) {
                    $sn = $snMatch[1];
                } elseif (isset($lines[$j + 1]) && preg_match('/SN\(([A-Z0-9]+)\)/', $lines[$j + 1], $snMatchNext)) {
                    $sn = $snMatchNext[1];
                }

                $onu_data = [
                    'interface' => $interface,
                    'onu_index' => $onu_index,
                    'sn' => $sn ?? 'SN_DESCONHECIDO',
                    'model' => 'F670L',
                    'tx' => '-50.00',
                    'rx' => '-50.00'
                ];

                $ssh->write("show pon power attenuation {$interface_onu}:{$onu_index}\n");
                $signal_output = $ssh->read();

                $pattern = '/up\s+Rx\s*:\s*(-?[\d.]+)\(dbm\)\s+Tx\s*:\s*(-?[\d.]+)\(dbm\).*?down\s+Tx\s*:\s*(-?[\d.]+)\(dbm\)\s+Rx\s*:\s*(-?[\d.]+)\(dbm\)/s';
                if (preg_match($pattern, $signal_output, $signal_matches)) {
                    $onu_data['tx'] = $signal_matches[2]; // upstream Tx
                    $onu_data['rx'] = $signal_matches[4]; // downstream Rx
                }

                // Se for uma busca por SN específico e bate, retorna somente ele
                if (!empty($target_sn) && $extracted_sn === $target_sn) {
                    echo json_encode(['success' => true, 'onus' => [$onu_data]], JSON_PRETTY_PRINT);
                    exit;
                }
                $onus[] = $onu_data;
            }
        }
    }

    if (empty($onus)) {
        echo json_encode(['success' => false, 'output' => 'Nenhuma ONU encontrada nas interfaces.'], JSON_PRETTY_PRINT);
    } else {
        echo json_encode(['success' => true, 'onus' => $onus], JSON_PRETTY_PRINT);
    }
}
