<?php
$page_title = 'Announcements';
require_once 'config.php';
require_once 'db.php';

// Require login (before any HTML output)
requireLogin();

require_once 'header.php';

// Get all announcements
try {
    $stmt = $pdo->prepare("SELECT * FROM announcements ORDER BY created_at DESC");
    $stmt->execute();
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = 'Database error occurred while loading announcements.';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-bullhorn me-2"></i>University Announcements</h1>
    <?php if (isAdmin()): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
            <i class="fas fa-plus me-1"></i>Add Announcement
        </button>
    <?php endif; ?>
</div>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= $error_message ?>
    </div>
<?php endif; ?>

<?php if (!empty($announcements)): ?>
    <div class="row">
        <?php foreach ($announcements as $index => $announcement): ?>
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bullhorn me-2 text-primary"></i>
                            <?= sanitizeInput($announcement['title']) ?>
                        </h5>
                        <div class="d-flex align-items-center">
                            <small class="text-muted me-3">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?= date('F j, Y \a\t g:i A', strtotime($announcement['created_at'])) ?>
                            </small>
                            <?php if (isAdmin()): ?>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" 
                                            onclick="editAnnouncement(<?= htmlspecialchars(json_encode($announcement), ENT_QUOTES, 'UTF-8') ?>)"
                                            title="Edit Announcement">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" 
                                            onclick="deleteAnnouncement(<?= $announcement['id'] ?>, '<?= sanitizeInput($announcement['title']) ?>')"
                                            title="Delete Announcement">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="announcement-content">
                            <?= nl2br(sanitizeInput($announcement['content'])) ?>
                        </div>
                        
                        <?php if ($index === 0): ?>
                            <div class="mt-3">
                                <span class="badge bg-success">
                                    <i class="fas fa-star me-1"></i>Latest
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Announcement Statistics -->
    <div class="card mt-4">
        <div class="card-header">
            <h5><i class="fas fa-chart-bar me-2"></i>Announcement Statistics</h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <h4 class="text-primary"><?= count($announcements) ?></h4>
                    <p class="text-muted">Total Announcements</p>
                </div>
                <div class="col-md-3">
                    <h4 class="text-success">
                        <?= count(array_filter($announcements, function($a) { return strtotime($a['created_at']) > strtotime('-7 days'); })) ?>
                    </h4>
                    <p class="text-muted">This Week</p>
                </div>
                <div class="col-md-3">
                    <h4 class="text-warning">
                        <?= count(array_filter($announcements, function($a) { return strtotime($a['created_at']) > strtotime('-30 days'); })) ?>
                    </h4>
                    <p class="text-muted">This Month</p>
                </div>
                <div class="col-md-3">
                    <h4 class="text-info"><?= date('Y') ?></h4>
                    <p class="text-muted">Academic Year</p>
                </div>
            </div>
        </div>
    </div>
    
<?php else: ?>
    <div class="text-center py-5">
        <i class="fas fa-bullhorn fa-5x text-muted mb-3"></i>
        <h3 class="text-muted">No Announcements</h3>
        <p class="text-muted">There are currently no announcements available.</p>
        <?php if (isAdmin()): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                <i class="fas fa-plus me-1"></i>Add First Announcement
            </button>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (isAdmin()): ?>
<!-- Add Announcement Modal -->
<div class="modal fade" id="addAnnouncementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_announcement">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-3">
                        <label for="add_title" class="form-label">Announcement Title</label>
                        <input type="text" class="form-control" id="add_title" name="title" 
                               placeholder="Enter announcement title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_content" class="form-label">Content</label>
                        <textarea class="form-control" id="add_content" name="content" rows="6" 
                                  placeholder="Enter announcement content" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-bullhorn me-1"></i>Publish Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div class="modal fade" id="editAnnouncementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_announcement">
                    <input type="hidden" name="announcement_id" id="edit_announcement_id">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Announcement Title</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_content" class="form-label">Content</label>
                        <textarea class="form-control" id="edit_content" name="content" rows="6" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Announcement Modal -->
<div class="modal fade" id="deleteAnnouncementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this announcement?</p>
                <p><strong id="delete_announcement_title"></strong></p>
                <p class="text-danger"><i class="fas fa-warning me-1"></i>This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete_announcement">
                    <input type="hidden" name="announcement_id" id="delete_announcement_id">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Delete Announcement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editAnnouncement(announcement) {
    document.getElementById('edit_announcement_id').value = announcement.id;
    document.getElementById('edit_title').value = announcement.title;
    document.getElementById('edit_content').value = announcement.content;
    
    new bootstrap.Modal(document.getElementById('editAnnouncementModal')).show();
}

function deleteAnnouncement(announcementId, announcementTitle) {
    document.getElementById('delete_announcement_id').value = announcementId;
    document.getElementById('delete_announcement_title').textContent = announcementTitle;
    
    new bootstrap.Modal(document.getElementById('deleteAnnouncementModal')).show();
}
</script>

<?php
// Handle form submissions
if ($_POST) {
    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
    
    if ($action === 'add_announcement') {
        $title = sanitizeInput($_POST['title'] ?? '');
        $content = sanitizeInput($_POST['content'] ?? '');
        
        if (!empty($title) && !empty($content)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO announcements (title, content) VALUES (?, ?)");
                $stmt->execute([$title, $content]);
                header('Location: news.php?success=1');
                exit();
            } catch (PDOException $e) {
                $error_message = 'Error adding announcement. Please try again.';
            }
        }
    } elseif ($action === 'edit_announcement') {
        $announcement_id = $_POST['announcement_id'] ?? '';
        $title = sanitizeInput($_POST['title'] ?? '');
        $content = sanitizeInput($_POST['content'] ?? '');
        
        if (is_numeric($announcement_id) && !empty($title) && !empty($content)) {
            try {
                $stmt = $pdo->prepare("UPDATE announcements SET title = ?, content = ? WHERE id = ?");
                $stmt->execute([$title, $content, $announcement_id]);
                header('Location: news.php?success=2');
                exit();
            } catch (PDOException $e) {
                $error_message = 'Error updating announcement. Please try again.';
            }
        }
    } elseif ($action === 'delete_announcement') {
        $announcement_id = $_POST['announcement_id'] ?? '';
        
        if (is_numeric($announcement_id)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
                $stmt->execute([$announcement_id]);
                header('Location: news.php?success=3');
                exit();
            } catch (PDOException $e) {
                $error_message = 'Error deleting announcement. Please try again.';
            }
        }
    }
    }
}
?>

<?php endif; ?>

<?php require_once 'footer.php'; ?>