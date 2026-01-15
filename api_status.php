<?php
// api_status.php
header('Content-Type: application/json');
require_once 'config/database.php';

// Recebe os dados do JavaScript
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id']) && isset($data['status'])) {
    $id = $data['id'];
    $status = $data['status'];

    try {
        $stmt = $pdo->prepare("UPDATE agendamentos SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
}
?>