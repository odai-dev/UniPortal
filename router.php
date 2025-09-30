<?php
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (preg_match('/^\/uploads(?:\/|$)/', $request_path)) {
    http_response_code(403);
    echo '403 Forbidden - Direct access to uploaded files is not allowed';
    exit();
}

return false;
?>
