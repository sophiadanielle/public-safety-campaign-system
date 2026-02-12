<?php
// Direct events API test - bypasses routing
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../src/Config/db_connect.php';

try {
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
    
    $stmt = $pdo->query($sql);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates
    foreach ($events as &$event) {
        if ($event['date']) {
            $event['date_formatted'] = date('Y-m-d', strtotime($event['date']));
        }
        if ($event['start_time']) {
            $event['start_time_formatted'] = date('H:i', strtotime($event['start_time']));
        }
    }
    
    echo json_encode(['data' => $events], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
}
