<?php
require_once 'session.php';

$loggedIn = isLoggedIn();
$user = null;
if ($loggedIn) {
    $user = getCurrentUser($pdo);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoboForge – Collaborate on Robotics</title>
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
            background: #0a0a0a;
            color: #e0e0e0;
            line-height: 1.5;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #00c6fb, #005bea);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .logo i {
            color: #00c6fb;
            margin-right: 0.5rem;
        }

        .nav-links a {
            color: #e0e0e0;
            text-decoration: none;
            margin-left: 2rem;
            transition: color 0.3s;
            font-weight: 500;
        }

        .nav-links a:hover {
            color: #00c6fb;
        }

        .btn-outline {
            border: 1px solid #00c6fb;
            padding: 0.5rem 1.2rem;
            border-radius: 30px;
        }

        .btn-outline:hover {
            background: rgba(0,198,251,0.1);
        }

        /* Hero Section */
        .hero {
            max-width: 1200px;
            margin: 0 auto;
            padding: 5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 3rem;
            flex-wrap: wrap;
        }

        .hero-content {
            flex: 1;
            min-width: 280px;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff, #00c6fb);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
        }

        .hero-content p {
            font-size: 1.2rem;
            color: #b0b0b0;
            margin-bottom: 2rem;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-primary, .btn-secondary {
            padding: 0.8rem 2rem;
            border-radius: 40px;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.2s, background 0.2s;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00c6fb, #005bea);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,198,251,0.3);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid #00c6fb;
            color: #00c6fb;
        }

        .btn-secondary:hover {
            background: rgba(0,198,251,0.1);
            transform: translateY(-3px);
        }

        .hero-image {
            flex: 1;
            text-align: center;
        }

        .hero-image img {
            max-width: 100%;
            filter: drop-shadow(0 0 20px rgba(0,198,251,0.3));
        }

        /* Features Section */
        .features {
            background: #111111;
            padding: 5rem 2rem;
            text-align: center;
        }

        .features h2 {
            font-size: 2.5rem;
            margin-bottom: 3rem;
            background: linear-gradient(135deg, #fff, #00c6fb);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .features-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: center;
        }

        .feature-card {
            background: #1a1a1a;
            border-radius: 20px;
            padding: 2rem;
            width: 300px;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #2a2a2a;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 30px rgba(0,0,0,0.5);
            border-color: #00c6fb;
        }

        .feature-card i {
            font-size: 3rem;
            color: #00c6fb;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: #b0b0b0;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #777;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            .nav-links a {
                margin: 0 1rem;
            }
            .hero {
                flex-direction: column;
                text-align: center;
            }
            .btn-group {
                justify-content: center;
            }
            .hero-content h1 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>

<header class="header">
    <div class="logo">
        <i class="fas fa-robot"></i> RoboForge
    </div>
    <div class="nav-links">
        <?php if ($loggedIn): ?>
            <span>Welcome, <?= htmlspecialchars($user['username']) ?>!</span>
            <a href="profile.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php" class="btn-outline">Get Started</a>
        <?php endif; ?>
    </div>
</header>

<main>
    <section class="hero">
        <div class="hero-content">
            <h1>Build. Share. Collaborate.</h1>
            <p>RoboForge is the ultimate platform for robotics enthusiasts. Share your designs, get feedback, and collaborate with a global community of makers.</p>
            <div class="btn-group">
                <?php if ($loggedIn): ?>
                    <a href="profile.php" class="btn-primary">Go to Dashboard</a>
                <?php else: ?>
                    <a href="register.php" class="btn-primary">Join the Forge</a>
                    <a href="login.php" class="btn-secondary">Sign In</a>
                <?php endif; ?>
            </div>
        </div>

    </section>

    <section class="features">
        <h2>Why RoboForge?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-microchip"></i>
                <h3>Share Designs</h3>
                <p>Upload 3D models, code, and schematics. Showcase your creations.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-users"></i>
                <h3>Collaborate</h3>
                <p>Find teammates, get feedback, and improve together.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-chalkboard-teacher"></i>
                <h3>Learn & Grow</h3>
                <p>Tutorials, challenges, and a supportive community to level up your skills.</p>
            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <p>&copy; <?= date('Y') ?> RoboForge. Forged by robotics enthusiasts.</p>
</footer>

</body>
</html>