<?php
require_once 'session.php';
require_once 'tags_data.php';
requireLogin();

$query = $_GET['q'] ?? '';
$users = [];
$posts = [];

if ($query) {
    // Search users (unchanged)
    $stmt = $pdo->prepare("SELECT id, username, profile_photo, bio FROM users WHERE username LIKE ? LIMIT 10");
    $stmt->execute(['%' . $query . '%']);
    $users = $stmt->fetchAll();
    
    // Updated: Search posts by caption, tags, OR specific tag search
    // Remove '#' if user included it in search
    $searchTerm = ltrim($query, '#');
    
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.profile_photo
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.caption LIKE ? 
        OR p.tags LIKE ? 
        OR p.tags LIKE ? 
        OR p.tags LIKE ?
        ORDER BY p.created_at DESC 
        LIMIT 20
    ");
    
    // Search patterns:
    // 1. Tag at beginning: "arduino,other"
    // 2. Tag in middle: "something,arduino,other"  
    // 3. Tag at end: "something,arduino"
    $stmt->execute([
        '%' . $searchTerm . '%',           // Caption search
        $searchTerm . ',%',                 // Tag at beginning
        '%,' . $searchTerm . ',%',          // Tag in middle
        '%,' . $searchTerm                  // Tag at end
    ]);
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
        .post-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 8px;
        }
        .post-tag {
            background: rgba(0, 198, 251, 0.2);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            color: #00c6fb;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #888;
        }
        .popular-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(0, 198, 251, 0.2);
        }
        .popular-tag {
            background: rgba(0, 198, 251, 0.1);
            padding: 6px 14px;
            border-radius: 20px;
            text-decoration: none;
            color: #00c6fb;
            font-size: 13px;
            transition: all 0.2s;
        }
        .popular-tag:hover {
            background: rgba(0, 198, 251, 0.3);
            transform: translateY(-2px);
        }
        .tag-search-badge {
            display: inline-block;
            background: rgba(0, 198, 251, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
            color: #00c6fb;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <form class="search-box" method="GET" action="search.php">
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Search users, tags (#arduino), or designs..." value="<?= htmlspecialchars($query) ?>" autofocus>
            <button type="submit">Search</button>
        </form>
        
        <!-- Popular Tags Section (Quick Search) -->
        <div class="results-section">
            <h3><i class="fas fa-hashtag"></i> Popular Tags</h3>
            <div class="popular-tags">
                <a href="search.php?q=arduino" class="popular-tag">#arduino</a>
                <a href="search.php?q=raspberrypi" class="popular-tag">#raspberrypi</a>
                <a href="search.php?q=3dprinting" class="popular-tag">#3dprinting</a>
                <a href="search.php?q=robotics" class="popular-tag">#robotics</a>
                <a href="search.php?q=ai" class="popular-tag">#ai</a>
                <a href="search.php?q=diy" class="popular-tag">#diy</a>
                <a href="search.php?q=sensors" class="popular-tag">#sensors</a>
                <a href="search.php?q=esp32" class="popular-tag">#esp32</a>
                <a href="search.php?q=servo" class="popular-tag">#servo</a>
                <a href="search.php?q=drone" class="popular-tag">#drone</a>
                <a href="search.php?q=opencv" class="popular-tag">#opencv</a>
                <a href="search.php?q=ros" class="popular-tag">#ros</a>
            </div>
        </div>
        
        <?php if ($query): ?>
            <?php if (!empty($users)): ?>
                <div class="results-section">
                    <h3><i class="fas fa-users"></i> Users (<?= count($users) ?>)</h3>
                    <div class="user-grid">
                        <?php foreach ($users as $user): ?>
                            <div class="user-card" onclick="location.href='profile.php?id=<?= $user['id'] ?>'">
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
                    <h3>
                        <i class="fas fa-microchip"></i> Designs (<?= count($posts) ?>)
                        <?php if (strpos($query, '#') !== false || true): ?>
                            <span class="tag-search-badge">
                                <i class="fas fa-tag"></i> Searching tag: #<?= htmlspecialchars(ltrim($query, '#')) ?>
                            </span>
                        <?php endif; ?>
                    </h3>
                    <div class="posts-grid">
                        <?php foreach ($posts as $post): ?>
                            <div class="post-card" onclick="location.href='post.php?id=<?= $post['id'] ?>'">
                                <img src="<?= htmlspecialchars($post['image_path']) ?>">
                                <div class="post-info">
                                    <div class="username"><?= htmlspecialchars($post['username']) ?></div>
                                    <div style="font-size: 0.75rem; color: #666; margin-top: 4px;">
                                        <?= date('M j, Y', strtotime($post['created_at'])) ?>
                                    </div>
                                    <?php 
                                    // Display tags if they exist
                                    if (!empty($post['tags'])): 
                                        $tags = explode(',', $post['tags']);
                                    ?>
                                        <div class="post-tags">
                                            <?php foreach ($tags as $tag): ?>
                                                <span class="post-tag">#<?= htmlspecialchars(trim($tag)) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
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
                    <p style="color: #888;">Try different keywords or browse popular tags above</p>
                    <a href="explore.php" style="color: #00c6fb; text-decoration: none;">Explore designs →</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>