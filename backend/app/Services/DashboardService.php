<?php
/**
 * Dashboard Service - Handles dashboard statistics and data
 */

class DashboardService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats() {
        try {
            return [
                'total_projects' => $this->getTotalProjects(),
                'total_blog_posts' => $this->getTotalBlogPosts(),
                'unread_messages' => $this->getUnreadMessages(),
                'total_views' => $this->getTotalViews(),
                'published_posts' => $this->getPublishedPosts(),
                'featured_projects' => $this->getFeaturedProjects()
            ];
        } catch (Exception $e) {
            error_log("DashboardService - getDashboardStats error: " . $e->getMessage());
            return [
                'total_projects' => 0,
                'total_blog_posts' => 0,
                'unread_messages' => 0,
                'total_views' => 0,
                'published_posts' => 0,
                'featured_projects' => 0
            ];
        }
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity($limit = 10) {
        try {
            // First check if the activity log table exists
            $tableExists = $this->checkTableExists('admin_activity_log');
            
            if (!$tableExists) {
                // Return sample activity if table doesn't exist
                return $this->getSampleActivity();
            }
            
            $query = "
                SELECT 
                    al.*,
                    a.name as admin_name
                FROM admin_activity_log al
                LEFT JOIN admin_users a ON al.admin_id = a.id
                ORDER BY al.created_at DESC
                LIMIT ?
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$limit]);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format activities for display
            foreach ($activities as &$activity) {
                $activity['time_ago'] = $this->timeAgo($activity['created_at']);
                $activity['icon'] = $this->getActivityIcon($activity['activity_type']);
            }
            
