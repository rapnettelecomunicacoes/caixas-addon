<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <title>Adicionar CTO - GERENCIADOR FTTH</title>
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
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            color: #1565c0;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
            border: 2px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

        .btn-submit {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        }

        .btn-cancel {
            background: #f0f0f0;
            color: #333;
            border: 2px solid #ddd;
        }

        .btn-cancel:hover {
            background: #e0e0e0;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        #googleMap {
            width: 100%;
            height: 400px;
            border-radius: 8px;
            margin-top: 10px;
        }

        #coordenadasSelecionadas {
            position: absolute;
            bottom: 70px;
            left: 50%;
            transform: translateX(-50%);
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 10;
            display: none;
            font-size: 0.9em;
        }

        #mapaModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        #mapaModal > div {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
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

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>➕ Adicionar Nova CTO</h1>
        </div>

        <a href="?_route=inicio" class="btn-voltar">← Voltar à Listagem</a>

        <!-- Content -->
        <div class="content">
            <?php
            // Exibir mensagens de erro/sucesso
            if (isset($_SESSION['mensagem_erro'])) {
                echo '<div style="background: #fee2e2; color: #7f1d1d; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444; margin-bottom: 20px;">';
                echo '<strong>❌ Erro:</strong> ' . htmlspecialchars($_SESSION['mensagem_erro']);
                echo '</div>';
                unset($_SESSION['mensagem_erro']);
            }
            if (isset($_SESSION['mensagem_sucesso'])) {
                echo '<div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; border-left: 4px solid #22c55e; margin-bottom: 20px;">';
                echo '<strong>✓ Sucesso:</strong> ' . htmlspecialchars($_SESSION['mensagem_sucesso']);
                echo '</div>';
                unset($_SESSION['mensagem_sucesso']);
            }
            ?>
            <div class="info-box">
                <strong>ℹ️ Informação:</strong> Preencha os dados da nova CTO abaixo.
            </div>

            <form method="POST" action="?_route=adicionar">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nome">Nome/Identificação:</label>
                        <input type="text" id="nome" name="nome" required placeholder="Ex: CTO-001">
                    </div>

                    <div class="form-group">
                        <label for="tipo">Tipo de CTO:</label>
                        <select id="tipo" name="tipo">
                            <option value="">-- Selecione --</option>
                            <option value="Fibra Óptica">Fibra Óptica</option>
                            <option value="Cobre">Cobre</option>
                            <option value="Híbrido">Híbrido</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="localizacao">Localização/Endereço:</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="localizacao" name="localizacao" placeholder="Ex: Rua Principal, 100" style="flex: 1;">
                        <button type="button" id="btnMapa" class="btn" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px;">🗺️ Selecionar no Mapa</button>
                    </div>
                </div>

                <!-- Modal do Mapa -->
                <div id="mapaModal">
                    <div>
                        <div style="padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3 style="margin: 0;">Selecionar Localização no Mapa</h3>
                                <small style="color: #666;">Clique no mapa ou arraste o marcador para selecionar a localização</small>
                            </div>
                            <button type="button" id="fecharMapa" style="background: none; border: none; font-size: 1.5em; cursor: pointer;">✕</button>
                        </div>
                        <div id="googleMap" style="flex: 1; min-height: 400px; position: relative;"></div>
                        <!-- Indicador de Coordenadas Selecionadas -->
                        <div id="coordenadasSelecionadas">
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
                        <input type="text" id="latitude" name="latitude" placeholder="Ex: -5.123456">
                    </div>

                    <div class="form-group">
                        <label for="longitude">Longitude:</label>
                        <input type="text" id="longitude" name="longitude" placeholder="Ex: -35.123456">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="portas">Capacidade (Portas):</label>
                        <input type="number" id="portas" name="portas" min="1" required placeholder="Ex: 48">
                    </div>

                    <div class="form-group">
                        <label for="sinal">Sinal:</label>
                        <input type="text" id="sinal" name="sinal" placeholder="Ex: FTTC, FTTH">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="olt">OLT:</label>
                        <input type="text" id="olt" name="olt" placeholder="Ex: OLT-01">
                    </div>

                    <div class="form-group">
                        <label for="fsp">FSP:</label>
                        <input type="text" id="fsp" name="fsp" placeholder="Ex: FSP-01">
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-submit" style="background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-size: 1em; font-weight: 600; box-shadow: 0 2px 6px rgba(76, 175, 80, 0.3);">
                        ✓ Adicionar CTO
                    </button>
                    <a href="?_route=inicio" class="btn btn-cancel" style="background: #f0f0f0; color: #333; padding: 12px 25px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; font-size: 1em; font-weight: 600; text-decoration: none; display: inline-block;">
                        ❌ Cancelar
                    </a>
                </div>
            </form>
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
                    inputLatitude.value = latitudeSelecionada.toFixed(6);
                    inputLongitude.value = longitudeSelecionada.toFixed(6);
                    
                    const geocoder = new google.maps.Geocoder();
                    geocoder.geocode({
                        location: { lat: latitudeSelecionada, lng: longitudeSelecionada }
                    }, function(results, status) {
                        if (status === 'OK' && results[0]) {
                            inputLocalizacao.value = results[0].formatted_address;
                        } else {
                            inputLocalizacao.value = latitudeSelecionada.toFixed(6) + ', ' + longitudeSelecionada.toFixed(6);
                        }
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
            const lat = -5.5;
            const lng = -35.2;
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

            marcador = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: mapa,
                draggable: true,
                animation: google.maps.Animation.DROP
            });

            latitudeSelecionada = lat;
            longitudeSelecionada = lng;
            atualizarIndicadorCoordenadas();

            marcador.addListener('dragend', function() {
                latitudeSelecionada = marcador.getPosition().lat();
                longitudeSelecionada = marcador.getPosition().lng();
                atualizarIndicadorCoordenadas();
            });

            mapa.addListener('click', function(event) {
                latitudeSelecionada = event.latLng.lat();
                longitudeSelecionada = event.latLng.lng();
                marcador.setPosition(event.latLng);
                atualizarIndicadorCoordenadas();
            });

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
