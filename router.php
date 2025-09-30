<?php
// Router script for PHP built-in server to block direct access to uploads

// Get clean request path
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Block direct access to uploads directory
if (preg_match('/^\/uploads(?:\/|$)/', $request_path)) {
    http_response_code(403);
    echo '403 Forbidden - Direct access to uploaded files is not allowed';
    exit();
}

// For all other requests, serve the file normally
return false;
?>
