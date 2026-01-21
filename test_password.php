<?php
// Test script to
//  verify password hash and generate a new one if needed

// Test the existing hash
$existingHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$password = 'password123';

echo "Testing password verification:\n";
echo "Password: $password\n";
echo "Existing hash: $existingHash\n";
echo "Verification result: " . (password_verify($password, $existingHash) ? "SUCCESS" : "FAILED") . "\n\n";

// Generate a fresh hash
$newHash = password_hash($password, PASSWORD_DEFAULT);
echo "New hash (for password123): $newHash\n";
echo "Verification with new hash: " . (password_verify($password, $newHash) ? "SUCCESS" : "FAILED") . "\n";


















