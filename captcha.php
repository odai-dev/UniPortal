<?php
// Image-based CAPTCHA verification
session_start();

// Create image with random numbers and letters
$width = 120;
$height = 40;
$font_size = 16;

// Generate random string of 5 characters (letters and numbers)
$captcha_string = '';
$possible_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789';
for ($i = 0; $i < 5; $i++) {
    $captcha_string .= $possible_chars[rand(0, strlen($possible_chars) - 1)];
}

// Store in session for verification
$_SESSION['captcha_code'] = $captcha_string;

// Create image
$image = imagecreate($width, $height);

// Colors
$bg_color = imagecolorallocate($image, 240, 240, 240);
$text_color = imagecolorallocate($image, 50, 50, 150);
$line_color = imagecolorallocate($image, 200, 200, 200);

// Fill background
imagefill($image, 0, 0, $bg_color);

// Add some noise lines
for ($i = 0; $i < 5; $i++) {
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
}

// Add text to image
$x = 10;
for ($i = 0; $i < strlen($captcha_string); $i++) {
    $angle = rand(-15, 15);
    $y = rand(25, 35);
    
    // Use imagestring instead of imagettftext for better compatibility
    imagestring($image, 5, $x + ($i * 20), $y - 20, $captcha_string[$i], $text_color);
}

// Set content type and output image
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
?>