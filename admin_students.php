<?php
$page_title = 'Manage Students';
require_once 'header.php';

// Require admin access
requireAdmin();

$success_message = '';
$error_message = '';
$selected_student = null;

// Handle form submissions
if ($_POST) {
    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
    
    if ($action === 'delete_student') {
        $student_id = $_POST['student_id'] ?? '';
        if (is_numeric($student_id)) {
            try {
                // Delete student and all related data (cascading deletes)
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
                $stmt->execute([$student_id]);
                $success_message = 'Student deleted successfully.';
            } catch (PDOException $e) {
                $error_message = 'Error deleting student. Please try again.';
            }
        }
    } elseif ($action === 'edit_student') {
        $student_id = $_POST['student_id'] ?? '';
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        
        if (is_numeric($student_id) && !empty($name) && !empty($email)) {
            try {
                if (!empty($new_password)) {
                    // Update with new password
                    if (!validatePassword($new_password)) {
                        $error_message = 'Password must be at least 8 characters long and contain letters, numbers, and symbols.';
                    } else {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ? AND role = 'student'");
                        $stmt->execute([$name, $email, $hashed_password, $student_id]);
                        $success_message = 'Student updated successfully with new password.';
                    }
                } else {
                    // Update without changing password
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ? AND role = 'student'");
                    $stmt->execute([$name, $email, $student_id]);
                    $success_message = 'Student updated successfully.';
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
                    $error_message = 'Email address is already in use by another user.';
                } else {
                    $error_message = 'Error updating student. Please try again.';
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
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
                    $stmt->execute([$name, $email, $hashed_password]);
                    $success_message = 'Student added successfully.';
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
                        $error_message = 'Email address is already registered.';
                    } else {
                        $error_message = 'Error adding student. Please try again.';
                    }
                }
            }
        } else {
            $error_message = 'All fields are required.';
        }
    }
    }
}

// Get student for editing
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
        $stmt->execute([$_GET['edit']]);
        $selected_student = $stmt->fetch();
    } catch (PDOException $e) {
        $error_message = 'Error loading student data.';
    }
}

// Get all students with their statistics
try {
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(DISTINCT e.course_id) as enrolled_courses,
               COUNT(DISTINCT g.course_id) as graded_courses
        FROM users u
        LEFT JOIN enrollments e ON u.id = e.user_id
        LEFT JOIN grades g ON u.id = g.user_id
        WHERE u.role = 'student'
        GROUP BY u.id, u.name, u.email, u.created_at
        ORDER BY u.name
    ");
    $stmt->execute();
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = 'Database error occurred while loading students.';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-users me-2"></i>Manage Students</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="fas fa-plus me-1"></i>Add New Student
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

<!-- Students Table -->
<?php if (!empty($students)): ?>
<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-list me-2"></i>Students List</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Enrolled Courses</th>
                        <th>Graded Courses</th>
                        <th>Member Since</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td>
                                <i class="fas fa-user me-1"></i>
                                <strong><?= sanitizeInput($student['name']) ?></strong>
                            </td>
                            <td><?= sanitizeInput($student['email']) ?></td>
                            <td>
                                <span class="badge bg-primary"><?= $student['enrolled_courses'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-success"><?= $student['graded_courses'] ?></span>
                            </td>
                            <td>
                                <small><?= date('M j, Y', strtotime($student['created_at'])) ?></small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="grades.php?student_id=<?= $student['id'] ?>" 
                                       class="btn btn-outline-info" title="View Grades">
                                        <i class="fas fa-chart-line"></i>
                                    </a>
                                    <button class="btn btn-outline-primary" 
                                            onclick="editStudent(<?= htmlspecialchars(json_encode($student), ENT_QUOTES, 'UTF-8') ?>)"
                                            title="Edit Student">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" 
                                            onclick="deleteStudent(<?= $student['id'] ?>, '<?= sanitizeInput($student['name']) ?>')"
                                            title="Delete Student">
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
    <h3 class="text-muted">No Students Found</h3>
    <p class="text-muted">There are no students registered in the system.</p>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="fas fa-plus me-1"></i>Add First Student
    </button>
</div>
<?php endif; ?>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Student</h5>
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
                        <i class="fas fa-plus me-1"></i>Add Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Student</h5>
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
                        <i class="fas fa-save me-1"></i>Update Student
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
                <p>Are you sure you want to delete this student?</p>
                <p><strong id="delete_student_name"></strong></p>
                <p class="text-danger"><i class="fas fa-warning me-1"></i>This action cannot be undone. All student data, enrollments, and grades will be permanently deleted.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete_student">
                    <input type="hidden" name="student_id" id="delete_student_id">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Delete Student
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editStudent(student) {
    document.getElementById('edit_student_id').value = student.id;
    document.getElementById('edit_name').value = student.name;
    document.getElementById('edit_email').value = student.email;
    document.getElementById('edit_password').value = '';
    
    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
}

function deleteStudent(studentId, studentName) {
    document.getElementById('delete_student_id').value = studentId;
    document.getElementById('delete_student_name').textContent = studentName;
    
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