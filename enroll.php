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

// Only students can enroll
if (isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin users cannot enroll in courses']);
    exit();
}

$course_id = $_POST['course_id'] ?? null;

if (!$course_id || !is_numeric($course_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
    exit();
}

try {
    // Check if course exists
    $stmt = $pdo->prepare("SELECT id, course_name FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        echo json_encode(['success' => false, 'message' => 'Course not found']);
        exit();
    }
    
    // Check if already enrolled
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    $already_enrolled = $stmt->fetchColumn() > 0;
    
    if ($already_enrolled) {
        echo json_encode(['success' => false, 'message' => 'You are already enrolled in this course']);
        exit();
    }
    
    // Enroll student
    $stmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Successfully enrolled in ' . $course['course_name']
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>