<?php
/**
 * Test script to verify Google AutoML integration
 * 
 * Usage: php test_automl.php [campaign_id]
 * 
 * This script tests the AutoML service and shows:
 * - Whether Google AutoML is configured
 * - Which prediction method is being used
 * - The prediction results
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AutoMLService;

// Database connection
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'LGU';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Get campaign ID from command line or use first available campaign
$campaignId = isset($argv[1]) ? (int)$argv[1] : null;

if (!$campaignId) {
    $stmt = $pdo->query('SELECT id, title FROM campaigns ORDER BY id DESC LIMIT 1');
    $campaign = $stmt->fetch();
    if (!$campaign) {
        die("No campaigns found in database. Please create a campaign first.\n");
    }
    $campaignId = $campaign['id'];
    echo "Using Campaign ID: $campaignId - {$campaign['title']}\n\n";
} else {
    $stmt = $pdo->prepare('SELECT id, title FROM campaigns WHERE id = ?');
    $stmt->execute([$campaignId]);
    $campaign = $stmt->fetch();
    if (!$campaign) {
        die("Campaign ID $campaignId not found.\n");
    }
    echo "Testing Campaign ID: $campaignId - {$campaign['title']}\n\n";
}

// Check AutoML configuration
echo "=== Google AutoML Configuration ===\n";
$endpoint = getenv('GOOGLE_AUTOML_ENDPOINT');
$apiKey = getenv('GOOGLE_AUTOML_API_KEY');

echo "GOOGLE_AUTOML_ENDPOINT: " . ($endpoint !== false ? "SET (" . substr($endpoint, 0, 50) . "...)" : "NOT SET") . "\n";
echo "GOOGLE_AUTOML_API_KEY: " . ($apiKey !== false ? "SET (" . substr($apiKey, 0, 10) . "...)" : "NOT SET") . "\n";
echo "Status: " . ($endpoint !== false && $apiKey !== false ? "✓ CONFIGURED" : "⚠ NOT CONFIGURED (will use heuristic fallback)") . "\n\n";

// Initialize AutoML Service
$autoMLService = new AutoMLService($pdo);

// Test prediction
echo "=== Running Prediction ===\n";
try {
    $features = [
        'campaign_category' => 'safety',
        'audience_segment_id' => null,
    ];
    
    echo "Features: " . json_encode($features, JSON_PRETTY_PRINT) . "\n\n";
    
    $prediction = $autoMLService->predict($campaignId, $features);
    
    echo "=== Prediction Results ===\n";
    echo "Model Source: " . ($prediction['model_source'] ?? 'unknown') . "\n";
    echo "Suggested DateTime: " . ($prediction['suggested_datetime'] ?? 'N/A') . "\n";
    echo "Recommended Day: " . ($prediction['recommended_day'] ?? 'N/A') . "\n";
    echo "Recommended Time: " . ($prediction['recommended_time'] ?? 'N/A') . "\n";
    echo "Confidence Score: " . ($prediction['confidence_score'] ?? 'N/A') . "\n";
    
    if (isset($prediction['automl_configured'])) {
        echo "AutoML Configured: " . ($prediction['automl_configured'] ? 'YES' : 'NO') . "\n";
    }
    
    if (isset($prediction['fallback_reason'])) {
        echo "Fallback Reason: " . $prediction['fallback_reason'] . "\n";
    }
    
    echo "\n=== Full Prediction Data ===\n";
    echo json_encode($prediction, JSON_PRETTY_PRINT) . "\n";
    
    echo "\n✓ Prediction completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}








