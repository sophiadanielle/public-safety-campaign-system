<?php
/**
 * System Status Checker
 * Shows current git commit, database columns, and PHP file versions
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>System Status Check</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}pre{background:#2d2d2d;padding:10px;overflow-x:auto;}.success{color:#4ec9b0;}.error{color:#f48771;}.warning{color:#dcdcaa;}</style>";

echo "<h2>1. Git Status</h2>";
echo "<pre>";
$gitDir = __DIR__ . '/..';
chdir($gitDir);
$currentCommit = trim(shell_exec('git rev-parse --short HEAD 2>&1'));
$latestCommit = '922002a'; // Expected latest commit
echo "Current commit: <strong>$currentCommit</strong>\n";
echo "Expected commit: <strong>$latestCommit</strong>\n";
if ($currentCommit === $latestCommit) {
    echo "<span class='success'>✓ Code is up to date</span>\n";
} else {
    echo "<span class='error'>✗ Code is OUTDATED - run: git pull origin main</span>\n";
}
echo "</pre>";

echo "<h2>2. Database Columns</h2>";
require_once __DIR__ . '/../src/Config/db_connect.php';

echo "<h3>campaign_department_attendance:</h3>";
echo "<pre>";
try {
    $stmt = $pdo->query("DESCRIBE campaign_department_attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $requiredCols = ['participant_identifier', 'checkin_method', 'checkin_timestamp', 'check_in'];
    foreach ($requiredCols as $col) {
        $exists = false;
        foreach ($columns as $dbCol) {
            if ($dbCol['Field'] === $col) {
                $exists = true;
                echo "<span class='success'>✓</span> $col exists (Type: {$dbCol['Type']}, Default: " . ($dbCol['Default'] ?? 'NULL') . ")\n";
                break;
            }
        }
        if (!$exists) {
            echo "<span class='error'>✗</span> $col MISSING\n";
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>Error: " . $e->getMessage() . "</span>\n";
}
echo "</pre>";

echo "<h3>campaign_department_event_agency_coordination:</h3>";
echo "<pre>";
try {
    $stmt = $pdo->query("DESCRIBE campaign_department_event_agency_coordination");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $requiredCols = ['action_type', 'agency_type', 'status'];
    foreach ($requiredCols as $col) {
        $exists = false;
        foreach ($columns as $dbCol) {
            if ($dbCol['Field'] === $col) {
                $exists = true;
                echo "<span class='success'>✓</span> $col exists (Type: {$dbCol['Type']})\n";
                break;
            }
        }
        if (!$exists) {
            echo "<span class='error'>✗</span> $col MISSING\n";
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>Error: " . $e->getMessage() . "</span>\n";
}
echo "</pre>";

echo "<h2>3. Test Queries</h2>";
echo "<pre>";

// Test attendance insert
try {
    $testEventId = 3; // Use existing event
    $pdo->exec("INSERT INTO campaign_department_attendance (event_id, audience_member_id, participant_identifier, checkin_method, checkin_timestamp, check_in) VALUES ($testEventId, NULL, 'TEST', 'manual', NOW(), 1)");
    $lastId = $pdo->lastInsertId();
    echo "<span class='success'>✓</span> Attendance INSERT works (ID: $lastId)\n";
    // Clean up
    $pdo->exec("DELETE FROM campaign_department_attendance WHERE attendance_id = $lastId");
} catch (Exception $e) {
    echo "<span class='error'>✗</span> Attendance INSERT failed: " . $e->getMessage() . "\n";
}

// Test attendance select
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM campaign_department_attendance WHERE event_id = 3");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<span class='success'>✓</span> Attendance SELECT works (Count: {$result['count']})\n";
} catch (Exception $e) {
    echo "<span class='error'>✗</span> Attendance SELECT failed: " . $e->getMessage() . "\n";
}

// Test agency coordination insert
try {
    $pdo->exec("INSERT INTO campaign_department_event_agency_coordination (event_id, agency_type, agency_name, status, request_details) VALUES (3, 'fire', 'TEST', 'pending', 'test')");
    $lastId = $pdo->lastInsertId();
    echo "<span class='success'>✓</span> Agency Coordination INSERT works (ID: $lastId)\n";
    // Clean up
    $pdo->exec("DELETE FROM campaign_department_event_agency_coordination WHERE id = $lastId");
} catch (Exception $e) {
    echo "<span class='error'>✗</span> Agency Coordination INSERT failed: " . $e->getMessage() . "\n";
}

echo "</pre>";

echo "<h2>Summary</h2>";
echo "<p>If you see errors above:</p>";
echo "<ol>";
echo "<li>Run: <code>git pull origin main</code> on the server</li>";
echo "<li>Run the SQL migrations if columns are missing</li>";
echo "<li>Refresh this page to verify fixes</li>";
echo "</ol>";
echo "<p><a href='/public/events.php' style='color:#4ec9b0;'>Go to Events Page</a></p>";
