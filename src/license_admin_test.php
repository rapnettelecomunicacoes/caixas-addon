<?php
/**
 * PAINEL DE ADMINISTRAÇÃO DE LICENÇAS - VERSÃO TESTE
 * (sem verificação de autenticação)
 */

// Debug: mostrar erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Tentar iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>DEBUG - Informações da Sessão</h1>";
echo "<pre>";
echo "Session Status: " . session_status() . "\n";
echo "Session ID: " . session_id() . "\n";
echo "\$_SESSION contents:\n";
print_r($_SESSION);
echo "</pre>";

// Tentar carregar LicenseManager
$licenseManagerPath = dirname(__FILE__) . '/LicenseManager.php';
echo "<h2>Testando LicenseManager</h2>";
echo "Caminho: " . $licenseManagerPath . "<br>";
echo "Existe? " . (file_exists($licenseManagerPath) ? "SIM ✓" : "NÃO ✗") . "<br>";

if (file_exists($licenseManagerPath)) {
    require_once $licenseManagerPath;
    echo "Carregado com sucesso! ✓<br>";
    
    try {
        $license = new LicenseManager();
        echo "LicenseManager instanciado com sucesso! ✓<br>";
        $status = $license->getLicenseStatus();
        echo "<h3>Status da Licença:</h3>";
        echo "<pre>";
        print_r($status);
        echo "</pre>";
    } catch (Exception $e) {
        echo "Erro ao instanciar: " . $e->getMessage() . "<br>";
    }
} else {
    echo "ERRO: LicenseManager.php não encontrado!<br>";
}

// Informações do servidor
echo "<h2>Informações do Servidor</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Diretório atual: " . __DIR__ . "<br>";
echo "User: " . get_current_user() . "<br>";
?>
