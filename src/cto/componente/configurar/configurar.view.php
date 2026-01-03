<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <title>Configurações - GERENCIADOR FTTH</title>
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
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            margin-bottom: 30px;
            color: white;
        }

        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .btn-voltar {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid white;
            display: inline-block;
        }

        .btn-voltar:hover {
            background: white;
            color: #667eea;
        }

        .content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.6s ease-out;
        }

        .info-box {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .success-box {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #065f46;
        }

        .error-box {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #7f1d1d;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #333;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1em;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group small {
            display: block;
            color: #666;
            margin-top: 5px;
            line-height: 1.5;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-submit {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            color: white;
            flex: 1;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(14, 165, 233, 0.4);
        }

        .btn-cancel {
            background: #e5e7eb;
            color: #333;
            flex: 1;
        }

        .btn-cancel:hover {
            background: #d1d5db;
        }

        .help-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .help-link:hover {
            text-decoration: underline;
        }

        .section-title {
            font-size: 1.3em;
            color: #333;
            margin-top: 30px;
            margin-bottom: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.5em;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>⚙️ Configurações</h1>
        </div>

        <a href="?" class="btn-voltar">← Voltar ao Painel</a>

        <!-- Content -->
        <div class="content">
            <?php
            // As variáveis $message, $message_type e $api_key já estão definidas pelo controller
            ?>

            <?php if ($message): ?>
                <div class="<?php echo $message_type === 'success' ? 'success-box' : 'error-box'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <strong>ℹ️ Informação:</strong> Configure a chave da API do Google Maps para ativar funcionalidades de mapa no sistema.
            </div>

            <div class="section-title">🗺️ Google Maps API</div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="google_maps_api_key">Chave da API do Google Maps:</label>
                    <input 
                        type="text" 
                        id="google_maps_api_key" 
                        name="google_maps_api_key" 
                        value="<?php echo htmlspecialchars($api_key); ?>"
                        placeholder="AIzaSyD..."
                    >
                    <small>
                        Obtenha sua chave de API gratuita no 
                        <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="help-link">
                            Google Cloud Console
                        </a>
                        . Certifique-se de habilitar as APIs:
                        <br>• Maps JavaScript API
                        <br>• Geocoding API
                        <br>• Directions API
                    </small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-submit">✓ Salvar Configurações</button>
                    <a href="?" class="btn btn-cancel">Cancelar</a>
                </div>
            </form>

            <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #eee;">
                <h3 style="color: #333; margin-bottom: 15px;">📚 Como obter a chave da API:</h3>
                <ol style="color: #666; line-height: 2;">
                    <li>Acesse <a href="https://console.cloud.google.com/" target="_blank" class="help-link">Google Cloud Console</a></li>
                    <li>Crie um novo projeto ou selecione um existente</li>
                    <li>Habilite as APIs necessárias:
                        <ul style="margin-left: 20px; margin-top: 10px;">
                            <li>Maps JavaScript API</li>
                            <li>Geocoding API</li>
                            <li>Directions API</li>
                        </ul>
                    </li>
                    <li>Vá para "Credenciais" e crie uma chave de API</li>
                    <li>Copie e cole a chave acima</li>
                    <li>Salve as configurações</li>
                </ol>
            </div>

            <div style="margin-top: 30px; padding: 20px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                <strong>⚠️ Importante:</strong> A chave da API será armazenada no arquivo de configuração local. 
                Certifique-se de que este arquivo não seja acessível publicamente.
            </div>
        </div>
    </div>
</body>
</html>
