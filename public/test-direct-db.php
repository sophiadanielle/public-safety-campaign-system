<?php
// Direct database test - bypasses all routing
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Direct database config
    $config = require __DIR__ . '/../config/database.php';
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $results = ['status' => 'OK'];
    
    // Test segments
    $stmt = $pdo->query("SELECT id, segment_name AS name, risk_level FROM campaign_department_audience_segments LIMIT 5");
    $results['segments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $results['segments_count'] = count($results['segments']);
    
    // Test events
    $stmt = $pdo->query("SELECT event_id, event_title, event_type, date, venue FROM campaign_department_events LIMIT 5");
    $results['events'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $results['events_count'] = count($results['events']);
    
    // Test campaigns
    $stmt = $pdo->query("SELECT id, title FROM campaign_department_campaigns LIMIT 5");
    $results['campaigns'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $results['campaigns_count'] = count($results['campaigns']);
    
    echo json_encode($results, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
