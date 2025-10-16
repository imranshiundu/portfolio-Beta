<?php
/**
 * Blog Service - Handles all blog-related business logic
 */

class BlogService {
    private $db;
    private $uploadPath;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->uploadPath = '../storage/blog/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Get all blog posts with filtering and pagination
     */
    public function getPosts($filters = [], $page = 1, $perPage = 10) {
        $whereConditions = [];
        $params = [];
        
        // Build WHERE conditions based on filters
        if (!empty($filters['status'])) {
            $whereConditions[] = 'p.status = ?';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['category'])) {
            $whereConditions[] = 'JSON_CONTAINS(p.categories, ?)';
            $params[] = json_encode($filters['category']);
        }
        
        if (!empty($filters['search'])) {
            $whereConditions[] = '(p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM blog_posts p $whereClause";
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Calculate pagination
        $offset = ($page - 1) * $perPage;
        
        // Get posts
        $query = "
            SELECT 
                p.*,
                LENGTH(p.content) - LENGTH(REPLACE(p.content, ' ', '')) + 1 as word_count
            FROM blog_posts p 
            $whereClause 
            ORDER BY 
                CASE WHEN p.status = 'published' THEN 0 ELSE 1 END,
                p.published_at DESC,
                p.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $perPage;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse JSON fields and calculate reading time
        foreach ($posts as &$post) {
            $post['categories'] = json_decode($post['categories'] ?? '[]', true) ?: [];
            $post['tags'] = json_decode($post['tags'] ?? '[]', true) ?: [];
            $post['reading_time'] = $this->calculateReadingTime($post['word_count']);
            unset($post['word_count']);
        }
        
        return [
            'posts' => $posts,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get single blog post by ID
     */
    public function getPost($id) {
        $query = "
            SELECT 
                p.*,
                LENGTH(p.content) - LENGTH(REPLACE(p.content, ' ', '')) + 1 as word_count
            FROM blog_posts p 
            WHERE p.id = ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$post) {
            return null;
        }
        
        // Parse JSON fields
        $post['categories'] = json_decode($post['categories'] ?? '[]', true) ?: [];
        $post['tags'] = json_decode($post['tags'] ?? '[]', true) ?: [];
        $post['reading_time'] = $this->calculateReadingTime($post['word_count']);
        unset($post['word_count']);
        
        return $post;
    }

    /**
     * Create new blog post
     */
    public function createPost($data, $files = []) {
        $this->db->beginTransaction();
        
        try {
            // Handle featured image upload
            $featuredImage = $this->handleFeaturedImageUpload($files['featured_image'] ?? null);
            
            // Process categories and tags
            $categories = $this->processCategories($data['post_categories'] ?? []);
            $tags = $this->processTags($data['post_tags'] ?? '');
            
            // Determine publish date
            $publishedAt = null;
            if ($data['post_status'] === 'published') {
                if (!empty($data['post_schedule'])) {
                    $publishedAt = $data['post_schedule'];
                } else {
                    $publishedAt = date('Y-m-d H:i:s');
                }
            }
            
            // Insert post
            $query = "
                INSERT INTO blog_posts (
                    title, slug, excerpt, content, featured_image, author_name,
                    categories, tags, meta_title, meta_description, status,
                    published_at, is_new
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $slug = $this->generateSlug($data['post_title']);
            $authorName = $data['author_name'] ?? 'Imran Shiundu';
            $isNew = ($data['post_status'] === 'published' && empty($data['post_schedule'])) ? 1 : 0;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['post_title'],
                $slug,
                $data['post_excerpt'] ?? '',
                $data['post_content'],
                $featuredImage,
                $authorName,
                json_encode($categories),
                json_encode($tags),
                $data['meta_title'] ?? '',
                $data['meta_description'] ?? '',
                $data['post_status'],
                $publishedAt,
                $isNew
            ]);
            
            $postId = $this->db->lastInsertId();
            
            $this->db->commit();
            
            // Log activity
            $this->logActivity('post_created', "Created blog post: {$data['post_title']}");
            
            return $postId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Failed to create blog post: " . $e->getMessage());
        }
    }

