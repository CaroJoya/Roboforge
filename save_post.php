<?php
require_once 'session.php';
requireLogin();

if ($_POST && isset($_POST['post_id'])) {
    $postId = (int)$_POST['post_id'];
    
    $stmt = $pdo->prepare("SELECT id FROM saved_posts WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$_SESSION['user_id'], $postId]);
    
    if ($stmt->fetch()) {
        // Unsave
        $stmt = $pdo->prepare("DELETE FROM saved_posts WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$_SESSION['user_id'], $postId]);
    } else {
        // Save
        $stmt = $pdo->prepare("INSERT INTO saved_posts (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $postId]);
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
}
?>