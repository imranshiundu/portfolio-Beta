<?php
/**
 * Database Setup Script for ZoneVS MySQL
 * Run this once to create the necessary tables
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Setup for Imran Shiundu Portfolio</h2>";

try {
    require_once 'backend/autoload.php';
    $db = Database::getInstance();
    
    echo "<div style='color: green; margin: 10px 0;'>✓ Database connection successful!</div>";
    
    // Create admin_users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            last_login DATETIME NULL,
            login_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "<div style='color: green; margin: 10px 0;'>✓ admin_users table created/verified</div>";
    
    // Create login_attempts table
    $db->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            success BOOLEAN DEFAULT FALSE,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "<div style='color: green; margin: 10px 0;'>✓ login_attempts table created/verified</div>";
    
    // Create admin_activity_log table
    $db->exec("
        CREATE TABLE IF NOT EXISTS admin_activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NULL,
            activity_type VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "<div style='color: green; margin: 10px 0;'>✓ admin_activity_log table created/verified</div>";
    
    // Create settings table
    $db->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            key_name VARCHAR(100) UNIQUE NOT NULL,
            key_value TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "<div style='color: green; margin: 10px 0;'>✓ settings table created/verified</div>";
    
    // Check if admin user exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM admin_users WHERE email = ?");
    $stmt->execute(['admin@imranshiundu.eu']);
    $adminExists = $stmt->fetchColumn();
    
    if (!$adminExists) {
        // Create default admin user
        $default_password = password_hash('Admin123!', PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("
            INSERT INTO admin_users (name, email, password, is_active) 
            VALUES (?, ?, ?, 1)
        ");
        $stmt->execute(['Administrator', 'admin@imranshiundu.eu', $default_password]);
        
        echo "<div style='color: green; margin: 10px 0;'>✓ Default admin user created</div>";
    } else {
        echo "<div style='color: orange; margin: 10px 0;'>⚠ Admin user already exists</div>";
    }
    
    // Insert default security settings
    $security_settings = json_encode([
        'session_timeout' => '60',
        'enable_2fa' => false,
        'login_notifications' => true,
        'password_min_length' => '12',
        'password_require_special' => true
    ]);
    
    $stmt = $db->prepare("INSERT IGNORE INTO settings (key_name, key_value) VALUES ('security', ?)");
    $stmt->execute([$security_settings]);
    echo "<div style='color: green; margin: 10px 0;'>✓ Default settings created</div>";
    
    // Display success message
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; margin: 20px 0;'>";
    echo "<h3>✅ Database Setup Completed Successfully!</h3>";
    echo "<p><strong>Default Admin Credentials:</strong></p>";
    echo "<p>Email: <strong>admin@imranshiundu.eu</strong></p>";
    echo "<p>Password: <strong>Admin123!</strong></p>";
    echo "<p><em>IMPORTANT: Change this password after first login!</em></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin: 20px 0;'>";
    echo "<h3>❌ Database Setup Failed</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>MySQL password in Database.php is correct</li>";
    echo "<li>Database user has proper privileges</li>";
    echo "<li>MySQL server is accessible</li>";
    echo "</ul>";
    echo "</div>";
}
?>

<div style="margin: 20px 0; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7;">
    <strong>Security Notice:</strong> 
    <ul>
        <li>Delete this file after successful setup</li>
        <li>Change the default admin password immediately</li>
        <li>Keep your MySQL password secure</li>
    </ul>
</div>