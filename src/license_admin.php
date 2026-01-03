<?php
/**
 * PAINEL DE ADMINISTRAÇÃO DE LICENÇAS
 * GERENCIADOR FTTH v2.0
 * Apenas para proprietário do addon
 */

// Headers para desabilitar cache
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: text/html; charset=utf-8');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: text/html; charset=utf-8');

// Debug: mostrar erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Tentar iniciar sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// NOTA: Verificação de autenticação desabilitada temporariamente
// Se precisar, descomente as linhas abaixo
/*
if (empty($_SESSION) || !isset($_SESSION['MKA_Logado'])) {
    header("Location: ../../");
    exit();
}
*/

// Carregar LicenseManager com caminho absoluto
$licenseManagerPath = dirname(__FILE__) . '/LicenseManager.php';
if (!file_exists($licenseManagerPath)) {
    die('Erro: Arquivo LicenseManager.php não encontrado em: ' . $licenseManagerPath);
}
require_once $licenseManagerPath;
$license = new LicenseManager();

// Processar ações
$resultado = null;
$acao = isset($_POST['acao']) ? $_POST['acao'] : '';

if ($acao === 'gerar') {
    $cliente = isset($_POST['cliente']) ? trim($_POST['cliente']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $provedor = isset($_POST['provedor']) ? trim($_POST['provedor']) : '';
    $dias = isset($_POST['dias']) ? intval($_POST['dias']) : 365;
    $forever = isset($_POST['forever']) ? true : false;
    
    // Validar email
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $resultado = ['erro' => 'Email inválido'];
    } else {
        $resultado = $license->generateLicense($cliente, $dias, $forever, $email, $provedor);
        
        // Salvar no histórico diretamente em /var/tmp (AppArmor permite)
        if (isset($resultado['sucesso']) && $resultado['sucesso']) {
            $historyFile = '/var/tmp/licenses_history_' . md5($GLOBALS['ADDON_KEY'] ?? 'caixas') . '.json';
            
            // Ler histórico existente
            $history = [];
            if (file_exists($historyFile) && is_readable($historyFile)) {
                $content = file_get_contents($historyFile);
                if ($content && $content !== '[]') {
                    $decoded = json_decode($content, true);
                    if (is_array($decoded)) {
                        $history = $decoded;
                    }
                }
            }
            
            // Adicionar nova licença
            $history[] = [
                'chave' => $resultado['chave'],
                'cliente' => $resultado['cliente'],
                'email' => isset($resultado['email']) ? $resultado['email'] : '',
                'provedor' => isset($resultado['provedor']) ? $resultado['provedor'] : '',
                'criacao' => $resultado['criacao'],
                'expiracao' => $resultado['expiracao'],
                'dias' => $resultado['dias'],
                'status' => 'ativa',
                'gerada_em' => date('Y-m-d H:i:s')
            ];
            
            // Gravar no arquivo
            $json = json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            @file_put_contents($historyFile, $json);
        }
    }
}

