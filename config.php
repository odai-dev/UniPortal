<?php
define('SITE_NAME', 'جامعة صنعاء - Sana\'a University');
$domain = $_ENV['REPLIT_DEV_DOMAIN'] ?? 'localhost:5000';
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'https';
define('SITE_URL', $protocol . '://' . $domain);

define('SESSION_TIMEOUT', 3600);
define('REMEMBER_ME_DURATION', 2592000);

define('CSRF_TOKEN_EXPIRE', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);

define('MAX_FILE_SIZE', 2097152);
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'txt']);

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Aden'); // Yemen timezone

?>