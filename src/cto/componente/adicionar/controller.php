<?php
/**
 * Controller - Componente ADICIONAR
 * Processa a adição de nova CTO
 */

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$component_base = dirname(__FILE__);

// Carregar banco de dados
$db_init = $component_base . '/../../database/index.php';

if (file_exists($db_init)) {
    require_once $db_init;
}

// Processar formulário de adição de CTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($db_linker)) {
    // Obter dados do formulário
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
    $localizacao = isset($_POST['localizacao']) ? trim($_POST['localizacao']) : '';
    $latitude = isset($_POST['latitude']) ? trim($_POST['latitude']) : '';
    $longitude = isset($_POST['longitude']) ? trim($_POST['longitude']) : '';
    $portas = isset($_POST['portas']) ? intval($_POST['portas']) : 0;
    $sinal = isset($_POST['sinal']) ? trim($_POST['sinal']) : '';
    $olt = isset($_POST['olt']) ? trim($_POST['olt']) : '';
    $fsp = isset($_POST['fsp']) ? trim($_POST['fsp']) : '';

    // Validações básicas
    if (empty($nome)) {
        $_SESSION['mensagem_erro'] = 'Nome da CTO é obrigatório!';
        header('Location: ?_route=adicionar');
        exit;
    }

    if ($portas <= 0) {
        $_SESSION['mensagem_erro'] = 'Capacidade deve ser maior que zero!';
        header('Location: ?_route=adicionar');
        exit;
    }

    // Preparar dados para inserção
    $sql = "INSERT INTO mp_caixa (nome, tipo, endereco, latitude, longitude, capacidade, sinal, olt, fsp) 
            VALUES ('{$db_linker->escape($nome)}', 
                    '{$db_linker->escape($tipo)}', 
                    '{$db_linker->escape($localizacao)}', 
                    '{$db_linker->escape($latitude)}', 
                    '{$db_linker->escape($longitude)}', 
                    {$portas}, 
                    '{$db_linker->escape($sinal)}', 
                    '{$db_linker->escape($olt)}', 
                    '{$db_linker->escape($fsp)}')";

    // Executar inserção
    if ($db_linker->query($sql)) {
        $novo_id = $db_linker->insert_id();
        $_SESSION['mensagem_sucesso'] = 'CTO adicionada com sucesso!';
        
        // Redirecionar para a página de edição da nova CTO para atribuir clientes
        header('Location: ?_route=editar&id=' . $novo_id);
        exit;
    } else {
        $_SESSION['mensagem_erro'] = 'Erro ao adicionar CTO: ' . $db_linker->error();
        header('Location: ?_route=adicionar');
        exit;
    }
}

// Renderizar a view
require_once __DIR__ . '/adicionar.view.php';
