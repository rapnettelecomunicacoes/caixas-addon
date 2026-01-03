<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <title>Mapa de CTOs - GERENCIADOR FTTH</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            overflow: hidden;
        }

        #googleMap {
            width: 100%;
            height: 100%;
        }

        .header-maps {
            position: absolute;
            top: 10px;
            left: 10px;
            right: auto;
            z-index: 10;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            max-width: 350px;
        }

        .header-maps h1 {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 10px;
        }

        .header-maps p {
            color: #666;
            font-size: 0.9em;
            margin: 0;
        }

        .controls-maps {
            position: absolute;
            bottom: 20px;
            left: 20px;
            z-index: 10;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .btn-voltar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-voltar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .filter-buttons {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 6px 12px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85em;
            font-weight: 600;
            transition: all 0.2s ease;
            color: #666;
        }

        .filter-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }

        .legend {
            position: absolute;
            bottom: 20px;
            right: 20px;
            z-index: 10;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            font-size: 0.9em;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .legend-color.online {
            background: #10b981;
        }

        .legend-color.offline {
            background: #ef4444;
        }

        .legend-color.offline {
            background: #ef4444;
        }

        .info-window-custom {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-width: 320px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .info-window-custom h3 {
            color: #1a202c;
            margin: 0;
            font-size: 1.4em;
            font-weight: 700;
            padding: 16px 16px 8px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            word-break: break-word;
        }

        .info-window-custom p {
            color: #4a5568;
            margin: 0;
            font-size: 0.9em;
            line-height: 1.6;
            padding: 0 16px;
        }

        .info-window-custom strong {
            color: #2d3748;
            font-weight: 600;
        }

        .info-item {
            padding: 10px 16px;
            border-bottom: 1px solid #edf2f7;
        }

        .info-item:last-of-type {
            border-bottom: none;
        }

        .info-item-label {
            display: block;
            color: #667eea;
            font-size: 0.85em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .info-item-value {
            color: #2d3748;
            font-size: 1.05em;
            word-break: break-word;
            line-height: 1.5;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 0.95em;
            font-weight: 600;
            margin-top: 8px;
        }

        .status-badge.online {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .status-badge.offline {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .status-details {
            padding: 12px 16px;
            background: #f7fafc;
            font-size: 1.0em;
            color: #4a5568;
            border-radius: 6px;
            margin-top: 8px;
            display: none;
            font-weight: 500;
            line-height: 1.6;
        }

        .status-details.show {
            display: block;
        }

        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 5;
            text-align: center;
        }

        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .header-maps {
                left: 5px;
                right: 5px;
                top: 5px;
                max-width: none;
            }

            .controls-maps {
                left: 10px;
                bottom: 10px;
            }

            .legend {
                bottom: 70px;
                right: 10px;
            }

            .info-window-custom {
                min-width: 280px;
            }

            .info-window-custom h3 {
                font-size: 1.1em;
            }

            .info-item {
                padding: 8px 12px;
            }

            .info-item-label {
                font-size: 0.7em;
            }

            .info-item-value {
                font-size: 0.9em;
            }
        }
        }
    </style>
</head>
<body>
    <div id="loading" class="loading">
        <div class="loading-spinner"></div>
        <p>Carregando mapa...</p>
    </div>

    <div id="googleMap"></div>

    <div class="header-maps">
        <h1>🗺️ Mapa de Clientes</h1>
        <p style="margin: 10px 0 0 0; font-size: 0.9em;">
            <strong>Total:</strong> <span id="total-clientes"><?php echo count($clientes_data); ?></span> cliente(s) | 
            <strong style="color: #10b981;">🟢 Online:</strong> <span id="total-online"><?php echo $total_online; ?></span> | 
            <strong style="color: #ef4444;">🔴 Offline:</strong> <span id="total-offline"><?php echo $total_offline; ?></span>
        </p>
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all" id="btn-todos">Todos</button>
            <button class="filter-btn" data-filter="online" id="btn-online">🟢 Online</button>
            <button class="filter-btn" data-filter="offline" id="btn-offline">🔴 Offline</button>
        </div>
    </div>

    <div class="controls-maps">
        <a href="?" class="btn-voltar">← Voltar ao Painel</a>
    </div>

    <div class="legend">
        <div class="legend-item">
            <div class="legend-color online"></div>
            <span>Online</span>
        </div>
        <div class="legend-item">
            <div class="legend-color offline"></div>
            <span>Offline</span>
        </div>
    </div>

    <!-- Google Maps API ou Fallback -->
    <?php
    // Garantir que a chave seja carregada de forma limpa
    if (empty($api_key)) {
        $api_key = 'AIzaSyAP0AsCe9LGVW1V5r0rTdKUvqVLCO-DBYQ'; // Fallback
    }
    ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($api_key); ?>&libraries=places&language=pt-BR"></script>
    
    <!-- Leaflet como fallback (Open Source) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        // Dados dos clientes
        const clientesData = <?php echo json_encode($clientes_data); ?>;

        let mapa = null;
        let marcadores = [];
        let infoWindows = [];
        let infoWindowAberta = null;  // Rastreia apenas a infoWindow aberta (otimização)
        let bounds = null;
        let usarLeaflet = false;

        // Verificar se o Google Maps foi carregado
        function checkGoogleMapsLoaded() {
            if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
                console.warn('Google Maps não foi carregado, usando Leaflet como alternativa');
                usarLeaflet = true;
                return false;
            }
            return true;
        }

        // Função para inicializar com Leaflet (fallback)
        function initializeMapWithLeaflet() {
            console.log('Inicializando mapa com Leaflet...');
            
            if (!clientesData || clientesData.length === 0) {
                document.getElementById('loading').innerHTML = '<p style="color: #666; padding: 20px;">Nenhum cliente ativo para exibir no mapa</p>';
                document.getElementById('loading').style.display = 'block';
                return;
            }

            try {
                // Centro inicial (primeira coordenada)
                const primeira = clientesData[0];
                const centro = [parseFloat(primeira.latitude), parseFloat(primeira.longitude)];

                // Criar o mapa com Leaflet
                mapa = L.map('googleMap').setView(centro, 13);

                // Adicionar tile layer (OpenStreetMap)
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(mapa);

                // Criar bounds
                const bounds = L.latLngBounds();

                // Adicionar marcadores
                clientesData.forEach((cliente) => {
                    const posicao = [parseFloat(cliente.latitude), parseFloat(cliente.longitude)];
                    bounds.extend(posicao);

                    // Criar marcador com cor baseada no status
                    const cor = cliente.status === 'online' ? '#10b981' : '#ef4444';
                    const marcador = L.circleMarker(posicao, {
                        radius: 8,
                        fillColor: cor,
                        color: '#ffffff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 1
                    }).addTo(mapa);

                    // Criar popup
                    const popupContent = createInfoWindowContent(cliente);
                    marcador.bindPopup(popupContent);

                    marcadores.push(marcador);
                });

                // Ajustar zoom para mostrar todos os marcadores
                if (clientesData.length > 1) {
                    mapa.fitBounds(bounds, { padding: [50, 50] });
                }

                console.log('Mapa Leaflet inicializado com sucesso');
                document.getElementById('loading').style.display = 'none';
            } catch (error) {
                console.error('Erro ao inicializar Leaflet:', error);
                document.getElementById('loading').innerHTML = '<p style="color: #ef4444; padding: 20px; text-align: center;"><strong>⚠️ Erro:</strong> Falha ao carregar o mapa.</p>';
                document.getElementById('loading').style.display = 'block';
            }
        }

        // Função para inicializar com Google Maps
        function initializeMapWithGoogleMaps() {
            console.log('Inicializando mapa com Google Maps...');
            
            if (!clientesData || clientesData.length === 0) {
                document.getElementById('loading').innerHTML = '<p style="color: #666; padding: 20px;">Nenhum cliente ativo para exibir no mapa</p>';
                document.getElementById('loading').style.display = 'block';
                return;
            }

            try {
                // Criar bounds para ajustar o zoom
                bounds = new google.maps.LatLngBounds();

                // Centro inicial
                const centro = {
                    lat: parseFloat(clientesData[0].latitude),
                    lng: parseFloat(clientesData[0].longitude)
                };

                // Criar o mapa
                mapa = new google.maps.Map(document.getElementById('googleMap'), {
                    zoom: 13,
                    gestureHandling: 'greedy',
                    center: centro,
                    mapTypeControl: true,
                    fullscreenControl: true,
                    streetViewControl: false,
                    zoomControl: true,
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                });

                // Adicionar marcadores (otimizado com lazy loading)
                clientesData.forEach((cliente) => {
                    const posicao = {
                        lat: parseFloat(cliente.latitude),
                        lng: parseFloat(cliente.longitude)
                    };

                    bounds.extend(posicao);

                    // Criar marcador com cor baseada no status
                    const marcador = new google.maps.Marker({
                        position: posicao,
                        map: mapa,
                        title: cliente.nome,
                        icon: getMarkerIcon(cliente.status),
                        optimized: true  // Otimização
                    });

                    // Lazy loading: criar infoWindow apenas ao clicar
                    marcador.addListener('click', () => {
                        if (infoWindowAberta) {
                            infoWindowAberta.close();
                        }
                        
                        const infoWindow = new google.maps.InfoWindow({
                            content: createInfoWindowContent(cliente),
                            maxWidth: 300
                        });
                        
                        infoWindow.open(mapa, marcador);
                        infoWindowAberta = infoWindow;
                    });

                    marcadores.push(marcador);
                });

                if (clientesData.length > 1) {
                    mapa.fitBounds(bounds);
                    setTimeout(() => {
                        if (mapa.getZoom() > 17) {
                            mapa.setZoom(16);
                        }
                    }, 200);
                } else if (clientesData.length === 1) {
                    mapa.setZoom(15);
                }

                console.log('Mapa Google Maps inicializado com sucesso');
                document.getElementById('loading').style.display = 'none';
            } catch (error) {
                console.error('Erro ao inicializar Google Maps:', error);
                console.log('Tentando usar Leaflet como fallback...');
                usarLeaflet = true;
                initializeMapWithLeaflet();
            }
        }

        // Função para inicializar o mapa
        function initializeMap() {
            // Verificar se Google Maps foi carregado
            const googleMapsOK = checkGoogleMapsLoaded();

            if (googleMapsOK) {
                initializeMapWithGoogleMaps();
            } else {
                // Usar Leaflet como fallback
                setTimeout(() => {
                    if (!usarLeaflet) {
                        initializeMapWithLeaflet();
                    }
                }, 500);
            }
        }

        // Função para criar conteúdo da janela de informação
        function createInfoWindowContent(cliente) {
            const statusClass = cliente.status === 'online' ? 'online' : 'offline';
            const statusText = cliente.status === 'online' ? '🟢 Online' : '🔴 Offline';
            
            // Formatar data/hora de offline se existir
            let offlineInfo = '';
            if (cliente.offline_time && cliente.offline_time !== 'null') {
                try {
                    const offlineDate = new Date(cliente.offline_time);
                    const formattedDate = offlineDate.toLocaleDateString('pt-BR');
                    const formattedTime = offlineDate.toLocaleTimeString('pt-BR');
                    offlineInfo = `📅 ${formattedDate} às ${formattedTime}`;
                } catch (e) {
                    offlineInfo = cliente.offline_time;
                }
            }

            return `
                <div class="info-window-custom">
                    <h3>${cliente.nome}</h3>
                    
                    <div class="info-item">
                        <span class="info-item-label">📍 Localização</span>
                        <div class="info-item-value">${cliente.endereco || 'Não informado'}</div>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-item-label">📐 Coordenadas</span>
                        <div class="info-item-value" style="font-family: monospace;">
                            ${parseFloat(cliente.latitude).toFixed(6)}, ${parseFloat(cliente.longitude).toFixed(6)}
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-item-label">👤 Login</span>
                        <div class="info-item-value">${cliente.login || 'N/A'}</div>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-item-label">🔌 Status</span>
                        <div class="status-badge ${statusClass}" style="display: inline-block; margin-top: 4px;">${statusText}</div>
                        ${cliente.status === 'offline' && offlineInfo ? `<div class="status-details show" style="margin-top: 8px; font-size: 0.8em;">⏱️ Desconectado em: ${offlineInfo}</div>` : ''}
                    </div>
                </div>
            `;
        }

        // Função para obter cor do marcador
        function getMarkerIcon(status) {
            const color = status === 'online' ? '#10b981' : '#ef4444';
            return {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 8,
                fillColor: color,
                fillOpacity: 1,
                strokeColor: '#ffffff',
                strokeWeight: 2
            };
        }

        // Variável global para armazenar filtro atual
        let currentFilter = 'all';

        // Função para filtrar marcadores
        function filterMarkers(filterType) {
            currentFilter = filterType;
            let visibleCount = 0;
            let visibleOnline = 0;
            let visibleOffline = 0;

            marcadores.forEach((marcador, index) => {
                const cliente = clientesData[index];
                let mostrar = false;

                if (filterType === 'all') {
                    mostrar = true;
                } else if (filterType === 'online') {
                    mostrar = cliente.status === 'online';
                } else if (filterType === 'offline') {
                    mostrar = cliente.status === 'offline';
                }

                if (usarLeaflet) {
                    if (mostrar) {
                        marcador.setOpacity(1);
                        visibleCount++;
                        if (cliente.status === 'online') visibleOnline++;
                        if (cliente.status === 'offline') visibleOffline++;
                    } else {
                        marcador.setOpacity(0);
                    }
                } else {
                    marcador.setVisible(mostrar);
                    if (mostrar) {
                        visibleCount++;
                        if (cliente.status === 'online') visibleOnline++;
                        if (cliente.status === 'offline') visibleOffline++;
                    }
                }
            });

            // Atualizar contadores
            document.getElementById('total-clientes').textContent = visibleCount;
            document.getElementById('total-online').textContent = visibleOnline;
            document.getElementById('total-offline').textContent = visibleOffline;

            // Atualizar botões ativos
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-filter="${filterType}"]`).classList.add('active');
        }

        // Adicionar eventos aos botões de filtro
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const filterType = this.getAttribute('data-filter');
                filterMarkers(filterType);
            });
        });

        // Inicializar quando pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(initializeMap, 500);
            });
        } else {
            window.addEventListener('load', initializeMap);
            setTimeout(initializeMap, 500);
        }
    </script>
        
        // ========== ATUALIZAÇÃO EM TEMPO REAL A CADA 10 SEGUNDOS ==========
        // Otimizações de performance
        let ultimaAtualizacao = 0;
        let aguardandoResposta = false;
        const INTERVALO_MINIMO = 20000;  // 20 segundos mínimo entre atualizações

        function atualizarClientesEmTempoReal() {
            const agora = Date.now();
            
            // Evitar requisições simultâneas
            if (aguardandoResposta) {
                return;
            }
            
            // Não fazer request muito frequente
            if (agora - ultimaAtualizacao < INTERVALO_MINIMO) {
                return;
            }
            
            aguardandoResposta = true;
            
            fetch('get_clientes_json.php?t=' + agora, {  // Cache buster
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Erro ao buscar dados');
                return response.json();
            })
            .then(novosDados => {
                // Comparação rápida por comprimento primeiro
                if (novosDados.length !== clientesData.length) {
                    console.log('✅ Número de clientes mudou, atualizando...');
                    
                    // Limpar marcadores antigos
                    marcadores.forEach(marker => marker.setMap(null));
                    marcadores = [];
                    if (infoWindowAberta) infoWindowAberta.close();
                    
                    // Atualizar dados
                    clientesData.length = 0;
                    clientesData.push(...novosDados);
                    
                    // Re-inicializar mapa apenas se necessário
                    initializeMap();
                } else {
                    // Apenas verificar se status dos clientes mudou
                    let houveMudanca = false;
                    for (let i = 0; i < novosDados.length; i++) {
                        if (novosDados[i].status !== clientesData[i].status) {
                            houveMudanca = true;
                            clientesData[i].status = novosDados[i].status;
                            
                            // Atualizar icon do marcador
                            if (marcadores[i]) {
                                marcadores[i].setIcon(getMarkerIcon(novosDados[i].status));
                            }
                        }
                    }
                    
                    if (houveMudanca) {
                        console.log('⚡ Status de clientes atualizado');
                    }
                }
                
                ultimaAtualizacao = Date.now();
            })
            .catch(error => {
                console.warn('⚠️ Erro ao atualizar clientes:', error.message);
            })
            .finally(() => {
                aguardandoResposta = false;
            });
        }
        
        // Iniciar atualização automática a cada 30 segundos
        setInterval(atualizarClientesEmTempoReal, 30000);
</body>
    </script>
</html>
