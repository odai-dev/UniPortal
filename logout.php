<?php
require_once 'db.php';

invalidateCSRFToken();

session_destroy();

if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/', '', false, true);
}

$_SESSION = array();

session_start();
regenerateSession();

header('Location: login.php');
exit();
?>
