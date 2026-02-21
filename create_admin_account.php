<?php
/**
 * Create Admin Account Script
 * Creates a new admin user account for testing
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load database configuration
require_once __DIR__ . '/src/Config/db_connect.php';

echo "<h1>Create Admin Account</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}pre{background:#2d2d2d;padding:10px;overflow-x:auto;}.success{color:#4ec9b0;}.error{color:#f48771;}.info{color:#569cd6;}</style>";

try {
    // Admin account details
    $email = 'admin@test.com';
    $password = 'admin123';
    $name = 'Test Admin';
    
    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    
    echo "<h2>Creating Admin Account...</h2>";
    echo "<pre>";
    
    // Check if admin role exists (role_id = 1 is typically admin)
    $stmt = $pdo->query("SELECT id, name FROM campaign_department_roles WHERE id = 1");
    $adminRole = $stmt->fetch();
    
    if (!$adminRole) {
        echo "<span class='error'>✗</span> Admin role not found. Creating default admin role...\n";
        $pdo->exec("INSERT INTO campaign_department_roles (id, name, description) VALUES (1, 'Admin', 'System Administrator')");
        echo "<span class='success'>✓</span> Admin role created\n";
    } else {
        echo "<span class='success'>✓</span> Admin role found: {$adminRole['name']}\n";
    }
    
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id, email FROM campaign_department_users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "<span class='info'>⚠</span> User already exists with email: $email\n";
        echo "<span class='info'>→</span> Updating password...\n";
        
        $stmt = $pdo->prepare("UPDATE campaign_department_users SET password_hash = ?, is_active = 1 WHERE email = ?");
        $stmt->execute([$passwordHash, $email]);
        
        echo "<span class='success'>✓</span> Password updated successfully\n";
    } else {
        echo "<span class='info'>→</span> Creating new admin user...\n";
        
        $stmt = $pdo->prepare("
            INSERT INTO campaign_department_users 
            (role_id, name, email, password_hash, is_active) 
            VALUES (1, ?, ?, ?, 1)
        ");
        $stmt->execute([$name, $email, $passwordHash]);
        
        echo "<span class='success'>✓</span> Admin user created successfully\n";
    }
    
    // If user_type column exists, update it to Super Admin
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'campaign_department_users' 
        AND COLUMN_NAME = 'user_type'
    ");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        $stmt = $pdo->prepare("UPDATE campaign_department_users SET user_type = 'Super Admin' WHERE email = ?");
        $stmt->execute([$email]);
        echo "<span class='success'>✓</span> User type set to 'Super Admin'\n";
    }
    
    echo "</pre>";
    
    echo "<h2 class='success'>✓ Admin Account Ready!</h2>";
    echo "<div style='background:#2d2d2d;padding:15px;margin:20px 0;border-left:3px solid #4ec9b0;'>";
    echo "<p><strong>Login Credentials:</strong></p>";
    echo "<p><strong>Email:</strong> <span style='color:#ce9178;'>$email</span></p>";
    echo "<p><strong>Password:</strong> <span style='color:#ce9178;'>$password</span></p>";
    echo "</div>";
    
    echo "<p><a href='/login.php' style='color:#4ec9b0;text-decoration:none;font-weight:bold;'>→ Go to Login Page</a></p>";
    
    echo "<div style='margin-top:30px;padding:10px;background:#2d2d2d;border-left:3px solid #dcdcaa;'>";
    echo "<p style='color:#dcdcaa;'><strong>⚠ Security Note:</strong></p>";
    echo "<p>Please delete this file after creating your admin account:</p>";
    echo "<code style='color:#ce9178;'>create_admin_account.php</code>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<h2 class='error'>✗ Failed to Create Admin Account</h2>";
    echo "<p class='error'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
} catch (Exception $e) {
    echo "<h2 class='error'>✗ Error</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
