<?php

declare(strict_types=1);

$secret = getenv('PARTNER_WEBHOOK_SECRET') ?: 'demo_secret';
$raw = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_SIGNATURE'] ?? '';

if (!$sig || !hash_equals($sig, hash_hmac('sha256', $raw, $secret))) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['status' => 'received', 'payload' => json_decode($raw, true)]);





