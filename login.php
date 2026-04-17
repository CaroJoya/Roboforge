<?php
require_once 'session.php';

if (isLoggedIn()) {
    redirectToProfile();
}

$error = '';
if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Use the secure login function from session.php
        setLoginSession($user['id']);
        redirectToProfile();
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RoboForge</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
            color: #e0e0e0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background elements */
        .bg-robot {
            position: fixed;
            font-size: 20rem;
            opacity: 0.03;
            bottom: -10%;
            right: -5%;
            pointer-events: none;
            animation: float 20s ease-in-out infinite;
        }

        .bg-gear {
            position: fixed;
            font-size: 15rem;
            opacity: 0.03;
            top: -10%;
            left: -5%;
            pointer-events: none;
            animation: spin 30s linear infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(5deg); }
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Login Container */
        .login-container {
            background: rgba(26, 26, 46, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 3rem;
            width: 90%;
            max-width: 450px;
            border: 1px solid rgba(0, 198, 251, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 30px rgba(0, 198, 251, 0.1);
            animation: slideUp 0.6s ease-out;
            z-index: 1;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Logo Section */
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo i {
            font-size: 4rem;
            background: linear-gradient(135deg, #00c6fb, #005bea);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
        }

        .logo h2 {
            font-size: 2rem;
            background: linear-gradient(135deg, #fff, #00c6fb);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-top: 0.5rem;
        }

        .logo p {
            color: #888;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        /* Form Styles */
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #00c6fb;
            font-size: 1.2rem;
        }

        input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            background: rgba(10, 10, 10, 0.8);
            border: 1px solid rgba(0, 198, 251, 0.3);
            border-radius: 12px;
            color: #e0e0e0;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #00c6fb;
            box-shadow: 0 0 15px rgba(0, 198, 251, 0.2);
            background: rgba(10, 10, 10, 1);
        }

        input::placeholder {
            color: #666;
        }

        /* Button */
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #00c6fb, #005bea);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 0.5rem;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 198, 251, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        /* Error Message */
        .error {
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid #ff6b6b;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 1.5rem;
            color: #ff6b6b;
            text-align: center;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .error i {
            font-size: 1rem;
        }

        /* Footer Links */
        .footer-links {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-links a {
            color: #00c6fb;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .footer-links a:hover {
            color: #fff;
            text-decoration: underline;
        }

        .back-home {
            margin-top: 1rem;
        }

        .back-home a {
            color: #888;
            font-size: 0.85rem;
        }

        .back-home a:hover {
            color: #00c6fb;
        }
    </style>
</head>
<body>
    <!-- Animated background elements -->
    <div class="bg-robot">
        <i class="fas fa-robot"></i>
    </div>
    <div class="bg-gear">
        <i class="fas fa-cogs"></i>
    </div>

    <div class="login-container">
        <div class="logo">
            <i class="fas fa-robot"></i>
            <h2>Welcome Back</h2>
            <p>Sign in to continue your robotics journey</p>
        </div>

        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="Username" required autofocus>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="footer-links">
            <a href="register.php">
                <i class="fas fa-user-plus"></i> Create new account
            </a>
            <div class="back-home">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>