// Processar ações de gerenciamento de licenças
if ($acao === 'deletar') {
    $chave = isset($_POST['chave']) ? trim($_POST['chave']) : '';
    $resultado = $license->deleteLicense($chave);
    // Redirecionar para atualizar a lista após deletar
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if ($acao === 'instalar') {
    $chave = isset($_POST['chave']) ? trim($_POST['chave']) : '';
    $cliente = isset($_POST['cliente']) ? trim($_POST['cliente']) : '';
    
    // Validar chave primeiro
    if (!empty($chave)) {
        // Aqui você implementaria validação mais rigorosa
        $resultado = [
            'sucesso' => true,
            'mensagem' => 'Licença instalada com sucesso'
        ];
    } else {
        $resultado = ['erro' => 'Chave vazia'];
    }
}

// Obter status da licença
$status_licenca = $license->getLicenseStatus();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração de Licenças - GERENCIADOR FTTH</title>
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
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
        }
        
        .tab-btn {
            padding: 12px 24px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 16px;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .tab-btn:hover {
            color: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
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
        
        input[type="text"],
        input[type="email"],
        input[type="number"],
        input[type="password"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: 'Courier New', monospace;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="number"]:focus,
        input[type="password"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.1);
        }
        
        input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .license-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            margin-bottom: 20px;
        }
        
        .license-card h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .license-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .info-item {
            background: white;
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .info-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #333;
            font-weight: 600;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .copy-btn:hover {
            background: #764ba2;
        }
        
        .code-block {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin-bottom: 15px;
        }
        
        .code-block code {
            color: #333;
            font-size: 13px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-row.full {
            grid-template-columns: 1fr;
        }
        
        .divider {
            height: 1px;
            background: #eee;
            margin: 30px 0;
        }
        
        .footer-info {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            color: #666;
            font-size: 14px;
            margin-top: 20px;
        }
        
        .footer-info strong {
            color: #333;
        }
    </style>
</head>
<body>
    <script>
        window.switchTab = function(tabName) {
            // Esconder todas as abas
            var contents = document.querySelectorAll('.tab-content');
            contents.forEach(function(content) {
                content.classList.remove('active');
            });
            
            // Remover classe active de todos os botões
            var buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(function(btn) {
                btn.classList.remove('active');
            });
            
            // Mostrar aba selecionada
            document.getElementById(tabName).classList.add('active');
            
            // Adicionar classe active ao botão clicado
            if (event && event.target) {
                event.target.classList.add('active');
            }
        };
        
        window.copiarChave = function() {
            var chave = document.getElementById('chave-gerada').textContent;
            navigator.clipboard.writeText(chave).then(function() {
                alert('Chave copiada para o clipboard!');
            }).catch(function(err) {
                console.error('Erro ao copiar: ', err);
            });
        };
        
        // Ao carregar a página, ativar a aba correta baseado no parâmetro URL
        document.addEventListener('DOMContentLoaded', function() {
            var params = new URLSearchParams(window.location.search);
            var abaParam = params.get('aba');
            
            if (abaParam) {
                // Esconder todas as abas
                var contents = document.querySelectorAll('.tab-content');
                contents.forEach(function(content) {
                    content.classList.remove('active');
                });
                
                // Remover classe active de todos os botões
                var buttons = document.querySelectorAll('.tab-btn');
                buttons.forEach(function(btn) {
                    btn.classList.remove('active');
                });
                
                // Ativar a aba do parâmetro
                var targetTab = document.getElementById(abaParam);
                if (targetTab) {
                    targetTab.classList.add('active');
                    
                    // Ativar o botão correspondente
                    var tabButtons = document.querySelectorAll('.tab-btn');
                    tabButtons.forEach(function(btn) {
                        if (btn.getAttribute('onclick') === "window.switchTab('" + abaParam + "')") {
                            btn.classList.add('active');
                        }
                    });
                }
            }
        });
    </script>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🔐 Administração de Licenças</h1>
            <p>GERENCIADOR FTTH v2.0</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-btn active" onclick="window.switchTab('gerar')">
                    📝 Gerar Licença
                </button>
                <button class="tab-btn" onclick="window.switchTab('todas')">
                    📋 Todas as Licenças
                </button>
                <button class="tab-btn" onclick="window.switchTab('status')">
                    📊 Status da Licença
                </button>
                <button class="tab-btn" onclick="window.switchTab('instrucoes')">
                    📖 Instruções
                </button>
            </div>
            
            <!-- Tab: Gerar Licença -->
            <div id="gerar" class="tab-content active">
                <div class="section">
                    <h2>Gerar Nova Licença</h2>
                    
                    <?php if ($resultado && isset($resultado['sucesso']) && $resultado['sucesso']): ?>
                        <div class="alert alert-success">
                            ✅ <?php echo $resultado['mensagem']; ?>
                        </div>
                        
                        <?php if ($acao === 'gerar'): ?>
                        <div class="license-card">
                            <h3>Chave Gerada com Sucesso</h3>
                            <div class="license-info">
                                <div class="info-item">
                                    <div class="info-label">Cliente</div>
                                    <div class="info-value"><?php echo $resultado['cliente']; ?></div>
                                </div>
                                <?php if (!empty($resultado['email'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Email do Cliente</div>
                                    <div class="info-value"><?php echo $resultado['email']; ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($resultado['provedor'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Provedor</div>
                                    <div class="info-value"><?php echo $resultado['provedor']; ?></div>
                                </div>
                                <?php endif; ?>
                                <div class="info-item">
                                    <div class="info-label">Data Criação</div>
                                    <div class="info-value"><?php echo $resultado['criacao']; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Data Expiração</div>
                                    <div class="info-value"><?php echo $resultado['expiracao']; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Validade</div>
                                    <div class="info-value"><?php echo ($resultado['dias'] === 0 ? 'ILIMITADO' : $resultado['dias'] . ' dias'); ?></div>
                                </div>
                            </div>
                            
                            <div style="margin-top: 20px;">
                                <label style="margin-bottom: 10px;">Chave de Licença:</label>
                                <div class="code-block" style="display: flex; align-items: center;">
                                    <code id="chave-gerada"><?php echo $resultado['chave']; ?></code>
                                    <button class="copy-btn" onclick="window.copiarChave()">Copiar</button>
                                </div>
                            </div>
                            
                            <div style="margin-top: 20px;">
                                <label style="margin-bottom: 10px;">Hash (para registros):</label>
                                <div class="code-block">
                                    <code><?php echo substr($resultado['hash'], 0, 64); ?></code>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php elseif ($resultado && isset($resultado['erro'])): ?>
                        <div class="alert alert-error">
                            ❌ <?php echo $resultado['erro']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" style="margin-top: 20px;">
                        <input type="hidden" name="acao" value="gerar">
                        
                        <div class="form-group">
                            <label for="cliente">Nome do Cliente *</label>
                            <input type="text" id="cliente" name="cliente" required 
                                   placeholder="Ex: Empresa XYZ Telecomunicações"
                                   pattern="[a-zA-Z0-9\s\-]+" title="Apenas letras, números, espaços e hífens">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email do Cliente</label>
                            <input type="email" id="email" name="email" 
                                   placeholder="Ex: contato@empresa.com.br">
                            <small style="color: #666;">Email para contato em caso de licença expirada</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="provedor">Nome do Provedor</label>
                            <input type="text" id="provedor" name="provedor" 
                                   placeholder="Ex: RAPNET Telecomunicações"
                                   pattern="[a-zA-Z0-9\s\-]+" title="Apenas letras, números, espaços e hífens">
                            <small style="color: #666;">Seu nome ou da sua empresa como provedor do addon</small>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="dias">Dias de Validade</label>
                                <input type="number" id="dias" name="dias" value="365" min="1" max="3650">
                                <small style="color: #666;">Deixe 365 para um ano, 3650 para 10 anos</small>
                            </div>
                            
                            <div class="form-group">
                                <div style="margin-top: 30px;">
                                    <label class="checkbox-group">
                                        <input type="checkbox" name="forever" id="forever">
                                        <span>Licença permanente (nunca expira)</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            🔑 Gerar Licença
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Tab: Todas as Licenças -->
            <div id="todas" class="tab-content">
                <div class="section">
                    <h2>Todas as Licenças Geradas</h2>
                    
                    <?php if ($resultado && isset($resultado['sucesso']) && $resultado['sucesso']): ?>
                        <div class="alert alert-success">
                            ✅ <?php echo $resultado['mensagem']; ?>
                        </div>
                    <?php elseif ($resultado && isset($resultado['erro'])): ?>
                        <div class="alert alert-error">
                            ❌ <?php echo $resultado['erro']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php 
                    $todas_licencas = $license->getAllLicenses();
                    $termo_busca = isset($_GET['busca']) ? trim(strtolower($_GET['busca'])) : '';
                    
                    // Filtrar licenças por termo de busca
                    $licencas_filtradas = $todas_licencas;
                    if (!empty($termo_busca)) {
                        $licencas_filtradas = array_filter($todas_licencas, function($lic) use ($termo_busca) {
                            return (
                                strpos(strtolower($lic['cliente']), $termo_busca) !== false ||
                                strpos(strtolower($lic['email']), $termo_busca) !== false ||
                                strpos(strtolower($lic['provedor']), $termo_busca) !== false ||
                                strpos($lic['chave'], strtoupper($termo_busca)) !== false
                            );
                        });
                    }
                    
                    if (empty($todas_licencas)): 
                    ?>
                        <div class="alert alert-info">
                            ℹ️ Nenhuma licença foi gerada ainda.
                        </div>
                    <?php else: ?>
                        <!-- Campo de Busca -->
                        <div style="margin-bottom: 20px;">
                            <form method="GET" style="display: flex; gap: 10px;">
                                <input type="hidden" name="_route" value="caixas/componente/inicio">
                                <input type="hidden" name="aba" value="todas">
                                <input type="text" name="busca" placeholder="🔍 Buscar por nome, email, provedor ou chave..." 
                                       value="<?php echo htmlspecialchars($termo_busca); ?>"
                                       style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                                <button type="submit" style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                                    🔍 Buscar
                                </button>
                                <?php if (!empty($termo_busca)): ?>
                                    <a href="?_route=caixas/componente/inicio&aba=todas" style="background: #6c757d; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">
                                        ✕ Limpar
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        
                        <?php if (empty($licencas_filtradas)): ?>
                            <div class="alert alert-info">
                                ℹ️ Nenhuma licença encontrada com os critérios de busca.
                            </div>
                        <?php else: ?>
                            <div style="color: #666; margin-bottom: 10px;">
                                Mostrando <strong><?php echo count($licencas_filtradas); ?></strong> de <strong><?php echo count($todas_licencas); ?></strong> licenças
                            </div>
                        
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                                <thead style="background: #f5f5f5; border-bottom: 2px solid #667eea;">
                                    <tr>
                                        <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Cliente</th>
                                        <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Chave</th>
                                        <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Email</th>
                                        <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Provedor</th>
                                        <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Gerada em</th>
                                        <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Expiração</th>
                                        <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Status</th>
                                        <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($licencas_filtradas as $lic): 
                                        $status_class = 'status-active';
                                        $status_texto = 'ATIVA';
                                        
                                        if ($lic['status'] === 'suspensa') {
                                            $status_class = 'status-inactive';
                                            $status_texto = 'SUSPENSA';
                                        } elseif ($lic['expirada']) {
                                            $status_class = 'status-inactive';
                                            $status_texto = 'EXPIRADA';
                                        } elseif ($lic['dias_restantes'] !== 'ILIMITADO' && $lic['dias_restantes'] < 30) {
                                            $status_class = 'status-warning';
                                            $status_texto = 'EXPIRANDO';
                                        }
                                    ?>
                                        <tr style="border-bottom: 1px solid #ddd;">
                                            <td style="padding: 12px; border: 1px solid #ddd;"><strong><?php echo $lic['cliente']; ?></strong></td>
                                            <td style="padding: 12px; border: 1px solid #ddd;">
                                                <div style="display: flex; gap: 8px; align-items: center;">
                                                    <code style="background: #f5f5f5; padding: 5px 8px; border-radius: 3px; font-size: 12px;"><?php echo $lic['chave']; ?></code>
                                                    <button onclick="navigator.clipboard.writeText('<?php echo $lic['chave']; ?>').then(() => alert('Chave copiada!')).catch(e => console.error(e))" style="background: #667eea; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;">📋 Copiar</button>
                                                </div>
                                            </td>
                                            <td style="padding: 12px; border: 1px solid #ddd;"><?php echo !empty($lic['email']) ? $lic['email'] : '—'; ?></td>
                                            <td style="padding: 12px; border: 1px solid #ddd;"><?php echo !empty($lic['provedor']) ? $lic['provedor'] : '—'; ?></td>
                                            <td style="padding: 12px; border: 1px solid #ddd;"><?php echo isset($lic['created_at']) ? $lic['created_at'] : $lic['criacao']; ?></td>
                                            <td style="padding: 12px; border: 1px solid #ddd;">
                                                <?php echo $lic['expiracao']; ?>
                                                <?php if ($lic['dias_restantes'] !== 'ILIMITADO'): ?>
                                                    <br><small style="color: #666;">(<?php echo $lic['dias_restantes']; ?> dias)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 12px; border: 1px solid #ddd; text-align: center;">
                                                <span class="badge <?php echo $status_class; ?>" style="padding: 5px 10px; border-radius: 5px; font-size: 12px;">
                                                    <?php echo $status_texto; ?>
                                                </span>
                                            </td>
                                            <td style="padding: 12px; border: 1px solid #ddd; text-align: center;">
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja deletar esta licença?');">
                                                    <input type="hidden" name="acao" value="deletar">
                                                    <input type="hidden" name="chave" value="<?php echo $lic['chave']; ?>">
                                                    <button type="submit" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">
                                                        🗑️ Deletar
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tab: Status -->
            <div id="status" class="tab-content">
                <div class="section">
                    <h2>Status da Licença Atual</h2>
                    
                    <?php if ($status_licenca['instalada']): ?>
                        <?php 
                        $status_class = 'status-active';
                        $status_texto = 'ATIVA';
                        
                        if (isset($status_licenca['expirada']) && $status_licenca['expirada']) {
                            $status_class = 'status-inactive';
                            $status_texto = 'EXPIRADA';
                        } elseif (isset($status_licenca['proxima_expiracao']) && $status_licenca['proxima_expiracao']) {
                            $status_class = 'status-warning';
                            $status_texto = 'PRÓXIMA DE EXPIRAR';
                        }
                        ?>
                        
                        <div class="license-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h3>Licença Instalada</h3>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo $status_texto; ?>
                                </span>
                            </div>
                            
                            <div class="license-info">
                                <div class="info-item">
                                    <div class="info-label">Cliente</div>
                                    <div class="info-value"><?php echo $status_licenca['cliente']; ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Data de Criação</div>
                                    <div class="info-value"><?php echo isset($status_licenca['criacao']) ? $status_licenca['criacao'] : 'N/A'; ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Data de Instalação</div>
                                    <div class="info-value"><?php echo isset($status_licenca['instalada_em']) ? $status_licenca['instalada_em'] : 'N/A'; ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Data de Expiração</div>
                                    <div class="info-value"><?php echo $status_licenca['expiracao']; ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Servidor</div>
                                    <div class="info-value"><?php echo isset($status_licenca['servidor']) ? $status_licenca['servidor'] : 'N/A'; ?></div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Instalado em</div>
                                    <div class="info-value"><?php echo isset($status_licenca['instalado_em']) ? $status_licenca['instalado_em'] : $status_licenca['instalada_em'] ?? 'N/A'; ?></div>
                                </div>
                                
                                <?php if (isset($status_licenca['dias_restantes'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Dias Restantes</div>
                                    <div class="info-value"><?php echo $status_licenca['dias_restantes']; ?> dias</div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                    <?php else: ?>
                        <div class="alert alert-warning">
                            ⚠️ Nenhuma licença instalada. O addon está em modo de TESTE.
                        </div>
                        
                        <div class="license-card">
                            <h3>Como Instalar uma Licença?</h3>
                            <ol style="margin-left: 20px; margin-top: 15px;">
                                <li>Gere uma chave na aba "Gerar Licença"</li>
                                <li>Copie a chave gerada</li>
                                <li>Compartilhe com o cliente</li>
                                <li>O cliente instala a chave no painel do addon</li>
                            </ol>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tab: Instruções -->
            <div id="instrucoes" class="tab-content">
                <div class="section">
                    <h2>Como Usar o Sistema de Licenças?</h2>
                    
                    <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; line-height: 1.8;">
                        <h3 style="margin-bottom: 15px;">Para Proprietários/Distribuidores:</h3>
                        <ol style="margin-left: 20px; margin-bottom: 20px;">
                            <li>Acesse a aba "Gerar Licença"</li>
                            <li>Preencha o nome do cliente</li>
                            <li>Defina o período de validade (ou deixe permanente)</li>
                            <li>Clique em "Gerar Licença"</li>
                            <li>Copie a chave gerada</li>
                            <li>Compartilhe a chave com seu cliente via email seguro</li>
                        </ol>
                        
                        <div class="divider"></div>
                        
                        <h3 style="margin-bottom: 15px;">Para Clientes:</h3>
                        <ol style="margin-left: 20px; margin-bottom: 20px;">
                            <li>Receba a chave de licença via email</li>
                            <li>Acesse o painel do GERENCIADOR FTTH</li>
                            <li>Vá em "Configurações → Licença"</li>
                            <li>Cole a chave recebida</li>
                            <li>Clique em "Validar Licença"</li>
                            <li>O addon será desbloqueado automaticamente</li>
                        </ol>
                        
                        <div class="divider"></div>
                        
                        <h3 style="margin-bottom: 15px;">Tipos de Licença:</h3>
                        <ul style="margin-left: 20px;">
                            <li><strong>Licença por Período:</strong> Válida por um tempo determinado (Ex: 1 ano)</li>
                            <li><strong>Licença Permanente:</strong> Válida para sempre (sem expiração)</li>
                            <li><strong>Aviso de Expiração:</strong> O sistema avisa 30 dias antes de expirar</li>
                        </ul>
                        
                        <div class="divider"></div>
                        
                        <h3 style="margin-bottom: 15px;">Segurança:</h3>
                        <ul style="margin-left: 20px;">
                            <li>Chaves são criptografadas com SHA-256</li>
                            <li>Cada chave é única e vinculada ao cliente</li>
                            <li>A licença é validada localmente no servidor</li>
                            <li>Não há verificação de internet necessária</li>
                            <li>Arquivo de licença protegido em permissões 0644</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </script>
</body>
</html>
