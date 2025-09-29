<?php
$page_title = 'Login';
require_once 'db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_POST) {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha = sanitizeInput($_POST['captcha'] ?? '');
    $remember_me = isset($_POST['remember_me']);

    // Validate CAPTCHA
    if (empty($captcha) || !isset($_SESSION['captcha']) || strtoupper($captcha) !== $_SESSION['captcha']) {
        $error_message = 'Invalid CAPTCHA. Please try again.';
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
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Set remember me cookie if checked
                if ($remember_me) {
                    $cookie_value = base64_encode($user['id'] . ':' . hash('sha256', $user['password']));
                    setcookie('remember_me', $cookie_value, time() + REMEMBER_ME_DURATION, '/');
                }

                header('Location: dashboard.php');
                exit();
            } else {
                $error_message = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $error_message = 'Database error occurred. Please try again.';
        }
    }
}

// Check for remember me cookie
if (!isLoggedIn() && isset($_COOKIE['remember_me'])) {
    $cookie_data = base64_decode($_COOKIE['remember_me']);
    $parts = explode(':', $cookie_data);
    
    if (count($parts) === 2) {
        $user_id = $parts[0];
        $password_hash = $parts[1];
        
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user && hash('sha256', $user['password']) === $password_hash) {
                // Auto login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                header('Location: dashboard.php');
                exit();
            }
        } catch (PDOException $e) {
            // Remove invalid cookie
            setcookie('remember_me', '', time() - 3600, '/');
        }
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
</head>
<body>

<div class="auth-container">
    <div class="auth-card col-md-6 col-lg-4">
        <div class="auth-header">
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
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= isset($_POST['email']) ? sanitizeInput($_POST['email']) : '' ?>"
                               placeholder="Enter your Gmail or Hotmail address" required>
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
                               placeholder="Enter your password" required>
                    </div>
                </div>

                <!-- CAPTCHA -->
                <div class="mb-3">
                    <label for="captcha" class="form-label">Security Code</label>
                    <div class="captcha-container">
                        <img src="captcha.php" alt="CAPTCHA" class="captcha-image" id="captcha-image">
                        <br>
                        <small class="captcha-refresh" onclick="refreshCaptcha()">
                            <i class="fas fa-sync-alt"></i> Click to refresh
                        </small>
                    </div>
                    <input type="text" class="form-control" id="captcha" name="captcha" 
                           placeholder="Enter the code shown above" required>
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

<script>
function refreshCaptcha() {
    document.getElementById('captcha-image').src = 'captcha.php?' + Math.random();
}
</script>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>