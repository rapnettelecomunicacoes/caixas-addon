<?php
/**
 * VALIDADOR DE LICENÇA PARA COMPONENTES
 * Incluir este arquivo no topo de cada componente
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obter caminho base do addon
$addon_base = dirname(__DIR__);

// Carregar middleware
require_once $addon_base . '/src/LicenseMiddleware.php';
$licenseMiddleware = new LicenseMiddleware();
$licenseStatus = $licenseMiddleware->getStatus();

// Verificar se licença é válida
if (!$licenseStatus['instalada']) {
    // Redirecionar para página de instalação de licença (URL relativa)
    header('Location: src/license_install.php');
    exit;
}

// Verificar expiração
if (isset($licenseStatus['expirada']) && $licenseStatus['expirada']) {
    // Redirecionar para página de instalação de licença (também serve para renovação)
    header('Location: src/license_install.php?reason=expired');
    exit;
}

// Se chegou aqui, licença é válida
// Renderizar aviso se necessário
if ($licenseMiddleware->isNearExpiration()) {
    $licenseMiddleware->renderWarning();
}
?>
