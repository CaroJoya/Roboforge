<?php
require_once 'session.php';
requireLogin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$receiverId = $data['receiverId'] ?? 0;
$message = trim($data['message'] ?? '');

if (!$receiverId || !$message) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, message_text, is_read) 
        VALUES (?, ?, ?, 0)
    ");
    $stmt->execute([$_SESSION['user_id'], $receiverId, $message]);
    
    echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>