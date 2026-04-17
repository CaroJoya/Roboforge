<?php
require_once 'session.php';
requireLogin();

$chatUserId = $_GET['chat'] ?? 0;
$currentUser = getCurrentUser($pdo);

// Get all chats with unread count
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.username, u.profile_photo,
           (SELECT message_text FROM messages 
            WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) 
            ORDER BY created_at DESC LIMIT 1) as last_message,
           (SELECT created_at FROM messages 
            WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) 
            ORDER BY created_at DESC LIMIT 1) as last_time,
           (SELECT COUNT(*) FROM messages 
            WHERE receiver_id = ? AND sender_id = u.id AND is_read = 0) as unread_count
    FROM messages m
    JOIN users u ON (u.id = m.sender_id OR u.id = m.receiver_id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
    GROUP BY u.id
    ORDER BY last_time DESC
");
$stmt->execute([
    $_SESSION['user_id'], $_SESSION['user_id'], 
    $_SESSION['user_id'], $_SESSION['user_id'],
    $_SESSION['user_id'],
    $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']
]);
$chats = $stmt->fetchAll();

if ($chatUserId) {
    // Mark messages as read
    $stmt = $pdo->prepare("
        UPDATE messages SET is_read = 1 
        WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$chatUserId, $_SESSION['user_id']]);
    
    // Get messages
    $stmt = $pdo->prepare("
        SELECT m.*, u.username, u.profile_photo 
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$_SESSION['user_id'], $chatUserId, $chatUserId, $_SESSION['user_id']]);
    $messages = $stmt->fetchAll();
    
    // Get chat user info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$chatUserId]);
    $chatUser = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - RoboForge</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
            color: #e0e0e0;
            min-height: 100vh;
        }
        .messages-container {
            max-width: 1200px;
            margin: 2rem auto;
            background: rgba(26, 26, 46, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(0, 198, 251, 0.3);
            display: flex;
            height: 70vh;
        }
        /* Sidebar */
        .chat-sidebar {
            width: 320px;
            border-right: 1px solid rgba(0, 198, 251, 0.2);
            overflow-y: auto;
        }
        .chat-sidebar-header {
            padding: 1.2rem;
            border-bottom: 1px solid rgba(0, 198, 251, 0.2);
            background: rgba(0, 0, 0, 0.3);
        }
        .chat-sidebar-header h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .chat-item {
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
        }
        .chat-item:hover, .chat-item.active {
            background: rgba(0, 198, 251, 0.1);
        }
        .chat-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #00c6fb;
        }
        .chat-info {
            flex: 1;
        }
        .chat-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .chat-last {
            font-size: 0.75rem;
            color: #888;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .unread-badge {
            background: #00c6fb;
            color: white;
            border-radius: 20px;
            padding: 0.2rem 0.5rem;
            font-size: 0.7rem;
            font-weight: bold;
            min-width: 20px;
            text-align: center;
        }
        /* Main Chat */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 198, 251, 0.2);
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(0, 0, 0, 0.3);
        }
        .chat-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .message {
            max-width: 70%;
            display: flex;
            flex-direction: column;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .message.sent {
            align-self: flex-end;
        }
        .message.received {
            align-self: flex-start;
        }
        .message-bubble {
            padding: 0.8rem 1rem;
            border-radius: 18px;
            word-wrap: break-word;
            position: relative;
        }
        .sent .message-bubble {
            background: linear-gradient(135deg, #00c6fb, #005bea);
            color: white;
            border-bottom-right-radius: 5px;
        }
        .received .message-bubble {
            background: rgba(255, 255, 255, 0.1);
            border-bottom-left-radius: 5px;
        }
        .message-time {
            font-size: 0.65rem;
            color: #888;
            margin-top: 0.25rem;
            padding: 0 0.5rem;
        }
        .message-status {
            font-size: 0.6rem;
            margin-left: 0.5rem;
            color: #00c6fb;
        }
        .message-input-area {
            padding: 1rem;
            border-top: 1px solid rgba(0, 198, 251, 0.2);
            display: flex;
            gap: 0.5rem;
            background: rgba(0, 0, 0, 0.3);
        }
        .message-input-area input {
            flex: 1;
            padding: 0.8rem 1rem;
            background: rgba(10, 10, 10, 0.8);
            border: 1px solid rgba(0, 198, 251, 0.3);
            border-radius: 25px;
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
        }
        .message-input-area input:focus {
            outline: none;
            border-color: #00c6fb;
        }
        .message-input-area button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00c6fb, #005bea);
            border: none;
            color: white;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .message-input-area button:hover {
            transform: scale(1.05);
        }
        .empty-chat {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 1rem;
            color: #888;
        }
        .typing-indicator {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            color: #00c6fb;
            font-style: italic;
        }
        @media (max-width: 768px) {
            .chat-sidebar { width: 80px; }
            .chat-sidebar .chat-info { display: none; }
            .chat-sidebar .chat-item { justify-content: center; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="messages-container">
        <div class="chat-sidebar">
            <div class="chat-sidebar-header">
                <h3><i class="fas fa-comments"></i> Chats</h3>
            </div>
            <?php foreach ($chats as $chat): ?>
                <div class="chat-item <?= $chatUserId == $chat['id'] ? 'active' : '' ?>" 
                     onclick="location.href='messages.php?chat=<?= $chat['id'] ?>'">
                    <img src="<?= htmlspecialchars($chat['profile_photo'] ?? 'uploads/profile_photos/default.jpg') ?>" class="chat-avatar">
                    <div class="chat-info">
                        <div class="chat-name"><?= htmlspecialchars($chat['username']) ?></div>
                        <div class="chat-last"><?= htmlspecialchars(substr($chat['last_message'] ?? 'No messages', 0, 30)) ?></div>
                    </div>
                    <?php if ($chat['unread_count'] > 0): ?>
                        <span class="unread-badge"><?= $chat['unread_count'] ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if (empty($chats)): ?>
                <div style="padding: 2rem; text-align: center; color: #888;">
                    <i class="fas fa-inbox"></i>
                    <p>No messages yet</p>
                    <p style="font-size: 0.8rem;">Start a conversation from someone's profile</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="chat-main">
            <?php if ($chatUserId && $chatUser): ?>
                <div class="chat-header">
                    <img src="<?= htmlspecialchars($chatUser['profile_photo'] ?? 'uploads/profile_photos/default.jpg') ?>">
                    <h3><?= htmlspecialchars($chatUser['username']) ?></h3>
                </div>
                <div class="messages-area" id="messagesArea">
                    <?php foreach ($messages as $msg): ?>
                        <div class="message <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received' ?>" data-id="<?= $msg['id'] ?>">
                            <div class="message-bubble"><?= nl2br(htmlspecialchars($msg['message_text'])) ?></div>
                            <div class="message-time">
                                <?= date('g:i A', strtotime($msg['created_at'])) ?>
                                <?php if ($msg['sender_id'] == $_SESSION['user_id'] && $msg['is_read']): ?>
                                    <span class="message-status"><i class="fas fa-check-double"></i></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="typingIndicator" class="typing-indicator" style="display: none;"></div>
                <div class="message-input-area">
                    <input type="text" id="messageInput" placeholder="Type a message..." autocomplete="off">
                    <button onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
                </div>
            <?php else: ?>
                <div class="empty-chat">
                    <i class="fas fa-comment-dots" style="font-size: 4rem; color: #00c6fb;"></i>
                    <h3>Select a chat to start messaging</h3>
                    <p style="color: #888;">Click on a conversation from the left sidebar</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($chatUserId): ?>
    <script>
        const socket = io('http://localhost:3000');
        const chatUserId = <?= $chatUserId ?>;
        const currentUserId = <?= $_SESSION['user_id'] ?>;
        const messagesArea = document.getElementById('messagesArea');
        const messageInput = document.getElementById('messageInput');
        let typingTimeout;
        
        // Join room for this chat
        socket.emit('joinRoom', { chatId: [currentUserId, chatUserId].sort().join('_') });
        
        // Typing indicator
        messageInput.addEventListener('input', () => {
            socket.emit('typing', {
                chatId: [currentUserId, chatUserId].sort().join('_'),
                userId: currentUserId,
                username: '<?= addslashes($currentUser['username']) ?>'
            });
            
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                socket.emit('stopTyping', { chatId: [currentUserId, chatUserId].sort().join('_') });
            }, 1000);
        });
        
        socket.on('userTyping', (data) => {
            const typingDiv = document.getElementById('typingIndicator');
            typingDiv.style.display = 'block';
            typingDiv.innerHTML = `<i class="fas fa-ellipsis-h"></i> ${data.username} is typing...`;
            clearTimeout(window.typingHideTimeout);
            window.typingHideTimeout = setTimeout(() => {
                typingDiv.style.display = 'none';
            }, 2000);
        });
        
        socket.on('userStopTyping', () => {
            document.getElementById('typingIndicator').style.display = 'none';
        });
        
        socket.on('message', (data) => {
            if ((data.senderId == chatUserId && data.receiverId == currentUserId) ||
                (data.senderId == currentUserId && data.receiverId == chatUserId)) {
                displayMessage(data.message, data.senderId == currentUserId ? 'sent' : 'received');
                
                // Mark as read if received
                if (data.senderId != currentUserId) {
                    fetch('mark_read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ sender_id: data.senderId })
                    });
                }
            }
        });
        
        function sendMessage() {
            const message = messageInput.value.trim();
            if (!message) return;
            
            const data = {
                senderId: currentUserId,
                receiverId: chatUserId,
                message: message,
                chatId: [currentUserId, chatUserId].sort().join('_')
            };
            
            socket.emit('sendMessage', data);
            displayMessage(message, 'sent');
            messageInput.value = '';
            
            // Save to database
            fetch('send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
        }
        
        function displayMessage(message, type) {
            const div = document.createElement('div');
            div.className = `message ${type}`;
            div.innerHTML = `
                <div class="message-bubble">${escapeHtml(message)}</div>
                <div class="message-time">Just now</div>
            `;
            messagesArea.appendChild(div);
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        messagesArea.scrollTop = messagesArea.scrollHeight;
        
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>