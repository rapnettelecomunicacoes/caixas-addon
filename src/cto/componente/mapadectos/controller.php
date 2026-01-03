<?php
/**
 * Controller - Componente MAPA DE CTOs
 * Busca dados das CTOs para exibição no mapa
 */

$component_base = dirname(__FILE__);
$ctos_data = array();

if (isset($connection) && $connection) {
    // Buscar todas as CTOs com dados de clientes
    $sql = "SELECT 
                c.id,
                c.nome,
                c.endereco,
                c.latitude,
                c.longitude,
                c.capacidade,
                c.tipo,
                c.sinal,
                c.olt,
                c.fsp
            FROM mp_caixa c
            ORDER BY c.nome";
    
    $result = mysqli_query($connection, $sql);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
             = mysqli_real_escape_string(, );
            
            // Contar clientes atribuídos
            $count_sql = "SELECT COUNT(*) as total FROM sis_cliente 
                         WHERE (cto_id = " . $cto_id . " AND cto_id IS NOT NULL AND cto_id > 0)
                         OR (caixa_herm = '" . mysqli_real_escape_string($connection, $row['nome']) . "' AND caixa_herm IS NOT NULL AND caixa_herm != '')";
            $count_result = mysqli_query($connection, $count_sql);
            $count_row = mysqli_fetch_assoc($count_result);
            $total_clientes = $count_row['total'];
            
            // Contar clientes online (com sessão ativa no RADIUS)
            $online_sql = "SELECT COUNT(*) as total FROM sis_cliente sc 
                          INNER JOIN radacct ra ON ra.username = sc.login 
                          WHERE ra.acctstoptime IS NULL
                          AND ((sc.caixa_herm = " . $cto_nome . " AND sc.caixa_herm IS NOT NULL AND sc.caixa_herm != "")
                               OR (sc.caixa_herm = '" . mysqli_real_escape_string($connection, $row['nome']) . "' AND sc.caixa_herm IS NOT NULL AND sc.caixa_herm != ''))";
            $online_result = mysqli_query($connection, $online_sql);
            $online_row = mysqli_fetch_assoc($online_result);
            $total_online = $online_row['total'];
            
            $total_offline = $total_clientes - $total_online;
            
            // Buscar lista de clientes atribuídos
            $clientes_sql = "SELECT sc.id, sc.nome, sc.login, 
                            CASE WHEN ra.radacctid IS NOT NULL THEN 'online' ELSE 'offline' END as status
                            FROM sis_cliente sc
                            LEFT JOIN radacct ra ON ra.username = sc.login AND ra.acctstoptime IS NULL
                            WHERE (sc.caixa_herm = " . $cto_nome . " AND sc.caixa_herm IS NOT NULL AND sc.caixa_herm != "")
                            OR (sc.caixa_herm = '" . mysqli_real_escape_string($connection, $row['nome']) . "' AND sc.caixa_herm IS NOT NULL AND sc.caixa_herm != '')
                            ORDER BY sc.nome";
            $clientes_result = mysqli_query($connection, $clientes_sql);
            $clientes_list = array();
            
            while ($cliente = mysqli_fetch_assoc($clientes_result)) {
                $clientes_list[] = array(
                    'id' => $cliente['id'],
                    'nome' => $cliente['nome'],
                    'login' => $cliente['login'],
                    'status' => $cliente['status']
                );
            }
            
            // Calcular portas livres
            $portas_utilizadas = $total_clientes;
            $portas_livres = $row['capacidade'] - $portas_utilizadas;
            
            // Validar coordenadas
            $lat = floatval($row['latitude']);
            $lng = floatval($row['longitude']);
            
            if (empty($lat) || empty($lng) || $lat == 0 || $lng == 0) {
                continue; // Pular CTOs sem coordenadas válidas
            }
            
            $ctos_data[] = array(
                'id' => intval($row['id']),
                'nome' => $row['nome'],
                'endereco' => $row['endereco'],
                'latitude' => $lat,
                'longitude' => $lng,
                'capacidade' => intval($row['capacidade']),
                'tipo' => $row['tipo'],
                'sinal' => $row['sinal'],
                'olt' => $row['olt'],
                'fsp' => $row['fsp'],
                'total_clientes' => intval($total_clientes),
                'clientes_online' => intval($total_online),
                'clientes_offline' => intval($total_offline),
                'portas_utilizadas' => intval($portas_utilizadas),
                'portas_livres' => intval($portas_livres),
                'clientes' => $clientes_list
            );
        }
    }
}

// Converter para JSON para uso no JavaScript
$ctos_json = json_encode($ctos_data);

// Renderizar a view
require_once __DIR__ . '/mapadectos.view.php';
