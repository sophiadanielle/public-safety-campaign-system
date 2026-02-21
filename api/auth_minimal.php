<?php
/**
 * Minimal auth test - step by step debugging
 */

// Step 1: Can we output JSON at all?
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Step 2: Can we connect to database?
try {
    $pdo = new PDO(
        "mysql:host=localhost;port=3306;dbname=LGU;charset=utf8mb4",
        "root",
        "YsqnXk6q#145",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (Exception $e) {
    echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

// Step 3: Can we get the input?
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['error' => 'Email and password required']);
    exit;
}

// Step 4: Can we query the user?
try {
    $stmt = $pdo->prepare("SELECT * FROM campaign_users WHERE email = ? AND archived = 0");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['error' => 'Invalid password']);
        exit;
    }
    
    // Step 5: Generate OTP
    $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // Step 6: Insert OTP
    $pdo->prepare("UPDATE campaign_otp_codes SET is_used = 1 WHERE user_id = ? AND is_used = 0")->execute([$user['id']]);
    $stmt = $pdo->prepare("INSERT INTO campaign_otp_codes (user_id, email, otp_code, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user['id'], $email, $otpCode, $expiresAt]);
    
    // Success
    echo json_encode([
        'success' => true,
        'message' => 'OTP generated',
        'user_id' => $user['id'],
        'email' => substr($email, 0, 2) . '***@' . explode('@', $email)[1],
        'otp_for_testing' => $otpCode
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
}
