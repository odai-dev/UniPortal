<?php
$page_title = 'Register';
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
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $captcha_token = $_POST['captcha_token'] ?? '';
        $remember_me = isset($_POST['remember_me']);

    // Validate custom CAPTCHA verification
    if (!verifyCustomCaptcha($captcha_token)) {
        $error_message = 'Please complete the CAPTCHA verification.';
    } elseif (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = 'Please fill in all fields.';
    } elseif (!validateEmail($email)) {
        $error_message = 'Please use a valid Gmail or Hotmail email address.';
    } elseif (!validatePassword($password)) {
        $error_message = 'Password must be at least 8 characters long and contain letters, numbers, and symbols.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $email_exists = $stmt->fetchColumn();

            if ($email_exists > 0) {
                $error_message = 'Email address is already registered. Please use a different email.';
            } else {
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
                $stmt->execute([$name, $email, $hashed_password]);
                
                // Get the new user ID
                $user_id = $pdo->lastInsertId();
                
                // Auto-login if remember me is checked
                if ($remember_me) {
                    // Invalidate old CSRF token and regenerate session
                    invalidateCSRFToken();
                    regenerateSession();
                    
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = 'student';
                    
                    $token = generateRememberToken($user_id);
                    if ($token) {
                        setcookie('remember_me', $token, time() + REMEMBER_ME_DURATION, '/', '', false, true);
                    }
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    // Redirect to login with success message
                    header('Location: login.php?registered=1');
                    exit();
                }
            }
        } catch (PDOException $e) {
            $error_message = 'Database error occurred. Please try again.';
        }
    }
    }
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
    
    <!-- Theme Initialization - Load before body to prevent flash -->
    <script>
    (function() {
        const savedTheme = localStorage.getItem('theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const theme = savedTheme || (systemPrefersDark ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', theme);
    })();
    </script>
</head>
<body>

<div class="auth-container page-transition">
    <div class="auth-card col-md-6 col-lg-4">
        <div class="auth-header">
            <i class="fas fa-user-plus fa-3x mb-3"></i>
            <h3>Create Account</h3>
            <p class="mb-0">Join <?= SITE_NAME ?></p>
        </div>
        
        <div class="auth-body">
            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= $error_message ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?= isset($_POST['name']) ? sanitizeInput($_POST['name']) : '' ?>"
                               placeholder="Enter your full name" required autocomplete="name">
                    </div>
                </div>

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
                               placeholder="Create a strong password" required autocomplete="new-password">
                    </div>
                    <small class="form-text text-muted">Must be 8+ characters with letters, numbers, and symbols</small>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm your password" required autocomplete="new-password">
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
                        Remember me and auto-login after registration
                    </label>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </button>
                </div>
            </form>

            <hr class="my-4">
            
            <div class="text-center">
                <p class="mb-0">Already have an account?</p>
                <a href="login.php" class="btn btn-outline-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Login Here
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