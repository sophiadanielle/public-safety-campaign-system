<?php
/**
 * Import Seed Data into lgu Database
 * 
 * This script imports sample/seed data from migrations into the lgu database.
 * Use this if you can't access production data.
 * 
 * Usage: php import_seed_data.php
 */

declare(strict_types=1);

// Database credentials
$localHost = 'localhost';
$targetDb = 'lgu';
$localUser = 'root';
$localPort = 3306;
$passwords = ['Phiarren@182212', ''];

// Connect to MySQL server
$pdo = null;
$connected = false;

foreach ($passwords as $localPass) {
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $localHost, $localPort);
        $pdo = new PDO($dsn, $localUser, $localPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $connected = true;
        break;
    } catch (PDOException $e) {
        continue;
    }
}

if (!$connected) {
    die("ERROR: Cannot connect to MySQL server\n");
}

echo "=== Importing Seed Data into lgu Database ===\n\n";

// Switch to target database
try {
    $pdo->exec("USE `$targetDb`");
    echo "✓ Connected to database: $targetDb\n\n";
} catch (PDOException $e) {
    die("ERROR: Cannot access database $targetDb: " . $e->getMessage() . "\n");
}

// Seed files to import (in order)
$seedFiles = [
    '012_seed_data.sql',
    '013_seed_qc_reference_data.sql',
    '015_content_repository_seed.sql',
];

$migrationDir = __DIR__ . '/migrations';
$importedFiles = 0;
$errors = [];

foreach ($seedFiles as $seedFile) {
    $seedPath = $migrationDir . '/' . $seedFile;
    
    if (!file_exists($seedPath)) {
        echo "  ⚠ Skipping $seedFile (not found)\n";
        continue;
    }
    
    echo "  → Importing $seedFile... ";
    
    try {
        // Read and modify seed file to use lgu instead of LGU
        $sql = file_get_contents($seedPath);
        $sql = str_replace('USE `LGU`;', "USE `$targetDb`;", $sql);
        $sql = str_replace('USE LGU;', "USE `$targetDb`;", $sql);
        
        // Execute SQL
        $pdo->exec($sql);
        echo "✓\n";
        $importedFiles++;
    } catch (PDOException $e) {
        // Some errors are expected (e.g., duplicate entries with INSERT IGNORE)
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "⚠ (some duplicates, but OK)\n";
            $importedFiles++;
        } else {
            echo "✗ ERROR: " . $e->getMessage() . "\n";
            $errors[] = "$seedFile: " . $e->getMessage();
        }
    }
}

// Summary
echo "\n=== Import Complete ===\n";
echo "Files imported: $importedFiles\n";

if (!empty($errors)) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

// Verify data
echo "\n--- Verification ---\n";
try {
    $userCount = $pdo->query("SELECT COUNT(*) FROM campaign_department_users")->fetchColumn();
    $roleCount = $pdo->query("SELECT COUNT(*) FROM campaign_department_roles")->fetchColumn();
    $campaignCount = $pdo->query("SELECT COUNT(*) FROM campaign_department_campaigns")->fetchColumn();
    $barangayCount = $pdo->query("SELECT COUNT(*) FROM campaign_department_barangays")->fetchColumn();
    
    echo "Users: $userCount\n";
    echo "Roles: $roleCount\n";
    echo "Campaigns: $campaignCount\n";
    echo "Barangays: $barangayCount\n";
} catch (PDOException $e) {
    echo "Could not verify data: " . $e->getMessage() . "\n";
}

echo "\n✓ Seed data import completed!\n";
echo "\nYour lgu database now has sample data for testing.\n";



