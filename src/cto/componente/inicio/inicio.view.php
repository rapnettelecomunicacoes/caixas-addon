<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <title>Listar CTOs - GERENCIADOR FTTH</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            color: white;
        }

        .header h1 {
            font-size: 2em;
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

        .section-title {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
        }

        .info-box {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table tbody tr:hover {
            background: #f8f9ff;
        }

        table tbody tr:nth-child(even) {
            background: #f5f7ff;
        }

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: #0ea5e9;
            color: white;
        }

        .btn-edit:hover {
            background: #0284c7;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .btn-add {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 12px 24px;
            margin-bottom: 20px;
            display: inline-block;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
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
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            table {
                font-size: 0.9em;
            }

            table th, table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📋 Listar CTOs</h1>
            <a href="?_route=" class="btn-voltar">← Voltar ao Painel</a>
        </div>

        <!-- Content -->
        <div class="content">
            <h2 class="section-title">Caixas de Terminação Óptica Cadastradas</h2>

            <div class="info-box">
                <strong>ℹ️ Informação:</strong> Esta página exibe todas as CTOs cadastradas no sistema. Você pode editar ou deletar registros conforme necessário.
            </div>

            <a href="?_route=adicionar" class="btn-add">+ Adicionar Nova CTO</a>
            <a href="?_route=mapadeclientes" class="btn-add" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); margin-left: 10px;">🗺️ Mapa de Clientes</a>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome/Identificação</th>
                            <th>Localização</th>
                            <th>Portas Totais</th>
                            <th>Portas Livres</th>
                            <th>Clientes</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!empty($cto_list) && is_array($cto_list) && count($cto_list) > 0):
                            foreach ($cto_list as $index => $cto):
                                $cto_id = isset($cto['id']) ? $cto['id'] : ($index + 1);
                                $cto_nome = isset($cto['nome']) ? $cto['nome'] : 'N/A';
                                $cto_local = isset($cto['localizacao']) ? $cto['localizacao'] : 'N/A';
                                $cto_portas = isset($cto['portas']) ? $cto['portas'] : 0;
                                $cto_livres = isset($cto['portas_livres']) ? $cto['portas_livres'] : 0;
                                $cto_status = isset($cto['status']) ? $cto['status'] : 'Ativo';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cto_id); ?></td>
                            <td><?php echo htmlspecialchars($cto_nome); ?></td>
                            <td><?php echo htmlspecialchars($cto_local); ?></td>
                            <td><?php echo htmlspecialchars($cto_portas); ?></td>
                            <td><?php echo htmlspecialchars($cto_livres); ?></td>
                            <td>
                                <span style="background: #f0f4ff; padding: 4px 8px; border-radius: 4px; font-weight: 600;">
                                    <?php 
                                    $total = isset($cto['total_clientes']) ? $cto['total_clientes'] : 0;
                                    $online = isset($cto['clientes_online']) ? $cto['clientes_online'] : 0;
                                    $offline = isset($cto['clientes_offline']) ? $cto['clientes_offline'] : 0;
                                    echo $total . ' cliente(s) ';
                                    if ($total > 0) {
                                        echo '<span style="color: #10b981;">🟢 ' . $online . '</span> ';
                                        echo '<span style="color: #ef4444;">🔴 ' . $offline . '</span>';
                                    }
                                    ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($cto_status); ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="?_route=editar&id=<?php echo urlencode($cto_id); ?>" class="btn btn-edit">Editar</a>
                                    <a href="?_route=inicio&action=delete&id=<?php echo urlencode($cto_id); ?>" class="btn btn-delete" onclick="return confirm('Deseja deletar esta CTO?');">Deletar</a>
                                </div>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                        else:
                        ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <div class="empty-state">
                                    <h2>Nenhuma CTO cadastrada</h2>
                                    <p>Clique em "Adicionar Nova CTO" para começar</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
