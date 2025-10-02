<?php
$page_title = 'Workout Resources';
require_once 'config.php';
require_once 'db.php';

// Require login (before any HTML output)
requireLogin();

require_once 'header.php';

// Handle session messages
$success_message = '';
$error_message = '';

if (isset($_SESSION['upload_success'])) {
    $success_message = $_SESSION['upload_success'];
    unset($_SESSION['upload_success']);
}

if (isset($_SESSION['upload_error'])) {
    $error_message = $_SESSION['upload_error'];
    unset($_SESSION['upload_error']);
}

if (isset($_SESSION['download_error'])) {
    $error_message = $_SESSION['download_error'];
    unset($_SESSION['download_error']);
}

// Get all classes for filtering
try {
    $stmt = $pdo->prepare("SELECT id, class_code, class_name FROM classes ORDER BY class_code");
    $stmt->execute();
    $classes = $stmt->fetchAll();
    
    // Initialize variables
    $resources = [];
    $current_class = null;
    
    // Get selected class
    $selected_class_id = $_GET['course_id'] ?? null;
    
    // Get resources for selected class or all resources
    if ($selected_class_id && is_numeric($selected_class_id)) {
        // Check if member is registered in this class (unless admin)
        if (!isAdmin()) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM memberships WHERE user_id = ? AND class_id = ?");
            $stmt->execute([$_SESSION['user_id'], $selected_class_id]);
            $is_registered = $stmt->fetchColumn() > 0;
            
            if (!$is_registered) {
                $error_message = 'You must be registered for a class to view its resources.';
                $selected_class_id = null;
            }
        }
        
        if ($selected_class_id) {
            $stmt = $pdo->prepare("
                SELECT cm.*, c.class_code, c.class_name, u.name as uploaded_by_name
                FROM course_materials cm
                JOIN classes c ON cm.course_id = c.id
                JOIN users u ON cm.uploaded_by = u.id
                WHERE cm.course_id = ?
                ORDER BY cm.created_at DESC
            ");
            $stmt->execute([$selected_class_id]);
            $resources = $stmt->fetchAll();
            
            // Get class info
            $stmt = $pdo->prepare("SELECT class_code, class_name FROM classes WHERE id = ?");
            $stmt->execute([$selected_class_id]);
            $current_class = $stmt->fetch();
        }
    } else {
        // Show all resources for registered classes (or all if admin)
        if (isAdmin()) {
            $stmt = $pdo->prepare("
                SELECT cm.*, c.class_code, c.class_name, u.name as uploaded_by_name
                FROM course_materials cm
                JOIN classes c ON cm.course_id = c.id
                JOIN users u ON cm.uploaded_by = u.id
                ORDER BY cm.created_at DESC
            ");
            $stmt->execute();
            $resources = $stmt->fetchAll();
        } else {
            $stmt = $pdo->prepare("
                SELECT cm.*, c.class_code, c.class_name, u.name as uploaded_by_name
                FROM course_materials cm
                JOIN classes c ON cm.course_id = c.id
                JOIN users u ON cm.uploaded_by = u.id
                JOIN memberships m ON m.class_id = cm.course_id
                WHERE m.user_id = ?
                ORDER BY cm.created_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $resources = $stmt->fetchAll();
        }
    }
} catch (PDOException $e) {
    $error_message = 'Database error occurred while loading resources.';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-folder-open me-2"></i>Workout Resources & Plans</h1>
    <?php if (isAdmin()): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadMaterialModal">
            <i class="fas fa-upload me-1"></i>Upload Resource
        </button>
    <?php endif; ?>
</div>

<?php if (isset($error_message) && !empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= $error_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($success_message) && !empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= $success_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Class Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="materials.php" class="row g-3">
            <div class="col-md-10">
                <label for="course_id" class="form-label">Filter by Class:</label>
                <select name="course_id" id="course_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Classes <?= !isAdmin() ? '(Registered)' : '' ?></option>
                    <?php foreach ($classes as $class): ?>
                        <?php
                        // For members, only show registered classes
                        if (!isAdmin()) {
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM memberships WHERE user_id = ? AND class_id = ?");
                            $stmt->execute([$_SESSION['user_id'], $class['id']]);
                            $is_registered = $stmt->fetchColumn() > 0;
                            if (!$is_registered) continue;
                        }
                        ?>
                        <option value="<?= $class['id'] ?>" <?= $selected_class_id == $class['id'] ? 'selected' : '' ?>>
                            <?= sanitizeInput($class['class_code']) ?> - <?= sanitizeInput($class['class_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label d-block">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (isset($current_class)): ?>
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        Showing resources for: <strong><?= sanitizeInput($current_class['class_code']) ?> - <?= sanitizeInput($current_class['class_name']) ?></strong>
    </div>
<?php endif; ?>

<!-- Resources List -->
<?php if (!empty($resources)): ?>
    <div class="row">
        <?php foreach ($resources as $resource): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-file me-2"></i>
                            <?= sanitizeInput($resource['title']) ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!isset($current_class)): ?>
                            <p class="text-muted mb-2">
                                <i class="fas fa-dumbbell me-1"></i>
                                <strong><?= sanitizeInput($resource['class_code']) ?>:</strong> 
                                <?= sanitizeInput($resource['class_name']) ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($resource['description'])): ?>
                            <p class="mb-2"><?= sanitizeInput($resource['description']) ?></p>
                        <?php endif; ?>
                        
                        <p class="text-muted mb-2">
                            <i class="fas fa-file-alt me-1"></i>
                            <strong>File:</strong> <?= sanitizeInput($resource['file_name']) ?>
                        </p>
                        
                        <p class="text-muted mb-2">
                            <i class="fas fa-hdd me-1"></i>
                            <strong>Size:</strong> <?= number_format($resource['file_size'] / 1024, 2) ?> KB
                        </p>
                        
                        <p class="text-muted mb-2">
                            <i class="fas fa-user me-1"></i>
                            <strong>Uploaded by:</strong> <?= sanitizeInput($resource['uploaded_by_name']) ?>
                        </p>
                        
                        <p class="text-muted mb-3">
                            <i class="fas fa-calendar me-1"></i>
                            <?= date('F j, Y', strtotime($resource['created_at'])) ?>
                        </p>
                        
                        <div class="d-flex gap-2">
                            <a href="download_material.php?id=<?= $resource['id'] ?>" class="btn btn-primary btn-sm flex-fill">
                                <i class="fas fa-download me-1"></i>Download
                            </a>
                            <?php if (isAdmin()): ?>
                                <button class="btn btn-danger btn-sm" onclick="deleteResource(<?= $resource['id'] ?>, '<?= sanitizeInput($resource['title']) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info" role="alert">
        <i class="fas fa-info-circle me-2"></i>
        <?php if ($selected_class_id): ?>
            No resources available for this class yet.
        <?php else: ?>
            No resources available. <?= !isAdmin() ? 'Register for classes to access workout plans and resources.' : '' ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (isAdmin()): ?>
<!-- Upload Resource Modal -->
<div class="modal fade" id="uploadMaterialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Workout Resource</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="upload_material.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="material_course_id" class="form-label">Class *</label>
                        <select name="course_id" id="material_course_id" class="form-select" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>" <?= $selected_class_id == $class['id'] ? 'selected' : '' ?>>
                                    <?= sanitizeInput($class['class_code']) ?> - <?= sanitizeInput($class['class_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="material_title" class="form-label">Title *</label>
                        <input type="text" name="title" id="material_title" class="form-control" required maxlength="255">
                    </div>
                    
                    <div class="mb-3">
                        <label for="material_description" class="form-label">Description</label>
                        <textarea name="description" id="material_description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="material_file" class="form-label">File * (Max 10MB)</label>
                        <input type="file" name="file" id="material_file" class="form-control" required 
                               accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar">
                        <div class="form-text">
                            Allowed: PDF, Word, PowerPoint, Excel, Text, ZIP, RAR
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i>Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteResource(id, title) {
    if (confirm(`Are you sure you want to delete "${title}"?`)) {
        const formData = new FormData();
        formData.append('material_id', id);
        formData.append('csrf_token', '<?= generateCSRFToken() ?>');
        
        fetch('delete_material.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to delete resource');
            }
        })
        .catch(error => {
            alert('Error occurred while deleting resource');
        });
    }
}
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
