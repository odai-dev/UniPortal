<?php
$page_title = 'Course Details';
require_once 'config.php';
require_once 'db.php';

// Require login (before any HTML output)
requireLogin();

require_once 'header.php';

$course_id = $_GET['id'] ?? null;

if (!$course_id || !is_numeric($course_id)) {
    header('Location: courses.php');
    exit();
}

// Get course details
try {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        header('Location: courses.php');
        exit();
    }
    
    // Check if student is enrolled in this course
    $is_enrolled = false;
    $enrollment_count = 0;
    $student_grade = null;
    
    if (!isAdmin()) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$_SESSION['user_id'], $course_id]);
        $is_enrolled = $stmt->fetchColumn() > 0;
        
        // Get student's grade for this course if available
        $stmt = $pdo->prepare("SELECT grade, grade_points FROM grades WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$_SESSION['user_id'], $course_id]);
        $student_grade = $stmt->fetch();
    }
    
    // Get enrollment count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $enrollment_count = $stmt->fetchColumn();
    
    // Get enrolled students (for admin or enrolled students)
    $enrolled_students = [];
    if (isAdmin() || $is_enrolled) {
        $stmt = $pdo->prepare("
            SELECT u.name, u.email, e.enrolled_at,
                   g.grade, g.grade_points
            FROM enrollments e
            JOIN users u ON e.user_id = u.id
            LEFT JOIN grades g ON g.user_id = u.id AND g.course_id = e.course_id
            WHERE e.course_id = ?
            ORDER BY e.enrolled_at DESC
        ");
        $stmt->execute([$course_id]);
        $enrolled_students = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    $error_message = 'Database error occurred while loading course details.';
}

$page_title = isset($course) ? $course['course_name'] : 'Course Details';
?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= $error_message ?>
    </div>
<?php endif; ?>

<?php if (isset($course)): ?>
    <!-- Course Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="course-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="course-code"><?= sanitizeInput($course['course_code']) ?></div>
                            <div class="course-name"><?= sanitizeInput($course['course_name']) ?></div>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if (!isAdmin()): ?>
                                <?php if ($is_enrolled): ?>
                                    <span class="badge bg-success fs-6">
                                        <i class="fas fa-check me-1"></i>Enrolled
                                    </span>
                                <?php else: ?>
                                    <button class="btn btn-light" onclick="enrollInCourse()">
                                        <i class="fas fa-plus me-1"></i>Enroll
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="admin_courses.php?edit=<?= $course['id'] ?>" class="btn btn-light">
                                    <i class="fas fa-edit me-1"></i>Edit Course
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Information -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle me-2"></i>Course Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Course Code:</strong></div>
                        <div class="col-sm-9"><?= sanitizeInput($course['course_code']) ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Course Name:</strong></div>
                        <div class="col-sm-9"><?= sanitizeInput($course['course_name']) ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Instructor:</strong></div>
                        <div class="col-sm-9">
                            <i class="fas fa-user-tie me-1"></i>
                            <?= sanitizeInput($course['instructor']) ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Description:</strong></div>
                        <div class="col-sm-9"><?= nl2br(sanitizeInput($course['description'])) ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Created:</strong></div>
                        <div class="col-sm-9">
                            <i class="fas fa-calendar-alt me-1"></i>
                            <?= date('F j, Y', strtotime($course['created_at'])) ?>
                        </div>
                    </div>
                    
                    <!-- Student's Grade (if enrolled and graded) -->
                    <?php if (!isAdmin() && $student_grade): ?>
                    <div class="row">
                        <div class="col-sm-3"><strong>Your Grade:</strong></div>
                        <div class="col-sm-9">
                            <span class="badge bg-success fs-6">
                                <?= sanitizeInput($student_grade['grade']) ?>
                            </span>
                            <?php if ($student_grade['grade_points']): ?>
                                <small class="text-muted ms-2">
                                    (<?= $student_grade['grade_points'] ?> points)
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar me-2"></i>Course Statistics</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <h4 class="text-primary"><?= $enrollment_count ?></h4>
                        <p class="text-muted mb-0">Enrolled Students</p>
                    </div>
                    
                    <?php if (isAdmin()): ?>
                    <div class="mb-3">
                        <h4 class="text-success"><?= count(array_filter($enrolled_students, function($s) { return !empty($s['grade']); })) ?></h4>
                        <p class="text-muted mb-0">Graded Students</p>
                    </div>
                    <?php endif; ?>
                    
                    <div>
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            Academic Year <?= date('Y') ?>
                        </small>
                    </div>
                </div>
            </div>
            
            <?php if (!isAdmin() && $is_enrolled): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-tasks me-2"></i>Course Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="grades.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-chart-line me-1"></i>View My Grades
                        </a>
                        <a href="courses.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-book me-1"></i>Browse All Courses
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Enrolled Students (visible to admin or enrolled students) -->
    <?php if ((isAdmin() || $is_enrolled) && !empty($enrolled_students)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-users me-2"></i>Enrolled Students</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Email</th>
                                    <th>Enrolled Date</th>
                                    <th>Grade</th>
                                    <?php if (isAdmin()): ?>
                                    <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrolled_students as $student): ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-user me-1"></i>
                                            <?= sanitizeInput($student['name']) ?>
                                        </td>
                                        <td><?= sanitizeInput($student['email']) ?></td>
                                        <td>
                                            <small><?= date('M j, Y', strtotime($student['enrolled_at'])) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($student['grade']): ?>
                                                <span class="badge bg-success">
                                                    <?= sanitizeInput($student['grade']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Not Graded</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if (isAdmin()): ?>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="manageGrade('<?= $student['email'] ?>')">
                                                <i class="fas fa-edit me-1"></i>Grade
                                            </button>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <div class="row mt-4">
        <div class="col-12">
            <a href="courses.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Courses
            </a>
        </div>
    </div>

<?php endif; ?>

<script>
function enrollInCourse() {
    if (confirm('Are you sure you want to enroll in this course?')) {
        // Simple enrollment - in a real system, this would be an AJAX request
        fetch('enroll.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'course_id=<?= $course_id ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Enrollment failed. Please try again.');
            }
        })
        .catch(error => {
            alert('An error occurred. Please try again.');
        });
    }
}

function manageGrade(studentEmail) {
    // In a real system, this would open a modal or redirect to grade management
    alert('Grade management for: ' + studentEmail + '\n\nThis feature would be implemented in the admin course management system.');
}
</script>

<?php require_once 'footer.php'; ?>