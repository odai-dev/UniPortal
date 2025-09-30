<?php
require_once 'config.php';
require_once 'db.php';

// Require admin access
requireAdmin();

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        $_SESSION['upload_error'] = 'Invalid security token. Please refresh and try again.';
        header('Location: materials.php');
        exit();
    }
    
    $course_id = $_POST['course_id'] ?? null;
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    // Validate inputs
    if (!$course_id || !is_numeric($course_id)) {
        $_SESSION['upload_error'] = 'Invalid course selection.';
        header('Location: materials.php');
        exit();
    }
    
    if (empty($title)) {
        $_SESSION['upload_error'] = 'Title is required.';
        header('Location: materials.php?course_id=' . $course_id);
        exit();
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['upload_error'] = 'Please select a file to upload.';
        header('Location: materials.php?course_id=' . $course_id);
        exit();
    }
    
    $file = $_FILES['file'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    
    // Check file size (max 10MB)
    $max_size = 10 * 1024 * 1024; // 10MB in bytes
    if ($file_size > $max_size) {
        $_SESSION['upload_error'] = 'File size exceeds 10MB limit.';
        header('Location: materials.php?course_id=' . $course_id);
        exit();
    }
    
    // Get file extension
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Allowed file types with MIME mapping
    $allowed_types = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'txt' => ['text/plain'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'rar' => ['application/x-rar-compressed', 'application/vnd.rar']
    ];
    
    if (!isset($allowed_types[$file_ext])) {
        $_SESSION['upload_error'] = 'File type not allowed. Allowed types: PDF, Word, PowerPoint, Excel, Text, ZIP, RAR.';
        header('Location: materials.php?course_id=' . $course_id);
        exit();
    }
    
    // Verify course exists
    try {
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch();
        
        if (!$course) {
            $_SESSION['upload_error'] = 'Course not found.';
            header('Location: materials.php');
            exit();
        }
        
        // Create unique filename
        $unique_name = uniqid() . '_' . time() . '.' . $file_ext;
        $upload_dir = 'uploads/course_materials/';
        $upload_path = $upload_dir . $unique_name;
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($file_tmp, $upload_path)) {
            // Get file MIME type and validate
            $file_type = mime_content_type($upload_path);
            
            // Validate MIME type matches extension
            if (!in_array($file_type, $allowed_types[$file_ext])) {
                // Delete uploaded file if MIME doesn't match
                unlink($upload_path);
                $_SESSION['upload_error'] = 'File type mismatch. The file content does not match its extension.';
                header('Location: materials.php?course_id=' . $course_id);
                exit();
            }
            
            // Insert into database
            $stmt = $pdo->prepare("
                INSERT INTO course_materials 
                (course_id, title, description, file_name, file_path, file_size, file_type, uploaded_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $course_id,
                $title,
                $description,
                $file_name,
                $upload_path,
                $file_size,
                $file_type,
                $_SESSION['user_id']
            ]);
            
            $_SESSION['upload_success'] = 'Material uploaded successfully!';
            header('Location: materials.php?course_id=' . $course_id);
            exit();
        } else {
            $_SESSION['upload_error'] = 'Failed to upload file. Please try again.';
            header('Location: materials.php?course_id=' . $course_id);
            exit();
        }
        
    } catch (PDOException $e) {
        error_log('Upload material error: ' . $e->getMessage());
        $_SESSION['upload_error'] = 'Database error occurred. Please try again.';
        header('Location: materials.php?course_id=' . $course_id);
        exit();
    }
} else {
    header('Location: materials.php');
    exit();
}
?>
