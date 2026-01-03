<?php
/**
 * TESTE DE INTEGRAÇÃO COM BANCO DE DADOS
 * Verifica se o sistema de licenças está funcionando com o banco
 */

require_once dirname(__FILE__) . '/src/LicenseManager.php';

echo "=== TESTE DE INTEGRAÇÃO COM BANCO DE DADOS ===\n\n";

// 1. Testar conexão
echo "1. Testando conexão com banco...\n";
$db = new LicenseDB();
if ($db->isConnected()) {
    echo "   ✅ Conectado com sucesso ao banco de dados\n\n";
} else {
    echo "   ❌ Falha na conexão ao banco de dados\n";
    exit(1);
}

// 2. Obter status da licença
echo "2. Obtendo status da licença...\n";
$manager = new LicenseManager();
$status = $manager->getLicenseStatus();
echo "   Status: " . json_encode($status, JSON_PRETTY_PRINT) . "\n\n";

// 3. Listar todas as licenças
echo "3. Listando todas as licenças no banco...\n";
$licenses = $manager->getAllLicenses();
echo "   Total de licenças: " . count($licenses) . "\n";
if (!empty($licenses)) {
    foreach ($licenses as $license) {
        echo "   - {$license['chave']} ({$license['cliente']}) - ";
        echo "Status: {$license['status']} - ";
        echo "Dias restantes: " . (isset($license['dias_restantes']) ? $license['dias_restantes'] : 'N/A') . "\n";
    }
}
echo "\n";

// 4. Testar validação de licença
echo "4. Testando validação de licença...\n";
if (!empty($licenses)) {
    $test_chave = $licenses[0]['chave'];
    $validation = $manager->validateLicense($test_chave);
    echo "   Validando: {$test_chave}\n";
    echo "   Resultado: " . json_encode($validation, JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "   ⚠️  Nenhuma licença para testar\n\n";
}

// 5. Testar geração de nova licença
echo "5. Testando geração de nova licença...\n";
$result = $manager->generateLicense('TESTE_DB_' . date('YmdHis'), 30, false, 'teste@test.com', 'test_provider');
if (isset($result['sucesso']) && $result['sucesso']) {
    echo "   ✅ Licença gerada com sucesso\n";
    echo "   Chave: {$result['chave']}\n";
    echo "   Cliente: {$result['cliente']}\n";
    echo "   Dias: {$result['dias']}\n\n";
    
    // Verificar se foi salva no banco
    echo "6. Verificando se a licença foi salva no banco...\n";
    $saved = $db->getLicenseByKey($result['chave']);
    if ($saved) {
        echo "   ✅ Licença encontrada no banco\n";
        echo "   Dados: " . json_encode($saved, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   ❌ Licença NÃO foi salva no banco\n";
    }
} else {
    echo "   ❌ Erro ao gerar licença: " . $result['erro'] . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>
