<?php
require_once 'session.php';
requireLogin();

$currentUserId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT DISTINCT p.*, u.username, u.id as user_id
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.user_id IN (
        SELECT following_id FROM followers WHERE follower_id = ?
    )
    ORDER BY p.created_at DESC 
    LIMIT 20
");
$stmt->execute([$currentUserId]);
$followedPosts = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.id as user_id
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.user_id NOT IN (
        SELECT following_id FROM followers WHERE follower_id = ?
    ) AND p.user_id != ?
    ORDER BY RAND() 
    LIMIT 12
");
$stmt->execute([$currentUserId, $currentUserId]);
$randomPosts = $stmt->fetchAll();

$posts = array_merge($followedPosts, $randomPosts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore - RoboForge</title>
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
        .explore-header {
            text-align: center;
            padding: 2rem;
            background: rgba(26, 26, 46, 0.6);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 198, 251, 0.2);
            margin-bottom: 2rem;
        }
        .explore-header h1 {
            font-size: 2rem;
            background: linear-gradient(135deg, #fff, #00c6fb);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .masonry {
            column-count: 4;
            column-gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }
        .post-card {
            break-inside: avoid;
            margin-bottom: 20px;
            background: rgba(26, 26, 46, 0.6);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid rgba(0, 198, 251, 0.2);
            position: relative;
        }
        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 198, 251, 0.2);
            border-color: #00c6fb;
        }
        .post-image {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            cursor: pointer;
        }
        .post-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
            padding: 1rem;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .post-card:hover .post-overlay {
            opacity: 1;
        }
        .post-username {
            color: #00c6fb;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            display: inline-block;
            transition: color 0.2s;
        }
        .post-username:hover {
            color: #fff;
            text-decoration: underline;
        }
        .post-caption {
            font-size: 0.75rem;
            color: white;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-top: 0.25rem;
        }
        .empty-state {
            text-align: center;
            padding: 4rem;
            background: rgba(26, 26, 46, 0.6);
            border-radius: 20px;
            margin: 2rem;
        }
        @media (max-width: 1024px) { .masonry { column-count: 3; } }
        @media (max-width: 768px) { .masonry { column-count: 2; } }
        @media (max-width: 480px) { .masonry { column-count: 1; } }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="explore-header">
        <h1><i class="fas fa-compass"></i> Explore Robotics Designs</h1>
        <p style="color: #888; margin-top: 0.5rem;">Discover amazing creations from the community</p>
    </div>
    
    <div class="masonry">
        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <i class="fas fa-robot" style="font-size: 4rem; color: #00c6fb;"></i>
                <h3 style="margin-top: 1rem;">No designs yet</h3>
                <p>Be the first to share your robot design!</p>
                <a href="upload.php" style="display: inline-block; margin-top: 1rem; padding: 0.8rem 2rem; background: linear-gradient(135deg, #00c6fb, #005bea); border-radius: 40px; color: white; text-decoration: none;">Upload Now</a>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post-card">
                    <img src="<?= htmlspecialchars($post['image_path']) ?>" alt="Design" class="post-image" onclick="location.href='post.php?id=<?= $post['id'] ?>'">
                    <div class="post-overlay">
                        <div class="post-username" onclick="event.stopPropagation(); location.href='profile.php?id=<?= $post['user_id'] ?>'">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($post['username']) ?>
                        </div>
                        <?php if (!empty($post['caption'])): ?>
                            <div class="post-caption"><?= htmlspecialchars(substr($post['caption'], 0, 60)) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>