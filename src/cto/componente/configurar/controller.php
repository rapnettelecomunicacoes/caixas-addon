<?php
/**
 * Controller - Configurações de API
 * As variáveis globais $connection e as funções de API já foram carregadas pelo index.php
 */

$message = '';
$message_type = '';

// Obter chave atual da API
$api_key = '';
if (function_exists('getGoogleMapsApiKey')) {
    $api_key = getGoogleMapsApiKey();
} else {
    // Função não disponível, mostrar erro depois
}

// Processar formulário se foi submetido
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $new_api_key = trim($_POST['google_maps_api_key'] ?? '');
    
    if (!empty($new_api_key)) {
        // Verificar se função existe
        if (!function_exists('setGoogleMapsApiKey')) {
            $message = '✗ Erro: função de salvar não está disponível. Recarregue a página.';
            $message_type = 'error';
        } else {
            // Tentar salvar
            $result = setGoogleMapsApiKey($new_api_key);
            
            if ($result === true) {
                $message = '✓ Chave da API do Google Maps salva com sucesso!';
                $message_type = 'success';
                $api_key = $new_api_key;
            } else {
                $message = '✗ Erro ao salvar a chave da API. Verifique os logs do servidor.';
                $message_type = 'error';
            }
        }
    } else {
        $message = '✗ Por favor, insira uma chave de API válida.';
        $message_type = 'error';
    }
}
?>
