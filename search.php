<?php
require_once 'session.php';
requireLogin();

$query = $_GET['q'] ?? '';
$users = [];
$posts = [];

if ($query) {
    $stmt = $pdo->prepare("SELECT id, username, profile_photo, bio FROM users WHERE username LIKE ? LIMIT 10");
    $stmt->execute(['%' . $query . '%']);
    $users = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("
        SELECT p.*, u.username 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.caption LIKE ? OR p.tags LIKE ? 
        ORDER BY p.created_at DESC 
        LIMIT 20
    ");
    $stmt->execute(['%' . $query . '%', '%' . $query . '%']);
    $posts = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - RoboForge</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
            color: #e0e0e0;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .search-box {
            background: rgba(26, 26, 46, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 60px;
            padding: 0.2rem;
            display: flex;
            align-items: center;
            border: 1px solid rgba(0, 198, 251, 0.3);
            margin-bottom: 2rem;
        }
        .search-box i {
            padding: 0 1rem;
            color: #00c6fb;
        }
        .search-box input {
            flex: 1;
            padding: 1rem;
            background: transparent;
            border: none;
            color: white;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
        }
        .search-box input:focus {
            outline: none;
        }
        .search-box button {
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, #00c6fb, #005bea);
            border: none;
            border-radius: 40px;
            color: white;
            cursor: pointer;
            font-weight: 600;
        }
        .results-section {
            background: rgba(26, 26, 46, 0.6);
            backdrop-filter: blur(5px);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0, 198, 251, 0.2);
        }
        .results-section h3 {
            margin-bottom: 1rem;
            color: #00c6fb;
        }
        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
        }
        .user-card {
            background: rgba(10, 10, 10, 0.6);
            border-radius: 15px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid rgba(0, 198, 251, 0.1);
        }
        .user-card:hover {
            transform: translateY(-3px);
            border-color: #00c6fb;
            background: rgba(0, 198, 251, 0.1);
        }
        .user-card img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 0.5rem;
            border: 2px solid #00c6fb;
        }
        .user-card .username {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .user-card .bio {
            font-size: 0.75rem;
            color: #888;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }
        .post-card {
            background: rgba(10, 10, 10, 0.6);
            border-radius: 15px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid rgba(0, 198, 251, 0.1);
        }
        .post-card:hover {
            transform: translateY(-3px);
            border-color: #00c6fb;
        }
        .post-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .post-info {
            padding: 0.8rem;
        }
        .post-info .username {
            font-weight: 600;
            color: #00c6fb;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #888;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <form class="search-box" method="GET" action="search.php">
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Search users, designs, tags..." value="<?= htmlspecialchars($query) ?>" autofocus>
            <button type="submit">Search</button>
        </form>
        
        <?php if ($query): ?>
            <?php if (!empty($users)): ?>
                <div class="results-section">
                    <h3><i class="fas fa-users"></i> Users (<?= count($users) ?>)</h3>
                    <div class="user-grid">
                        <?php foreach ($users as $user): ?>
                            <div class="user-card" onclick="location.href='profile.php?user=<?= $user['id'] ?>'">
                                <img src="<?= htmlspecialchars($user['profile_photo'] ?? 'uploads/profile_photos/default.jpg') ?>">
                                <div class="username"><?= htmlspecialchars($user['username']) ?></div>
                                <div class="bio"><?= htmlspecialchars(substr($user['bio'] ?? 'Robotics enthusiast', 0, 30)) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($posts)): ?>
                <div class="results-section">
                    <h3><i class="fas fa-microchip"></i> Designs (<?= count($posts) ?>)</h3>
                    <div class="posts-grid">
                        <?php foreach ($posts as $post): ?>
                            <div class="post-card" onclick="location.href='post.php?id=<?= $post['id'] ?>'">
                                <img src="<?= htmlspecialchars($post['image_path']) ?>">
                                <div class="post-info">
                                    <div class="username"><?= htmlspecialchars($post['username']) ?></div>
                                    <div style="font-size: 0.75rem; color: #666;"><?= date('M j, Y', strtotime($post['created_at'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (empty($users) && empty($posts)): ?>
                <div class="empty-state">
                    <i class="fas fa-search" style="font-size: 4rem; color: #00c6fb;"></i>
                    <h3 style="margin-top: 1rem;">No results found for "<?= htmlspecialchars($query) ?>"</h3>
                    <p style="color: #888;">Try different keywords or browse the explore page</p>
                    <a href="explore.php" style="color: #00c6fb; text-decoration: none;">Explore designs →</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>