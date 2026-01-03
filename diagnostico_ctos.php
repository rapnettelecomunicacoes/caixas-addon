<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=== DIAGNÓSTICO DE CTOs ===\n\n";

// 1. Verificar sessão
session_name('mka');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "1. SESSÃO: " . (isset($_SESSION['mka_logado']) ? "AUTENTICADO" : "NÃO AUTENTICADO") . "\n\n";

// 2. Carregar configurações
$db_config = __DIR__ . '/src/cto/config/database.php';
echo "2. ARQUIVO DE CONFIG:\n";
echo "   Caminho: $db_config\n";
echo "   Existe: " . (file_exists($db_config) ? "SIM" : "NÃO") . "\n\n";

// 3. Tentar conexão manual
echo "3. TESTANDO CONEXÃO:\n";
try {
    require_once $db_config;
    
    echo "   Host: " . (isset($Host) ? $Host : 'NÃO DEFINIDO') . "\n";
    echo "   Usuário: " . (isset($user) ? $user : 'NÃO DEFINIDO') . "\n";
    echo "   Banco: " . (isset($db_name) ? $db_name : 'NÃO DEFINIDO') . "\n";
    echo "   Tabela: " . (isset($table_name) ? $table_name : 'NÃO DEFINIDO') . "\n";
    
    // Conectar
    $socket = isset($socket) ? $socket : null;
    $conn = @mysqli_connect($Host, $user, $pass, $db_name, 0, $socket);
    
    if ($conn) {
        echo "   Conexão: SUCESSO ✓\n\n";
        
        // 4. Verificar tabela
        echo "4. VERIFICANDO TABELA:\n";
        $table = isset($table_name) ? $table_name : 'mp_caixa';
        $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        if ($check && mysqli_num_rows($check) > 0) {
            echo "   Tabela '$table': EXISTE ✓\n\n";
            
            // 5. Contar registros
            echo "5. REGISTROS NA TABELA:\n";
            $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM $table");
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                $total = $row['total'];
                echo "   Total de registros: $total\n\n";
                
                // 6. Mostrar estrutura
                echo "6. ESTRUTURA DA TABELA:\n";
                $fields = mysqli_query($conn, "DESCRIBE $table");
                if ($fields) {
                    while ($field = mysqli_fetch_assoc($fields)) {
                        echo "   - " . $field['Field'] . " (" . $field['Type'] . ")\n";
                    }
                    echo "\n";
                }
                
                // 7. Mostrar dados
                if ($total > 0) {
                    echo "7. PRIMEIROS REGISTROS:\n";
                    $data = mysqli_query($conn, "SELECT * FROM $table LIMIT 3");
                    if ($data) {
                        $index = 1;
                        while ($row = mysqli_fetch_assoc($data)) {
                            echo "   Registro $index:\n";
                            foreach ($row as $key => $value) {
                                echo "     $key: " . substr($value, 0, 50) . "\n";
                            }
                            $index++;
                        }
                    }
                } else {
                    echo "7. DADOS: Nenhum registro encontrado\n";
                }
            }
        } else {
            echo "   Tabela '$table': NÃO EXISTE ⚠️\n";
        }
        
        mysqli_close($conn);
    } else {
        echo "   Conexão: FALHA ⚠️\n";
        echo "   Erro: " . mysqli_connect_error() . "\n\n";
    }
} catch (Exception $e) {
    echo "   ERRO: " . $e->getMessage() . "\n\n";
}

echo "</pre>";
?>
