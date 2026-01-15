<?php
// api_horarios.php
header('Content-Type: application/json');
require_once 'config/database.php';

// Recebe a Data e o ID da Loja
$data = filter_input(INPUT_GET, 'data'); // YYYY-MM-DD
$loja_id = filter_input(INPUT_GET, 'loja_id', FILTER_VALIDATE_INT);

if (!$data || !$loja_id) {
    echo json_encode([]); 
    exit;
}

try {
    // A MÁGICA ESTÁ AQUI:
    // Buscamos apenas os horários que estão ocupando vaga de verdade.
    // Ignoramos (NOT LIKE) qualquer status que comece com 'cancelado' (seja pelo cliente ou loja).
    
    $sql = "SELECT DATE_FORMAT(data_hora_inicio, '%H:%i') as hora 
            FROM agendamentos 
            WHERE estabelecimento_id = ? 
            AND DATE(data_hora_inicio) = ? 
            AND status NOT LIKE 'cancelado%'"; 
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$loja_id, $data]);
    
    // Retorna array dos horários OCUPADOS
    $ocupados = $stmt->fetchAll(PDO::FETCH_COLUMN); 

    echo json_encode($ocupados);

} catch (PDOException $e) {
    echo json_encode([]);
}
?>