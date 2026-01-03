<?php
// Arquivo para carregar CTOs via AJAX
header('Content-Type: application/json; charset=utf-8');

try {
    // Incluir arquivo de configuração do banco de dados
    $db_file = dirname(__FILE__) . '/../../config/database.php';
    if (!file_exists($db_file)) {
        $db_file = dirname(__FILE__) . '/../../config/database.hhvm';
    }
    
    // Carregar configuração do banco
    if (file_exists($db_file)) {
        require_once $db_file;
        if (isset($connection)) {
            $conn = $connection;
        }
    }

    // Se não conseguiu pela inclusão, tentar conexão direta
    if (!isset($conn) || !$conn) {
        // Valores padrão
        $Host = 'localhost';
        $user = 'root';
        $pass = 'vertrigo';
        $db_name = 'mkradius';
        $socket = '/var/run/mysqld/mysqld.sock';

        // Verificar socket
        if (!file_exists($socket)) {
            $socket = null;
        }

        // Conectar com socket ou TCP
        if ($socket) {
            $conn = @mysqli_connect('localhost', $user, $pass, $db_name, 0, $socket);
        } else {
            $conn = @mysqli_connect($Host, $user, $pass, $db_name);
        }
    }

    if (!$conn) {
        throw new Exception('Erro ao conectar ao banco de dados: ' . mysqli_connect_error());
    }

    // Configurar charset
    mysqli_set_charset($conn, "utf8mb4");

    // Buscar CTOs com coordenadas válidas
    $sql = "SELECT id, nome, endereco, latitude, longitude FROM mp_caixa 
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL 
            AND CAST(latitude AS DECIMAL(10,6)) != 0
            AND CAST(longitude AS DECIMAL(10,6)) != 0
            ORDER BY nome";
    
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        throw new Exception('Erro ao buscar CTOs: ' . mysqli_error($conn));
    }

    $ctos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Validar dados
        if (!empty($row['latitude']) && !empty($row['longitude']) && !empty($row['nome'])) {
            $ctos[] = [
                'id' => $row['id'],
                'nomecaixa' => $row['nome'],
                'endereco' => $row['endereco'] ?? '',
                'latitude' => (float)$row['latitude'],
                'longitude' => (float)$row['longitude']
            ];
        }
    }

    // Fechar conexão se foi criada localmente
    if (isset($conn) && !isset($GLOBALS['connection'])) {
        mysqli_close($conn);
    }

    // Retornar CTOs como JSON
    echo json_encode($ctos, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => true,
        'mensagem' => $e->getMessage()
    ]);
}
