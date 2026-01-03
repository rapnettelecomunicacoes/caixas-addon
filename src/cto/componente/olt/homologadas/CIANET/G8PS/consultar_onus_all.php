<?php
require '../../../vendor/autoload.php';
use phpseclib3\Net\SSH2;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Filtragem das entradas
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
        $ssh->setTimeout(30); // Timeout de 30 segundos

        // Primeiro tenta login direto
        if (!$ssh->login($username, $password)) {
            // Se falhar, tenta autenticação interativa
            $output = $ssh->read('/User name:|login:|username:/i');
            if (preg_match('/User name:|login:|username:/i', $output)) {
                $ssh->write("$username\n");
                $output = $ssh->read('/User password:|password:/i');
                if (preg_match('/User password:|password:/i', $output)) {
                    $ssh->write("$password\n");
                    $output = $ssh->read('/[#>$]/');
                }
            }
            
            // Verifica novamente se está autenticado
            if (strpos($output, "invalid") !== false || strpos($output, "fail") !== false) {
                throw new Exception('Usuário ou senha inválidos!');
            }
        }

        // Obtém informações das ONUs
        $ssh->write("enable\n");
        $ssh->read('/[#>$]/');
        $ssh->write("config\n");
        $ssh->read('/[#>$]/');
        $ssh->write("show ont info all\n");
        $output = $ssh->read('/[#>$]/');

        // Processa as ONUs
        $linhas = explode("\n", $output);
        $onus = [];

        foreach ($linhas as $linha) {
            if (preg_match('/^\s*(\d+\/\d+)\s+(\d+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)/', trim($linha), $matches)) {
                $onus[] = [
                    'interface' => $matches[1] . '/' . $matches[2],
                    'onu_index' => $matches[3],
                    'sn' => $matches[4],
                    'model' => substr($matches[4], 0, 4),
                    'tx' => "-50.00",
                    'rx' => "-50.00"
                ];
            }
        }

        // Obtém informações ópticas
        $ssh->write("interface gpon 0/0\n");
        $ssh->read('/[#>$]/');
        $onu_signals = [];
        
        for ($p = 1; $p <= 8; $p++) {
            $ssh->write("show ont optical-info {$p} all\n");
            sleep(2); // Reduzido de 6 para 2 segundos
            $output = $ssh->read('/[#>$]/');
            
            $linhas_sinal = explode("\n", $output);
            foreach ($linhas_sinal as $linha) {
                if (preg_match('/^\s*(\d+\/\d+)\s+(\d+)\s+(\d+)\s+([\d.-]+)\s+([\d.-]+)\s+([\d.-]+)\s+([\d.-]+)\s+([\d.-]+)/', trim($linha), $matches)) {
                    $onu_signals[] = [
                        'interface' => $matches[1] . '/' . $matches[2],
                        'onu_index' => $matches[3],
                        'tx' => $matches[5],
                        'rx' => $matches[6]
                    ];
                }
            }
        }

        // Atualiza os valores de TX/RX
        foreach ($onus as &$onu) {
            foreach ($onu_signals as $signal) {
                if ($onu['interface'] . $onu['onu_index'] === $signal['interface'] . $signal['onu_index']) {
                    $onu['tx'] = $signal['tx'];
                    $onu['rx'] = $signal['rx'];
                    break;
                }
            }
        }

        // Filtra por SN se fornecido
        if (!empty($sn)) {
            $onus_filtradas = array_filter($onus, function ($onu) use ($sn) {
                return $onu['sn'] === $sn;
            });

            if (empty($onus_filtradas)) {
                throw new Exception('Nenhuma ONU encontrada com o SN informado.');
            }

            echo json_encode([
                'success' => true,
                'onus' => array_values($onus_filtradas)
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode([
                'success' => true,
                'onus' => $onus
            ], JSON_PRETTY_PRINT);
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro: ' . $e->getMessage()
        ], JSON_PRETTY_PRINT);
    }
}