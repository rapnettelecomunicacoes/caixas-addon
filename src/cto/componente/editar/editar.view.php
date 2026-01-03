<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <title>Editar CTO - GERENCIADOR FTTH</title>
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group label {
            display: block;
            color: #333;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1em;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

        .btn-delete {
            background: #ef4444;
            color: white;
            flex: 1;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .btn-cancel {
            background: #e5e7eb;
            color: #333;
            flex: 1;
        }

        .btn-cancel:hover {
            background: #d1d5db;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state h2 {
            color: #666;
            margin-bottom: 10px;
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

            .form-row {
                grid-template-columns: 1fr;
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
            <h1>✏️ Editar CTO</h1>
        </div>

        <a href="?_route=inicio" class="btn-voltar">← Voltar à Listagem</a>
        <a href="?" style="margin-left: 10px; background: rgba(255, 255, 255, 0.2); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; transition: all 0.3s ease; border: 2px solid white; display: inline-block;" class="btn-voltar">🏠 Ir ao Painel</a>

        <!-- Content -->
        <div class="content">
            <?php if ($success_message): ?>
                <div style="background: #d4edda; border-left: 4px solid #28a745; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <strong>✅ Sucesso:</strong> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div style="background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <strong>❌ Erro:</strong> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Debug Info -->
            <?php if (isset($_GET['debug'])): ?>
                <div style="background: #f0f0f0; border: 1px solid #ccc; padding: 10px; margin-bottom: 20px; border-radius: 4px; font-size: 12px;">
                    <strong>Debug:</strong> Total de clientes carregados: <?php echo count($todos_clientes); ?><br>
                    CTO Selecionada ID: <?php echo $cto_selecionada['id'] ?? 'N/A'; ?><br>
                    Clientes na CTO: <?php echo count($cto_clientes); ?>
                </div>
            <?php endif; ?>

            <?php if ($cto_selecionada): ?>
                <div class="info-box">
                    <strong>ℹ️ Informação:</strong> Edite os dados da CTO abaixo.
                </div>

                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome">Nome/Identificação:</label>
                            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($cto_selecionada['nome']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="tipo">Tipo de CTO:</label>
                            <input type="text" id="tipo" name="tipo" value="<?php echo htmlspecialchars($cto_selecionada['tipo']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="localizacao">Localização/Endereço:</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="localizacao" name="localizacao" value="<?php echo htmlspecialchars($cto_selecionada['localizacao']); ?>" style="flex: 1;">
                            <button type="button" id="btnMapa" class="btn" style="padding: 10px 20px; background: #667eea; color: white; border: none; cursor: pointer; border-radius: 8px;">🗺️ Selecionar no Mapa</button>
                        </div>
                    </div>

                    <!-- Modal do Mapa -->
                    <div id="mapaModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000;">
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); width: 90%; max-width: 900px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;">
                            <div style="padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h3 style="margin: 0;">Selecionar Localização no Mapa</h3>
                                    <small style="color: #666;">Clique no mapa ou arraste o marcador para selecionar a localização</small>
                                </div>
                                <button type="button" id="fecharMapa" style="background: none; border: none; font-size: 1.5em; cursor: pointer;">✕</button>
                            </div>
                            <div id="googleMap" style="flex: 1; min-height: 400px; position: relative;"></div>
                            <!-- Indicador de Coordenadas Selecionadas -->
                            <div id="coordenadasSelecionadas" style="position: absolute; bottom: 70px; left: 50%; transform: translateX(-50%); background: #667eea; color: white; padding: 10px 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.2); z-index: 10; display: none; font-size: 0.9em;">
                                📍 <span id="textoCoordenadas"></span>
                            </div>
                            <div style="padding: 20px; border-top: 1px solid #eee; display: flex; gap: 10px; justify-content: flex-end;">
                                <button type="button" id="confirmarMapa" class="btn btn-submit" style="margin: 0; padding: 10px 20px;">✓ Confirmar</button>
                                <button type="button" id="cancelarMapa" class="btn btn-cancel" style="margin: 0; padding: 10px 20px;">Cancelar</button>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="latitude">Latitude:</label>
                            <input type="text" id="latitude" name="latitude" value="<?php echo htmlspecialchars($cto_selecionada['latitude']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="longitude">Longitude:</label>
                            <input type="text" id="longitude" name="longitude" value="<?php echo htmlspecialchars($cto_selecionada['longitude']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="portas">Capacidade (Portas):</label>
                            <input type="number" id="portas" name="portas" value="<?php echo htmlspecialchars($cto_selecionada['portas']); ?>">
                            <div style="margin-top: 8px; padding: 10px; background: #f0f0f0; border-radius: 6px; border-left: 4px solid #667eea;">
                                <small><strong>Utilizadas:</strong> 
                                    <span style="color: #dc3545; font-weight: bold;"><?php echo htmlspecialchars($cto_selecionada['portas_utilizadas'] ?? 0); ?></span>
                                    / 
                                    <span style="color: #333;"><?php echo htmlspecialchars($cto_selecionada['portas']); ?></span>
                                </small><br>
                                <small><strong>Livres:</strong> 
                                    <span style="color: #4CAF50; font-weight: bold;"><?php echo htmlspecialchars($cto_selecionada['portas_livres'] ?? 0); ?></span>
                                </small>
                                <!-- Barra de Progresso -->
                                <div style="margin-top: 8px; height: 6px; background: #ddd; border-radius: 3px; overflow: hidden;">
                                    <?php 
                                        $percentual = ($cto_selecionada['portas'] > 0) ? (($cto_selecionada['portas_utilizadas'] ?? 0) / $cto_selecionada['portas']) * 100 : 0;
                                        $cor = $percentual < 50 ? '#4CAF50' : ($percentual < 80 ? '#FFC107' : '#dc3545');
                                    ?>
                                    <div style="height: 100%; width: <?php echo $percentual; ?>%; background: <?php echo $cor; ?>;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="sinal">Sinal:</label>
                            <input type="text" id="sinal" name="sinal" value="<?php echo htmlspecialchars($cto_selecionada['sinal']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="olt">OLT:</label>
                            <input type="text" id="olt" name="olt" value="<?php echo htmlspecialchars($cto_selecionada['olt']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="fsp">FSP:</label>
                            <input type="text" id="fsp" name="fsp" value="<?php echo htmlspecialchars($cto_selecionada['fsp']); ?>">
                        </div>
                    </div>

                    <!-- Botões de Ação para Formulário da CTO -->
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e0e0e0; display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="submit" name="salvar_cto" value="1" class="btn btn-submit" style="background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-size: 1em; font-weight: 600; box-shadow: 0 2px 6px rgba(76, 175, 80, 0.3);">
                            💾 Salvar Alterações da CTO
                        </button>
                        <a href="?_route=inicio" class="btn btn-cancel" style="background: #f0f0f0; color: #333; padding: 12px 25px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; font-size: 1em; font-weight: 600; text-decoration: none; display: inline-block;">
                            ❌ Cancelar
                        </a>
                    </div>
                </form>

                    <!-- Seção de Atribuição de Clientes - Nova Interface -->
                <div style="margin-top: 40px; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h2 style="color: #333; margin: 0 0 20px 0; padding-bottom: 15px; border-bottom: 2px solid #667eea;">👥 Gerenciar Clientes</h2>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                        
                        <!-- Coluna 1: Lista de Clientes Disponíveis -->
                        <div>
                            <h3 style="color: #667eea; margin-top: 0; font-size: 1.1em;">📋 Clientes Disponíveis</h3>
                            
                            <?php if (!empty($todos_clientes)): ?>
                                <!-- Campo de Busca -->
                                <div style="margin-bottom: 15px;">
                                    <input type="text" id="buscar_cliente" placeholder="🔍 Buscar cliente..." 
                                           style="width: 100%; padding: 10px; border: 2px solid #667eea; border-radius: 8px; font-size: 0.95em;">
                                </div>
                                
                                <!-- Lista de Clientes -->
                                <div id="lista_clientes" style="max-height: 450px; overflow-y: auto; border: 2px solid #e0e0e0; border-radius: 8px; background: #fafafa;">
                                    <?php foreach ($todos_clientes as $cliente): ?>
                                        <?php if (!$cliente['atribuido']): ?>
                                            <div class="cliente-item" data-nome="<?php echo strtolower($cliente['nome']); ?>" data-login="<?php echo strtolower($cliente['login']); ?>"
                                                 style="padding: 12px 15px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; transition: background 0.2s;">
                                                <div style="flex: 1; min-width: 150px;">
                                                    <strong style="color: #333;"><?php echo htmlspecialchars($cliente['nome']); ?></strong><br>
                                                    <small style="color: #999;"><?php echo htmlspecialchars($cliente['login']); ?></small>
                                                </div>
                                                <form method="POST" action="" style="display: inline; flex-shrink: 0;">
                                                    <input type="hidden" name="cliente_id_atribuir" value="<?php echo intval($cliente['id']); ?>">
                                                    <button type="submit" class="btn" style="background: #4CAF50; color: white; border: none; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 0.85em; white-space: nowrap;">➕ Adicionar</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div style="background: #fff3cd; border: 2px solid #ffc107; color: #856404; padding: 15px; border-radius: 8px; text-align: center;">
                                    <strong>⚠️ Nenhum cliente ativo disponível</strong>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Coluna 2: Clientes Atribuídos -->
                        <div>
                            <h3 style="color: #667eea; margin-top: 0; font-size: 1.1em;">✓ Clientes Atribuídos</h3>
                            
                            <?php if (!empty($cto_clientes)): ?>
                                <div style="max-height: 450px; overflow-y: auto; border: 2px solid #4CAF50; border-radius: 8px; background: #f1f8f4;">
                                    <?php foreach ($cto_clientes as $cliente): ?>
                                        <div style="padding: 12px 15px; border-bottom: 1px solid #c8e6c9; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;">
                                            <div style="flex: 1; min-width: 150px;">
                                                <strong style="color: #1b5e20;"><?php echo htmlspecialchars($cliente['nome']); ?></strong><br>
                                                <small style="color: #558b2f;"><?php echo htmlspecialchars($cliente['login']); ?></small><br>
                                                <small style="color: #999;">
                                                    <?php echo ($cliente['status'] === 'online' ? '🟢 Online' : '🔴 Offline'); ?>
                                                </small>
                                            </div>
                                            <form method="POST" action="" style="display: inline; flex-shrink: 0;">
                                                <input type="hidden" name="cliente_id_remover" value="<?php echo intval($cliente['id']); ?>">
                                                <button type="submit" onclick="return confirm('Remover este cliente da CTO?')" class="btn" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 0.85em; white-space: nowrap;">❌ Remover</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div style="margin-top: 12px; padding: 12px; background: #e8f5e9; border-radius: 6px; border-left: 4px solid #4CAF50;">
                                    <small>
                                        <strong>Total: <?php echo count($cto_clientes); ?> cliente(s)</strong> | 
                                        <strong>Portas: <?php echo $cto_selecionada['portas_utilizadas']; ?>/<?php echo $cto_selecionada['portas']; ?></strong>
                                    </small>
                                </div>
                            <?php else: ?>
                                <div style="background: #e3f2fd; border: 2px solid #2196F3; color: #1565c0; padding: 15px; border-radius: 8px; text-align: center;">
                                    <strong>ℹ️ Nenhum cliente atribuído</strong>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                </div>

                <!-- JavaScript para Busca -->
                <script>
                document.getElementById('buscar_cliente').addEventListener('keyup', function() {
                    const termo = this.value.toLowerCase();
                    const clientes = document.querySelectorAll('.cliente-item');
                    
                    clientes.forEach(cliente => {
                        const nome = cliente.getAttribute('data-nome');
                        const login = cliente.getAttribute('data-login');
                        
                        if (nome.includes(termo) || login.includes(termo)) {
                            cliente.style.display = 'flex';
                        } else {
                            cliente.style.display = 'none';
                        }
                    });
                });
                </script>

            <?php else: ?>
                <div class="info-box">
                    <strong>ℹ️ Informação:</strong> Selecione uma CTO para editar seus dados ou deletá-la.
                </div>

                <div class="empty-state">
                    <h2>Nenhuma CTO selecionada</h2>
                    <p>Volte à listagem e selecione uma CTO para editar</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Google Maps API -->
    <?php
    require_once dirname(__FILE__) . '/../../config/api.php';
    $api_key = getGoogleMapsApiKey();
    if (empty($api_key)) {
        echo '<div style="position: fixed; top: 20px; right: 20px; background: #fee2e2; color: #7f1d1d; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444; z-index: 999;">';
        echo '<strong>⚠️ Aviso:</strong> API do Google Maps não configurada. <a href="?_route=configurar" style="color: #7f1d1d; font-weight: bold;">Configurar agora</a>';
        echo '</div>';
    }
    ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($api_key); ?>"></script>
    
    <script>
        // Variables para o mapa
        let mapa = null;
        let marcador = null;
        let latitudeSelecionada = null;
        let longitudeSelecionada = null;

        const modalMapa = document.getElementById('mapaModal');
        const btnMapa = document.getElementById('btnMapa');
        const fecharMapa = document.getElementById('fecharMapa');
        const confirmarMapa = document.getElementById('confirmarMapa');
        const cancelarMapa = document.getElementById('cancelarMapa');
        const inputLocalizacao = document.getElementById('localizacao');
        const inputLatitude = document.getElementById('latitude');
        const inputLongitude = document.getElementById('longitude');

        // Abrir modal do mapa
        if (btnMapa) {
            btnMapa.addEventListener('click', function(e) {
                e.preventDefault();
                modalMapa.style.display = 'flex';
                setTimeout(initializeMap, 300);
            });
        }

        // Função para atualizar o indicador de coordenadas
        function atualizarIndicadorCoordenadas() {
            const elemento = document.getElementById('coordenadasSelecionadas');
            const texto = document.getElementById('textoCoordenadas');
            if (latitudeSelecionada !== null && longitudeSelecionada !== null) {
                texto.textContent = latitudeSelecionada.toFixed(6) + ', ' + longitudeSelecionada.toFixed(6);
                elemento.style.display = 'block';
            }
        }

        // Fechar modal
        if (fecharMapa) {
            fecharMapa.addEventListener('click', function() {
                modalMapa.style.display = 'none';
            });
        }

        if (cancelarMapa) {
            cancelarMapa.addEventListener('click', function() {
                modalMapa.style.display = 'none';
            });
        }

        // Confirmar localização
        if (confirmarMapa) {
            confirmarMapa.addEventListener('click', function() {
                if (latitudeSelecionada !== null && longitudeSelecionada !== null) {
                    // Preencher latitude e longitude imediatamente
                    inputLatitude.value = latitudeSelecionada.toFixed(6);
                    inputLongitude.value = longitudeSelecionada.toFixed(6);
                    
                    // Geocodificar para obter endereço e atualizar localização
                    const geocoder = new google.maps.Geocoder();
                    geocoder.geocode({
                        location: { lat: latitudeSelecionada, lng: longitudeSelecionada }
                    }, function(results, status) {
                        if (status === 'OK' && results[0]) {
                            inputLocalizacao.value = results[0].formatted_address;
                        } else {
                            // Se não conseguir geocodificar, usar as coordenadas
                            inputLocalizacao.value = latitudeSelecionada.toFixed(6) + ', ' + longitudeSelecionada.toFixed(6);
                        }
                        // Fechar modal após preencher
                        modalMapa.style.display = 'none';
                    });
                } else {
                    alert('⚠️ Selecione uma localização no mapa!');
                }
            });
        }

        // Fechar ao clicar fora
        modalMapa.addEventListener('click', function(e) {
            if (e.target === modalMapa) {
                modalMapa.style.display = 'none';
            }
        });

        // Inicializar o mapa
        function initializeMap() {
            const lat = parseFloat(inputLatitude.value) || -5.5;
            const lng = parseFloat(inputLongitude.value) || -35.2;

            const mapElement = document.getElementById('googleMap');
            
            mapa = new google.maps.Map(mapElement, {
                zoom: 15,
                center: { lat: lat, lng: lng },
                mapTypeControl: true,
                fullscreenControl: true,
                streetViewControl: true,
                scrollwheel: true,
                zoomControl: true,
                gestureHandling: 'auto'
            });

            // Criar marcador inicial
            marcador = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: mapa,
                draggable: true,
                animation: google.maps.Animation.DROP
            });

            latitudeSelecionada = lat;
            longitudeSelecionada = lng;

            // Evento ao arrastar o marcador
            marcador.addListener('dragend', function() {
                latitudeSelecionada = marcador.getPosition().lat();
                longitudeSelecionada = marcador.getPosition().lng();
                atualizarIndicadorCoordenadas();
            });

            // Evento ao clicar no mapa
            mapa.addListener('click', function(event) {
                latitudeSelecionada = event.latLng.lat();
                longitudeSelecionada = event.latLng.lng();
                marcador.setPosition(event.latLng);
                atualizarIndicadorCoordenadas();
            });

            // Buscar endereço ao digitar
            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.placeholder = 'Buscar endereço...';
            searchInput.style.cssText = 'width: 250px; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;';
            
            const searchButton = document.createElement('button');
            searchButton.type = 'button';
            searchButton.textContent = '🔍 Buscar';
            searchButton.style.cssText = 'padding: 10px 15px; margin-left: 5px; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;';

            const searchContainer = document.createElement('div');
            searchContainer.style.cssText = 'position: absolute; top: 10px; left: 10px; z-index: 5; background: white; padding: 10px; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);';
            searchContainer.appendChild(searchInput);
            searchContainer.appendChild(searchButton);
            
            mapElement.style.position = 'relative';
            mapElement.parentElement.appendChild(searchContainer);

            searchButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (searchInput.value) {
                    searchLocation(searchInput.value);
                }
            });

            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (searchInput.value) {
                        searchLocation(searchInput.value);
                    }
                }
            });
        }

        // Função para buscar endereço
        function searchLocation(address) {
            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ address: address }, function(results, status) {
                if (status === 'OK') {
                    const location = results[0].geometry.location;
                    mapa.setCenter(location);
                    mapa.setZoom(15);
                    marcador.setPosition(location);
                    latitudeSelecionada = location.lat();
                    longitudeSelecionada = location.lng();
                    atualizarIndicadorCoordenadas();
                } else {
                    alert('Endereço não encontrado: ' + status);
                }
            });
        }
    </script>
</body>
</html>
