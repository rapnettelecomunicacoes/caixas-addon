<?php
require '../../../vendor/autoload.php';
use phpseclib3\Net\SSH2;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'];
    $port = $_POST['port'] ?? 22;
    $username = $_POST['username'];
    $password = $_POST['password'];
    $fsp = $_POST['interface'];
    $sn = $_POST['sn'];
    $indice = (int) $_POST['indice'];

    $ssh = new SSH2($host, $port);
    if (!$ssh->login($username, $password)) {
        echo json_encode(['success' => false, 'output' => 'Falha na autenticação SSH.']);
        exit;
    }

    $ssh->setTimeout(1); 
    $ssh->write("enable\n");
    usleep(200000);
    $ssh->write("config t\n");
    usleep(200000);
    $ssh->write("interface gpon-olt_{$fsp}\n");
    usleep(200000);
    $ssh->write("no onu {$indice}\n");
    usleep(200000);
    
    $ssh->write("show running-config interface gpon-olt_{$fsp}\n");

    // Ler a saída inteira até o prompt
    $output = '';
    while (true) {
        $output .= $ssh->read();
        if (strpos($output, 'end') !== false || strpos($output, 'ZXAN#') !== false) {
            break;
        }
        usleep(100000);
    }

    $success = strpos($output, $sn) === false;

    echo json_encode([
        'success' => $success,
        'indice' => $indice,
        'output' => str_replace("ZXAN#", "ZXAN#\n", trim($output))
    ], JSON_PRETTY_PRINT);
}