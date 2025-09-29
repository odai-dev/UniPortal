<?php
// Database connection using PDO
require_once 'config.php';
session_start();

// Database configuration - supports both MySQL and PostgreSQL
$host = $_ENV['PGHOST'] ?? $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['PGPORT'] ?? $_ENV['DB_PORT'] ?? 3306;
$dbname = $_ENV['PGDATABASE'] ?? $_ENV['DB_NAME'] ?? 'university_portal';
$username = $_ENV['PGUSER'] ?? $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['PGPASSWORD'] ?? $_ENV['DB_PASS'] ?? '';

// Determine database type and create appropriate DSN
if (isset($_ENV['PGHOST']) || isset($_ENV['PGDATABASE'])) {
    // PostgreSQL (current environment)
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
} else {
    // MySQL (production environment)
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
}

try {
    // Create PDO connection with error handling
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Set character set (database-specific)
    if (isset($_ENV['PGHOST']) || isset($_ENV['PGDATABASE'])) {
        // PostgreSQL - charset is set in DSN, no additional command needed
        // PostgreSQL uses UTF-8 by default
    } else {
        // MySQL
        $pdo->exec("SET NAMES utf8");
    }
    
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

// CSRF Protection functions with proper per-request handling
function generateCSRFToken() {
    // Generate token only if it doesn't exist or has expired
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
        ((time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRE)) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    // Check if token exists and is not expired
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Check if token has expired
    if ((time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRE) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    // Validate token using constant-time comparison
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    
    // Regenerate token AFTER successful validation for next request
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
    
    return true;
}

// Function to invalidate CSRF token on authentication events
function invalidateCSRFToken() {
    unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
}

// Session security improvements
function regenerateSession() {
    session_regenerate_id(true);
}

function isSessionExpired() {
    $timeout = SESSION_TIMEOUT;
    return isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout;
}

function updateSessionActivity() {
    $_SESSION['last_activity'] = time();
}

// Secure remember me token functions with server-side storage
function generateRememberToken($user_id) {
    global $pdo;
    
    // Generate a secure random token
    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $expires_at = date('Y-m-d H:i:s', time() + REMEMBER_ME_DURATION);
    
    try {
        // Clean up old tokens for this user
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ? OR expires_at < NOW()");
        $stmt->execute([$user_id]);
        
        // Store new token hash in database
        $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $token_hash, $expires_at]);
        
        // Return the plain token for cookie (never store plain tokens in DB)
        return $token;
    } catch (PDOException $e) {
        error_log("Remember token generation failed: " . $e->getMessage());
        return false;
    }
}

function validateRememberToken($token) {
    global $pdo;
    
    if (empty($token) || strlen($token) !== 64) {
        return false;
    }
    
    $token_hash = hash('sha256', $token);
    
    try {
        // Find valid token in database
        $stmt = $pdo->prepare("
            SELECT rt.user_id, u.name, u.email, u.role 
            FROM remember_tokens rt 
            JOIN users u ON rt.user_id = u.id 
            WHERE rt.token_hash = ? AND rt.expires_at > NOW()
        ");
        $stmt->execute([$token_hash]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Clean up the used token for security
            $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token_hash = ?");
            $stmt->execute([$token_hash]);
            
            return $result;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Remember token validation failed: " . $e->getMessage());
        return false;
    }
}

// Enhanced session management
if (isLoggedIn()) {
    if (isSessionExpired()) {
        session_destroy();
        header('Location: login.php?expired=1');
        exit();
    }
    updateSessionActivity();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > 300) {
        regenerateSession();
        $_SESSION['last_regeneration'] = time();
    }
}

?>