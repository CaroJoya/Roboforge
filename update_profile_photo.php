<?php
require_once 'session.php';
requireLogin();

// Get current user ID
$userId = $_SESSION['user_id'];

// Check if file was uploaded
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
    
    $file = $_FILES['profile_photo'];
    
    // Validate file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = 'File upload error. Please try again.';
        header('Location: profile.php');
        exit();
    }
    
    // Get file info
    $fileName = $file['name'];
    $fileTmpPath = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileType = $file['type'];
    
    // Validate file size (max 5MB)
    $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($fileSize > $maxFileSize) {
        $_SESSION['error'] = 'File size exceeds 5MB limit.';
        header('Location: profile.php');
        exit();
    }
    
    // Validate file type (only JPEG and PNG)
    $allowedMimes = ['image/jpeg', 'image/png'];
    $allowedExtensions = ['jpg', 'jpeg', 'png'];
    
    if (!in_array($fileType, $allowedMimes)) {
        $_SESSION['error'] = 'Only JPEG and PNG images are allowed.';
        header('Location: profile.php');
        exit();
    }
    
    // Validate file extension
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions)) {
        $_SESSION['error'] = 'Invalid file extension. Only JPG and PNG allowed.';
        header('Location: profile.php');
        exit();
    }
    
    // Validate MIME type by reading file
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileTmpPath);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedMimes)) {
        $_SESSION['error'] = 'File content is not a valid image.';
        header('Location: profile.php');
        exit();
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = 'uploads/profile_photos';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $_SESSION['error'] = 'Failed to create upload directory.';
            header('Location: profile.php');
            exit();
        }
    }
    
    // Generate unique filename
    $newFileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
    $uploadPath = $uploadDir . '/' . $newFileName;
    
    // Get current profile photo for deletion
    $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    $oldPhoto = $user['profile_photo'] ?? null;
    
    try {
        // Move uploaded file to destination
        if (!move_uploaded_file($fileTmpPath, $uploadPath)) {
            $_SESSION['error'] = 'Failed to save uploaded file.';
            header('Location: profile.php');
            exit();
        }
        
        // Delete old profile photo if it exists and is not the default
        if ($oldPhoto && 
            $oldPhoto !== 'uploads/profile_photos/default.jpg' && 
            file_exists($oldPhoto)) {
            @unlink($oldPhoto);
        }
        
        // Update database with new profile photo path
        $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
        $stmt->execute([$uploadPath, $userId]);
        
        $_SESSION['success'] = 'Profile photo updated successfully!';
        
    } catch (Exception $e) {
        // Delete uploaded file if database update fails
        if (file_exists($uploadPath)) {
            @unlink($uploadPath);
        }
        
        $_SESSION['error'] = 'Failed to update profile photo. Please try again.';
        error_log('Profile photo update error: ' . $e->getMessage());
    }
    
    header('Location: profile.php');
    exit();
    
} else {
    // If not POST request or no file uploaded, redirect to profile
    header('Location: profile.php');
    exit();
}
?>