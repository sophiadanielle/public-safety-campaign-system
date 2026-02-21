<?php
/**
 * Debug login endpoint - shows actual error
 */

header('Content-Type: text/plain');

echo "=== LOGIN DEBUG ===\n\n";

// Test 1: Load database
echo "1. Loading database...\n";
try {
    require_once __DIR__ . '/../src/Config/db_connect.php';
    echo "   ✓ Database loaded\n";
    echo "   PDO: " . (isset($pdo) ? "EXISTS" : "NOT SET") . "\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
    exit;
}

// Test 2: Load vendor
echo "2. Loading vendor autoload...\n";
try {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        echo "   ✓ Vendor autoload loaded\n\n";
    } else {
        echo "   ✗ Vendor autoload not found\n\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Check JWT
echo "3. Checking JWT class...\n";
if (class_exists('Firebase\JWT\JWT')) {
    echo "   ✓ JWT class exists\n\n";
} else {
    echo "   ✗ JWT class not found\n\n";
}

// Test 4: Simulate login
echo "4. Simulating login request...\n";
$testEmail = 'admin@alertaraqc.com';
$testPassword = 'admin123';

try {
    $stmt = $pdo->prepare("SELECT * FROM campaign_users WHERE email = ? AND archived = 0");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "   ✗ User not found\n\n";
    } else {
        echo "   ✓ User found: " . $user['fullname'] . "\n";
        echo "   Email: " . $user['email'] . "\n";
        echo "   User Type: " . $user['user_type'] . "\n";
        
        // Test password
        if (password_verify($testPassword, $user['password'])) {
            echo "   ✓ Password verified\n\n";
        } else {
            echo "   ✗ Password verification failed\n\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 5: Try to generate OTP
echo "5. Testing OTP generation...\n";
try {
    $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    echo "   ✓ OTP generated: $otpCode\n";
    
    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    echo "   Expires at: $expiresAt\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 6: Try to insert OTP
echo "6. Testing OTP insertion...\n";
if (isset($user) && $user) {
    try {
        $pdo->prepare("UPDATE campaign_otp_codes SET is_used = 1 WHERE user_id = ? AND is_used = 0")->execute([$user['id']]);
        
        $stmt = $pdo->prepare("INSERT INTO campaign_otp_codes (user_id, email, otp_code, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user['id'], $user['email'], $otpCode, $expiresAt]);
        
        echo "   ✓ OTP inserted into database\n";
        echo "   OTP ID: " . $pdo->lastInsertId() . "\n\n";
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n\n";
    }
}

echo "=== END DEBUG ===\n";
