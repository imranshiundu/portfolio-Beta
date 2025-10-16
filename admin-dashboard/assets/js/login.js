/**
 * Login Manager - Handles admin login functionality
 */

class LoginManager {
    constructor() {
        this.form = document.getElementById('loginForm');
        this.emailInput = document.getElementById('email');
        this.passwordInput = document.getElementById('password');
        this.passwordToggle = document.getElementById('passwordToggle');
        this.loginButton = document.getElementById('loginButton');
        this.btnLoading = document.getElementById('btnLoading');
        this.securityMonitor = document.getElementById('securityMonitor');
        
        this.attempts = 0;
        this.maxAttempts = 5;
        this.lockoutTime = 15 * 60 * 1000; // 15 minutes
    }

    init() {
        this.setupEventListeners();
        this.checkLockoutStatus();
        this.initializeSecurityMonitoring();
    }

    setupEventListeners() {
        // Form submission
        this.form.addEventListener('submit', (e) => this.handleLogin(e));
        
        // Password visibility toggle
        this.passwordToggle.addEventListener('click', () => this.togglePasswordVisibility());
        
        // Real-time validation
        this.emailInput.addEventListener('input', () => this.validateEmail());
        this.passwordInput.addEventListener('input', () => this.validatePassword());
        
        // Enter key submission
        this.form.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.handleLogin(e);
            }
        });
        
        // Input focus effects
        this.setupInputFocusEffects();
    }

    setupInputFocusEffects() {
        const inputs = [this.emailInput, this.passwordInput];
        
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', () => {
                input.parentElement.classList.remove('focused');
            });
        });
    }

    togglePasswordVisibility() {
        const type = this.passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        this.passwordInput.setAttribute('type', type);
        
        const icon = this.passwordToggle.querySelector('i');
        icon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
    }

    validateEmail() {
        const email = this.emailInput.value.trim();
        const errorElement = document.getElementById('emailError');
        
        if (!email) {
            this.showError(errorElement, 'Email address is required');
            return false;
        }
        
        if (!this.isValidEmail(email)) {
            this.showError(errorElement, 'Please enter a valid email address');
            return false;
        }
        
        this.hideError(errorElement);
        return true;
    }

    validatePassword() {
        const password = this.passwordInput.value;
        const errorElement = document.getElementById('passwordError');
        
        if (!password) {
            this.showError(errorElement, 'Password is required');
            return false;
        }
        
        if (password.length < 6) {
            this.showError(errorElement, 'Password must be at least 6 characters');
            return false;
        }
        
        this.hideError(errorElement);
        return true;
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    showError(errorElement, message) {
        errorElement.textContent = message;
        errorElement.classList.add('show');
    }

    hideError(errorElement) {
        errorElement.textContent = '';
        errorElement.classList.remove('show');
    }

    async handleLogin(event) {
        event.preventDefault();
        
        // Validate form
        const isEmailValid = this.validateEmail();
        const isPasswordValid = this.validatePassword();
        
        if (!isEmailValid || !isPasswordValid) {
            this.shakeForm();
            return;
        }
        
        // Check if user is locked out
        if (this.isLockedOut()) {
            this.showLockoutMessage();
            return;
        }
        
        // Show loading state
        this.setLoadingState(true);
        
        try {
            // Simulate API call (replace with actual API call)
            const success = await this.attemptLogin();
            
            if (success) {
                this.handleSuccess();
            } else {
                this.handleFailure();
            }
        } catch (error) {
            this.handleError(error);
        } finally {
            this.setLoadingState(false);
        }
    }

    async attemptLogin() {
        // This would be replaced with actual API call to backend
        return new Promise((resolve) => {
            setTimeout(() => {
                // Simulate API response
                const formData = new FormData(this.form);
                const email = formData.get('email');
                const password = formData.get('password');
                
                // Demo validation (replace with actual backend validation)
                const isValid = email === 'admin@imranshiundu.eu' && password === 'demo123';
                resolve(isValid);
            }, 1500); // Simulate network delay
        });
    }

    handleSuccess() {
        this.attempts = 0;
        this.clearLockout();
        
        // Show success state
        this.loginButton.classList.add('success');
        this.loginButton.innerHTML = '<i class="fas fa-check"></i> Login Successful';
        
        // Redirect after short delay
        setTimeout(() => {
            window.location.href = 'dashboard.php';
        }, 1000);
    }

    handleFailure() {
        this.attempts++;
        
        if (this.attempts >= this.maxAttempts) {
            this.lockoutUser();
            this.showLockoutMessage();
        } else {
            this.shakeForm();
            this.showAttemptsRemaining();
        }
    }

    handleError(error) {
        console.error('Login error:', error);
        this.showErrorMessage('An error occurred during login. Please try again.');
        this.shakeForm();
    }

    setLoadingState(loading) {
        if (loading) {
            this.loginButton.disabled = true;
            this.loginButton.classList.add('loading');
        } else {
            this.loginButton.disabled = false;
            this.loginButton.classList.remove('loading');
        }
    }

    shakeForm() {
        this.form.classList.add('shake');
        setTimeout(() => {
            this.form.classList.remove('shake');
        }, 500);
    }

    showAttemptsRemaining() {
        const remaining = this.maxAttempts - this.attempts;
        const message = `Invalid credentials. ${remaining} attempt${remaining !== 1 ? 's' : ''} remaining.`;
        this.showErrorMessage(message);
    }

    showErrorMessage(message) {
        // Create or update error alert
        let errorAlert = document.querySelector('.alert-error');
        
        if (!errorAlert) {
            errorAlert = document.createElement('div');
            errorAlert.className = 'alert alert-error';
            errorAlert.innerHTML = `
                <div class="alert-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="alert-content">
                    <p>${message}</p>
                </div>
                <button class="alert-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            this.form.parentElement.insertBefore(errorAlert, this.form);
        } else {
            errorAlert.querySelector('.alert-content p').textContent = message;
        }
    }

    lockoutUser() {
        const lockoutUntil = Date.now() + this.lockoutTime;
        localStorage.setItem('loginLockout', lockoutUntil.toString());
    }

    isLockedOut() {
        const lockoutUntil = localStorage.getItem('loginLockout');
        if (!lockoutUntil) return false;
        
        return Date.now() < parseInt(lockoutUntil);
    }

    clearLockout() {
        localStorage.removeItem('loginLockout');
    }

    checkLockoutStatus() {
        if (this.isLockedOut()) {
            this.showLockoutMessage();
            this.disableForm();
        }
    }

    showLockoutMessage() {
        const lockoutUntil = localStorage.getItem('loginLockout');
        const timeLeft = Math.ceil((parseInt(lockoutUntil) - Date.now()) / 1000 / 60);
        
        const message = `Too many failed attempts. Please try again in ${timeLeft} minutes.`;
        this.showErrorMessage(message);
        this.disableForm();
    }

    disableForm() {
        this.emailInput.disabled = true;
        this.passwordInput.disabled = true;
        this.loginButton.disabled = true;
    }

    initializeSecurityMonitoring() {
        // Monitor for suspicious activity
        this.monitorInputPatterns();
        this.monitorTypingSpeed();
    }

    monitorInputPatterns() {
        let lastKeyTime = Date.now();
        
        this.passwordInput.addEventListener('keydown', (e) => {
            const currentTime = Date.now();
            const timeDiff = currentTime - lastKeyTime;
            
            // Detect copy-paste or automated input
            if (timeDiff < 50) {
                this.logSuspiciousActivity('rapid_input');
            }
            
            lastKeyTime = currentTime;
        });
    }

    monitorTypingSpeed() {
        let startTime = null;
        
        this.passwordInput.addEventListener('focus', () => {
            startTime = Date.now();
        });
        
        this.passwordInput.addEventListener('blur', () => {
            if (startTime) {
                const typingTime = Date.now() - startTime;
                const passwordLength = this.passwordInput.value.length;
                
                // Very fast typing might indicate paste or automation
                if (passwordLength > 8 && typingTime < 1000) {
                    this.logSuspiciousActivity('fast_typing');
                }
            }
        });
    }

    logSuspiciousActivity(type) {
        // In a real application, this would send to your backend
        console.warn(`Suspicious activity detected: ${type}`);
        
        // Visual feedback
        this.securityMonitor.style.background = 'rgba(198, 40, 40, 0.9)';
        setTimeout(() => {
            this.securityMonitor.style.background = 'rgba(46, 125, 50, 0.9)';
        }, 2000);
    }
}

// Additional CSS for dynamic states
const dynamicStyles = `
    .form-group.focused .form-label {
        color: #0D47A1;
    }
    
    .form-group.focused .form-label i {
        color: #0D47A1;
    }
    
    .shake {
        animation: shake 0.5s ease-in-out;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-10px); }
        75% { transform: translateX(10px); }
    }
    
    .btn-login.success {
        background: linear-gradient(135deg, #2E7D32 0%, #1B5E20 100%) !important;
    }
`;

// Add dynamic styles to document
const styleSheet = document.createElement('style');
styleSheet.textContent = dynamicStyles;
document.head.appendChild(styleSheet);