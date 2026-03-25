<?php
require_once 'session.php';
requireLogin();

$postId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, u.username, u.profile_photo, u.id as user_id FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: explore.php');
    exit();
}

// Likes count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
$stmt->execute([$postId]);
$likesCount = $stmt->fetch()['count'];

// Comments
$stmt = $pdo->prepare("
    SELECT c.*, u.username, u.profile_photo, u.id as user_id
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.post_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->execute([$postId]);
$comments = $stmt->fetchAll();

// Check if current user liked
$isLiked = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$_SESSION['user_id'], $postId]);
    $isLiked = $stmt->fetch() !== false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post - RoboForge</title>
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
        .post-container {
            max-width: 800px;
            margin: 2rem auto;
            background: rgba(26, 26, 46, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(0, 198, 251, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .post-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
        }
        .post-header {
            padding: 1.2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 1px solid rgba(0, 198, 251, 0.2);
        }
        .profile-pic {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #00c6fb;
        }
        .username-link {
            color: #00c6fb;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: color 0.3s;
        }
        .username-link:hover {
            color: #fff;
            text-decoration: underline;
        }
        .actions {
            padding: 1rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            border-top: 1px solid rgba(0, 198, 251, 0.2);
        }
        .action-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            transition: transform 0.2s;
            color: #e0e0e0;
        }
        .action-btn:hover {
            transform: scale(1.1);
        }
        .action-btn.active {
            color: #ff6b6b;
        }
        .likes-count {
            font-weight: 600;
            color: #00c6fb;
        }
        .caption {
            padding: 0 1rem 1rem;
            line-height: 1.5;
        }
        .caption strong {
            color: #00c6fb;
        }
        .tags {
            margin-top: 0.5rem;
            color: #888;
            font-size: 0.85rem;
        }
        .download-btn {
            margin-left: auto;
            background: linear-gradient(135deg, #00c6fb, #005bea);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: transform 0.2s;
        }
        .download-btn:hover {
            transform: scale(1.05);
        }
        .comments {
            padding: 1rem;
            border-top: 1px solid rgba(0, 198, 251, 0.2);
        }
        .comment-form {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .comment-input {
            flex: 1;
            padding: 0.8rem;
            background: rgba(10, 10, 10, 0.8);
            border: 1px solid rgba(0, 198, 251, 0.3);
            border-radius: 25px;
            color: white;
            font-family: 'Inter', sans-serif;
        }
        .comment-input:focus {
            outline: none;
            border-color: #00c6fb;
        }
        .comment-form button {
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, #00c6fb, #005bea);
            border: none;
            border-radius: 25px;
            color: white;
            cursor: pointer;
            font-weight: 600;
        }
        .comment {
            display: flex;
            gap: 0.8rem;
            margin-bottom: 1rem;
            padding: 0.5rem;
            border-radius: 10px;
            transition: background 0.2s;
        }
        .comment:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        .comment img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }
        .comment-username {
            color: #00c6fb;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .comment-username:hover {
            text-decoration: underline;
        }
        .comment-text {
            font-size: 0.9rem;
            margin-top: 0.2rem;
        }
        .comment-time {
            font-size: 0.7rem;
            color: #888;
            margin-top: 0.2rem;
        }
        .empty-comments {
            text-align: center;
            padding: 2rem;
            color: #888;
        }
        @media (max-width: 768px) {
            .post-container { margin: 1rem; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="post-container">
        <img src="<?= htmlspecialchars($post['image_path']) ?>" class="post-image" alt="Post">
        
        <div class="post-header">
            <img src="<?= htmlspecialchars($post['profile_photo'] ?? 'uploads/profile_photos/default.jpg') ?>" class="profile-pic">
            <a href="profile.php?id=<?= $post['user_id'] ?>" class="username-link">
                <?= htmlspecialchars($post['username']) ?>
            </a>
            <button class="download-btn" onclick="downloadImage('<?= htmlspecialchars($post['image_path']) ?>')">
                <i class="fas fa-download"></i> Download Design
            </button>
        </div>
        
        <div class="actions">
            <button class="action-btn <?= $isLiked ? 'active' : '' ?>" onclick="toggleLike(<?= $postId ?>)">
                <i class="fas fa-heart"></i>
            </button>
            <button class="action-btn" onclick="savePost(<?= $postId ?>)">
                <i class="fas fa-bookmark"></i>
            </button>
            <button class="action-btn" onclick="scrollToComments()">
                <i class="fas fa-comment"></i>
            </button>
            <span class="likes-count" id="likesCount"><?= $likesCount ?> likes</span>
        </div>
        
        <div class="caption">
            <a href="profile.php?id=<?= $post['user_id'] ?>" class="username-link" style="font-size: 1rem;">
                <?= htmlspecialchars($post['username']) ?>
            </a>
            <span> <?= nl2br(htmlspecialchars($post['caption'])) ?></span>
            <?php if ($post['tags']): ?>
                <div class="tags">
                    <i class="fas fa-tags"></i> <?= str_replace(',', ' ', htmlspecialchars($post['tags'])) ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="comments" id="commentsSection">
            <form class="comment-form" onsubmit="addComment(event, <?= $postId ?>)">
                <input type="text" class="comment-input" placeholder="Add a comment..." required>
                <button type="submit"><i class="fas fa-paper-plane"></i> Post</button>
            </form>
            
            <?php if (empty($comments)): ?>
                <div class="empty-comments">
                    <i class="fas fa-comments" style="font-size: 2rem; color: #888;"></i>
                    <p>No comments yet. Be the first to comment!</p>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <img src="<?= htmlspecialchars($comment['profile_photo'] ?? 'uploads/profile_photos/default.jpg') ?>">
                        <div style="flex: 1;">
                            <a href="profile.php?id=<?= $comment['user_id'] ?>" class="comment-username">
                                <?= htmlspecialchars($comment['username']) ?>
                            </a>
                            <div class="comment-text"><?= htmlspecialchars($comment['comment_text']) ?></div>
                            <div class="comment-time"><?= date('M j, Y \a\t g:i A', strtotime($comment['created_at'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        async function toggleLike(postId) {
            const response = await fetch('like_post.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `post_id=${postId}`
            });
            const result = await response.json();
            if (result.success) {
                const likesSpan = document.getElementById('likesCount');
                likesSpan.textContent = result.likes_count + ' likes';
                const likeBtn = document.querySelector('.action-btn');
                if (result.liked) {
                    likeBtn.classList.add('active');
                } else {
                    likeBtn.classList.remove('active');
                }
            }
        }
        
        async function savePost(postId) {
            const response = await fetch('save_post.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `post_id=${postId}`
            });
            const result = await response.json();
            if (result.success) {
                alert('Post saved to your collection!');
            }
        }
        
        async function addComment(e, postId) {
            e.preventDefault();
            const input = e.target.querySelector('input');
            const commentText = input.value.trim();
            
            if (!commentText) return;
            
            const response = await fetch('comment_post.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `post_id=${postId}&comment=${encodeURIComponent(commentText)}`
            });
            
            const result = await response.json();
            if (result.success) {
                input.value = '';
                location.reload();
            }
        }
        
        function downloadImage(imagePath) {
            const link = document.createElement('a');
            link.href = imagePath;
            link.download = 'roboforge_design.jpg';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        function scrollToComments() {
            document.getElementById('commentsSection').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>