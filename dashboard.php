<?php
$page_title = 'Dashboard';
require_once 'config.php';
require_once 'db.php';

// Require login (before any HTML output)
requireLogin();

require_once 'header.php';

// Get statistics for dashboard
try {
    // Get total students count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student'");
    $stmt->execute();
    $total_students = $stmt->fetchColumn();

    // Get total courses count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses");
    $stmt->execute();
    $total_courses = $stmt->fetchColumn();

    // Get total announcements count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM announcements");
    $stmt->execute();
    $total_announcements = $stmt->fetchColumn();

    // Student-specific data
    if (!isAdmin()) {
        // Get enrolled courses count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $enrolled_courses = $stmt->fetchColumn();

        // Get grades count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM grades WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $total_grades = $stmt->fetchColumn();

        // Get recent grades
        $stmt = $pdo->prepare("
            SELECT g.grade, c.course_name, c.course_code, g.created_at 
            FROM grades g 
            JOIN courses c ON g.course_id = c.id 
            WHERE g.user_id = ? 
            ORDER BY g.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $recent_grades = $stmt->fetchAll();
    }

    // Get recent announcements
    $stmt = $pdo->prepare("SELECT title, content, created_at FROM announcements ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_announcements = $stmt->fetchAll();

} catch (PDOException $e) {
    $error_message = 'Database error occurred while loading dashboard.';
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="fas fa-tachometer-alt me-2"></i>
            Welcome, <?= sanitizeInput($_SESSION['name']) ?>!
        </h1>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4 stagger-animation">
    <?php if (isAdmin()): ?>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?= $total_students ?></h3>
                        <p>Total Students</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?= $total_courses ?></h3>
                        <p>Total Courses</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-book"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?= $total_announcements ?></h3>
                        <p>Announcements</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?= date('Y') ?></h3>
                        <p>Academic Year</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?= $enrolled_courses ?? 0 ?></h3>
                        <p>My Courses</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?= $total_grades ?? 0 ?></h3>
                        <p>Graded Courses</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?= $total_announcements ?></h3>
                        <p>Announcements</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?= ucfirst($_SESSION['role']) ?></h3>
                        <p>Account Type</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- System Overview Section -->
<?php if (isAdmin()): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>System Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="p-3">
                            <i class="fas fa-user-graduate fa-3x text-primary mb-2"></i>
                            <h4><?= $total_students ?></h4>
                            <p class="text-muted">Total Students</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <i class="fas fa-book fa-3x text-success mb-2"></i>
                            <h4><?= $total_courses ?></h4>
                            <p class="text-muted">Available Courses</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <i class="fas fa-bullhorn fa-3x text-warning mb-2"></i>
                            <h4><?= $total_announcements ?></h4>
                            <p class="text-muted">Active Announcements</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <i class="fas fa-calendar-check fa-3x text-info mb-2"></i>
                            <h4><?= date('Y') ?></h4>
                            <p class="text-muted">Current Year</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Content Row -->
<div class="row">
    <!-- Quick Actions -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <?php if (isAdmin()): ?>
                    <div class="d-grid gap-2">
                        <a href="admin_students.php" class="btn btn-outline-primary">
                            <i class="fas fa-users me-2"></i>Manage Students
                        </a>
                        <a href="admin_courses.php" class="btn btn-outline-primary">
                            <i class="fas fa-book-open me-2"></i>Manage Courses
                        </a>
                        <a href="news.php" class="btn btn-outline-primary">
                            <i class="fas fa-bullhorn me-2"></i>View Announcements
                        </a>
                    </div>
                <?php else: ?>
                    <div class="d-grid gap-2">
                        <a href="courses.php" class="btn btn-outline-primary">
                            <i class="fas fa-book me-2"></i>Browse Courses
                        </a>
                        <a href="grades.php" class="btn btn-outline-primary">
                            <i class="fas fa-chart-line me-2"></i>View My Grades
                        </a>
                        <a href="profile.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-edit me-2"></i>Update Profile
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Announcements -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bullhorn me-2"></i>Recent Announcements
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_announcements)): ?>
                    <?php foreach (array_slice($recent_announcements, 0, 3) as $announcement): ?>
                        <div class="mb-3 pb-2 border-bottom">
                            <h6 class="fw-bold"><?= sanitizeInput($announcement['title']) ?></h6>
                            <p class="text-muted small mb-1">
                                <?= substr(sanitizeInput($announcement['content']), 0, 100) ?>...
                            </p>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                <?= date('M j, Y', strtotime($announcement['created_at'])) ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                    <a href="news.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-1"></i>View All Announcements
                    </a>
                <?php else: ?>
                    <p class="text-muted">No announcements available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Student's Recent Grades -->
<?php if (!isAdmin() && !empty($recent_grades)): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>Recent Grades
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Grade</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_grades as $grade): ?>
                                <tr>
                                    <td><strong><?= sanitizeInput($grade['course_code']) ?></strong></td>
                                    <td><?= sanitizeInput($grade['course_name']) ?></td>
                                    <td>
                                        <span class="badge bg-success"><?= sanitizeInput($grade['grade']) ?></span>
                                    </td>
                                    <td>
                                        <small><?= date('M j, Y', strtotime($grade['created_at'])) ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="grades.php" class="btn btn-outline-primary">
                    <i class="fas fa-eye me-1"></i>View All Grades
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<?php require_once 'footer.php'; ?>