<?php
session_start();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'generate') {
    generateChallenge();
} elseif ($action === 'verify') {
    verifyChallenge();
}

function getClientIP() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return $ip;
    }
    
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $forwardedIP = trim($ips[0]);
        if (filter_var($forwardedIP, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $forwardedIP;
        }
    }
    
    return $ip;
}

function getAttemptsFilePath($ip) {
    $attemptsDir = '/tmp/captcha_attempts/';
    if (!is_dir($attemptsDir)) {
        if (!mkdir($attemptsDir, 0755, true)) {
            error_log('Failed to create captcha attempts directory');
            return null;
        }
    }
    
    $hashedIP = hash('sha256', $ip);
    return $attemptsDir . $hashedIP . '.json';
}

function cleanOldAttemptFiles() {
    $attemptsDir = '/tmp/captcha_attempts/';
    if (!is_dir($attemptsDir)) {
        return;
    }
    
    $currentTime = time();
    $files = glob($attemptsDir . '*.json');
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $fileAge = $currentTime - filemtime($file);
            if ($fileAge > 3600) {
                @unlink($file);
            }
        }
    }
}

function loadAttemptData($ip) {
    $filePath = getAttemptsFilePath($ip);
    if (!$filePath || !file_exists($filePath)) {
        return [
            'count' => 0,
            'lockout_until' => 0
        ];
    }
    
    $data = @file_get_contents($filePath);
    if ($data === false) {
        return [
            'count' => 0,
            'lockout_until' => 0
        ];
    }
    
    $attempts = json_decode($data, true);
    if (!is_array($attempts)) {
        return [
            'count' => 0,
            'lockout_until' => 0
        ];
    }
    
    return [
        'count' => $attempts['count'] ?? 0,
        'lockout_until' => $attempts['lockout_until'] ?? 0
    ];
}

function saveAttemptData($ip, $data) {
    $filePath = getAttemptsFilePath($ip);
    if (!$filePath) {
        return false;
    }
    
    $jsonData = json_encode($data);
    return @file_put_contents($filePath, $jsonData, LOCK_EX) !== false;
}

function checkRateLimit() {
    $ip = getClientIP();
    $currentTime = time();
    
    cleanOldAttemptFiles();
    
    $attempts = loadAttemptData($ip);
    
    if ($attempts['lockout_until'] > $currentTime) {
        $remainingTime = $attempts['lockout_until'] - $currentTime;
        return [
            'allowed' => false,
            'message' => 'Too many failed attempts. Please try again in ' . ceil($remainingTime / 60) . ' minute(s).',
            'lockout_remaining' => $remainingTime
        ];
    }
    
    if ($attempts['lockout_until'] > 0 && $attempts['lockout_until'] <= $currentTime) {
        $attempts['count'] = 0;
        $attempts['lockout_until'] = 0;
        saveAttemptData($ip, $attempts);
    }
    
    return ['allowed' => true];
}

function recordFailedAttempt() {
    $ip = getClientIP();
    $currentTime = time();
    
    $attempts = loadAttemptData($ip);
    $attempts['count']++;
    
    if ($attempts['count'] >= 5) {
        $attempts['lockout_until'] = $currentTime + 300;
    }
    
    saveAttemptData($ip, $attempts);
}

function resetAttempts() {
    $ip = getClientIP();
    $attempts = [
        'count' => 0,
        'lockout_until' => 0
    ];
    saveAttemptData($ip, $attempts);
}

function generateChallenge() {
    $rateLimit = checkRateLimit();
    if (!$rateLimit['allowed']) {
        echo json_encode([
            'success' => false,
            'message' => $rateLimit['message'],
            'lockout' => true
        ]);
        return;
    }
    
    $challenges = [
        [
            'instruction' => 'Select all BLUE squares',
            'target' => 'blue',
            'type' => 'color'
        ],
        [
            'instruction' => 'Select all RED squares',
            'target' => 'red',
            'type' => 'color'
        ],
        [
            'instruction' => 'Select all GREEN squares',
            'target' => 'green',
            'type' => 'color'
        ],
        [
            'instruction' => 'Select all images with ⭐',
            'target' => '⭐',
            'type' => 'emoji'
        ],
        [
            'instruction' => 'Select all images with 🌙',
            'target' => '🌙',
            'type' => 'emoji'
        ],
        [
            'instruction' => 'Select all images with ☀️',
            'target' => '☀️',
            'type' => 'emoji'
        ],
        [
            'instruction' => 'Select all images with 🔥',
            'target' => '🔥',
            'type' => 'emoji'
        ],
        [
            'instruction' => 'Select all images with 💧',
            'target' => '💧',
            'type' => 'emoji'
        ]
    ];
    
    $challenge = $challenges[array_rand($challenges)];
    $puzzleId = bin2hex(random_bytes(16));
    
    $tiles = [];
    $correctAnswers = [];
    
    if ($challenge['type'] === 'color') {
        $colors = ['blue', 'red', 'green', 'yellow', 'purple', 'orange'];
        $targetColor = $challenge['target'];
        
        for ($i = 0; $i < 9; $i++) {
            $isCorrect = (mt_rand(0, 100) < 33);
            if ($isCorrect) {
                $tiles[] = [
                    'index' => $i,
                    'color' => $targetColor,
                    'type' => 'color'
                ];
                $correctAnswers[] = $i;
            } else {
                $randomColor = $colors[array_rand(array_diff($colors, [$targetColor]))];
                $tiles[] = [
                    'index' => $i,
                    'color' => $randomColor,
                    'type' => 'color'
                ];
            }
        }
    } else {
        $emojis = ['⭐', '🌙', '☀️', '🔥', '💧', '🌟', '⚡', '🌊', '🌈'];
        $targetEmoji = $challenge['target'];
        
        for ($i = 0; $i < 9; $i++) {
            $isCorrect = (mt_rand(0, 100) < 33);
            if ($isCorrect) {
                $tiles[] = [
                    'index' => $i,
                    'emoji' => $targetEmoji,
                    'type' => 'emoji'
                ];
                $correctAnswers[] = $i;
            } else {
                $randomEmoji = $emojis[array_rand(array_diff($emojis, [$targetEmoji]))];
                $tiles[] = [
                    'index' => $i,
                    'emoji' => $randomEmoji,
                    'type' => 'emoji'
                ];
            }
        }
    }
    
    if (empty($correctAnswers)) {
        $randomIndex = mt_rand(0, 8);
        if ($challenge['type'] === 'color') {
            $tiles[$randomIndex]['color'] = $challenge['target'];
        } else {
            $tiles[$randomIndex]['emoji'] = $challenge['target'];
        }
        $correctAnswers[] = $randomIndex;
    }
    
    $_SESSION['captcha_puzzle_id'] = $puzzleId;
    $_SESSION['captcha_correct_answers'] = $correctAnswers;
    $_SESSION['captcha_time'] = time();
    $_SESSION['captcha_start_time'] = microtime(true);
    
    echo json_encode([
        'success' => true,
        'puzzleId' => $puzzleId,
        'instruction' => $challenge['instruction'],
        'tiles' => $tiles
    ]);
}

