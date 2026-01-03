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
  
    // 1. Estabelecer conexão SSH
    $ssh = new SSH2($host, $port);
    $ssh->setTimeout(15); // Timeout aumentado para operações de configuração

    if (!$ssh->login($username, $password)) {
        throw new Exception('❌ Falha na autenticação SSH');
    }

    // 2. Obter último ID de ONU na interface
    $ssh->write("enable\n");
    $ssh->write("show running-config interface {$interface}\n");
    $output = $ssh->read('/[#>]\s*$/');

    // Expressão regular para capturar o número da última ONU
    preg_match_all('/onu\s+(\d+)\s+type/', $output, $matches);
    $id_onu = !empty($matches[1]) ? end($matches[1]) + 1 : 1;

    // 3. Configurar nova ONU
    $ssh->write("config t\n");
    $ssh->write("interface {$interface}\n");
    $ssh->write("onu {$id_onu} type {$modelo} sn {$sn} vport-mode manual\n");
    $ssh->write("do write\n");
    $ssh->write("exit\n");
    $ssh->write("exit\n");

    // 4. Verificar configuração final
    $ssh->write("show running-config interface {$interface}\n");
    $output_final = $ssh->read('/[#>]\s*$/');
    $success = strpos($output_final, $sn) !== false;

    // 5. Atualizar banco de dados se necessário
    if ($idCliente && $success) {
        require '../../../../../config/database.hhvm';
        
        $conn = new mysqli($Host, $user, $pass, $db_name);
        
        if ($conn->connect_error) {
            throw new Exception('Falha na conexão com o banco de dados');
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
                'onu_id' => $id_onu,
                'interface' => $interface
            ], JSON_PRETTY_PRINT);
        } else {
            throw new Exception('ONU autorizada mas falha ao preparar statement SQL');
        }
        
        $conn->close();
    } else {
        echo json_encode([
            'success' => $success,
            'message' => $success ? '✅ ONU autorizada com sucesso!' : '❌ Erro: ONU não encontrada na configuração final',
            'onu_id' => $id_onu,
            'interface' => $interface
        ], JSON_PRETTY_PRINT);
    }
}