<?php
/**
 * Controller - Componente BACKUP
 * Processa backup e restore de CTOs
 */

$component_base = dirname(__FILE__);
$backup_message = '';
$backup_error = '';

// Carregar banco de dados
$db_init = $component_base . '/../../database/index.php';
if (file_exists($db_init)) {
    require_once $db_init;
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'backup' && isset($db_linker)) {
        // GERAR BACKUP
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = 'backup_ctos_' . $timestamp . '.json';
            
            // Buscar todas as CTOs
            $sql = "SELECT * FROM mp_caixa ORDER BY id";
            $result = $db_linker->query($sql);
            
            if (!$result) {
                throw new Exception('Erro ao buscar CTOs: ' . $db_linker->error());
            }
            
            $ctos = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $ctos[] = $row;
            }
            
            // Criar JSON
            $backup_data = array(
                'data_backup' => date('Y-m-d H:i:s'),
                'versao' => '1.0',
                'total_ctos' => count($ctos),
                'ctos' => $ctos
            );
            
            $json_content = json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            // Preparar para download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($json_content));
            
            echo $json_content;
            exit;
            
        } catch (Exception $e) {
            $backup_error = 'Erro ao gerar backup: ' . $e->getMessage();
        }
    }
    elseif ($_POST['action'] === 'restore' && isset($_FILES['backup_file'])) {
        // RESTAURAR BACKUP
        try {
            $file = $_FILES['backup_file'];
            
            // Validar arquivo
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Erro no upload do arquivo');
            }
            
            if (!in_array($file['type'], ['application/json', 'text/plain'])) {
                throw new Exception('Tipo de arquivo inválido. Use JSON.');
            }
            
            // Ler conteúdo do arquivo
            $content = file_get_contents($file['tmp_name']);
            $data = json_decode($content, true);
            
            if (!$data || !isset($data['ctos'])) {
                throw new Exception('Arquivo de backup inválido');
            }
            
            // Iniciar transação
            $db_linker->query('START TRANSACTION');
            
            // Limpar CTOs existentes (opcional - comentar se quiser manter)
            // $db_linker->query('DELETE FROM mp_caixa');
            
            // Inserir/atualizar CTOs
            $inserted = 0;
            $updated = 0;
            
            foreach ($data['ctos'] as $cto) {
                $id = intval($cto['id']);
                $nome = $db_linker->escape($cto['nome']);
                $tipo = $db_linker->escape($cto['tipo'] ?? '');
                $endereco = $db_linker->escape($cto['endereco'] ?? '');
                $latitude = $db_linker->escape($cto['latitude'] ?? '');
                $longitude = $db_linker->escape($cto['longitude'] ?? '');
                $capacidade = intval($cto['capacidade'] ?? 0);
                $sinal = $db_linker->escape($cto['sinal'] ?? '');
                $olt = $db_linker->escape($cto['olt'] ?? '');
                $fsp = $db_linker->escape($cto['fsp'] ?? '');
                
                // Verificar se CTO já existe
                $check = $db_linker->query("SELECT id FROM mp_caixa WHERE id = $id");
                
                if (mysqli_num_rows($check) > 0) {
                    // Atualizar
                    $update_sql = "UPDATE mp_caixa SET 
                        nome = '$nome',
                        tipo = '$tipo',
                        endereco = '$endereco',
                        latitude = '$latitude',
                        longitude = '$longitude',
                        capacidade = $capacidade,
                        sinal = '$sinal',
                        olt = '$olt',
                        fsp = '$fsp'
                        WHERE id = $id";
                    
                    if ($db_linker->query($update_sql)) {
                        $updated++;
                    }
                } else {
                    // Inserir
                    $insert_sql = "INSERT INTO mp_caixa (id, nome, tipo, endereco, latitude, longitude, capacidade, sinal, olt, fsp) 
                        VALUES ($id, '$nome', '$tipo', '$endereco', '$latitude', '$longitude', $capacidade, '$sinal', '$olt', '$fsp')";
                    
                    if ($db_linker->query($insert_sql)) {
                        $inserted++;
                    }
                }
            }
            
            // Confirmar transação
            $db_linker->query('COMMIT');
            
            $_SESSION['mensagem_sucesso'] = 'Backup restaurado com sucesso! (' . $inserted . ' inseridas, ' . $updated . ' atualizadas)';
            header('Location: ?_route=backup');
            exit;
            
        } catch (Exception $e) {
            $db_linker->query('ROLLBACK');
            $backup_error = 'Erro ao restaurar backup: ' . $e->getMessage();
        }
    }
}

// Renderizar a view
require_once __DIR__ . '/backup.view.php';
