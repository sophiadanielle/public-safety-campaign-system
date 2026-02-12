<?php
// Debug Events API - shows SQL query and results
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../src/Config/db_connect.php';

echo "<h1>Events API Debug</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}pre{background:#2d2d2d;padding:10px;overflow-x:auto;}.success{color:#4ec9b0;}.error{color:#f48771;}</style>";

try {
    // Build the same SQL query as EventController
    $sql = "
        SELECT 
            e.id as event_id,
            e.name as event_title,
            e.name as event_name,
            e.event_type,
            e.description as event_description,
            e.event_date as date,
            e.event_time as start_time,
            e.event_time as end_time,
            e.venue,
            e.location,
            e.status as event_status,
            e.created_at,
            c.title as campaign_title
        FROM `campaign_department_events` e
        LEFT JOIN `campaign_department_campaigns` c ON c.id = e.linked_campaign_id
        ORDER BY e.event_date DESC, e.event_time DESC
        LIMIT 100
    ";
    
    echo "<h2>SQL Query:</h2>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    echo "<h2>Executing Query...</h2>";
    $stmt = $pdo->query($sql);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2 class='success'>Query executed successfully!</h2>";
    echo "<p>Events found: <strong>" . count($events) . "</strong></p>";
    
    if (count($events) > 0) {
        echo "<h2>Events Data:</h2>";
        echo "<pre>" . htmlspecialchars(json_encode($events, JSON_PRETTY_PRINT)) . "</pre>";
    } else {
        echo "<h2 class='error'>No events found!</h2>";
        
        // Check if table has any data at all
        echo "<h3>Checking if events table has any rows...</h3>";
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM campaign_department_events");
        $count = $countStmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total rows in campaign_department_events: <strong>" . $count['count'] . "</strong></p>";
        
        if ($count['count'] > 0) {
            echo "<h3>Sample raw data from table:</h3>";
            $rawStmt = $pdo->query("SELECT * FROM campaign_department_events LIMIT 5");
            $rawData = $rawStmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre>" . htmlspecialchars(json_encode($rawData, JSON_PRETTY_PRINT)) . "</pre>";
        }
    }
    
} catch (PDOException $e) {
    echo "<h2 class='error'>SQL Error!</h2>";
    echo "<p>Error message: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Error code: " . $e->getCode() . "</p>";
}
