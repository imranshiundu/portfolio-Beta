<?php
/**
 * Create blog posts table migration
 */

class CreateBlogPostsTable {
    public function up() {
        $sql = "
            CREATE TABLE IF NOT EXISTS blog_posts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                excerpt TEXT,
                content LONGTEXT NOT NULL,
                featured_image VARCHAR(500),
                author_name VARCHAR(100) DEFAULT 'Imran Shiundu',
                author_avatar VARCHAR(500),
                categories JSON,
                tags JSON,
                meta_title VARCHAR(255),
                meta_description TEXT,
                featured BOOLEAN DEFAULT FALSE,
                is_new BOOLEAN DEFAULT FALSE,
                status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
                published_at TIMESTAMP NULL,
                views INT DEFAULT 0,
                likes INT DEFAULT 0,
                comment_count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        return $sql;
    }
    
    public function down() {
        return "DROP TABLE IF EXISTS blog_posts;";
    }
}
?>