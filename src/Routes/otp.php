<?php
/**
 * OTP Routes
 * Handles OTP generation and verification for login
 */

declare(strict_types=1);

use App\Controllers\OTPController;

return [
    [
        'method' => 'POST',
        'path' => '/api/v1/auth/otp/send',
        'handler' => [OTPController::class, 'sendOTP'],
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/auth/otp/verify',
        'handler' => [OTPController::class, 'verifyOTP'],
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/auth/otp/resend',
        'handler' => [OTPController::class, 'resendOTP'],
    ],
];
