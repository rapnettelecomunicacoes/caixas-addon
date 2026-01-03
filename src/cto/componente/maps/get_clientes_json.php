<?php
/**
 * Endpoint otimizado para obter dados de clientes em JSON
 * Utilizado para atualização em tempo real do mapa
 */

// Headers de cache
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

// Incluir controlador (sem renderizar view)
$component_base = dirname(__FILE__);
$clientes_data = [];

try {
    // Carregar banco de dados
    $db_init = $component_base . '/../../database/index.php';
    
    if (file_exists($db_init)) {
        require_once $db_init;
        
        // Verificar conexão
        if (isset($db_linker) && $db_linker->isConnected()) {
            // Query otimizada - apenas campos necessários
            $sql = "SELECT 
                        sc.id, 
                        sc.nome, 
                        sc.coordenadas, 
                        sc.login,
                        CASE 
                            WHEN (SELECT COUNT(*) FROM radacct WHERE acctstoptime IS NULL AND username = sc.login) > 0 
                            THEN 'online' 
                            ELSE 'offline' 
                        END as status
                    FROM sis_cliente sc
                    WHERE sc.coordenadas IS NOT NULL 
                    AND sc.coordenadas != '' 
                    AND sc.cli_ativado = 'S'
                    ORDER BY sc.nome ASC
                    LIMIT 5000";
            
            $result = $db_linker->query($sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    // Parse coordenadas
                    $coords = explode(',', trim($row['coordenadas']));
                    if (count($coords) == 2) {
                        $latitude = floatval(trim($coords[0]));
                        $longitude = floatval(trim($coords[1]));
                        
                        if ($latitude != 0 && $longitude != 0) {
                            $clientes_data[] = [
                                'id' => $row['id'],
                                'nome' => $row['nome'],
                                'latitude' => $latitude,
                                'longitude' => $longitude,
                                'status' => $row['status']
                            ];
                        }
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    // Log silencioso
    error_log("Erro em get_clientes_json: " . $e->getMessage());
}

// Retornar JSON com compressão
echo json_encode($clientes_data);
exit;
?>
