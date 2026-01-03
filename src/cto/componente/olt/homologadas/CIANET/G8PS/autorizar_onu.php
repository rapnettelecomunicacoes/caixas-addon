<?php
require '../../../vendor/autoload.php';
use phpseclib3\Net\SSH2;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Filtragem de entradas
    $host = filter_input(INPUT_POST, 'host', FILTER_SANITIZE_STRING);
    $port = filter_input(INPUT_POST, 'port', FILTER_VALIDATE_INT, [
        'options' => ['default' => 22, 'min_range' => 1, 'max_range' => 65535]
    ]);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $fsp = filter_input(INPUT_POST, 'interface', FILTER_SANITIZE_STRING);
    $sn = filter_input(INPUT_POST, 'sn', FILTER_SANITIZE_STRING);
    $modelo = substr(filter_input(INPUT_POST, 'modelo', FILTER_SANITIZE_STRING), 0, 4);

    try {
        // Inicializa conexão SSH
        $ssh = new SSH2($host, $port ?: 22);
        $ssh->setTimeout(30); // Timeout aumentado para provisionamento
        
        if (!$ssh->login($username, $password)) {
            throw new Exception('Falha na autenticação SSH');
        }

        // Obtém configuração atual das ONUs
        $output = $ssh->exec("enable\nshow running-config interface {$fsp}");
        
        // Encontra último ID de ONU
        preg_match_all('/onu\s+(\d+)\s+type/', $output, $matches);
        $id_onu = !empty($matches[1]) ? end($matches[1]) + 1 : 1;

        // Comandos para provisionamento
        $comandos_provisionamento = [
            "configure terminal",
            "interface {$fsp}",
            "onu {$id_onu} type {$modelo} sn {$sn} vport-mode manual",
            "do write",
            "exit",
            "exit"
        ];

        // Executa provisionamento
        foreach ($comandos_provisionamento as $comando) {
            $ssh->write("{$comando}\n");
            usleep(300000); // Atraso de 0.3s entre comandos
        }

        // Captura saída do provisionamento
        $saida_provisionamento = $ssh->read('/[#>]\s*$/', SSH2::READ_REGEX);

        // Verifica configuração final
        $ssh->write("show running-config interface {$fsp}\n");
        $saida_final = $ssh->read('/[#>]\s*$/', SSH2::READ_REGEX);
        
        // Verifica se ONU foi adicionada com sucesso
        $sucesso = strpos($saida_final, $sn) !== false;

        // Formata saídas para exibição
        $formatarSaida = function($saida) {
            return str_replace("ZXAN#", "ZXAN#\n", trim($saida));
        };

        echo json_encode([
            'success' => $sucesso,
            'id_onu' => $id_onu,
            'output' => $formatarSaida($output),
            'output_prov' => $formatarSaida($saida_provisionamento),
            'output_final' => $formatarSaida($saida_final),
            'message' => $sucesso ? 'ONU provisionada com sucesso' : 'Atenção: O provisionamento da ONU pode ter falhado'
        ], JSON_PRETTY_PRINT);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro: ' . $e->getMessage(),
            'error' => true
        ], JSON_PRETTY_PRINT);
    }
}