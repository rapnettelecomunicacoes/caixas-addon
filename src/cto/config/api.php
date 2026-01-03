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
            
            if (isset($db_linker) && $db_linker->isConnected()) {
                $sql = "SELECT valor FROM sis_opcao WHERE nome = 'key_googlemaps' LIMIT 1";
                $result = $db_linker->query($sql);
                
                if ($result && mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_assoc($result);
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
    if ($connection) {
        $nome = "key_googlemaps";
        $table_name = "sis_opcao";
        
        $result = @mysqli_query($connection, "SELECT valor FROM $table_name WHERE nome = '" . addslashes($nome) . "' LIMIT 1");
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_array($result)) {
                $valor = $row['valor'] ?? '';
                if (!empty($valor)) {
                    return $valor;
                }
            }
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
    // Usar $GLOBALS para acessar a variável global
    $connection = isset($GLOBALS['connection']) ? $GLOBALS['connection'] : null;
    
    if (!$connection) {
        error_log("✗ Conexão com banco não disponível");
        return false;
    }
    
    try {
        $nome = "key_googlemaps";
        $table_name = "sis_opcao";
        $valor = trim($key);
        
        // Verificar se já existe
        $check = @mysqli_query($connection, "SELECT id FROM $table_name WHERE nome = '" . addslashes($nome) . "' LIMIT 1");
        
        if ($check && mysqli_num_rows($check) > 0) {
            // Atualizar
            $sql = "UPDATE $table_name SET valor = '" . addslashes($valor) . "' WHERE nome = '" . addslashes($nome) . "'";
        } else {
            // Inserir
            $sql = "INSERT INTO $table_name (nome, valor) VALUES ('" . addslashes($nome) . "', '" . addslashes($valor) . "')";
        }
        
        error_log("Executando SQL: $sql");
        $result = @mysqli_query($connection, $sql);
        
        if ($result) {
            error_log("✓ API configurada com sucesso");
            return true;
        } else {
            error_log("✗ Erro SQL: " . mysqli_error($connection));
            return false;
        }
    } catch (Exception $e) {
        error_log("✗ Exceção: " . $e->getMessage());
        return false;
    }
}
