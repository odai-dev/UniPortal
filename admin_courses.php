<?php
$page_title = 'Manage Courses';
require_once 'header.php';

// Require admin access
requireAdmin();

$success_message = '';
$error_message = '';
$selected_course = null;

// Handle form submissions
if ($_POST) {
    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
    
    if ($action === 'delete_course') {
        $course_id = $_POST['course_id'] ?? '';
        if (is_numeric($course_id)) {
            try {
                // Delete course and all related data (cascading deletes)
                $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
                $stmt->execute([$course_id]);
                $success_message = 'Course deleted successfully.';
            } catch (PDOException $e) {
                $error_message = 'Error deleting course. Please try again.';
            }
        }
    } elseif ($action === 'edit_course') {
        $course_id = $_POST['course_id'] ?? '';
        $course_code = sanitizeInput($_POST['course_code'] ?? '');
        $course_name = sanitizeInput($_POST['course_name'] ?? '');
        $instructor = sanitizeInput($_POST['instructor'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        
        if (is_numeric($course_id) && !empty($course_code) && !empty($course_name) && !empty($instructor)) {
            try {
                $stmt = $pdo->prepare("UPDATE courses SET course_code = ?, course_name = ?, instructor = ?, description = ? WHERE id = ?");
                $stmt->execute([$course_code, $course_name, $instructor, $description, $course_id]);
                $success_message = 'Course updated successfully.';
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
                    $error_message = 'Course code is already in use.';
                } else {
                    $error_message = 'Error updating course. Please try again.';
                }
            }
        } else {
            $error_message = 'Course code, name, and instructor are required.';
        }
    } elseif ($action === 'add_course') {
        $course_code = sanitizeInput($_POST['course_code'] ?? '');
        $course_name = sanitizeInput($_POST['course_name'] ?? '');
        $instructor = sanitizeInput($_POST['instructor'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        
        if (!empty($course_code) && !empty($course_name) && !empty($instructor)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO courses (course_code, course_name, instructor, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([$course_code, $course_name, $instructor, $description]);
                $success_message = 'Course added successfully.';
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
                    $error_message = 'Course code is already in use.';
                } else {
                    $error_message = 'Error adding course. Please try again.';
                }
            }
        } else {
            $error_message = 'Course code, name, and instructor are required.';
        }
    } elseif ($action === 'assign_grade') {
        $student_email = sanitizeInput($_POST['student_email'] ?? '');
        $course_id = $_POST['course_id'] ?? '';
        $grade = sanitizeInput($_POST['grade'] ?? '');
        $grade_points = $_POST['grade_points'] ?? '';
        
        if (!empty($student_email) && is_numeric($course_id) && !empty($grade)) {
            try {
                // Get student ID by email
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'student'");
                $stmt->execute([$student_email]);
                $student = $stmt->fetch();
                
                if (!$student) {
                    $error_message = 'Student not found.';
                } else {
                    // Check if student is enrolled in the course
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND course_id = ?");
                    $stmt->execute([$student['id'], $course_id]);
                    $is_enrolled = $stmt->fetchColumn() > 0;
                    
                    if (!$is_enrolled) {
                        $error_message = 'Student is not enrolled in this course.';
                    } else {
                        // Insert or update grade (database agnostic)
                        try {
                            // Try insert first
                            $stmt = $pdo->prepare("INSERT INTO grades (user_id, course_id, grade, grade_points) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$student['id'], $course_id, $grade, $grade_points]);
                        } catch (PDOException $insert_e) {
                            // If insert fails due to duplicate, try update
                            $stmt = $pdo->prepare("UPDATE grades SET grade = ?, grade_points = ? WHERE user_id = ? AND course_id = ?");
                            $stmt->execute([$grade, $grade_points, $student['id'], $course_id]);
                        }
                        $success_message = 'Grade assigned successfully.';
                    }
                }
            } catch (PDOException $e) {
                $error_message = 'Error assigning grade. Please try again.';
            }
        } else {
            $error_message = 'All grade fields are required.';
        }
    }
    }
}

// Get course for editing
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $selected_course = $stmt->fetch();
    } catch (PDOException $e) {
        $error_message = 'Error loading course data.';
    }
}

