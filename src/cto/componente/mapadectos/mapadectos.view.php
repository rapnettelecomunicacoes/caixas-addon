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

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
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
            padding: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.6s ease-out;
        }

        .controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .filter-btn:hover {
            border-color: #667eea;
        }

        #map {
            width: 100%;
            height: 600px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .stat-card h3 {
            font-size: 2em;
            margin-bottom: 5px;
        }

        .stat-card p {
            opacity: 0.9;
            font-size: 0.9em;
        }

        .info-popup {
            background: white;
            padding: 15px;
            border-radius: 8px;
            max-width: 300px;
        }

        .info-popup h3 {
            color: #667eea;
            margin-bottom: 10px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 8px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
            font-size: 0.9em;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-row strong {
            color: #333;
        }

        .info-row span {
            color: #666;
        }

        .progress-bar {
            height: 8px;
            background: #eee;
            border-radius: 4px;
            margin: 8px 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #f59e0b);
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

            #map {
                height: 400px;
            }

            .controls {
                flex-direction: column;
            }

            .filter-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🌐 Gerenciador FTTH</h1>
            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <a href="?_route=inicio" class="btn-voltar">← Voltar à Listagem</a>
                <a href="?_route=painel" class="btn-voltar" style="background: rgba(255, 107, 107, 0.2); border-color: #ff6b6b;">🏠 Painel Principal</a>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Estatísticas -->
            <div class="stats">
                <div class="stat-card">
                    <h3 id="totalCtos">0</h3>
                    <p>CTOs Cadastradas</p>
                </div>
                <div class="stat-card">
                    <h3 id="totalClientes">0</h3>
                    <p>Clientes Atribuídos</p>
                </div>
                <div class="stat-card">
                    <h3 id="clientesOnline">0</h3>
                    <p>Clientes Online</p>
                </div>
                <div class="stat-card">
                    <h3 id="clientesOffline">0</h3>
                    <p>Clientes Offline</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="controls">
                <button class="filter-btn active" data-filter="todos">Todas as CTOs</button>
                <button class="filter-btn" data-filter="comclientes">Com Clientes</button>
                <button class="filter-btn" data-filter="semclientes">Sem Clientes</button>
            </div>

            <!-- Mapa -->
            <div id="map"></div>
        </div>
    </div>

    <!-- Google Maps API -->
    <?php
    require_once dirname(__FILE__) . '/../../config/api.php';
    $api_key = getGoogleMapsApiKey();
    ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($api_key); ?>"></script>

    <script>
        // Dados das CTOs
        const ctosData = <?php echo $ctos_json; ?>;
        let mapa = null;
        let marcadores = [];
        let filtroAtual = 'todos';

        // Inicializar o mapa
        function initializeMap() {
            // Centro padrão (Brasil)
            const centro = { lat: -10.5, lng: -51.9 };

            mapa = new google.maps.Map(document.getElementById('map'), {
                zoom: 4,
                center: centro,
                mapTypeControl: true,
                fullscreenControl: true,
                streetViewControl: true,
                scrollwheel: true,
                zoomControl: true,
                gestureHandling: 'auto'
            });

            // Adicionar marcadores para cada CTO
            adicionarMarcadores();

            // Atualizar estatísticas
            atualizarEstatisticas();
        }

        // Adicionar marcadores no mapa
        function adicionarMarcadores() {
            // Limpar marcadores antigos
            marcadores.forEach(marker => marker.setMap(null));
            marcadores = [];

            let totalClientesVisiveis = 0;
            let totalOnlineVisiveis = 0;
            let totalOfflineVisiveis = 0;
            let infoWindowAberta = null; // Controlar popup aberto

            ctosData.forEach(cto => {
                // Verificar filtro
                if (filtroAtual === 'comclientes' && cto.total_clientes === 0) {
                    return;
                }
                if (filtroAtual === 'semclientes' && cto.total_clientes > 0) {
                    return;
                }

                // Definir cor do marcador baseado em clientes online
                let cor = '#667eea'; // Padrão
                if (cto.total_clientes === 0) {
                    cor = '#9ca3af'; // Cinza se sem clientes
                } else if (cto.clientes_online > 0) {
                    cor = '#10b981'; // Verde se tem online
                } else {
                    cor = '#ef4444'; // Vermelho se todos offline
                }

                // Criar SVG para o marcador
                const svgMarker = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 60" width="40" height="60">
                    <path d="M20 0C8.95 0 0 8.95 0 20c0 11.05 20 40 20 40s20-28.95 20-40C40 8.95 31.05 0 20 0z" fill="${cor}"/>
                    <circle cx="20" cy="20" r="8" fill="white"/>
                </svg>`;

                const marker = new google.maps.Marker({
                    position: { lat: cto.latitude, lng: cto.longitude },
                    map: mapa,
                    title: cto.nome,
                    icon: {
                        url: 'data:image/svg+xml;base64,' + btoa(svgMarker),
                        scaledSize: new google.maps.Size(40, 60),
                        anchor: new google.maps.Point(20, 60)
                    }
                });

                // Criar conteúdo do popup com design moderno
                const infoContent = `
                    <div style="width: 500px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 0; overflow: hidden; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; color: white;">
                            <h3 style="margin: 0 0 8px 0; font-size: 1.6em; font-weight: 700; letter-spacing: -0.5px;">${cto.nome}</h3>
                            <p style="margin: 0; font-size: 0.95em; opacity: 0.95;">CTO - Caixa de Terminação Óptica</p>
                        </div>
                        
                        <div style="padding: 20px; background: white;">
                            <!-- Endereço -->
                            <div style="margin-bottom: 16px;">
                                <div style="font-size: 0.85em; font-weight: 600; color: #667eea; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">📍 Endereço</div>
                                <div style="font-size: 1em; color: #444; line-height: 1.4;">${cto.endereco}</div>
                            </div>
                            
                            <!-- Informações Técnicas -->
                            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.95em;">
                                    <div>
                                        <div style="font-weight: 600; color: #667eea; font-size: 0.85em;">Tipo</div>
                                        <div style="color: #333; font-size: 1.05em;">${cto.tipo || 'N/A'}</div>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #667eea; font-size: 0.85em;">Sinal</div>
                                        <div style="color: #333; font-size: 1.05em;">${cto.sinal || 'N/A'}</div>
                                    </div>
                                    <div style="grid-column: 1 / -1;">
                                        <div style="font-weight: 600; color: #667eea; font-size: 0.85em;">OLT</div>
                                        <div style="color: #333; font-size: 1.05em;">${cto.olt || 'N/A'}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Clientes Atribuídos -->
                            <div style="margin-bottom: 16px;">
                                <div style="font-size: 0.85em; font-weight: 600; color: #667eea; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">👥 Clientes Atribuídos</div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                                    <div style="background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); border-left: 3px solid #667eea; border-radius: 6px; padding: 12px; text-align: center;">
                                        <div style="font-size: 1.6em; font-weight: 700; color: #667eea;">${cto.total_clientes}</div>
                                        <div style="font-size: 0.85em; color: #666; margin-top: 4px;">Total</div>
                                    </div>
                                    <div style="background: #d1fae5; border-left: 3px solid #10b981; border-radius: 6px; padding: 12px; text-align: center;">
                                        <div style="font-size: 1.6em; font-weight: 700; color: #059669;">🟢 ${cto.clientes_online}</div>
                                        <div style="font-size: 0.85em; color: #666; margin-top: 4px;">Online</div>
                                    </div>
                                    <div style="background: #fee2e2; border-left: 3px solid #ef4444; border-radius: 6px; padding: 12px; text-align: center;">
                                        <div style="font-size: 1.6em; font-weight: 700; color: #dc2626;">🔴 ${cto.clientes_offline}</div>
                                        <div style="font-size: 0.85em; color: #666; margin-top: 4px;">Offline</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Lista de Clientes -->
                            ${cto.clientes && cto.clientes.length > 0 ? `
                            <div style="margin-bottom: 16px; background: #f8fafc; border-radius: 8px; padding: 12px;">
                                <div style="font-size: 0.85em; font-weight: 600; color: #667eea; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">📋 Lista de Clientes</div>
                                <div style="max-height: 300px; overflow-y: auto; font-size: 0.95em;">
                                    ${cto.clientes.map(cliente => `
                                        <div style="padding: 8px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; margin-bottom: 4px;">
                                            <span style="color: #333; flex: 1;">
                                                ${cliente.status === 'online' ? '🟢' : '🔴'} 
                                                <strong>${cliente.nome}</strong>
                                            </span>
                                            <span style="font-size: 0.85em; color: #999; margin-left: 8px;">${cliente.login}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                            ` : `
                            <div style="margin-bottom: 16px; background: #fef3c7; border-radius: 8px; padding: 12px; text-align: center; color: #92400e; font-size: 0.95em;">
                                ⚠️ Nenhum cliente atribuído
                            </div>
                            `}
                            
                            <!-- Capacidade de Portas -->
                            <div style="margin-bottom: 16px;">
                                <div style="font-size: 0.85em; font-weight: 600; color: #667eea; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">🔌 Capacidade de Portas</div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span style="color: #333; font-size: 1em; font-weight: 600;">${cto.portas_utilizadas}/${cto.capacidade} portas</span>
                                    <span style="color: #10b981; font-size: 0.95em; font-weight: 600;">${cto.portas_livres} livres</span>
                                </div>
                                <div style="background: #e0e7ff; border-radius: 8px; height: 8px; overflow: hidden;">
                                    <div style="height: 100%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); width: ${Math.min((cto.portas_utilizadas / cto.capacidade) * 100, 100)}%; border-radius: 8px; transition: width 0.3s ease;"></div>
                                </div>
                            </div>
                            
                            <!-- Botão de Edição -->
                            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                                <a href="?_route=editar&id=${cto.id}" style="display: block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 1em; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(102, 126, 234, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(102, 126, 234, 0.3)'">
                                    ✎ Editar CTO
                                </a>
                            </div>
                        </div>
                    </div>
                `;

                const infoWindow = new google.maps.InfoWindow({
                    content: infoContent,
                    maxWidth: 500
                });

                marker.addListener('click', () => {
                    // Fechar popup anterior se existir
                    if (infoWindowAberta) {
                        infoWindowAberta.close();
                    }
                    // Abrir novo popup
                    infoWindow.open(mapa, marker);
                    infoWindowAberta = infoWindow;
                });

                marcadores.push(marker);

                // Contar para estatísticas
                totalClientesVisiveis += parseInt(cto.total_clientes);
                totalOnlineVisiveis += parseInt(cto.clientes_online);
                totalOfflineVisiveis += parseInt(cto.clientes_offline);
            });

            // Ajustar view para mostrar todos os marcadores
            if (marcadores.length > 0) {
                const bounds = new google.maps.LatLngBounds();
                marcadores.forEach(marker => {
                    bounds.extend(marker.getPosition());
                });
                mapa.fitBounds(bounds);
            }

            // Atualizar contadores visíveis
            document.getElementById('totalCtos').textContent = marcadores.length;
            document.getElementById('totalClientes').textContent = totalClientesVisiveis;
            document.getElementById('clientesOnline').textContent = totalOnlineVisiveis;
            document.getElementById('clientesOffline').textContent = totalOfflineVisiveis;
        }

        // Atualizar estatísticas gerais
        function atualizarEstatisticas() {
            let totalClientes = 0;
            let totalOnline = 0;
            let totalOffline = 0;

            ctosData.forEach(cto => {
                totalClientes += parseInt(cto.total_clientes);
                totalOnline += parseInt(cto.clientes_online);
                totalOffline += parseInt(cto.clientes_offline);
            });

            document.getElementById('totalCtos').textContent = ctosData.length;
            document.getElementById('totalClientes').textContent = totalClientes;
            document.getElementById('clientesOnline').textContent = totalOnline;
            document.getElementById('clientesOffline').textContent = totalOffline;
        }

        // Configurar filtros
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                filtroAtual = btn.getAttribute('data-filter');
                adicionarMarcadores();
            });
        });

        // Inicializar ao carregar
        // Função para recarregar dados em tempo real
        function atualizarDadosEmTempoReal() {
            fetch(window.location.href, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                // Extrair dados JSON da resposta
                const match = html.match(/const ctosData = (\[[sS]*?\]);/);
                if (match && match[1]) {
                    const novosDados = JSON.parse(match[1]);
                    
                    // Verificar se há mudanças
                    if (JSON.stringify(ctosData) !== JSON.stringify(novosDados)) {
                        // Atualizar dados
                        while (ctosData.length > 0) ctosData.pop();
                        ctosData.push(...novosDados);
                        
                        // Limpar marcadores antigos
                        marcadores.forEach(marker => marker.setMap(null));
                        marcadores = [];
                        
                        // Re-renderizar mapa
                        adicionarMarcadores();
                        atualizarEstatisticas();
                        
                        console.log('✅ Mapa atualizado em tempo real');
                    }
                }
            })
            .catch(error => console.error('Erro ao atualizar:', error));
        }
        
        // Atualizar a cada 10 segundos
        setInterval(atualizarDadosEmTempoReal, 10000);
        window.addEventListener('load', initializeMap);
    </script>
</body>
</html>
