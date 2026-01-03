<?php
/**
 * Controller - Componente EDITAR
 * Busca dados da CTO e passa para a view
 */

$component_base = dirname(__FILE__);
$cto_selecionada = null;
$cto_clientes = array();
$todos_clientes = array();
$cto_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$success_message = '';
$error_message = '';

try {
    // Carregar banco de dados
    $db_init = $component_base . '/../../database/index.php';
    
    if (file_exists($db_init) && $cto_id) {
        require_once $db_init;
        
        // Verificar se conseguiu conexão
        if (isset($db_linker) && $db_linker->isConnected()) {
            
            // PROCESSAR ATRIBUIÇÃO/REMOÇÃO DE UM CLIENTE (POST)
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($cto_id)) {
                try {
                    // Salvar alterações da CTO
                    if (isset($_POST['salvar_cto'])) {
                        $nome = $db_linker->escape($_POST['nome'] ?? '');
                        $tipo = $db_linker->escape($_POST['tipo'] ?? '');
                        $localizacao = $db_linker->escape($_POST['localizacao'] ?? '');
                        $latitude = $db_linker->escape($_POST['latitude'] ?? '');
                        $longitude = $db_linker->escape($_POST['longitude'] ?? '');
                        $portas = intval($_POST['portas'] ?? 0);
                        $sinal = $db_linker->escape($_POST['sinal'] ?? '');
                        $olt = $db_linker->escape($_POST['olt'] ?? '');
                        $fsp = $db_linker->escape($_POST['fsp'] ?? '');
                        
                        $update_sql = "UPDATE mp_caixa SET 
                            nome = '$nome',
                            tipo = '$tipo',
                            endereco = '$localizacao',
                            latitude = '$latitude',
                            longitude = '$longitude',
                            capacidade = $portas,
                            sinal = '$sinal',
                            olt = '$olt',
                            fsp = '$fsp'
                            WHERE id = $cto_id";
                        
                        if ($db_linker->query($update_sql)) {
                            $success_message = '✅ CTO atualizada com sucesso!';
                            header("Location: ?_route=editar&id=$cto_id");
                            exit();
                        } else {
                            $error_message = 'Erro ao atualizar CTO: ' . $db_linker->error;
                        }
                    }
                    
                    // Atribuir um cliente
                    if (isset($_POST['cliente_id_atribuir'])) {
                        $cliente_id = intval($_POST['cliente_id_atribuir']);
                        $db_linker->query("UPDATE sis_cliente SET cto_id = $cto_id WHERE id = $cliente_id");
                        $success_message = 'Cliente atribuído com sucesso!';
                        header("Location: ?_route=editar&id=$cto_id");
                        exit();
                    }
                    
                    // Remover um cliente
                    if (isset($_POST['cliente_id_remover'])) {
                        $cliente_id = intval($_POST['cliente_id_remover']);
                        $db_linker->query("UPDATE sis_cliente SET cto_id = 0 WHERE id = $cliente_id");
                        $success_message = 'Cliente removido com sucesso!';
                        header("Location: ?_route=editar&id=$cto_id");
                        exit();
                    }
                    
                    // Antigas atribuições em lote (para compatibilidade)
                    if (isset($_POST['clientes_atribuir'])) {
                        $clientes_selecionados = isset($_POST['clientes_ids']) ? (array) $_POST['clientes_ids'] : array();
                        $clientes_selecionados = array_map('intval', $clientes_selecionados);
                        
                        $cto_query = $db_linker->query("SELECT nome FROM mp_caixa WHERE id = $cto_id LIMIT 1");
                        $cto_data = mysqli_fetch_assoc($cto_query);
                        $cto_nome = $cto_data['nome'];
                        
                        $atribuidos_query = $db_linker->query("SELECT id FROM sis_cliente WHERE cto_id = $cto_id OR caixa_herm = '" . $db_linker->escape($cto_nome) . "'");
                        $atribuidos_atuais = array();
                        while ($row = mysqli_fetch_assoc($atribuidos_query)) {
                            $atribuidos_atuais[] = $row['id'];
                        }
                        
                        $clientes_para_remover = array_diff($atribuidos_atuais, $clientes_selecionados);
                        foreach ($clientes_para_remover as $cliente_id) {
                            $db_linker->query("UPDATE sis_cliente SET cto_id = 0, caixa_herm = '' WHERE id = " . intval($cliente_id));
                        }
                        
                        foreach ($clientes_selecionados as $cliente_id) {
                            $db_linker->query("UPDATE sis_cliente SET cto_id = $cto_id WHERE id = " . intval($cliente_id));
                        }
                        
                        $success_message = 'Clientes atribuídos com sucesso!';
                        header("Location: ?_route=editar&id=$cto_id");
                        exit();
                    }
                } catch (Exception $e) {
                    $error_message = 'Erro ao processar: ' . $e->getMessage();
                }
            }
            
            // Buscar CTO específica
            $sql = "SELECT * FROM mp_caixa WHERE id = $cto_id LIMIT 1";
            $result = $db_linker->query($sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $cto_nome = $row['nome'];
                
                // Mapear dados
                $cto_selecionada = array(
                    'id' => $row['id'],
                    'nome' => $row['nome'],
                    'localizacao' => $row['endereco'],
                    'portas' => $row['capacidade'],
                    'portas_livres' => $row['capacidade'],
                    'status' => 'Ativo',
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude'],
                    'tipo' => $row['tipo'],
                    'sinal' => $row['sinal'],
                    'olt' => $row['olt'],
                    'fsp' => $row['fsp']
                );
                
                // Buscar clientes atribuídos a esta CTO
                $clientes_sql = "SELECT sc.id, sc.nome, sc.login, sc.coordenadas, 
                                CASE 
                                    WHEN ra.radacctid IS NOT NULL THEN 'online' 
                                    ELSE 'offline' 
                                END as status
                               FROM sis_cliente sc
                               LEFT JOIN radacct ra ON ra.username = sc.login AND ra.acctstoptime IS NULL
                               WHERE (sc.cto_id = " . $cto_id . " OR sc.caixa_herm = '" . $db_linker->escape($cto_nome) . "')
                               ORDER BY sc.nome ASC";
                
                $clientes_result = $db_linker->query($clientes_sql);
                
                if ($clientes_result && mysqli_num_rows($clientes_result) > 0) {
                    while ($cliente_row = mysqli_fetch_assoc($clientes_result)) {
                        $cto_clientes[] = array(
                            'id' => $cliente_row['id'],
                            'nome' => $cliente_row['nome'],
                            'login' => $cliente_row['login'],
                            'coordenadas' => $cliente_row['coordenadas'],
                            'status' => $cliente_row['status']
                        );
                    }
                }
                
                // Calcular portas livres (capacidade - quantidade de clientes atribuídos)
                $total_clientes_atribuidos = count($cto_clientes);
                $portas_livres = $row['capacidade'] - $total_clientes_atribuidos;
                if ($portas_livres < 0) $portas_livres = 0;
                
                // Atualizar dados da CTO com portas calculadas
                $cto_selecionada['portas_livres'] = $portas_livres;
                $cto_selecionada['portas_utilizadas'] = $total_clientes_atribuidos;
                
                // Buscar todos os clientes ativos disponíveis
                $todos_sql = "SELECT sc.id, sc.nome, sc.login, sc.cli_ativado
                             FROM sis_cliente sc
                             WHERE sc.cli_ativado = 's'
                             ORDER BY sc.nome ASC";
                
                $todos_result = $db_linker->query($todos_sql);
                
                if ($todos_result) {
                    $cto_clientes_ids = array_column($cto_clientes, 'id');
                    while ($todos_row = mysqli_fetch_assoc($todos_result)) {
                        $ativado = (isset($todos_row['cli_ativado']) && strtolower($todos_row['cli_ativado']) === 's') ? 1 : 0;
                        $todos_clientes[] = array(
                            'id' => $todos_row['id'],
                            'nome' => $todos_row['nome'],
                            'login' => $todos_row['login'],
                            'ativado' => $ativado,
                            'atribuido' => in_array($todos_row['id'], $cto_clientes_ids)
                        );
                    }
                } else {
                    error_log('Erro na query de clientes: ' . $db_linker->error);
                }
            }
        }
    }
} catch (Exception $e) {
    error_log('Erro ao carregar CTO: ' . $e->getMessage());
}
?>
