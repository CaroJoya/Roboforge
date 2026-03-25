<?php
require_once 'session.php';
requireLogin();

if ($_POST && isset($_POST['post_id'])) {
    $postId = (int)$_POST['post_id'];
    $userId = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    if ($post) {
        $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$userId, $postId]);
        
        if ($stmt->fetch()) {
            // Unlike
            $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
            $stmt->execute([$userId, $postId]);
            $action = 'unlike';
        } else {
            // Like
            $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
            $stmt->execute([$userId, $postId]);
            
            // Notification (not to self)
            if ($post['user_id'] != $userId) {
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, from_user_id, type, post_id) VALUES (?, ?, 'like', ?)");
                $stmt->execute([$post['user_id'], $userId, $postId]);
            }
            $action = 'like';
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'action' => $action]);
    }
}
?>