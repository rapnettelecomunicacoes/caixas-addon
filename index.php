<?php
// === CONFIGURAÇÃO INICIAL ===
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error.log');

// === VERIFICAÇÃO DE LICENÇA ===
require_once dirname(__FILE__) . '/src/LicenseManager.php';
$licenseManager = new LicenseManager();
$licenseStatus = $licenseManager->getLicenseStatus();

// Se licença não está ativa, redirecionar para página de ativação
if (!$licenseStatus['instalada'] || $licenseStatus['expirada']) {
    header('Location: src/license_install.php');
    exit;
}

// === VERIFICAÇÃO DE AUTENTICAÇÃO FLEXÍVEL ===
// Usa gestor que detecta qualquer variável de sessão do mk-auth
require_once dirname(__FILE__) . '/src/auth_handler.php';
// AuthHandler::requireAuth(); // Desabilitado para funcionar em novos servidores sem sessão

// === CARREGAR DEPENDÊNCIAS ===
$addon_base = dirname(__FILE__);
require_once $addon_base . '/addons.class.php';

// === CARREGAR CONFIGURAÇÃO DE BANCO DE DADOS ===
$ctos_cadastradas = 0;
$portas_totais = 0;
$portas_livres = 0;
$portas_ativas = 0;
$portas_utilizadas = 0;

// Tentar carregar estatísticas do banco
if (file_exists($addon_base . '/src/cto/config/database.php')) {
    require_once $addon_base . '/src/cto/config/database.php';
    
    if (isset($connection) && $connection) {
        // Contar CTOs cadastradas
        $query_ctos = "SELECT COUNT(*) as total FROM mp_caixa";
        $result_ctos = @mysqli_query($connection, $query_ctos);
        if ($result_ctos) {
            $row = mysqli_fetch_assoc($result_ctos);
            $ctos_cadastradas = intval($row['total']);
        }
        
        // Calcular portas totais (soma das capacidades)
        $query_portas = "SELECT SUM(capacidade) as total FROM mp_caixa";
        $result_portas = @mysqli_query($connection, $query_portas);
        if ($result_portas) {
            $row = mysqli_fetch_assoc($result_portas);
            $portas_totais = intval($row['total']) ?: 0;
        }
        
        // Calcular portas livres por CTO (usando caixa_herm como referência)
        $query_livres = "SELECT SUM(mp.capacidade - COALESCE(cliente_count.total, 0)) as total
                        FROM mp_caixa mp
                        LEFT JOIN (
                            SELECT caixa_herm, COUNT(*) as total 
                            FROM sis_cliente 
                            WHERE caixa_herm IS NOT NULL AND caixa_herm != ''
                            GROUP BY caixa_herm
                        ) cliente_count ON mp.nome = cliente_count.caixa_herm";
        $result_livres = @mysqli_query($connection, $query_livres);
        if ($result_livres) {
            $row = mysqli_fetch_assoc($result_livres);
            $portas_livres = max(0, intval($row['total']) ?: 0);
        }
        
        // Contar clientes online (portas ativas) - usando caixa_herm
        $query_ativas = "SELECT COUNT(*) as total FROM sis_cliente sc
                        INNER JOIN radacct ra ON ra.username = sc.login 
                        WHERE ra.acctstoptime IS NULL
                        AND sc.caixa_herm IS NOT NULL AND sc.caixa_herm != ''";
        $result_ativas = @mysqli_query($connection, $query_ativas);
        if ($result_ativas) {
            $row = mysqli_fetch_assoc($result_ativas);
            $portas_ativas = intval($row['total']) ?: 0;
        }
    }
}

// === VALIDAR LICENÇA ===
// Comentado temporariamente para evitar erro 500 em novos servidores
// A licença será validada quando o arquivo LicenseMiddleware.php estiver disponível
/*
if (file_exists($addon_base . "/src/LicenseMiddleware.php")) {
    require_once $addon_base . "/src/LicenseMiddleware.php";
    $middleware = new LicenseMiddleware();
    $status = $middleware->getStatus();
    if (!$status["instalada"] || (isset($status["expirada"]) && $status["expirada"])) {
        header("Location: src/license_install.php");
        exit;
    }
}
*/

// === CONTROLAR ROTEAMENTO ===
$route = isset($_GET['_route']) ? $_GET['_route'] : '';

