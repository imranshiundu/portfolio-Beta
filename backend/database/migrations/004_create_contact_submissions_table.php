<?php
/**
 * Create contact submissions table migration
 */

class CreateContactSubmissionsTable {
    public function up() {
        $sql = "
            CREATE TABLE IF NOT EXISTS contact_submissions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL,
                phone VARCHAR(20),
                project_type VARCHAR(50),
                budget_range VARCHAR(50),
                timeline VARCHAR(50),
                message TEXT NOT NULL,
                newsletter BOOLEAN DEFAULT FALSE,
                ip_address VARCHAR(45),
                user_agent TEXT,
                status ENUM('new', 'read', 'replied', 'spam') DEFAULT 'new',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        return $sql;
    }
    
    public function down() {
        return "DROP TABLE IF EXISTS contact_submissions;";
    }
}
?>