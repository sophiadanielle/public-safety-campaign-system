<?php
/**
 * PHPMailer Configuration
 * Configure your SMTP settings here for OTP email delivery
 */

return [
    'smtp_host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'smtp_port' => getenv('SMTP_PORT') ?: 587,
    'smtp_username' => getenv('SMTP_USERNAME') ?: 'dxjolomamaril@gmail.com',
    'smtp_password' => getenv('SMTP_PASSWORD') ?: 'btyd vamg uraa jnsi',
    'smtp_encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
    'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'noreply@alertaraqc.com',
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'Alertara QC',
    'reply_to_email' => getenv('MAIL_REPLY_TO') ?: 'support@alertaraqc.com',
    'reply_to_name' => getenv('MAIL_REPLY_TO_NAME') ?: 'Alertara QC Support',
];
