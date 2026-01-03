<?php
/**
 * Roteador - Componente
 */

// === FUNÇÃO PARA FAZER UNSERIALIZE MANUAL DE SESSÃO ===
if (!function_exists('unserialize_session')) {
    function unserialize_session($data) {
        $vars = array();
        $a = preg_split("/(\w+)\|/", $data, -1, PREG_SPLIT_DELIM_CAPTURE);
        for ($i = 1; $i < count($a); $i += 2) {
            $k = $a[$i];
            $v = unserialize($a[$i + 1]);
            $vars[$k] = $v;
        }
        return $vars;
    }
}

// === CARREGAR DADOS DE SESSÃO MANUALMENTE ===
if (empty($_SESSION)) {
    $_SESSION = [];

    foreach ($_COOKIE as $name => $value) {
        if (strpos($name, '_admin-') === 0 && strpos($name, '-MKA') !== false) {
            $sess_file = '/var/tmp/sess_' . $value;
            
            if (file_exists($sess_file)) {
                $content = file_get_contents($sess_file);
                if (!empty($content)) {
                    $_SESSION = unserialize_session($content);
                    break;
                }
            }
        }
    }
}

// === VERIFICAÇÃO DE AUTENTICAÇÃO ===
if (empty($_SESSION) || !isset($_SESSION['MKA_Logado'])) {
    header("Location: ../../../../../../../");
    exit();
}

$component_base = dirname(__FILE__);
$component_name = basename(dirname(__FILE__));

// === INCLUIR ARQUIVOS NECESSÁRIOS DO ADDON ===
// Precisa ser relativo ao diretório raiz do addon
$addon_base = dirname(dirname(dirname(dirname(__FILE__))));

// Incluir configurações de banco de dados
if (file_exists($addon_base . '/src/cto/config/database.hhvm')) {
    include_once $addon_base . '/src/cto/config/database.hhvm';
} elseif (file_exists($addon_base . '/src/cto/config/database.php')) {
    include_once $addon_base . '/src/cto/config/database.php';
}

// Incluir índice do banco de dados
if (file_exists($addon_base . '/src/cto/database/index.hhvm')) {
    include_once $addon_base . '/src/cto/database/index.hhvm';
} elseif (file_exists($addon_base . '/src/cto/database/index.php')) {
    include_once $addon_base . '/src/cto/database/index.php';
}

// Incluir modelos
if (file_exists($addon_base . '/src/cto/models/client.hhvm')) {
    include_once $addon_base . '/src/cto/models/client.hhvm';
} elseif (file_exists($addon_base . '/src/cto/models/client.php')) {
    include_once $addon_base . '/src/cto/models/client.php';
}

// === DEFINIR VARIÁVEIS PADRÃO ===
if (!isset($table_name)) {
    $table_name = 'mp_caixas';
}

// Carregar controller - procurar por .hhvm ou .php
$controller_file_hhvm = $component_base . '/controller.hhvm';
$controller_file_php = $component_base . '/controller.php';

if (file_exists($controller_file_hhvm)) {
    include_once $controller_file_hhvm;
} elseif (file_exists($controller_file_php)) {
    include_once $controller_file_php;
}

// Carregar view - procurar por .hhvm ou .php
$view_file_hhvm = $component_base . '/' . $component_name . '.view.hhvm';
$view_file_php = $component_base . '/' . $component_name . '.view.php';

if (file_exists($view_file_hhvm)) {
    include_once $view_file_hhvm;
} elseif (file_exists($view_file_php)) {
    include_once $view_file_php;
} else {
    die('Erro: Arquivo de view não encontrado');
}
?>
