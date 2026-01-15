<?php
// api_reagendar.php
header('Content-Type: application/json');
require_once 'config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id']) && isset($data['data']) && isset($data['hora'])) {
    $id = $data['id'];
    $nova_data_hora = $data['data'] . ' ' . $data['hora']; // Formato YYYY-MM-DD HH:MM

    try {
        // Atualiza a data e volta o status para 'agendado' ou 'confirmado'
        $stmt = $pdo->prepare("UPDATE agendamentos SET data_hora_inicio = ?, status = 'confirmado' WHERE id = ?");
        $stmt->execute([$nova_data_hora, $id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
}
?>