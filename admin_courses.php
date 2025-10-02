<?php
$page_title = 'Manage Classes';
require_once 'config.php';
require_once 'db.php';

// Require admin access (before any HTML output)
requireAdmin();

require_once 'header.php';

$success_message = '';
$error_message = '';
$selected_class = null;

// Handle form submissions
if ($_POST) {
    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
    
    if ($action === 'delete_course') {
        $class_id = $_POST['course_id'] ?? '';
        if (is_numeric($class_id)) {
            try {
                // Delete class and all related data (cascading deletes)
                $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
                $stmt->execute([$class_id]);
                $success_message = 'Class deleted successfully.';
            } catch (PDOException $e) {
                $error_message = 'Error deleting class. Please try again.';
            }
        }
    } elseif ($action === 'edit_course') {
        $class_id = $_POST['course_id'] ?? '';
        $class_code = sanitizeInput($_POST['course_code'] ?? '');
        $class_name = sanitizeInput($_POST['course_name'] ?? '');
        $trainer = sanitizeInput($_POST['instructor'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        
        if (is_numeric($class_id) && !empty($class_code) && !empty($class_name) && !empty($trainer)) {
            try {
                $stmt = $pdo->prepare("UPDATE classes SET class_code = ?, class_name = ?, trainer = ?, description = ? WHERE id = ?");
                $stmt->execute([$class_code, $class_name, $trainer, $description, $class_id]);
                $success_message = 'Class updated successfully.';
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
                    $error_message = 'Class code is already in use.';
                } else {
                    $error_message = 'Error updating class. Please try again.';
                }
            }
        } else {
            $error_message = 'Class code, name, and trainer are required.';
        }
    } elseif ($action === 'add_course') {
        $class_code = sanitizeInput($_POST['course_code'] ?? '');
        $class_name = sanitizeInput($_POST['course_name'] ?? '');
        $trainer = sanitizeInput($_POST['instructor'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        
        if (!empty($class_code) && !empty($class_name) && !empty($trainer)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO classes (class_code, class_name, trainer, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([$class_code, $class_name, $trainer, $description]);
                $success_message = 'Class added successfully.';
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
                    $error_message = 'Class code is already in use.';
                } else {
                    $error_message = 'Error adding class. Please try again.';
                }
            }
        } else {
            $error_message = 'Class code, name, and trainer are required.';
        }
    } elseif ($action === 'assign_grade') {
        $member_email = sanitizeInput($_POST['student_email'] ?? '');
        $class_id = $_POST['course_id'] ?? '';
        $performance_score = sanitizeInput($_POST['grade'] ?? '');
        $score_points = $_POST['grade_points'] ?? '';
        
        if (!empty($member_email) && is_numeric($class_id) && !empty($performance_score)) {
            try {
                // Get member ID by email
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'member'");
                $stmt->execute([$member_email]);
                $member = $stmt->fetch();
                
                if (!$member) {
                    $error_message = 'Member not found.';
                } else {
                    // Check if member is registered in the class
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM memberships WHERE user_id = ? AND class_id = ?");
                    $stmt->execute([$member['id'], $class_id]);
                    $is_registered = $stmt->fetchColumn() > 0;
                    
                    if (!$is_registered) {
                        $error_message = 'Member is not registered for this class.';
                    } else {
                        // Insert or update progress (database agnostic)
                        try {
                            // Try insert first
                            $stmt = $pdo->prepare("INSERT INTO progress (user_id, class_id, performance_score, score_points) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$member['id'], $class_id, $performance_score, $score_points]);
                        } catch (PDOException $insert_e) {
                            // If insert fails due to duplicate, try update
                            $stmt = $pdo->prepare("UPDATE progress SET performance_score = ?, score_points = ? WHERE user_id = ? AND class_id = ?");
                            $stmt->execute([$performance_score, $score_points, $member['id'], $class_id]);
                        }
                        $success_message = 'Progress assigned successfully.';
                    }
                }
            } catch (PDOException $e) {
                $error_message = 'Error assigning progress. Please try again.';
            }
        } else {
            $error_message = 'All progress fields are required.';
        }
    }
    }
}

// Get class for editing
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $selected_class = $stmt->fetch();
    } catch (PDOException $e) {
        $error_message = 'Error loading class data.';
    }
}

