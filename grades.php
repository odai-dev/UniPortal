<?php
$page_title = 'My Progress';
require_once 'config.php';
require_once 'db.php';

// Require login (before any HTML output)
requireLogin();

require_once 'header.php';

// Members can only view their own progress, admins can view all
$user_id = isAdmin() ? ($_GET['student_id'] ?? null) : $_SESSION['user_id'];

// If admin and no specific member selected, show member list
if (isAdmin() && !$user_id) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE role = 'member' ORDER BY name");
        $stmt->execute();
        $members = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error_message = 'Database error occurred while loading members.';
    }
} else {
    // Get member's progress
    try {
        // Get member info (for admin view)
        $member_info = null;
        if (isAdmin() && $user_id) {
            $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $member_info = $stmt->fetch();
        }
        
        // Get progress with class information
        $stmt = $pdo->prepare("
            SELECT p.performance_score, p.score_points, p.created_at,
                   c.class_code, c.class_name, c.trainer
            FROM progress p
            JOIN classes c ON p.class_id = c.id
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $progress_records = $stmt->fetchAll();
        
        // Calculate average score and statistics
        $total_points = 0;
        $total_classes = 0;
        $score_distribution = [];
        
        foreach ($progress_records as $progress) {
            if ($progress['score_points']) {
                $total_points += $progress['score_points'];
                $total_classes++;
            }
            
            $score_rating = $progress['performance_score'];
            $score_distribution[$score_rating] = ($score_distribution[$score_rating] ?? 0) + 1;
        }
        
        $average_score = $total_classes > 0 ? round($total_points / $total_classes, 2) : 0;
        
        // Get classes without progress (registered but not tracked)
        $stmt = $pdo->prepare("
            SELECT c.class_code, c.class_name, c.trainer, m.enrolled_at
            FROM memberships m
            JOIN classes c ON m.class_id = c.id
            LEFT JOIN progress p ON p.class_id = c.id AND p.user_id = m.user_id
            WHERE m.user_id = ? AND p.id IS NULL
            ORDER BY m.enrolled_at DESC
        ");
        $stmt->execute([$user_id]);
        $pending_classes = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        $error_message = 'Database error occurred while loading progress.';
    }
}

// Update page title for admin view
if (isAdmin() && isset($member_info)) {
    $page_title = 'Progress - ' . $member_info['name'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>
        <i class="fas fa-chart-line me-2"></i>
        <?php if (isAdmin() && isset($member_info)): ?>
            Progress for <?= sanitizeInput($member_info['name']) ?>
        <?php elseif (isAdmin()): ?>
            Member Progress Management
        <?php else: ?>
            My Progress
        <?php endif; ?>
    </h1>
    
    <?php if (isAdmin() && isset($member_info)): ?>
        <a href="grades.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Member List
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
    <!-- Member List for Admin -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-users me-2"></i>Select Member to View Progress</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($members)): ?>
                <div class="table-responsive">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>Member Name</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-user me-1"></i>
                                        <?= sanitizeInput($member['name']) ?>
                                    </td>
                                    <td><?= sanitizeInput($member['email']) ?></td>
                                    <td>
                                        <a href="grades.php?student_id=<?= $member['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i>View Progress
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
                    <h5 class="text-muted">No Members Found</h5>
                    <p class="text-muted">There are no members registered in the system.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
<?php else: ?>
    <!-- Progress Display -->
    
    <!-- Member Info (for admin view) -->
    <?php if (isAdmin() && isset($member_info)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-user me-2"></i>Member Information</h5>
                            <p><strong>Name:</strong> <?= sanitizeInput($member_info['name']) ?></p>
                            <p><strong>Email:</strong> <?= sanitizeInput($member_info['email']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-chart-bar me-2"></i>Fitness Summary</h5>
                            <p><strong>Total Classes Tracked:</strong> <?= count($progress_records) ?></p>
                            <p><strong>Average Score:</strong> <span class="badge bg-primary"><?= $average_score ?></span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Average Score and Statistics -->
    <?php if (!empty($progress_records)): ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?= $average_score ?></h3>
                        <p>Average Score</p>
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
                        <h3><?= count($progress_records) ?></h3>
                        <p>Tracked Classes</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><?= count($pending_classes) ?></h3>
                        <p>Pending Progress</p>
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
                        <h3><?= count($progress_records) + count($pending_classes) ?></h3>
                        <p>Total Registered</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Progress Table -->
    <?php if (!empty($progress_records)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-list me-2"></i>Progress History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>Class Code</th>
                            <th>Class Name</th>
                            <th>Trainer</th>
                            <th>Performance</th>
                            <th>Points</th>
                            <th>Date Recorded</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($progress_records as $progress): ?>
                            <tr>
                                <td><strong><?= sanitizeInput($progress['class_code']) ?></strong></td>
                                <td><?= sanitizeInput($progress['class_name']) ?></td>
                                <td><?= sanitizeInput($progress['trainer']) ?></td>
                                <td>
                                    <span class="badge bg-success fs-6">
                                        <?= sanitizeInput($progress['performance_score']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($progress['score_points']): ?>
                                        <span class="text-primary fw-bold"><?= $progress['score_points'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= date('M j, Y g:i A', strtotime($progress['created_at'])) ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Pending Classes -->
    <?php if (!empty($pending_classes)): ?>
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-clock me-2"></i>Classes Awaiting Progress Tracking</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Class Code</th>
                            <th>Class Name</th>
                            <th>Trainer</th>
                            <th>Registered Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_classes as $class): ?>
                            <tr>
                                <td><strong><?= sanitizeInput($class['class_code']) ?></strong></td>
                                <td><?= sanitizeInput($class['class_name']) ?></td>
                                <td><?= sanitizeInput($class['trainer']) ?></td>
                                <td>
                                    <small><?= date('M j, Y', strtotime($class['enrolled_at'])) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-warning">Pending Progress</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- No Progress Message -->
    <?php if (empty($progress_records) && empty($pending_classes)): ?>
    <div class="text-center py-5">
        <i class="fas fa-chart-line fa-5x text-muted mb-3"></i>
        <h3 class="text-muted">No Progress Available</h3>
        <p class="text-muted">
            <?php if (!isAdmin()): ?>
                You don't have any progress tracked yet. Register for classes to see your fitness progress here.
            <?php else: ?>
                This member doesn't have any progress recorded.
            <?php endif; ?>
        </p>
        <?php if (!isAdmin()): ?>
            <a href="courses.php" class="btn btn-primary">
                <i class="fas fa-dumbbell me-1"></i>Browse Classes
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Performance Distribution Chart -->
    <?php if (!empty($score_distribution)): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h5><i class="fas fa-chart-pie me-2"></i>Performance Distribution</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <canvas id="gradeChart" height="200"></canvas>
                </div>
                <div class="col-md-6">
                    <div class="mt-3">
                        <?php foreach ($score_distribution as $score => $count): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?= sanitizeInput($score) ?>:</span>
                                <span class="badge bg-primary"><?= $count ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Performance Distribution Chart
    document.addEventListener('DOMContentLoaded', function() {
        const gradeCtx = document.getElementById('gradeChart').getContext('2d');
        new Chart(gradeCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($score_distribution)) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($score_distribution)) ?>,
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