    /**
     * Update existing blog post
     */
    public function updatePost($id, $data, $files = []) {
        $this->db->beginTransaction();
        
        try {
            $existingPost = $this->getPost($id);
            if (!$existingPost) {
                throw new Exception("Blog post not found");
            }
            
            // Handle featured image upload
            $featuredImage = $existingPost['featured_image'];
            if (!empty($files['featured_image']['name'])) {
                // Delete old image
                if ($existingPost['featured_image'] && file_exists($existingPost['featured_image'])) {
                    unlink($existingPost['featured_image']);
                }
                $featuredImage = $this->handleFeaturedImageUpload($files['featured_image']);
            }
            
            // Process categories and tags
            $categories = $this->processCategories($data['post_categories'] ?? []);
            $tags = $this->processTags($data['post_tags'] ?? '');
            
            // Determine publish date
            $publishedAt = $existingPost['published_at'];
            $isNew = $existingPost['is_new'];
            
            if ($data['post_status'] === 'published' && !$existingPost['published_at']) {
                if (!empty($data['post_schedule'])) {
                    $publishedAt = $data['post_schedule'];
                } else {
                    $publishedAt = date('Y-m-d H:i:s');
                    $isNew = 1;
                }
            }
            
            // Update post
            $query = "
                UPDATE blog_posts SET 
                    title = ?, slug = ?, excerpt = ?, content = ?, 
                    featured_image = ?, author_name = ?, categories = ?, 
                    tags = ?, meta_title = ?, meta_description = ?, 
                    status = ?, published_at = ?, is_new = ?, updated_at = NOW()
                WHERE id = ?
            ";
            
            $slug = $data['post_slug'] ?? $this->generateSlug($data['post_title'], $id);
            $authorName = $data['author_name'] ?? 'Imran Shiundu';
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['post_title'],
                $slug,
                $data['post_excerpt'] ?? '',
                $data['post_content'],
                $featuredImage,
                $authorName,
                json_encode($categories),
                json_encode($tags),
                $data['meta_title'] ?? '',
                $data['meta_description'] ?? '',
                $data['post_status'],
                $publishedAt,
                $isNew,
                $id
            ]);
            
            $this->db->commit();
            
