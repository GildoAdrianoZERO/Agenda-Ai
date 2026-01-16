<?php
// api_atribuir.php
header('Content-Type: application/json');
require_once 'config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['agendamento_id']) && isset($data['profissional_id'])) {
    $agendamento_id = $data['agendamento_id'];
    $prof_id = $data['profissional_id']; // ID ou null

    try {
        $stmt = $pdo->prepare("UPDATE agendamentos SET profissional_id = ? WHERE id = ?");
        $stmt->execute([$prof_id, $agendamento_id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
}
?>