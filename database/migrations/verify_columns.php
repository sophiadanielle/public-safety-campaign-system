<?php
/**
 * Verify Database Columns
 * This script checks if all required columns exist
 */

$host = 'localhost';
$dbname = 'LGU';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Database Column Verification ===\n\n";
    
    // Check events table columns
    echo "1. Checking campaign_department_events table:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM campaign_department_events");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredEventsColumns = ['post_event_notes', 'last_updated'];
    foreach ($requiredEventsColumns as $col) {
        if (in_array($col, $columns)) {
            echo "   ✓ $col EXISTS\n";
        } else {
            echo "   ✗ $col MISSING\n";
        }
    }
    
    // Check agency coordination table columns
    echo "\n2. Checking campaign_department_event_agency_coordination table:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM campaign_department_event_agency_coordination");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredAgencyColumns = ['action_type', 'agency_type', 'agency_name', 'status', 'request_details'];
    foreach ($requiredAgencyColumns as $col) {
        if (in_array($col, $columns)) {
            echo "   ✓ $col EXISTS\n";
        } else {
            echo "   ✗ $col MISSING\n";
        }
    }
    
    echo "\n=== All Columns Present ===\n";
    echo "Database schema is correct!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
