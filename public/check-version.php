<?php
header('Content-Type: text/plain');
echo "File: public/events.php\n";
echo "Last modified: " . date('Y-m-d H:i:s', filemtime(__DIR__ . '/events.php')) . "\n\n";

// Check if autocomplete is disabled
$content = file_get_contents(__DIR__ . '/events.php');
if (strpos($content, '// Temporarily disabled - autocomplete endpoints not yet implemented') !== false) {
    echo "✓ Autocomplete fix is PRESENT\n";
} else {
    echo "✗ Autocomplete fix is MISSING\n";
}

// Check git info if available
if (file_exists(__DIR__ . '/../.git/HEAD')) {
    echo "\nGit HEAD: " . trim(file_get_contents(__DIR__ . '/../.git/HEAD')) . "\n";
}
