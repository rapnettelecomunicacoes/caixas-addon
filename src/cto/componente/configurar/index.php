<?php
/**
 * Roteador - Componente Configurar
 * Simplificado para garantir que todas as dependências sejam carregadas
 */

// === VERIFICAÇÃO DE AUTENTICAÇÃO ===
if (empty($_SESSION) || !isset($_SESSION['MKA_Logado'])) {
    // Tentar carregar session manualmente
    if (empty($_SESSION)) {
        $_SESSION = [];
        foreach ($_COOKIE as $name => $value) {
            if (strpos($name, '_admin-') === 0 && strpos($name, '-MKA') !== false) {
                $sess_file = '/var/tmp/sess_' . $value;
                if (file_exists($sess_file)) {
                    $content = file_get_contents($sess_file);
                    if (!empty($content)) {
                        // Tentar desserializar
                        $a = preg_split("/(\w+)\|/", $content, -1, PREG_SPLIT_DELIM_CAPTURE);
                        for ($i = 1; $i < count($a); $i += 2) {
                            $_SESSION[$a[$i]] = unserialize($a[$i + 1]);
                        }
                        break;
                    }
                }
            }
        }
    }
    
    if (empty($_SESSION) || !isset($_SESSION['MKA_Logado'])) {
        header("Location: ../../../../../../../");
        exit();
    }
}

// === DEFINIR VARIÁVEIS ===
$component_base = dirname(__FILE__);
$component_name = 'configurar';
$addon_base = dirname(dirname(dirname(__FILE__)));

// === INCLUIR BANCO DE DADOS ===
require_once $addon_base . '/config/database.php';

// === INCLUIR API ===
require_once $addon_base . '/config/api.php';

// === DEFINIR TABELA ===
if (!isset($table_name)) {
    $table_name = 'mp_caixas';
}

// === INCLUIR CONTROLLER ===
require_once $component_base . '/controller.php';

// === INCLUIR VIEW ===
require_once $component_base . '/configurar.view.php';
?>
