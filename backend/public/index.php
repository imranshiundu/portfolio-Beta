<?php
/**
 * Portfolio API - Main Entry Point
 */

// Set headers for CORS and JSON response
header('Access-Control-Allow-Origin: https://imranshiundu.eu');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load required files
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../app/Core/Database.php';
require_once '../app/Services/AuthService.php';
require_once '../app/Services/ProjectService.php';
require_once '../app/Services/BlogService.php';
require_once '../app/Services/ContactService.php';
require_once '../app/Services/SettingsService.php';

// Error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error: $errstr in $errfile on line $errline");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => APP_CONFIG['env'] === 'development' ? $errstr : null
    ]);
    exit();
});

set_exception_handler(function($exception) {
    error_log("Exception: " . $exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => APP_CONFIG['env'] === 'development' ? $exception->getMessage() : null
    ]);
    exit();
});

// Initialize configuration
define('APP_CONFIG', include '../config/app.php');
define('DB_CONFIG', include '../config/database.php');

try {
    // Parse request
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    
    // Remove base path if exists
    $basePath = '/api';
    if (strpos($requestUri, $basePath) === 0) {
        $requestUri = substr($requestUri, strlen($basePath));
    }
    
    $pathSegments = explode('/', trim($requestUri, '/'));
    $endpoint = $pathSegments[0] ?? '';
    $id = $pathSegments[1] ?? null;
    
    // Get request data
    $input = [];
    if ($requestMethod === 'POST' || $requestMethod === 'PUT') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $input = $_POST;
        }
        
        // Handle file uploads
        if (!empty($_FILES)) {
            $input = array_merge($input, $_FILES);
        }
    }
    
    // Route the request
    switch ("$requestMethod $endpoint") {
        // Public endpoints
        case 'GET projects':
            $service = new ProjectService();
            $filters = $_GET;
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 12;
            
            $result = $service->getProjects($filters, $page, $perPage);
            sendResponse(200, $result);
            break;
            
        case 'GET projects/':
            if (!$id) {
                sendError(400, 'Project ID is required');
            }
            $service = new ProjectService();
            $project = $service->getProject($id);
            
            if (!$project) {
                sendError(404, 'Project not found');
            }
            
            sendResponse(200, ['success' => true, 'data' => $project]);
            break;
            
        case 'GET blog':
            $service = new BlogService();
            $filters = $_GET;
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 10;
            
            $result = $service->getPosts($filters, $page, $perPage);
            sendResponse(200, $result);
            break;
            
        case 'GET blog/':
            if (!$id) {
                sendError(400, 'Post ID is required');
            }
            $service = new BlogService();
            $post = $service->getPost($id);
            
            if (!$post) {
                sendError(404, 'Blog post not found');
            }
            
            sendResponse(200, ['success' => true, 'data' => $post]);
            break;
            
        case 'GET settings':
            $service = new SettingsService();
            $settings = $service->getAllSettings();
            sendResponse(200, ['success' => true, 'data' => $settings]);
            break;
            
        case 'POST contact':
            $service = new ContactService();
            $result = $service->submitContact($input);
            sendResponse(201, ['success' => true, 'message' => 'Message sent successfully']);
            break;
            
        // Authentication
        case 'POST login':
            if (empty($input['email']) || empty($input['password'])) {
                sendError(400, 'Email and password are required');
            }
            
            $remember = isset($input['remember']);
            $success = AuthService::login($input['email'], $input['password'], $remember);
            
            if ($success) {
                sendResponse(200, [
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $_SESSION['admin_id'],
                        'name' => $_SESSION['admin_name'],
                        'email' => $_SESSION['admin_email']
                    ]
                ]);
            } else {
                sendError(401, 'Invalid credentials');
            }
            break;
            
        case 'POST logout':
            AuthService::logout();
            sendResponse(200, ['success' => true, 'message' => 'Logged out successfully']);
            break;
            
        // Protected endpoints (require authentication)
        default:
            // Check if this is a protected endpoint
            $protectedEndpoints = [
                'POST projects', 'PUT projects', 'DELETE projects',
                'POST blog', 'PUT blog', 'DELETE blog',
                'PUT settings', 'POST upload'
            ];
            
            $currentEndpoint = "$requestMethod $endpoint";
            
            if (in_array($currentEndpoint, $protectedEndpoints) || 
                ($requestMethod === 'PUT' && $endpoint === 'projects') ||
                ($requestMethod === 'PUT' && $endpoint === 'blog') ||
                ($requestMethod === 'DELETE' && $endpoint === 'projects') ||
                ($requestMethod === 'DELETE' && $endpoint === 'blog')) {
                
                if (!AuthService::isAuthenticated()) {
                    sendError(401, 'Authentication required');
                }
            }
            
            // Handle protected endpoints
            switch ($currentEndpoint) {
                // Project management
                case 'POST projects':
                    $service = new ProjectService();
                    $projectId = $service->createProject($input, $_FILES);
                    sendResponse(201, [
                        'success' => true,
                        'message' => 'Project created successfully',
                        'data' => ['id' => $projectId]
                    ]);
                    break;
                    
                case 'PUT projects':
                    if (!$id) {
                        sendError(400, 'Project ID is required');
                    }
                    $service = new ProjectService();
                    $service->updateProject($id, $input, $_FILES);
                    sendResponse(200, ['success' => true, 'message' => 'Project updated successfully']);
                    break;
                    
                case 'DELETE projects':
                    if (!$id) {
                        sendError(400, 'Project ID is required');
                    }
                    $service = new ProjectService();
                    $service->deleteProject($id);
                    sendResponse(200, ['success' => true, 'message' => 'Project deleted successfully']);
                    break;
                    
                // Blog management
                case 'POST blog':
                    $service = new BlogService();
                    $postId = $service->createPost($input, $_FILES);
                    sendResponse(201, [
                        'success' => true,
                        'message' => 'Blog post created successfully',
                        'data' => ['id' => $postId]
                    ]);
                    break;
                    
                case 'PUT blog':
                    if (!$id) {
                        sendError(400, 'Post ID is required');
                    }
                    $service = new BlogService();
                    $service->updatePost($id, $input, $_FILES);
                    sendResponse(200, ['success' => true, 'message' => 'Blog post updated successfully']);
                    break;
                    
                case 'DELETE blog':
                    if (!$id) {
                        sendError(400, 'Post ID is required');
                    }
                    $service = new BlogService();
                    $service->deletePost($id);
                    sendResponse(200, ['success' => true, 'message' => 'Blog post deleted successfully']);
                    break;
                    
                // Settings management
                case 'PUT settings':
                    $service = new SettingsService();
                    $action = $input['action'] ?? '';
                    
                    switch ($action) {
                        case 'save_personal':
                            $service->savePersonalSettings($input);
                            break;
                        case 'save_social':
                            $service->saveSocialSettings($input);
                            break;
                        case 'save_site':
                            $service->saveSiteSettings($input);
                            break;
                        case 'save_contact':
                            $service->saveContactSettings($input);
                            break;
                        case 'save_seo':
                            $service->saveSeoSettings($input);
                            break;
                        case 'save_appearance':
                            $service->saveAppearanceSettings($input);
                            break;
                        case 'save_security':
                            $service->saveSecuritySettings($input);
                            break;
                        case 'upload_image':
                            $service->handleImageUpload($_FILES, $input);
                            break;
                        case 'backup_database':
                            $backupFile = $service->createBackup();
                            sendResponse(200, [
                                'success' => true,
                                'message' => 'Backup created successfully',
                                'data' => ['backup_file' => $backupFile]
                            ]);
                            break;
                        case 'clear_cache':
                            $service->clearCache();
                            sendResponse(200, ['success' => true, 'message' => 'Cache cleared successfully']);
                            break;
                        default:
                            sendError(400, 'Invalid settings action');
                    }
                    
                    sendResponse(200, ['success' => true, 'message' => 'Settings updated successfully']);
                    break;
                    
                default:
                    sendError(404, 'Endpoint not found');
            }
    }
    
} catch (Exception $e) {
    sendError(500, $e->getMessage());
}

/**
 * Send JSON response
 */
function sendResponse($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

/**
 * Send error response
 */
function sendError($statusCode, $message) {
    sendResponse($statusCode, [
        'success' => false,
        'message' => $message
    ]);
}

// Close database connection if exists
if (isset($db)) {
    $db = null;
}
?>