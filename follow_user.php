<?php
require_once 'session.php';
requireLogin();

if ($_POST && isset($_POST['user_id'])) {
    $targetUserId = (int)$_POST['user_id'];
    
    if ($targetUserId != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$_SESSION['user_id'], $targetUserId]);
        
        if (!$stmt->fetch()) {
            // Insert follow
            $stmt = $pdo->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $targetUserId]);
            
            // Update counts
            $pdo->prepare("UPDATE users SET following_count = following_count + 1 WHERE id = ?")->execute([$_SESSION['user_id']]);
            $pdo->prepare("UPDATE users SET followers_count = followers_count + 1 WHERE id = ?")->execute([$targetUserId]);
            
            // Create notification
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, from_user_id, type) VALUES (?, ?, 'follow')");
            $stmt->execute([$targetUserId, $_SESSION['user_id']]);
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
}
?>