<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Get current user if logged in
$current_user = null;
if (isset($_SESSION['user_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $user_query = "SELECT * FROM users WHERE id = :user_id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(':user_id', $_SESSION['user_id']);
    $user_stmt->execute();
    
    if ($user_stmt->rowCount() > 0) {
        $current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - TRAGOS' : 'TRAGOS - Travel Together'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
</head>
<body>
    <header class="main-header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-brand">
                    <a href="index.php" class="brand-link">
                        <img src="assets/images/logo.png" alt="TRAGOS" class="brand-logo">
                        <span class="brand-text">TRAGOS</span>
                    </a>
                </div>
                
                <div class="nav-menu" id="navMenu">
                    <div class="nav-links">
                        <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Home
                        </a>
                        <a href="groups.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'groups.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i> Groups
                        </a>
                        <a href="search.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'search.php' ? 'active' : ''; ?>">
                            <i class="fas fa-search"></i> Search
                        </a>
                        <?php if ($current_user): ?>
                            <a href="my-groups.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my-groups.php' ? 'active' : ''; ?>">
                                <i class="fas fa-user-friends"></i> My Groups
                            </a>
                            <a href="create-group.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'create-group.php' ? 'active' : ''; ?>">
                                <i class="fas fa-plus"></i> Create
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="nav-actions">
                        <?php if ($current_user): ?>
                            <a href="notifications.php" class="nav-notification">
                                <i class="fas fa-bell"></i>
                                <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
                            </a>
                            
                            <div class="nav-user-menu">
                                <button class="user-menu-toggle" onclick="toggleUserMenu()">
                                    <img src="assets/images/<?php echo $current_user['profile_picture']; ?>" 
                                         alt="Profile" class="user-avatar">
                                    <span class="user-name"><?php echo htmlspecialchars($current_user['display_name'] ?: $current_user['username']); ?></span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                
                                <div class="user-dropdown" id="userDropdown">
                                    <a href="profile.php" class="dropdown-item">
                                        <i class="fas fa-user"></i> My Profile
                                    </a>
                                    <a href="settings.php" class="dropdown-item">
                                        <i class="fas fa-cog"></i> Settings
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a href="logout.php" class="dropdown-item">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="login.php" class="nav-btn btn-outline">Login</a>
                            <a href="register.php" class="nav-btn btn-primary">Sign Up</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <button class="nav-toggle" onclick="toggleMobileMenu()">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </nav>
    </header>

    <style>
    .main-header {
        background: var(--bg-primary);
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
        border-bottom: 1px solid var(--border-color);
    }

    .navbar {
        width: 100%;
    }

    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 70px;
    }

    .nav-brand {
        flex-shrink: 0;
    }

    .brand-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        color: var(--primary-color);
        font-weight: 700;
        font-size: 1.5rem;
    }

    .brand-logo {
        height: 40px;
        width: auto;
    }

    .brand-text {
        font-family: 'Arial Black', sans-serif;
        letter-spacing: -0.5px;
    }

    .nav-menu {
        display: flex;
        align-items: center;
        gap: 2rem;
        flex: 1;
        justify-content: space-between;
        margin-left: 3rem;
    }

    .nav-links {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        color: var(--text-secondary);
        text-decoration: none;
        border-radius: var(--border-radius);
        transition: var(--transition);
        font-weight: 500;
        white-space: nowrap;
    }

    .nav-link:hover,
    .nav-link.active {
        color: var(--primary-color);
        background: var(--bg-secondary);
    }

    .nav-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .nav-notification {
        position: relative;
        color: var(--text-secondary);
        font-size: 1.2rem;
        padding: 0.5rem;
        border-radius: 50%;
        transition: var(--transition);
    }

    .nav-notification:hover {
        color: var(--primary-color);
        background: var(--bg-secondary);
    }

    .notification-badge {
        position: absolute;
        top: 0;
        right: 0;
        background: var(--error-color);
        color: white;
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
        border-radius: 10px;
        min-width: 18px;
        text-align: center;
    }

    .nav-user-menu {
        position: relative;
    }

    .user-menu-toggle {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 1rem;
        background: none;
        border: none;
        border-radius: var(--border-radius);
        cursor: pointer;
        transition: var(--transition);
        color: var(--text-secondary);
    }

    .user-menu-toggle:hover {
        background: var(--bg-secondary);
    }

    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--border-color);
    }

    .user-name {
        font-weight: 500;
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .user-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-md);
        min-width: 200px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: var(--transition);
        z-index: 1000;
        margin-top: 0.5rem;
    }

    .user-dropdown.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: var(--text-secondary);
        text-decoration: none;
        transition: var(--transition);
    }

    .dropdown-item:hover {
        background: var(--bg-secondary);
        color: var(--primary-color);
    }

    .dropdown-divider {
        height: 1px;
        background: var(--border-color);
        margin: 0.5rem 0;
    }

    .nav-btn {
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius);
        text-decoration: none;
        font-weight: 500;
        transition: var(--transition);
        white-space: nowrap;
    }

    .btn-outline {
        color: var(--primary-color);
        border: 1px solid var(--primary-color);
        background: transparent;
    }

    .btn-outline:hover {
        background: var(--primary-color);
        color: var(--text-light);
    }

    .btn-primary {
        background: var(--primary-color);
        color: var(--text-light);
        border: 1px solid var(--primary-color);
    }

    .nav-toggle {
        display: none;
        flex-direction: column;
        gap: 4px;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0.5rem;
    }

    .nav-toggle span {
        width: 25px;
        height: 3px;
        background: var(--text-secondary);
        border-radius: 2px;
        transition: var(--transition);
    }

    .nav-toggle.active span:nth-child(1) {
        transform: rotate(45deg) translate(6px, 6px);
    }

    .nav-toggle.active span:nth-child(2) {
        opacity: 0;
    }

    .nav-toggle.active span:nth-child(3) {
        transform: rotate(-45deg) translate(6px, -6px);
    }

    @media (max-width: 768px) {
        .nav-container {
            padding: 0 1rem;
        }

        .nav-menu {
            position: fixed;
            top: 70px;
            left: 0;
            width: 100%;
            height: calc(100vh - 70px);
            background: var(--bg-primary);
            flex-direction: column;
            justify-content: flex-start;
            padding: 2rem;
            gap: 2rem;
            transform: translateX(-100%);
            transition: var(--transition);
            margin-left: 0;
            border-top: 1px solid var(--border-color);
        }

        .nav-menu.show {
            transform: translateX(0);
        }

        .nav-links {
            flex-direction: column;
            width: 100%;
            gap: 0;
        }

        .nav-link {
            width: 100%;
            justify-content: flex-start;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .nav-actions {
            flex-direction: column;
            width: 100%;
            gap: 1rem;
        }

        .user-menu-toggle {
            width: 100%;
            justify-content: flex-start;
        }

        .user-dropdown {
            position: static;
            opacity: 1;
            visibility: visible;
            transform: none;
            box-shadow: none;
            border: none;
            margin-top: 0;
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
        }

        .nav-btn {
            width: 100%;
            text-align: center;
            padding: 1rem;
        }

        .nav-toggle {
            display: flex;
        }

        .brand-text {
            display: none;
        }
    }

    @media (max-width: 480px) {
        .nav-container {
            height: 60px;
        }

        .nav-menu {
            top: 60px;
            height: calc(100vh - 60px);
        }

        .user-name {
            display: none;
        }
    }
    </style>

    <script>
    function toggleUserMenu() {
        const dropdown = document.getElementById('userDropdown');
        dropdown.classList.toggle('show');
    }

    function toggleMobileMenu() {
        const navMenu = document.getElementById('navMenu');
        const navToggle = document.querySelector('.nav-toggle');
        navMenu.classList.toggle('show');
        navToggle.classList.toggle('active');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const userMenu = document.querySelector('.nav-user-menu');
        const dropdown = document.getElementById('userDropdown');
        
        if (userMenu && !userMenu.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });

    // Load notification count
    <?php if ($current_user): ?>
    function loadNotificationCount() {
        fetch('api/get-notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const unreadCount = data.notifications.filter(n => !n.is_read).length;
                    const badge = document.getElementById('notificationCount');
                    if (unreadCount > 0) {
                        badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                        badge.style.display = 'block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            })
            .catch(error => console.error('Error loading notifications:', error));
    }

    // Load notification count on page load
    document.addEventListener('DOMContentLoaded', loadNotificationCount);

    // Refresh notification count every 30 seconds
    setInterval(loadNotificationCount, 30000);
    <?php endif; ?>
    </script>
