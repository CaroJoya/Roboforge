<?php if (isLoggedIn()): ?>
<nav style="background: rgba(10, 10, 10, 0.95); backdrop-filter: blur(10px); padding: 0.8rem 2rem; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid rgba(0, 198, 251, 0.2);">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <a href="index.php" style="font-size: 1.5rem; font-weight: 700; background: linear-gradient(135deg, #00c6fb, #005bea); -webkit-background-clip: text; background-clip: text; color: transparent; text-decoration: none;">
            <i class="fas fa-robot"></i> RoboForge
        </a>
        <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: center;">
            <a href="explore.php" style="color: #e0e0e0; text-decoration: none; transition: color 0.3s; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-compass"></i> Explore
            </a>
            <a href="search.php" style="color: #e0e0e0; text-decoration: none; transition: color 0.3s; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-search"></i> Search
            </a>
            <a href="upload.php" style="color: #e0e0e0; text-decoration: none; transition: color 0.3s; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-upload"></i> Upload
            </a>
            <a href="messages.php" style="color: #e0e0e0; text-decoration: none; transition: color 0.3s; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-envelope"></i> Messages
            </a>
            <a href="notifications.php" style="color: #e0e0e0; text-decoration: none; transition: color 0.3s; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-bell"></i> Notifications
            </a>
            <a href="profile.php" style="color: #e0e0e0; text-decoration: none; transition: color 0.3s; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-user-circle"></i> Profile
            </a>
            <a href="logout.php" style="color: #ff6b6b; text-decoration: none; transition: color 0.3s; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</nav>

<style>
    nav a:hover {
        color: #00c6fb !important;
        transform: translateY(-2px);
        transition: all 0.3s;
    }
    
    @media (max-width: 768px) {
        nav div {
            flex-direction: column;
            text-align: center;
        }
        nav div div {
            justify-content: center;
        }
    }
</style>
<?php endif; ?>