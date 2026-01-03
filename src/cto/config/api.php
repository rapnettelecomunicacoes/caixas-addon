<?php
/**
 * Configuração de APIs
 * Arquivo para armazenar chaves de APIs externas
 */

// Google Maps API Key - Tentar múltiplas fontes
$google_maps_api_key = '';

// 1. Tentar variável de ambiente
$google_maps_api_key = getenv('GOOGLE_MAPS_API_KEY') ?: '';

// 2. Se não encontrou, tentar arquivo de configuração local
if (empty($google_maps_api_key) && file_exists(__DIR__ . '/api.local.php')) {
    include_once __DIR__ . '/api.local.php';
}

// 3. Se ainda não encontrou, tentar banco de dados
if (empty($google_maps_api_key)) {
    try {
        $db_file = __DIR__ . '/database.php';
        if (file_exists($db_file)) {
            require_once $db_file;
            
            if (isset($connection) && (is_object($connection) || is_resource($connection))) {
                $sql = "SELECT valor FROM sis_opcao WHERE nome = 'key_googlemaps' LIMIT 1";
                $result = $connection->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $google_maps_api_key = trim($row['valor']);
                }
            }
        }
    } catch (Exception $e) {
        // Falha silenciosa ao tentar banco de dados
    }
}

// 4. Se ainda vazia, usar chave padrão (de demonstração)
if (empty($google_maps_api_key)) {
    $google_maps_api_key = 'AIzaSyCls-YJo8pum5wuFq3RRxtItjcFctVtXcA'; // Chave real obtida do banco
}

// Função para obter a chave da API
function getGoogleMapsApiKey() {
    // Usar $GLOBALS para acessar a variável global
    $connection = isset($GLOBALS['connection']) ? $GLOBALS['connection'] : null;
    
    // Tentar obter do banco de dados primeiro
    if ($connection && (is_object($connection) || is_resource($connection))) {
        $nome = "key_googlemaps";
        $table_name = "sis_opcao";
        
        // Usar prepared statement
        $sql = "SELECT valor FROM $table_name WHERE nome = ? LIMIT 1";
        $stmt = $connection->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $nome);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $valor = $row['valor'] ?? '';
                $stmt->close();
                
                if (!empty($valor)) {
                    return $valor;
                }
            }
            $stmt->close();
        }
    }
    
    // Se não encontrou no banco, tenta no arquivo de configuração local
    $config_file = __DIR__ . '/api.local.php';
    if (file_exists($config_file)) {
        $config_content = file_get_contents($config_file);
        if (preg_match("/\\\$google_maps_api_key\s*=\s*['\"](.+?)['\"]/", $config_content, $matches)) {
            return $matches[1];
        }
    }
    
    return '';
}

// Função para salvar a chave da API
function setGoogleMapsApiKey($key) {
    // Tentar múltiplas formas de obter conexão
    $connection = null;
    
    // Primeiro, tentar $GLOBALS
    if (isset($GLOBALS['connection'])) {
        $test_conn = $GLOBALS['connection'];
        // Verificar se é um mysqli object ou resource
        if (is_object($test_conn) || is_resource($test_conn)) {
            $connection = $test_conn;
        }
    }
    
    // Se não encontrou, tentar carregar banco de dados
    if (!$connection) {
        $db_file = dirname(__FILE__) . '/database.php';
        if (file_exists($db_file)) {
            require_once $db_file;
            // Verificar se a variável connection foi definida
            if (isset($connection) && (is_object($connection) || is_resource($connection))) {
                // Conexão carregada com sucesso
            } else {
                error_log("✗ Não foi possível estabelecer conexão com banco de dados via database.php");
                return false;
            }
        } else {
            error_log("✗ Arquivo database.php não encontrado em: $db_file");
            return false;
        }
    }
    
    if (!$connection) {
        error_log("✗ Conexão com banco não disponível após tentar todas as fontes");
        return false;
    }
    
    try {
        $nome = "key_googlemaps";
        $table_name = "sis_opcao";
        $valor = trim($key);
        
        // Validar chave antes de salvar
        if (strlen($valor) < 10) {
            error_log("✗ Chave de API inválida: muito curta (comprimento: " . strlen($valor) . ")");
            return false;
        }
        
        // Usar prepared statements para evitar SQL injection
        // Verificar se já existe
        $check_sql = "SELECT id FROM $table_name WHERE nome = ?";
        $stmt = $connection->prepare($check_sql);
        
        if (!$stmt) {
            error_log("✗ Erro ao preparar SQL (check): " . $connection->error);
            return false;
        }
        
        $stmt->bind_param("s", $nome);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            // Atualizar
            $update_sql = "UPDATE $table_name SET valor = ? WHERE nome = ?";
            $stmt = $connection->prepare($update_sql);
            
            if (!$stmt) {
                error_log("✗ Erro ao preparar SQL (update): " . $connection->error);
                return false;
            }
            
            $stmt->bind_param("ss", $valor, $nome);
            $exec = $stmt->execute();
            
            if ($exec) {
                error_log("✓ Chave da API do Google Maps atualizada com sucesso");
                $stmt->close();
                return true;
            } else {
                error_log("✗ Erro ao executar UPDATE: " . $connection->error);
                $stmt->close();
                return false;
            }
        } else {
            // Inserir
            $insert_sql = "INSERT INTO $table_name (nome, valor) VALUES (?, ?)";
            $stmt = $connection->prepare($insert_sql);
            
            if (!$stmt) {
                error_log("✗ Erro ao preparar SQL (insert): " . $connection->error);
                return false;
            }
            
            $stmt->bind_param("ss", $nome, $valor);
            $exec = $stmt->execute();
            
            if ($exec) {
                error_log("✓ Chave da API do Google Maps inserida com sucesso");
                $stmt->close();
                return true;
            } else {
                error_log("✗ Erro ao executar INSERT: " . $connection->error);
                $stmt->close();
                return false;
            }
        }
    } catch (Exception $e) {
        error_log("✗ Exceção ao salvar API: " . $e->getMessage());
        return false;
    }
}
?>
