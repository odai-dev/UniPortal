<?php
// Simple PHP CAPTCHA generator
session_start();

// Generate random CAPTCHA string
function generateCaptchaString($length = 5) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $captcha_string = '';
    for ($i = 0; $i < $length; $i++) {
        $captcha_string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $captcha_string;
}

// Store CAPTCHA in session
$captcha_string = generateCaptchaString(5);
$_SESSION['captcha'] = $captcha_string;

// Create image
$image_width = 120;
$image_height = 40;
$image = imagecreate($image_width, $image_height);

// Colors
$bg_color = imagecolorallocate($image, 255, 255, 255);
$text_color = imagecolorallocate($image, 0, 0, 0);
$line_color = imagecolorallocate($image, 128, 128, 128);

// Add noise lines
for ($i = 0; $i < 5; $i++) {
    imageline($image, 
        rand(0, $image_width), rand(0, $image_height),
        rand(0, $image_width), rand(0, $image_height),
        $line_color
    );
}

// Add text
$font_size = 5;
$text_x = ($image_width - strlen($captcha_string) * imagefontwidth($font_size)) / 2;
$text_y = ($image_height - imagefontheight($font_size)) / 2;

imagestring($image, $font_size, $text_x, $text_y, $captcha_string, $text_color);

// Set content type
header('Content-Type: image/png');

// Output image
imagepng($image);

// Clean up memory
imagedestroy($image);
?>