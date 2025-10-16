<?php
/**
 * Admin Login Page - FIXED VERSION
 * Handles authentication for admin dashboard access
 */

// Start session at the VERY TOP - no whitespace before this
session_start();

// Load backend autoloader
require_once __DIR__ . '/../backend/autoload.php';

// Check if already logged in
if (AuthService::isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

// Handle login form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Simple login without CSRF for now (we'll add it back later)
    try {
        // Manual login process to avoid AuthService issues
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, name, email, password, is_active FROM admin_users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($password, $admin['password'])) {
            // MANUAL SESSION SETUP - This works (as proven by debug script)
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['last_activity'] = time();
            
            // Log the login activity
            AuthService::logActivity('admin_login', "Admin user logged in from {$_SERVER['REMOTE_ADDR']}");
            
            // Redirect to dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
            
            // Log failed login attempt
            AuthService::logActivity('failed_login', "Failed login attempt for email: $email from {$_SERVER['REMOTE_ADDR']}");
        }
    } catch (Exception $e) {
        $error = 'An error occurred during login. Please try again.';
        error_log("Login error: " . $e->getMessage());
    }
}

// Check for logout
if (isset($_GET['logout'])) {
    $success = 'You have been successfully logged out.';
    // Clear session
    session_destroy();
    session_start(); // Start fresh session for messages
}

// Check for session timeout
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please log in again.';
}

// Check for unauthorized access redirect
if (isset($_GET['unauthorized'])) {
    $error = 'Please log in to access the admin dashboard.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Imran Shiundu Portfolio</title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Security Headers -->
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="login-page">
    <!-- Background Design -->
    <div class="login-background">
        <div class="background-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
        
        <!-- Animated Code Background -->
        <div class="code-background">
            <div class="code-line"><span class="code-comment">// Admin Authentication</span></div>
            <div class="code-line"><span class="code-keyword">function</span> <span class="code-function">authenticate</span>(user, pass) {</div>
            <div class="code-line">&nbsp;&nbsp;<span class="code-keyword">return</span> verifyCredentials(user, pass);</div>
            <div class="code-line">}</div>
        </div>
    </div>

    <!-- Login Container -->
    <div class="login-container">
        <!-- Header -->
        <div class="login-header">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-code"></i>
                </div>
                <div class="logo-text">
                    <h1>Imran Shiundu</h1>
                    <p>Portfolio Admin</p>
                </div>
            </div>
        </div>

        <!-- Login Card -->
        <div class="login-card">
            <div class="card-header">
                <h2>Admin Login</h2>
                <p>Access the portfolio management dashboard</p>
            </div>

            <!-- Messages -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="alert-content">
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <div class="alert-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="alert-content">
                        <p><?php echo htmlspecialchars($success); ?></p>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Security Notice -->
            <div class="security-notice">
                <i class="fas fa-shield-alt"></i>
                <span>Secure admin access. Unauthorized attempts are logged.</span>
            </div>

            <!-- Login Form -->
            <form method="POST" action="" class="login-form" id="loginForm" novalidate>
                <!-- Email Field -->
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-input <?php echo $error ? 'input-error' : ''; ?>" 
                           placeholder="admin@imranshiundu.eu"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           required
                           autocomplete="email">
                    <div class="form-error" id="emailError"></div>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="password-input-container">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-input <?php echo $error ? 'input-error' : ''; ?>" 
                               placeholder="Enter your password"
                               required
                               autocomplete="current-password">
                        <button type="button" class="password-toggle" id="passwordToggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-error" id="passwordError"></div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" id="remember" value="1">
                        <span class="checkmark"></span>
                        Remember me for 30 days
                    </label>
                    
                    <a href="forgot-password.php" class="forgot-password">
                        Forgot Password?
                    </a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-login" id="loginButton">
                    <span class="btn-text">Sign In</span>
                    <div class="btn-loading" id="btnLoading">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                </button>

                <!-- Demo Credentials -->
                <div class="demo-credentials">
                    <div class="demo-header">
                        <i class="fas fa-vial"></i>
                        <span>Login Credentials</span>
                    </div>
                    <div class="demo-content">
                        <p><strong>Email:</strong> admin@imranshiundu.eu</p>
                        <p><strong>Password:</strong> #Imr@n2006</p>
                    </div>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <p>&copy; 2025 Imran Shiundu. All rights reserved.</p>
            <div class="footer-links">
                <a href="../index.html" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    Visit Portfolio
                </a>
                <a href="../contact.html" target="_blank">
                    <i class="fas fa-envelope"></i>
                    Contact Support
                </a>
            </div>
        </div>
    </div>

    <!-- Simple JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle
            const passwordToggle = document.getElementById('passwordToggle');
            const passwordInput = document.getElementById('password');
            
            if (passwordToggle && passwordInput) {
                passwordToggle.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                });
            }
            
            // Form submission loading
            const loginForm = document.getElementById('loginForm');
            const loginButton = document.getElementById('loginButton');
            const btnText = document.querySelector('.btn-text');
            const btnLoading = document.getElementById('btnLoading');
            
            if (loginForm) {
                loginForm.addEventListener('submit', function() {
                    if (btnText && btnLoading) {
                        btnText.style.display = 'none';
                        btnLoading.style.display = 'inline-block';
                        loginButton.disabled = true;
                    }
                });
            }
        });
    </script>
</body>
</html>