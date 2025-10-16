<?php
/**
 * Forgot Password Page
 * Handles password reset requests
 */

session_start();

// Load backend autoloader
require_once __DIR__ . '/../backend/autoload.php';

// Check if already logged in
if (AuthService::isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

// Handle password reset request
$error = '';
$success = '';
$step = 1; // 1: Request reset, 2: Enter code, 3: Set new password

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!AuthService::validateCSRFToken($csrf_token)) {
        $error = 'Security token validation failed. Please try again.';
    } else {
        try {
            $action = $_POST['action'] ?? 'request';
            
            switch ($action) {
                case 'request':
                    $email = $_POST['email'] ?? '';
                    if ($this->sendResetCode($email)) {
                        $_SESSION['reset_email'] = $email;
                        $_SESSION['reset_code'] = $this->generateResetCode();
                        $_SESSION['reset_expires'] = time() + 3600; // 1 hour
                        $step = 2;
                        $success = 'Reset code sent to your email.';
                    } else {
                        $error = 'Email not found or reset request failed.';
                    }
                    break;
                    
                case 'verify':
                    $code = $_POST['code'] ?? '';
                    if ($this->verifyResetCode($code)) {
                        $step = 3;
                        $success = 'Code verified. Please set your new password.';
                    } else {
                        $error = 'Invalid or expired reset code.';
                    }
                    break;
                    
                case 'reset':
                    $new_password = $_POST['new_password'] ?? '';
                    $confirm_password = $_POST['confirm_password'] ?? '';
                    
                    if ($new_password !== $confirm_password) {
                        $error = 'Passwords do not match.';
                    } elseif (strlen($new_password) < 8) {
                        $error = 'Password must be at least 8 characters long.';
                    } else {
                        if ($this->resetPassword($new_password)) {
                            $success = 'Password reset successfully! You can now login with your new password.';
                            // Clear reset session
                            unset($_SESSION['reset_email']);
                            unset($_SESSION['reset_code']);
                            unset($_SESSION['reset_expires']);
                            header('Refresh: 3; URL=login.php');
                        } else {
                            $error = 'Password reset failed. Please try again.';
                        }
                    }
                    break;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            error_log("Password reset error: " . $e->getMessage());
        }
    }
}

// Check if we're in the middle of a reset process
if (isset($_SESSION['reset_email'])) {
    if (isset($_SESSION['reset_code']) && !isset($_POST['action'])) {
        $step = 2;
    }
    if (isset($_POST['action']) && $_POST['action'] === 'verify' && !$error) {
        $step = 3;
    }
}

/**
 * Send reset code to email (placeholder - implement email service)
 */
function sendResetCode($email) {
    // In a real implementation, you would:
    // 1. Check if email exists in admin_users table
    // 2. Generate a secure reset code
    // 3. Send email with reset link/code
    // 4. Store reset token in database with expiration
    
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT id FROM admin_users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    return $stmt->fetch() !== false;
}

/**
 * Generate reset code (placeholder)
 */
function generateResetCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Verify reset code (placeholder)
 */
function verifyResetCode($code) {
    return isset($_SESSION['reset_code']) && 
           $_SESSION['reset_code'] === $code && 
           time() < $_SESSION['reset_expires'];
}

/**
 * Reset password
 */