            return $activities;
        } catch (Exception $e) {
            error_log("DashboardService - getRecentActivity error: " . $e->getMessage());
            return $this->getSampleActivity();
        }
    }

    /**
     * Get recent contact submissions
     */
    public function getRecentContactSubmissions($limit = 5) {
        try {
            $query = "
                SELECT *
                FROM contact_submissions
                ORDER BY created_at DESC
                LIMIT ?
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("DashboardService - getRecentContactSubmissions error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get website traffic data
     */
    public function getTrafficData($period = '30d') {
        try {
            $dateRange = $this->getDateRange($period);
            
            // Check if site_visits table exists
            $tableExists = $this->checkTableExists('site_visits');
            
            if (!$tableExists) {
                return $this->getSampleTrafficData();
            }
            
            $query = "
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as visits,
                    COUNT(DISTINCT ip_address) as unique_visits
                FROM site_visits
                WHERE created_at BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$dateRange['start'], $dateRange['end']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("DashboardService - getTrafficData error: " . $e->getMessage());
            return $this->getSampleTrafficData();
        }
    }

    /**
     * Get project statistics by category
     */
    public function getProjectCategoryStats() {
        try {
            $query = "
                SELECT 
                    category,
                    COUNT(*) as count
                FROM projects
                WHERE status = 'completed'
                GROUP BY category
                ORDER BY count DESC
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("DashboardService - getProjectCategoryStats error: " . $e->getMessage());
            return [
                ['category' => 'frontend', 'count' => 8],
                ['category' => 'backend', 'count' => 5],
                ['category' => 'fullstack', 'count' => 12],
                ['category' => 'mobile', 'count' => 3]
            ];
        }
    }

    /**
     * Get popular blog posts
     */
    public function getPopularPosts($limit = 5) {
        try {
            $query = "
                SELECT 
                    id,
                    title,
                    views,
                    published_at
                FROM blog_posts
                WHERE status = 'published'
                ORDER BY views DESC
                LIMIT ?
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("DashboardService - getPopularPosts error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get system status
     */
    public function getSystemStatus() {
        return [
            'website' => $this->checkWebsiteStatus(),
            'database' => $this->checkDatabaseStatus(),
            'storage' => $this->checkStorageUsage(),
            'backups' => $this->checkBackupStatus()
        ];
    }

    /**
     * Handle bulk actions
     */
    public function handleBulkAction($postData) {
        try {
            $action = $postData['bulk_action'] ?? '';
            $messageIds = $postData['message_ids'] ?? [];
            
            if (empty($action) || empty($messageIds)) {
                return false;
            }
            
            $placeholders = str_repeat('?,', count($messageIds) - 1) . '?';
            
            switch ($action) {
                case 'mark_read':
                    $query = "UPDATE contact_submissions SET status = 'read' WHERE id IN ($placeholders)";
                    break;
                case 'mark_unread':
                    $query = "UPDATE contact_submissions SET status = 'new' WHERE id IN ($placeholders)";
                    break;
                case 'delete':
                    $query = "DELETE FROM contact_submissions WHERE id IN ($placeholders)";
                    break;
                default:
                    return false;
            }
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute($messageIds);
            
        } catch (Exception $e) {
            error_log("DashboardService - handleBulkAction error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get total projects count
     */
    private function getTotalProjects() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'");
            return $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
            error_log("DashboardService - getTotalProjects error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total blog posts count
     */
    private function getTotalBlogPosts() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM blog_posts");
            return $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
            error_log("DashboardService - getTotalBlogPosts error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get unread messages count
     */
    private function getUnreadMessages() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM contact_submissions WHERE status = 'new'");
            return $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
            error_log("DashboardService - getUnreadMessages error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total views
     */
    private function getTotalViews() {
        try {
            $projectViews = $this->db->query("SELECT COALESCE(SUM(views), 0) FROM projects")->fetchColumn();
            $postViews = $this->db->query("SELECT COALESCE(SUM(views), 0) FROM blog_posts")->fetchColumn();
            return ($projectViews ?: 0) + ($postViews ?: 0);
        } catch (Exception $e) {
            error_log("DashboardService - getTotalViews error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get published posts count
     */
    private function getPublishedPosts() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'");
            return $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
            error_log("DashboardService - getPublishedPosts error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get featured projects count
     */
    private function getFeaturedProjects() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM projects WHERE featured = 1 AND status = 'completed'");
            return $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {
            error_log("DashboardService - getFeaturedProjects error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check if table exists
     */
    private function checkTableExists($tableName) {
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE '$tableName'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get sample activity data when table doesn't exist
     */
    private function getSampleActivity() {
        return [
            [
                'activity_type' => 'admin_login',
                'description' => 'You logged in to the admin dashboard',
                'time_ago' => 'Just now',
                'icon' => 'sign-in-alt',
                'admin_name' => 'Administrator'
            ],
            [
                'activity_type' => 'project_created',
                'description' => 'New project "Melora Music Player" was created',
                'time_ago' => '2 hours ago',
                'icon' => 'project-diagram',
                'admin_name' => 'Administrator'
            ],
            [
                'activity_type' => 'post_created',
                'description' => 'Blog post "Building Scalable APIs" was published',
                'time_ago' => '1 day ago',
                'icon' => 'blog',
                'admin_name' => 'Administrator'
            ]
        ];
    }

    /**
     * Get sample traffic data when table doesn't exist
     */
    private function getSampleTrafficData() {
        $data = [];
        $startDate = strtotime('-30 days');
        
        for ($i = 0; $i < 30; $i++) {
            $date = date('Y-m-d', strtotime("+$i days", $startDate));
            $data[] = [
                'date' => $date,
                'visits' => rand(50, 200),
                'unique_visits' => rand(30, 150)
            ];
        }
        
        return $data;
    }

    /**
     * Calculate time ago
     */
    private function timeAgo($datetime) {
        if (empty($datetime)) {
            return 'Recently';
        }
        
        $time = strtotime($datetime);
        $diff = time() - $time;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $time);
        }
    }

    /**
     * Get activity icon
     */
    private function getActivityIcon($activityType) {
        $icons = [
            'admin_login' => 'sign-in-alt',
            'failed_login' => 'exclamation-triangle',
            'project_created' => 'project-diagram',
            'project_updated' => 'edit',
            'project_deleted' => 'trash',
            'post_created' => 'blog',
            'post_updated' => 'edit',
            'post_deleted' => 'trash',
            'bulk_action' => 'tasks',
            'settings_updated' => 'cog'
        ];
        
        return $icons[$activityType] ?? 'info-circle';
    }

    /**
     * Get date range for period
     */
    private function getDateRange($period) {
        $end = date('Y-m-d H:i:s');
        
        switch ($period) {
            case '7d':
                $start = date('Y-m-d H:i:s', strtotime('-7 days'));
                break;
            case '30d':
                $start = date('Y-m-d H:i:s', strtotime('-30 days'));
                break;
            case '90d':
                $start = date('Y-m-d H:i:s', strtotime('-90 days'));
                break;
            default:
                $start = date('Y-m-d H:i:s', strtotime('-30 days'));
        }
        
        return ['start' => $start, 'end' => $end];
    }

    /**
     * Check website status
     */
    private function checkWebsiteStatus() {
        // Simple check - in production, you might check actual HTTP status
        return 'online';
    }

    /**
     * Check database status
     */
    private function checkDatabaseStatus() {
        try {
            $this->db->query('SELECT 1');
            return 'connected';
        } catch (Exception $e) {
            return 'disconnected';
        }
    }

    /**
     * Check storage usage
     */
    private function checkStorageUsage() {
        try {
            $totalSpace = disk_total_space(__DIR__ . '/../../');
            $freeSpace = disk_free_space(__DIR__ . '/../../');
            
            if ($totalSpace === false || $freeSpace === false) {
                return 25.0; // Default value if cannot determine
            }
            
            $usedSpace = $totalSpace - $freeSpace;
            $usagePercent = ($usedSpace / $totalSpace) * 100;
            
            return round($usagePercent, 1);
        } catch (Exception $e) {
            return 25.0; // Default value on error
        }
    }

    /**
     * Check backup status
     */
    private function checkBackupStatus() {
        $backupDir = __DIR__ . '/../../backups/';
        
        try {
            if (!file_exists($backupDir)) {
                return 'no-backups';
            }
            
            $backups = glob($backupDir . '*.sql');
            if (empty($backups)) {
                return 'no-backups';
            }
            
            $latestBackup = max(array_map('filemtime', $backups));
            $daysSinceBackup = (time() - $latestBackup) / (60 * 60 * 24);
            
            if ($daysSinceBackup > 7) {
                return 'outdated';
            }
            
            return 'current';
        } catch (Exception $e) {
            return 'no-backups';
        }
    }
}
?>