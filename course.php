<?php
$page_title = 'Class Details';
require_once 'config.php';
require_once 'db.php';

// Require login (before any HTML output)
requireLogin();

require_once 'header.php';

$class_id = $_GET['id'] ?? null;

if (!$class_id || !is_numeric($class_id)) {
    header('Location: courses.php');
    exit();
}

// Get class details
try {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch();
    
    if (!$class) {
        header('Location: courses.php');
        exit();
    }
    
    // Check if member is registered in this class
    $is_registered = false;
    $membership_count = 0;
    $member_progress = null;
    
    if (!isAdmin()) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM memberships WHERE user_id = ? AND class_id = ?");
        $stmt->execute([$_SESSION['user_id'], $class_id]);
        $is_registered = $stmt->fetchColumn() > 0;
        
        // Get member's progress for this class if available
        $stmt = $pdo->prepare("SELECT performance_score, score_points FROM progress WHERE user_id = ? AND class_id = ?");
        $stmt->execute([$_SESSION['user_id'], $class_id]);
        $member_progress = $stmt->fetch();
    }
    
    // Get membership count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM memberships WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $membership_count = $stmt->fetchColumn();
    
    // Get registered members (for admin or registered members)
    $registered_members = [];
    if (isAdmin() || $is_registered) {
        $stmt = $pdo->prepare("
            SELECT u.name, u.email, m.enrolled_at,
                   p.performance_score, p.score_points
            FROM memberships m
            JOIN users u ON m.user_id = u.id
            LEFT JOIN progress p ON p.user_id = u.id AND p.class_id = m.class_id
            WHERE m.class_id = ?
            ORDER BY m.enrolled_at DESC
        ");
        $stmt->execute([$class_id]);
        $registered_members = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    $error_message = 'Database error occurred while loading class details.';
}

$page_title = isset($class) ? $class['class_name'] : 'Class Details';
?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= $error_message ?>
    </div>
<?php endif; ?>

<?php if (isset($class)): ?>
    <!-- Class Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="course-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="course-code"><?= sanitizeInput($class['class_code']) ?></div>
                            <div class="course-name"><?= sanitizeInput($class['class_name']) ?></div>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if (!isAdmin()): ?>
                                <?php if ($is_registered): ?>
                                    <span class="badge bg-success fs-6">
                                        <i class="fas fa-check me-1"></i>Registered
                                    </span>
                                <?php else: ?>
                                    <button class="btn btn-outline-primary" onclick="registerInClass()">
                                        <i class="fas fa-plus me-1"></i>Register
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="admin_courses.php?edit=<?= $class['id'] ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-edit me-1"></i>Edit Class
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Class Information -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle me-2"></i>Class Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Class Code:</strong></div>
                        <div class="col-sm-9"><?= sanitizeInput($class['class_code']) ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Class Name:</strong></div>
                        <div class="col-sm-9"><?= sanitizeInput($class['class_name']) ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Trainer:</strong></div>
                        <div class="col-sm-9">
                            <i class="fas fa-user-tie me-1"></i>
                            <?= sanitizeInput($class['trainer']) ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Description:</strong></div>
                        <div class="col-sm-9"><?= nl2br(sanitizeInput($class['description'])) ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Created:</strong></div>
                        <div class="col-sm-9">
                            <i class="fas fa-calendar-alt me-1"></i>
                            <?= date('F j, Y', strtotime($class['created_at'])) ?>
                        </div>
                    </div>
                    
                    <!-- Member's Progress (if registered and tracked) -->
                    <?php if (!isAdmin() && $member_progress): ?>
                    <div class="row">
                        <div class="col-sm-3"><strong>Your Performance:</strong></div>
                        <div class="col-sm-9">
                            <span class="badge bg-success fs-6">
                                <?= sanitizeInput($member_progress['performance_score']) ?>
                            </span>
                            <?php if ($member_progress['score_points']): ?>
                                <small class="text-muted ms-2">
                                    (<?= $member_progress['score_points'] ?> points)
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
                    <h5><i class="fas fa-chart-bar me-2"></i>Class Statistics</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <h4 class="text-primary"><?= $membership_count ?></h4>
                        <p class="text-muted mb-0">Registered Members</p>
                    </div>
                    
                    <?php if (isAdmin()): ?>
                    <div class="mb-3">
                        <h4 class="text-success"><?= count(array_filter($registered_members, function($m) { return !empty($m['performance_score']); })) ?></h4>
                        <p class="text-muted mb-0">Tracked Progress</p>
                    </div>
                    <?php endif; ?>
                    
                    <div>
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            Current Year <?= date('Y') ?>
                        </small>
                    </div>
                </div>
            </div>
            
            <?php if (!isAdmin() && $is_registered): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-tasks me-2"></i>Class Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="grades.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-chart-line me-1"></i>View My Progress
                        </a>
                        <a href="courses.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-dumbbell me-1"></i>Browse All Classes
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Registered Members (visible to admin or registered members) -->
    <?php if ((isAdmin() || $is_registered) && !empty($registered_members)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-users me-2"></i>Registered Members</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>Member Name</th>
                                    <th>Email</th>
                                    <th>Registered Date</th>
                                    <th>Performance</th>
                                    <?php if (isAdmin()): ?>
                                    <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registered_members as $member): ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-user me-1"></i>
                                            <?= sanitizeInput($member['name']) ?>
                                        </td>
                                        <td><?= sanitizeInput($member['email']) ?></td>
                                        <td>
                                            <small><?= date('M j, Y', strtotime($member['enrolled_at'])) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($member['performance_score']): ?>
                                                <span class="badge bg-success">
                                                    <?= sanitizeInput($member['performance_score']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Not Tracked</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if (isAdmin()): ?>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="manageProgress('<?= $member['email'] ?>')">
                                                <i class="fas fa-edit me-1"></i>Track Progress
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
                <i class="fas fa-arrow-left me-1"></i>Back to Classes
            </a>
        </div>
    </div>

<?php endif; ?>

<script>
function registerInClass() {
    if (confirm('Are you sure you want to register for this class?')) {
        fetch('enroll.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'course_id=<?= $class_id ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Registration failed. Please try again.');
            }
        })
        .catch(error => {
            alert('An error occurred. Please try again.');
        });
    }
}

function manageProgress(memberEmail) {
    alert('Progress tracking for: ' + memberEmail + '\n\nThis feature would be implemented in the admin class management system.');
}
</script>

<?php require_once 'footer.php'; ?>
