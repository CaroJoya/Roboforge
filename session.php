<?php
session_start();
require_once 'db.php';

// Session timeout duration (24 hours)
define('SESSION_TIMEOUT', 86400);

function isLoggedIn() {
    // Check if user_id exists (backward compatible with old sessions)
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // If login_time doesn't exist, set it now (for old sessions)
    if (!isset($_SESSION['login_time'])) {
        $_SESSION['login_time'] = time();
    }
    
    return true;
}

// Validate session timeout
function isSessionValid() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // If login_time doesn't exist, set it now
    if (!isset($_SESSION['login_time'])) {
        $_SESSION['login_time'] = time();
        return true;
    }
    
    // Check if session has expired
    if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    
    return true;
}

// Validate that the user_id in session actually exists in the database
function validateSession($pdo) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    if (!isSessionValid()) {
        return false;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        // User doesn't exist – destroy session
        session_destroy();
        return false;
    }
    
    // Refresh session timeout on activity
    $_SESSION['login_time'] = time();
    return true;
}

function requireLogin() {
    global $pdo;
    if (!validateSession($pdo)) {
        header('Location: login.php');
        exit();
    }
}

function getCurrentUser($pdo) {
    if (isset($_SESSION['user_id']) && validateSession($pdo)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}

function redirectToProfile() {
    if (isset($_SESSION['user_id'])) {
        header('Location: profile.php');
        exit();
    }
}

// Secure logout function
function logout() {
    session_unset();
    session_destroy();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
}

// Set login session (call after successful authentication)
function setLoginSession($userId) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['login_time'] = time();
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
}
?>