<?php
header('Content-Type: application/json');
echo json_encode([
    'auth_otp_first_line' => file(__DIR__ . '/auth_otp.php')[6] ?? 'not found',
    'db_connect_first_lines' => array_slice(file(__DIR__ . '/../src/Config/db_connect.php'), 0, 20),
    'git_head' => trim(shell_exec('cd ' . __DIR__ . '/.. && git rev-parse HEAD 2>&1')),
    'git_status' => shell_exec('cd ' . __DIR__ . '/.. && git status 2>&1')
], JSON_PRETTY_PRINT);
