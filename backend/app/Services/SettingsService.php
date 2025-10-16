<?php
/**
 * Settings Service - Handles site settings and configuration
 */

class SettingsService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all settings
     */
    public function getAllSettings() {
        $settings = [];
        
        // Get settings from database
        $stmt = $this->db->query("SELECT key_name, key_value FROM settings");
        $dbSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Parse JSON values and organize by category
        foreach ($dbSettings as $key => $value) {
            $parsedValue = json_decode($value, true);
            $settings[$key] = $parsedValue !== null ? $parsedValue : $value;
        }
        
        // Return organized settings
        return [
            'site' => $this->getSiteSettings($settings),
            'personal' => $this->getPersonalSettings($settings),
            'social' => $this->getSocialSettings($settings),
            'contact' => $this->getContactSettings($settings),
            'seo' => $this->getSeoSettings($settings),
            'appearance' => $this->getAppearanceSettings($settings),
            'security' => $this->getSecuritySettings($settings)
        ];
    }

    /**
     * Save personal settings
     */
    public function savePersonalSettings($data) {
        $settings = [
            'name' => $data['personal_name'] ?? '',
            'title' => $data['personal_title'] ?? '',
            'bio' => $data['personal_bio'] ?? '',
            'location' => $data['personal_location'] ?? '',
            'availability' => $data['personal_availability'] ?? 'available'
        ];
        
        return $this->saveSetting('personal_info', $settings);
    }

    /**
     * Save social media settings
     */
    public function saveSocialSettings($data) {
        $settings = [
            'github' => $data['social_github'] ?? '',
            'linkedin' => $data['social_linkedin'] ?? '',
            'twitter' => $data['social_twitter'] ?? '',
            'youtube' => $data['social_youtube'] ?? '',
            'instagram' => $data['social_instagram'] ?? '',
            'facebook' => $data['social_facebook'] ?? '',
            'stackoverflow' => $data['social_stackoverflow'] ?? '',
            'website' => $data['social_website'] ?? ''
        ];
        
        return $this->saveSetting('social_links', $settings);
    }

    /**
     * Save site settings
     */
    public function saveSiteSettings($data) {
        $settings = [
            'title' => $data['site_title'] ?? '',
            'description' => $data['site_description'] ?? '',
            'keywords' => $data['site_keywords'] ?? '',
            'features' => [
                'blog' => isset($data['feature_blog']),
                'contact' => isset($data['feature_contact']),
                'dark_mode' => isset($data['feature_dark_mode']),
                'analytics' => isset($data['feature_analytics'])
            ],
            'maintenance_mode' => isset($data['maintenance_mode']),
            'maintenance_text' => $data['maintenance_text'] ?? ''
        ];
        
        return $this->saveSetting('site_config', $settings);
    }

    /**
     * Save contact settings
     */
    public function saveContactSettings($data) {
        $settings = [
            'email' => $data['contact_email'] ?? '',
            'phone' => $data['contact_phone'] ?? '',
            'subtitle' => $data['contact_subtitle'] ?? '',
            'description' => $data['contact_description'] ?? '',
            'response_time' => $data['contact_response_time'] ?? '24',
            'projects_completed' => $data['contact_projects_completed'] ?? '50+',
            'collaboration_count' => $data['contact_collaboration_count'] ?? '15+'
        ];
        
        return $this->saveSetting('contact_info', $settings);
    }

    /**
     * Save SEO settings
     */
    public function saveSeoSettings($data) {
        $settings = [
            'meta_title' => $data['seo_meta_title'] ?? '',
            'meta_description' => $data['seo_meta_description'] ?? '',
            'ga_tracking_id' => $data['seo_ga_tracking_id'] ?? '',
            'gtm_id' => $data['seo_gtm_id'] ?? ''
        ];
        
        return $this->saveSetting('seo_config', $settings);
    }

    /**
     * Save appearance settings
     */
    public function saveAppearanceSettings($data) {
        $settings = [
            'colors' => [
                'primary' => $data['color_primary'] ?? '#0D47A1',
                'secondary' => $data['color_secondary'] ?? '#FF6D00',
                'accent' => $data['color_accent'] ?? '#2E7D32'
            ],
            'fonts' => [
                'primary' => $data['font_primary'] ?? 'Inter',
                'secondary' => $data['font_secondary'] ?? 'Source Sans Pro'
            ],
            'layout' => $data['layout_type'] ?? 'modern',
            'animations' => isset($data['enable_animations'])
        ];
        
        return $this->saveSetting('appearance', $settings);
    }

    /**
     * Save security settings
     */
    public function saveSecuritySettings($data) {
        $settings = [
            'session_timeout' => $data['admin_session_timeout'] ?? '60',
            'enable_2fa' => isset($data['enable_2fa']),
            'login_notifications' => isset($data['login_notifications']),
            'password_min_length' => $data['password_min_length'] ?? '12',
            'password_require_special' => isset($data['password_require_special'])
        ];
        
        return $this->saveSetting('security', $settings);
    }

    /**
     * Handle image upload
     */
    public function handleImageUpload($files, $data) {
        $type = $data['image_type'] ?? '';
        $uploadPath = '../storage/';
        
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $maxSize = 5 * 1024 * 1024;
        
        $file = $files['image'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("No file uploaded or upload error");
        }
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Invalid file type. Only JPG, PNG, and WebP are allowed.");
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception("File too large. Maximum size is 5MB.");
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $type . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadPath . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception("Failed to upload image.");
        }
        
        // Update setting based on image type
        switch ($type) {
            case 'avatar':
                $this->saveSetting('personal_avatar', $filepath);
                break;
            case 'og_image':
                $this->saveSetting('seo_og_image', $filepath);
                break;
        }
        
        return $filepath;
    }

    /**
     * Create database backup
     */
    public function createBackup() {
        $backupDir = '../backups/';
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Get database configuration
        $config = include '../config/database.php';
        $dbHost = $config['host'];
        $dbName = $config['database'];
        $dbUser = $config['username'];
        $dbPass = $config['password'];
        
        // Create backup using mysqldump
        $command = "mysqldump --host={$dbHost} --user={$dbUser} --password={$dbPass} {$dbName} > {$backupFile}";
        system($command, $output);
        
        if ($output !== 0) {
            throw new Exception("Failed to create database backup");
        }
        
        return $backupFile;
    }

    /**
     * Clear application cache
     */
    public function clearCache() {
        $cacheDirs = [
            '../storage/cache/',
            '../storage/views/',
            '../storage/sessions/'
        ];
        
        foreach ($cacheDirs as $dir) {
            if (file_exists($dir)) {
                $this->clearDirectory($dir);
            }
        }
        
        return true;
    }

    /**
     * Get site settings
     */
    private function getSiteSettings($settings) {
        return array_merge([
            'title' => 'Imran Shiundu - Full Stack Developer',
            'description' => 'Full Stack Developer specializing in modern web technologies',
            'keywords' => 'developer, portfolio, web development',
            'features' => [
                'blog' => true,
                'contact' => true,
                'dark_mode' => true,
                'analytics' => true
            ],
            'maintenance_mode' => false,
            'maintenance_text' => 'Site is under maintenance. Please check back later.'
        ], $settings['site_config'] ?? []);
    }

    /**
     * Get personal settings
     */
    private function getPersonalSettings($settings) {
        return array_merge([
            'name' => 'Imran Shiundu',
            'title' => 'Full Stack Developer',
            'bio' => 'Passionate developer with expertise in modern web technologies...',
            'location' => 'Nairobi, Kenya',
            'availability' => 'available',
            'avatar' => $settings['personal_avatar'] ?? '../assets/images/default-avatar.jpg'
        ], $settings['personal_info'] ?? []);
    }

    /**
     * Get social settings
     */
    private function getSocialSettings($settings) {
        return array_merge([
            'github' => '',
            'linkedin' => '',
            'twitter' => '',
            'youtube' => '',
            'instagram' => '',
            'facebook' => '',
            'stackoverflow' => '',
            'website' => ''
        ], $settings['social_links'] ?? []);
    }

    /**
     * Get contact settings
     */
    private function getContactSettings($settings) {
        return array_merge([
            'email' => 'contact@imranshiundu.eu',
            'phone' => '',
            'subtitle' => "Let's discuss your next project",
            'description' => "I'm always interested in new opportunities and collaborations",
            'response_time' => '24',
            'projects_completed' => '50+',
            'collaboration_count' => '15+'
        ], $settings['contact_info'] ?? []);
    }

    /**
     * Get SEO settings
     */
    private function getSeoSettings($settings) {
        return array_merge([
            'meta_title' => '',
            'meta_description' => '',
            'ga_tracking_id' => '',
            'gtm_id' => '',
            'og_image' => $settings['seo_og_image'] ?? '../assets/images/default-og.jpg'
        ], $settings['seo_config'] ?? []);
    }

    /**
     * Get appearance settings
     */
    private function getAppearanceSettings($settings) {
        return array_merge([
            'colors' => [
                'primary' => '#0D47A1',
                'secondary' => '#FF6D00',
                'accent' => '#2E7D32'
            ],
            'fonts' => [
                'primary' => 'Inter',
                'secondary' => 'Source Sans Pro'
            ],
            'layout' => 'modern',
            'animations' => true
        ], $settings['appearance'] ?? []);
    }

    /**
     * Get security settings
     */
    private function getSecuritySettings($settings) {
        return array_merge([
            'session_timeout' => '60',
            'enable_2fa' => false,
            'login_notifications' => true,
            'password_min_length' => '12',
            'password_require_special' => true
        ], $settings['security'] ?? []);
    }

    /**
     * Save setting to database
     */
    private function saveSetting($key, $value) {
        $jsonValue = is_array($value) ? json_encode($value) : $value;
        
        // Check if setting exists
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM settings WHERE key_name = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            $stmt = $this->db->prepare("UPDATE settings SET key_value = ?, updated_at = NOW() WHERE key_name = ?");
        } else {
            $stmt = $this->db->prepare("INSERT INTO settings (key_name, key_value) VALUES (?, ?)");
        }
        
        $result = $stmt->execute([$jsonValue, $key]);
        
        // Log activity
        if ($result) {
            $this->logActivity('settings_updated', "Updated setting: $key");
        }
        
        return $result;
    }

    /**
     * Clear directory contents
     */
    private function clearDirectory($dir) {
        $files = glob($dir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Log activity
     */
    private function logActivity($type, $description) {
        $adminId = $_SESSION['admin_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt = $this->db->prepare("
            INSERT INTO admin_activity_log (admin_id, activity_type, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$adminId, $type, $description, $ipAddress, $userAgent]);
    }
}
?>