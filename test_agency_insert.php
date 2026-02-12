<?php
/**
 * Test Agency Coordination Insert
 * This directly tests the INSERT statement to identify the exact error
 */

$host = 'localhost';
$dbname = 'LGU';
$username = 'root';

echo "Enter MySQL root password: ";
$password = trim(fgets(STDIN));

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "\n✓ Connected to database\n\n";
    
    // Test 1: Show table structure
    echo "=== Table Structure ===\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM campaign_department_event_agency_coordination");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['Field']} - {$row['Type']}\n";
    }
    
    // Test 2: Try the exact INSERT from the code
    echo "\n=== Testing INSERT Statement ===\n";
    echo "Using the exact statement from EventController.php...\n";
    
    $sql = "INSERT INTO `campaign_department_event_agency_coordination` (
        event_id, agency_type, agency_name, status, request_details
    ) VALUES (
        :event_id, :agency_type, :agency_name, 'pending', :request_details
    )";
    
    echo "SQL: " . str_replace("\n", " ", $sql) . "\n\n";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'event_id' => 1,
            'agency_type' => 'police',
            'agency_name' => 'Test Agency',
            'request_details' => 'Test request'
        ]);
        
        $id = $pdo->lastInsertId();
        echo "✓ INSERT successful! ID: $id\n";
        
        // Clean up test data
        $pdo->exec("DELETE FROM campaign_department_event_agency_coordination WHERE id = $id");
        echo "✓ Test data cleaned up\n";
        
    } catch (PDOException $e) {
        echo "✗ INSERT failed!\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "\nThis is the exact error your application is seeing.\n";
    }
    
    // Test 3: Check for triggers
    echo "\n=== Checking for Triggers ===\n";
    $stmt = $pdo->query("
        SELECT TRIGGER_NAME, EVENT_MANIPULATION, ACTION_TIMING
        FROM INFORMATION_SCHEMA.TRIGGERS
        WHERE EVENT_OBJECT_SCHEMA = 'LGU'
          AND EVENT_OBJECT_TABLE = 'campaign_department_event_agency_coordination'
    ");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($triggers)) {
        echo "  No triggers found\n";
    } else {
        foreach ($triggers as $trigger) {
            echo "  - {$trigger['TRIGGER_NAME']} ({$trigger['ACTION_TIMING']} {$trigger['EVENT_MANIPULATION']})\n";
        }
    }
    
} catch (PDOException $e) {
    echo "\n✗ Connection error: " . $e->getMessage() . "\n";
    exit(1);
}
