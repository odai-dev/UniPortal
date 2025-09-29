<?php
$page_title = 'My Grades';
require_once 'config.php';
require_once 'db.php';

// Require login (before any HTML output)
requireLogin();

require_once 'header.php';

// Students can only view their own grades, admins can view all
$user_id = isAdmin() ? ($_GET['student_id'] ?? null) : $_SESSION['user_id'];

// If admin and no specific student selected, show student list
if (isAdmin() && !$user_id) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE role = 'student' ORDER BY name");
        $stmt->execute();
        $students = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error_message = 'Database error occurred while loading students.';
    }
} else {
    // Get student's grades
    try {
        // Get student info (for admin view)
        $student_info = null;
        if (isAdmin() && $user_id) {
            $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $student_info = $stmt->fetch();
        }
        
        // Get grades with course information
        $stmt = $pdo->prepare("
            SELECT g.grade, g.grade_points, g.created_at,
                   c.course_code, c.course_name, c.instructor
            FROM grades g
            JOIN courses c ON g.course_id = c.id
            WHERE g.user_id = ?
            ORDER BY g.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $grades = $stmt->fetchAll();
        
        // Calculate GPA and statistics
        $total_points = 0;
        $total_courses = 0;
        $grade_distribution = [];
        
        foreach ($grades as $grade) {
            if ($grade['grade_points']) {
                $total_points += $grade['grade_points'];
                $total_courses++;
            }
            
            $grade_letter = $grade['grade'];
            $grade_distribution[$grade_letter] = ($grade_distribution[$grade_letter] ?? 0) + 1;
        }
        
        $gpa = $total_courses > 0 ? round($total_points / $total_courses, 2) : 0;
        
        // Get courses without grades (enrolled but not graded)
        $stmt = $pdo->prepare("
            SELECT c.course_code, c.course_name, c.instructor, e.enrolled_at
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            LEFT JOIN grades g ON g.course_id = c.id AND g.user_id = e.user_id
            WHERE e.user_id = ? AND g.id IS NULL
            ORDER BY e.enrolled_at DESC
        ");
        $stmt->execute([$user_id]);
        $pending_courses = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        $error_message = 'Database error occurred while loading grades.';
    }
}

// Update page title for admin view
if (isAdmin() && isset($student_info)) {
    $page_title = 'Grades - ' . $student_info['name'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>
        <i class="fas fa-chart-line me-2"></i>
        <?php if (isAdmin() && isset($student_info)): ?>
            Grades for <?= sanitizeInput($student_info['name']) ?>
        <?php elseif (isAdmin()): ?>
            Student Grades Management
        <?php else: ?>
            My Grades
        <?php endif; ?>
    </h1>
    
    <?php if (isAdmin() && isset($student_info)): ?>
        <a href="grades.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Student List
        </a>
    <?php endif; ?>
</div>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= $error_message ?>
    </div>
<?php endif; ?>

<?php if (isAdmin() && !$user_id): ?>
    <!-- Student List for Admin -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-users me-2"></i>Select Student to View Grades</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($students)): ?>
                <div class="table-responsive">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-user me-1"></i>
                                        <?= sanitizeInput($student['name']) ?>
                                    </td>
                                    <td><?= sanitizeInput($student['email']) ?></td>
                                    <td>
                                        <a href="grades.php?student_id=<?= $student['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i>View Grades
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-3">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Students Found</h5>
                    <p class="text-muted">There are no students registered in the system.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
<?php else: ?>
    <!-- Grades Display -->
    
    <!-- Student Info (for admin view) -->
    <?php if (isAdmin() && isset($student_info)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-user me-2"></i>Student Information</h5>
                            <p><strong>Name:</strong> <?= sanitizeInput($student_info['name']) ?></p>
                            <p><strong>Email:</strong> <?= sanitizeInput($student_info['email']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-chart-bar me-2"></i>Academic Summary</h5>
                            <p><strong>Total Courses Graded:</strong> <?= count($grades) ?></p>
                            <p><strong>GPA:</strong> <span class="badge bg-primary"><?= $gpa ?></span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- GPA and Statistics -->
    <?php if (!empty($grades)): ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?= $gpa ?></h3>
                        <p>Current GPA</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?= count($grades) ?></h3>
                        <p>Graded Courses</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-book"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?= count($pending_courses) ?></h3>
                        <p>Pending Grades</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?= count($grades) + count($pending_courses) ?></h3>
                        <p>Total Enrolled</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Grades Table -->
    <?php if (!empty($grades)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-list me-2"></i>Grade History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Instructor</th>
                            <th>Grade</th>
                            <th>Points</th>
                            <th>Date Recorded</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades as $grade): ?>
                            <tr>
                                <td><strong><?= sanitizeInput($grade['course_code']) ?></strong></td>
                                <td><?= sanitizeInput($grade['course_name']) ?></td>
                                <td><?= sanitizeInput($grade['instructor']) ?></td>
                                <td>
                                    <span class="badge bg-success fs-6">
                                        <?= sanitizeInput($grade['grade']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($grade['grade_points']): ?>
                                        <span class="text-primary fw-bold"><?= $grade['grade_points'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= date('M j, Y g:i A', strtotime($grade['created_at'])) ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Pending Courses -->
    <?php if (!empty($pending_courses)): ?>
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-clock me-2"></i>Courses Awaiting Grades</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Instructor</th>
                            <th>Enrolled Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_courses as $course): ?>
                            <tr>
                                <td><strong><?= sanitizeInput($course['course_code']) ?></strong></td>
                                <td><?= sanitizeInput($course['course_name']) ?></td>
                                <td><?= sanitizeInput($course['instructor']) ?></td>
                                <td>
                                    <small><?= date('M j, Y', strtotime($course['enrolled_at'])) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-warning">Pending Grade</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- No Grades Message -->
    <?php if (empty($grades) && empty($pending_courses)): ?>
    <div class="text-center py-5">
        <i class="fas fa-chart-line fa-5x text-muted mb-3"></i>
        <h3 class="text-muted">No Grades Available</h3>
        <p class="text-muted">
            <?php if (!isAdmin()): ?>
                You don't have any grades yet. Enroll in courses to see your academic progress here.
            <?php else: ?>
                This student doesn't have any grades recorded.
            <?php endif; ?>
        </p>
        <?php if (!isAdmin()): ?>
            <a href="courses.php" class="btn btn-primary">
                <i class="fas fa-book me-1"></i>Browse Courses
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Grade Distribution Chart -->
    <?php if (!empty($grade_distribution)): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h5><i class="fas fa-chart-pie me-2"></i>Grade Distribution</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <canvas id="gradeChart" height="200"></canvas>
                </div>
                <div class="col-md-6">
                    <div class="mt-3">
                        <?php foreach ($grade_distribution as $grade => $count): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?= sanitizeInput($grade) ?>:</span>
                                <span class="badge bg-primary"><?= $count ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Grade Distribution Chart
    document.addEventListener('DOMContentLoaded', function() {
        const gradeCtx = document.getElementById('gradeChart').getContext('2d');
        new Chart(gradeCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($grade_distribution)) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($grade_distribution)) ?>,
                    backgroundColor: [
                        'rgba(102, 126, 234, 0.8)',
                        'rgba(118, 75, 162, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    });
    </script>
    <?php endif; ?>
    
<?php endif; ?>

<?php require_once 'footer.php'; ?>