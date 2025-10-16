<?php
/**
 * Sidebar Partial - Included in all admin pages
 */

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="admin-sidebar">
    <div class="sidebar-header">
        <div class="admin-logo">
            <i class="fas fa-code"></i>
            <span class="logo-text">Imran Shiundu</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <ul class="sidebar-nav">
        <li class="nav-item <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-home"></i>
                <span class="nav-text">Dashboard</span>
            </a>
        </li>
        <li class="nav-item <?php echo $currentPage === 'projects.php' ? 'active' : ''; ?>">
            <a href="projects.php" class="nav-link">
                <i class="fas fa-project-diagram"></i>
                <span class="nav-text">Projects</span>
            </a>
        </li>
        <li class="nav-item <?php echo $currentPage === 'blog.php' ? 'active' : ''; ?>">
            <a href="blog.php" class="nav-link">
                <i class="fas fa-blog"></i>
                <span class="nav-text">Blog Posts</span>
            </a>
        </li>
        <li class="nav-item <?php echo $currentPage === 'messages.php' ? 'active' : ''; ?>">
            <a href="messages.php" class="nav-link">
                <i class="fas fa-envelope"></i>
                <span class="nav-text">Messages</span>
            </a>
        </li>
        <li class="nav-item <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
            <a href="settings.php" class="nav-link">
                <i class="fas fa-cog"></i>
                <span class="nav-text">Settings</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <div class="admin-user">
            <div class="user-avatar">
                <img src="../assets/images/default-avatar.jpg" alt="Admin Avatar">
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></span>
                <span class="user-role">Administrator</span>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</nav>