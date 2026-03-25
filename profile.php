<?php
require_once 'session.php';
requireLogin();

$currentUserId = $_SESSION['user_id'];
$profileUserId = $_GET['id'] ?? $currentUserId; // If no id, show own profile

// Get profile user data
$stmt = $pdo->prepare("
    SELECT u.*, 
           COALESCE((SELECT COUNT(*) FROM posts WHERE user_id = u.id), 0) as posts_count,
           COALESCE((SELECT COUNT(*) FROM followers WHERE following_id = u.id), 0) as followers_count,
           COALESCE((SELECT COUNT(*) FROM followers WHERE follower_id = u.id), 0) as following_count
    FROM users u 
    WHERE u.id = ?
");
$stmt->execute([$profileUserId]);
$profileUser = $stmt->fetch();

if (!$profileUser) {
    header('Location: explore.php');
    exit();
}

// Check if this is the current user's own profile
$isOwnProfile = ($currentUserId == $profileUserId);

// Check follow status (if viewing someone else)
$isFollowing = false;
if (!$isOwnProfile) {
    $stmt = $pdo->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$currentUserId, $profileUserId]);
    $isFollowing = $stmt->fetch() ? true : false;
}

// Get posts based on profile type
$tab = $_GET['tab'] ?? 'posts';

