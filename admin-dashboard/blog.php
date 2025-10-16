<?php
/**
 * Blog Management - Admin Dashboard
 * Handles blog post creation, editing, and management
 */

session_start();
require_once '../backend/app/Services/AuthService.php';
require_once '../backend/app/Services/BlogService.php';

// Check if user is authenticated
if (!AuthService::isAuthenticated()) {
    header('Location: login.php');
    exit;
}

// Initialize blog service
$blogService = new BlogService();

// Handle actions
$action = $_GET['action'] ?? '';
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    try {
        switch ($postAction) {
            case 'create_post':
                $result = $blogService->createPost($_POST, $_FILES);
                if ($result) {
                    $message = 'Blog post created successfully!';
                    $messageType = 'success';
                    header('Location: blog.php?action=edit&id=' . $result);
                    exit;
                }
                break;
                
            case 'update_post':
                $postId = $_POST['post_id'] ?? 0;
                $result = $blogService->updatePost($postId, $_POST, $_FILES);
                if ($result) {
                    $message = 'Blog post updated successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'delete_post':
                $postId = $_POST['post_id'] ?? 0;
                $result = $blogService->deletePost($postId);
                if ($result) {
                    $message = 'Blog post deleted successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'bulk_action':
                $result = $blogService->handleBulkAction($_POST);
                if ($result) {
                    $message = 'Bulk action completed successfully!';
                    $messageType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get posts for listing
$posts = $blogService->getPosts();
$categories = $blogService->getCategories();
$tags = $blogService->getTags();

// Get post for editing
$editingPost = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editingPost = $blogService->getPost($_GET['id']);
}

// Get post for viewing
$viewingPost = null;
if ($action === 'view' && isset($_GET['id'])) {
    $viewingPost = $blogService->getPost($_GET['id']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Management - Imran Shiundu Admin</title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/blog.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Rich Text Editor -->
    <link rel="stylesheet" href="https://cdn.quilljs.com/1.3.6/quill.snow.css">
    
    <!-- Tagging System -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tagify/4.17.9/tagify.min.css">
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
                            echo 'Create New Post';
                            break;
                        case 'edit':
                            echo 'Edit Post';
                            break;
                        case 'view':
                            echo 'View Post';
                            break;
                        default:
                            echo 'Blog Management';
                    }
                    ?>
                </h1>
                <p class="page-subtitle">
                    <?php
                    switch ($action) {
                        case 'create':
                            echo 'Write and publish a new blog post';
                            break;
                        case 'edit':
                            echo 'Edit existing blog post';
                            break;
                        case 'view':
                            echo 'Preview blog post';
                            break;
                        default:
                            echo 'Manage your blog posts and content';
                    }
                    ?>
                </p>
            </div>
            
            <div class="header-actions">
                <?php if (in_array($action, ['create', 'edit'])): ?>
                    <button type="submit" form="blogForm" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php echo $action === 'create' ? 'Publish Post' : 'Update Post'; ?>
                    </button>
                    <a href="blog.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Posts
                    </a>
                <?php elseif ($action === 'view'): ?>
                    <a href="blog.php?action=edit&id=<?php echo $viewingPost['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i>
                        Edit Post
                    </a>
                    <a href="blog.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Posts
                    </a>
                <?php else: ?>
                    <a href="blog.php?action=create" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        New Post
                    </a>
                    <button class="btn btn-secondary" id="importPostsBtn">
                        <i class="fas fa-upload"></i>
                        Import
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

        <!-- Blog Management Content -->
        <div class="blog-content">
            <?php if (in_array($action, ['create', 'edit'])): ?>
                <!-- Create/Edit Post Form -->
                <div class="blog-editor">
                    <form method="POST" 
                          class="blog-form" 
                          id="blogForm" 
                          enctype="multipart/form-data"
                          novalidate>
                        
                        <input type="hidden" name="action" value="<?php echo $action === 'create' ? 'create_post' : 'update_post'; ?>">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="post_id" value="<?php echo $editingPost['id']; ?>">
                        <?php endif; ?>

                        <div class="editor-layout">
                            <!-- Main Editor Column -->
                            <div class="editor-main">
                                <!-- Title -->
                                <div class="form-group">
                                    <input type="text" 
                                           id="post_title" 
                                           name="post_title" 
                                           class="post-title-input" 
                                           placeholder="Enter post title..."
                                           value="<?php echo htmlspecialchars($editingPost['title'] ?? ''); ?>"
                                           required>
                                    <div class="form-error" id="titleError"></div>
                                </div>

                                <!-- Content Editor -->
                                <div class="form-group">
                                    <label for="post_content" class="form-label">Content</label>
                                    <div id="editor-container">
                                        <div id="post_editor">
                                            <?php echo $editingPost['content'] ?? ''; ?>
                                        </div>
                                    </div>
                                    <textarea id="post_content" 
                                              name="post_content" 
                                              style="display: none;" 
                                              required><?php echo htmlspecialchars($editingPost['content'] ?? ''); ?></textarea>
                                    <div class="form-error" id="contentError"></div>
                                </div>

                                <!-- Excerpt -->
                                <div class="form-group">
                                    <label for="post_excerpt" class="form-label">Excerpt</label>
                                    <textarea id="post_excerpt" 
                                              name="post_excerpt" 
                                              class="form-textarea" 
                                              rows="4" 
                                              placeholder="Brief description of your post (used in post listings and SEO)"><?php echo htmlspecialchars($editingPost['excerpt'] ?? ''); ?></textarea>
                                    <div class="character-count">
                                        <span id="excerptCount">0</span>/300
                                    </div>
                                </div>
                            </div>

                            <!-- Sidebar -->
                            <div class="editor-sidebar">
                                <!-- Publish Settings -->
                                <div class="sidebar-card">
                                    <h3 class="card-title">Publish</h3>
                                    <div class="card-content">
                                        <div class="publish-status">
                                            <strong>Status:</strong>
                                            <span class="status-badge status-<?php echo strtolower($editingPost['status'] ?? 'draft'); ?>">
                                                <?php echo ucfirst($editingPost['status'] ?? 'draft'); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="post_status" class="form-label">Post Status</label>
                                            <select id="post_status" name="post_status" class="form-select">
                                                <option value="draft" <?php echo ($editingPost['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                <option value="published" <?php echo ($editingPost['status'] ?? 'draft') === 'published' ? 'selected' : ''; ?>>Published</option>
                                                <option value="archived" <?php echo ($editingPost['status'] ?? 'draft') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="post_schedule" class="form-label">Publish Schedule</label>
                                            <input type="datetime-local" 
                                                   id="post_schedule" 
                                                   name="post_schedule" 
                                                   class="form-input"
                                                   value="<?php echo isset($editingPost['published_at']) ? date('Y-m-d\TH:i', strtotime($editingPost['published_at'])) : ''; ?>">
                                            <div class="form-help">Leave empty to publish immediately</div>
                                        </div>

                                        <div class="publish-actions">
                                            <?php if ($action === 'edit'): ?>
                                                <button type="submit" name="save_action" value="update" class="btn btn-primary btn-block">
                                                    <i class="fas fa-save"></i>
                                                    Update Post
                                                </button>
                                                <button type="submit" name="save_action" value="publish" class="btn btn-success btn-block">
                                                    <i class="fas fa-paper-plane"></i>
                                                    Publish Now
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="save_action" value="draft" class="btn btn-secondary btn-block">
                                                    <i class="fas fa-save"></i>
                                                    Save Draft
                                                </button>
                                                <button type="submit" name="save_action" value="publish" class="btn btn-primary btn-block">
                                                    <i class="fas fa-paper-plane"></i>
                                                    Publish Post
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Featured Image -->
                                <div class="sidebar-card">
                                    <h3 class="card-title">Featured Image</h3>
                                    <div class="card-content">
                                        <div class="featured-image-upload">
                                            <div class="current-image" id="currentFeaturedImage">
                                                <?php if (isset($editingPost['featured_image']) && !empty($editingPost['featured_image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($editingPost['featured_image']); ?>" 
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
                                                Recommended: 1200x630px, JPG or PNG
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Categories & Tags -->
                                <div class="sidebar-card">
                                    <h3 class="card-title">Categories & Tags</h3>
                                    <div class="card-content">
                                        <div class="form-group">
                                            <label for="post_categories" class="form-label">Categories</label>
                                            <select id="post_categories" 
                                                    name="post_categories[]" 
                                                    class="form-select" 
                                                    multiple 
                                                    size="4">
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>" 
                                                        <?php echo (isset($editingPost['categories']) && in_array($category['id'], $editingPost['categories'])) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-help">Hold Ctrl/Cmd to select multiple</div>
                                        </div>

                                        <div class="form-group">
                                            <label for="post_tags" class="form-label">Tags</label>
                                            <input type="text" 
                                                   id="post_tags" 
                                                   name="post_tags" 
                                                   class="form-input"
                                                   value="<?php echo isset($editingPost['tags']) ? implode(', ', $editingPost['tags']) : ''; ?>">
                                            <div class="form-help">Separate tags with commas</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SEO Settings -->
                                <div class="sidebar-card">
                                    <h3 class="card-title">SEO Settings</h3>
                                    <div class="card-content">
                                        <div class="form-group">
                                            <label for="post_slug" class="form-label">URL Slug</label>
                                            <input type="text" 
                                                   id="post_slug" 
                                                   name="post_slug" 
                                                   class="form-input"
                                                   value="<?php echo htmlspecialchars($editingPost['slug'] ?? ''); ?>">
                                            <div class="form-help">Leave empty to auto-generate from title</div>
                                        </div>

                                        <div class="form-group">
                                            <label for="meta_title" class="form-label">Meta Title</label>
                                            <input type="text" 
                                                   id="meta_title" 
                                                   name="meta_title" 
                                                   class="form-input"
                                                   value="<?php echo htmlspecialchars($editingPost['meta_title'] ?? ''); ?>">
                                            <div class="character-count">
                                                <span id="metaTitleCount">0</span>/60
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="meta_description" class="form-label">Meta Description</label>
                                            <textarea id="meta_description" 
                                                      name="meta_description" 
                                                      class="form-textarea" 
                                                      rows="3"><?php echo htmlspecialchars($editingPost['meta_description'] ?? ''); ?></textarea>
                                            <div class="character-count">
                                                <span id="metaDescriptionCount">0</span>/160
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Post Statistics -->
                                <?php if ($action === 'edit'): ?>
                                <div class="sidebar-card">
                                    <h3 class="card-title">Post Statistics</h3>
                                    <div class="card-content">
                                        <div class="statistics-grid">
                                            <div class="stat-item">
                                                <div class="stat-icon">
                                                    <i class="fas fa-eye"></i>
                                                </div>
                                                <div class="stat-info">
                                                    <span class="stat-number"><?php echo $editingPost['views'] ?? 0; ?></span>
                                                    <span class="stat-label">Views</span>
                                                </div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-icon">
                                                    <i class="fas fa-heart"></i>
                                                </div>
                                                <div class="stat-info">
                                                    <span class="stat-number"><?php echo $editingPost['likes'] ?? 0; ?></span>
                                                    <span class="stat-label">Likes</span>
                                                </div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-icon">
                                                    <i class="fas fa-comments"></i>
                                                </div>
                                                <div class="stat-info">
                                                    <span class="stat-number"><?php echo $editingPost['comment_count'] ?? 0; ?></span>
                                                    <span class="stat-label">Comments</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="post-meta">
                                            <p><strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($editingPost['created_at'])); ?></p>
                                            <p><strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($editingPost['updated_at'])); ?></p>
                                            <?php if ($editingPost['published_at']): ?>
                                                <p><strong>Published:</strong> <?php echo date('M j, Y g:i A', strtotime($editingPost['published_at'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

            <?php elseif ($action === 'view' && $viewingPost): ?>
                <!-- Post Preview -->
                <div class="post-preview">
                    <div class="preview-header">
                        <h1 class="preview-title"><?php echo htmlspecialchars($viewingPost['title']); ?></h1>
                        <div class="preview-meta">
                            <span class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <?php echo date('F j, Y', strtotime($viewingPost['published_at'] ?? $viewingPost['created_at'])); ?>
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-clock"></i>
                                <?php echo $viewingPost['reading_time'] ?? '5'; ?> min read
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-eye"></i>
                                <?php echo $viewingPost['views'] ?? 0; ?> views
                            </span>
                        </div>
                    </div>

                    <?php if ($viewingPost['featured_image']): ?>
                        <div class="preview-image">
                            <img src="<?php echo htmlspecialchars($viewingPost['featured_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($viewingPost['title']); ?>">
                        </div>
                    <?php endif; ?>

                    <div class="preview-content">
                        <?php echo $viewingPost['content']; ?>
                    </div>

                    <div class="preview-footer">
                        <div class="post-tags">
                            <?php if (isset($viewingPost['tags']) && is_array($viewingPost['tags'])): ?>
                                <?php foreach ($viewingPost['tags'] as $tag): ?>
                                    <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Posts List -->
                <div class="blog-listing">
                    <!-- Filters and Search -->
                    <div class="listing-header">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" 
                                   id="postsSearch" 
                                   placeholder="Search posts..." 
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                        
                        <div class="filter-controls">
                            <select id="statusFilter" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="draft" <?php echo ($_GET['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo ($_GET['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="archived" <?php echo ($_GET['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                            
                            <select id="categoryFilter" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                        <?php echo ($_GET['category'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
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
                            <span id="selectedCount">0</span> posts selected
                        </div>
                        <div class="bulk-controls">
                            <select id="bulkAction" class="form-select">
                                <option value="">Choose action...</option>
                                <option value="publish">Publish</option>
                                <option value="draft">Move to Draft</option>
                                <option value="archive">Archive</option>
                                <option value="delete">Delete</option>
                            </select>
                            <button type="button" class="btn btn-primary" id="applyBulkAction">Apply</button>
                            <button type="button" class="btn btn-outline" id="cancelBulkAction">Cancel</button>
                        </div>
                    </div>

                    <!-- Posts Table -->
                    <div class="posts-table-container">
                        <table class="data-table posts-table">
                            <thead>
                                <tr>
                                    <th width="30">
                                        <input type="checkbox" id="selectAllPosts">
                                    </th>
                                    <th>Title</th>
                                    <th width="120">Author</th>
                                    <th width="100">Status</th>
                                    <th width="120">Categories</th>
                                    <th width="100">Views</th>
                                    <th width="120">Date</th>
                                    <th width="100">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($posts)): ?>
                                    <tr>
                                        <td colspan="8" class="empty-state">
                                            <i class="fas fa-blog"></i>
                                            <p>No blog posts found</p>
                                            <a href="blog.php?action=create" class="btn btn-primary">
                                                <i class="fas fa-plus"></i>
                                                Create Your First Post
                                            </a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($posts as $post): ?>
                                        <tr class="post-row <?php echo $post['status'] === 'draft' ? 'draft' : ''; ?>">
                                            <td>
                                                <input type="checkbox" 
                                                       name="post_ids[]" 
                                                       value="<?php echo $post['id']; ?>" 
                                                       class="post-checkbox">
                                            </td>
                                            <td>
                                                <div class="post-title-cell">
                                                    <?php if ($post['featured_image']): ?>
                                                        <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($post['title']); ?>" 
                                                             class="post-thumbnail">
                                                    <?php endif; ?>
                                                    <div class="post-info">
                                                        <a href="blog.php?action=edit&id=<?php echo $post['id']; ?>" 
                                                           class="post-title">
                                                            <?php echo htmlspecialchars($post['title']); ?>
                                                        </a>
                                                        <div class="post-excerpt">
                                                            <?php echo htmlspecialchars(substr($post['excerpt'] ?? '', 0, 100)); ?>...
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="author-info">
                                                    <span class="author-name"><?php echo htmlspecialchars($post['author_name']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($post['status']); ?>">
                                                    <?php echo ucfirst($post['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="post-categories">
                                                    <?php if (isset($post['categories']) && is_array($post['categories'])): ?>
                                                        <?php foreach (array_slice($post['categories'], 0, 2) as $category): ?>
                                                            <span class="category-tag"><?php echo htmlspecialchars($category); ?></span>
                                                        <?php endforeach; ?>
                                                        <?php if (count($post['categories']) > 2): ?>
                                                            <span class="category-more">+<?php echo count($post['categories']) - 2; ?> more</span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="post-views">
                                                    <i class="fas fa-eye"></i>
                                                    <?php echo number_format($post['views']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="post-date">
                                                    <?php echo date('M j, Y', strtotime($post['published_at'] ?? $post['created_at'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="../blog.html?preview=<?php echo $post['id']; ?>" 
                                                       class="btn btn-icon btn-sm" 
                                                       target="_blank"
                                                       title="Preview">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="blog.php?action=edit&id=<?php echo $post['id']; ?>" 
                                                       class="btn btn-icon btn-sm" 
                                                       title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-icon btn-sm btn-danger delete-post" 
                                                            data-post-id="<?php echo $post['id']; ?>"
                                                            data-post-title="<?php echo htmlspecialchars($post['title']); ?>"
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="pagination">
                        <div class="pagination-info">
                            Showing 1-<?php echo count($posts); ?> of <?php echo count($posts); ?> posts
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
                <h3>Delete Post</h3>
                <button class="modal-close" id="closeDeleteModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete "<strong id="deletePostTitle"></strong>"?</p>
                <p class="text-warning">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete_post">
                    <input type="hidden" name="post_id" id="deletePostId">
                    <button type="button" class="btn btn-secondary" id="cancelDelete">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Post</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Upload Modal -->
    <div class="modal" id="imageUploadModal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3>Upload Image</h3>
                <button class="modal-close" id="closeImageModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="image-upload-interface">
                    <div class="upload-area" id="uploadArea">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Drag & drop images here or click to browse</p>
                        <input type="file" 
                               id="imageUpload" 
                               accept="image/*" 
                               multiple 
                               style="display: none;">
                        <button type="button" class="btn btn-primary" id="browseImages">
                            Browse Files
                        </button>
                    </div>
                    <div class="uploaded-images" id="uploadedImages"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/admin.js"></script>
    <script src="assets/js/blog.js"></script>
    
    <!-- Rich Text Editor -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    
    <!-- Tagging System -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tagify/4.17.9/tagify.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tagify/4.17.9/tagify.polyfills.min.js"></script>
    
    <script>
        // Initialize blog functionality
        document.addEventListener('DOMContentLoaded', function() {
            const blogManager = new BlogManager();
            blogManager.init();
        });
    </script>
</body>
</html>