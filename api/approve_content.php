<?php
/**
 * Minimal endpoint to approve content materials
 * Only updates approval_status field - no other changes
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get content ID from request
$input = json_decode(file_get_contents('php://input'), true);
$contentId = isset($input['id']) ? (int)$input['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : null);

if (!$contentId) {
    http_response_code(400);
    echo json_encode(['error' => 'Content ID is required']);
    exit;
}

try {
    // Only update approval_status to 'approved'
    $stmt = $mysqli->prepare("UPDATE campaign_department_content_items SET approval_status = 'approved' WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
    }
    
    $stmt->bind_param('i', $contentId);
    
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Content not found']);
        $stmt->close();
        exit;
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Content approved successfully',
        'id' => $contentId
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

