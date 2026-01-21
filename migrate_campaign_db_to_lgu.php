<?php
/**
 * Migrate campaign_db to lgu database
 * 
 * This script:
 * 1. Creates the proper schema in lgu database using migrations (with campaign_department_ prefix)
 * 2. Copies data from campaign_db (non-prefixed tables) to lgu (prefixed tables)
 * 
 * Usage: php migrate_campaign_db_to_lgu.php
 */

declare(strict_types=1);

// Database credentials
$localHost = 'localhost';
$localUser = 'root';
$localPort = 3306;
$passwords = ['Phiarren@182212', ''];

$sourceDb = 'campaign_db';
$targetDb = 'lgu';

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

echo "=== Migrating campaign_db to lgu database ===\n\n";

// Step 1: Create lgu database if it doesn't exist
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$targetDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '$targetDb' ready\n";
} catch (PDOException $e) {
    die("ERROR: Cannot create database: " . $e->getMessage() . "\n");
}

// Step 2: Run migrations to create schema in lgu
echo "\n--- Step 1: Running migrations to create schema ---\n";
$migrationDir = __DIR__ . '/migrations';
$migrations = [
    '001_initial_schema.sql',
    '011_complete_schema_update.sql',
    '004_content_extensions.sql',
    '006_survey_status.sql',
    '007_evaluation_reports.sql',
    '008_schedule_status.sql',
    '009_links_and_enrichment.sql',
    '010_campaign_planning_fields.sql',
    '014_content_repository.sql',
    '016_segments_module_update.sql',
    '017_events_module_enhancement.sql',
    '018_notifications_system.sql',
    '019_messaging_system.sql',
    '020_automl_integration.sql',
    '025_events_module_complete_requirements.sql',
    '026_external_system_integration.sql',
    '027_surveys_module_complete_requirements.sql',
];

$pdo->exec("USE `$targetDb`");

foreach ($migrations as $migrationFile) {
    $migrationPath = $migrationDir . '/' . $migrationFile;
    if (!file_exists($migrationPath)) {
        echo "  ⚠ Skipping $migrationFile (not found)\n";
        continue;
    }
    
    echo "  → Running $migrationFile... ";
    
    try {
        // Read and modify migration to use lgu instead of LGU
        $sql = file_get_contents($migrationPath);
        $sql = str_replace('USE `LGU`;', "USE `$targetDb`;", $sql);
        $sql = str_replace('USE LGU;', "USE `$targetDb`;", $sql);
        
        // Execute migration
        $pdo->exec($sql);
        echo "✓\n";
    } catch (PDOException $e) {
        // Some errors are expected (e.g., table already exists, column already exists)
        if (strpos($e->getMessage(), 'already exists') !== false || 
            strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⚠ (already exists)\n";
        } else {
            echo "✗ ERROR: " . $e->getMessage() . "\n";
        }
    }
}

// Step 3: Map source tables (campaign_db, no prefix) to target tables (lgu, with prefix)
echo "\n--- Step 2: Copying data from campaign_db to lgu ---\n";