function resetPassword($new_password) {
    if (!isset($_SESSION['reset_email'])) {
        return false;
    }
    
    try {
        $db = Database::getInstance();
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("UPDATE admin_users SET password = ? WHERE email = ?");
        $result = $stmt->execute([$password_hash, $_SESSION['reset_email']]);
        
        if ($result) {
            AuthService::logActivity('password_reset', "Password reset for: {$_SESSION['reset_email']}");
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Imran Shiundu Admin</title>
    
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
            <div class="code-line"><span class="code-comment">// Password Reset System</span></div>
            <div class="code-line"><span class="code-keyword">function</span> <span class="code-function">resetPassword</span>(email, newPass) {</div>
            <div class="code-line">&nbsp;&nbsp;<span class="code-keyword">return</span> updatePassword(hash(newPass));</div>
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
            <a href="login.php" class="back-to-login">
                <i class="fas fa-arrow-left"></i>
                Back to Login
            </a>
        </div>

        <!-- Password Reset Card -->
        <div class="login-card">
            <div class="card-header">
                <h2>Reset Password</h2>
                <p>Recover access to your admin account</p>
            </div>

            <!-- Progress Steps -->
            <div class="reset-steps">
                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>">
                    <div class="step-number">1</div>
                    <span class="step-label">Request</span>
                </div>
                <div class="step-separator"></div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">
                    <div class="step-number">2</div>
                    <span class="step-label">Verify</span>
                </div>
                <div class="step-separator"></div>
                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">
                    <div class="step-number">3</div>
                    <span class="step-label">Reset</span>
                </div>
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

            <!-- Step 1: Request Reset -->
            <?php if ($step === 1): ?>
            <form method="POST" action="" class="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo AuthService::generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="request">
                
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i>
                        Admin Email Address
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-input" 
                           placeholder="admin@imranshiundu.eu"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           required
                           autocomplete="email">
                    <div class="form-help">
                        Enter the email address associated with your admin account.
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    <span class="btn-text">Send Reset Code</span>
                </button>
            </form>
            <?php endif; ?>

            <!-- Step 2: Verify Code -->
            <?php if ($step === 2): ?>
            <form method="POST" action="" class="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo AuthService::generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="verify">
                
                <div class="form-group">
                    <label for="code" class="form-label">
                        <i class="fas fa-shield-alt"></i>
                        Verification Code
                    </label>
                    <input type="text" 
                           id="code" 
                           name="code" 
                           class="form-input" 
                           placeholder="Enter 6-digit code"
                           maxlength="6"
                           required
                           autocomplete="off">
                    <div class="form-help">
                        Enter the 6-digit verification code sent to your email.
                        <br><strong>Demo Code: 123456</strong>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    <span class="btn-text">Verify Code</span>
                </button>
                
                <div class="resend-code">
                    Didn't receive the code? 
                    <a href="#" id="resendCode">Resend Code</a>
                </div>
            </form>
            <?php endif; ?>

            <!-- Step 3: Set New Password -->
            <?php if ($step === 3): ?>
            <form method="POST" action="" class="login-form" id="resetForm">
                <input type="hidden" name="csrf_token" value="<?php echo AuthService::generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="reset">
                
                <div class="form-group">
                    <label for="new_password" class="form-label">
                        <i class="fas fa-lock"></i>
                        New Password
                    </label>
                    <div class="password-input-container">
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               class="form-input" 
                               placeholder="Enter new password"
                               required
                               autocomplete="new-password">
                        <button type="button" class="password-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar"></div>
                        <span class="strength-text">Password strength</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Confirm Password
                    </label>
                    <div class="password-input-container">
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="form-input" 
                               placeholder="Confirm new password"
                               required
                               autocomplete="new-password">
                        <button type="button" class="password-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-error" id="confirmError"></div>
                </div>

                <div class="password-requirements">
                    <h4>Password Requirements:</h4>
                    <ul>
                        <li id="req-length">At least 8 characters</li>
                        <li id="req-uppercase">One uppercase letter</li>
                        <li id="req-lowercase">One lowercase letter</li>
                        <li id="req-number">One number</li>
                    </ul>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    <span class="btn-text">Reset Password</span>
                </button>
            </form>
            <?php endif; ?>

            <!-- Security Notice -->
            <div class="security-notice">
                <i class="fas fa-shield-alt"></i>
                <span>For security reasons, reset codes expire after 1 hour.</span>
            </div>
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

    <!-- Scripts -->
    <script src="assets/js/login.js"></script>
    <script>
        // Password reset functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Password strength indicator
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const strengthBar = document.querySelector('.strength-bar');
            const strengthText = document.querySelector('.strength-text');
            
            if (newPassword) {
                newPassword.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;
                    
                    // Length check
                    if (password.length >= 8) strength += 25;
                    
                    // Uppercase check
                    if (/[A-Z]/.test(password)) strength += 25;
                    
                    // Lowercase check
                    if (/[a-z]/.test(password)) strength += 25;
                    
                    // Number check
                    if (/[0-9]/.test(password)) strength += 25;
                    
                    // Update strength bar
                    strengthBar.style.width = strength + '%';
                    
                    // Update strength text and color
                    if (strength < 50) {
                        strengthBar.style.backgroundColor = '#dc3545';
                        strengthText.textContent = 'Weak';
                    } else if (strength < 75) {
                        strengthBar.style.backgroundColor = '#ffc107';
                        strengthText.textContent = 'Medium';
                    } else {
                        strengthBar.style.backgroundColor = '#28a745';
                        strengthText.textContent = 'Strong';
                    }
                });
            }
            
            // Password confirmation check
            if (confirmPassword && newPassword) {
                confirmPassword.addEventListener('input', function() {
                    const confirmError = document.getElementById('confirmError');
                    if (this.value !== newPassword.value) {
                        confirmError.textContent = 'Passwords do not match';
                    } else {
                        confirmError.textContent = '';
                    }
                });
            }
            
            // Resend code functionality
            const resendCode = document.getElementById('resendCode');
            if (resendCode) {
                resendCode.addEventListener('click', function(e) {
                    e.preventDefault();
                    alert('Reset code resent! Demo code: 123456');
                });
            }
            
            // Demo code auto-fill for testing
            if (window.location.search.includes('demo=true')) {
                const codeInput = document.getElementById('code');
                if (codeInput) {
                    codeInput.value = '123456';
                }
            }
        });
    </script>
</body>
</html>