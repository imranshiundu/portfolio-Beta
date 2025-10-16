<?php
/**
 * Projects Management - Admin Dashboard
 * Handles project creation, editing, and management
 */

// Fix for file upload warnings - suppress specific warnings
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

// Start session with output buffering to prevent header errors
ob_start();
session_start();

require_once '../backend/app/Services/AuthService.php';
require_once '../backend/app/Services/ProjectService.php';

// Check if user is authenticated
if (!AuthService::isAuthenticated()) {
    ob_end_clean(); // Clean the buffer before redirect
    header('Location: login.php');
    exit;
}

// Clear the buffer
ob_end_clean();

// Initialize project service
$projectService = new ProjectService();

// Handle actions
$action = $_GET['action'] ?? '';
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    try {
        switch ($postAction) {
            case 'create_project':
                $result = $projectService->createProject($_POST, $_FILES);
                if ($result) {
                    $message = 'Project created successfully!';
                    $messageType = 'success';
                    header('Location: projects.php?action=edit&id=' . $result);
                    exit;
                }
                break;
                
            case 'update_project':
                $projectId = $_POST['project_id'] ?? 0;
                $result = $projectService->updateProject($projectId, $_POST, $_FILES);
                if ($result) {
                    $message = 'Project updated successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'delete_project':
                $projectId = $_POST['project_id'] ?? 0;
                $result = $projectService->deleteProject($projectId);
                if ($result) {
                    $message = 'Project deleted successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'bulk_action':
                $result = $projectService->handleBulkAction($_POST);
                if ($result) {
                    $message = 'Bulk action completed successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'reorder_projects':
                $result = $projectService->reorderProjects($_POST['project_order'] ?? []);
                if ($result) {
                    $message = 'Projects reordered successfully!';
                    $messageType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get projects for listing - FIX: Ensure we get the projects array properly
$projectsData = $projectService->getProjects();
$projects = $projectsData['projects'] ?? []; // Extract projects array from the returned data
$categories = $projectService->getCategories();
$technologies = $projectService->getTechnologies();

// Get project for editing
$editingProject = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editingProject = $projectService->getProject($_GET['id']);
}

// Get project for viewing
$viewingProject = null;
if ($action === 'view' && isset($_GET['id'])) {
    $viewingProject = $projectService->getProject($_GET['id']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects Management - Imran Shiundu Admin</title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/projects.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tagging System -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tagify/4.17.9/tagify.min.css">
    
    <!-- Image Gallery -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.1/css/lightgallery-bundle.min.css">
</head>
<body class="admin-dashboard">
    <!-- Navigation Sidebar -->
    <?php include 'partials/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Header -->
        <header class="admin-header">
            <div class="header-left">
                <h1 class="page-title">
                    <?php
                    switch ($action) {
                        case 'create':
                            echo 'Create New Project';
                            break;
                        case 'edit':
                            echo 'Edit Project';
                            break;
                        case 'view':
                            echo 'View Project';
                            break;
                        default:
                            echo 'Projects Management';
                    }
                    ?>
                </h1>
                <p class="page-subtitle">
                    <?php
                    switch ($action) {
                        case 'create':
                            echo 'Add a new project to your portfolio';
                            break;
                        case 'edit':
                            echo 'Edit existing project details';
                            break;
                        case 'view':
                            echo 'Preview project details';
                            break;
                        default:
                            echo 'Manage your portfolio projects and showcase your work';
                    }
                    ?>
                </p>
            </div>
            
            <div class="header-actions">
                <?php if (in_array($action, ['create', 'edit'])): ?>
                    <button type="submit" form="projectForm" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php echo $action === 'create' ? 'Create Project' : 'Update Project'; ?>
                    </button>
                    <a href="projects.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Projects
                    </a>
                <?php elseif ($action === 'view'): ?>
                    <a href="projects.php?action=edit&id=<?php echo $viewingProject['id'] ?? ''; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i>
                        Edit Project
                    </a>
                    <a href="projects.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Projects
                    </a>
                <?php else: ?>
                    <a href="projects.php?action=create" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        New Project
                    </a>
                    <button class="btn btn-secondary" id="reorderProjectsBtn">
                        <i class="fas fa-sort"></i>
                        Reorder
                    </button>
                    <button class="btn btn-outline" id="exportProjectsBtn">
                        <i class="fas fa-download"></i>
                        Export
                    </button>
                <?php endif; ?>
            </div>
        </header>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <div class="alert-icon">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                </div>
                <div class="alert-content">
                    <p><?php echo htmlspecialchars($message); ?></p>
                </div>
                <button class="alert-close" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- Projects Management Content -->
        <div class="projects-content">
            <?php if (in_array($action, ['create', 'edit'])): ?>
                <!-- Create/Edit Project Form -->
                <div class="project-editor">
                    <form method="POST" 
                          class="project-form" 
                          id="projectForm" 
                          enctype="multipart/form-data"
                          novalidate>
                        
                        <input type="hidden" name="action" value="<?php echo $action === 'create' ? 'create_project' : 'update_project'; ?>">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="project_id" value="<?php echo $editingProject['id'] ?? ''; ?>">
                        <?php endif; ?>

                        <div class="editor-layout">
                            <!-- Main Editor Column -->
                            <div class="editor-main">
                                <!-- Basic Information -->
                                <div class="form-section">
                                    <h3 class="section-title">Basic Information</h3>
                                    
                                    <div class="form-group">
                                        <label for="project_title" class="form-label">Project Title *</label>
                                        <input type="text" 
                                               id="project_title" 
                                               name="project_title" 
                                               class="form-input" 
                                               placeholder="Enter project title..."
                                               value="<?php echo htmlspecialchars($editingProject['title'] ?? ''); ?>"
                                               required>
                                        <div class="form-error" id="titleError"></div>
                                    </div>

                                    <div class="form-group">
                                        <label for="project_description" class="form-label">Short Description *</label>
                                        <textarea id="project_description" 
                                                  name="project_description" 
                                                  class="form-textarea" 
                                                  rows="3" 
                                                  placeholder="Brief description of your project..."
                                                  required><?php echo htmlspecialchars($editingProject['description'] ?? ''); ?></textarea>
                                        <div class="character-count">
                                            <span id="descriptionCount">0</span>/200
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="project_full_description" class="form-label">Full Description</label>
                                        <textarea id="project_full_description" 
                                                  name="project_full_description" 
                                                  class="form-textarea" 
                                                  rows="6" 
                                                  placeholder="Detailed description of your project, technologies used, challenges faced, etc."><?php echo htmlspecialchars($editingProject['full_description'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <!-- Project Details -->
                                <div class="form-section">
                                    <h3 class="section-title">Project Details</h3>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="project_category" class="form-label">Category *</label>
                                            <select id="project_category" 
                                                    name="project_category" 
                                                    class="form-select" 
                                                    required>
                                                <option value="">Select category</option>
                                                <option value="frontend" <?php echo ($editingProject['category'] ?? '') === 'frontend' ? 'selected' : ''; ?>>Frontend</option>
                                                <option value="backend" <?php echo ($editingProject['category'] ?? '') === 'backend' ? 'selected' : ''; ?>>Backend</option>
                                                <option value="fullstack" <?php echo ($editingProject['category'] ?? '') === 'fullstack' ? 'selected' : ''; ?>>Full Stack</option>
                                                <option value="mobile" <?php echo ($editingProject['category'] ?? '') === 'mobile' ? 'selected' : ''; ?>>Mobile</option>
                                                <option value="other" <?php echo ($editingProject['category'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="project_status" class="form-label">Status *</label>
                                            <select id="project_status" 
                                                    name="project_status" 
                                                    class="form-select" 
                                                    required>
                                                <option value="completed" <?php echo ($editingProject['status'] ?? 'completed') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="in-progress" <?php echo ($editingProject['status'] ?? '') === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                                <option value="planned" <?php echo ($editingProject['status'] ?? '') === 'planned' ? 'selected' : ''; ?>>Planned</option>
                                                <option value="archived" <?php echo ($editingProject['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="project_timeline" class="form-label">Timeline</label>
                                            <input type="text" 
                                                   id="project_timeline" 
                                                   name="project_timeline" 
                                                   class="form-input" 
                                                   placeholder="e.g., 3 months, 6 weeks..."
                                                   value="<?php echo htmlspecialchars($editingProject['timeline'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="project_date" class="form-label">Project Date</label>
                                            <input type="date" 
                                                   id="project_date" 
                                                   name="project_date" 
                                                   class="form-input"
                                                   value="<?php echo isset($editingProject['project_date']) ? date('Y-m-d', strtotime($editingProject['project_date'])) : ''; ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Technologies & Skills -->
                                <div class="form-section">
                                    <h3 class="section-title">Technologies & Skills</h3>
                                    
                                    <div class="form-group">
                                        <label for="project_technologies" class="form-label">Technologies Used *</label>
                                        <input type="text" 
                                               id="project_technologies" 
                                               name="project_technologies" 
                                               class="form-input"
                                               value="<?php echo isset($editingProject['technologies']) && is_array($editingProject['technologies']) ? implode(', ', $editingProject['technologies']) : ''; ?>"
                                               required>
                                        <div class="form-help">Separate technologies with commas</div>
                                        <div class="form-error" id="technologiesError"></div>
                                    </div>

                                    <div class="technologies-suggestions">
                                        <div class="suggestions-header">
                                            <span>Common Technologies:</span>
                                        </div>
                                        <div class="suggestions-list">
                                            <?php foreach ($technologies as $tech): ?>
                                                <button type="button" class="tech-tag-suggestion" data-tech="<?php echo htmlspecialchars($tech); ?>">
                                                    <?php echo htmlspecialchars($tech); ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Project Features -->
                                <div class="form-section">
                                    <h3 class="section-title">Key Features</h3>
                                    
                                    <div class="features-container" id="featuresContainer">
                                        <?php if (isset($editingProject['features']) && is_array($editingProject['features'])): ?>
                                            <?php foreach ($editingProject['features'] as $index => $feature): ?>
                                                <div class="feature-item" data-index="<?php echo $index; ?>">
                                                    <input type="text" 
                                                           name="project_features[]" 
                                                           class="form-input" 
                                                           placeholder="Describe a key feature..."
                                                           value="<?php echo htmlspecialchars($feature); ?>">
                                                    <button type="button" class="btn btn-icon btn-danger remove-feature">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="feature-item" data-index="0">
                                                <input type="text" 
                                                       name="project_features[]" 
                                                       class="form-input" 
                                                       placeholder="Describe a key feature...">
                                                <button type="button" class="btn btn-icon btn-danger remove-feature">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button type="button" class="btn btn-outline" id="addFeatureBtn">
                                        <i class="fas fa-plus"></i>
                                        Add Feature
                                    </button>
                                </div>

                                <!-- Challenges & Solutions -->
                                <div class="form-section">
                                    <h3 class="section-title">Challenges & Solutions</h3>
                                    
                                    <div class="challenges-container" id="challengesContainer">
                                        <?php if (isset($editingProject['challenges']) && is_array($editingProject['challenges'])): ?>
                                            <?php foreach ($editingProject['challenges'] as $index => $challenge): ?>
                                                <div class="challenge-item" data-index="<?php echo $index; ?>">
                                                    <div class="challenge-row">
                                                        <div class="form-group">
                                                            <label class="form-label">Challenge</label>
                                                            <input type="text" 
                                                                   name="challenge_problem[]" 
                                                                   class="form-input" 
                                                                   placeholder="Describe the challenge..."
                                                                   value="<?php echo htmlspecialchars($challenge['problem'] ?? ''); ?>">
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="form-label">Solution</label>
                                                            <input type="text" 
                                                                   name="challenge_solution[]" 
                                                                   class="form-input" 
                                                                   placeholder="Describe your solution..."
                                                                   value="<?php echo htmlspecialchars($challenge['solution'] ?? ''); ?>">
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn btn-icon btn-danger remove-challenge">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="challenge-item" data-index="0">
                                                <div class="challenge-row">
                                                    <div class="form-group">
                                                        <label class="form-label">Challenge</label>
                                                        <input type="text" 
                                                               name="challenge_problem[]" 
                                                               class="form-input" 
                                                               placeholder="Describe the challenge...">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Solution</label>
                                                        <input type="text" 
                                                               name="challenge_solution[]" 
                                                               class="form-input" 
                                                               placeholder="Describe your solution...">
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-icon btn-danger remove-challenge">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button type="button" class="btn btn-outline" id="addChallengeBtn">
                                        <i class="fas fa-plus"></i>
                                        Add Challenge
                                    </button>
                                </div>
                            </div>

                            <!-- Sidebar -->
                            <div class="editor-sidebar">
                                <!-- Project Settings -->
                                <div class="sidebar-card">
                                    <h3 class="card-title">Project Settings</h3>
                                    <div class="card-content">
                                        <div class="form-group">
                                            <label class="checkbox-label">
                                                <input type="checkbox" 
                                                       name="project_featured" 
                                                       value="1" 
                                                       <?php echo ($editingProject['featured'] ?? false) ? 'checked' : ''; ?>>
                                                <span class="checkmark"></span>
                                                Featured Project
                                            </label>
                                            <div class="form-help">Show this project in featured section</div>
                                        </div>

                                        <div class="form-group">
                                            <label class="checkbox-label">
                                                <input type="checkbox" 
                                                       name="project_showcase" 
                                                       value="1" 
                                                       <?php echo ($editingProject['showcase'] ?? true) ? 'checked' : ''; ?>>
                                                <span class="checkmark"></span>
                                                Show in Portfolio
                                            </label>
                                            <div class="form-help">Display this project on your portfolio</div>
                                        </div>

                                        <div class="form-actions">
                                            <button type="submit" class="btn btn-primary btn-block">
                                                <i class="fas fa-save"></i>
                                                <?php echo $action === 'create' ? 'Create Project' : 'Update Project'; ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Featured Image -->
                                <div class="sidebar-card">
                                    <h3 class="card-title">Featured Image</h3>
                                    <div class="card-content">
                                        <div class="featured-image-upload">
                                            <div class="current-image" id="currentFeaturedImage">
                                                <?php if (isset($editingProject['featured_image']) && !empty($editingProject['featured_image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($editingProject['featured_image']); ?>" 
                                                         alt="Featured Image">
                                                    <button type="button" class="remove-image-btn" id="removeFeaturedImage">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <div class="image-placeholder">
                                                        <i class="fas fa-image"></i>
                                                        <span>No image selected</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <input type="file" 
                                                   id="featured_image" 
                                                   name="featured_image" 
                                                   accept="image/*" 
                                                   class="file-input" 
                                                   style="display: none;">
                                            <label for="featured_image" class="btn btn-outline btn-block">
                                                <i class="fas fa-upload"></i>
                                                Upload Image
                                            </label>
                                            <div class="form-help">
                                                Recommended: 800x600px, JPG or PNG
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Project Links -->
                                <div class="sidebar-card">
                                    <h3 class="card-title">Project Links</h3>
                                    <div class="card-content">
                                        <div class="form-group">
                                            <label for="project_demo_url" class="form-label">Live Demo URL</label>
                                            <input type="url" 
                                                   id="project_demo_url" 
                                                   name="project_demo_url" 
                                                   class="form-input" 
                                                   placeholder="https://demo.example.com"
                                                   value="<?php echo htmlspecialchars($editingProject['demo_url'] ?? ''); ?>">
                                        </div>

                                        <div class="form-group">
                                            <label for="project_github_url" class="form-label">GitHub Repository</label>
                                            <input type="url" 
                                                   id="project_github_url" 
                                                   name="project_github_url" 
                                                   class="form-input" 
                                                   placeholder="https://github.com/username/project"
                                                   value="<?php echo htmlspecialchars($editingProject['github_url'] ?? ''); ?>">
                                        </div>

                                        <div class="form-group">
                                            <label for="project_documentation_url" class="form-label">Documentation</label>
                                            <input type="url" 
                                                   id="project_documentation_url" 
                                                   name="project_documentation_url" 
                                                   class="form-input" 
                                                   placeholder="https://docs.example.com"
                                                   value="<?php echo htmlspecialchars($editingProject['documentation_url'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Project Gallery -->
                                <div class="sidebar-card">
                                    <h3 class="card-title">Project Gallery</h3>
                                    <div class="card-content">
                                        <div class="gallery-upload">
                                            <div class="gallery-preview" id="galleryPreview">
                                                <?php if (isset($editingProject['gallery']) && is_array($editingProject['gallery'])): ?>
                                                    <?php foreach ($editingProject['gallery'] as $index => $image): ?>
                                                        <div class="gallery-item" data-index="<?php echo $index; ?>">
                                                            <img src="<?php echo htmlspecialchars($image); ?>" alt="Gallery image">
                                                            <button type="button" class="remove-gallery-image">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                            <input type="hidden" name="project_gallery[]" value="<?php echo htmlspecialchars($image); ?>">
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <input type="file" 
                                                   id="gallery_images" 
                                                   name="gallery_images[]" 
                                                   accept="image/*" 
                                                   multiple 
                                                   class="file-input" 
                                                   style="display: none;">
                                            <label for="gallery_images" class="btn btn-outline btn-block">
                                                <i class="fas fa-images"></i>
                                                Add Gallery Images
                                            </label>
                                            <div class="form-help">
                                                Multiple images allowed
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Project Statistics -->
                                <?php if ($action === 'edit' && isset($editingProject)): ?>
                                <div class="sidebar-card">
                                    <h3 class="card-title">Project Statistics</h3>
                                    <div class="card-content">
                                        <div class="statistics-grid">
                                            <div class="stat-item">
                                                <div class="stat-icon">
                                                    <i class="fas fa-eye"></i>
                                                </div>
                                                <div class="stat-info">
                                                    <span class="stat-number"><?php echo $editingProject['views'] ?? 0; ?></span>
                                                    <span class="stat-label">Views</span>
                                                </div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-icon">
                                                    <i class="fas fa-heart"></i>
                                                </div>
                                                <div class="stat-info">
                                                    <span class="stat-number"><?php echo $editingProject['likes'] ?? 0; ?></span>
                                                    <span class="stat-label">Likes</span>
                                                </div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-icon">
                                                    <i class="fas fa-share"></i>
                                                </div>
                                                <div class="stat-info">
                                                    <span class="stat-number"><?php echo $editingProject['shares'] ?? 0; ?></span>
                                                    <span class="stat-label">Shares</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="project-meta">
                                            <p><strong>Created:</strong> <?php echo isset($editingProject['created_at']) ? date('M j, Y g:i A', strtotime($editingProject['created_at'])) : 'N/A'; ?></p>
                                            <p><strong>Last Updated:</strong> <?php echo isset($editingProject['updated_at']) ? date('M j, Y g:i A', strtotime($editingProject['updated_at'])) : 'N/A'; ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

            <?php elseif ($action === 'view' && $viewingProject): ?>
                <!-- Project Preview -->
                <div class="project-preview">
                    <div class="preview-header">
                        <div class="preview-badge">
                            <span class="status-badge status-<?php echo strtolower($viewingProject['status'] ?? 'completed'); ?>">
                                <?php echo ucfirst($viewingProject['status'] ?? 'Completed'); ?>
                            </span>
                            <?php if ($viewingProject['featured'] ?? false): ?>
                                <span class="featured-badge">
                                    <i class="fas fa-star"></i>
                                    Featured
                                </span>
                            <?php endif; ?>
                        </div>
                        <h1 class="preview-title"><?php echo htmlspecialchars($viewingProject['title'] ?? 'Untitled Project'); ?></h1>
                        <p class="preview-description"><?php echo htmlspecialchars($viewingProject['description'] ?? ''); ?></p>
                    </div>

                    <?php if (isset($viewingProject['featured_image']) && !empty($viewingProject['featured_image'])): ?>
                        <div class="preview-image">
                            <img src="<?php echo htmlspecialchars($viewingProject['featured_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($viewingProject['title'] ?? 'Project Image'); ?>">
                        </div>
                    <?php endif; ?>

                    <div class="preview-content">
                        <div class="preview-details">
                            <div class="detail-section">
                                <h3>Project Overview</h3>
                                <p><?php echo nl2br(htmlspecialchars($viewingProject['full_description'] ?? $viewingProject['description'] ?? 'No description available.')); ?></p>
                            </div>

                            <?php if (isset($viewingProject['features']) && is_array($viewingProject['features']) && !empty($viewingProject['features'])): ?>
                                <div class="detail-section">
                                    <h3>Key Features</h3>
                                    <ul class="features-list">
                                        <?php foreach ($viewingProject['features'] as $feature): ?>
                                            <li><?php echo htmlspecialchars($feature); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($viewingProject['technologies']) && is_array($viewingProject['technologies']) && !empty($viewingProject['technologies'])): ?>
                                <div class="detail-section">
                                    <h3>Technologies Used</h3>
                                    <div class="technologies-list">
                                        <?php foreach ($viewingProject['technologies'] as $tech): ?>
                                            <span class="tech-tag"><?php echo htmlspecialchars($tech); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="preview-sidebar">
                            <div class="project-links">
                                <?php if (isset($viewingProject['demo_url']) && !empty($viewingProject['demo_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($viewingProject['demo_url']); ?>" 
                                       class="btn btn-primary btn-block" 
                                       target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                        Live Demo
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (isset($viewingProject['github_url']) && !empty($viewingProject['github_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($viewingProject['github_url']); ?>" 
                                       class="btn btn-secondary btn-block" 
                                       target="_blank">
                                        <i class="fab fa-github"></i>
                                        View Code
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="project-meta-info">
                                <div class="meta-item">
                                    <strong>Category:</strong>
                                    <span><?php echo ucfirst($viewingProject['category'] ?? 'other'); ?></span>
                                </div>
                                <div class="meta-item">
                                    <strong>Timeline:</strong>
                                    <span><?php echo htmlspecialchars($viewingProject['timeline'] ?? 'Not specified'); ?></span>
                                </div>
                                <div class="meta-item">
                                    <strong>Date:</strong>
                                    <span><?php echo isset($viewingProject['project_date']) ? date('M Y', strtotime($viewingProject['project_date'])) : (isset($viewingProject['created_at']) ? date('M Y', strtotime($viewingProject['created_at'])) : 'N/A'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($viewingProject['gallery']) && is_array($viewingProject['gallery']) && !empty($viewingProject['gallery'])): ?>
                        <div class="preview-gallery">
                            <h3>Project Gallery</h3>
                            <div class="gallery-grid">
                                <?php foreach ($viewingProject['gallery'] as $image): ?>
                                    <div class="gallery-item">
                                        <img src="<?php echo htmlspecialchars($image); ?>" 
                                             alt="Project screenshot">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <!-- Projects List -->
                <div class="projects-listing">
                    <!-- Filters and Search -->
                    <div class="listing-header">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" 
                                   id="projectsSearch" 
                                   placeholder="Search projects..." 
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                        
                        <div class="filter-controls">
                            <select id="statusFilter" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="in-progress" <?php echo ($_GET['status'] ?? '') === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="planned" <?php echo ($_GET['status'] ?? '') === 'planned' ? 'selected' : ''; ?>>Planned</option>
                            </select>
                            
                            <select id="categoryFilter" class="form-select">
                                <option value="">All Categories</option>
                                <option value="frontend" <?php echo ($_GET['category'] ?? '') === 'frontend' ? 'selected' : ''; ?>>Frontend</option>
                                <option value="backend" <?php echo ($_GET['category'] ?? '') === 'backend' ? 'selected' : ''; ?>>Backend</option>
                                <option value="fullstack" <?php echo ($_GET['category'] ?? '') === 'fullstack' ? 'selected' : ''; ?>>Full Stack</option>
                                <option value="mobile" <?php echo ($_GET['category'] ?? '') === 'mobile' ? 'selected' : ''; ?>>Mobile</option>
                            </select>
                            
                            <select id="featuredFilter" class="form-select">
                                <option value="">All Projects</option>
                                <option value="featured" <?php echo ($_GET['featured'] ?? '') === 'featured' ? 'selected' : ''; ?>>Featured Only</option>
                            </select>
                            
                            <button class="btn btn-outline" id="clearFilters">
                                <i class="fas fa-times"></i>
                                Clear
                            </button>
                        </div>
                    </div>

                    <!-- Bulk Actions -->
                    <div class="bulk-actions-bar" id="bulkActionsBar" style="display: none;">
                        <div class="bulk-info">
                            <span id="selectedCount">0</span> projects selected
                        </div>
                        <div class="bulk-controls">
                            <select id="bulkAction" class="form-select">
                                <option value="">Choose action...</option>
                                <option value="feature">Mark as Featured</option>
                                <option value="unfeature">Remove Featured</option>
                                <option value="show">Show in Portfolio</option>
                                <option value="hide">Hide from Portfolio</option>
                                <option value="delete">Delete</option>
                            </select>
                            <button type="button" class="btn btn-primary" id="applyBulkAction">Apply</button>
                            <button type="button" class="btn btn-outline" id="cancelBulkAction">Cancel</button>
                        </div>
                    </div>

                    <!-- Projects Grid -->
                    <div class="projects-grid-container">
                        <div class="projects-grid" id="projectsGrid">
                            <?php if (empty($projects)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-project-diagram"></i>
                                    <p>No projects found</p>
                                    <a href="projects.php?action=create" class="btn btn-primary">
                                        <i class="fas fa-plus"></i>
                                        Create Your First Project
                                    </a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($projects as $project): ?>
                                    <div class="project-card" data-project-id="<?php echo $project['id'] ?? ''; ?>">
                                        <div class="project-card-header">
                                            <input type="checkbox" 
                                                   name="project_ids[]" 
                                                   value="<?php echo $project['id'] ?? ''; ?>" 
                                                   class="project-checkbox">
                                            <div class="project-badges">
                                                <?php if ($project['featured'] ?? false): ?>
                                                    <span class="featured-badge">
                                                        <i class="fas fa-star"></i>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="status-badge status-<?php echo strtolower($project['status'] ?? 'completed'); ?>">
                                                    <?php echo ucfirst($project['status'] ?? 'Completed'); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="project-image">
                                            <?php if (isset($project['featured_image']) && !empty($project['featured_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($project['featured_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($project['title'] ?? 'Project Image'); ?>">
                                            <?php else: ?>
                                                <div class="image-placeholder">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="project-overlay">
                                                <div class="project-actions">
                                                    <a href="projects.php?action=view&id=<?php echo $project['id'] ?? ''; ?>" 
                                                       class="btn btn-icon" 
                                                       title="Preview">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="projects.php?action=edit&id=<?php echo $project['id'] ?? ''; ?>" 
                                                       class="btn btn-icon" 
                                                       title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-icon btn-danger delete-project" 
                                                            data-project-id="<?php echo $project['id'] ?? ''; ?>"
                                                            data-project-title="<?php echo htmlspecialchars($project['title'] ?? 'Untitled Project'); ?>"
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="project-content">
                                            <h3 class="project-title">
                                                <a href="projects.php?action=edit&id=<?php echo $project['id'] ?? ''; ?>">
                                                    <?php echo htmlspecialchars($project['title'] ?? 'Untitled Project'); ?>
                                                </a>
                                            </h3>
                                            <p class="project-description">
                                                <?php echo htmlspecialchars(substr($project['description'] ?? '', 0, 100)); ?>...
                                            </p>
                                            
                                            <div class="project-technologies">
                                                <?php if (isset($project['technologies']) && is_array($project['technologies'])): ?>
                                                    <?php foreach (array_slice($project['technologies'], 0, 3) as $tech): ?>
                                                        <span class="tech-tag"><?php echo htmlspecialchars($tech); ?></span>
                                                    <?php endforeach; ?>
                                                    <?php if (count($project['technologies']) > 3): ?>
                                                        <span class="tech-more">+<?php echo count($project['technologies']) - 3; ?> more</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="project-meta">
                                                <span class="project-category">
                                                    <i class="fas fa-folder"></i>
                                                    <?php echo ucfirst($project['category'] ?? 'other'); ?>
                                                </span>
                                                <span class="project-date">
                                                    <i class="fas fa-calendar"></i>
                                                    <?php echo isset($project['project_date']) ? date('M Y', strtotime($project['project_date'])) : (isset($project['created_at']) ? date('M Y', strtotime($project['created_at'])) : 'N/A'); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="pagination">
                        <div class="pagination-info">
                            Showing 1-<?php echo count($projects); ?> of <?php echo count($projects); ?> projects
                        </div>
                        <div class="pagination-controls">
                            <button class="btn btn-outline" disabled>
                                <i class="fas fa-chevron-left"></i>
                                Previous
                            </button>
                            <span class="pagination-page">Page 1 of 1</span>
                            <button class="btn btn-outline" disabled>
                                Next
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Project</h3>
                <button class="modal-close" id="closeDeleteModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete "<strong id="deleteProjectTitle"></strong>"?</p>
                <p class="text-warning">This action cannot be undone and will remove all project data including images.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete_project">
                    <input type="hidden" name="project_id" id="deleteProjectId">
                    <button type="button" class="btn btn-secondary" id="cancelDelete">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Project</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Reorder Projects Modal -->
    <div class="modal" id="reorderModal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3>Reorder Projects</h3>
                <button class="modal-close" id="closeReorderModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="reorder-instructions">
                    <p>Drag and drop projects to reorder them. The order will affect how they appear on your portfolio.</p>
                </div>
                <div class="reorder-list" id="reorderList">
                    <?php foreach ($projects as $project): ?>
                        <div class="reorder-item" data-project-id="<?php echo $project['id'] ?? ''; ?>">
                            <div class="reorder-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="reorder-content">
                                <?php if (isset($project['featured_image']) && !empty($project['featured_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($project['featured_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($project['title'] ?? 'Project Image'); ?>" 
                                         class="reorder-image">
                                <?php endif; ?>
                                <div class="reorder-info">
                                    <h4><?php echo htmlspecialchars($project['title'] ?? 'Untitled Project'); ?></h4>
                                    <p><?php echo htmlspecialchars($project['category'] ?? 'other'); ?> • <?php echo ucfirst($project['status'] ?? 'completed'); ?></p>
                                </div>
                            </div>
                            <div class="reorder-position">
                                <span class="position-number"><?php echo $project['display_order'] ?? 0; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelReorder">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveReorder">Save Order</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/admin.js"></script>
    <script src="assets/js/projects.js"></script>
    
    <!-- Tagging System -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tagify/4.17.9/tagify.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tagify/4.17.9/tagify.polyfills.min.js"></script>
    
    <!-- Drag & Drop -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    
    <!-- Image Gallery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.1/lightgallery.min.js"></script>
    
    <script>
        // Initialize projects functionality
        document.addEventListener('DOMContentLoaded', function() {
            const projectsManager = new ProjectsManager();
            projectsManager.init();
        });
    </script>
</body>
</html>