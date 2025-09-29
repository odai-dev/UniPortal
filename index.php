<?php
require_once 'db.php';

// Redirect based on login status
if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: register.php');
}
exit();
?>