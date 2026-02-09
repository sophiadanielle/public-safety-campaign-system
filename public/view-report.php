<?php
/**
 * Public report viewer - serves evaluation reports without authentication
 * This file allows viewing generated reports without going through the main application routing
 */

// Get the report file path from query parameter
$reportPath = $_GET['file'] ?? '';

if (empty($reportPath)) {
    http_response_code(400);
    die('Error: No report file specified');
}

// Normalize path separators
$reportPath = str_replace('\\', '/', $reportPath);

// Security: Only allow files from uploads/reports directory
// Must start with uploads/reports/ and end with .html
// Must contain report_campaign_ followed by numbers
if (!preg_match('#^uploads/reports/report_campaign_\d+_\d+\.html$#', $reportPath)) {
    http_response_code(403);
    error_log("Invalid report path attempted: " . $reportPath);
    die('Error: Invalid report path. Received: ' . htmlspecialchars($reportPath) . '. Expected format: uploads/reports/report_campaign_X_YYYYMMDD_HHMMSS.html');
}

// Prevent directory traversal
if (strpos($reportPath, '..') !== false) {
    http_response_code(403);
    die('Error: Invalid report path');
}

// Construct full file path
$fullPath = __DIR__ . '/../' . $reportPath;

// Check if file exists
if (!file_exists($fullPath)) {
    http_response_code(404);
    error_log("Report file not found: " . $fullPath);
    die('Error: Report file not found at: ' . htmlspecialchars($fullPath));
}

// Check if file is readable
if (!is_readable($fullPath)) {
    http_response_code(403);
    error_log("Report file not readable: " . $fullPath);
    die('Error: Report file exists but is not readable');
}

// Serve the HTML file
header('Content-Type: text/html; charset=utf-8');
readfile($fullPath);
