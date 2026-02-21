<?php
/**
 * Combined Migration Runner for User Management System
 * Creates campaign_users and campaign_otp_codes tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../src/Config/db_connect.php';

$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

echo "<h1>User Management System - Database Migration</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}pre{background:#2d2d2d;padding:10px;overflow-x:auto;}.success{color:#4ec9b0;}.error{color:#f48771;}.warning{color:#dcdcaa;}.info{color:#9cdcfe;}</style>";

$migrations = [
    '034_campaign_users_table.sql' => 'Campaign Users Table',
    '035_otp_codes_table.sql' => 'OTP Codes Table'
];

$successCount = 0;
$errorCount = 0;

foreach ($migrations as $file => $name) {
    echo "<h2 class='info'>Running: $name</h2>";
    echo "<pre>";
    
    $sqlFile = __DIR__ . '/../../migrations/' . $file;
    
    if (!file_exists($sqlFile)) {
        echo "<span class='error'>✗</span> Migration file not found: $file\n";
        $errorCount++;
        echo "</pre>";
        continue;
    }
    
    $sql = file_get_contents($sqlFile);
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && strlen($stmt) > 5;
        }
    );
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $pdo->exec($statement . ';');
            
            if (preg_match('/^(CREATE TABLE|ALTER TABLE|CREATE INDEX)/i', $statement, $matches)) {
                echo "<span class='success'>✓</span> " . $matches[1] . " executed successfully\n";
            }
            $successCount++;
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            
            if (strpos($errorMsg, 'already exists') !== false || 
                strpos($errorMsg, 'Duplicate') !== false) {
                echo "<span class='warning'>⚠</span> Skipped (already exists)\n";
            } else {
                echo "<span class='error'>✗</span> Error: " . $errorMsg . "\n";
                $errorCount++;
            }
        }
    }
    
    echo "</pre>";
}

echo "<h2>Verifying Tables...</h2>";
echo "<pre>";

$tablesToCheck = ['campaign_users', 'campaign_otp_codes'];

foreach ($tablesToCheck as $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    if ($stmt->rowCount() > 0) {
        echo "<span class='success'>✓</span> Table exists: $table\n";
        
        $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "    - {$column['Field']} ({$column['Type']})\n";
        }
        echo "\n";
    } else {
        echo "<span class='error'>✗</span> Table missing: $table\n";
    }
}

echo "</pre>";

echo "<h2 class='success'>Migration Summary</h2>";
echo "<p><strong>Successful statements:</strong> $successCount</p>";
echo "<p><strong>Errors:</strong> $errorCount</p>";

// Create default admin user if none exists
echo "<h2>Creating Default Admin User...</h2>";
echo "<pre>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM campaign_users");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO campaign_users (email, password, fullname, user_type) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin@alertaraqc.com', $defaultPassword, 'System Administrator', 'Super Admin']);
        
        echo "<span class='success'>✓</span> Default admin user created\n";
        echo "    Email: admin@alertaraqc.com\n";
        echo "    Password: admin123\n";
        echo "    <span class='warning'>⚠ Please change this password after first login!</span>\n";
    } else {
        echo "<span class='info'>ℹ</span> Users already exist, skipping default admin creation\n";
    }
} catch (Exception $e) {
    echo "<span class='error'>✗</span> Error creating default admin: " . $e->getMessage() . "\n";
}

echo "</pre>";

echo "<h2 class='success'>✓ Migration Completed!</h2>";
echo "<p>You can now:</p>";
echo "<ul>";
echo "<li>Login at <a href='/login.php' style='color:#4ec9b0;'>/login.php</a></li>";
echo "<li>Manage users at <a href='/public/user_management.php' style='color:#4ec9b0;'>/public/user_management.php</a></li>";
echo "</ul>";

echo "<h3>PHPMailer Setup</h3>";
echo "<p>To enable OTP emails, install PHPMailer and configure SMTP:</p>";
echo "<pre>";
echo "1. Run: composer install\n";
echo "2. Configure SMTP in src/Config/mail_config.php or set environment variables:\n";
echo "   - SMTP_HOST (default: smtp.gmail.com)\n";
echo "   - SMTP_PORT (default: 587)\n";
echo "   - SMTP_USERNAME (your email)\n";
echo "   - SMTP_PASSWORD (your app password)\n";
echo "</pre>";
