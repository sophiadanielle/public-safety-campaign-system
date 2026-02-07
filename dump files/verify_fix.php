<?php
/**
 * Quick Verification Script
 * Upload this to production and visit: https://campaign.alertaraqc.com/verify_fix.php
 * This will tell you if the fix is active or not
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Verification</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .status { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .good { background: #d1fae5; border-left: 4px solid #10b981; }
        .bad { background: #fee2e2; border-left: 4px solid #ef4444; }
        .info { background: #dbeafe; border-left: 4px solid #3b82f6; }
        pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 4px; overflow-x: auto; }
        h2 { margin-top: 30px; }
    </style>
</head>
<body>
    <h1>🔍 Production Fix Verification</h1>
    
    <?php
    // Check 1: HTTP_HOST
    $host = strtolower($_SERVER['HTTP_HOST'] ?? 'NOT SET');
    echo "<div class='info status'>";
    echo "<strong>HTTP_HOST:</strong> $host";
    echo "</div>";
    
    // Check 2: Is this production?
    $isProduction = (
        strpos($host, 'alertaraqc.com') !== false ||
        strpos($host, 'campaign.') !== false ||
        ($host !== 'NOT SET' && $host !== '' && strpos($host, 'localhost') === false && $host !== '127.0.0.1')
    );
    
    echo "<div class='" . ($isProduction ? 'good' : 'bad') . " status'>";
    echo "<strong>Production Detected:</strong> " . ($isProduction ? 'YES ✓' : 'NO ✗');
    echo "</div>";
    
    // Check 3: Test path_helper.php
    unset($basePath, $apiPath, $cssPath, $imgPath, $publicPath);
    require_once __DIR__ . '/header/includes/path_helper.php';
    
    $basePathValue = $basePath ?? 'NOT SET';
    $expectedValue = $isProduction ? '' : '/public-safety-campaign-system';
    $isCorrect = ($basePathValue === $expectedValue);
    
    echo "<div class='" . ($isCorrect ? 'good' : 'bad') . " status'>";
    echo "<strong>BasePath Value:</strong><br>";
    echo "Expected: <code>$expectedValue</code><br>";
    echo "Actual: <code>$basePathValue</code><br>";
    echo "Status: " . ($isCorrect ? '✓ CORRECT' : '✗ INCORRECT');
    echo "</div>";
    
    // Check 4: Check if early detection code exists in index.php
    $indexPath = __DIR__ . '/index.php';
    $indexContent = file_exists($indexPath) ? file_get_contents($indexPath) : '';
    $hasEarlyDetection = strpos($indexContent, 'FORCE_PRODUCTION_BASEPATH') !== false;
    
    echo "<div class='" . ($hasEarlyDetection ? 'good' : 'bad') . " status'>";
    echo "<strong>Early Detection Code in index.php:</strong> " . ($hasEarlyDetection ? 'YES ✓' : 'NO ✗');
    if (!$hasEarlyDetection) {
        echo "<br><small>⚠️ index.php needs to be updated with the fix</small>";
    }
    echo "</div>";
    
    // Check 5: Check if global flag check exists in path_helper.php
    $pathHelperContent = file_exists(__DIR__ . '/header/includes/path_helper.php') 
        ? file_get_contents(__DIR__ . '/header/includes/path_helper.php') 
        : '';
    $hasGlobalFlag = strpos($pathHelperContent, 'FORCE_PRODUCTION_BASEPATH') !== false;
    
    echo "<div class='" . ($hasGlobalFlag ? 'good' : 'bad') . " status'>";
    echo "<strong>Global Flag Check in path_helper.php:</strong> " . ($hasGlobalFlag ? 'YES ✓' : 'NO ✗');
    if (!$hasGlobalFlag) {
        echo "<br><small>⚠️ path_helper.php needs to be updated with the fix</small>";
    }
    echo "</div>";
    
    // Final verdict
    echo "<h2>📋 Summary</h2>";
    if ($isCorrect && $hasEarlyDetection && $hasGlobalFlag) {
        echo "<div class='good status'>";
        echo "<strong>✅ ALL CHECKS PASSED</strong><br>";
        echo "The fix is active and working correctly!";
        echo "</div>";
    } else {
        echo "<div class='bad status'>";
        echo "<strong>❌ ACTION REQUIRED</strong><br>";
        echo "<ul>";
        if (!$isCorrect) {
            echo "<li>BasePath is incorrect - files may need to be updated</li>";
        }
        if (!$hasEarlyDetection) {
            echo "<li>index.php is missing the early detection code - needs update</li>";
        }
        if (!$hasGlobalFlag) {
            echo "<li>path_helper.php is missing the global flag check - needs update</li>";
        }
        echo "</ul>";
        echo "<strong>Next Steps:</strong><br>";
        echo "1. Upload the updated <code>index.php</code> and <code>header/includes/path_helper.php</code><br>";
        echo "2. Clear PHP cache: <code>sudo service php-fpm reload</code><br>";
        echo "3. Hard refresh browser: <code>Ctrl+Shift+R</code>";
        echo "</div>";
    }
    
    // Show what JavaScript will see
    echo "<h2>🔧 JavaScript Output</h2>";
    echo "<div class='info status'>";
    echo "<pre>const basePath = '<?php echo $basePath ?? ''; ?>';</pre>";
    echo "<p>Browser console will show: <code>BASE PATH: <?php echo $basePath ?? ''; ?></code></p>";
    echo "</div>";
    ?>
    
    <script>
    const basePath = '<?php echo $basePath ?? ''; ?>';
    console.log('=== VERIFICATION ===');
    console.log('BASE PATH:', basePath);
    console.log('Expected (production):', '');
    console.log('Match:', basePath === '' ? '✓ YES' : '✗ NO');
    </script>
</body>
</html>

