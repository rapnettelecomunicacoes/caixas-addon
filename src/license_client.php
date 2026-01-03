<?php
/**
 * PAINEL DE VALIDAÇÃO DE LICENÇA
 * Onde o cliente insere a chave
 */

session_start();

// Verificar autenticação
if (empty($_SESSION) || !isset($_SESSION['MKA_Logado'])) {
    header("Location: ../../");
    exit();
}

require_once dirname(__FILE__) . '/LicenseManager.php';
$licenseManager = new LicenseManager();

$mensagem = '';
$tipo_mensagem = '';
$status = $licenseManager->getLicenseStatus();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = isset($_POST['acao']) ? $_POST['acao'] : '';
    
    if ($acao === 'validar') {
        $chave = isset($_POST['chave']) ? trim($_POST['chave']) : '';
        $cliente = isset($_POST['cliente']) ? trim($_POST['cliente']) : '';
        
        if (empty($chave)) {
            $mensagem = 'Por favor, insira uma chave de licença válida.';
            $tipo_mensagem = 'erro';
        } else {
            // Validar chave
            $validacao = $licenseManager->validateLicense($chave, $cliente);
            
            if ($validacao['valida']) {
                // Salvar licença
                $resultado = $licenseManager->saveLicense($chave, [
                    'cliente' => $validacao['cliente'],
                    'criacao' => $validacao['criacao'],
                    'expiracao' => $validacao['expiracao'],
                    'versao' => $validacao['versao']
                ]);
                
                if (isset($resultado['sucesso'])) {
                    $mensagem = 'Licença validada e instalada com sucesso! O addon agora está totalmente desbloqueado.';
                    $tipo_mensagem = 'sucesso';
                    $status = $licenseManager->getLicenseStatus();
                } else {
                    $mensagem = $resultado['erro'] ?? 'Erro ao instalar a licença.';
                    $tipo_mensagem = 'erro';
                }
            } else {
                $mensagem = $validacao['erro'] ?? 'Chave de licença inválida.';
                $tipo_mensagem = 'erro';
                
                if (isset($validacao['aviso'])) {
                    $mensagem .= ' (' . $validacao['aviso'] . ')';
                    $tipo_mensagem = 'warning';
                }
            }
        }
    } elseif ($acao === 'remover') {
        // Remover licença (apenas se confirmado)
        if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'sim') {
            $licenseFile = '/var/tmp/license_' . md5($GLOBALS['ADDON_KEY'] ?? 'caixas') . '.json';
            if (file_exists($licenseFile)) {
                if (unlink($licenseFile)) {
                    $mensagem = 'Licença removida com sucesso.';
                    $tipo_mensagem = 'info';
                    $status = $licenseManager->getLicenseStatus();
                } else {
                    $mensagem = 'Erro ao remover a licença.';
                    $tipo_mensagem = 'erro';
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validação de Licença - GERENCIADOR FTTH</title>
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
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        
        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: 'Courier New', monospace;
            transition: all 0.3s ease;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.2);
        }
        
        input::placeholder {
            color: #999;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            margin-top: 10px;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .status-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
            margin-bottom: 30px;
        }
        
        .status-card.expired {
            border-left-color: #dc3545;
        }
        
        .status-card.warning {
            border-left-color: #ffc107;
        }
        
        .status-card h3 {
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.expired {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-badge.warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .info-item {
            background: white;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .info-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .info-value {
            color: #333;
            font-weight: 600;
            margin-top: 4px;
            word-break: break-word;
        }
        
        .divider {
            height: 1px;
            background: #eee;
            margin: 30px 0;
        }
        
        .help-text {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 4px;
            color: #0c5460;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .help-text strong {
            color: #004085;
        }
        
        .footer {
            text-align: center;
            padding: 20px 30px;
            background: #f9f9f9;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 12px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal.show {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 400px;
            text-align: center;
        }
        
        .modal-content h2 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .modal-content p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
        }
        
        .modal-buttons button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .modal-buttons .btn-confirm {
            background: #dc3545;
            color: white;
        }
        
        .modal-buttons .btn-cancel {
            background: #ddd;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🔐 GERENCIADOR FTTH</h1>
            <p>Validação de Licença</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <?php if ($mensagem): ?>
                <div class="alert alert-<?php echo $tipo_mensagem; ?>">
                    <?php 
                    $icon = [
                        'sucesso' => '✅',
                        'erro' => '❌',
                        'warning' => '⚠️',
                        'info' => 'ℹ️'
                    ];
                    echo ($icon[$tipo_mensagem] ?? '') . ' ' . $mensagem;
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Status da Licença -->
            <?php if ($status['instalada']): ?>
                <?php 
                $clase_card = '';
                $status_text = '✅ Licença Ativa';
                
                if (isset($status['expirada']) && $status['expirada']) {
                    $clase_card = 'expired';
                    $status_text = '❌ Licença Expirada';
                } elseif (isset($status['proxima_expiracao']) && $status['proxima_expiracao']) {
                    $clase_card = 'warning';
                    $status_text = '⚠️ Próxima de Expirar';
                }
                ?>
                
                <div class="status-card <?php echo $clase_card; ?>">
                    <h3>
                        <?php echo $status_text; ?>
                    </h3>
                    <div class="status-info">
                        <div class="info-item">
                            <div class="info-label">Cliente</div>
                            <div class="info-value"><?php echo $status['cliente']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Validade</div>
                            <div class="info-value"><?php echo $status['expiracao']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Instalado em</div>
                            <div class="info-value"><?php echo $status['instalado_em']; ?></div>
                        </div>
                        <?php if (isset($status['dias_restantes'])): ?>
                        <div class="info-item">
                            <div class="info-label">Dias Restantes</div>
                            <div class="info-value"><?php echo $status['dias_restantes']; ?> dias</div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" class="btn btn-danger" onclick="mostrarModalRemocao()">
                        Remover Licença
                    </button>
                </div>
                
                <div class="help-text">
                    <strong>✅ Addon Desbloqueado:</strong> Você tem acesso a todas as funcionalidades do GERENCIADOR FTTH.
                </div>
                
            <?php else: ?>
                <!-- Formulário de Validação -->
                <div class="help-text">
                    <strong>📝 Como fazer:</strong> Recebeu uma chave de licença? Insira-a abaixo para desbloquear todas as funcionalidades do addon.
                </div>
                
                <form method="POST">
                    <input type="hidden" name="acao" value="validar">
                    
                    <div class="form-group">
                        <label for="chave">Chave de Licença *</label>
                        <input type="text" id="chave" name="chave" placeholder="XXXX-XXXX-XXXX-XXXX" 
                               required pattern="[A-Z0-9-]{19}" title="Formato esperado: XXXX-XXXX-XXXX-XXXX">
                    </div>
                    
                    <div class="form-group">
                        <label for="cliente">Seu Nome / Nome da Empresa (Opcional)</label>
                        <input type="text" id="cliente" name="cliente" placeholder="Ex: Empresa ABC Telecomunicações">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        🔓 Validar Licença
                    </button>
                </form>
                
                <div class="divider"></div>
                
                <div style="background: #fff3cd; padding: 15px; border-radius: 8px; color: #856404; font-size: 14px;">
                    <strong>ℹ️ Informação:</strong> O addon está funcionando em <strong>modo de teste</strong>. Todas as funcionalidades estão disponíveis, mas sem uma licença válida.
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            GERENCIADOR FTTH v2.0 | Desenvolvido por Patrick Nascimento | © 2026
        </div>
    </div>
    
    <!-- Modal de Confirmação -->
    <div id="modalRemocao" class="modal">
        <div class="modal-content">
            <h2>⚠️ Remover Licença?</h2>
            <p>Tem certeza que deseja remover a licença? O addon voltará ao modo de teste.</p>
            <form method="POST" style="display: contents;">
                <input type="hidden" name="acao" value="remover">
                <input type="hidden" name="confirmar" value="sim">
                <div class="modal-buttons">
                    <button type="submit" class="btn-confirm">Remover</button>
                    <button type="button" class="btn-cancel" onclick="fecharModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function mostrarModalRemocao() {
            document.getElementById('modalRemocao').classList.add('show');
        }
        
        function fecharModal() {
            document.getElementById('modalRemocao').classList.remove('show');
        }
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            var modal = document.getElementById('modalRemocao');
            if (event.target == modal) {
                modal.classList.remove('show');
            }
        }
        
        // Formatar chave enquanto digita
        document.getElementById('chave').addEventListener('input', function(e) {
            let valor = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            let formatado = '';
            
            for (let i = 0; i < valor.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formatado += '-';
                }
                formatado += valor[i];
            }
            
            e.target.value = formatado;
        });
    </script>
</body>
</html>