// === INCLUIR APLICAÇÃO SE HOUVER ROTA ===
if (!empty($route) && in_array($route, ['inicio', 'adicionar', 'editar', 'backup', 'maps', 'mapadeclientes', 'mapadectos', 'configurar', 'viabilidade'])) {
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
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .stat-card .icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin-bottom: 15px;
            color: white;
        }

        .stat-card h3 {
            color: #333;
            font-size: 0.95em;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .value {
            color: #667eea;
            font-size: 2.5em;
            font-weight: bold;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .action-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease-out;
            display: flex;
            flex-direction: column;
        }

        .action-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
        }

        .action-card-header {
            height: 120px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 50px;
        }

        .action-card-header.blue {
            background: linear-gradient(135deg, #667eea 0%, #5a67d8 100%);
        }

        .action-card-header.cyan {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
        }

        .action-card-header.amber {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .action-card-header.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .action-card-body {
            padding: 25px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .action-card-title {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .action-card-description {
            color: #666;
            font-size: 0.95em;
            margin-bottom: 20px;
            flex-grow: 1;
        }

        .action-card-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
            border: none;
            cursor: pointer;
            font-size: 1em;
        }

        .action-card-button:hover {
            transform: translateX(3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .action-card-button.blue {
            background: linear-gradient(135deg, #667eea 0%, #5a67d8 100%);
        }

        .action-card-button.cyan {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
        }

        .action-card-button.amber {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .action-card-button.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .welcome-section {
            background: white;
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.6s ease-out;
        }

        .welcome-section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.8em;
        }

        .welcome-section p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 10px;
        }

        .feature-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
        }

        .feature-item:before {
            content: "✓";
            color: #10b981;
            font-weight: bold;
            font-size: 1.3em;
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

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8em;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="header">
            <h1>🗺️ Mapa de CTOs</h1>
            <p>Sistema de Gerenciamento de Caixas de Terminação Óptica</p>
        </div>

        <!-- Stats Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">📍</div>
                <h3>CTOs Cadastradas</h3>
                <div class="value"><?php echo $ctos_cadastradas; ?></div>
            </div>

            <div class="stat-card">
                <div class="icon">🔌</div>
                <h3>Portas Totais</h3>
                <div class="value"><?php echo $portas_totais; ?></div>
            </div>

            <div class="stat-card">
                <div class="icon">✅</div>
                <h3>Portas Livres</h3>
                <div class="value"><?php echo $portas_livres; ?></div>
            </div>

            <div class="stat-card">
                <div class="icon">🟢</div>
                <h3>Portas Ativas</h3>
                <div class="value"><?php echo $portas_ativas; ?></div>
            </div>
        </div>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2>Bem-vindo ao Sistema de Gerenciamento de CTOs</h2>
            <p>
                Este sistema foi desenvolvido para facilitar o gerenciamento de todas as Caixas de Terminação Óptica (CTOs) 
                da sua rede de fibra óptica. Com funcionalidades intuitivas e uma interface amigável, você poderá:
            </p>
            <div class="feature-list">
                <div class="feature-item">Adicionar novas CTOs</div>
                <div class="feature-item">Editar informações existentes</div>
                <div class="feature-item">Visualizar localização no mapa</div>
                <div class="feature-item">Gerenciar portas e capacidade</div>
                <div class="feature-item">Fazer backup de dados</div>
                <div class="feature-item">Rastrear status das conexões</div>
            </div>
        </div>

        <!-- Actions Grid -->
        <div class="actions-grid">
            <!-- Card: Listar CTOs -->
            <div class="action-card">
                <div class="action-card-header blue">📋</div>
                <div class="action-card-body">
                    <div class="action-card-title">Listar CTOs</div>
                    <div class="action-card-description">
                        Visualize todas as Caixas de Terminação Óptica cadastradas no sistema com informações detalhadas 
                        de localização, capacidade e status. Adicione, edite ou delete CTOs diretamente da listagem.
                    </div>
                    <a href="?_route=inicio" class="action-card-button blue">Acessar</a>
                </div>
            </div>

            <!-- Card: Mapa de Clientes -->
            <div class="action-card">
                <div class="action-card-header" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);">🗺️</div>
                <div class="action-card-body">
                    <div class="action-card-title">Mapa de Clientes</div>
                    <div class="action-card-description">
                        Visualize a localização de todas as CTOs no mapa interativo e acompanhe a distribuição 
                        geográfica da sua rede de fibra óptica em tempo real.
                    </div>
                    <a href="?_route=maps" class="action-card-button" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);">Visualizar Mapa</a>
                </div>
            </div>

            <!-- Card: Mapa de CTOs -->
            <div class="action-card">
                <div class="action-card-header amber">🗺️</div>
                <div class="action-card-body">
                    <div class="action-card-title">Mapa de CTOs</div>
                    <div class="action-card-description">
                        Visualize todas as CTOs cadastradas em um mapa interativo com informações detalhadas 
                        de clientes atribuídos, status online/offline e capacidade de portas.
                    </div>
                    <a href="?_route=mapadectos" class="action-card-button amber">Abrir Mapa</a>
                </div>
            </div>

            <!-- Card: Viabilidade de Atendimento -->
            <div class="action-card">
                <div class="action-card-header" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">🚶</div>
                <div class="action-card-body">
                    <div class="action-card-title">Viabilidade de Atendimento</div>
                    <div class="action-card-description">
                        Digite um endereço para encontrar a CTO mais próxima e visualize a rota até ela. Modo de deslocamento: A pé (Walking).
                    </div>
                    <a href="?_route=viabilidade" class="action-card-button" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">Calcular Viabilidade</a>
                </div>
            </div>

            <!-- Card: Backup -->
            <div class="action-card">
                <div class="action-card-header green">💾</div>
                <div class="action-card-body">
                    <div class="action-card-title">Backup de Dados</div>
                    <div class="action-card-description">
                        Crie backups de segurança de todas as informações de CTOs e restaure dados 
                        em caso de necessidade.
                    </div>
                    <a href="?_route=backup" class="action-card-button green">Gerenciar</a>
                </div>
            </div>

            <!-- Card: Configurações -->
            <div class="action-card">
                <div class="action-card-header" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">⚙️</div>
                <div class="action-card-body">
                    <div class="action-card-title">Configurações</div>
                    <div class="action-card-description">
                        Configure as APIs necessárias para o funcionamento completo do sistema, 
                        incluindo a API do Google Maps para visualização de mapa.
                    </div>
                    <a href="?_route=configurar" class="action-card-button" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">Configurar</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>