<?php
// Diagnostic and fix script for login issues
// Access via: http://localhost/public-safety-campaign-system/public/fix_login_diagnostic.php

require_once __DIR__ . '/../src/Config/db_connect.php';

$messages = [];
$errors = [];

// Generate fresh password hash
$password = 'password123';
$newHash = password_hash($password, PASSWORD_DEFAULT);

$messages[] = "Generated fresh password hash for 'password123': " . $newHash;
$messages[] = "Verification test: " . (password_verify($password, $newHash) ? "SUCCESS" : "FAILED");

// Check database connection
try {
    $messages[] = "Database connection: SUCCESS";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        $messages[] = "Users table: EXISTS";
        
        // Check if admin user exists
        $stmt = $pdo->prepare("SELECT id, name, email, role_id, is_active FROM users WHERE email = ?");
        $stmt->execute(['admin@barangay1.qc.gov.ph']);
        $user = $stmt->fetch();
        
        if ($user) {
            $messages[] = "Admin user: EXISTS (ID: {$user['id']}, Active: {$user['is_active']})";
            
            // Update password hash
            if (isset($_GET['fix']) && $_GET['fix'] === '1') {
                $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
                $updateStmt->execute([$newHash, 'admin@barangay1.qc.gov.ph']);
                $messages[] = "Password hash UPDATED successfully!";
                $messages[] = "You can now login with: admin@barangay1.qc.gov.ph / password123";
            } else {
                $messages[] = "To fix the password, add ?fix=1 to the URL";
                $messages[] = "Current password hash in DB: " . substr($pdo->query("SELECT password_hash FROM users WHERE email = 'admin@barangay1.qc.gov.ph'")->fetchColumn(), 0, 50) . "...";
                
                // Test current hash
                $currentHash = $pdo->query("SELECT password_hash FROM users WHERE email = 'admin@barangay1.qc.gov.ph'")->fetchColumn();
                $testResult = password_verify($password, $currentHash);
                $messages[] = "Current hash verification: " . ($testResult ? "SUCCESS" : "FAILED - This is why login doesn't work!");
            }
        } else {
            $errors[] = "Admin user DOES NOT EXIST";
            
            // Create user if requested
            if (isset($_GET['create']) && $_GET['create'] === '1') {
                try {
                    $pdo->beginTransaction();
                    
                    // Make sure roles exist
                    $pdo->exec("INSERT IGNORE INTO roles (id, name, description) VALUES (1, 'Barangay Administrator', 'Full access to all campaign management features')");
                    $messages[] = "Role check: OK";
                    
                    // Make sure barangays exist
                    $pdo->exec("INSERT IGNORE INTO barangays (id, name, city, province, region) VALUES (1, 'Barangay 1', 'Quezon City', 'Metro Manila', 'NCR')");
                    $messages[] = "Barangay check: OK";
                    
                    // Check if user exists first
                    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR id = 1");
                    $checkStmt->execute(['admin@barangay1.qc.gov.ph']);
                    $existingUser = $checkStmt->fetch();
                    
                    if ($existingUser) {
                        // Update existing user password (can't delete due to foreign key constraints)
                        $messages[] = "Admin user exists - Updating password";
                        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ?, role_id = 1, barangay_id = 1, name = 'Admin User', is_active = 1 WHERE id = ?");
                        $updateStmt->execute([$newHash, $existingUser['id']]);
                        $messages[] = "Updated existing admin user password";
                    } else {
                        // Create new admin user
                        $messages[] = "Creating new admin user";
                        $insertStmt = $pdo->prepare("INSERT INTO users (id, role_id, barangay_id, name, email, password_hash, is_active) VALUES (1, 1, 1, 'Admin User', 'admin@barangay1.qc.gov.ph', ?, 1)");
                        $insertStmt->execute([$newHash]);
                        $messages[] = "Created new admin user";
                    }
                    $pdo->commit();
                    
                    $messages[] = "✅ Admin user CREATED successfully!";
                    $messages[] = "✅ Password hash: " . substr($newHash, 0, 30) . "...";
                    $messages[] = "✅ You can now login with: admin@barangay1.qc.gov.ph / password123";
                    
                    // Refresh user check
                    $stmt = $pdo->prepare("SELECT id, name, email, role_id, is_active FROM users WHERE email = ?");
                    $stmt->execute(['admin@barangay1.qc.gov.ph']);
                    $user = $stmt->fetch();
                } catch (Exception $createError) {
                    $pdo->rollBack();
                    $errors[] = "Failed to create user: " . $createError->getMessage();
                    $errors[] = "Error details: " . $createError->getFile() . ":" . $createError->getLine();
                }
            } else {
                $messages[] = "To create the admin user, add ?create=1 to the URL or click the button below";
            }
        }
    } else {
        $errors[] = "Users table DOES NOT EXIST - You need to run migrations first!";
    }
    
} catch (Exception $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Diagnostic Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4c8a89;
            padding-bottom: 10px;
        }
        .message {
            padding: 10px;
            margin: 5px 0;
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
            border-radius: 4px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border-left-color: #ffc107;
        }
        .action-box {
            margin-top: 20px;
            padding: 20px;
            background: #e7f3ff;
            border: 2px solid #4c8a89;
            border-radius: 4px;
        }
        .action-box a {
            display: inline-block;
            margin: 10px 10px 10px 0;
            padding: 10px 20px;
            background: #4c8a89;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .action-box a:hover {
            background: #3d6f6e;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Login Diagnostic Tool</h1>
        
        <?php foreach ($messages as $msg): ?>
            <div class="message"><?php echo htmlspecialchars($msg); ?></div>
        <?php endforeach; ?>
        
        <?php foreach ($errors as $error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
        
        <?php if (isset($user) && $user): ?>
            <div class="action-box">
                <h3>Fix Password</h3>
                <p>Click the button below to update the admin password to 'password123':</p>
            <a href="?fix=1">Fix Password Hash</a>
            </div>
        <?php elseif (!isset($user) || !$user): ?>
            <div class="action-box">
                <h3>Create Admin User</h3>
                <p>Click the button below to create the admin user:</p>
                <a href="?create=1">Create Admin User</a>
            </div>
        <?php endif; ?>
        
        <div class="action-box" style="margin-top: 20px;">
            <h3>Test Login</h3>
            <p>After fixing, try logging in with:</p>
            <ul>
                <li><strong>Email:</strong> admin@barangay1.qc.gov.ph</li>
                <li><strong>Password:</strong> password123</li>
            </ul>
                <a href="index.php">Go to Login Page</a>
        </div>
    </div>
</body>
</html>

















