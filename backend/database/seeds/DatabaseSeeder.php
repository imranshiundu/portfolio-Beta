<?php
/**
 * Database Seeder - Populates initial data
 */

class DatabaseSeeder {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function run() {
        $this->seedAdminUser();
        $this->seedDefaultSettings();
        $this->seedSampleProjects();
        $this->seedSampleBlogPosts();
        $this->seedTechnologies();
    }
    
    private function seedAdminUser() {
        // Check if admin user already exists
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM admin_users");
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("
                INSERT INTO admin_users (name, email, password, is_active) 
                VALUES (?, ?, ?, 1)
            ");
            $stmt->execute(['Administrator', 'admin@imranshiundu.eu', $password]);
            
            echo "Admin user created: admin@imranshiundu.eu / admin123\n";
        }
    }
    
    private function seedDefaultSettings() {
        $defaultSettings = [
            'site_config' => [
                'title' => 'Imran Shiundu - Full Stack Developer',
                'description' => 'Full Stack Developer specializing in modern web technologies',
                'keywords' => 'developer, portfolio, web development, full stack, kenya',
                'features' => [
                    'blog' => true,
                    'contact' => true,
                    'dark_mode' => true,
                    'analytics' => true
                ],
                'maintenance_mode' => false,
                'maintenance_text' => 'Site is under maintenance. Please check back later.'
            ],
            'personal_info' => [
                'name' => 'Imran Shiundu',
                'title' => 'Full Stack Developer',
                'bio' => 'Passionate full-stack developer with expertise in modern web technologies. I love building scalable applications and solving complex problems.',
                'location' => 'Nairobi, Kenya',
                'availability' => 'available'
            ],
            'social_links' => [
                'github' => 'https://github.com/imranshiundu',
                'linkedin' => 'https://linkedin.com/in/imranshiundu',
                'twitter' => 'https://twitter.com/imranshiundu',
                'youtube' => '',
                'instagram' => '',
                'facebook' => '',
                'stackoverflow' => '',
                'website' => 'https://imranshiundu.eu'
            ],
            'contact_info' => [
                'email' => 'contact@imranshiundu.eu',
                'phone' => '+254 740 293 859',
                'subtitle' => "Let's Build Something Amazing Together",
                'description' => "I'm always interested in new opportunities and collaborations. Whether you have a project in mind or just want to connect, feel free to reach out!",
                'response_time' => '24',
                'projects_completed' => '50+',
                'collaboration_count' => '15+'
            ],
            'seo_config' => [
                'meta_title' => 'Imran Shiundu - Full Stack Developer Portfolio',
                'meta_description' => 'Full Stack Developer specializing in React, Node.js, Laravel, and modern web technologies. View my projects and get in touch.',
                'ga_tracking_id' => '',
                'gtm_id' => ''
            ],
            'appearance' => [
                'colors' => [
                    'primary' => '#0D47A1',
                    'secondary' => '#FF6D00',
                    'accent' => '#2E7D32'
                ],
                'fonts' => [
                    'primary' => 'Inter',
                    'secondary' => 'Source Sans Pro'
                ],
                'layout' => 'modern',
                'animations' => true
            ],
            'security' => [
                'session_timeout' => '60',
                'enable_2fa' => false,
                'login_notifications' => true,
                'password_min_length' => '12',
                'password_require_special' => true
            ]
        ];
        
        foreach ($defaultSettings as $key => $value) {
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO settings (key_name, key_value) 
                VALUES (?, ?)
            ");
            $stmt->execute([$key, json_encode($value)]);
        }
    }
    