$tableMapping = [
    // Core tables
    'users' => 'campaign_department_users',
    'roles' => 'campaign_department_roles',
    'permissions' => 'campaign_department_permissions',
    'role_permissions' => 'campaign_department_role_permissions',
    'barangays' => 'campaign_department_barangays',
    
    // Campaign tables
    'campaigns' => 'campaign_department_campaigns',
    'campaign_schedules' => 'campaign_department_campaign_schedules',
    'campaign_audience' => 'campaign_department_campaign_audience',
    
    // Content tables
    'content_items' => 'campaign_department_content_items',
    'content_tags' => 'campaign_department_content_tags',
    'content_usage' => 'campaign_department_content_usage',
    'tags' => 'campaign_department_tags',
    'attachments' => 'campaign_department_attachments',
    
    // Segments tables
    'audience_segments' => 'campaign_department_audience_segments',
    'audience_members' => 'campaign_department_audience_members',
    
    // Events tables
    'events' => 'campaign_department_events',
    'attendance' => 'campaign_department_attendance',
    
    // Surveys tables
    'surveys' => 'campaign_department_surveys',
    'survey_questions' => 'campaign_department_survey_questions',
    'survey_responses' => 'campaign_department_survey_responses',
    
    // Other tables
    'partners' => 'campaign_department_partners',
    'partner_engagements' => 'campaign_department_partner_engagements',
    'evaluation_reports' => 'campaign_department_evaluation_reports',
    'impact_metrics' => 'campaign_department_impact_metrics',
    'audit_logs' => 'campaign_department_audit_logs',
    'notifications' => 'campaign_department_notifications',
    'notification_logs' => 'campaign_department_notification_logs',
    'integration_logs' => 'campaign_department_integration_logs',
    'automl_predictions' => 'campaign_department_automl_predictions',
];

$copiedTables = 0;
$copiedRows = 0;
$errors = [];

// Switch to source database to get table list
try {
    $pdo->exec("USE `$sourceDb`");
    $sourceTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($sourceTables) . " tables in $sourceDb\n\n";
} catch (PDOException $e) {
    die("ERROR: Cannot access source database $sourceDb: " . $e->getMessage() . "\n");
}

// Switch back to target database
$pdo->exec("USE `$targetDb`");

foreach ($tableMapping as $sourceTable => $targetTable) {
    if (!in_array($sourceTable, $sourceTables)) {
        echo "  ⚠ Skipping $sourceTable → $targetTable (source table not found)\n";
        continue;
    }
    
    echo "  → Copying $sourceTable → $targetTable... ";
    
    try {
        // Check if target table exists
        $targetExists = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$targetDb' AND TABLE_NAME = '$targetTable'")->fetchColumn();
        if (!$targetExists) {
            echo "SKIPPED (target table doesn't exist)\n";
            continue;
        }
        
        // Get source data
        $pdo->exec("USE `$sourceDb`");
        $sourceData = $pdo->query("SELECT * FROM `$sourceTable`")->fetchAll(PDO::FETCH_ASSOC);
        $rowCount = count($sourceData);
        
        if ($rowCount > 0) {
            // Get column names
            $columns = array_keys($sourceData[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';
            
            // Switch to target database
            $pdo->exec("USE `$targetDb`");
            
            // Clear target table first
            $pdo->exec("DELETE FROM `$targetTable`");
            
            // Insert data in batches
            $batchSize = 500;
            for ($i = 0; $i < $rowCount; $i += $batchSize) {
                $batch = array_slice($sourceData, $i, $batchSize);
                
                $values = [];
                foreach ($batch as $row) {
                    $rowValues = array_map(function($value) use ($pdo) {
                        if ($value === null) {
                            return 'NULL';
                        }
                        return $pdo->quote($value);
                    }, array_values($row));
                    $values[] = '(' . implode(', ', $rowValues) . ')';
                }
                
                $sql = "INSERT INTO `$targetTable` ($columnList) VALUES " . implode(', ', $values);
                $pdo->exec($sql);
            }
            
            echo "OK ($rowCount rows)\n";
            $copiedRows += $rowCount;
        } else {
            echo "OK (0 rows)\n";
        }
        
        $copiedTables++;
        
    } catch (PDOException $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        $errors[] = "$sourceTable → $targetTable: " . $e->getMessage();
    }
}

// Summary
echo "\n=== Migration Complete ===\n";
echo "Tables copied: $copiedTables\n";
echo "Total rows copied: $copiedRows\n";

if (!empty($errors)) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

// Verify
echo "\n--- Verification ---\n";
$pdo->exec("USE `$targetDb`");
$targetTableCount = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$targetDb' AND TABLE_TYPE = 'BASE TABLE'")->fetchColumn();
echo "Tables in $targetDb: $targetTableCount\n";

echo "\n✓ Migration completed!\n";




