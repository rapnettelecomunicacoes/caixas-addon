<?php
require '../../../vendor/autoload.php'; // phpseclib

use phpseclib3\Net\SSH2;

// Função para ler a saída completa, tratando o '--More--'
function readUntilDone($ssh) {
    $output = '';
    while (true) {
        $chunk = $ssh->read();
        $output .= $chunk;

        if (strpos($chunk, '--More--') !== false) {
            $ssh->write(" ");
            usleep(100000); // pequena pausa para evitar travamentos
        } else {
            break;
        }
    }
    return $output;
}

// Configurações de acesso
$host = '187.61.97.75';
$port = 2222;
$username = 'manaos';
$password = 'Mana0s@fib3r##2025';

// Conectar via SSH
$ssh = new SSH2($host, $port);
if (!$ssh->login($username, $password)) {
    exit("❌ Falha na autenticação" . PHP_EOL);
}

echo "✅ Conectado à OLT ZTE C620" . PHP_EOL . PHP_EOL;

$totalPorts = 16;
$ssh->write("config t\n");
$onus = [];

$ssh->setTimeout(1); // Timeout reduzido

for ($i = 1; $i <= $totalPorts; $i++) {
    $interface_full = "gpon_olt-1/1/{$i}";
    $interface_onu = "gpon_onu-1/1/{$i}";
    $ssh->write("show pon onu information $interface_full\n");
    $output = readUntilDone($ssh); // agora lê até o fim, mesmo com --More--

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

            $onus[] = [
                'interface' => $interface,
                'onu_index' => $onu_index,
                'sn' => $sn ?? 'SN_DESCONHECIDO',
                'model' => 'F670L',
                'tx' => '-50.00',
                'rx' => '-50.00'
            ];
        }
    }
}

// Exibir cabeçalho formatado com quebra de linha e alinhamento
echo "📋 ONUs encontradas:" . PHP_EOL;
printf("%-12s %-10s %-18s %-10s %-8s %-8s" . PHP_EOL, "Interface", "ONU Index", "SN", "Model", "TX", "RX");
echo str_repeat("-", 70) . PHP_EOL;

foreach ($onus as $onu) {
    printf(
        "%-12s %-10s %-18s %-10s %-8s %-8s" . PHP_EOL,
        $onu['interface'],
        $onu['onu_index'],
        $onu['sn'],
        $onu['model'],
        $onu['tx'],
        $onu['rx']
    );
}
