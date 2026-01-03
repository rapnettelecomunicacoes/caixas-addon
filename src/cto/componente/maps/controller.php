<?php
/**
 * Controller - Componente MAPS
 * Carrega dados dos clientes (online e offline) para exibição no mapa
 */

$component_base = dirname(__FILE__);
$clientes_data = [];
$total_online = 0;
$total_offline = 0;

try {
    // Carregar banco de dados
    $db_init = $component_base . '/../../database/index.php';
    
    if (file_exists($db_init)) {
        require_once $db_init;
        
        // Verificar se conseguiu conexão
        if (isset($db_linker) && $db_linker->isConnected()) {
            // Buscar clientes com coordenadas e que estão ativados
            $sql = "SELECT sc.id, sc.nome, sc.coordenadas, sc.endereco, sc.login, sc.cli_ativado
                    FROM sis_cliente sc
                    WHERE sc.coordenadas IS NOT NULL 
                    AND sc.coordenadas != '' 
                    AND sc.cli_ativado = 'S'
                    ORDER BY sc.nome ASC";
            $result = $db_linker->query($sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    // Parse das coordenadas (formato: "latitude,longitude")
                    $coords = explode(',', trim($row['coordenadas']));
                    if (count($coords) == 2) {
                        $latitude = floatval(trim($coords[0]));
                        $longitude = floatval(trim($coords[1]));
                        
                        // Verificar se cliente está online (tem sessão aberta em radacct)
                        $status = 'offline';
                        $login = $row['login'];
                        $offline_time = null;
                        
                        // Verificar em radacct se tem sessão ativa (acctstoptime IS NULL)
                        if (!empty($login)) {
                            // Primeiro, buscar sessão ATIVA (online)
                            $check_sql = "SELECT radacctid, acctstoptime FROM radacct WHERE acctstoptime IS NULL AND username = '" . $db_linker->escape($login) . "' LIMIT 1";
                            $check_result = $db_linker->query($check_sql);
                            
                            if ($check_result && mysqli_num_rows($check_result) > 0) {
                                // Tem sessão ativa
                                $status = 'online';
                                $total_online++;
                            } else {
                                // Não tem sessão ativa, buscar última sessão offline
                                $offline_sql = "SELECT radacctid, acctstoptime FROM radacct WHERE acctstoptime IS NOT NULL AND username = '" . $db_linker->escape($login) . "' ORDER BY acctstoptime DESC LIMIT 1";
                                $offline_result = $db_linker->query($offline_sql);
                                
                                if ($offline_result && mysqli_num_rows($offline_result) > 0) {
                                    $offline_row = mysqli_fetch_assoc($offline_result);
                                    $status = 'offline';
                                    $offline_time = $offline_row['acctstoptime'];
                                }
                                $total_offline++;
                            }
                        } else {
                            $total_offline++;
                        }
                        
                        $clientes_data[] = array(
                            'id' => $row['id'],
                            'nome' => $row['nome'],
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                            'endereco' => $row['endereco'],
                            'login' => $login,
                            'cli_ativado' => $row['cli_ativado'],
                            'status' => $status,
                            'offline_time' => $offline_time
                        );
                    }
                }
                mysqli_free_result($result);
            }
        }
    }
} catch (Exception $e) {
    error_log("Erro ao carregar dados do mapa: " . $e->getMessage());
}

// Carregar configuração de API
require_once $component_base . '/../../config/api.php';
$api_key = getGoogleMapsApiKey();



