<?php
require_once 'session.php';
requireLogin();

$postId = (int)($_GET['id'] ?? 0);
$currentUserId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT p.*, u.username, u.profile_photo, u.id as user_id FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: explore.php');
    exit();
}

// Check if current user is the post owner
$isPostOwner = ($currentUserId == $post['user_id']);

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
            justify-content: space-between;
        }

        .post-header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
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

        .post-actions-menu {
            position: relative;
        }

        .menu-btn {
            background: none;
            border: none;
            color: #e0e0e0;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            transition: all 0.3s;
        }

        .menu-btn:hover {
            color: #00c6fb;
            transform: scale(1.1);
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 35px;
            background: rgba(26, 26, 46, 0.95);
            border: 2px solid rgba(0, 198, 251, 0.3);
            border-radius: 10px;
            min-width: 180px;
            z-index: 1000;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.5);
        }

        .dropdown-menu.active {
            display: block;
            animation: slideDown 0.2s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-menu button {
            width: 100%;
            padding: 0.75rem 1rem;
            background: none;
            border: none;
            color: #e0e0e0;
            text-align: left;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all 0.2s;
            border-bottom: 1px solid rgba(0, 198, 251, 0.1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dropdown-menu button:last-child {
            border-bottom: none;
        }

        .dropdown-menu button:hover {
            background: rgba(0, 198, 251, 0.1);
            color: #00c6fb;
        }

        .dropdown-menu button.delete-btn:hover {
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
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

        /* Alert Messages */
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
            max-width: 400px;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #00c6fb, #005bea);
            color: white;
            border-left: 4px solid #00ff00;
            box-shadow: 0 5px 15px rgba(0, 198, 251, 0.3);
        }
        
        .alert-error {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            border-left: 4px solid #ff0000;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
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

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: rgba(26, 26, 46, 0.95);
            border-radius: 20px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            border: 2px solid rgba(0, 198, 251, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-content h3 {
            color: #ff6b6b;
            margin-bottom: 1rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-content p {
            color: #b0b0b0;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
        }

        .modal-buttons button {
            flex: 1;
            padding: 0.75rem;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
        }

        .delete-confirm-btn {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }

        .delete-confirm-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }

        .cancel-modal-btn {
            background: rgba(200, 200, 200, 0.2);
            color: #e0e0e0;
            border: 2px solid rgba(200, 200, 200, 0.3);
        }

        .cancel-modal-btn:hover {
            background: rgba(200, 200, 200, 0.3);
            border-color: rgba(200, 200, 200, 0.5);
        }

        @media (max-width: 768px) {
            .post-container { margin: 1rem; }
            .post-header {
                flex-wrap: wrap;
            }
            .download-btn {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" id="successAlert">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button onclick="this.parentElement.style.display='none';" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.2rem;">&times;</button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error" id="errorAlert">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button onclick="this.parentElement.style.display='none';" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.2rem;">&times;</button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php include 'navbar.php'; ?>
    
    <div class="post-container">
        <img src="<?= htmlspecialchars($post['image_path']) ?>" class="post-image" alt="Post">
        
        <div class="post-header">
            <div class="post-header-left">
                <img src="<?= htmlspecialchars($post['profile_photo'] ?? 'uploads/profile_photos/default.jpg') ?>" class="profile-pic">
                <a href="profile.php?id=<?= $post['user_id'] ?>" class="username-link">
                    <?= htmlspecialchars($post['username']) ?>
                </a>
            </div>
            
            <?php if ($isPostOwner): ?>
            <div class="post-actions-menu">
                <button class="menu-btn" onclick="toggleMenu()" id="menuToggle">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-menu" id="dropdownMenu">
                    <button onclick="openDeleteModal()">
                        <i class="fas fa-trash-alt"></i> Delete Post
                    </button>
                </div>
            </div>
            <?php else: ?>
            <button class="download-btn" onclick="downloadImage('<?= htmlspecialchars($post['image_path']) ?>')">
                <i class="fas fa-download"></i> Download Design
            </button>
            <?php endif; ?>
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
            <?php if (!$isPostOwner): ?>
            <button class="download-btn" style="margin-left: auto;" onclick="downloadImage('<?= htmlspecialchars($post['image_path']) ?>')">
                <i class="fas fa-download"></i> Download Design
            </button>
            <?php endif; ?>
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

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <h3>
                <i class="fas fa-exclamation-triangle"></i> Delete Post
            </h3>
            <p>Are you sure you want to delete this post? This action cannot be undone and all likes and comments will be removed.</p>
            <div class="modal-buttons">
                <button class="cancel-modal-btn" onclick="closeDeleteModal()">Cancel</button>
                <button class="delete-confirm-btn" onclick="confirmDeletePost(<?= $postId ?>)">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            </div>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('dropdownMenu');
            menu.classList.toggle('active');
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('dropdownMenu');
            const toggle = document.getElementById('menuToggle');
            if (!menu.contains(event.target) && !toggle.contains(event.target)) {
                menu.classList.remove('active');
            }
        });

        function openDeleteModal() {
            document.getElementById('deleteModal').classList.add('active');
            document.getElementById('dropdownMenu').classList.remove('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        function confirmDeletePost(postId) {
            const deleteBtn = event.target;
            deleteBtn.disabled = true;
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

            fetch('delete_post.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Show success message and redirect
                    setTimeout(() => {
                        window.location.href = 'profile.php';
                    }, 1000);
                } else {
                    alert('Error: ' + (result.error || 'Failed to delete post'));
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
                }
            })
            .catch(error => {
                alert('Network error: ' + error);
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
            });
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

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

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.style.display = 'none', 300);
            });
        }, 5000);
    </script>
</body>
</html>