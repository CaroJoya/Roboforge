<?php
require_once 'session.php';

if (isLoggedIn()) {
    redirectToProfile();
}

$error = '';
$success = false;

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Validation
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username can only contain letters, numbers, and underscores';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            $error = 'Username already taken';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hashedPassword]);
            
            $userId = $pdo->lastInsertId();
            // Regenerate session ID for security
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            redirectToProfile();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - RoboForge</title>
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

        .bg-microchip {
            position: fixed;
            font-size: 15rem;
            opacity: 0.03;
            top: -10%;
            left: -5%;
            pointer-events: none;
            animation: spin 30s linear infinite;
        }

        .bg-gear {
            position: fixed;
            font-size: 12rem;
            opacity: 0.03;
            bottom: 20%;
            left: 10%;
            pointer-events: none;
            animation: float 15s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(5deg); }
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Register Container */
        .register-container {
            background: rgba(26, 26, 46, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 3rem;
            width: 90%;
            max-width: 480px;
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

        /* Password Strength Indicator */
        .password-strength {
            margin-top: 8px;
            display: flex;
            gap: 5px;
        }

        .strength-bar {
            flex: 1;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            transition: all 0.3s;
        }

        .strength-bar.weak {
            background: #ff6b6b;
        }

        .strength-bar.medium {
            background: #ffd93d;
        }

        .strength-bar.strong {
            background: #6bcf7f;
        }

        .strength-text {
            font-size: 0.75rem;
            margin-top: 5px;
            color: #888;
        }

        /* Username Hint */
        .username-hint {
            font-size: 0.75rem;
            color: #888;
            margin-top: 5px;
        }

        .username-hint i {
            font-size: 0.7rem;
            margin-right: 4px;
        }

        /* Button */
        .register-btn {
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

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 198, 251, 0.3);
        }

        .register-btn:active {
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

        /* Requirements List */
        .requirements {
            margin-top: 1rem;
            font-size: 0.75rem;
            color: #666;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
        }

        .requirements span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .requirements i {
            font-size: 0.7rem;
        }

        .req-met {
            color: #6bcf7f;
        }

        .req-unmet {
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Animated background elements -->
    <div class="bg-robot">
        <i class="fas fa-robot"></i>
    </div>
    <div class="bg-microchip">
        <i class="fas fa-microchip"></i>
    </div>
    <div class="bg-gear">
        <i class="fas fa-cogs"></i>
    </div>

    <div class="register-container">
        <div class="logo">
            <i class="fas fa-user-astronaut"></i>
            <h2>Join the Forge</h2>
            <p>Start your robotics journey today</p>
        </div>

        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" id="username" placeholder="Username" required maxlength="50" autofocus>
                <div class="username-hint">
                    <i class="fas fa-info-circle"></i> Letters, numbers, and underscores only
                </div>
            </div>
            
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <div class="password-strength" id="strengthBars">
                    <div class="strength-bar" id="bar1"></div>
                    <div class="strength-bar" id="bar2"></div>
                    <div class="strength-bar" id="bar3"></div>
                </div>
                <div class="strength-text" id="strengthText">Password strength: <span id="strengthLabel">Not entered</span></div>
            </div>
            
            <div class="requirements">
                <span id="reqLength">
                    <i class="fas fa-circle"></i> Min. 6 characters
                </span>
            </div>
            
            <button type="submit" class="register-btn">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="footer-links">
            <a href="login.php">
                <i class="fas fa-sign-in-alt"></i> Already have an account? Login
            </a>
            <div class="back-home">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const bar1 = document.getElementById('bar1');
        const bar2 = document.getElementById('bar2');
        const bar3 = document.getElementById('bar3');
        const strengthLabel = document.getElementById('strengthLabel');
        const reqLength = document.getElementById('reqLength');
        
        function checkPasswordStrength(password) {
            let strength = 0;
            
            // Length check
            if (password.length >= 6) {
                strength++;
                reqLength.innerHTML = '<i class="fas fa-check-circle"></i> Min. 6 characters';
                reqLength.className = 'req-met';
            } else {
                reqLength.innerHTML = '<i class="fas fa-circle"></i> Min. 6 characters';
                reqLength.className = 'req-unmet';
            }
            
            // Contains number
            if (/\d/.test(password)) strength++;
            
            // Contains letter
            if (/[a-zA-Z]/.test(password)) strength++;
            
            // Contains special character (bonus)
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength = Math.min(strength + 1, 3);
            
            // Reset bars
            bar1.className = 'strength-bar';
            bar2.className = 'strength-bar';
            bar3.className = 'strength-bar';
            
            if (password.length === 0) {
                strengthLabel.textContent = 'Not entered';
                return;
            }
            
            if (strength === 1 || (strength === 2 && password.length < 6)) {
                bar1.classList.add('weak');
                strengthLabel.textContent = 'Weak';
            } else if (strength === 2 || (strength === 3 && password.length < 8)) {
                bar1.classList.add('medium');
                bar2.classList.add('medium');
                strengthLabel.textContent = 'Medium';
            } else if (strength >= 3) {
                bar1.classList.add('strong');
                bar2.classList.add('strong');
                bar3.classList.add('strong');
                strengthLabel.textContent = 'Strong';
            }
        }
        
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
    </script>
</body>
</html>