<?php
// Simple checkbox-style robot verification
session_start();

// Generate a verification token for this session
if (!isset($_SESSION['robot_token'])) {
    $_SESSION['robot_token'] = bin2hex(random_bytes(16));
}

// Return the token for JavaScript validation
header('Content-Type: application/json');
echo json_encode(['token' => $_SESSION['robot_token']]);
?>