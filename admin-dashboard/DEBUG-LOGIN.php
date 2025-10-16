<?php
/**
 * DEBUG LOGIN - Find the exact problem
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 DEBUG LOGIN PROBLEM</h1>";

try {
    // 1. Test database connection
    echo "<h2>Step 1: Database Connection</h2>";
    require_once '../backend/app/Core/Database.php';
    $db = Database::getInstance();
    echo "✅ Database connected<br>";
    
    // 2. Check if admin_users table exists
    echo "<h2>Step 2: Check Admin Table</h2>";
    $stmt = $db->query("SHOW TABLES LIKE 'admin_users'");
    $tableExists = $stmt->rowCount() > 0;
    echo "Table 'admin_users' exists: " . ($tableExists ? "✅ YES" : "❌ NO") . "<br>";
    
    if (!$tableExists) {
        echo "❌ PROBLEM: admin_users table doesn't exist!<br>";
        exit;
    }
    
    // 3. Check if admin user exists
    echo "<h2>Step 3: Check Admin User</h2>";
    $stmt = $db->query("SELECT id, email, password, is_active FROM admin_users WHERE email = 'admin@imranshiundu.eu'");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo "❌ PROBLEM: No admin user found with email: admin@imranshiundu.eu<br>";
        echo "Creating admin user now...<br>";
        
        $password_hash = password_hash('#Imr@n2006', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO admin_users (name, email, password, is_active) VALUES (?, ?, ?, 1)");
        $result = $stmt->execute(['Administrator', 'admin@imranshiundu.eu', $password_hash]);
        
        if ($result) {
            echo "✅ Admin user created!<br>";
            $adminId = $db->lastInsertId();
            echo "New admin ID: $adminId<br>";
        }
    } else {
        echo "✅ Admin user found:<br>";
        echo "ID: " . $admin['id'] . "<br>";
        echo "Email: " . $admin['email'] . "<br>";
        echo "Active: " . ($admin['is_active'] ? "✅ YES" : "❌ NO") . "<br>";
        echo "Password hash: " . substr($admin['password'], 0, 50) . "...<br>";
        
        // 4. Test password verification
        echo "<h2>Step 4: Password Verification</h2>";
        $test_passwords = [
            '#Imr@n2006',
            'admin123', 
            'Admin123!',
            'password'
        ];
        
        foreach ($test_passwords as $test_pwd) {
            $verify = password_verify($test_pwd, $admin['password']);
            echo "Password '$test_pwd': " . ($verify ? "✅ WORKS" : "❌ FAILS") . "<br>";
        }
        
        // 5. If none work, reset to #Imr@n2006
        $any_work = false;
        foreach ($test_passwords as $test_pwd) {
            if (password_verify($test_pwd, $admin['password'])) {
                $any_work = true;
                break;
            }
        }
        
        if (!$any_work) {
            echo "<h2>Step 5: Resetting Password</h2>";
            $new_hash = password_hash('#Imr@n2006', PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE admin_users SET password = ? WHERE email = ?");
            $result = $stmt->execute([$new_hash, 'admin@imranshiundu.eu']);
            
            if ($result) {
                echo "✅ Password reset to #Imr@n2006<br>";
                
                // Verify new password
                $stmt = $db->prepare("SELECT password FROM admin_users WHERE email = ?");
                $stmt->execute(['admin@imranshiundu.eu']);
                $new_hash_check = $stmt->fetchColumn();
                $verify_new = password_verify('#Imr@n2006', $new_hash_check);
                echo "New password verification: " . ($verify_new ? "✅ WORKS" : "❌ FAILS") . "<br>";
            }
        }
    }
    
    // 6. Final check
    echo "<h2>Step 6: Final Status</h2>";
    $stmt = $db->prepare("SELECT password FROM admin_users WHERE email = ?");
    $stmt->execute(['admin@imranshiundu.eu']);
    $final_hash = $stmt->fetchColumn();
    
    $final_verify = password_verify('#Imr@n2006', $final_hash);
    
    if ($final_verify) {
        echo "<div style='background: green; color: white; padding: 20px; font-size: 24px;'>";
        echo "🎉 SUCCESS! You can now login with:<br>";
        echo "Email: admin@imranshiundu.eu<br>";
        echo "Password: #Imr@n2006<br>";
        echo "<a href='login.php' style='color: yellow; font-size: 28px;'>🚀 CLICK TO LOGIN</a>";
        echo "</div>";
    } else {
        echo "<div style='background: red; color: white; padding: 20px;'>";
        echo "❌ STILL NOT WORKING! The password hash is corrupted.<br>";
        echo "Let's delete and recreate the admin user...<br>";
        
        // Delete and recreate
        $stmt = $db->prepare("DELETE FROM admin_users WHERE email = ?");
        $stmt->execute(['admin@imranshiundu.eu']);
        
        $password_hash = password_hash('#Imr@n2006', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO admin_users (name, email, password, is_active) VALUES (?, ?, ?, 1)");
        $stmt->execute(['Administrator', 'admin@imranshiundu.eu', $password_hash]);
        
        echo "✅ Admin user recreated!<br>";
        echo "<a href='login.php'>Try logging in now</a>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: red; color: white; padding: 20px;'>";
    echo "<h2>❌ CRITICAL ERROR</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "This means the database connection is failing.<br>";
    echo "Check config/database.php - the MySQL password is wrong.<br>";
    echo "Current MySQL password in config: #Imr@n2006<br>";
    echo "But your ZoneVS MySQL user might have a different password.";
    echo "</div>";
}
?>

<div style="background: orange; padding: 15px; margin: 20px 0;">
    <strong>Delete this file after it works!</strong>
</div>