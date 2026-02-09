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

// Security: Only allow files from uploads/reports directory
if (!preg_match('#^uploads/reports/report_campaign_\d+_\d+\.html$#', $reportPath)) {
    http_response_code(403);
    die('Error: Invalid report path');
}

// Construct full file path
$fullPath = __DIR__ . '/../' . $reportPath;

// Check if file exists
if (!file_exists($fullPath)) {
    http_response_code(404);
    die('Error: Report file not found');
}

// Serve the HTML file
header('Content-Type: text/html; charset=utf-8');
readfile($fullPath);