// Get all courses with their statistics
try {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(DISTINCT e.user_id) as enrolled_students,
               COUNT(DISTINCT g.user_id) as graded_students
        FROM courses c
        LEFT JOIN enrollments e ON c.id = e.course_id
        LEFT JOIN grades g ON c.id = g.course_id
        GROUP BY c.id, c.course_code, c.course_name, c.instructor, c.description, c.created_at
        ORDER BY c.course_code
    ");
    $stmt->execute();
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = 'Database error occurred while loading courses.';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-book-open me-2"></i>Manage Courses</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
        <i class="fas fa-plus me-1"></i>Add New Course
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

<!-- Courses Table -->
<?php if (!empty($courses)): ?>
<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-list me-2"></i>Courses List</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Instructor</th>
                        <th>Enrolled Students</th>
                        <th>Graded Students</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td>
                                <strong><?= sanitizeInput($course['course_code']) ?></strong>
                            </td>
                            <td><?= sanitizeInput($course['course_name']) ?></td>
                            <td>
                                <i class="fas fa-user-tie me-1"></i>
                                <?= sanitizeInput($course['instructor']) ?>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?= $course['enrolled_students'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-success"><?= $course['graded_students'] ?></span>
                            </td>
                            <td>
                                <small><?= date('M j, Y', strtotime($course['created_at'])) ?></small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="course.php?id=<?= $course['id'] ?>" 
                                       class="btn btn-outline-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-outline-success" 
                                            onclick="manageGrades(<?= $course['id'] ?>, '<?= sanitizeInput($course['course_name']) ?>')"
                                            title="Manage Grades">
                                        <i class="fas fa-chart-line"></i>
                                    </button>
                                    <button class="btn btn-outline-primary" 
                                            onclick="editCourse(<?= htmlspecialchars(json_encode($course), ENT_QUOTES, 'UTF-8') ?>)"
                                            title="Edit Course">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" 
                                            onclick="deleteCourse(<?= $course['id'] ?>, '<?= sanitizeInput($course['course_name']) ?>')"
                                            title="Delete Course">
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
    <i class="fas fa-book-open fa-5x text-muted mb-3"></i>
    <h3 class="text-muted">No Courses Found</h3>
    <p class="text-muted">There are no courses in the system.</p>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
        <i class="fas fa-plus me-1"></i>Add First Course
    </button>
</div>
<?php endif; ?>

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_course">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_course_code" class="form-label">Course Code</label>
                                <input type="text" class="form-control" id="add_course_code" name="course_code" 
                                       placeholder="e.g., CS101" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_instructor" class="form-label">Instructor</label>
                                <input type="text" class="form-control" id="add_instructor" name="instructor" 
                                       placeholder="e.g., Dr. Smith" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_course_name" class="form-label">Course Name</label>
                        <input type="text" class="form-control" id="add_course_name" name="course_name" 
                               placeholder="e.g., Introduction to Computer Science" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_description" class="form-label">Description</label>
                        <textarea class="form-control" id="add_description" name="description" rows="4" 
                                  placeholder="Course description and objectives"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Course</h5>
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
                                <label for="edit_course_code" class="form-label">Course Code</label>
                                <input type="text" class="form-control" id="edit_course_code" name="course_code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_instructor" class="form-label">Instructor</label>
                                <input type="text" class="form-control" id="edit_instructor" name="instructor" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_course_name" class="form-label">Course Name</label>
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
                        <i class="fas fa-save me-1"></i>Update Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Grade Assignment Modal -->
<div class="modal fade" id="gradeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-chart-line me-2"></i>Assign Grade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_grade">
                    <input type="hidden" name="course_id" id="grade_course_id">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Course</label>
                        <input type="text" class="form-control" id="grade_course_name" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="student_email" class="form-label">Student Email</label>
                        <input type="email" class="form-control" id="student_email" name="student_email" 
                               placeholder="Enter student's email address" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="grade" class="form-label">Grade</label>
                                <select class="form-select" id="grade" name="grade" required>
                                    <option value="">Select Grade</option>
                                    <option value="A+">A+</option>
                                    <option value="A">A</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B">B</option>
                                    <option value="B-">B-</option>
                                    <option value="C+">C+</option>
                                    <option value="C">C</option>
                                    <option value="C-">C-</option>
                                    <option value="D">D</option>
                                    <option value="F">F</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="grade_points" class="form-label">Grade Points</label>
                                <input type="number" class="form-control" id="grade_points" name="grade_points" 
                                       min="0" max="4" step="0.1" placeholder="0.0 - 4.0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Assign Grade
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Course Modal -->
<div class="modal fade" id="deleteCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this course?</p>
                <p><strong id="delete_course_name"></strong></p>
                <p class="text-danger"><i class="fas fa-warning me-1"></i>This action cannot be undone. All enrollments and grades for this course will be permanently deleted.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete_course">
                    <input type="hidden" name="course_id" id="delete_course_id">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Delete Course
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editCourse(course) {
    document.getElementById('edit_course_id').value = course.id;
    document.getElementById('edit_course_code').value = course.course_code;
    document.getElementById('edit_course_name').value = course.course_name;
    document.getElementById('edit_instructor').value = course.instructor;
    document.getElementById('edit_description').value = course.description || '';
    
    new bootstrap.Modal(document.getElementById('editCourseModal')).show();
}

function deleteCourse(courseId, courseName) {
    document.getElementById('delete_course_id').value = courseId;
    document.getElementById('delete_course_name').textContent = courseName;
    
    new bootstrap.Modal(document.getElementById('deleteCourseModal')).show();
}

function manageGrades(courseId, courseName) {
    document.getElementById('grade_course_id').value = courseId;
    document.getElementById('grade_course_name').value = courseName;
    document.getElementById('student_email').value = '';
    document.getElementById('grade').value = '';
    document.getElementById('grade_points').value = '';
    
    new bootstrap.Modal(document.getElementById('gradeModal')).show();
}

// Auto-fill grade points based on selected grade
document.getElementById('grade').addEventListener('change', function() {
    const gradePoints = {
        'A+': '4.0', 'A': '4.0', 'A-': '3.7',
        'B+': '3.3', 'B': '3.0', 'B-': '2.7',
        'C+': '2.3', 'C': '2.0', 'C-': '1.7',
        'D': '1.0', 'F': '0.0'
    };
    
    document.getElementById('grade_points').value = gradePoints[this.value] || '';
});
</script>

<?php require_once 'footer.php'; ?>