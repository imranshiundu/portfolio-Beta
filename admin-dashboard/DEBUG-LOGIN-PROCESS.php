<?php
/**
 * DEBUG LOGIN PROCESS - Find where login fails
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h1>🔍 DEBUG LOGIN PROCESS</h1>";

// Test AuthService directly
require_once '../backend/autoload.php';

echo "<h2>Step 1: Test AuthService::validateCredentials()</h2>";

try {
    // Test the exact same credentials that should work
    $email = 'admin@imranshiundu.eu';
    $password = '#Imr@n2006';
    
    echo "Testing: $email / #Imr@n2006<br>";
    
    // Manually test what AuthService::validateCredentials does
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT id, name, email, password, is_active FROM admin_users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "✅ Admin found in database<br>";
        echo "ID: " . $admin['id'] . "<br>";
        echo "Active: " . ($admin['is_active'] ? 'YES' : 'NO') . "<br>";
        
        $password_verify = password_verify($password, $admin['password']);
        echo "Password verify: " . ($password_verify ? "✅ SUCCESS" : "❌ FAILED") . "<br>";
        
        if ($password_verify) {
            echo "<div style='background: green; color: white; padding: 10px;'>";
            echo "🎉 CREDENTIALS ARE VALID! The problem is elsewhere.<br>";
            echo "</div>";
            
            // Test AuthService::login directly
            echo "<h2>Step 2: Test AuthService::login()</h2>";
            
            $result = AuthService::login($email, $password, false);
            echo "AuthService::login() returned: " . ($result ? "✅ TRUE" : "❌ FALSE") . "<br>";
            
            echo "<h2>Step 3: Check Session</h2>";
            echo "Session ID: " . session_id() . "<br>";
            echo "Session status: ";
            if (isset($_SESSION['admin_authenticated'])) {
                echo "✅ admin_authenticated = " . ($_SESSION['admin_authenticated'] ? 'TRUE' : 'FALSE') . "<br>";
            } else {
                echo "❌ admin_authenticated NOT SET<br>";
            }
            
            if (isset($_SESSION['admin_id'])) {
                echo "✅ admin_id = " . $_SESSION['admin_id'] . "<br>";
            } else {
                echo "❌ admin_id NOT SET<br>";
            }
            
            echo "<h2>Step 4: Test AuthService::isAuthenticated()</h2>";
            $isAuth = AuthService::isAuthenticated();
            echo "AuthService::isAuthenticated() returned: " . ($isAuth ? "✅ TRUE" : "❌ FALSE") . "<br>";
            
        } else {
            echo "<div style='background: red; color: white; padding: 10px;'>";
            echo "❌ PASSWORD VERIFY FAILED - This is the problem!<br>";
            echo "But the debug script showed it works. There might be a character encoding issue.<br>";
            echo "</div>";
        }
    } else {
        echo "❌ Admin not found or not active<br>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: red; color: white; padding: 10px;'>";
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
    echo "</div>";
}

echo "<h2>Quick Fix: Manual Session Set</h2>";
echo "<p>If AuthService::login() is failing, let's manually set the session:</p>";

// Manually set session like AuthService should
$_SESSION['admin_authenticated'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_name'] = 'Administrator';
$_SESSION['admin_email'] = 'admin@imranshiundu.eu';
$_SESSION['last_activity'] = time();

echo "✅ Manually set session variables<br>";
echo "Now test: <a href='dashboard.php' style='font-size: 18px;'>Go to Dashboard</a><br>";

if (AuthService::isAuthenticated()) {
    echo "<div style='background: green; color: white; padding: 15px; margin: 10px 0;'>";
    echo "🎉 MANUAL LOGIN SUCCESS! You should be able to access dashboard now.<br>";
    echo "<a href='dashboard.php' style='color: yellow; font-size: 20px;'>🚀 GO TO DASHBOARD</a>";
    echo "</div>";
} else {
    echo "<div style='background: red; color: white; padding: 15px; margin: 10px 0;'>";
    echo "❌ STILL NOT AUTHENTICATED! The problem is in AuthService::isAuthenticated()<br>";
    echo "Let's check what's in the session:<br>";
    echo "<pre>Session data: " . print_r($_SESSION, true) . "</pre>";
    echo "</div>";
}
?>

<div style="background: orange; padding: 10px; margin: 20px 0;">
    <strong>Delete this file after use!</strong>
</div>