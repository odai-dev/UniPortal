<?php
// Configuration settings for University Student Portal

// Site settings
define('SITE_NAME', 'University Student Portal');
define('SITE_URL', 'http://localhost:5000');

// Session settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('REMEMBER_ME_DURATION', 2592000); // 30 days

// CAPTCHA settings (using simple PHP image captcha)
define('CAPTCHA_LENGTH', 5);
define('CAPTCHA_WIDTH', 120);
define('CAPTCHA_HEIGHT', 40);

// File upload settings
define('MAX_FILE_SIZE', 2097152); // 2MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'txt']);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('America/New_York');

?>