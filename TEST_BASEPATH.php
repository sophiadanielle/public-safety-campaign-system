<?php
/**
 * SIMPLE TEST: Check if basePath is being set correctly
 * Upload to production and visit: https://campaign.alertaraqc.com/TEST_BASEPATH.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>BasePath Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .test { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #4c8a89; }
        .pass { border-color: #10b981; }
        .fail { border-color: #ef4444; }
        pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>BasePath Detection Test</h1>
    
    <?php
    // Test 1: Check HTTP_HOST
    $host = strtolower($_SERVER['HTTP_HOST'] ?? 'NOT SET');
    echo "<div class='test'>";
    echo "<h3>Test 1: HTTP_HOST</h3>";
    echo "<pre>HTTP_HOST: $host</pre>";
    echo "</div>";
    
    // Test 2: Check if production should be detected
    $isProduction = (
        strpos($host, 'alertaraqc.com') !== false ||
        strpos($host, 'campaign.') !== false ||
        ($host !== 'NOT SET' && $host !== '' && strpos($host, 'localhost') === false && $host !== '127.0.0.1')
    );
    
    echo "<div class='test " . ($isProduction ? 'pass' : 'fail') . "'>";
    echo "<h3>Test 2: Production Detection</h3>";
    echo "<pre>Should be production: " . ($isProduction ? 'YES ✓' : 'NO ✗') . "</pre>";
    echo "</div>";
    
    // Test 3: Include path_helper and check result
    unset($basePath, $apiPath, $cssPath, $imgPath, $publicPath);
    require_once __DIR__ . '/header/includes/path_helper.php';
    
    $basePathValue = $basePath ?? 'NOT SET';
    $expectedValue = $isProduction ? '' : '/public-safety-campaign-system';
    $isCorrect = ($basePathValue === $expectedValue);
    
    echo "<div class='test " . ($isCorrect ? 'pass' : 'fail') . "'>";
    echo "<h3>Test 3: BasePath After path_helper.php</h3>";
    echo "<pre>";
    echo "Expected: '$expectedValue'\n";
    echo "Actual:   '$basePathValue'\n";
    echo "Status:   " . ($isCorrect ? '✓ CORRECT' : '✗ INCORRECT');
    echo "</pre>";
    echo "</div>";
    
    // Test 4: Check JavaScript output
    echo "<div class='test'>";
    echo "<h3>Test 4: JavaScript Output (what browser sees)</h3>";
    echo "<pre>";
    echo "const basePath = '<?php echo $basePath; ?>';\n";
    echo "// This will output: const basePath = '" . ($basePath ?? '') . "';";
    echo "</pre>";
    echo "</div>";
    
    // Test 5: Show all server variables
    echo "<div class='test'>";
    echo "<h3>Test 5: All Server Variables</h3>";
    echo "<pre>";
    echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n";
    echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NOT SET') . "\n";
    echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
    echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "\n";
    echo "</pre>";
    echo "</div>";
    
    // Final verdict
    echo "<div class='test " . ($isCorrect ? 'pass' : 'fail') . "'>";
    echo "<h2>" . ($isCorrect ? "✓ ALL TESTS PASSED" : "✗ TESTS FAILED") . "</h2>";
    if (!$isCorrect) {
        echo "<p><strong>Action Required:</strong></p>";
        echo "<ul>";
        echo "<li>Check that updated files are uploaded to production</li>";
        echo "<li>Clear PHP opcode cache: <code>sudo service php-fpm reload</code></li>";
        echo "<li>Check server error logs for path_helper.php messages</li>";
        echo "</ul>";
    }
    echo "</div>";
    ?>
    
    <script>
    // Test what JavaScript actually sees
    const basePath = '<?php echo $basePath ?? ''; ?>';
    console.log('BASE PATH (from PHP):', basePath);
    console.log('Expected (production):', '');
    console.log('Match:', basePath === '' ? '✓ YES' : '✗ NO');
    </script>
</body>
</html>

