<?php
$page_title = 'Profile';
require_once 'config.php';
require_once 'db.php';

// Require login (before any HTML output)
requireLogin();

require_once 'header.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_POST) {
    $name = sanitizeInput($_POST['name'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name)) {
        $error_message = 'Name is required.';
    } else {
        try {
            // If changing password
            if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    $error_message = 'All password fields are required when changing password.';
                } elseif (!validatePassword($new_password)) {
                    $error_message = 'New password must be at least 8 characters long and contain letters, numbers, and symbols.';
                } elseif ($new_password !== $confirm_password) {
                    $error_message = 'New passwords do not match.';
                } else {
                    // Verify current password
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();

                    if (!password_verify($current_password, $user['password'])) {
                        $error_message = 'Current password is incorrect.';
                    } else {
                        // Update name and password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET name = ?, password = ? WHERE id = ?");
                        $stmt->execute([$name, $hashed_password, $_SESSION['user_id']]);
                        
                        $_SESSION['name'] = $name;
                        $success_message = 'Profile and password updated successfully!';
                    }
                }
            } else {
                // Update only name
                $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
                $stmt->execute([$name, $_SESSION['user_id']]);
                
                $_SESSION['name'] = $name;
                $success_message = 'Profile updated successfully!';
            }
        } catch (PDOException $e) {
            $error_message = 'Database error occurred. Please try again.';
        }
    }
}

// Get current user data
try {
    $stmt = $pdo->prepare("SELECT name, email, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $error_message = 'Error loading profile data.';
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h3><?= sanitizeInput($user['name'] ?? $_SESSION['name']) ?></h3>
                <p class="text-muted"><?= sanitizeInput($user['email'] ?? $_SESSION['email']) ?></p>
                <span class="badge bg-primary"><?= ucfirst($user['role'] ?? $_SESSION['role']) ?></span>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $success_message ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= $error_message ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-user-edit me-2"></i>Profile Information</h5>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= sanitizeInput($user['name'] ?? $_SESSION['name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" 
                                   value="<?= sanitizeInput($user['email'] ?? $_SESSION['email']) ?>" disabled>
                            <small class="form-text text-muted">Email cannot be changed</small>
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <input type="text" class="form-control" id="role" 
                                   value="<?= ucfirst($user['role'] ?? $_SESSION['role']) ?>" disabled>
                        </div>

                        <?php if (isset($user['created_at'])): ?>
                        <div class="mb-3">
                            <label for="member_since" class="form-label">Member Since</label>
                            <input type="text" class="form-control" id="member_since" 
                                   value="<?= date('F j, Y', strtotime($user['created_at'])) ?>" disabled>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <h5><i class="fas fa-lock me-2"></i>Change Password</h5>
                        <p class="text-muted small">Leave password fields empty if you don't want to change your password.</p>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" 
                                   placeholder="Enter your current password">
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   placeholder="Enter new password">
                            <small class="form-text text-muted">Must be 8+ characters with letters, numbers, and symbols</small>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm new password">
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Update Profile
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-lg ms-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Password strength indicator
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    if (password.length === 0) {
        this.classList.remove('is-valid', 'is-invalid');
        return;
    }
    
    const hasLetters = /[a-zA-Z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSymbols = /[\W_]/.test(password);
    const isLongEnough = password.length >= 8;
    
    const isValid = hasLetters && hasNumbers && hasSymbols && isLongEnough;
    
    if (isValid) {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    } else {
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
    }
});

// Confirm password validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword.length === 0) {
        this.classList.remove('is-valid', 'is-invalid');
        return;
    }
    
    if (password === confirmPassword) {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    } else {
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
    }
});

// Clear password fields when one is cleared
document.addEventListener('input', function(e) {
    if (e.target.id === 'current_password' || e.target.id === 'new_password' || e.target.id === 'confirm_password') {
        const current = document.getElementById('current_password');
        const newPass = document.getElementById('new_password');
        const confirm = document.getElementById('confirm_password');
        
        if (current.value === '' && newPass.value === '' && confirm.value === '') {
            [current, newPass, confirm].forEach(field => {
                field.classList.remove('is-valid', 'is-invalid');
            });
        }
    }
});
</script>

<?php require_once 'footer.php'; ?>