<?php
// === CONFIGURAÇÃO INICIAL ===
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error.log');

// === INICIALIZAR SESSÃO ===
session_name('mka');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === CARREGAR AUTH HANDLER ===
require_once dirname(__FILE__) . '/src/auth_handler.php';

// Se sessão está vazia, ainda assim deixar carregar o dashboard
if (!empty($_SESSION) && !AuthHandler::isAuthenticated()) {
    header("Location: ../../");
    exit();
}

// === DADOS DO ADDON ===
$addon_name = "Gerenciador FTTH";
$addon_version = "2.0";
$addon_base = dirname(__FILE__);

// Funcionalidades disponíveis
$features = [
    [
        'icon' => '📊',
        'title' => 'Componentes CTO',
        'description' => 'Gerencie todos os componentes de seus CTOs instalados na rede.',
        'link' => '?_route=inicio',
        'color' => '#667eea'
    ],
    [
        'icon' => '➕',
        'title' => 'Adicionar Componente',
        'description' => 'Registre novos componentes e CTO no sistema.',
        'link' => '?_route=adicionar',
        'color' => '#764ba2'
    ],
    [
        'icon' => '✏️',
        'title' => 'Editar Componentes',
        'description' => 'Modifique informações de componentes existentes.',
        'link' => '?_route=editar',
        'color' => '#f093fb'
    ],
    [
        'icon' => '🗺️',
        'title' => 'Mapas',
        'description' => 'Visualize a localização dos CTOs em mapas interativos.',
        'link' => '?_route=maps',
        'color' => '#4facfe'
    ],
    [
        'icon' => '👥',
        'title' => 'Mapa de Clientes',
        'description' => 'Visualize distribuição de clientes por área.',
        'link' => '?_route=mapadeclientes',
        'color' => '#43e97b'
    ],
    [
        'icon' => '��',
        'title' => 'Mapa de CTOs',
        'description' => 'Visualize todos os CTOs na rede.',
        'link' => '?_route=mapadectos',
        'color' => '#fa709a'
    ],
    [
        'icon' => '⚙️',
        'title' => 'Configurações',
        'description' => 'Configure opções do sistema e preferências.',
        'link' => '?_route=configurar',
        'color' => '#30cfd0'
    ],
    [
        'icon' => '💾',
        'title' => 'Backup',
        'description' => 'Realize backup e restaure dados do sistema.',
        'link' => '?_route=backup',
        'color' => '#a8edea'
    ]
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $addon_name; ?> v<?php echo $addon_version; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 50px;
        }

        .header h1 {
            font-size: 3em;
            margin-bottom: 10px;
            font-weight: 700;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.2);
        }

        .header p {
            font-size: 1.2em;
            opacity: 0.95;
        }

        .version {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            margin-left: 10px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            cursor: pointer;
            overflow: hidden;
            position: relative;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.25);
        }

        .feature-card a {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .feature-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
        }

        .feature-card h3 {
            color: #333;
            font-size: 1.3em;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .feature-card p {
            color: #666;
            font-size: 0.95em;
            line-height: 1.6;
        }

        .footer {
            text-align: center;
            color: rgba(255,255,255,0.8);
            padding: 20px;
            font-size: 0.9em;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📡 <?php echo $addon_name; ?><span class="version">v<?php echo $addon_version; ?></span></h1>
            <p>Sistema de Gestão de CTO e Componentes FTTH</p>
        </div>

        <div class="features-grid">
            <?php foreach ($features as $feature): ?>
                <div class="feature-card">
                    <a href="<?php echo htmlspecialchars($feature['link']); ?>">
                        <div class="feature-icon"><?php echo $feature['icon']; ?></div>
                        <h3><?php echo htmlspecialchars($feature['title']); ?></h3>
                        <p><?php echo htmlspecialchars($feature['description']); ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="footer">
            <p>Gerenciador FTTH © 2026 | Desenvolvido por Patrick Nascimento</p>
        </div>
    </div>
</body>
</html>