// Get all classes with their statistics
try {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(DISTINCT m.user_id) as registered_members,
               COUNT(DISTINCT p.user_id) as tracked_members
        FROM classes c
        LEFT JOIN memberships m ON c.id = m.class_id
        LEFT JOIN progress p ON c.id = p.class_id
        GROUP BY c.id, c.class_code, c.class_name, c.trainer, c.description, c.created_at
        ORDER BY c.class_code
    ");
    $stmt->execute();
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = 'Database error occurred while loading classes.';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-dumbbell me-2"></i>Manage Fitness Classes</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
        <i class="fas fa-plus me-1"></i>Add New Class
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

<!-- Classes Table -->
<?php if (!empty($classes)): ?>
<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-list me-2"></i>Classes List</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>Class Code</th>
                        <th>Class Name</th>
                        <th>Trainer</th>
                        <th>Registered Members</th>
                        <th>Tracked Progress</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $class): ?>
                        <tr>
                            <td>
                                <strong><?= sanitizeInput($class['class_code']) ?></strong>
                            </td>
                            <td><?= sanitizeInput($class['class_name']) ?></td>
                            <td>
                                <i class="fas fa-user-tie me-1"></i>
                                <?= sanitizeInput($class['trainer']) ?>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?= $class['registered_members'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-success"><?= $class['tracked_members'] ?></span>
                            </td>
                            <td>
                                <small><?= date('M j, Y', strtotime($class['created_at'])) ?></small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="course.php?id=<?= $class['id'] ?>" 
                                       class="btn btn-outline-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-outline-success" 
                                            onclick="manageProgress(<?= $class['id'] ?>, '<?= sanitizeInput($class['class_name']) ?>')"
                                            title="Manage Progress">
                                        <i class="fas fa-chart-line"></i>
                                    </button>
                                    <button class="btn btn-outline-primary" 
                                            onclick="editClass(<?= htmlspecialchars(json_encode($class), ENT_QUOTES, 'UTF-8') ?>)"
                                            title="Edit Class">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" 
                                            onclick="deleteClass(<?= $class['id'] ?>, '<?= sanitizeInput($class['class_name']) ?>')"
                                            title="Delete Class">
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
    <i class="fas fa-dumbbell fa-5x text-muted mb-3"></i>
    <h3 class="text-muted">No Classes Found</h3>
    <p class="text-muted">There are no fitness classes in the system.</p>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
        <i class="fas fa-plus me-1"></i>Add First Class
    </button>
</div>
<?php endif; ?>

<!-- Add Class Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_course">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_course_code" class="form-label">Class Code</label>
                                <input type="text" class="form-control" id="add_course_code" name="course_code" 
                                       placeholder="e.g., YOGA101" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_instructor" class="form-label">Trainer</label>
                                <input type="text" class="form-control" id="add_instructor" name="instructor" 
                                       placeholder="e.g., Coach Sarah" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_course_name" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="add_course_name" name="course_name" 
                               placeholder="e.g., Beginner Yoga & Flexibility" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_description" class="form-label">Description</label>
                        <textarea class="form-control" id="add_description" name="description" rows="4" 
                                  placeholder="Class description and fitness goals"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add Class
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Class Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_course">
                    <input type="hidden" name="course_id" id="edit_course_id">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_course_code" class="form-label">Class Code</label>
                                <input type="text" class="form-control" id="edit_course_code" name="course_code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_instructor" class="form-label">Trainer</label>
                                <input type="text" class="form-control" id="edit_instructor" name="instructor" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_course_name" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="edit_course_name" name="course_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update Class
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Progress Assignment Modal -->
<div class="modal fade" id="gradeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-chart-line me-2"></i>Assign Progress</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_grade">
                    <input type="hidden" name="course_id" id="grade_course_id">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Class</label>
                        <input type="text" class="form-control" id="grade_course_name" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="student_email" class="form-label">Member Email</label>
                        <input type="email" class="form-control" id="student_email" name="student_email" 
                               placeholder="Enter member's email address" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="grade" class="form-label">Performance</label>
                                <select class="form-select" id="grade" name="grade" required>
                                    <option value="">Select Performance</option>
                                    <option value="Excellent">Excellent</option>
                                    <option value="Very Good">Very Good</option>
                                    <option value="Good">Good</option>
                                    <option value="Average">Average</option>
                                    <option value="Needs Improvement">Needs Improvement</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="grade_points" class="form-label">Score Points</label>
                                <input type="number" class="form-control" id="grade_points" name="grade_points" 
                                       min="0" max="100" step="1" placeholder="0 - 100">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Assign Progress
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Class Modal -->
<div class="modal fade" id="deleteCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this class?</p>
                <p><strong id="delete_course_name"></strong></p>
                <p class="text-danger"><i class="fas fa-warning me-1"></i>This action cannot be undone. All memberships and progress for this class will be permanently deleted.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete_course">
                    <input type="hidden" name="course_id" id="delete_course_id">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Delete Class
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editClass(classData) {
    document.getElementById('edit_course_id').value = classData.id;
    document.getElementById('edit_course_code').value = classData.class_code;
    document.getElementById('edit_course_name').value = classData.class_name;
    document.getElementById('edit_instructor').value = classData.trainer;
    document.getElementById('edit_description').value = classData.description || '';
    
    new bootstrap.Modal(document.getElementById('editCourseModal')).show();
}

function deleteClass(classId, className) {
    document.getElementById('delete_course_id').value = classId;
    document.getElementById('delete_course_name').textContent = className;
    
    new bootstrap.Modal(document.getElementById('deleteCourseModal')).show();
}

function manageProgress(classId, className) {
    document.getElementById('grade_course_id').value = classId;
    document.getElementById('grade_course_name').value = className;
    document.getElementById('student_email').value = '';
    document.getElementById('grade').value = '';
    document.getElementById('grade_points').value = '';
    
    new bootstrap.Modal(document.getElementById('gradeModal')).show();
}

// Auto-fill score points based on selected performance
document.getElementById('grade').addEventListener('change', function() {
    const scorePoints = {
        'Excellent': '95',
        'Very Good': '85',
        'Good': '75',
        'Average': '65',
        'Needs Improvement': '50'
    };
    
    document.getElementById('grade_points').value = scorePoints[this.value] || '';
});
</script>

<?php require_once 'footer.php'; ?>
