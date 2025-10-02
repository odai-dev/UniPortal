<?php
require_once 'config.php';
session_start();

$host = 'localhost';
$port = 3306;
$dbname = 'fitness_center';
$username = 'root';
$password = '';

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    $pdo->exec("SET NAMES utf8");
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

function validateEmail($email) {
    $pattern = '/^[a-zA-Z0-9._%+-]+@(gmail\.com|hotmail\.com)$/i';
    return preg_match($pattern, $email);
}

function validatePassword($password) {
    $pattern = '/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    return preg_match($pattern, $password);
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
        ((time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRE)) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    if ((time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRE) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
    
    return true;
}

function invalidateCSRFToken() {
    unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
}

function verifyRecaptcha($recaptcha_response) {
    $secret_key = $_ENV['RECAPTCHA_SECRET_KEY'] ?? '';
    
    if (empty($secret_key)) {
        error_log('reCAPTCHA not configured - skipping verification');
        return true;
    }
    
    if (empty($recaptcha_response)) {
        return false;
    }
    
    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    
    $data = [
        'secret' => $secret_key,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $verify_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        error_log('reCAPTCHA verification cURL error: ' . $curl_error);
        return false;
    }
    
    $response_data = json_decode($response, true);
    
    return isset($response_data['success']) && $response_data['success'] === true;
}

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

function generateRememberToken($user_id) {
    global $pdo;
    
    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $expires_at = date('Y-m-d H:i:s', time() + REMEMBER_ME_DURATION);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ? OR expires_at < NOW()");
        $stmt->execute([$user_id]);
        
        $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $token_hash, $expires_at]);
        
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
        $stmt = $pdo->prepare("
            SELECT rt.user_id, u.name, u.email, u.role 
            FROM remember_tokens rt 
            JOIN users u ON rt.user_id = u.id 
            WHERE rt.token_hash = ? AND rt.expires_at > NOW()
        ");
        $stmt->execute([$token_hash]);
        $result = $stmt->fetch();
        
        if ($result) {
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

if (isLoggedIn()) {
    if (isSessionExpired()) {
        session_destroy();
        header('Location: login.php?expired=1');
        exit();
    }
    updateSessionActivity();
    
    if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > 300) {
        regenerateSession();
        $_SESSION['last_regeneration'] = time();
    }
}

?>
