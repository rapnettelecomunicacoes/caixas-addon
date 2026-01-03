<?php
/**
 * INSTALAÇÃO DE LICENÇA - SIMPLIFICADO
 * Sem dependência de banco de dados
 */

session_name('mka');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__FILE__) . '/LicenseManager.php';

$errors = [];
$success = false;
$message = '';

// Processa submissão do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['license_code'])) {
    $license_code = trim($_POST['license_code']);
    
    if (empty($license_code)) {
        $errors[] = 'Código de licença não pode estar vazio';
    } else {
        // Validar formato
        $manager = new LicenseManager();
        $result = $manager->validateLicense($license_code);
        
        if ($result) {
            $success = true;
            $message = 'Licença instalada com sucesso! Redirecionando...';
            header('Refresh: 2; url=/admin/addons/caixas/');
        } else {
            $errors[] = 'Formato de licença inválido. Use: XXXX-XXXX-XXXX-XXXX (hexadecimal)';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação de Licença - GERENCIADOR FTTH</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .license-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        
        .license-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .license-header h1 {
            margin: 0;
            color: #333;
            font-size: 28px;
            font-weight: 600;
        }
        
        .license-header p {
            color: #666;
            margin: 10px 0 0 0;
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
        
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            cursor: pointer;
        }
        
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: 400;
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }
        
        .success-message {
            background: #efe;
            color: #3c3;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #3c3;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        
        button:hover {
            opacity: 0.9;
        }
        
        .help-text {
            color: #999;
            font-size: 13px;
            margin-top: 5px;
        }
        
        .format-hint {
            background: #f5f5f5;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            color: #666;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="license-container">
        <div class="license-header">
            <h1>📜 Instalação de Licença</h1>
            <p>GERENCIADOR FTTH v2.0</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <strong>Erro ao validar licença:</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                <strong>✅ Sucesso!</strong><br>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="license_code">Código de Licença:</label>
                    <input 
                        type="text" 
                        id="license_code" 
                        name="license_code" 
                        placeholder="XXXX-XXXX-XXXX-XXXX"
                        required
                    >
                    <div class="help-text">
                        Digite o código de licença fornecido
                    </div>
                </div>
                
                <div class="format-hint">
                    <strong>Formato aceito:</strong> XXXX-XXXX-XXXX-XXXX<br>
                    Onde X é um caractere hexadecimal (0-9, A-F)
                </div>
                
                <button type="submit" style="margin-top: 20px;">
                    Validar Licença
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
