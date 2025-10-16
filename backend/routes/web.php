<?php
/**
 * Web Routes - Admin Dashboard Routes
 */

session_start();

// Authentication middleware
function requireAuth() {
    if (!isset($_SESSION['admin_authenticated']) || !$_SESSION['admin_authenticated']) {
        header('Location: /admin-dashboard/login.php');
        exit();
    }
}

// Route requests
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/admin-dashboard';

// Remove base path
$path = str_replace($basePath, '', $requestUri);
$path = parse_url($path, PHP_URL_PATH);
$path = trim($path, '/');

// Define routes
$routes = [
    '' => 'dashboard.php',
    'dashboard' => 'dashboard.php',
    'login' => 'login.php',
    'logout' => 'logout.php',
    'projects' => 'projects.php',
    'blog' => 'blog.php',
    'settings' => 'settings.php',
    'messages' => 'messages.php',
    'analytics' => 'analytics.php'
];

// Handle route
if ($path === '' || $path === 'dashboard') {
    requireAuth();
    include 'dashboard.php';
} elseif ($path === 'login') {
    // If already logged in, redirect to dashboard
    if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated']) {
        header('Location: /admin-dashboard/dashboard.php');
        exit();
    }
    include 'login.php';
} elseif ($path === 'logout') {
    require 'logout.php';
} elseif (isset($routes[$path])) {
    requireAuth();
    include $routes[$path];
} else {
    // 404 - Page not found
    http_response_code(404);
    include '404.php';
}
?>