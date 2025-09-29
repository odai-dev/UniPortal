<?php
require_once 'config.php';

function verifyCustomCaptcha($captcha_token) {
    if (empty($captcha_token)) {
        return false;
    }
    
    if (!isset($_SESSION['captcha_verified']) || !$_SESSION['captcha_verified']) {
        return false;
    }
    
    if (!isset($_SESSION['captcha_token']) || $_SESSION['captcha_token'] !== $captcha_token) {
        return false;
    }
    
    if (!isset($_SESSION['captcha_token_time']) || (time() - $_SESSION['captcha_token_time']) > 300) {
        unset($_SESSION['captcha_verified'], $_SESSION['captcha_token'], $_SESSION['captcha_token_time']);
        return false;
    }
    
    unset($_SESSION['captcha_verified'], $_SESSION['captcha_token'], $_SESSION['captcha_token_time']);
    
    return true;
}
?>