            // Log activity
            $this->logActivity('post_updated', "Updated blog post: {$data['post_title']}");
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Failed to update blog post: " . $e->getMessage());
        }
    }

    /**
     * Delete blog post
     */
    public function deletePost($id) {
        $this->db->beginTransaction();
        
        try {
            $post = $this->getPost($id);
            if (!$post) {
                throw new Exception("Blog post not found");
            }
            
            // Delete featured image
            if ($post['featured_image'] && file_exists($post['featured_image'])) {
                unlink($post['featured_image']);
            }
            
            // Delete from database
            $stmt = $this->db->prepare("DELETE FROM blog_posts WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->db->commit();
            
            // Log activity
            $this->logActivity('post_deleted', "Deleted blog post: {$post['title']}");
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Failed to delete blog post: " . $e->getMessage());
        }
    }

    /**
     * Handle bulk actions
     */
    public function handleBulkAction($data) {
        $postIds = $data['post_ids'] ?? [];
        $action = $data['bulk_action'] ?? '';
        
        if (empty($postIds) || empty($action)) {
            throw new Exception("No posts selected or action specified");
        }
        
        $placeholders = str_repeat('?,', count($postIds) - 1) . '?';
        
        switch ($action) {
            case 'publish':
                $query = "UPDATE blog_posts SET status = 'published', published_at = NOW() WHERE id IN ($placeholders)";
                break;
            case 'draft':
                $query = "UPDATE blog_posts SET status = 'draft', published_at = NULL WHERE id IN ($placeholders)";
                break;
            case 'archive':
                $query = "UPDATE blog_posts SET status = 'archived' WHERE id IN ($placeholders)";
                break;
            case 'delete':
                // Delete each post individually to handle file cleanup
                foreach ($postIds as $postId) {
                    $this->deletePost($postId);
                }
                return true;
            default:
                throw new Exception("Invalid bulk action");
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($postIds);
        
        // Log activity
        $this->logActivity('bulk_action', "Performed bulk action '$action' on " . count($postIds) . " posts");
        
        return true;
    }

    /**
     * Get blog categories
     */
    public function getCategories() {
        // In a real application, you might have a categories table
        // For now, return predefined categories
        return [
            ['id' => 1, 'name' => 'Web Development'],
            ['id' => 2, 'name' => 'Mobile Development'],
            ['id' => 3, 'name' => 'Backend Development'],
            ['id' => 4, 'name' => 'Frontend Development'],
            ['id' => 5, 'name' => 'DevOps'],
            ['id' => 6, 'name' => 'Career'],
            ['id' => 7, 'name' => 'Tutorials'],
            ['id' => 8, 'name' => 'Case Studies']
        ];
    }

    /**
     * Get popular tags
     */
    public function getTags() {
        $stmt = $this->db->query("
            SELECT DISTINCT tag
            FROM (
                SELECT JSON_UNQUOTE(JSON_EXTRACT(tags, '$[0]')) as tag FROM blog_posts
                UNION ALL
                SELECT JSON_UNQUOTE(JSON_EXTRACT(tags, '$[1]')) as tag FROM blog_posts
                UNION ALL
                SELECT JSON_UNQUOTE(JSON_EXTRACT(tags, '$[2]')) as tag FROM blog_posts
            ) as all_tags
            WHERE tag IS NOT NULL AND tag != ''
            ORDER BY tag ASC
            LIMIT 50
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Calculate reading time
     */
    private function calculateReadingTime($wordCount) {
        $wordsPerMinute = 200;
        $readingTime = ceil($wordCount / $wordsPerMinute);
        return max(1, $readingTime); // Minimum 1 minute
    }

    /**
     * Handle featured image upload
     */
    private function handleFeaturedImageUpload($file) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return '';
        }
        
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Invalid file type. Only JPG, PNG, and WebP are allowed.");
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception("File too large. Maximum size is 5MB.");
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'featured_' . uniqid() . '.' . $extension;
        $filepath = $this->uploadPath . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception("Failed to upload image.");
        }
        
        // Optimize image
        $this->optimizeImage($filepath);
        
        return $filepath;
    }

    /**
     * Process categories
     */
    private function processCategories($categoryIds) {
        $categories = $this->getCategories();
        $selectedCategories = [];
        
        foreach ($categories as $category) {
            if (in_array($category['id'], (array)$categoryIds)) {
                $selectedCategories[] = $category['name'];
            }
        }
        
        return $selectedCategories;
    }

    /**
     * Process tags
     */
    private function processTags($tagsString) {
        $tags = array_map('trim', explode(',', $tagsString));
        $tags = array_filter(array_unique($tags));
        return array_slice($tags, 0, 10); // Limit to 10 tags
    }

    /**
     * Generate URL slug
     */
    private function generateSlug($title, $excludeId = null) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check for uniqueness
        $query = "SELECT COUNT(*) FROM blog_posts WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $slug .= '-' . uniqid();
        }
        
        return $slug;
    }

    /**
     * Optimize image
     */
    private function optimizeImage($filepath) {
        // Basic image optimization - in production, you might use Intervention Image or similar
        // This is a placeholder for actual image optimization logic
        return true;
    }

    /**
     * Log activity
     */
    private function logActivity($type, $description) {
        $adminId = $_SESSION['admin_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt = $this->db->prepare("
            INSERT INTO admin_activity_log (admin_id, activity_type, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$adminId, $type, $description, $ipAddress, $userAgent]);
    }
}
?>