<?php
// === CONFIGURAÇÃO INICIAL ===
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error.log');

// === INICIALIZAR SESSÃO COM MESMO MÉTODO DO MK-AUTH ===
session_name('mka');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === DEBUG: Log da sessão recebida ===
error_log("INDEX.PHP - SESSION COUNT: " . count($_SESSION));
error_log("INDEX.PHP - SESSION VARS: " . json_encode(array_keys($_SESSION)));

// === VERIFICAÇÃO DE AUTENTICAÇÃO FLEXÍVEL ===
// Não redirecionar se sessão vazia - o mk-auth vai cuidar
require_once dirname(__FILE__) . '/src/auth_handler.php';

// Se sessão está vazia, pode estar em redirecionamento do mk-auth
if (empty($_SESSION)) {
    // Não fazer nada - deixar a página carregar
    // O mk-auth vai cuidar da autenticação
    error_log("INDEX.PHP - SESSÃO VAZIA - DEIXANDO mk-auth CUIDAR");
} else {
    // Se tem sessão, verificar se está autenticado
    if (!AuthHandler::isAuthenticated()) {
        header("Location: ../../");
        exit();
    }
}

// === CARREGAR DEPENDÊNCIAS ===
$addon_base = dirname(__FILE__);

// Verificar se addons.class.php existe
if (!file_exists($addon_base . '/addons.class.php')) {
    // Se não existe, criar um arquivo dummy
    file_put_contents($addon_base . '/addons.class.php', '<?php class Addons {} ?>');
}

require_once $addon_base . '/addons.class.php';

// === VALIDAR LICENÇA ===
if (file_exists($addon_base . "/src/LicenseMiddleware.php")) {
    require_once $addon_base . "/src/LicenseMiddleware.php";
    $middleware = new LicenseMiddleware();
    $status = $middleware->getStatus();
    if (!$status["instalada"] || (isset($status["expirada"]) && $status["expirada"])) {
        header("Location: src/license_install.php");
        exit;
    }
}

// === CONTROLAR ROTEAMENTO ===
$route = isset($_GET['_route']) ? $_GET['_route'] : '';

// === INCLUIR APLICAÇÃO SE HOUVER ROTA ===
if (!empty($route) && in_array($route, ['inicio', 'adicionar', 'editar', 'backup', 'maps', 'mapadeclientes', 'mapadectos', 'configurar'])) {
    $app_file = $addon_base . '/src/cto/componente/' . $route . '/index.php';
    if (file_exists($app_file)) {
        include_once $app_file;
        exit();
    }
}

// === RENDERIZAR DASHBOARD PADRÃO ===
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <title>MK - AUTH :: Gerenciador FTTH</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            width: 100%;
            height: 100%;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            animation: fadeInDown 0.6s ease-out;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.6s ease-out;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .card-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
        }

        .card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.3em;
        }

        .card p {
            color: #666;
            font-size: 0.95em;
            line-height: 1.6;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .debug-info {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 30px;
            font-family: monospace;
            font-size: 0.85em;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <h1>📡 Gerenciador FTTH</h1>
            <p>Sistema de Gestão de CTO e Componentes</p>
        </div>

        <div class="cards-grid">
            <div class="card">
                <div class="card-icon">📊</div>
                <h3>Componentes CTO</h3>
                <p>Gerencie todos os componentes de seus CTOs instalados na rede.</p>
            </div>

            <div class="card">
                <div class="card-icon">🗺️</div>
                <h3>Mapas e Localização</h3>
                <p>Visualize a localização dos CTOs e componentes em mapas interativos.</p>
            </div>

            <div class="card">
                <div class="card-icon">⚙️</div>
                <h3>Configurações</h3>
                <p>Configure opções do sistema e preferências de visualização.</p>
            </div>

            <div class="card">
                <div class="card-icon">💾</div>
                <h3>Backup</h3>
                <p>Realize backup e restaure dados do seu sistema.</p>
            </div>
        </div>

        <?php if (empty($_SESSION)): ?>
        <div class="debug-info">
            <strong>ℹ️ Nota:</strong> Sistema está carregando. Se você está logado no mk-auth, os dados serão carregados em breve.<br>
            <small>Session Status: Aguardando inicialização | Auth Variable: <?php echo AuthHandler::getAuthVariable() ?? 'Nenhuma'; ?></small>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
