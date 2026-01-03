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
    $target_sn = filter_input(INPUT_POST, 'sn', FILTER_SANITIZE_STRING);

    $ssh = new SSH2($host, $port);
    if (!$ssh->login($username, $password)) {
        echo json_encode(['success' => false, 'output' => 'Falha na autenticação SSH.']);
        exit;
    }

    $totalPorts = 16;
    $ssh->write("enable\n");
    $ssh->setTimeout(1);
    $onus = [];

    for ($i = 1; $i <= $totalPorts; $i++) {
        $interface_full = "gpon-olt_1/1/{$i}";
        $interface_onu = "gpon-onu_1/1/{$i}";

        $ssh->write("show running-config interface $interface_full\n");
        $output = readUntilDone($ssh);

        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^onu\s+(\d+)\s+type\s+(\S+)\s+sn\s+(\S+)/', $line, $matches)) {
                $onu_index = $matches[1];
                $model = $matches[2];
                $sn = $matches[3];

                $onu_data = [
                    'interface' => "1/1/{$i}",
                    'onu_index' => $onu_index,
                    'sn' => $sn,
                    'model' => $model,
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

                if (!empty($target_sn) && $sn === $target_sn) {
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
