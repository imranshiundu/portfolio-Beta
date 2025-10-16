<?php
/**
 * Database Singleton Class
 */

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // Try different possible config file locations
        $configPaths = [
            __DIR__ . '/../../../../config/database.php', // htdocs/config/
            __DIR__ . '/../../../config/database.php',     // backend/config/
            __DIR__ . '/../../config/database.php',        // app/config/
        ];
        
        $config = null;
        foreach ($configPaths as $path) {
            if (file_exists($path)) {
                $config = require $path;
                break;
            }
        }
        
        if (!$config) {
            throw new Exception("Database configuration file not found. Tried paths: " . implode(', ', $configPaths));
        }
        
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            $this->connection = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->connection;
    }
    
    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup() {}
}
?>