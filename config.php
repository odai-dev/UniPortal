<?php
define('SITE_NAME', 'FitZone Fitness Center');
define('SITE_URL', 'http://localhost/fitness_center');

define('SESSION_TIMEOUT', 3600);
define('REMEMBER_ME_DURATION', 2592000);

define('CSRF_TOKEN_EXPIRE', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);

define('MAX_FILE_SIZE', 2097152);
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'txt']);

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('America/New_York');
?>
