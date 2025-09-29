<?php
require_once 'config.php';

function verifyRecaptcha($recaptcha_response) {
    if (empty($recaptcha_response)) {
        return false;
    }
    
    $secret_key = RECAPTCHA_SECRET_KEY;
    $verify_url = RECAPTCHA_VERIFY_URL;
    
    $data = [
        'secret' => $secret_key,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($verify_url, false, $context);
    
    if ($result === false) {
        return false;
    }
    
    $response_data = json_decode($result, true);
    
    return isset($response_data['success']) && $response_data['success'] === true;
}
?>
