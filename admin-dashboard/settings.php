<?php
/**
 * Admin Settings Page
 * Manages site configuration, personal info, and system settings
 */

session_start();
require_once '../backend/app/Services/AuthService.php';
require_once '../backend/app/Services/SettingsService.php';

// Check if user is authenticated
if (!AuthService::isAuthenticated()) {
    header('Location: login.php');
    exit;
}

// Initialize settings service
$settingsService = new SettingsService();
$settings = $settingsService->getAllSettings();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'save_personal':
                $result = $settingsService->savePersonalSettings($_POST);
                if ($result) {
                    $message = 'Personal settings updated successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'save_social':
                $result = $settingsService->saveSocialSettings($_POST);
                if ($result) {
                    $message = 'Social media settings updated successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'save_site':
                $result = $settingsService->saveSiteSettings($_POST);
                if ($result) {
                    $message = 'Site settings updated successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'save_contact':
                $result = $settingsService->saveContactSettings($_POST);
                if ($result) {
                    $message = 'Contact settings updated successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'upload_image':
                $result = $settingsService->handleImageUpload($_FILES, $_POST);
                if ($result) {
                    $message = 'Image uploaded successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'backup_database':
                $result = $settingsService->createBackup();
                if ($result) {
                    $message = 'Database backup created successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'clear_cache':
                $result = $settingsService->clearCache();
                if ($result) {
                    $message = 'Cache cleared successfully!';
                    $messageType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
    
    // Refresh settings after update
    $settings = $settingsService->getAllSettings();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Imran Shiundu Admin</title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Image Cropper -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
</head>
<body class="admin-dashboard">
    <!-- Navigation Sidebar -->
    <?php include 'partials/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Header -->
        <header class="admin-header">
            <div class="header-left">
                <h1 class="page-title">Settings</h1>
                <p class="page-subtitle">Manage your portfolio configuration and preferences</p>
            </div>
            
            <div class="header-actions">
                <button class="btn btn-secondary" id="previewSite">
                    <i class="fas fa-eye"></i>
                    Preview Site
                </button>
                <button class="btn btn-primary" id="saveAllSettings">
                    <i class="fas fa-save"></i>
                    Save All Changes
                </button>
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

        <!-- Settings Tabs -->
        <div class="settings-container">
            <div class="settings-sidebar">
                <nav class="settings-nav" id="settingsNav">
                    <a href="#personal" class="nav-item active" data-tab="personal">
                        <i class="fas fa-user"></i>
                        Personal Info
                    </a>
                    <a href="#social" class="nav-item" data-tab="social">
                        <i class="fas fa-share-alt"></i>
                        Social Media
                    </a>
                    <a href="#site" class="nav-item" data-tab="site">
                        <i class="fas fa-cog"></i>
                        Site Settings
                    </a>
                    <a href="#contact" class="nav-item" data-tab="contact">
                        <i class="fas fa-envelope"></i>
                        Contact Info
                    </a>
                    <a href="#seo" class="nav-item" data-tab="seo">
                        <i class="fas fa-search"></i>
                        SEO Settings
                    </a>
                    <a href="#appearance" class="nav-item" data-tab="appearance">
                        <i class="fas fa-palette"></i>
                        Appearance
                    </a>
                    <a href="#security" class="nav-item" data-tab="security">
                        <i class="fas fa-shield-alt"></i>
                        Security
                    </a>
                    <a href="#backup" class="nav-item" data-tab="backup">
                        <i class="fas fa-database"></i>
                        Backup & Restore
                    </a>
                </nav>
            </div>

            <div class="settings-content">
                <!-- Personal Information Tab -->
                <div class="settings-tab active" id="personalTab">
                    <div class="tab-header">
                        <h2>Personal Information</h2>
                        <p>Update your personal details and profile information</p>
                    </div>

                    <form method="POST" class="settings-form" id="personalForm">
                        <input type="hidden" name="action" value="save_personal">
                        
                        <div class="form-section">
                            <h3 class="section-title">Profile Details</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="personal_name" class="form-label">Full Name *</label>
                                    <input type="text" 
                                           id="personal_name" 
                                           name="personal_name" 
                                           class="form-input" 
                                           value="<?php echo htmlspecialchars($settings['personal']['name'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="personal_title" class="form-label">Professional Title *</label>
                                    <input type="text" 
                                           id="personal_title" 
                                           name="personal_title" 
                                           class="form-input" 
                                           value="<?php echo htmlspecialchars($settings['personal']['title'] ?? ''); ?>" 
                                           required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="personal_bio" class="form-label">Bio/Introduction *</label>
                                <textarea id="personal_bio" 
                                          name="personal_bio" 
                                          class="form-textarea" 
                                          rows="4" 
                                          required><?php echo htmlspecialchars($settings['personal']['bio'] ?? ''); ?></textarea>
                                <div class="form-help">Brief introduction displayed on your portfolio</div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">Profile Image</h3>
                            
                            <div class="image-upload-section">
                                <div class="current-image">
                                    <img src="<?php echo htmlspecialchars($settings['personal']['avatar'] ?? '../assets/images/default-avatar.jpg'); ?>" 
                                         alt="Current Profile Image" 
                                         id="currentAvatar">
                                    <div class="image-info">
                                        <p>Current Profile Image</p>
                                        <span>Recommended: 500x500px, JPG or PNG</span>
                                    </div>
                                </div>
                                
                                <div class="upload-controls">
                                    <div class="file-upload">
                                        <input type="file" 
                                               id="avatarUpload" 
                                               name="avatar" 
                                               accept="image/*" 
                                               class="file-input">
                                        <label for="avatarUpload" class="btn btn-secondary">
                                            <i class="fas fa-upload"></i>
                                            Upload New Image
                                        </label>
                                    </div>
                                    <button type="button" class="btn btn-outline" id="removeAvatar">
                                        <i class="fas fa-trash"></i>
                                        Remove Image
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">Location & Availability</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="personal_location" class="form-label">Location</label>
                                    <input type="text" 
                                           id="personal_location" 
                                           name="personal_location" 
                                           class="form-input" 
                                           value="<?php echo htmlspecialchars($settings['personal']['location'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="personal_availability" class="form-label">Availability</label>
                                    <select id="personal_availability" name="personal_availability" class="form-select">
                                        <option value="available" <?php echo ($settings['personal']['availability'] ?? '') === 'available' ? 'selected' : ''; ?>>Available for new projects</option>
                                        <option value="busy" <?php echo ($settings['personal']['availability'] ?? '') === 'busy' ? 'selected' : ''; ?>>Busy with current projects</option>
                                        <option value="unavailable" <?php echo ($settings['personal']['availability'] ?? '') === 'unavailable' ? 'selected' : ''; ?>>Not available</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Save Personal Information
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Social Media Tab -->
                <div class="settings-tab" id="socialTab">
                    <div class="tab-header">
                        <h2>Social Media Links</h2>
                        <p>Manage your social media profiles and links</p>
                    </div>

                    <form method="POST" class="settings-form" id="socialForm">
                        <input type="hidden" name="action" value="save_social">
                        
                        <div class="social-platforms-grid">
                            <!-- GitHub -->
                            <div class="platform-card">
                                <div class="platform-header">
                                    <div class="platform-icon github">
                                        <i class="fab fa-github"></i>
                                    </div>
                                    <div class="platform-info">
                                        <h4>GitHub</h4>
                                        <p>Your code repositories</p>
                                    </div>
                                </div>
                                <div class="platform-input">
                                    <input type="url" 
                                           name="social_github" 
                                           class="form-input" 
                                           placeholder="https://github.com/username"
                                           value="<?php echo htmlspecialchars($settings['social']['github'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- LinkedIn -->
                            <div class="platform-card">
                                <div class="platform-header">
                                    <div class="platform-icon linkedin">
                                        <i class="fab fa-linkedin"></i>
                                    </div>
                                    <div class="platform-info">
                                        <h4>LinkedIn</h4>
                                        <p>Professional network</p>
                                    </div>
                                </div>
                                <div class="platform-input">
                                    <input type="url" 
                                           name="social_linkedin" 
                                           class="form-input" 
                                           placeholder="https://linkedin.com/in/username"
                                           value="<?php echo htmlspecialchars($settings['social']['linkedin'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- Twitter -->
                            <div class="platform-card">
                                <div class="platform-header">
                                    <div class="platform-icon twitter">
                                        <i class="fab fa-twitter"></i>
                                    </div>
                                    <div class="platform-info">
                                        <h4>Twitter</h4>
                                        <p>Latest updates</p>
                                    </div>
                                </div>
                                <div class="platform-input">
                                    <input type="url" 
                                           name="social_twitter" 
                                           class="form-input" 
                                           placeholder="https://twitter.com/username"
                                           value="<?php echo htmlspecialchars($settings['social']['twitter'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- YouTube -->
                            <div class="platform-card">
                                <div class="platform-header">
                                    <div class="platform-icon youtube">
                                        <i class="fab fa-youtube"></i>
                                    </div>
                                    <div class="platform-info">
                                        <h4>YouTube</h4>
                                        <p>Video content</p>
                                    </div>
                                </div>
                                <div class="platform-input">
                                    <input type="url" 
                                           name="social_youtube" 
                                           class="form-input" 
                                           placeholder="https://youtube.com/@username"
                                           value="<?php echo htmlspecialchars($settings['social']['youtube'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- Instagram -->
                            <div class="platform-card">
                                <div class="platform-header">
                                    <div class="platform-icon instagram">
                                        <i class="fab fa-instagram"></i>
                                    </div>
                                    <div class="platform-info">
                                        <h4>Instagram</h4>
                                        <p>Visual stories</p>
                                    </div>
                                </div>
                                <div class="platform-input">
                                    <input type="url" 
                                           name="social_instagram" 
                                           class="form-input" 
                                           placeholder="https://instagram.com/username"
                                           value="<?php echo htmlspecialchars($settings['social']['instagram'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- Facebook -->
                            <div class="platform-card">
                                <div class="platform-header">
                                    <div class="platform-icon facebook">
                                        <i class="fab fa-facebook"></i>
                                    </div>
                                    <div class="platform-info">
                                        <h4>Facebook</h4>
                                        <p>Social network</p>
                                    </div>
                                </div>
                                <div class="platform-input">
                                    <input type="url" 
                                           name="social_facebook" 
                                           class="form-input" 
                                           placeholder="https://facebook.com/username"
                                           value="<?php echo htmlspecialchars($settings['social']['facebook'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- Stack Overflow -->
                            <div class="platform-card">
                                <div class="platform-header">
                                    <div class="platform-icon stackoverflow">
                                        <i class="fab fa-stack-overflow"></i>
                                    </div>
                                    <div class="platform-info">
                                        <h4>Stack Overflow</h4>
                                        <p>Developer community</p>
                                    </div>
                                </div>
                                <div class="platform-input">
                                    <input type="url" 
                                           name="social_stackoverflow" 
                                           class="form-input" 
                                           placeholder="https://stackoverflow.com/users/userid"
                                           value="<?php echo htmlspecialchars($settings['social']['stackoverflow'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- Personal Website -->
                            <div class="platform-card">
                                <div class="platform-header">
                                    <div class="platform-icon website">
                                        <i class="fas fa-globe"></i>
                                    </div>
                                    <div class="platform-info">
                                        <h4>Personal Website</h4>
                                        <p>Your main website</p>
                                    </div>
                                </div>
                                <div class="platform-input">
                                    <input type="url" 
                                           name="social_website" 
                                           class="form-input" 
                                           placeholder="https://yourwebsite.com"
                                           value="<?php echo htmlspecialchars($settings['social']['website'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Save Social Media Links
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Site Settings Tab -->
                <div class="settings-tab" id="siteTab">
                    <div class="tab-header">
                        <h2>Site Settings</h2>
                        <p>Configure your portfolio website behavior and features</p>
                    </div>

                    <form method="POST" class="settings-form" id="siteForm">
                        <input type="hidden" name="action" value="save_site">
                        
                        <div class="form-section">
                            <h3 class="section-title">Basic Information</h3>
                            
                            <div class="form-group">
                                <label for="site_title" class="form-label">Site Title *</label>
                                <input type="text" 
                                       id="site_title" 
                                       name="site_title" 
                                       class="form-input" 
                                       value="<?php echo htmlspecialchars($settings['site']['title'] ?? ''); ?>" 
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_description" class="form-label">Site Description *</label>
                                <textarea id="site_description" 
                                          name="site_description" 
                                          class="form-textarea" 
                                          rows="3" 
                                          required><?php echo htmlspecialchars($settings['site']['description'] ?? ''); ?></textarea>
                                <div class="form-help">Brief description for search engines and social media</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_keywords" class="form-label">Keywords</label>
                                <input type="text" 
                                       id="site_keywords" 
                                       name="site_keywords" 
                                       class="form-input" 
                                       value="<?php echo htmlspecialchars($settings['site']['keywords'] ?? ''); ?>">
                                <div class="form-help">Comma-separated keywords for SEO</div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">Features & Modules</h3>
                            
                            <div class="features-grid">
                                <label class="feature-toggle">
                                    <input type="checkbox" 
                                           name="feature_blog" 
                                           value="1" 
                                           <?php echo ($settings['site']['features']['blog'] ?? true) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                    <div class="feature-info">
                                        <span class="feature-name">Blog Module</span>
                                        <span class="feature-desc">Enable blog functionality</span>
                                    </div>
                                </label>
                                
                                <label class="feature-toggle">
                                    <input type="checkbox" 
                                           name="feature_contact" 
                                           value="1" 
                                           <?php echo ($settings['site']['features']['contact'] ?? true) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                    <div class="feature-info">
                                        <span class="feature-name">Contact Form</span>
                                        <span class="feature-desc">Enable contact form</span>
                                    </div>
                                </label>
                                
                                <label class="feature-toggle">
                                    <input type="checkbox" 
                                           name="feature_dark_mode" 
                                           value="1" 
                                           <?php echo ($settings['site']['features']['dark_mode'] ?? true) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                    <div class="feature-info">
                                        <span class="feature-name">Dark Mode</span>
                                        <span class="feature-desc">Enable dark/light mode toggle</span>
                                    </div>
                                </label>
                                
                                <label class="feature-toggle">
                                    <input type="checkbox" 
                                           name="feature_analytics" 
                                           value="1" 
                                           <?php echo ($settings['site']['features']['analytics'] ?? true) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                    <div class="feature-info">
                                        <span class="feature-name">Analytics</span>
                                        <span class="feature-desc">Enable visitor analytics</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">Maintenance Mode</h3>
                            
                            <div class="maintenance-settings">
                                <label class="maintenance-toggle">
                                    <input type="checkbox" 
                                           name="maintenance_mode" 
                                           value="1" 
                                           <?php echo ($settings['site']['maintenance_mode'] ?? false) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                    <span class="toggle-label">Enable Maintenance Mode</span>
                                </label>
                                
                                <div class="maintenance-message" id="maintenanceMessage" style="<?php echo ($settings['site']['maintenance_mode'] ?? false) ? '' : 'display: none;'; ?>">
                                    <label for="maintenance_text" class="form-label">Maintenance Message</label>
                                    <textarea id="maintenance_text" 
                                              name="maintenance_text" 
                                              class="form-textarea" 
                                              rows="3"><?php echo htmlspecialchars($settings['site']['maintenance_text'] ?? 'Site is under maintenance. Please check back later.'); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Save Site Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Contact Information Tab -->
                <div class="settings-tab" id="contactTab">
                    <div class="tab-header">
                        <h2>Contact Information</h2>
                        <p>Manage how visitors can contact you</p>
                    </div>

                    <form method="POST" class="settings-form" id="contactForm">
                        <input type="hidden" name="action" value="save_contact">
                        
                        <div class="form-section">
                            <h3 class="section-title">Contact Details</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="contact_email" class="form-label">Email Address *</label>
                                    <input type="email" 
                                           id="contact_email" 
                                           name="contact_email" 
                                           class="form-input" 
                                           value="<?php echo htmlspecialchars($settings['contact']['email'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="contact_phone" class="form-label">Phone Number</label>
                                    <input type="tel" 
                                           id="contact_phone" 
                                           name="contact_phone" 
                                           class="form-input" 
                                           value="<?php echo htmlspecialchars($settings['contact']['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_subtitle" class="form-label">Contact Page Subtitle</label>
                                <input type="text" 
                                       id="contact_subtitle" 
                                       name="contact_subtitle" 
                                       class="form-input" 
                                       value="<?php echo htmlspecialchars($settings['contact']['subtitle'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_description" class="form-label">Contact Page Description</label>
                                <textarea id="contact_description" 
                                          name="contact_description" 
                                          class="form-textarea" 
                                          rows="3"><?php echo htmlspecialchars($settings['contact']['description'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">Contact Statistics</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="contact_response_time" class="form-label">Response Time (hours)</label>
                                    <input type="number" 
                                           id="contact_response_time" 
                                           name="contact_response_time" 
                                           class="form-input" 
                                           min="1" 
                                           max="168" 
                                           value="<?php echo htmlspecialchars($settings['contact']['response_time'] ?? '24'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="contact_projects_completed" class="form-label">Projects Completed</label>
                                    <input type="text" 
                                           id="contact_projects_completed" 
                                           name="contact_projects_completed" 
                                           class="form-input" 
                                           value="<?php echo htmlspecialchars($settings['contact']['projects_completed'] ?? '50+'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="contact_collaboration_count" class="form-label">Collaborations</label>
                                    <input type="text" 
                                           id="contact_collaboration_count" 
                                           name="contact_collaboration_count" 
                                           class="form-input" 
                                           value="<?php echo htmlspecialchars($settings['contact']['collaboration_count'] ?? '15+'); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Save Contact Information
                            </button>
                        </div>
                    </form>
                </div>

                <!-- SEO Settings Tab -->
                <div class="settings-tab" id="seoTab">
                    <div class="tab-header">
                        <h2>SEO Settings</h2>
                        <p>Optimize your portfolio for search engines</p>
                    </div>

                    <form method="POST" class="settings-form" id="seoForm">
                        <input type="hidden" name="action" value="save_seo">
                        
                        <div class="form-section">
                            <h3 class="section-title">Meta Information</h3>
                            
                            <div class="form-group">
                                <label for="seo_meta_title" class="form-label">Default Meta Title</label>
                                <input type="text" 
                                       id="seo_meta_title" 
                                       name="seo_meta_title" 
                                       class="form-input" 
                                       value="<?php echo htmlspecialchars($settings['seo']['meta_title'] ?? ''); ?>">
                                <div class="form-help">Recommended: 50-60 characters</div>
                                <div class="character-count">
                                    <span id="metaTitleCount">0</span>/60
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="seo_meta_description" class="form-label">Default Meta Description</label>
                                <textarea id="seo_meta_description" 
                                          name="seo_meta_description" 
                                          class="form-textarea" 
                                          rows="3"><?php echo htmlspecialchars($settings['seo']['meta_description'] ?? ''); ?></textarea>
                                <div class="form-help">Recommended: 150-160 characters</div>
                                <div class="character-count">
                                    <span id="metaDescriptionCount">0</span>/160
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">Open Graph</h3>
                            
                            <div class="form-group">
                                <label for="seo_og_image" class="form-label">Open Graph Image</label>
                                <div class="image-upload">
                                    <div class="current-og-image">
                                        <img src="<?php echo htmlspecialchars($settings['seo']['og_image'] ?? '../assets/images/default-og.jpg'); ?>" 
                                             alt="Current OG Image" 
                                             id="currentOgImage">
                                    </div>
                                    <input type="file" 
                                           id="ogImageUpload" 
                                           name="og_image" 
                                           accept="image/*" 
                                           class="file-input">
                                    <label for="ogImageUpload" class="btn btn-outline">
                                        <i class="fas fa-upload"></i>
                                        Upload OG Image
                                    </label>
                                </div>
                                <div class="form-help">Recommended: 1200x630px, JPG or PNG</div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">Google Analytics</h3>
                            
                            <div class="form-group">
                                <label for="seo_ga_tracking_id" class="form-label">Google Analytics Tracking ID</label>
                                <input type="text" 
                                       id="seo_ga_tracking_id" 
                                       name="seo_ga_tracking_id" 
                                       class="form-input" 
                                       placeholder="G-XXXXXXXXXX"
                                       value="<?php echo htmlspecialchars($settings['seo']['ga_tracking_id'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="seo_gtm_id" class="form-label">Google Tag Manager ID</label>
                                <input type="text" 
                                       id="seo_gtm_id" 
                                       name="seo_gtm_id" 
                                       class="form-input" 
                                       placeholder="GTM-XXXXXX"
                                       value="<?php echo htmlspecialchars($settings['seo']['gtm_id'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Save SEO Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Appearance Tab -->
                <div class="settings-tab" id="appearanceTab">
                    <div class="tab-header">
                        <h2>Appearance</h2>
                        <p>Customize the look and feel of your portfolio</p>
                    </div>

                    <form method="POST" class="settings-form" id="appearanceForm">
                        <input type="hidden" name="action" value="save_appearance">
                        
                        <div class="form-section">
                            <h3 class="section-title">Color Scheme</h3>
                            
                            <div class="color-palette-grid">
                                <div class="color-picker-group">
                                    <label for="color_primary" class="form-label">Primary Color</label>
                                    <div class="color-picker-container">
                                        <input type="color" 
                                               id="color_primary" 
                                               name="color_primary" 
                                               value="<?php echo htmlspecialchars($settings['appearance']['colors']['primary'] ?? '#0D47A1'); ?>">
                                        <input type="text" 
                                               class="color-hex-input" 
                                               value="<?php echo htmlspecialchars($settings['appearance']['colors']['primary'] ?? '#0D47A1'); ?>"
                                               readonly>
                                    </div>
                                </div>
                                
                                <div class="color-picker-group">
                                    <label for="color_secondary" class="form-label">Secondary Color</label>
                                    <div class="color-picker-container">
                                        <input type="color" 
                                               id="color_secondary" 
                                               name="color_secondary" 
                                               value="<?php echo htmlspecialchars($settings['appearance']['colors']['secondary'] ?? '#FF6D00'); ?>">
                                        <input type="text" 
                                               class="color-hex-input" 
                                               value="<?php echo htmlspecialchars($settings['appearance']['colors']['secondary'] ?? '#FF6D00'); ?>"
                                               readonly>
                                    </div>
                                </div>
                                
                                <div class="color-picker-group">
                                    <label for="color_accent" class="form-label">Accent Color</label>
                                    <div class="color-picker-container">
                                        <input type="color" 
                                               id="color_accent" 
                                               name="color_accent" 
                                               value="<?php echo htmlspecialchars($settings['appearance']['colors']['accent'] ?? '#2E7D32'); ?>">
                                        <input type="text" 
                                               class="color-hex-input" 
                                               value="<?php echo htmlspecialchars($settings['appearance']['colors']['accent'] ?? '#2E7D32'); ?>"
                                               readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">Typography</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="font_primary" class="form-label">Primary Font</label>
                                    <select id="font_primary" name="font_primary" class="form-select">
                                        <option value="Inter" <?php echo ($settings['appearance']['fonts']['primary'] ?? 'Inter') === 'Inter' ? 'selected' : ''; ?>>Inter</option>
                                        <option value="Poppins" <?php echo ($settings['appearance']['fonts']['primary'] ?? 'Inter') === 'Poppins' ? 'selected' : ''; ?>>Poppins</option>
                                        <option value="Roboto" <?php echo ($settings['appearance']['fonts']['primary'] ?? 'Inter') === 'Roboto' ? 'selected' : ''; ?>>Roboto</option>
                                        <option value="Open Sans" <?php echo ($settings['appearance']['fonts']['primary'] ?? 'Inter') === 'Open Sans' ? 'selected' : ''; ?>>Open Sans</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="font_secondary" class="form-label">Secondary Font</label>
                                    <select id="font_secondary" name="font_secondary" class="form-select">
                                        <option value="Source Sans Pro" <?php echo ($settings['appearance']['fonts']['secondary'] ?? 'Source Sans Pro') === 'Source Sans Pro' ? 'selected' : ''; ?>>Source Sans Pro</option>
                                        <option value="Inter" <?php echo ($settings['appearance']['fonts']['secondary'] ?? 'Source Sans Pro') === 'Inter' ? 'selected' : ''; ?>>Inter</option>
                                        <option value="Roboto" <?php echo ($settings['appearance']['fonts']['secondary'] ?? 'Source Sans Pro') === 'Roboto' ? 'selected' : ''; ?>>Roboto</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">Layout</h3>
                            
                            <div class="form-group">
                                <label for="layout_type" class="form-label">Layout Style</label>
                                <select id="layout_type" name="layout_type" class="form-select">
                                    <option value="modern" <?php echo ($settings['appearance']['layout'] ?? 'modern') === 'modern' ? 'selected' : ''; ?>>Modern</option>
                                    <option value="minimal" <?php echo ($settings['appearance']['layout'] ?? 'modern') === 'minimal' ? 'selected' : ''; ?>>Minimal</option>
                                    <option value="creative" <?php echo ($settings['appearance']['layout'] ?? 'modern') === 'creative' ? 'selected' : ''; ?>>Creative</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" 
                                           name="enable_animations" 
                                           value="1" 
                                           <?php echo ($settings['appearance']['animations'] ?? true) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Enable Animations
                                </label>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Save Appearance Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Security Tab -->
                <div class="settings-tab" id="securityTab">
                    <div class="tab-header">
                        <h2>Security Settings</h2>
                        <p>Manage security preferences and access controls</p>
                    </div>

                    <form method="POST" class="settings-form" id="securityForm">
                        <input type="hidden" name="action" value="save_security">
                        
                        <div class="form-section">
                            <h3 class="section-title">Admin Security</h3>
                            
                            <div class="form-group">
                                <label for="admin_session_timeout" class="form-label">Session Timeout (minutes)</label>
                                <input type="number" 
                                       id="admin_session_timeout" 
                                       name="admin_session_timeout" 
                                       class="form-input" 
                                       min="5" 
                                       max="1440" 
                                       value="<?php echo htmlspecialchars($settings['security']['session_timeout'] ?? '60'); ?>">
                                <div class="form-help">Time before automatic logout due to inactivity</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" 
                                           name="enable_2fa" 
                                           value="1" 
                                           <?php echo ($settings['security']['enable_2fa'] ?? false) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Enable Two-Factor Authentication
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" 
                                           name="login_notifications" 
                                           value="1" 
                                           <?php echo ($settings['security']['login_notifications'] ?? true) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Send email notifications for new logins
                                </label>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">Password Policy</h3>
                            
                            <div class="form-group">
                                <label for="password_min_length" class="form-label">Minimum Password Length</label>
                                <input type="number" 
                                       id="password_min_length" 
                                       name="password_min_length" 
                                       class="form-input" 
                                       min="8" 
                                       max="32" 
                                       value="<?php echo htmlspecialchars($settings['security']['password_min_length'] ?? '12'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" 
                                           name="password_require_special" 
                                           value="1" 
                                           <?php echo ($settings['security']['password_require_special'] ?? true) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Require special characters in passwords
                                </label>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">Security Logs</h3>
                            
                            <div class="security-logs">
                                <div class="log-entries">
                                    <div class="log-entry">
                                        <div class="log-icon">
                                            <i class="fas fa-shield-alt text-success"></i>
                                        </div>
                                        <div class="log-content">
                                            <p>Successful login from Nairobi, Kenya</p>
                                            <span class="log-time">2 hours ago</span>
                                        </div>
                                    </div>
                                    <div class="log-entry">
                                        <div class="log-icon">
                                            <i class="fas fa-exclamation-triangle text-warning"></i>
                                        </div>
                                        <div class="log-content">
                                            <p>Failed login attempt from unknown location</p>
                                            <span class="log-time">5 hours ago</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="button" class="btn btn-outline" id="viewAllLogs">
                                    <i class="fas fa-list"></i>
                                    View All Security Logs
                                </button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Save Security Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Backup & Restore Tab -->
                <div class="settings-tab" id="backupTab">
                    <div class="tab-header">
                        <h2>Backup & Restore</h2>
                        <p>Manage database backups and system restoration</p>
                    </div>

                    <div class="backup-sections">
                        <!-- Create Backup -->
                        <div class="backup-section">
                            <h3 class="section-title">Create Backup</h3>
                            <div class="backup-options">
                                <div class="backup-option">
                                    <div class="option-icon">
                                        <i class="fas fa-database"></i>
                                    </div>
                                    <div class="option-content">
                                        <h4>Database Backup</h4>
                                        <p>Export your database as SQL file</p>
                                    </div>
                                    <form method="POST" class="backup-form">
                                        <input type="hidden" name="action" value="backup_database">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-download"></i>
                                            Create Backup
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="backup-option">
                                    <div class="option-icon">
                                        <i class="fas fa-file-archive"></i>
                                    </div>
                                    <div class="option-content">
                                        <h4>Full System Backup</h4>
                                        <p>Backup database and uploaded files</p>
                                    </div>
                                    <button type="button" class="btn btn-secondary" id="fullBackupBtn">
                                        <i class="fas fa-download"></i>
                                        Create Full Backup
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Restore Backup -->
                        <div class="backup-section">
                            <h3 class="section-title">Restore Backup</h3>
                            <div class="restore-options">
                                <div class="file-upload-area">
                                    <div class="upload-icon">
                                        <i class="fas fa-file-upload"></i>
                                    </div>
                                    <div class="upload-content">
                                        <h4>Upload Backup File</h4>
                                        <p>Select SQL file to restore database</p>
                                        <input type="file" 
                                               id="backupFile" 
                                               name="backup_file" 
                                               accept=".sql,.zip" 
                                               class="file-input">
                                        <label for="backupFile" class="btn btn-outline">
                                            <i class="fas fa-upload"></i>
                                            Choose File
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="button" class="btn btn-warning" id="restoreBackupBtn" disabled>
                                    <i class="fas fa-undo"></i>
                                    Restore Backup
                                </button>
                            </div>
                        </div>

                        <!-- Backup History -->
                        <div class="backup-section">
                            <h3 class="section-title">Backup History</h3>
                            <div class="backup-history">
                                <div class="backup-list">
                                    <div class="backup-item">
                                        <div class="backup-info">
                                            <div class="backup-icon">
                                                <i class="fas fa-database"></i>
                                            </div>
                                            <div class="backup-details">
                                                <h4>backup_2024_10_15_143022.sql</h4>
                                                <p>Created 2 hours ago • 2.4 MB</p>
                                            </div>
                                        </div>
                                        <div class="backup-actions">
                                            <button class="btn btn-icon btn-sm" title="Download">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-icon btn-sm" title="Restore">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                            <button class="btn btn-icon btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="backup-item">
                                        <div class="backup-info">
                                            <div class="backup-icon">
                                                <i class="fas fa-file-archive"></i>
                                            </div>
                                            <div class="backup-details">
                                                <h4>full_backup_2024_10_14_093415.zip</h4>
                                                <p>Created 1 day ago • 45.2 MB</p>
                                            </div>
                                        </div>
                                        <div class="backup-actions">
                                            <button class="btn btn-icon btn-sm" title="Download">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-icon btn-sm" title="Restore">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                            <button class="btn btn-icon btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System Tools -->
                        <div class="backup-section">
                            <h3 class="section-title">System Tools</h3>
                            <div class="system-tools">
                                <div class="tool-option">
                                    <div class="tool-icon">
                                        <i class="fas fa-broom"></i>
                                    </div>
                                    <div class="tool-content">
                                        <h4>Clear Cache</h4>
                                        <p>Remove temporary files and cached data</p>
                                    </div>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="clear_cache">
                                        <button type="submit" class="btn btn-outline">
                                            <i class="fas fa-broom"></i>
                                            Clear Cache
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="tool-option">
                                    <div class="tool-icon">
                                        <i class="fas fa-optimize"></i>
                                    </div>
                                    <div class="tool-content">
                                        <h4>Optimize Database</h4>
                                        <p>Improve database performance</p>
                                    </div>
                                    <button type="button" class="btn btn-outline" id="optimizeDbBtn">
                                        <i class="fas fa-rocket"></i>
                                        Optimize Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Image Cropper Modal -->
    <div class="modal" id="imageCropModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Crop Image</h3>
                <button class="modal-close" id="closeCropModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="crop-container">
                    <img id="cropImage" src="" alt="Image to crop">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelCrop">Cancel</button>
                <button class="btn btn-primary" id="applyCrop">Apply Crop</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/admin.js"></script>
    <script src="assets/js/settings.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
</body>
</html>