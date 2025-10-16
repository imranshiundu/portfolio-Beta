<?php
/**
 * Authentication Service - Handles admin authentication and security
 */

// Load required dependencies
require_once __DIR__ . '/../Core/Database.php';

class AuthService {
    private static $sessionTimeout = 3600; // 1 hour
    private static $maxLoginAttempts = 5;
    private static $lockoutTime = 900; // 15 minutes

    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        // FIXED: Check session status properly and start only once
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                session_start();
            } else {
                // If headers already sent, we can't start session safely
                return false;
            }
        }
        
        if (!isset($_SESSION['admin_authenticated']) || !$_SESSION['admin_authenticated']) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity']) > self::$sessionTimeout) {
            self::logout();
            return false;
        }
        
        // Check if user still exists and is active
        if (!isset($_SESSION['admin_id']) || !self::validateAdminUser($_SESSION['admin_id'])) {
            self::logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        return true;
    }

    /**
     * Authenticate user
     */
    public static function login($email, $password, $remember = false) {
        // Check for lockout
        if (self::isLockedOut($email)) {
            throw new Exception("Too many failed attempts. Please try again later.");
        }
        
        // Validate credentials
        $admin = self::validateCredentials($email, $password);
        
        if ($admin) {
            // Start session - FIXED: Check if headers already sent
            if (session_status() === PHP_SESSION_NONE) {
                if (!headers_sent()) {
                    session_start();
                } else {
                    throw new Exception("Cannot start session - headers already sent");
                }
            }
            
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['last_activity'] = time();
            
            // Set longer session if remember me is checked
            if ($remember) {
                self::$sessionTimeout = 30 * 24 * 3600; // 30 days
                session_set_cookie_params(self::$sessionTimeout);
            }
            
            // Regenerate session ID for security - ONLY if no output has been sent
            if (!headers_sent()) {
                session_regenerate_id(true);
            }
            
            // Clear failed attempts
            self::clearFailedAttempts($email);
            
            // Update last login
            self::updateLastLogin($admin['id']);
            
            return true;
        } else {
            // Record failed attempt
            self::recordFailedAttempt($email);
            return false;
        }
    }

    /**
     * Logout user
     */
    public static function logout() {
        // FIXED: Only start session if not already started and headers not sent
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                session_start();
            } else {
                return; // Can't logout properly if headers sent
            }
        }
        
        // Clear all session variables
        $_SESSION = array();
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }

    /**
     * Validate admin credentials
     */
    private static function validateCredentials($email, $password) {
        try {
            $db = Database::getInstance();
            
            $stmt = $db->prepare("
                SELECT id, name, email, password, is_active, last_login 
                FROM admin_users 
                WHERE email = ? AND is_active = 1
            ");
            
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin && password_verify($password, $admin['password'])) {
                // Remove password from returned array
                unset($admin['password']);
                return $admin;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("AuthService - validateCredentials error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate admin user exists and is active
     */
    private static function validateAdminUser($adminId) {
        try {
            $db = Database::getInstance();
            
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM admin_users 
                WHERE id = ? AND is_active = 1
            ");
            
            $stmt->execute([$adminId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("AuthService - validateAdminUser error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if account is locked out
     */
    private static function isLockedOut($email) {
        try {
            $db = Database::getInstance();
            
            $stmt = $db->prepare("
                SELECT COUNT(*) as attempts, MAX(created_at) as last_attempt
                FROM login_attempts 
                WHERE email = ? AND success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            
            $stmt->execute([$email, self::$lockoutTime]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['attempts'] >= self::$maxLoginAttempts;
        } catch (Exception $e) {
            error_log("AuthService - isLockedOut error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record failed login attempt
     */
    private static function recordFailedAttempt($email) {
        try {
            $db = Database::getInstance();
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $stmt = $db->prepare("
                INSERT INTO login_attempts (email, success, ip_address, user_agent) 
                VALUES (?, 0, ?, ?)
            ");
            
            $stmt->execute([$email, $ipAddress, $userAgent]);
            
            // Log activity
            self::logActivity('failed_login', "Failed login attempt for: $email");
        } catch (Exception $e) {
            error_log("AuthService - recordFailedAttempt error: " . $e->getMessage());
        }
    }

    /**
     * Clear failed attempts
     */
    private static function clearFailedAttempts($email) {
        try {
            $db = Database::getInstance();
            
            $stmt = $db->prepare("
                DELETE FROM login_attempts 
                WHERE email = ? AND success = 0
            ");
            
            $stmt->execute([$email]);
        } catch (Exception $e) {
            error_log("AuthService - clearFailedAttempts error: " . $e->getMessage());
        }
    }

    /**
     * Update last login
     */
    private static function updateLastLogin($adminId) {
        try {
            $db = Database::getInstance();
            
            $stmt = $db->prepare("
                UPDATE admin_users 
                SET last_login = NOW(), login_count = COALESCE(login_count, 0) + 1 
                WHERE id = ?
            ");
            
            $stmt->execute([$adminId]);
        } catch (Exception $e) {
            error_log("AuthService - updateLastLogin error: " . $e->getMessage());
        }
    }

    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        // FIXED: Check headers before starting session
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                session_start();
            } else {
                return null; // Can't generate token if headers sent
            }
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        // FIXED: Check headers before starting session
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                session_start();
            } else {
                return false; // Can't validate token if headers sent
            }
        }
        
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get last login attempt
     */
    public static function getLastLoginAttempt() {
        try {
            $db = Database::getInstance();
            
            $stmt = $db->prepare("
                SELECT created_at 
                FROM login_attempts 
                WHERE email = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            
            $email = $_SESSION['admin_email'] ?? '';
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? self::formatTimeAgo($result['created_at']) : 'Never';
        } catch (Exception $e) {
            error_log("AuthService - getLastLoginAttempt error: " . $e->getMessage());
            return 'Unknown';
        }
    }

    /**
     * Change admin password
     */
    public static function changePassword($adminId, $currentPassword, $newPassword) {
        try {
            $db = Database::getInstance();
            
            // Verify current password
            $stmt = $db->prepare("SELECT password FROM admin_users WHERE id = ?");
            $stmt->execute([$adminId]);
            $currentHash = $stmt->fetchColumn();
            
            if (!$currentHash || !password_verify($currentPassword, $currentHash)) {
                throw new Exception("Current password is incorrect");
            }
            
            // Validate new password
            if (strlen($newPassword) < 8) {
                throw new Exception("New password must be at least 8 characters long");
            }
            
            // Update password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE admin_users SET password = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$newHash, $adminId]);
            
            if ($result) {
                self::logActivity('password_changed', "Admin user changed password");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("AuthService - changePassword error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create admin user (for initial setup)
     */
    public static function createAdminUser($name, $email, $password) {
        try {
            $db = Database::getInstance();
            
            // Check if admin user already exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM admin_users");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                throw new Exception("Admin user already exists");
            }
            
            // Validate input
            if (strlen($password) < 8) {
                throw new Exception("Password must be at least 8 characters long");
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address");
            }
            
            // Create admin user
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO admin_users (name, email, password, is_active) 
                VALUES (?, ?, ?, 1)
            ");
            
            $result = $stmt->execute([$name, $email, $passwordHash]);
            
            if ($result) {
                self::logActivity('admin_created', "Initial admin user created: $email");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("AuthService - createAdminUser error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Format time ago
     */
    private static function formatTimeAgo($datetime) {
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
        } else {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
    }

    /**
     * Log activity
     */
    public static function logActivity($type, $description) {
        try {
            $db = Database::getInstance();
            $adminId = $_SESSION['admin_id'] ?? null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $stmt = $db->prepare("
                INSERT INTO admin_activity_log (admin_id, activity_type, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$adminId, $type, $description, $ipAddress, $userAgent]);
        } catch (Exception $e) {
            error_log("AuthService - logActivity error: " . $e->getMessage());
        }
    }

    /**
     * Get security settings
     */
    public static function getSecuritySettings() {
        try {
            $db = Database::getInstance();
            
            $stmt = $db->prepare("SELECT key_value FROM settings WHERE key_name = 'security'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return json_decode($result['key_value'], true);
            }
        } catch (Exception $e) {
            error_log("AuthService - getSecuritySettings error: " . $e->getMessage());
        }
        
        return [
            'session_timeout' => '60',
            'enable_2fa' => false,
            'login_notifications' => true,
            'password_min_length' => '12',
            'password_require_special' => true
        ];
    }

    /**
     * Check if strong password is required
     */
    public static function requiresStrongPassword() {
        $settings = self::getSecuritySettings();
        return $settings['password_require_special'] ?? true;
    }

    /**
     * Get session timeout
     */
    public static function getSessionTimeout() {
        $settings = self::getSecuritySettings();
        return (int)($settings['session_timeout'] ?? 60) * 60; // Convert to seconds
    }
}
?>