<?php
// Database connection using PDO
session_start();

// Database configuration
$host = $_ENV['PGHOST'] ?? 'localhost';
$port = $_ENV['PGPORT'] ?? 5432;
$dbname = $_ENV['PGDATABASE'] ?? 'university_portal';
$username = $_ENV['PGUSER'] ?? 'root';
$password = $_ENV['PGPASSWORD'] ?? '';

// PDO connection string for PostgreSQL
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

try {
    // Create PDO connection with error handling
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Set character set
    $pdo->exec("SET NAMES utf8");
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Helper function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Helper function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Helper function to require admin access
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

// Helper function to validate email format (Gmail or Hotmail only)
function validateEmail($email) {
    $pattern = '/^[a-zA-Z0-9._%+-]+@(gmail\.com|hotmail\.com)$/i';
    return preg_match($pattern, $email);
}

// Helper function to validate password strength
function validatePassword($password) {
    // Must contain letters, numbers, and symbols, minimum 8 characters
    $pattern = '/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    return preg_match($pattern, $password);
}

// Helper function to sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

?>