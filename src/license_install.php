<?php
/**
 * PÁGINA DE ATIVAÇÃO DE LICENÇA
 * Solicita a chave de licença para ativar o addon
 */

require_once dirname(__FILE__) . '/LicenseManager.php';

$licenseManager = new LicenseManager();
$status = $licenseManager->getLicenseStatus();
$mensagem = '';
$erro = false;

// Processar formulário de ativação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ativar_licenca'])) {
    $chave = trim($_POST['chave'] ?? '');
    $cliente = trim($_POST['cliente'] ?? '');
    
    if (empty($chave) || empty($cliente)) {
        $mensagem = 'Preencha todos os campos obrigatórios';
        $erro = true;
    } else {
        $result = $licenseManager->activateLicense($chave, $cliente, 365);
        
        if (isset($result['erro'])) {
            $mensagem = $result['mensagem'];
            $erro = true;
        } else {
            $mensagem = 'Licença ativada com sucesso! Redirecionando...';
            $erro = false;
            echo <<<HTML
            <script>
                setTimeout(function() {
                    window.location.href = '/admin/addons/caixas/';
                }, 2000);
            </script>
            HTML;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ativar Licença - GERENCIADOR FTTH</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .info-box {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }
        
        .info-box strong {
            color: #333;
            display: block;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>�� Ativar Licença</h1>
            <p>GERENCIADOR FTTH v2.0</p>
        </div>
        
        <?php if (!empty($mensagem)): ?>
            <div class="message <?php echo $erro ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$status['instalada'] || $status['expirada']): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="cliente">Nome da Empresa *</label>
                    <input type="text" id="cliente" name="cliente" placeholder="Ex: RAPNET Telecomunicações" required>
                </div>
                
                <div class="form-group">
                    <label for="chave">Chave de Licença *</label>
                    <input type="text" id="chave" name="chave" placeholder="XXXX-XXXX-XXXX-XXXX" required pattern="[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}">
                </div>
                
                <button type="submit" name="ativar_licenca">Ativar Licença</button>
                
                <div class="info-box">
                    <?php if ($status['expirada']): ?>
                        <strong>⚠️ Sua licença expirou!</strong>
                        Insira uma nova chave de licença para continuar usando o addon.
                    <?php else: ?>
                        <strong>❓ Como obter uma chave de licença?</strong>
                        Acesse o painel de administração do mk-auth e solicite uma chave de licença para este addon.
                    <?php endif; ?>
                </div>
            </form>
        <?php else: ?>
            <div class="message success">
                ✅ Licença ativa! Redirecionando para o addon...
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = '/admin/addons/caixas/';
                }, 2000);
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
