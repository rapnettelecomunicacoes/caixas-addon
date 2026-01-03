<?php
// Debug - Verificar estado da sessão
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/debug.log');

echo "<pre>";
echo "=== DEBUG SESSÃO CAIXAS ===\n";
echo "Session Status: " . session_status() . " (1=disabled, 0=none, 2=active)\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "\n_COOKIE: ";
print_r($_COOKIE);
echo "\n_SESSION (antes): ";
print_r($_SESSION);

// Tentar iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_name('mka');
    session_start();
}

echo "\n_SESSION (depois de start): ";
print_r($_SESSION);

echo "\nChecando variáveis de auth: \n";
echo "mka_logado: " . (isset($_SESSION['mka_logado']) ? 'SIM' : 'NAO') . "\n";
echo "MKA_Logado: " . (isset($_SESSION['MKA_Logado']) ? 'SIM' : 'NAO') . "\n";

echo "\n_GET: ";
print_r($_GET);

echo "</pre>";
?>
