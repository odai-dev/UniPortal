<?php
require_once 'config.php';
require_once 'db.php';

requireLogin();

$material_id = $_GET['id'] ?? null;

if (!$material_id || !is_numeric($material_id)) {
    header('Location: materials.php');
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT cm.*, c.id as course_id
        FROM course_materials cm
        JOIN courses c ON cm.course_id = c.id
        WHERE cm.id = ?
    ");
    $stmt->execute([$material_id]);
    $material = $stmt->fetch();
    
    if (!$material) {
        $_SESSION['download_error'] = 'Material not found.';
        header('Location: materials.php');
        exit();
    }
    
    if (!isAdmin()) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$_SESSION['user_id'], $material['course_id']]);
        $is_enrolled = $stmt->fetchColumn() > 0;
        
        if (!$is_enrolled) {
            $_SESSION['download_error'] = 'You must be enrolled in the course to download materials.';
            header('Location: materials.php');
            exit();
        }
    }
    
    if (!file_exists($material['file_path'])) {
        $_SESSION['download_error'] = 'File not found on server.';
        header('Location: materials.php');
        exit();
    }
    
    $safe_filename = basename($material['file_name']);
    $safe_filename = preg_replace('/[^\w\s\.\-]/', '', $safe_filename);
    
    $actual_file_size = filesize($material['file_path']);
    
    $file_ext = strtolower(pathinfo($material['file_name'], PATHINFO_EXTENSION));
    $mime_whitelist = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'txt' => 'text/plain',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed'
    ];
    
    $content_type = $mime_whitelist[$file_ext] ?? 'application/octet-stream';
    
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . $safe_filename . '"');
    header('Content-Length: ' . $actual_file_size);
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    readfile($material['file_path']);
    exit();
    
} catch (PDOException $e) {
    error_log('Download material error: ' . $e->getMessage());
    $_SESSION['download_error'] = 'Database error occurred.';
    header('Location: materials.php');
    exit();
}
?>
