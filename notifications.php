<?php
require_once 'session.php';
requireLogin();

$notifications = [];

try {
    $stmt = $pdo->prepare("
        SELECT n.*, u.username, u.profile_photo 
        FROM notifications n 
        LEFT JOIN users u ON n.from_user_id = u.id 
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll();
    
    // Mark as read
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
} catch (Exception $e) {
    $notifications = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - RoboForge</title>
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
            max-width: 700px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .header {
            background: rgba(26, 26, 46, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0, 198, 251, 0.3);
        }
        .header h2 {
            font-size: 1.8rem;
            background: linear-gradient(135deg, #fff, #00c6fb);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .notif-list {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        .notif-item {
            background: rgba(26, 26, 46, 0.6);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 1rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            transition: all 0.3s;
            border: 1px solid rgba(0, 198, 251, 0.1);
            cursor: pointer;
        }
        .notif-item:hover {
            transform: translateX(5px);
            border-color: rgba(0, 198, 251, 0.3);
            background: rgba(26, 26, 46, 0.8);
        }
        .notif-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00c6fb, #005bea);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .notif-content {
            flex: 1;
        }
        .notif-text {
            margin-bottom: 0.25rem;
        }
        .notif-text strong {
            color: #00c6fb;
        }
        .notif-time {
            font-size: 0.75rem;
            color: #888;
        }
        .empty-state {
            background: rgba(26, 26, 46, 0.6);
            border-radius: 20px;
            padding: 4rem;
            text-align: center;
            border: 1px dashed rgba(0, 198, 251, 0.3);
        }
        .empty-state i {
            font-size: 4rem;
            color: #00c6fb;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .notif-item { padding: 0.8rem; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <div class="header">
            <h2><i class="fas fa-bell"></i> Notifications</h2>
        </div>
        
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <h3>No notifications yet</h3>
                <p style="color: #888; margin-top: 0.5rem;">When someone likes, comments, or follows you, it'll appear here</p>
            </div>
        <?php else: ?>
            <div class="notif-list">
                <?php foreach ($notifications as $notif): ?>
                    <div class="notif-item" onclick="handleNotificationClick(<?= $notif['post_id'] ? 'this, ' . $notif['post_id'] : 'this' ?>)">
                        <div class="notif-icon">
                            <?php
                            $icon = 'fa-user-plus';
                            if ($notif['type'] == 'like') $icon = 'fa-heart';
                            elseif ($notif['type'] == 'comment') $icon = 'fa-comment';
                            elseif ($notif['type'] == 'follow') $icon = 'fa-user-plus';
                            else $icon = 'fa-bell';
                            ?>
                            <i class="fas <?= $icon ?>"></i>
                        </div>
                        <div class="notif-content">
                            <div class="notif-text">
                                <?php if ($notif['type'] == 'like'): ?>
                                    <strong><?= htmlspecialchars($notif['username'] ?? 'Someone') ?></strong> liked your post
                                <?php elseif ($notif['type'] == 'comment'): ?>
                                    <strong><?= htmlspecialchars($notif['username'] ?? 'Someone') ?></strong> commented on your post
                                <?php elseif ($notif['type'] == 'follow'): ?>
                                    <strong><?= htmlspecialchars($notif['username'] ?? 'Someone') ?></strong> started following you
                                <?php else: ?>
                                    <strong><?= htmlspecialchars($notif['username'] ?? 'Someone') ?></strong> interacted with you
                                <?php endif; ?>
                            </div>
                            <div class="notif-time">
                                <?= date('M j, Y \a\t g:i A', strtotime($notif['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function handleNotificationClick(element, postId) {
            if (postId) {
                window.location.href = 'post.php?id=' + postId;
            }
        }
    </script>
</body>
</html>