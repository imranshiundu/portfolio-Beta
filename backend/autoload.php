<?php
/**
 * Autoloader for backend classes
 */

spl_autoload_register(function ($className) {
    // Convert namespace to file path
    $file = __DIR__ . '/app/' . str_replace('\\', '/', $className) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
});

// Manually load core files
require_once __DIR__ . '/app/Core/Database.php';
require_once __DIR__ . '/app/Services/AuthService.php';
require_once __DIR__ . '/app/Services/DashboardService.php'; // Add this line
?>