if ($isOwnProfile && $tab === 'saved') {
    // Only show saved posts on own profile
    $stmt = $pdo->prepare("
        SELECT p.*, u.username 
        FROM saved_posts s 
        JOIN posts p ON s.post_id = p.id 
        JOIN users u ON p.user_id = u.id 
        WHERE s.user_id = ? 
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$currentUserId]);
    $posts = $stmt->fetchAll();
} else {
    // Show user's posts
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$profileUserId]);
    $posts = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($profileUser['username']) ?> - RoboForge Profile</title>
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
        }

        /* Profile Container */
        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Profile Header */
        .profile-header {
            background: rgba(26, 26, 46, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 3rem;
            margin-bottom: 2rem;
            text-align: center;
            border: 1px solid rgba(0, 198, 251, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .profile-pic-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 1.5rem;
        }

        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #00c6fb;
            box-shadow: 0 0 20px rgba(0, 198, 251, 0.3);
            transition: transform 0.3s;
        }

        .profile-pic:hover {
            transform: scale(1.05);
        }

        <?php if ($isOwnProfile): ?>
        .edit-icon {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: #00c6fb;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid #1a1a2e;
        }
        .edit-icon:hover {
            transform: scale(1.1);
            background: #005bea;
        }
        .edit-icon i {
            font-size: 1rem;
            color: white;
        }
        <?php endif; ?>

        .username {
            font-size: 2rem;
            background: linear-gradient(135deg, #fff, #00c6fb);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
        }

        .bio {
            color: #b0b0b0;
            margin-bottom: 1.5rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        /* Stats */
        .stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin: 1.5rem 0;
        }

        .stat {
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .stat:hover {
            transform: translateY(-3px);
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #00c6fb;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #888;
            margin-top: 0.25rem;
        }

        /* Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .edit-btn, .follow-btn, .message-btn {
            padding: 0.8rem 2rem;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
        }

        .edit-btn {
            background: linear-gradient(135deg, #00c6fb, #005bea);
            color: white;
        }

        .follow-btn {
            background: linear-gradient(135deg, #00c6fb, #005bea);
            color: white;
        }

        .follow-btn.following {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid #00c6fb;
            color: #00c6fb;
        }

        .message-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid #00c6fb;
            color: #00c6fb;
            text-decoration: none;
        }

        .edit-btn:hover, .follow-btn:hover, .message-btn:hover {
            transform: translateY(-2px);
        }

        .follow-btn:hover {
            box-shadow: 0 5px 15px rgba(0, 198, 251, 0.3);
        }

        .message-btn:hover {
            background: rgba(0, 198, 251, 0.1);
        }

        /* Tabs */
        .tabs {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 2rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 0.5rem;
        }

        .tab {
            padding: 0.8rem 1.5rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            border-radius: 30px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .tab i {
            font-size: 1.1rem;
        }

        .tab.active {
            background: rgba(0, 198, 251, 0.2);
            color: #00c6fb;
            border-bottom: 2px solid #00c6fb;
        }

        .tab:hover:not(.active) {
            background: rgba(255, 255, 255, 0.05);
        }

        /* Posts Grid */
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .post-card {
            background: rgba(26, 26, 46, 0.6);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            overflow: hidden;
            cursor: pointer;
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
        }

        .post-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 1rem;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .post-card:hover .post-overlay {
            opacity: 1;
        }

        .post-caption {
            font-size: 0.85rem;
            color: white;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem;
            background: rgba(26, 26, 46, 0.6);
            border-radius: 20px;
            border: 1px dashed rgba(0, 198, 251, 0.3);
        }

        .empty-state i {
            font-size: 4rem;
            color: #00c6fb;
            margin-bottom: 1rem;
        }

        .empty-state a {
            color: #00c6fb;
            text-decoration: none;
            font-weight: 600;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: rgba(26, 26, 46, 0.95);
            border-radius: 20px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            border: 1px solid #00c6fb;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }

        .modal-content textarea {
            width: 100%;
            padding: 1rem;
            background: rgba(10, 10, 10, 0.8);
            border: 1px solid rgba(0, 198, 251, 0.3);
            border-radius: 10px;
            color: white;
            font-family: 'Inter', sans-serif;
            margin-bottom: 1rem;
            resize: vertical;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .modal-buttons button {
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        .save-btn {
            background: linear-gradient(135deg, #00c6fb, #005bea);
            color: white;
        }

        .cancel-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-container {
                padding: 1rem;
            }
            .stats {
                gap: 1.5rem;
            }
            .stat-number {
                font-size: 1.4rem;
            }
            .tabs {
                gap: 1rem;
            }
            .tab {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-pic-wrapper">
                <img src="<?= htmlspecialchars($profileUser['profile_photo'] ?? 'uploads/profile_photos/default.jpg') ?>" 
                     alt="<?= htmlspecialchars($profileUser['username']) ?>" class="profile-pic">
                <?php if ($isOwnProfile): ?>
                <div class="edit-icon" onclick="openEditPhotoModal()">
                    <i class="fas fa-camera"></i>
                </div>
                <?php endif; ?>
            </div>
            <h1 class="username"><?= htmlspecialchars($profileUser['username']) ?></h1>
            <p class="bio"><?= htmlspecialchars($profileUser['bio'] ?: 'Robotics enthusiast | Building the future one robot at a time 🤖') ?></p>
            
            <div class="stats">
                <div class="stat">
                    <div class="stat-number"><?= (int)($profileUser['posts_count'] ?? 0) ?></div>
                    <div class="stat-label">Posts</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?= (int)($profileUser['followers_count'] ?? 0) ?></div>
                    <div class="stat-label">Followers</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?= (int)($profileUser['following_count'] ?? 0) ?></div>
                    <div class="stat-label">Following</div>
                </div>
            </div>
            
            <div class="action-buttons">
                <?php if ($isOwnProfile): ?>
                    <button class="edit-btn" onclick="openEditBioModal()">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                <?php else: ?>
                    <button class="follow-btn <?= $isFollowing ? 'following' : '' ?>" 
                            onclick="toggleFollow(<?= $profileUserId ?>, this)">
                        <i class="fas <?= $isFollowing ? 'fa-check' : 'fa-user-plus' ?>"></i>
                        <span><?= $isFollowing ? 'Following' : 'Follow' ?></span>
                    </button>
                    <a href="messages.php?chat=<?= $profileUserId ?>" class="message-btn">
                        <i class="fas fa-envelope"></i> Message
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Tabs: Only show Saved tab on own profile -->
        <div class="tabs">
            <div class="tab <?= $tab === 'posts' ? 'active' : '' ?>" onclick="location.href='?<?= $isOwnProfile ? '' : 'id=' . $profileUserId . '&' ?>tab=posts'">
                <i class="fas fa-th-large"></i> Posts
            </div>
            <?php if ($isOwnProfile): ?>
                <div class="tab <?= $tab === 'saved' ? 'active' : '' ?>" onclick="location.href='?tab=saved'">
                    <i class="fas fa-bookmark"></i> Saved
                </div>
            <?php endif; ?>
        </div>
        
        <div class="posts-grid">
            <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <i class="fas fa-<?= ($isOwnProfile && $tab === 'saved') ? 'bookmark' : 'camera' ?>"></i>
                    <h3>
                        <?php if ($isOwnProfile && $tab === 'saved'): ?>
                            No saved posts yet
                        <?php elseif ($isOwnProfile): ?>
                            No posts yet
                        <?php else: ?>
                            No posts yet
                        <?php endif; ?>
                    </h3>
                    <p>
                        <?php if ($isOwnProfile && $tab === 'saved'): ?>
                            Save designs you love while exploring
                        <?php elseif ($isOwnProfile): ?>
                            Share your first robot design!
                        <?php else: ?>
                            This user hasn't posted anything yet
                        <?php endif; ?>
                    </p>
                    <?php if ($isOwnProfile && $tab === 'posts'): ?>
                        <a href="upload.php"><i class="fas fa-upload"></i> Upload Now</a>
                    <?php elseif ($isOwnProfile && $tab === 'saved'): ?>
                        <a href="explore.php"><i class="fas fa-compass"></i> Explore Designs</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-card" onclick="location.href='post.php?id=<?= $post['id'] ?>'">
                        <img src="<?= htmlspecialchars($post['image_path']) ?>" alt="Post" class="post-image">
                        <?php if (!empty($post['caption'])): ?>
                            <div class="post-overlay">
                                <div class="post-caption"><?= htmlspecialchars(substr($post['caption'], 0, 60)) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Bio Modal (Only for own profile) -->
    <?php if ($isOwnProfile): ?>
    <div id="editBioModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-user-edit"></i> Edit Profile</h3>
            <form id="editBioForm" method="POST" action="update_profile.php">
                <textarea name="bio" rows="4" placeholder="Tell us about yourself..."><?= htmlspecialchars($profileUser['bio']) ?></textarea>
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeEditBioModal()">Cancel</button>
                    <button type="submit" class="save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editPhotoModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-camera"></i> Update Profile Photo</h3>
            <form id="editPhotoForm" method="POST" action="update_profile_photo.php" enctype="multipart/form-data">
                <input type="file" name="profile_photo" accept="image/jpeg,image/png" required>
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeEditPhotoModal()">Cancel</button>
                    <button type="submit" class="save-btn">Upload</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function openEditBioModal() {
            document.getElementById('editBioModal').style.display = 'flex';
        }
        
        function closeEditBioModal() {
            document.getElementById('editBioModal').style.display = 'none';
        }
        
        function openEditPhotoModal() {
            document.getElementById('editPhotoModal').style.display = 'flex';
        }
        
        function closeEditPhotoModal() {
            document.getElementById('editPhotoModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // AJAX Follow/Unfollow with notification
        async function toggleFollow(userId, button) {
            const isFollowing = button.classList.contains('following');
            const action = isFollowing ? 'unfollow' : 'follow';
            
            try {
                const response = await fetch('follow_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        action: action
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Update button UI
                    if (action === 'follow') {
                        button.classList.add('following');
                        button.innerHTML = '<i class="fas fa-check"></i> <span>Following</span>';
                        // Update follower count in stats
                        const followerStat = document.querySelector('.stats .stat:first-child + .stat .stat-number');
                        if (followerStat) {
                            let currentCount = parseInt(followerStat.innerText);
                            followerStat.innerText = currentCount + 1;
                        }
                    } else {
                        button.classList.remove('following');
                        button.innerHTML = '<i class="fas fa-user-plus"></i> <span>Follow</span>';
                        const followerStat = document.querySelector('.stats .stat:first-child + .stat .stat-number');
                        if (followerStat) {
                            let currentCount = parseInt(followerStat.innerText);
                            followerStat.innerText = currentCount - 1;
                        }
                    }
                    
                    // Show success message (optional)
                    if (result.notification_sent) {
                        console.log('Notification sent to user');
                    }
                } else {
                    alert('Error: ' + (result.error || 'Something went wrong'));
                }
            } catch (error) {
                alert('Network error. Please try again.');
            }
        }
    </script>
</body>
</html>