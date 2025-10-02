<?php
require_once 'db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Require login
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Only members can register
if (isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin users cannot register for classes']);
    exit();
}

$class_id = $_POST['course_id'] ?? null;

if (!$class_id || !is_numeric($class_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
    exit();
}

try {
    // Check if class exists
    $stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch();
    
    if (!$class) {
        echo json_encode(['success' => false, 'message' => 'Class not found']);
        exit();
    }
    
    // Check if already registered
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM memberships WHERE user_id = ? AND class_id = ?");
    $stmt->execute([$_SESSION['user_id'], $class_id]);
    $already_registered = $stmt->fetchColumn() > 0;
    
    if ($already_registered) {
        echo json_encode(['success' => false, 'message' => 'You are already registered for this class']);
        exit();
    }
    
    // Register member
    $stmt = $pdo->prepare("INSERT INTO memberships (user_id, class_id) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $class_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Successfully registered for ' . $class['class_name']
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>