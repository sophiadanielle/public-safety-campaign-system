<?php
// Check database schema for events table
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $configFile = __DIR__ . '/../src/Config/db_connect.php';
    if (file_exists($configFile)) {
        require_once $configFile;
        
        echo "=== CAMPAIGN_DEPARTMENT_EVENTS TABLE SCHEMA ===\n\n";
        
        $stmt = $pdo->query("DESCRIBE campaign_department_events");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Column Name                | Type                  | Null | Key | Default | Extra\n";
        echo str_repeat("-", 100) . "\n";
        
        foreach ($columns as $col) {
            printf("%-26s | %-21s | %-4s | %-3s | %-7s | %s\n",
                $col['Field'],
                $col['Type'],
                $col['Null'],
                $col['Key'],
                $col['Default'] ?? 'NULL',
                $col['Extra']
            );
        }
        
        echo "\n\n=== SAMPLE DATA (First Row) ===\n\n";
        $stmt = $pdo->query("SELECT * FROM campaign_department_events LIMIT 1");
        $sample = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sample) {
            foreach ($sample as $key => $value) {
                echo "$key: " . ($value ?? 'NULL') . "\n";
            }
        } else {
            echo "No data in table\n";
        }
        
    } else {
        echo "Error: Database config file not found at: $configFile\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
