<?php
// Force JSON
header('Content-Type: application/json; charset=utf-8');
require_once 'session.php';


global $pdo;
if (!validateSession($pdo)) {
    echo json_encode(['success' => false, 'error' => 'Session invalid. Please log in again.']);
    exit;
}

try {
    // Session check
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }

    // POST check
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'No valid image']);
        exit;
    }

    $caption = isset($_POST['caption']) ? trim($_POST['caption']) : '';
    $tags = isset($_POST['tags']) ? trim($_POST['tags']) : '';

    // Create upload dir
    $uploadDir = 'uploads/post_images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Validate file
    $fileTmp = $_FILES['image']['tmp_name'];
    $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, ['jpg', 'jpeg', 'png'])) {
        echo json_encode(['success' => false, 'error' => 'JPG/PNG only']);
        exit;
    }

    // Move file
    $fileName = uniqid('', true) . '.' . $fileExt;
    $filePath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($fileTmp, $filePath)) {
        echo json_encode(['success' => false, 'error' => 'File save failed']);
        exit;
    }

    // Insert post
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, image_path, caption, tags) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $filePath, $caption, $tags]);
    
    // Update user post count
    $stmt = $pdo->prepare("UPDATE users SET posts_count = posts_count + 1 WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    echo json_encode([
        'success' => true, 
        'post_id' => $pdo->lastInsertId(),
        'image_path' => $filePath
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Database: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Server: ' . $e->getMessage()
    ]);
}
?>