    private function seedSampleProjects() {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM projects");
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $sampleProjects = [
                [
                    'title' => 'Melora Music Player',
                    'description' => 'Modern music streaming platform with real-time features and personalized recommendations.',
                    'category' => 'fullstack',
                    'technologies' => ['React', 'Node.js', 'MongoDB', 'Web Audio API'],
                    'status' => 'completed',
                    'featured' => true
                ],
                [
                    'title' => 'ImFlix Streaming Service',
                    'description' => 'Video streaming platform with content management and user subscriptions.',
                    'category' => 'fullstack', 
                    'technologies' => ['Vue.js', 'Laravel', 'MySQL', 'AWS S3'],
                    'status' => 'completed',
                    'featured' => true
                ],
                [
                    'title' => 'Narmisites Web Services',
                    'description' => 'Comprehensive web services platform for small businesses and startups.',
                    'category' => 'fullstack',
                    'technologies' => ['PHP', 'JavaScript', 'Bootstrap', 'jQuery'],
                    'status' => 'completed',
                    'featured' => false
                ]
            ];
            
            foreach ($sampleProjects as $index => $project) {
                $slug = strtolower(str_replace(' ', '-', $project['title']));
                
                $stmt = $this->db->prepare("
                    INSERT INTO projects (title, slug, description, category, status, featured, display_order, project_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $project['title'],
                    $slug,
                    $project['description'],
                    $project['category'],
                    $project['status'],
                    $project['featured'] ? 1 : 0,
                    $index + 1,
                    date('Y-m-d', strtotime('-'.($index + 1).' months'))
                ]);
                
                $projectId = $this->db->lastInsertId();
                
                // Add technologies
                foreach ($project['technologies'] as $tech) {
                    $techStmt = $this->db->prepare("
                        INSERT IGNORE INTO technologies (name) VALUES (?)
                    ");
                    $techStmt->execute([$tech]);
                    
                    $techId = $this->db->lastInsertId();
                    if (!$techId) {
                        $techStmt = $this->db->prepare("SELECT id FROM technologies WHERE name = ?");
                        $techStmt->execute([$tech]);
                        $techId = $techStmt->fetchColumn();
                    }
                    
                    $linkStmt = $this->db->prepare("
                        INSERT INTO project_technologies (project_id, technology_id) VALUES (?, ?)
                    ");
                    $linkStmt->execute([$projectId, $techId]);
                }
            }
            
            echo "Sample projects created\n";
        }
    }
    
    private function seedSampleBlogPosts() {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM blog_posts");
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $samplePosts = [
                [
                    'title' => 'Getting Started with Modern Web Development',
                    'excerpt' => 'Learn the fundamentals of modern web development and the tools you need to get started.',
                    'content' => '<p>Web development has evolved significantly over the years...</p>',
                    'categories' => ['Web Development'],
                    'tags' => ['web development', 'beginners', 'tutorial'],
                    'status' => 'published'
                ],
                [
                    'title' => 'Building Scalable APIs with Laravel',
                    'excerpt' => 'Best practices for building robust and scalable REST APIs using Laravel.',
                    'content' => '<p>Building APIs that can scale with your application is crucial...</p>',
                    'categories' => ['Backend Development'],
                    'tags' => ['laravel', 'api', 'backend', 'php'],
                    'status' => 'published'
                ]
            ];
            
            foreach ($samplePosts as $post) {
                $slug = strtolower(str_replace(' ', '-', $post['title']));
                
                $stmt = $this->db->prepare("
                    INSERT INTO blog_posts (title, slug, excerpt, content, categories, tags, status, published_at, is_new)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $post['title'],
                    $slug,
                    $post['excerpt'],
                    $post['content'],
                    json_encode($post['categories']),
                    json_encode($post['tags']),
                    $post['status'],
                    date('Y-m-d H:i:s'),
                    true
                ]);
            }
            
            echo "Sample blog posts created\n";
        }
    }
    
    private function seedTechnologies() {
        $commonTechnologies = [
            'HTML5', 'CSS3', 'JavaScript', 'TypeScript', 'React', 'Vue.js', 'Angular',
            'Node.js', 'Express.js', 'PHP', 'Laravel', 'Python', 'Django', 'MySQL',
            'MongoDB', 'PostgreSQL', 'Redis', 'Docker', 'AWS', 'Git', 'REST API',
            'GraphQL', 'WebSocket', 'SASS', 'Bootstrap', 'Tailwind CSS'
        ];
        
        foreach ($commonTechnologies as $tech) {
            $stmt = $this->db->prepare("INSERT IGNORE INTO technologies (name) VALUES (?)");
            $stmt->execute([$tech]);
        }
        
        echo "Common technologies seeded\n";
    }
}
?>