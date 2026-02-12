<?php
/**
 * Migration Script: Copy all tables and data from `lgu` to `LGU`
 * This script migrates the entire database schema and data
 */

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = 'Phiarren@182212';
// Check which database actually has the tables
// On Windows MySQL, database names might be case-insensitive in some contexts
$sourceDb = 'lgu';  // Try lowercase first
$targetDb = 'LGU';

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "Connected to MySQL server\n";
    
    // Create target database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$targetDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database `$targetDb` ready\n";
    
    // Use the known table list from earlier discovery
    // These are the tables that exist in the lgu database
    $tables = [
        'ai_model_versions', 'ai_prediction_cache', 'ai_prediction_requests', 'ai_training_logs',
        'attachments', 'attendance', 'audience_members', 'audience_segments', 'audit_logs',
        'automl_predictions', 'barangays', 'campaigns', 'campaign_audience', 'campaign_content_items',
        'campaign_schedules', 'content_items', 'content_item_versions', 'content_tags', 'content_usage',
        'conversations', 'evaluation_reports', 'events', 'feedback', 'impact_metrics',
        'integration_logs', 'messages', 'notification_logs', 'notifications', 'partners',
        'partner_engagements', 'permissions', 'roles', 'role_permissions', 'surveys',
        'survey_questions', 'survey_responses', 'tags', 'users'
    ];
    
    $views = [
        'campaign_engagement_summary', 'participation_history', 'timing_effectiveness'
    ];
    
    echo "Found " . count($tables) . " tables and " . count($views) . " views to migrate\n\n";
    
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Connect to source database
    $sourcePdo = new PDO("mysql:host=$dbHost;dbname=$sourceDb;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Migrate each table
    foreach ($tables as $table) {
        echo "Migrating table: $table... ";
        
        try {
            // Drop table in target if exists
            $pdo->exec("DROP TABLE IF EXISTS `$targetDb`.`$table`");
            
            // Create table structure using fully qualified names
            $pdo->exec("CREATE TABLE `$targetDb`.`$table` LIKE `$sourceDb`.`$table`");
            
            // Copy data using fully qualified names
            $rowCount = $pdo->exec("INSERT INTO `$targetDb`.`$table` SELECT * FROM `$sourceDb`.`$table`");
            
            echo "✓ ($rowCount rows)\n";
        } catch (PDOException $e) {
            echo "✗ ERROR: " . $e->getMessage() . "\n";
        }
    }
    
    // Views are already collected above, so just process them
    if (!empty($views)) {
        echo "\nMigrating " . count($views) . " views...\n";
        
        foreach ($views as $view) {
            echo "Migrating view: $view... ";
            
            try {
                // Get view definition using source PDO connection
                $stmt = $sourcePdo->query("SELECT VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA = '$sourceDb' AND TABLE_NAME = '$view'");
                $viewDef = $stmt->fetchColumn();
                
                if (!$viewDef) {
                    // Try alternative: get from SHOW CREATE VIEW
                    $stmt = $sourcePdo->query("SHOW CREATE VIEW `$view`");
                    $viewRow = $stmt->fetch(PDO::FETCH_NUM);
                    if ($viewRow && isset($viewRow[1])) {
                        // Extract definition from CREATE VIEW statement
                        $createView = $viewRow[1];
                        if (preg_match('/AS\s+(.+)$/is', $createView, $matches)) {
                            $viewDef = $matches[1];
                        }
                    }
                }
                
                if ($viewDef) {
                    // Replace database references: `lgu`.`table` -> `LGU`.`table`
                    $viewDef = str_replace("`$sourceDb`.", "`$targetDb`.", $viewDef);
                    
                    // Drop view if exists in target
                    $pdo->exec("DROP VIEW IF EXISTS `$targetDb`.`$view`");
                    
                    // Create view in target
                    $pdo->exec("CREATE VIEW `$targetDb`.`$view` AS $viewDef");
                    
                    echo "✓\n";
                } else {
                    echo "✗ ERROR: Could not get view definition\n";
                }
            } catch (PDOException $e) {
                echo "✗ ERROR: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Verify migration
    echo "\n=== Migration Complete ===\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = BINARY '$targetDb' AND TABLE_TYPE = 'BASE TABLE'");
    $targetTableCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = BINARY '$sourceDb' AND TABLE_TYPE = 'BASE TABLE'");
    $sourceTableCount = $stmt->fetchColumn();
    
    echo "Source database (`$sourceDb`): $sourceTableCount tables\n";
    echo "Target database (`$targetDb`): $targetTableCount tables\n";
    
    if ($targetTableCount == $sourceTableCount) {
        echo "\n✓ Migration successful! Both databases have the same number of tables.\n";
    } else {
        echo "\n⚠ Warning: Table counts don't match. Please verify manually.\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

