<?php
header('Content-Type: application/json; charset=utf-8');

$configFile = '/var/tmp/server_config.json';
$resposta = ['sucesso' => false, 'mensagem' => 'Erro desconhecido'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_url'])) {
    $url = trim($_POST['admin_url']);
    
    // Validar URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $resposta = ['sucesso' => false, 'mensagem' => 'URL inválida'];
        echo json_encode($resposta);
        exit;
    }
    
    // Garantir que termine com /admin/
    $url = rtrim($url, '/');
    if (!str_ends_with($url, '/admin')) {
        $url = $url . '/admin';
    }
    $url = $url . '/';
    
    // Salvar configuração
    $dados = [
        'admin_url' => $url,
        'created_at' => date('c'),
        'server_name' => gethostname(),
        'updated_by' => 'manual'
    ];
    
    $json = json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($configFile, $json)) {
        chmod($configFile, 0644);
        @chown($configFile, 'www-data');
        @chgrp($configFile, 'www-data');
        
        $resposta = [
            'sucesso' => true,
            'mensagem' => 'URL salva com sucesso',
            'url' => $url
        ];
    } else {
        $resposta = ['sucesso' => false, 'mensagem' => 'Erro ao salvar arquivo'];
    }
} else {
    $resposta = ['sucesso' => false, 'mensagem' => 'Requisição inválida'];
}

echo json_encode($resposta);
?>
