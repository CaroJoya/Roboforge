<?php
require_once 'session.php';
requireLogin();

if ($_POST && isset($_POST['post_id']) && isset($_POST['comment'])) {
    $postId = (int)$_POST['post_id'];
    $commentText = trim($_POST['comment']);
    
    $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, comment_text) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $postId, $commentText]);
    
    // Notification
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $postOwnerId = $stmt->fetch()['user_id'];
    
    if ($postOwnerId != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, from_user_id, type, post_id) VALUES (?, ?, 'comment', ?)");
        $stmt->execute([$postOwnerId, $_SESSION['user_id'], $postId]);
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
}
?>