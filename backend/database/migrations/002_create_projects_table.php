<?php
/**
 * Create projects table migration
 */

class CreateProjectsTable {
    public function up() {
        $sql = "
            CREATE TABLE IF NOT EXISTS projects (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                description TEXT NOT NULL,
                full_description LONGTEXT,
                category ENUM('frontend', 'backend', 'fullstack', 'mobile', 'other') NOT NULL,
                status ENUM('planned', 'in-progress', 'completed', 'archived') DEFAULT 'completed',
                featured_image VARCHAR(500),
                demo_url VARCHAR(500),
                github_url VARCHAR(500),
                documentation_url VARCHAR(500),
                timeline VARCHAR(100),
                project_date DATE,
                featured BOOLEAN DEFAULT FALSE,
                showcase BOOLEAN DEFAULT TRUE,
                display_order INT DEFAULT 0,
                views INT DEFAULT 0,
                likes INT DEFAULT 0,
                shares INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS technologies (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS project_technologies (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                project_id BIGINT UNSIGNED NOT NULL,
                technology_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
                FOREIGN KEY (technology_id) REFERENCES technologies(id) ON DELETE CASCADE,
                UNIQUE KEY unique_project_technology (project_id, technology_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS project_features (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                project_id BIGINT UNSIGNED NOT NULL,
                feature TEXT NOT NULL,
                display_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS project_challenges (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                project_id BIGINT UNSIGNED NOT NULL,
                problem TEXT NOT NULL,
                solution TEXT NOT NULL,
                display_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS project_gallery (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                project_id BIGINT UNSIGNED NOT NULL,
                image_url VARCHAR(500) NOT NULL,
                display_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        return $sql;
    }
    
    public function down() {
        return "
            DROP TABLE IF EXISTS project_gallery;
            DROP TABLE IF EXISTS project_challenges;
            DROP TABLE IF EXISTS project_features;
            DROP TABLE IF EXISTS project_technologies;
            DROP TABLE IF EXISTS technologies;
            DROP TABLE IF EXISTS projects;
        ";
    }
}
?>