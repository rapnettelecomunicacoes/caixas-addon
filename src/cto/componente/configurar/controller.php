<?php
/**
 * Controller - Configurações de API
 */

$message = '';
$message_type = '';

// Certificar que as funções de API estão disponíveis
if (!function_exists('getGoogleMapsApiKey')) {
    // Requerer o arquivo de API se não estiver carregado
    if (file_exists(dirname(dirname(dirname(__DIR__))) . '/config/api.php')) {
        require_once dirname(dirname(dirname(__DIR__))) . '/config/api.php';
    }
}

// Obter chave atual da API
$api_key = function_exists('getGoogleMapsApiKey') ? getGoogleMapsApiKey() : '';

// Processar formulário se foi submetido
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $new_api_key = trim($_POST['google_maps_api_key'] ?? '');
    
    if (!empty($new_api_key)) {
        // Debug
        error_log("=== Tentativa de salvar API ===");
        error_log("Chave: $new_api_key");
        error_log("Conexão tipo: " . gettype($GLOBALS['connection'] ?? null));
        error_log("Conexão é_recurso: " . (is_resource($GLOBALS['connection'] ?? null) ? 'SIM' : 'NÃO'));
        
        if (function_exists('setGoogleMapsApiKey') && setGoogleMapsApiKey($new_api_key)) {
            $message = '✓ Chave da API do Google Maps salva com sucesso!';
            $message_type = 'success';
            // Recarregar a chave para refletir a mudança
            $api_key = $new_api_key;
            error_log("✓ API salva com sucesso!");
        } else {
            $message = '✗ Erro ao salvar a chave da API. Verifique os logs do servidor.';
            $message_type = 'error';
            error_log("✗ Erro ao salvar API - setGoogleMapsApiKey não está disponível ou retornou false");
        }
    } else {
        $message = '✗ Por favor, insira uma chave de API válida.';
        $message_type = 'error';
    }
}
?>
