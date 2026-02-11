<?php
// Simple API diagnostic script
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../header/includes/path_helper.php';

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
    'api_base_path' => $apiPath ?? 'NOT SET',
    'base_path' => $basePath ?? 'NOT SET',
];

// Test database connection
try {
    $config = require __DIR__ . '/../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $results['database_connection'] = 'OK';
    $results['database_name'] = $config['database'];
    $results['database_host'] = $config['host'];
    
    // Test events table
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM campaign_department_events");
        $count = $stmt->fetch();
        $results['events_table'] = 'OK';
        $results['events_count'] = $count['count'];
    } catch (Exception $e) {
        $results['events_table'] = 'ERROR: ' . $e->getMessage();
    }
    
    // Test segments table
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM campaign_department_audience_segments");
        $count = $stmt->fetch();
        $results['segments_table'] = 'OK';
        $results['segments_count'] = $count['count'];
        
        // Get sample segment
        $stmt = $pdo->query("SELECT id, segment_name AS name, risk_level FROM campaign_department_audience_segments LIMIT 1");
        $sample = $stmt->fetch();
        $results['sample_segment'] = $sample;
    } catch (Exception $e) {
        $results['segments_table'] = 'ERROR: ' . $e->getMessage();
    }
    
    // Test campaigns table
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM campaign_department_campaigns");
        $count = $stmt->fetch();
        $results['campaigns_table'] = 'OK';
        $results['campaigns_count'] = $count['count'];
    } catch (Exception $e) {
        $results['campaigns_table'] = 'ERROR: ' . $e->getMessage();
    }
    
    echo json_encode($results, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database connection failed',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
