<?php
declare(strict_types=1);

// Run migration 027: Surveys Module Complete Requirements
$dbHost = 'localhost';
$dbPort = '3306';
$dbUser = 'root';
$dbPass = 'Phiarren@182212';
$dbName = 'lgu';

echo "=== Running Migration 027: Surveys Module Complete Requirements ===\n\n";

// Connect to database
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✓ Connected to database: $dbName\n\n";
} catch (PDOException $e) {
    die("ERROR: Cannot connect to database: " . $e->getMessage() . "\n");
}

// Read migration file
$migrationFile = __DIR__ . '/migrations/027_surveys_module_complete_requirements.sql';
if (!file_exists($migrationFile)) {
    die("ERROR: Migration file not found: $migrationFile\n");
}

echo "Reading migration file: $migrationFile\n";
$sql = file_get_contents($migrationFile);

// Execute migration
try {
    echo "Executing migration...\n";
    $pdo->exec($sql);
    echo "✓ Migration 027 applied successfully!\n\n";
} catch (PDOException $e) {
    // Check if error is about existing objects
    if (strpos($e->getMessage(), 'already exists') !== false || 
        strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "⚠ Warning: Some objects already exist (this is OK if migration was partially run)\n";
        echo "Error: " . $e->getMessage() . "\n\n";
    } else {
        die("ERROR: Migration failed: " . $e->getMessage() . "\n");
    }
}

// Verify tables were created
echo "=== Verifying Survey Tables ===\n";
$tables = [
    'campaign_department_surveys',
    'campaign_department_survey_questions',
    'campaign_department_survey_responses',
    'campaign_department_survey_response_details',
    'campaign_department_survey_aggregated_results',
    'campaign_department_survey_audit_log',
    'campaign_department_survey_integration_checkpoints',
];

foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    if ($stmt->rowCount() > 0) {
        echo "✓ $table exists\n";
    } else {
        echo "✗ $table NOT FOUND\n";
    }
}

// Check for view
echo "\n=== Verifying Survey View ===\n";
$stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_lgu LIKE '%survey%'");
$views = $stmt->fetchAll();
if (count($views) > 0) {
    foreach ($views as $view) {
        echo "✓ View: " . $view['Tables_in_lgu'] . "\n";
    }
} else {
    echo "⚠ No survey views found\n";
}

echo "\n=== Migration Complete ===\n";
echo "Survey module integration infrastructure is now ready!\n";
echo "See SURVEY_MODULE_INTEGRATION_GUIDE.md for details.\n";




