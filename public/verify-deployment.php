<?php
// Deployment verification script
header('Content-Type: text/plain');

echo "=== DEPLOYMENT VERIFICATION ===\n\n";

// Check git commit
$gitDir = __DIR__ . '/../.git';
if (file_exists($gitDir . '/HEAD')) {
    $head = trim(file_get_contents($gitDir . '/HEAD'));
    echo "Git HEAD: $head\n";
    
    if (strpos($head, 'ref:') === 0) {
        $ref = substr($head, 5);
        $refFile = $gitDir . '/' . $ref;
        if (file_exists($refFile)) {
            $commit = trim(file_get_contents($refFile));
            echo "Current commit: $commit\n";
        }
    }
}

echo "\n=== REQUIRED COMMIT ===\n";
echo "Expected: b2327c3 (Fix index method WHERE and ORDER BY clauses)\n";

echo "\n=== FILE CHECKS ===\n";

// Check EventController.php for correct column names
$controllerFile = __DIR__ . '/../src/Controllers/EventController.php';
if (file_exists($controllerFile)) {
    $content = file_get_contents($controllerFile);
    
    // Check for correct column names in WHERE clause
    if (strpos($content, "e.status = :filter_event_status") !== false) {
        echo "✓ WHERE clause uses e.status (correct)\n";
    } else if (strpos($content, "e.event_status = :filter_event_status") !== false) {
        echo "✗ WHERE clause uses e.event_status (WRONG - needs update)\n";
    }
    
    // Check for correct column names in ORDER BY
    if (strpos($content, "ORDER BY e.event_date DESC, e.event_time DESC") !== false) {
        echo "✓ ORDER BY uses e.event_date, e.event_time (correct)\n";
    } else if (strpos($content, "ORDER BY e.date DESC, e.start_time DESC") !== false) {
        echo "✗ ORDER BY uses e.date, e.start_time (WRONG - needs update)\n";
    }
    
    // Check viewer filter
    if (strpos($content, "e.status IN ('ongoing', 'completed')") !== false) {
        echo "✓ Viewer filter uses correct ENUM values (correct)\n";
    } else if (strpos($content, "e.event_status IN ('confirmed', 'completed')") !== false) {
        echo "✗ Viewer filter uses wrong column/values (WRONG - needs update)\n";
    }
}

echo "\n=== ACTION REQUIRED ===\n";
echo "If any checks show ✗, run:\n";
echo "cd /var/www/html/safety_campaign_alertaraqc\n";
echo "git fetch origin\n";
echo "git reset --hard origin/main\n";
echo "git log --oneline -1\n";
