<?php
require_once 'db.php';

// Destroy all session data
session_start();
session_destroy();

// Remove remember me cookie
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

// Clear all session variables
$_SESSION = array();

// Redirect to login page
header('Location: login.php');
exit();
?>