<?php
require_once 'session.php';
requireLogin();

// Get current user ID
$userId = $_SESSION['user_id'];

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get bio from form
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    
    // Validate bio
    if (strlen($bio) > 500) {
        $_SESSION['error'] = 'Bio cannot exceed 500 characters';
        header('Location: profile.php');
        exit();
    }
    
    // Sanitize bio
    $bio = htmlspecialchars($bio, ENT_QUOTES, 'UTF-8');
    
    try {
        // Update bio in database
        $stmt = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
        $stmt->execute([$bio, $userId]);
        
        // Set success message
        $_SESSION['success'] = 'Bio updated successfully!';
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to update bio. Please try again.';
        error_log('Bio update error: ' . $e->getMessage());
    }
    
    // Redirect back to profile
    header('Location: profile.php');
    exit();
    
} else {
    // If not POST request, redirect to profile
    header('Location: profile.php');
    exit();
}
?>