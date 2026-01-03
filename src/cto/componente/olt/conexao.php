<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

function conectarOLT($host, $port, $username, $password) {
    if (!function_exists('ssh2_connect')) {
        return ['success' => false, 'output' => 'A extensão ssh2 não está instalada no PHP.'];
    }

    $connection = ssh2_connect($host, $port);
    if (!$connection) {
        return ['success' => false, 'output' => 'Falha ao conectar à OLT. Verifique o IP e a porta.'];
    }

    if (!ssh2_auth_password($connection, $username, $password)) {
        return ['success' => false, 'output' => 'Falha na autenticação. Verifique o usuário e a senha.'];
    }

    return ['success' => true, 'connection' => $connection];
}
?>
