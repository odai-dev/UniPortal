<?php
$page_title = 'Courses';
require_once 'config.php';
require_once 'db.php';

// Require login (before any HTML output)
requireLogin();

require_once 'header.php';

// Get all courses
try {
    $stmt = $pdo->prepare("SELECT * FROM courses ORDER BY course_code");
    $stmt->execute();
    $courses = $stmt->fetchAll();
    
    // If student, get enrolled courses
    $enrolled_course_ids = [];
    if (!isAdmin()) {
        $stmt = $pdo->prepare("SELECT course_id FROM enrollments WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $enrolled_course_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (PDOException $e) {
    $error_message = 'Database error occurred while loading courses.';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-book me-2"></i>Available Courses</h1>
    <?php if (isAdmin()): ?>
        <a href="admin_courses.php" class="btn btn-primary">
            <i class="fas fa-cog me-1"></i>Manage Courses
        </a>
    <?php endif; ?>
</div>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= $error_message ?>
    </div>
<?php endif; ?>

<?php if (!empty($courses)): ?>
    <div class="row">
        <?php foreach ($courses as $course): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card course-card h-100">
                    <div class="course-header">
                        <div class="course-code"><?= sanitizeInput($course['course_code']) ?></div>
                        <div class="course-name"><?= sanitizeInput($course['course_name']) ?></div>
                    </div>
                    
                    <div class="card-body d-flex flex-column">
                        <div class="mb-3">
                            <p class="text-muted mb-1">
                                <i class="fas fa-user-tie me-1"></i>
                                <strong>Instructor:</strong>
                            </p>
                            <p class="mb-2"><?= sanitizeInput($course['instructor']) ?></p>
                            
                            <p class="text-muted mb-1">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>Description:</strong>
                            </p>
                            <p class="text-muted">
                                <?= substr(sanitizeInput($course['description']), 0, 120) ?>
                                <?= strlen($course['description']) > 120 ? '...' : '' ?>
                            </p>
                        </div>
                        
                        <div class="mt-auto">
                            <?php if (!isAdmin() && in_array($course['id'], $enrolled_course_ids)): ?>
                                <span class="badge bg-success mb-2">
                                    <i class="fas fa-check me-1"></i>Enrolled
                                </span>
                            <?php endif; ?>
                            
                            <div class="d-grid">
                                <a href="course.php?id=<?= $course['id'] ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-transparent">
                        <small class="text-muted">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Added: <?= date('M j, Y', strtotime($course['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Statistics -->
    <div class="row mt-5">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Course Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h4 class="text-primary"><?= count($courses) ?></h4>
                            <p class="text-muted">Total Courses</p>
                        </div>
                        <?php if (!isAdmin()): ?>
                        <div class="col-md-3">
                            <h4 class="text-success"><?= count($enrolled_course_ids) ?></h4>
                            <p class="text-muted">Enrolled Courses</p>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-warning"><?= count($courses) - count($enrolled_course_ids) ?></h4>
                            <p class="text-muted">Available to Enroll</p>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-info"><?= count($enrolled_course_ids) > 0 ? round((count($enrolled_course_ids) / count($courses)) * 100) : 0 ?>%</h4>
                            <p class="text-muted">Enrollment Rate</p>
                        </div>
                        <?php else: ?>
                        <div class="col-md-3">
                            <h4 class="text-success">5</h4>
                            <p class="text-muted">Departments</p>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-warning">15</h4>
                            <p class="text-muted">Average Students/Course</p>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-info"><?= date('Y') ?></h4>
                            <p class="text-muted">Academic Year</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="text-center py-5">
        <i class="fas fa-book fa-5x text-muted mb-3"></i>
        <h3 class="text-muted">No Courses Available</h3>
        <p class="text-muted">There are currently no courses in the system.</p>
        <?php if (isAdmin()): ?>
            <a href="admin_courses.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Add First Course
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>