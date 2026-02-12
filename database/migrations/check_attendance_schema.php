<?php
// Check actual attendance table structure
require_once __DIR__ . '/../../src/Config/db_connect.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Attendance Table Schema</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}pre{background:#2d2d2d;padding:10px;overflow-x:auto;}.success{color:#4ec9b0;}</style>";

try {
    $stmt = $pdo->query("DESCRIBE campaign_department_attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Columns:</h2>";
    echo "<pre>";
    foreach ($columns as $col) {
        echo sprintf("%-30s %-20s %-10s %-10s %s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'], 
            $col['Key'],
            $col['Default'] ?? 'NULL'
        );
    }
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color:#f48771;'>Error: " . $e->getMessage() . "</p>";
}
