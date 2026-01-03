<?php
header('Content-Type: application/json');

// Conexão com o banco de dados
$connection = new mysqli("127.0.0.1", "root", "vertrigo", "mkradius");

// Verifica erros na conexão
if ($connection->connect_error) {
    echo json_encode(['success' => false, 'output' => 'Erro ao conectar ao banco de dados.']);
    exit;
}

// Recebe o nome da OLT via POST
$nome_olt = $_POST['nome_olt'] ?? '';

// Busca os dados da OLT no banco de dados
$query = $connection->prepare("SELECT endereco_ip, portaSSH, usuario, senha, fabricante, modelo FROM mp_olt WHERE nome_olt = ?");
$query->bind_param("s", $nome_olt);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $row]);
} else {
    echo json_encode(['success' => false, 'output' => 'OLT não encontrada.']);
}

$query->close();
$connection->close();
?>