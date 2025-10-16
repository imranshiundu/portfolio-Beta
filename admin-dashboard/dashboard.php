<?php
/**
 * Admin Dashboard - Main Overview
 * Access: Authenticated Admin Users Only
 */

session_start();

// Load backend autoloader
require_once __DIR__ . '/../backend/autoload.php';

// Check if user is authenticated
if (!AuthService::isAuthenticated()) {
    header('Location: login.php');
    exit;
}

// Get dashboard data
try {
    $dashboardService = new DashboardService();
    $stats = $dashboardService->getDashboardStats();
    $recentActivity = $dashboardService->getRecentActivity();
    $recentContacts = $dashboardService->getRecentContactSubmissions();
} catch (Exception $e) {
    // Initialize empty data if service fails
    $stats = [
        'total_projects' => 0,
        'total_blog_posts' => 0,
        'unread_messages' => 0,
        'total_views' => 0
    ];
    $recentActivity = [];
    $recentContacts = [];
    error_log("Dashboard data error: " . $e->getMessage());
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    try {
        $dashboardService->handleBulkAction($_POST);
        header('Location: dashboard.php?success=bulk_action_completed');
        exit;
    } catch (Exception $e) {
        error_log("Bulk action error: " . $e->getMessage());
    }
}

// Get admin name from session
$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Imran Shiundu Admin</title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Charts.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-dashboard">
    <!-- Navigation Sidebar -->
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
            <li class="nav-item active">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="projects.php" class="nav-link">
                    <i class="fas fa-project-diagram"></i>
                    <span class="nav-text">Projects</span>
                    <span class="nav-badge"><?php echo $stats['total_projects']; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="blog.php" class="nav-link">
                    <i class="fas fa-blog"></i>
                    <span class="nav-text">Blog Posts</span>
                    <span class="nav-badge"><?php echo $stats['total_blog_posts']; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="messages.php" class="nav-link">
                    <i class="fas fa-envelope"></i>
                    <span class="nav-text">Messages</span>
                    <span class="nav-badge badge-new"><?php echo $stats['unread_messages']; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span class="nav-text">Settings</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="section-label">Analytics</span>
            </li>
            <li class="nav-item">
                <a href="analytics.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span class="nav-text">Analytics</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="seo.php" class="nav-link">
                    <i class="fas fa-search"></i>
                    <span class="nav-text">SEO</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="admin-user">
                <div class="user-avatar">
                    <i class="fas fa-user-circle fa-2x"></i>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo $adminName; ?></span>
                    <span class="user-role">Administrator</span>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Header -->
        <header class="admin-header">
            <div class="header-left">
                <h1 class="page-title">Dashboard Overview</h1>
                <p class="page-subtitle">Welcome back, <?php echo $adminName; ?>!</p>
            </div>
            
            <div class="header-actions">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search..." id="globalSearch">
                </div>
                
                <div class="header-buttons">
                    <button class="btn btn-icon" id="notificationsBtn" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                    
                    <button class="btn btn-icon" id="fullscreenBtn" aria-label="Toggle fullscreen">
                        <i class="fas fa-expand"></i>
                    </button>
                    
                    <div class="theme-toggle">
                        <button class="btn btn-icon" id="themeToggle" aria-label="Toggle theme">
                            <i class="fas fa-moon"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Stats Overview -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo $stats['total_projects']; ?></h3>
                    <p class="stat-label">Total Projects</p>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>12% from last month</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-blog"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo $stats['total_blog_posts']; ?></h3>
                    <p class="stat-label">Blog Posts</p>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>8% from last month</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo $stats['unread_messages']; ?></h3>
                    <p class="stat-label">Unread Messages</p>
                    <div class="stat-trend negative">
                        <i class="fas fa-arrow-up"></i>
                        <span>5 new today</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo number_format($stats['total_views']); ?></h3>
                    <p class="stat-label">Total Views</p>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>23% from last week</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Charts and Analytics -->
        <section class="charts-section">
            <div class="chart-row">
                <!-- Traffic Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Website Traffic</h3>
                        <div class="chart-controls">
                            <select class="chart-period" id="trafficPeriod">
                                <option value="7d">Last 7 Days</option>
                                <option value="30d" selected>Last 30 Days</option>
                                <option value="90d">Last 90 Days</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="trafficChart"></canvas>
                    </div>
                </div>
                
                <!-- Project Categories Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Projects by Category</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="categoriesChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recent Activity & Quick Actions -->
        <section class="content-grid">
            <!-- Recent Activity -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Recent Activity</h3>
                    <a href="activity.php" class="card-link">View All</a>
                </div>
                <div class="card-body">
                    <div class="activity-list">
                        <?php if (empty($recentActivity)): ?>
                            <div class="empty-state">
                                <i class="fas fa-history"></i>
                                <p>No recent activity</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-<?php echo $activity['icon'] ?? 'circle'; ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p class="activity-text"><?php echo $activity['description'] ?? 'No description'; ?></p>
                                        <span class="activity-time"><?php echo $activity['time_ago'] ?? 'Recently'; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <a href="projects.php?action=create" class="quick-action">
                            <div class="action-icon primary">
                                <i class="fas fa-plus"></i>
                            </div>
                            <span class="action-text">Add Project</span>
                        </a>
                        
                        <a href="blog.php?action=create" class="quick-action">
                            <div class="action-icon success">
                                <i class="fas fa-edit"></i>
                            </div>
                            <span class="action-text">Write Post</span>
                        </a>
                        
                        <a href="settings.php" class="quick-action">
                            <div class="action-icon warning">
                                <i class="fas fa-cog"></i>
                            </div>
                            <span class="action-text">Site Settings</span>
                        </a>
                        
                        <a href="analytics.php" class="quick-action">
                            <div class="action-icon info">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <span class="action-text">View Analytics</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recent Contact Messages -->
        <section class="content-section">
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Recent Contact Messages</h3>
                    <a href="messages.php" class="card-link">View All Messages</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentContacts)): ?>
                        <div class="empty-state">
                            <i class="fas fa-envelope"></i>
                            <p>No recent messages</p>
                        </div>
                    <?php else: ?>
                        <form method="POST" id="messagesForm">
                            <div class="messages-table">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" id="selectAllMessages">
                                            </th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Project Type</th>
                                            <th>Message</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentContacts as $message): ?>
                                            <tr class="<?php echo ($message['status'] ?? 'new') === 'new' ? 'unread' : ''; ?>">
                                                <td>
                                                    <input type="checkbox" name="message_ids[]" value="<?php echo $message['id'] ?? ''; ?>">
                                                </td>
                                                <td>
                                                    <div class="user-info">
                                                        <span class="user-name"><?php echo htmlspecialchars($message['name'] ?? 'Unknown'); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($message['email'] ?? 'No email'); ?></td>
                                                <td>
                                                    <span class="project-tag"><?php echo ucfirst(str_replace('-', ' ', $message['project_type'] ?? 'general')); ?></span>
                                                </td>
                                                <td>
                                                    <div class="message-preview">
                                                        <?php echo substr(strip_tags($message['message'] ?? 'No message'), 0, 50); ?>...
                                                    </div>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($message['created_at'] ?? 'now')); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $message['status'] ?? 'new'; ?>">
                                                        <?php echo ucfirst($message['status'] ?? 'new'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button type="button" class="btn btn-icon btn-sm view-message" 
                                                                data-message-id="<?php echo $message['id'] ?? ''; ?>" 
                                                                title="View Message">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-icon btn-sm mark-read" 
                                                                data-message-id="<?php echo $message['id'] ?? ''; ?>"
                                                                title="Mark as Read">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-icon btn-sm btn-danger delete-message" 
                                                                data-message-id="<?php echo $message['id'] ?? ''; ?>"
                                                                title="Delete Message">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Bulk Actions -->
                            <div class="bulk-actions" id="messagesBulkActions" style="display: none;">
                                <select name="bulk_action" class="bulk-action-select">
                                    <option value="">Choose Action...</option>
                                    <option value="mark_read">Mark as Read</option>
                                    <option value="mark_unread">Mark as Unread</option>
                                    <option value="delete">Delete Selected</option>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- System Status -->
        <section class="content-section">
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">System Status</h3>
                </div>
                <div class="card-body">
                    <div class="system-status-grid">
                        <div class="status-item">
                            <div class="status-indicator online"></div>
                            <span class="status-label">Website</span>
                            <span class="status-value">Online</span>
                        </div>
                        <div class="status-item">
                            <div class="status-indicator online"></div>
                            <span class="status-label">Database</span>
                            <span class="status-value">Connected</span>
                        </div>
                        <div class="status-item">
                            <div class="status-indicator online"></div>
                            <span class="status-label">API</span>
                            <span class="status-value">Running</span>
                        </div>
                        <div class="status-item">
                            <div class="status-indicator warning"></div>
                            <span class="status-label">Storage</span>
                            <span class="status-value">75% Used</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Notifications Panel -->
    <div class="notifications-panel" id="notificationsPanel">
        <div class="panel-header">
            <h3>Notifications</h3>
            <button class="panel-close" id="closeNotifications">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="panel-body">
            <div class="notification-list">
                <div class="notification-item unread">
                    <div class="notification-icon">
                        <i class="fas fa-envelope text-primary"></i>
                    </div>
                    <div class="notification-content">
                        <p class="notification-text">New contact message from John Doe</p>
                        <span class="notification-time">2 minutes ago</span>
                    </div>
                </div>
                <div class="notification-item">
                    <div class="notification-icon">
                        <i class="fas fa-project-diagram text-success"></i>
                    </div>
                    <div class="notification-content">
                        <p class="notification-text">Project "Melora" was updated</p>
                        <span class="notification-time">1 hour ago</span>
                    </div>
                </div>
                <div class="notification-item">
                    <div class="notification-icon">
                        <i class="fas fa-blog text-info"></i>
                    </div>
                    <div class="notification-content">
                        <p class="notification-text">New blog comment awaiting moderation</p>
                        <span class="notification-time">3 hours ago</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Message View Modal -->
    <div class="modal" id="messageModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Contact Message</h3>
                <button class="modal-close" id="closeMessageModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="messageModalBody">
                <!-- Dynamic content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="closeModalBtn">Close</button>
                <button class="btn btn-primary" id="replyMessageBtn">Reply</button>
                <button class="btn btn-warning" id="markReadBtn">Mark as Read</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/admin.js"></script>
    <script src="assets/js/dashboard.js"></script>
    
    <script>
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Traffic Chart
            const trafficCtx = document.getElementById('trafficChart');
            if (trafficCtx) {
                const trafficChart = new Chart(trafficCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                        datasets: [{
                            label: 'Website Visitors',
                            data: [1200, 1900, 1500, 2200, 1800, 2500],
                            borderColor: '#0D47A1',
                            backgroundColor: 'rgba(13, 71, 161, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // Categories Chart
            const categoriesCtx = document.getElementById('categoriesChart');
            if (categoriesCtx) {
                const categoriesChart = new Chart(categoriesCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Frontend', 'Backend', 'Full Stack', 'Mobile'],
                        datasets: [{
                            data: [8, 5, 12, 3],
                            backgroundColor: [
                                '#0D47A1',
                                '#FF6D00',
                                '#2E7D32',
                                '#6A1B9A'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            // Basic dashboard functionality
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    document.body.classList.toggle('dark-mode');
                    const icon = this.querySelector('i');
                    icon.className = document.body.classList.contains('dark-mode') ? 'fas fa-sun' : 'fas fa-moon';
                });
            }

            // Notifications panel
            const notificationsBtn = document.getElementById('notificationsBtn');
            const notificationsPanel = document.getElementById('notificationsPanel');
            const closeNotifications = document.getElementById('closeNotifications');
            
            if (notificationsBtn && notificationsPanel) {
                notificationsBtn.addEventListener('click', function() {
                    notificationsPanel.style.display = 'block';
                });
                
                if (closeNotifications) {
                    closeNotifications.addEventListener('click', function() {
                        notificationsPanel.style.display = 'none';
                    });
                }
            }

            // Message modal
            const messageModal = document.getElementById('messageModal');
            const closeMessageModal = document.getElementById('closeMessageModal');
            const closeModalBtn = document.getElementById('closeModalBtn');
            
            if (closeMessageModal && messageModal) {
                closeMessageModal.addEventListener('click', function() {
                    messageModal.style.display = 'none';
                });
            }
            
            if (closeModalBtn && messageModal) {
                closeModalBtn.addEventListener('click', function() {
                    messageModal.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>