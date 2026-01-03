<?php
// === CONFIGURAÇÃO INICIAL ===
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error.log');

// === NOTA IMPORTANTE ===
// Se a sessão está vazia, o mk-auth já está controlando a autenticação no nível do sistema
// Não forçamos redirect aqui, deixamos a página renderizar e o mk-auth controla o acesso

// === CARREGAR DEPENDÊNCIAS ===
$addon_base = dirname(__FILE__);
require_once $addon_base . '/addons.class.php';

// === VERIFICAÇÃO DE AUTENTICAÇÃO FLEXÍVEL (SÓ SE SESSION NÃO VAZIA) ===
// Se a sessão tem dados, verificamos autenticação via AuthHandler
if (!empty($_SESSION)) {
    require_once dirname(__FILE__) . '/src/auth_handler.php';
    if (!AuthHandler::isAuthenticated()) {
        // Se sessão existe mas não está autenticado, redireciona
        header("Location: /admin/");
        exit;
    }
}
// Se sessão está vazia, deixamos a página renderizar (mk-auth já controla no nível do sistema)

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
    <title>MK - AUTH :: <?php echo $Manifest->name; ?></title>
    
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
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 280px;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .card-icon {
            font-size: 3.5em;
            margin-bottom: 15px;
            display: inline-block;
        }

        .card-title {
            font-size: 1.5em;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .card-description {
            color: #666;
            font-size: 0.95em;
            line-height: 1.5;
            flex-grow: 1;
        }

        .card-footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            color: #667eea;
            font-weight: 600;
            font-size: 0.9em;
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

        .feature-card {
            animation: fadeInDown 0.6s ease-out;
        }

        .feature-card:nth-child(1) { animation-delay: 0.1s; }
        .feature-card:nth-child(2) { animation-delay: 0.2s; }
        .feature-card:nth-child(3) { animation-delay: 0.3s; }
        .feature-card:nth-child(4) { animation-delay: 0.4s; }
        .feature-card:nth-child(5) { animation-delay: 0.5s; }
        .feature-card:nth-child(6) { animation-delay: 0.6s; }
        .feature-card:nth-child(7) { animation-delay: 0.7s; }
        .feature-card:nth-child(8) { animation-delay: 0.8s; }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8em;
            }
            .features-grid {
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <h1>🎯 Central de Componentes CTO</h1>
            <p>Gerencie todos os seus componentes e mapas em um único lugar</p>
        </div>

        <div class="features-grid">
            <a href="?_route=inicio" class="feature-card">
                <div>
                    <div class="card-icon">��</div>
                    <div class="card-title">Componentes CTO</div>
                    <div class="card-description">Visualize e gerencie todos os componentes cadastrados na sua rede.</div>
                </div>
                <div class="card-footer">Acessar →</div>
            </a>

            <a href="?_route=adicionar" class="feature-card">
                <div>
                    <div class="card-icon">➕</div>
                    <div class="card-title">Adicionar Componente</div>
                    <div class="card-description">Registre novos componentes CTO na sua base de dados.</div>
                </div>
                <div class="card-footer">Acessar →</div>
            </a>

            <a href="?_route=editar" class="feature-card">
                <div>
                    <div class="card-icon">✏️</div>
                    <div class="card-title">Editar Componentes</div>
                    <div class="card-description">Modifique informações de componentes existentes.</div>
                </div>
                <div class="card-footer">Acessar →</div>
            </a>

            <a href="?_route=maps" class="feature-card">
                <div>
                    <div class="card-icon">🗺️</div>
                    <div class="card-title">Mapas</div>
                    <div class="card-description">Visualize a localização geográfica dos componentes.</div>
                </div>
                <div class="card-footer">Acessar →</div>
            </a>

            <a href="?_route=mapadeclientes" class="feature-card">
                <div>
                    <div class="card-icon">👥</div>
                    <div class="card-title">Mapa de Clientes</div>
                    <div class="card-description">Veja onde estão localizados seus clientes.</div>
                </div>
                <div class="card-footer">Acessar →</div>
            </a>

            <a href="?_route=mapadectos" class="feature-card">
                <div>
                    <div class="card-icon">🏢</div>
                    <div class="card-title">Mapa de CTOs</div>
                    <div class="card-description">Analise a distribuição das CTOs na cobertura.</div>
                </div>
                <div class="card-footer">Acessar →</div>
            </a>

            <a href="?_route=configurar" class="feature-card">
                <div>
                    <div class="card-icon">⚙️</div>
                    <div class="card-title">Configurações</div>
                    <div class="card-description">Ajuste as configurações do addon.</div>
                </div>
                <div class="card-footer">Acessar →</div>
            </a>

            <a href="?_route=backup" class="feature-card">
                <div>
                    <div class="card-icon">💾</div>
                    <div class="card-title">Backup</div>
                    <div class="card-description">Faça backup dos seus dados.</div>
                </div>
                <div class="card-footer">Acessar →</div>
            </a>
        </div>
    </div>
</body>
</html>
