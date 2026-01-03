<?php
require '../../../vendor/autoload.php';
use phpseclib3\Net\SSH2;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = filter_input(INPUT_POST, 'host', FILTER_SANITIZE_STRING);
    $port = filter_input(INPUT_POST, 'port', FILTER_VALIDATE_INT, [
        'options' => ['default' => 22, 'min_range' => 1, 'max_range' => 65535]
    ]);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $interface = filter_input(INPUT_POST, 'interface', FILTER_SANITIZE_STRING);
    $sn = filter_input(INPUT_POST, 'sn', FILTER_SANITIZE_STRING);
    $modelo = substr(filter_input(INPUT_POST, 'modelo', FILTER_SANITIZE_STRING), 0, 4);
    $idCliente = filter_input(INPUT_POST, 'idCliente', FILTER_VALIDATE_INT);

    $ssh = new SSH2($host, $port);
    $ssh->setTimeout(15);

    if (!$ssh->login($username, $password)) {
        echo json_encode(['success' => false, 'message' => '❌ Falha na autenticação SSH']);
        exit;
    }

    // Parte 1: Obter índice da próxima ONU
    $ssh->write("enable\n");
    $ssh->write("show running-config\n");
    $output = $ssh->read('/[#>]\s*$/');

    // Converter interface gpon-olt_1/1/1 para gpon_onu-1/1/1
    $interface_onu = explode("-", $interface)[1];
    list($f, $s, $p) = explode("/", $interface_onu);
    $interface_onu_formatado = "gpon_onu-{$f}/{$s}/{$p}";
    $interface_vport = "vport-{$f}/{$s}/{$p}";

    preg_match_all('/' . preg_quote($interface_onu_formatado, '/') . ':(\d+)/', $output, $matches);
    $indices = array_map('intval', $matches[1]);
    $proximo_index = !empty($indices) ? max($indices) + 1 : 1;

    // Parte 2: Autorizar ONU
    $ssh->write("config t\n");
    $ssh->write("interface {$interface}\n");
    $ssh->write("onu {$proximo_index} type {$modelo} sn {$sn}\n");
    $ssh->write("do write\n");
    $ssh->write("exit\n");

    // Parte 3: Criar VPort
    $ssh->write("interface {$interface_onu_formatado}:{$proximo_index}\n");
    $ssh->write("tcont 1 profile 1G\n");
    $ssh->write("gemport 1 tcont 1\n");
    $ssh->write("exit\n");

    $ssh->write("interface {$interface_vport}.{$proximo_index}:1\n");
    $ssh->write("service-port 1 user-vlan 2025 vlan 2025\n");
    $ssh->write("exit\n");

    $ssh->write("pon-onu-mng {$interface_onu_formatado}:{$proximo_index}\n");
    $ssh->write("service 1 gemport 1 vlan 2025\n");
    $ssh->write("do write\n");

    // Verificar se a ONU foi autorizada
    $ssh->write("show running-config interface {$interface}\n");
    $output_final = $ssh->read('/[#>]\s*$/');
    $success = strpos($output_final, $sn) !== false;

    // Atualizar banco de dados
    if ($idCliente && $success) {
        require '../../../../../config/database.hhvm';
        $conn = new mysqli($Host, $user, $pass, $db_name);

        if ($conn->connect_error) {
            echo json_encode(['success' => false, 'message' => 'Erro na conexão com banco de dados']);
            exit;
        }

        $sql = "UPDATE sis_cliente SET porta_olt = ?, onu_ont = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssi", $interface, $sn, $idCliente);
            $stmt->execute();
            $stmt->close();
            echo json_encode([
                'success' => true,
                'message' => '✅ ONU autorizada e vinculada com sucesso!',
                'onu_id' => $proximo_index,
                'interface' => $interface
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(['success' => true, 'message' => 'ONU autorizada, mas falha ao atualizar cliente.']);
        }

        $conn->close();
    } else {
        echo json_encode([
            'success' => $success,
            'message' => $success ? '✅ ONU autorizada com sucesso!' : '❌ ONU não encontrada após autorização.',
            'onu_id' => $proximo_index,
            'interface' => $interface
        ], JSON_PRETTY_PRINT);
    }
}
