<?php
$page_title = 'Manage Members';
require_once 'config.php';
require_once 'db.php';

// Require admin access (before any HTML output)
requireAdmin();

require_once 'header.php';

$success_message = '';
$error_message = '';
$selected_member = null;

// Handle form submissions
if ($_POST) {
    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
    
    if ($action === 'delete_student') {
        $member_id = $_POST['student_id'] ?? '';
        if (is_numeric($member_id)) {
            try {
                // Delete member and all related data (cascading deletes)
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'member'");
                $stmt->execute([$member_id]);
                $success_message = 'Member deleted successfully.';
            } catch (PDOException $e) {
                $error_message = 'Error deleting member. Please try again.';
            }
        }
    } elseif ($action === 'edit_student') {
        $member_id = $_POST['student_id'] ?? '';
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        
        if (is_numeric($member_id) && !empty($name) && !empty($email)) {
            try {
                if (!empty($new_password)) {
                    // Update with new password
                    if (!validatePassword($new_password)) {
                        $error_message = 'Password must be at least 8 characters long and contain letters, numbers, and symbols.';
                    } else {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ? AND role = 'member'");
                        $stmt->execute([$name, $email, $hashed_password, $member_id]);
                        $success_message = 'Member updated successfully with new password.';
                    }
                } else {
                    // Update without changing password
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ? AND role = 'member'");
                    $stmt->execute([$name, $email, $member_id]);
                    $success_message = 'Member updated successfully.';
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
                    $error_message = 'Email address is already in use by another user.';
                } else {
                    $error_message = 'Error updating member. Please try again.';
                }
            }
        } else {
            $error_message = 'All fields are required.';
        }
    } elseif ($action === 'add_student') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (!empty($name) && !empty($email) && !empty($password)) {
            if (!validateEmail($email)) {
                $error_message = 'Please use a valid Gmail or Hotmail email address.';
            } elseif (!validatePassword($password)) {
                $error_message = 'Password must be at least 8 characters long and contain letters, numbers, and symbols.';
            } else {
                try {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'member')");
                    $stmt->execute([$name, $email, $hashed_password]);
                    $success_message = 'Member added successfully.';
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
                        $error_message = 'Email address is already registered.';
                    } else {
                        $error_message = 'Error adding member. Please try again.';
                    }
                }
            }
        } else {
            $error_message = 'All fields are required.';
        }
    }
    }
}

// Get member for editing
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'member'");
        $stmt->execute([$_GET['edit']]);
        $selected_member = $stmt->fetch();
    } catch (PDOException $e) {
        $error_message = 'Error loading member data.';
    }
}

// Get all members with their statistics
try {
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(DISTINCT m.class_id) as registered_classes,
               COUNT(DISTINCT p.class_id) as tracked_progress
        FROM users u
        LEFT JOIN memberships m ON u.id = m.user_id
        LEFT JOIN progress p ON u.id = p.user_id
        WHERE u.role = 'member'
        GROUP BY u.id, u.name, u.email, u.created_at
        ORDER BY u.name
    ");
    $stmt->execute();
    $members = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = 'Database error occurred while loading members.';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-users me-2"></i>Manage Members</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="fas fa-plus me-1"></i>Add New Member
    </button>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= $success_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= $error_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Members Table -->
<?php if (!empty($members)): ?>
<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-list me-2"></i>Members List</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Registered Classes</th>
                        <th>Tracked Progress</th>
                        <th>Member Since</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td>
                                <i class="fas fa-user me-1"></i>
                                <strong><?= sanitizeInput($member['name']) ?></strong>
                            </td>
                            <td><?= sanitizeInput($member['email']) ?></td>
                            <td>
                                <span class="badge bg-primary"><?= $member['registered_classes'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-success"><?= $member['tracked_progress'] ?></span>
                            </td>
                            <td>
                                <small><?= date('M j, Y', strtotime($member['created_at'])) ?></small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="grades.php?student_id=<?= $member['id'] ?>" 
                                       class="btn btn-outline-info" title="View Progress">
                                        <i class="fas fa-chart-line"></i>
                                    </a>
                                    <button class="btn btn-outline-primary" 
                                            onclick="editMember(<?= htmlspecialchars(json_encode($member), ENT_QUOTES, 'UTF-8') ?>)"
                                            title="Edit Member">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" 
                                            onclick="deleteMember(<?= $member['id'] ?>, '<?= sanitizeInput($member['name']) ?>')"
                                            title="Delete Member">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
<div class="text-center py-5">
    <i class="fas fa-users fa-5x text-muted mb-3"></i>
    <h3 class="text-muted">No Members Found</h3>
    <p class="text-muted">There are no members registered in the system.</p>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="fas fa-plus me-1"></i>Add First Member
    </button>
</div>
<?php endif; ?>

<!-- Add Member Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_student">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-3">
                        <label for="add_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="add_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="add_email" name="email" required>
                        <small class="form-text text-muted">Must be Gmail or Hotmail</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="add_password" name="password" required>
                        <small class="form-text text-muted">8+ characters with letters, numbers, and symbols</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Member Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_student">
                    <input type="hidden" name="student_id" id="edit_student_id">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                        <small class="form-text text-muted">Must be Gmail or Hotmail</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">New Password (Optional)</label>
                        <input type="password" class="form-control" id="edit_password" name="new_password">
                        <small class="form-text text-muted">Leave blank to keep current password</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this member?</p>
                <p><strong id="delete_student_name"></strong></p>
                <p class="text-danger"><i class="fas fa-warning me-1"></i>This action cannot be undone. All member data, registrations, and progress will be permanently deleted.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete_student">
                    <input type="hidden" name="student_id" id="delete_student_id">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Delete Member
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editMember(member) {
    document.getElementById('edit_student_id').value = member.id;
    document.getElementById('edit_name').value = member.name;
    document.getElementById('edit_email').value = member.email;
    document.getElementById('edit_password').value = '';
    
    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
}

function deleteMember(memberId, memberName) {
    document.getElementById('delete_student_id').value = memberId;
    document.getElementById('delete_student_name').textContent = memberName;
    
    new bootstrap.Modal(document.getElementById('deleteStudentModal')).show();
}

// Password validation
document.getElementById('add_password').addEventListener('input', function() {
    validatePasswordField(this);
});

document.getElementById('edit_password').addEventListener('input', function() {
    if (this.value.length > 0) {
        validatePasswordField(this);
    } else {
        this.classList.remove('is-valid', 'is-invalid');
    }
});

function validatePasswordField(field) {
    const password = field.value;
    const hasLetters = /[a-zA-Z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSymbols = /[\W_]/.test(password);
    const isLongEnough = password.length >= 8;
    
    const isValid = hasLetters && hasNumbers && hasSymbols && isLongEnough;
    
    if (isValid) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
    }
}
</script>

<?php require_once 'footer.php'; ?>
