<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <title>Backup - GERENCIADOR FTTH</title>
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

        .section {
            margin-bottom: 30px;
        }

        .section h2 {
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .section-content {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-backup {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-backup:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-restore {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            margin-left: 10px;
        }

        .btn-restore:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
        }

        .form-group {
            margin: 15px 0;
        }

        .form-group input[type="file"] {
            padding: 8px;
            border: 1px dashed #ddd;
            border-radius: 4px;
        }

        .info-box {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
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

            .btn-restore {
                margin-left: 0;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>💾 Backup de Dados</h1>
        </div>

        <a href="?" class="btn-voltar">🏠 Voltar ao Painel</a>

        <!-- Content -->
        <div class="content">
            <?php
            // Exibir mensagens
            if (isset($_SESSION['mensagem_sucesso'])) {
                echo '<div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; border-left: 4px solid #22c55e; margin-bottom: 20px;">';
                echo '<strong>✓ Sucesso:</strong> ' . htmlspecialchars($_SESSION['mensagem_sucesso']);
                echo '</div>';
                unset($_SESSION['mensagem_sucesso']);
            }
            if (isset($backup_error) && !empty($backup_error)) {
                echo '<div style="background: #fee2e2; color: #7f1d1d; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444; margin-bottom: 20px;">';
                echo '<strong>❌ Erro:</strong> ' . htmlspecialchars($backup_error);
                echo '</div>';
            }
            ?>
            <div class="info-box">
                <strong>ℹ️ Informação:</strong> Faça backup seguro de todos os seus dados de CTOs ou restaure de um backup anterior.
            </div>

            <!-- Seção de Backup -->
            <div class="section">
                <h2>📥 Criar Backup</h2>
                <div class="section-content">
                    <p style="margin-bottom: 15px; color: #666;">
                        Crie um arquivo de backup contendo todos os dados de CTOs cadastradas no sistema.
                    </p>
                    <form method="POST" action="?_route=backup">
                        <button type="submit" name="action" value="backup" class="btn btn-backup">
                            ✓ Baixar Backup Agora
                        </button>
                    </form>
                </div>
            </div>

            <!-- Seção de Restore -->
            <div class="section">
                <h2>📤 Restaurar Backup</h2>
                <div class="section-content">
                    <p style="margin-bottom: 15px; color: #666;">
                        Selecione um arquivo de backup para restaurar dados anteriores.
                    </p>
                    <form method="POST" action="?_route=backup" enctype="multipart/form-data">
                        <div class="form-group">
                            <input type="file" name="backup_file" accept=".json,.sql,.csv" required>
                        </div>
                        <button type="submit" name="action" value="restore" class="btn btn-restore">
                            ✓ Restaurar
                        </button>
                    </form>
                </div>
            </div>

            <!-- Histórico de Backups -->
            <div class="section">
                <h2>📋 Histórico de Backups</h2>
                <div class="section-content">
                    <p style="color: #999; text-align: center; padding: 20px;">
                        Nenhum backup disponível
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
