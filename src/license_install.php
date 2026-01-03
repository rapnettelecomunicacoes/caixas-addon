<?php
/**
 * FORMULÁRIO DE INSTALAÇÃO DE LICENÇA
 * Aceita código de licença e valida contra banco de dados
 * ⚠️ AGORA COM SUPORTE A BANCO DE DADOS
 */

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
        // Inicializar gerenciador
        $manager = new LicenseManager();
        
        // Procurar licença no banco
        $license_db = new LicenseDB();
        $license_data = $license_db->getLicenseByKey($license_code);
        
        if (!$license_data) {
            $errors[] = 'Código de licença não encontrado. Por favor, verifique e tente novamente.';
        } else {
            // Validar expiração
            if ($license_data['expiracao']) {
                $expiracao_time = strtotime($license_data['expiracao']);
                if (time() > $expiracao_time) {
                    $errors[] = 'Licença expirada em ' . $license_data['expiracao'];
                } else {
                    $success = true;
                    $message = 'Licença instalada com sucesso! Redirecionando...';
                }
            } else {
                $success = true;
                $message = 'Licença vitalícia instalada com sucesso! Redirecionando...';
            }
        }
    }
}

// Se sucesso, redireciona
if ($success) {
    header('Location: /admin/addons/caixas/', true, 302);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação de Licença - GERENCIADOR FTTH v2.0</title>
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
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            font-family: 'Monaco', 'Courier New', monospace;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input[type="text"]::placeholder {
            color: #999;
        }
        
        .form-group button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .form-group button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .form-group button:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background-color: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        .alert ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .alert li {
            margin: 5px 0;
        }
        
        .help-text {
            color: #666;
            font-size: 12px;
            margin-top: 8px;
            line-height: 1.6;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="license-container">
        <div class="license-header">
            <div class="logo">📜</div>
            <h1>Instalação de Licença</h1>
            <p>GERENCIADOR FTTH v2.0</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>Erro ao validar licença:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>Sucesso!</strong> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="license_code">Código de Licença:</label>
                <input 
                    type="text" 
                    id="license_code" 
                    name="license_code" 
                    placeholder="XXXX-XXXX-XXXX-XXXX"
                    maxlength="19"
                    pattern="[A-F0-9\-]{19}"
                    autocomplete="off"
                    required
                >
                <div class="help-text">
                    ℹ️ Digite o código de licença fornecido.<br>
                    Formato: XXXX-XXXX-XXXX-XXXX (maiúsculas)
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit">Validar Licença</button>
            </div>
        </form>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #999; font-size: 12px;">
            <p>Não possui uma licença?<br>Entre em contato com o suporte.</p>
        </div>
    </div>
</body>
</html>