function verifyChallenge() {
    $rateLimit = checkRateLimit();
    if (!$rateLimit['allowed']) {
        echo json_encode([
            'success' => false,
            'message' => $rateLimit['message'],
            'lockout' => true
        ]);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $puzzleId = $input['puzzleId'] ?? '';
    $selectedTiles = $input['selectedTiles'] ?? [];
    
    if (empty($puzzleId) || !isset($_SESSION['captcha_puzzle_id'])) {
        recordFailedAttempt();
        echo json_encode([
            'success' => false,
            'message' => 'Invalid or expired challenge'
        ]);
        return;
    }
    
    if ($puzzleId !== $_SESSION['captcha_puzzle_id']) {
        recordFailedAttempt();
        echo json_encode([
            'success' => false,
            'message' => 'Challenge ID mismatch'
        ]);
        return;
    }
    
    if (!isset($_SESSION['captcha_time']) || (time() - $_SESSION['captcha_time']) > 300) {
        unset($_SESSION['captcha_puzzle_id'], $_SESSION['captcha_correct_answers'], $_SESSION['captcha_time'], $_SESSION['captcha_start_time']);
        recordFailedAttempt();
        echo json_encode([
            'success' => false,
            'message' => 'Challenge expired'
        ]);
        return;
    }
    
    if (!isset($_SESSION['captcha_start_time'])) {
        unset($_SESSION['captcha_puzzle_id'], $_SESSION['captcha_correct_answers'], $_SESSION['captcha_time'], $_SESSION['captcha_start_time']);
        recordFailedAttempt();
        echo json_encode([
            'success' => false,
            'message' => 'Invalid challenge state'
        ]);
        return;
    }
    
    $solveTime = microtime(true) - $_SESSION['captcha_start_time'];
    if ($solveTime < 2.0) {
        unset($_SESSION['captcha_puzzle_id'], $_SESSION['captcha_correct_answers'], $_SESSION['captcha_time'], $_SESSION['captcha_start_time']);
        recordFailedAttempt();
        echo json_encode([
            'success' => false,
            'message' => 'Challenge solved too quickly. Please try again.'
        ]);
        return;
    }
    
    $correctAnswers = $_SESSION['captcha_correct_answers'] ?? [];
    sort($selectedTiles);
    sort($correctAnswers);
    
    $isCorrect = ($selectedTiles === $correctAnswers);
    
    if ($isCorrect) {
        $verificationToken = bin2hex(random_bytes(32));
        $_SESSION['captcha_verified'] = true;
        $_SESSION['captcha_token'] = $verificationToken;
        $_SESSION['captcha_token_time'] = time();
        
        resetAttempts();
        
        unset($_SESSION['captcha_puzzle_id'], $_SESSION['captcha_correct_answers'], $_SESSION['captcha_time'], $_SESSION['captcha_start_time']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Verification successful',
            'token' => $verificationToken
        ]);
    } else {
        recordFailedAttempt();
        
        unset($_SESSION['captcha_puzzle_id'], $_SESSION['captcha_correct_answers'], $_SESSION['captcha_time'], $_SESSION['captcha_start_time']);
        
        $attempts = loadAttemptData(getClientIP());
        $remainingAttempts = 5 - ($attempts['count'] ?? 0);
        $message = 'Incorrect selection. Please try again.';
        if ($remainingAttempts > 0 && $remainingAttempts <= 3) {
            $message .= ' (' . $remainingAttempts . ' attempt' . ($remainingAttempts === 1 ? '' : 's') . ' remaining)';
        }
        
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
    }
}
?>
