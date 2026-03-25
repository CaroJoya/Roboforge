<?php
require_once 'session.php';
requireLogin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['user_id'] ?? 0;
$action = $data['action'] ?? '';

$currentUserId = $_SESSION['user_id'];

if (!$userId || $userId == $currentUserId) {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit();
}

try {
    if ($action === 'follow') {
        // Check if already following
        $stmt = $pdo->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$currentUserId, $userId]);
        
        if (!$stmt->fetch()) {
            // Add follow
            $stmt = $pdo->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
            $stmt->execute([$currentUserId, $userId]);
            
            // Update follower counts
            $stmt = $pdo->prepare("UPDATE users SET followers_count = followers_count + 1 WHERE id = ?");
            $stmt->execute([$userId]);
            
            $stmt = $pdo->prepare("UPDATE users SET following_count = following_count + 1 WHERE id = ?");
            $stmt->execute([$currentUserId]);
            
            // Create notification
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, from_user_id, type, is_read) 
                VALUES (?, ?, 'follow', 0)
            ");
            $stmt->execute([$userId, $currentUserId]);
            
            echo json_encode(['success' => true, 'action' => 'follow', 'notification_sent' => true]);
        } else {
            echo json_encode(['success' => true, 'action' => 'already_following']);
        }
        
    } elseif ($action === 'unfollow') {
        // Remove follow
        $stmt = $pdo->prepare("DELETE FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$currentUserId, $userId]);
        
        // Update follower counts
        $stmt = $pdo->prepare("UPDATE users SET followers_count = followers_count - 1 WHERE id = ?");
        $stmt->execute([$userId]);
        
        $stmt = $pdo->prepare("UPDATE users SET following_count = following_count - 1 WHERE id = ?");
        $stmt->execute([$currentUserId]);
        
        echo json_encode(['success' => true, 'action' => 'unfollow']);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>