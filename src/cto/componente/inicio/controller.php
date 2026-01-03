<?php
/**
 * Controller - Componente INICIO
 * Busca dados e passa para a view
 */

$component_base = dirname(__FILE__);
$cto_list = array();

try {
    // Carregar banco de dados e modelos
    $db_init = $component_base . '/../../database/index.php';
    
    if (file_exists($db_init)) {
        require_once $db_init;
        
        // Verificar se conseguiu conexão
        if (isset($db_linker) && $db_linker->isConnected()) {
            
            // PROCESSAR EXCLUSÃO DE CTO
            if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
                $cto_id = intval($_GET['id']);
                
                try {
                    // Remover clientes atribuídos a esta CTO
                    $delete_clientes_sql = "UPDATE sis_cliente SET cto_id = NULL, caixa_herm = NULL WHERE cto_id = " . $cto_id;
                    $db_linker->query($delete_clientes_sql);
                    
                    // Deletar a CTO
                    $delete_cto_sql = "DELETE FROM mp_caixa WHERE id = " . $cto_id;
                    
                    if ($db_linker->query($delete_cto_sql)) {
                        // Redirecionar com sucesso
                        header('Location: ?_route=inicio');
                        exit;
                    } else {
                        error_log('Erro ao deletar CTO: ' . $db_linker->error());
                        header('Location: ?_route=inicio');
                        exit;
                    }
                } catch (Exception $e) {
                    error_log('Erro ao processar exclusão: ' . $e->getMessage());
                    header('Location: ?_route=inicio');
                    exit;
                }
            }
            // Buscar dados diretamente
            $sql = "SELECT * FROM mp_caixa ORDER BY id DESC";
            $result = $db_linker->query($sql);
            
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $cto_id = (int)$row['id'];
                    $cto_nome = $row['nome'];
                    
                    // Contar clientes atribuídos a esta CTO (por cto_id OU caixa_herm)
                    $count_sql = "SELECT COUNT(*) as total FROM sis_cliente 
                                 WHERE cto_id = " . $cto_id . " OR caixa_herm = '" . $db_linker->escape($cto_nome) . "'";
                    $count_result = $db_linker->query($count_sql);
                    $count_row = $count_result->fetch_assoc();
                    $total_clientes = $count_row['total'];
                    
                    // Contar clientes online atribuídos a esta CTO
                    $online_sql = "SELECT COUNT(*) as total FROM sis_cliente sc 
                                  LEFT JOIN radacct ra ON ra.username = sc.login AND ra.acctstoptime IS NULL
                                  WHERE (sc.cto_id = " . $cto_id . " OR sc.caixa_herm = '" . $db_linker->escape($cto_nome) . "')
                                  AND ra.radacctid IS NOT NULL";
                    $online_result = $db_linker->query($online_sql);
                    $online_row = $online_result->fetch_assoc();
                    $total_online = $online_row['total'];
                    
                    // Mapear os dados para o formato esperado pela view
                    $cto_list[] = array(
                        'id' => $row['id'],
                        'nome' => $row['nome'],
                        'localizacao' => $row['endereco'], // Mapear endereco para localizacao
                        'portas' => $row['capacidade'], // Mapear capacidade para portas
                        'portas_livres' => $row['capacidade'], // Usar capacidade como portas livres
                        'status' => 'Ativo', // Status padrão
                        'latitude' => $row['latitude'],
                        'longitude' => $row['longitude'],
                        'tipo' => $row['tipo'],
                        'sinal' => $row['sinal'],
                        'olt' => $row['olt'],
                        'fsp' => $row['fsp'],
                        'total_clientes' => $total_clientes,
                        'clientes_online' => $total_online,
                        'clientes_offline' => $total_clientes - $total_online
                    );
                }
            }
        } else {
            error_log('Database connection failed');
        }
    }
} catch (Exception $e) {
    error_log('Erro ao carregar CTOs: ' . $e->getMessage());
    $cto_list = array();
}

// Contar CTOs
$total_ctos = count($cto_list);
?>
