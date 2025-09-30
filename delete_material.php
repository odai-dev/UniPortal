<?php
require_once 'config.php';
require_once 'db.php';

// Require admin access
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Validate CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit();
}

$material_id = $_POST['material_id'] ?? null;

if (!$material_id || !is_numeric($material_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid material ID']);
    exit();
}

try {
    // Get material details
    $stmt = $pdo->prepare("SELECT * FROM course_materials WHERE id = ?");
    $stmt->execute([$material_id]);
    $material = $stmt->fetch();
    
    if (!$material) {
        echo json_encode(['success' => false, 'message' => 'Material not found']);
        exit();
    }
    
    // Delete file from server
    if (file_exists($material['file_path'])) {
        unlink($material['file_path']);
    }
    
    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM course_materials WHERE id = ?");
    $stmt->execute([$material_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Material deleted successfully'
    ]);
    
} catch (PDOException $e) {
    error_log('Delete material error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
