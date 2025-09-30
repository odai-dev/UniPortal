<?php
$page_title = 'Login';
require_once 'config.php';
require_once 'db.php';
require_once 'captcha_verify.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_POST) {
    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        $error_message = 'Invalid security token. Please refresh and try again.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $captcha_token = $_POST['captcha_token'] ?? '';
        $remember_me = isset($_POST['remember_me']);

    // Validate custom CAPTCHA verification
    if (!verifyCustomCaptcha($captcha_token)) {
        $error_message = 'Please complete the CAPTCHA verification.';
    } elseif (empty($email) || empty($password)) {
        $error_message = 'Please fill in all fields.';
    } elseif (!validateEmail($email)) {
        $error_message = 'Please use a valid Gmail or Hotmail email address.';
    } else {
        try {
            // Check user credentials
            $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Invalidate old CSRF token and regenerate session
                invalidateCSRFToken();
                regenerateSession();
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Set remember me cookie if checked (more secure)
                if ($remember_me) {
                    $token = generateRememberToken($user['id']);
                    if ($token) {
                        setcookie('remember_me', $token, time() + REMEMBER_ME_DURATION, '/', '', false, true);
                    }
                }

                header('Location: dashboard.php');
                exit();
            } else {
                $error_message = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            error_log('Login PDOException: ' . $e->getMessage());
            $error_message = 'Database error occurred. Please try again. Error: ' . $e->getMessage();
        }
    }
    }
}

// Check for remember me cookie
if (!isLoggedIn() && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $user_data = validateRememberToken($token);
    
    if ($user_data) {
        // Invalidate old CSRF token and regenerate session for auto-login
        invalidateCSRFToken();
        regenerateSession();
        
        // Auto login
        $_SESSION['user_id'] = $user_data['user_id'];
        $_SESSION['name'] = $user_data['name'];
        $_SESSION['email'] = $user_data['email'];
        $_SESSION['role'] = $user_data['role'];
        
        header('Location: dashboard.php');
        exit();
    } else {
        // Remove invalid cookie
        setcookie('remember_me', '', time() - 3600, '/', '', false, true);
    }
}

// Check for registration success message
if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $success_message = 'Registration successful! Please login with your credentials.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= SITE_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="style.css" rel="stylesheet">
    <link href="captcha.css" rel="stylesheet">
</head>
<body>

<div class="auth-container page-transition">
    <div class="auth-card col-md-6 col-lg-4">
        <div class="auth-header">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div></div>
                <button class="btn btn-sm" onclick="toggleTheme()" id="theme-toggle" title="Toggle theme" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; border-radius: var(--radius-md); padding: 0.4rem 0.8rem;">
                    <i class="fas fa-sun" id="theme-icon"></i>
                </button>
            </div>
            <i class="fas fa-graduation-cap fa-3x mb-3"></i>
            <h3><?= SITE_NAME ?></h3>
            <p class="mb-0">Student Login Portal</p>
        </div>
        
        <div class="auth-body">
            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= $error_message ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $success_message ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= isset($_POST['email']) ? sanitizeInput($_POST['email']) : '' ?>"
                               placeholder="Enter your Gmail or Hotmail address" required autocomplete="email">
                    </div>
                    <small class="form-text text-muted">Only Gmail and Hotmail addresses are accepted</small>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter your password" required autocomplete="current-password">
                    </div>
                </div>

                <!-- Custom CAPTCHA Verification -->
                <div class="mb-3">
                    <label class="form-label">Security Verification</label>
                    <div id="custom-captcha-container"></div>
                    <small class="form-text text-muted">Please verify that you are not a robot</small>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                    <label class="form-check-label" for="remember_me">
                        Remember me for 30 days
                    </label>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </div>
            </form>

            <hr class="my-4">
            
            <div class="text-center">
                <p class="mb-0">Don't have an account?</p>
                <a href="register.php" class="btn btn-outline-primary">
                    <i class="fas fa-user-plus me-2"></i>Create New Account
                </a>
            </div>
        </div>
    </div>
</div>

<script src="captcha.js"></script>
<script src="form-enhancements.js"></script>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Theme Toggle JavaScript -->
<script>
// Theme Toggle Functionality
function initTheme() {
    const savedTheme = localStorage.getItem('theme');
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = savedTheme || (systemPrefersDark ? 'dark' : 'light');
    
    document.documentElement.setAttribute('data-theme', theme);
    updateThemeToggle(theme);
}

function updateThemeToggle(theme) {
    const icon = document.getElementById('theme-icon');
    
    if (!icon) return;
    
    if (theme === 'dark') {
        icon.className = 'fas fa-moon';
    } else {
        icon.className = 'fas fa-sun';
    }
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeToggle(newTheme);
}

// Listen for system theme changes
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    if (!localStorage.getItem('theme')) {
        const theme = e.matches ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', theme);
        updateThemeToggle(theme);
    }
});

// Initialize theme
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTheme);
} else {
    initTheme();
}
</script>

</body>
</html>