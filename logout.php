<?php
require_once 'db.php';

// Invalidate CSRF token before destroying session
invalidateCSRFToken();

// Destroy all session data
session_destroy();

// Remove remember me cookie securely
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/', '', false, true);
}

// Clear all session variables
$_SESSION = array();

// Regenerate session ID for security
session_start();
regenerateSession();

// Redirect to login page
header('Location: login.php');
exit();
?>