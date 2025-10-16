<?php
/**
 * Database Setup Script
 * Run this once to set up the database
 * WARNING: This will drop existing tables and recreate them
 */

// Display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting database setup...\n";

// Load configuration
require_once 'config/database.php';
require_once 'app/Core/Database.php';

// Load migration classes
require_once 'database/migrations/001_create_admin_users_table.php';
require_once 'database/migrations/002_create_projects_table.php';
require_once 'database/migrations/003_create_blog_posts_table.php';
require_once 'database/migrations/004_create_contact_submissions_table.php';
require_once 'database/migrations/005_create_settings_table.php';

// Load seeder
require_once 'database/seeds/DatabaseSeeder.php';

try {
    $db = Database::getInstance();
    
    echo "Connected to database successfully.\n";
    
    // Run migrations in order
    $migrations = [
        new CreateAdminUsersTable(),
        new CreateProjectsTable(), 
        new CreateBlogPostsTable(),
        new CreateContactSubmissionsTable(),
        new CreateSettingsTable()
    ];
    
    echo "Running migrations...\n";
    
    foreach ($migrations as $migration) {
        $className = get_class($migration);
        echo "Running migration: $className\n";
        
        // Get SQL statements
        $sql = $migration->up();
        
        // Execute SQL (handle multiple statements)
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $db->exec($statement);
                    echo "  ✓ Executed: " . substr($statement, 0, 50) . "...\n";
                } catch (PDOException $e) {
                    echo "  ✗ Error executing: " . $e->getMessage() . "\n";
                    // Continue with next migration
                    continue 2;
                }
            }
        }
        
        echo "  ✓ Migration completed successfully\n";
    }
    
    echo "All migrations completed successfully.\n";
    
    // Run seeder
    echo "Seeding database...\n";
    $seeder = new DatabaseSeeder();
    $seeder->run();
    
    echo "Database setup completed successfully!\n";
    echo "You can now access the admin panel with:\n";
    echo "Email: admin@imranshiundu.eu\n";
    echo "Password: admin123\n";
    echo "\nIMPORTANT: Change the default password after first login!\n";
    
} catch (Exception $e) {
    echo "Setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>