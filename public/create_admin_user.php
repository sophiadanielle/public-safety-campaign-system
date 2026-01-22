<?php
// Simple one-click admin user creation
// Access via: http://localhost/public-safety-campaign-system/public/create_admin_user.php

require_once __DIR__ . '/../src/Config/db_connect.php';

header('Content-Type: text/html; charset=utf-8');

$success = false;
$error = null;
$messages = [];

try {
    // Generate fresh password hash
    $password = 'password123';
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $messages[] = "Step 1: Generated password hash - SUCCESS";
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Step 1: Ensure roles table and role exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            description VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $pdo->exec("INSERT IGNORE INTO roles (id, name, description) VALUES (1, 'Barangay Administrator', 'Full access to all campaign management features')");
    $messages[] = "Step 2: Created role - SUCCESS";
    
    // Step 2: Ensure barangays table and barangay exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS barangays (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL UNIQUE,
            city VARCHAR(150) NULL,
            province VARCHAR(150) NULL,
            region VARCHAR(150) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $pdo->exec("INSERT IGNORE INTO barangays (id, name, city, province, region) VALUES (1, 'Barangay 1', 'Quezon City', 'Metro Manila', 'NCR')");
    $messages[] = "Step 3: Created barangay - SUCCESS";
    
    // Step 3: Ensure users table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            role_id INT UNSIGNED NOT NULL,
            barangay_id INT UNSIGNED NULL,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
            CONSTRAINT fk_users_barangay FOREIGN KEY (barangay_id) REFERENCES barangays(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $messages[] = "Step 4: Users table exists - SUCCESS";
    
    // Step 4: Check if admin user exists
    $checkStmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ? OR id = 1");
    $checkStmt->execute(['admin@barangay1.qc.gov.ph']);
    $existingUser = $checkStmt->fetch();
    
    if ($existingUser) {
        // User exists - UPDATE password hash instead of deleting
        $messages[] = "Step 5: Admin user exists (ID: {$existingUser['id']}) - UPDATING password";
        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET password_hash = ?, role_id = 1, barangay_id = 1, name = 'Admin User', is_active = 1
            WHERE id = ? OR email = ?
        ");
        $updateStmt->execute([$passwordHash, $existingUser['id'], 'admin@barangay1.qc.gov.ph']);
        $messages[] = "Step 6: Updated admin user password - SUCCESS";
    } else {
        // User doesn't exist - CREATE new user
        $messages[] = "Step 5: Admin user does not exist - CREATING new user";
        $insertStmt = $pdo->prepare("
            INSERT INTO users (id, role_id, barangay_id, name, email, password_hash, is_active) 
            VALUES (1, 1, 1, 'Admin User', 'admin@barangay1.qc.gov.ph', ?, 1)
        ");
        $insertStmt->execute([$passwordHash]);
        $messages[] = "Step 6: Created admin user - SUCCESS";
    }
    
    // Verify password works
    $verifyStmt = $pdo->prepare("SELECT password_hash FROM users WHERE email = ?");
    $verifyStmt->execute(['admin@barangay1.qc.gov.ph']);
    $storedHash = $verifyStmt->fetchColumn();
    
    if (password_verify($password, $storedHash)) {
        $messages[] = "Step 7: Password verification - SUCCESS";
    } else {
        throw new Exception("Password verification failed after creation!");
    }
    
    $pdo->commit();
    $success = true;
    $messages[] = "✅ All steps completed successfully!";
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error = "Database Error: " . $e->getMessage();
    $messages[] = "❌ Transaction rolled back due to error";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Admin User</title>
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
            border-bottom: 3px solid <?php echo $success ? '#28a745' : '#dc3545'; ?>;
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
        .success-box {
            margin-top: 20px;
            padding: 20px;
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 4px;
        }
        .success-box h3 {
            margin-top: 0;
            color: #155724;
        }
        .credentials {
            background: #fff;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
            font-family: monospace;
        }
        .credentials strong {
            display: inline-block;
            width: 100px;
        }
        a {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: #4c8a89;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        a:hover {
            background: #3d6f6e;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $success ? '✅ Admin User Created Successfully!' : '❌ Error Creating Admin User'; ?></h1>
        
        <?php foreach ($messages as $msg): ?>
            <div class="message"><?php echo htmlspecialchars($msg); ?></div>
        <?php endforeach; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-box">
                <h3>🎉 Login Credentials</h3>
                <p>You can now login with these credentials:</p>
                <div class="credentials">
                    <div><strong>Email:</strong> admin@barangay1.qc.gov.ph</div>
                    <div><strong>Password:</strong> password123</div>
                </div>
                <a href="index.php">Go to Login Page →</a>
            </div>
        <?php else: ?>
            <div class="message error">
                <strong>If this error persists, please try running the SQL manually in phpMyAdmin:</strong>
                <pre style="background: #fff; padding: 10px; margin-top: 10px; overflow-x: auto;">
USE LGU;

INSERT IGNORE INTO roles (id, name, description) VALUES 
(1, 'Barangay Administrator', 'Full access');

INSERT IGNORE INTO barangays (id, name, city, province, region) VALUES 
(1, 'Barangay 1', 'Quezon City', 'Metro Manila', 'NCR');

DELETE FROM users WHERE email = 'admin@barangay1.qc.gov.ph' OR id = 1;

INSERT INTO users (id, role_id, barangay_id, name, email, password_hash, is_active) 
VALUES (1, 1, 1, 'Admin User', 'admin@barangay1.qc.gov.ph', '<?php echo $passwordHash ?? ''; ?>', 1);
                </pre>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>




















