<?php
$page_title = 'Course Materials';
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

// Get all courses for filtering
try {
    $stmt = $pdo->prepare("SELECT id, course_code, course_name FROM courses ORDER BY course_code");
    $stmt->execute();
    $courses = $stmt->fetchAll();
    
    // Initialize variables
    $materials = [];
    $current_course = null;
    
    // Get selected course
    $selected_course_id = $_GET['course_id'] ?? null;
    
    // Get materials for selected course or all materials
    if ($selected_course_id && is_numeric($selected_course_id)) {
        // Check if student is enrolled in this course (unless admin)
        if (!isAdmin()) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND course_id = ?");
            $stmt->execute([$_SESSION['user_id'], $selected_course_id]);
            $is_enrolled = $stmt->fetchColumn() > 0;
            
            if (!$is_enrolled) {
                $error_message = 'You must be enrolled in a course to view its materials.';
                $selected_course_id = null;
            }
        }
        
        if ($selected_course_id) {
            $stmt = $pdo->prepare("
                SELECT cm.*, c.course_code, c.course_name, u.name as uploaded_by_name
                FROM course_materials cm
                JOIN courses c ON cm.course_id = c.id
                JOIN users u ON cm.uploaded_by = u.id
                WHERE cm.course_id = ?
                ORDER BY cm.created_at DESC
            ");
            $stmt->execute([$selected_course_id]);
            $materials = $stmt->fetchAll();
            
            // Get course info
            $stmt = $pdo->prepare("SELECT course_code, course_name FROM courses WHERE id = ?");
            $stmt->execute([$selected_course_id]);
            $current_course = $stmt->fetch();
        }
    } else {
        // Show all materials for enrolled courses (or all if admin)
        if (isAdmin()) {
            $stmt = $pdo->prepare("
                SELECT cm.*, c.course_code, c.course_name, u.name as uploaded_by_name
                FROM course_materials cm
                JOIN courses c ON cm.course_id = c.id
                JOIN users u ON cm.uploaded_by = u.id
                ORDER BY cm.created_at DESC
            ");
            $stmt->execute();
            $materials = $stmt->fetchAll();
        } else {
            $stmt = $pdo->prepare("
                SELECT cm.*, c.course_code, c.course_name, u.name as uploaded_by_name
                FROM course_materials cm
                JOIN courses c ON cm.course_id = c.id
                JOIN users u ON cm.uploaded_by = u.id
                JOIN enrollments e ON e.course_id = cm.course_id
                WHERE e.user_id = ?
                ORDER BY cm.created_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $materials = $stmt->fetchAll();
        }
    }
} catch (PDOException $e) {
    $error_message = 'Database error occurred while loading materials.';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-folder-open me-2"></i>Course Materials</h1>
    <?php if (isAdmin()): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadMaterialModal">
            <i class="fas fa-upload me-1"></i>Upload Material
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

<!-- Course Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="materials.php" class="row g-3">
            <div class="col-md-10">
                <label for="course_id" class="form-label">Filter by Course:</label>
                <select name="course_id" id="course_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Courses <?= !isAdmin() ? '(Enrolled)' : '' ?></option>
                    <?php foreach ($courses as $course): ?>
                        <?php
                        // For students, only show enrolled courses
                        if (!isAdmin()) {
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND course_id = ?");
                            $stmt->execute([$_SESSION['user_id'], $course['id']]);
                            $is_enrolled = $stmt->fetchColumn() > 0;
                            if (!$is_enrolled) continue;
                        }
                        ?>
                        <option value="<?= $course['id'] ?>" <?= $selected_course_id == $course['id'] ? 'selected' : '' ?>>
                            <?= sanitizeInput($course['course_code']) ?> - <?= sanitizeInput($course['course_name']) ?>
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

<?php if (isset($current_course)): ?>
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        Showing materials for: <strong><?= sanitizeInput($current_course['course_code']) ?> - <?= sanitizeInput($current_course['course_name']) ?></strong>
    </div>
<?php endif; ?>

<!-- Materials List -->
<?php if (!empty($materials)): ?>
    <div class="row">
        <?php foreach ($materials as $material): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-file me-2"></i>
                            <?= sanitizeInput($material['title']) ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!isset($current_course)): ?>
                            <p class="text-muted mb-2">
                                <i class="fas fa-book me-1"></i>
                                <strong><?= sanitizeInput($material['course_code']) ?>:</strong> 
                                <?= sanitizeInput($material['course_name']) ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($material['description'])): ?>
                            <p class="mb-2"><?= sanitizeInput($material['description']) ?></p>
                        <?php endif; ?>
                        
                        <p class="text-muted mb-2">
                            <i class="fas fa-file-alt me-1"></i>
                            <strong>File:</strong> <?= sanitizeInput($material['file_name']) ?>
                        </p>
                        
                        <p class="text-muted mb-2">
                            <i class="fas fa-hdd me-1"></i>
                            <strong>Size:</strong> <?= number_format($material['file_size'] / 1024, 2) ?> KB
                        </p>
                        
                        <p class="text-muted mb-2">
                            <i class="fas fa-user me-1"></i>
                            <strong>Uploaded by:</strong> <?= sanitizeInput($material['uploaded_by_name']) ?>
                        </p>
                        
                        <p class="text-muted mb-3">
                            <i class="fas fa-calendar me-1"></i>
                            <?= date('F j, Y', strtotime($material['created_at'])) ?>
                        </p>
                        
                        <div class="d-flex gap-2">
                            <a href="download_material.php?id=<?= $material['id'] ?>" class="btn btn-primary btn-sm flex-fill">
                                <i class="fas fa-download me-1"></i>Download
                            </a>
                            <?php if (isAdmin()): ?>
                                <button class="btn btn-danger btn-sm" onclick="deleteMaterial(<?= $material['id'] ?>, '<?= sanitizeInput($material['title']) ?>')">
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
        <?php if ($selected_course_id): ?>
            No materials available for this course yet.
        <?php else: ?>
            No materials available. <?= !isAdmin() ? 'Enroll in courses to access materials.' : '' ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (isAdmin()): ?>
<!-- Upload Material Modal -->
<div class="modal fade" id="uploadMaterialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Course Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="upload_material.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="material_course_id" class="form-label">Course *</label>
                        <select name="course_id" id="material_course_id" class="form-select" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['id'] ?>" <?= $selected_course_id == $course['id'] ? 'selected' : '' ?>>
                                    <?= sanitizeInput($course['course_code']) ?> - <?= sanitizeInput($course['course_name']) ?>
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
function deleteMaterial(id, title) {
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
                alert(data.message || 'Failed to delete material');
            }
        })
        .catch(error => {
            alert('Error occurred while deleting material');
        });
    }
}
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
