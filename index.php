<?php
require_once 'db.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: register.php');
}
exit();
?>
