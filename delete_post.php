<?php
require_once 'session.php';
requireLogin();

header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

// Get post ID
$postId = (int)($_POST['post_id'] ?? 0);
$currentUserId = $_SESSION['user_id'];

if (!$postId) {
    echo json_encode(['success' => false, 'error' => 'Invalid post ID']);
    exit();
}

try {
    // Get post details
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$postId, $currentUserId]);
    $post = $stmt->fetch();

    if (!$post) {
        echo json_encode(['success' => false, 'error' => 'Post not found or you do not have permission to delete it']);
        exit();
    }

    // Delete post image file
    if (file_exists($post['image_path'])) {
        @unlink($post['image_path']);
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Delete all likes associated with the post
    $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ?");
    $stmt->execute([$postId]);

    // Delete all comments associated with the post
    $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
    $stmt->execute([$postId]);

    // Delete all saved posts (bookmarks) for this post
    $stmt = $pdo->prepare("DELETE FROM saved_posts WHERE post_id = ?");
    $stmt->execute([$postId]);

    // Delete all notifications for this post
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE post_id = ?");
    $stmt->execute([$postId]);

    // Delete the post itself
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$postId, $currentUserId]);

    // Update user's post count
    $stmt = $pdo->prepare("UPDATE users SET posts_count = GREATEST(0, posts_count - 1) WHERE id = ?");
    $stmt->execute([$currentUserId]);

    // Commit transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);

} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    error_log('Delete post error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    error_log('Delete post error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>