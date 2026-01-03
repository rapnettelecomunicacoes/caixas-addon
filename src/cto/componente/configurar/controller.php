<?php
/**
 * Controller - Configurações de API
 */

$message = '';
$message_type = '';

// Obter chave atual da API
$api_key = getGoogleMapsApiKey();

// Processar formulário se foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_api_key = trim($_POST['google_maps_api_key'] ?? '');
    
    if (!empty($new_api_key)) {
        // Debug
        error_log("=== Tentativa de salvar API ===");
        error_log("Chave: $new_api_key");
        error_log("Conexão tipo: " . gettype($GLOBALS['connection'] ?? null));
        error_log("Conexão é_recurso: " . (is_resource($GLOBALS['connection'] ?? null) ? 'SIM' : 'NÃO'));
        
        if (setGoogleMapsApiKey($new_api_key)) {
            $message = '✓ Chave da API do Google Maps salva com sucesso!';
            $message_type = 'success';
            // Recarregar a chave para refletir a mudança
            $api_key = $new_api_key;
            error_log("✓ API salva com sucesso!");
        } else {
            $message = '✗ Erro ao salvar a chave da API. Verifique os logs do servidor.';
            $message_type = 'error';
            error_log("✗ Erro ao salvar API");
        }
    } else {
        $message = '✗ Por favor, insira uma chave de API válida.';
        $message_type = 'error';
    }
}
?>
