<?php
/**
 * Simple Database Setup for ZoneVS
 * Fixed path issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Setup for Imran Shiundu Portfolio</h2>";
echo "<p>Checking configuration...</p>";

// Check if Database class exists
if (!class_exists('Database')) {
    // Try to load Database class
    $databaseFile = __DIR__ . '/app/Core/Database.php';
    if (file_exists($databaseFile)) {
        require_once $databaseFile;
        echo "<div style='color: green;'>✓ Database class loaded</div>";
    } else {
        echo "<div style='color: red;'>❌ Database class not found at: $databaseFile</div>";
        exit;
    }
}

try {
    $db = Database::getInstance();
    echo "<div style='color: green; margin: 10px 0;'>✓ Database connection successful!</div>";
    
    // Create tables
    $tables = [
        'admin_users' => "
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
        ",
        
        'projects' => "
            CREATE TABLE IF NOT EXISTS projects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE,
                description TEXT NOT NULL,
                full_description LONGTEXT,
                technologies JSON,
                categories JSON,
                featured_image VARCHAR(500),
                gallery_images JSON,
                demo_url VARCHAR(500),
                github_url VARCHAR(500),
                featured BOOLEAN DEFAULT FALSE,
                status ENUM('planned', 'in-progress', 'completed', 'archived') DEFAULT 'completed',
                timeline VARCHAR(100),
                features JSON,
                challenges JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'blog_posts' => "
            CREATE TABLE IF NOT EXISTS blog_posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE,
                excerpt TEXT,
                content LONGTEXT NOT NULL,
                featured_image VARCHAR(500),
                author_name VARCHAR(100) DEFAULT 'Imran Shiundu',
                categories JSON,
                tags JSON,
                meta_title VARCHAR(255),
                meta_description TEXT,
                featured BOOLEAN DEFAULT FALSE,
                status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
                published_at TIMESTAMP NULL,
                views INT DEFAULT 0,
                likes INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'contact_submissions' => "
            CREATE TABLE IF NOT EXISTS contact_submissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL,
                phone VARCHAR(20),
                project_type VARCHAR(50),
                budget_range VARCHAR(50),
                timeline VARCHAR(50),
                message TEXT NOT NULL,
                newsletter BOOLEAN DEFAULT FALSE,
                ip_address VARCHAR(45),
                user_agent TEXT,
                status ENUM('new', 'read', 'replied', 'spam') DEFAULT 'new',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'settings' => "
            CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                key_name VARCHAR(100) UNIQUE NOT NULL,
                key_value JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        "
    ];
    
    // Create each table
    foreach ($tables as $tableName => $sql) {
        try {
            $db->exec($sql);
            echo "<div style='color: green; margin: 10px 0;'>✓ Table '$tableName' created successfully</div>";
        } catch (Exception $e) {
            echo "<div style='color: orange; margin: 10px 0;'>⚠ Table '$tableName': " . $e->getMessage() . "</div>";
        }
    }
    
    // Create default admin user
    $defaultPassword = password_hash('Admin123!', PASSWORD_DEFAULT);
    try {
        $stmt = $db->prepare("
            INSERT IGNORE INTO admin_users (name, email, password, is_active) 
            VALUES (?, ?, ?, 1)
        ");
        $stmt->execute(['Administrator', 'admin@imranshiundu.eu', $defaultPassword]);
        echo "<div style='color: green; margin: 10px 0;'>✓ Default admin user created</div>";
    } catch (Exception $e) {
        echo "<div style='color: orange; margin: 10px 0;'>⚠ Admin user: " . $e->getMessage() . "</div>";
    }
    
    // Insert default settings
    try {
        $securitySettings = json_encode([
            'session_timeout' => '60',
            'enable_2fa' => false,
            'login_notifications' => true,
            'password_min_length' => '12',
            'password_require_special' => true
        ]);
        
        $stmt = $db->prepare("INSERT IGNORE INTO settings (key_name, key_value) VALUES ('security', ?)");
        $stmt->execute([$securitySettings]);
        echo "<div style='color: green; margin: 10px 0;'>✓ Default settings created</div>";
    } catch (Exception $e) {
        echo "<div style='color: orange; margin: 10px 0;'>⚠ Settings: " . $e->getMessage() . "</div>";
    }
    
    // Success message
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
    
    if (strpos($e->getMessage(), 'configuration file not found') !== false) {
        echo "<p><strong>Solution:</strong> Create config/database.php file with your database credentials.</p>";
        echo "<p>Expected locations:</p>";
        echo "<ul>";
        echo "<li>htdocs/config/database.php</li>";
        echo "<li>htdocs/backend/config/database.php</li>";
        echo "</ul>";
    } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<p><strong>Solution:</strong> Check your MySQL username and password in config/database.php</p>";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<p><strong>Solution:</strong> Database doesn't exist. Create it in your hosting control panel.</p>";
    }
    
    echo "</div>";
}
?>