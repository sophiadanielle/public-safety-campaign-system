<?php
/**
 * PHPMailer Configuration
 * Email configuration for OTP and notifications
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

class MailConfig
{
    private static ?PHPMailer $mailer = null;

    public static function getMailer(): PHPMailer
    {
        if (self::$mailer === null) {
            self::$mailer = new PHPMailer(true);
            self::configure(self::$mailer);
        }
        return self::$mailer;
    }

    public static function newMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        self::configure($mail);
        return $mail;
    }

    private static function configure(PHPMailer $mail): void
    {
        $env = self::loadEnv();
        $smtpHost = self::env($env, ['SMTP_HOST', 'MAIL_HOST'], 'smtp.gmail.com');
        $smtpPort = (int) self::env($env, ['SMTP_PORT', 'MAIL_PORT'], '587');
        $smtpUser = self::env($env, ['SMTP_USER', 'MAIL_USERNAME'], '');
        $smtpPass = self::env($env, ['SMTP_PASS', 'MAIL_PASSWORD'], '');
        $smtpFromEmail = self::env($env, ['SMTP_FROM_EMAIL', 'SMTP_FROM', 'MAIL_FROM_ADDRESS'], $smtpUser);
        $smtpFromName = self::env($env, ['SMTP_FROM_NAME', 'MAIL_FROM_NAME'], 'Alertara QC');
        $smtpEncryption = strtolower(self::env($env, ['SMTP_ENCRYPTION', 'MAIL_ENCRYPTION'], 'tls'));

        // If from email not set, use SMTP user
        if (empty($smtpFromEmail)) {
            $smtpFromEmail = $smtpUser;
        }

        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = $smtpEncryption === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtpPort;

        // Default sender
        if (!empty($smtpFromEmail)) {
            $mail->setFrom($smtpFromEmail, $smtpFromName);
        }

        // Content settings
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
    }

    private static function loadEnv(): array
    {
        $env = $_ENV;
        $envPath = __DIR__ . '/../../.env';

        if (!file_exists($envPath)) {
            return $env;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }

            $pos = strpos($line, '=');
            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));

            if (strlen($val) >= 2 && (($val[0] === '"' && substr($val, -1) === '"') || ($val[0] === "'" && substr($val, -1) === "'"))) {
                $val = substr($val, 1, -1);
            }

            $env[$key] = $val;
        }

        return $env;
    }

    private static function env(array $env, array $keys, string $default): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $env) && trim((string) $env[$key]) !== '') {
                return (string) $env[$key];
            }
        }

        return $default;
    }

    /**
     * Send OTP email
     */
    public static function sendOTPEmail(string $toEmail, string $toName, string $otp): bool
    {
        try {
            $mail = self::newMailer();

            $mail->addAddress($toEmail, $toName);
            $mail->Subject = 'Your Login Verification Code - Alertara QC';
            $mail->Body = self::getOTPEmailTemplate($otp, $toName);
            $mail->AltBody = "Your verification code is: $otp. This code expires in 1 minute.";

            $mail->send();
            error_log("OTP email sent successfully to: $toEmail");
            return true;
        } catch (Exception $e) {
            error_log("Failed to send OTP email to $toEmail: " . $e->getMessage());
            return false;
        }
    }

    public static function sendPasswordResetEmail(string $toEmail, string $toName, string $otp): bool
    {
        try {
            $mail = self::newMailer();

            $mail->addAddress($toEmail, $toName);
            $mail->Subject = 'Password Reset Code - Alertara QC';
            $mail->Body = self::getPasswordResetEmailTemplate($otp, $toName);
            $mail->AltBody = "Your password reset code is: $otp. This code expires in 1 minute.";

            $mail->send();
            error_log("Password reset email sent successfully to: $toEmail");
            return true;
        } catch (Exception $e) {
            error_log("Failed to send password reset email to $toEmail: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get OTP email HTML template
     */
    private static function getOTPEmailTemplate(string $otp, string $name): string
    {
        $year = date('Y');
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0fdfa;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" style="width: 100%; max-width: 600px; border-collapse: collapse; background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 20px; text-align: center; background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); border-radius: 16px 16px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">Alertara QC</h1>
                            <p style="margin: 8px 0 0; color: rgba(255, 255, 255, 0.9); font-size: 14px;">Public Safety Campaign Management System</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="margin: 0 0 16px; color: #0f172a; font-size: 24px; font-weight: 600;">Verification Code</h2>
                            <p style="margin: 0 0 24px; color: #475569; font-size: 16px; line-height: 1.6;">
                                Hello <strong>{$name}</strong>,<br><br>
                                You're attempting to sign in to your Alertara QC account. Use the verification code below to complete your login:
                            </p>
                            
                            <!-- OTP Code Box -->
                            <div style="background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 100%); border: 2px solid #0d9488; border-radius: 12px; padding: 24px; text-align: center; margin: 24px 0;">
                                <p style="margin: 0 0 8px; color: #0f766e; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Your Verification Code</p>
                                <p style="margin: 0; color: #0d9488; font-size: 42px; font-weight: 800; letter-spacing: 8px; font-family: 'Courier New', monospace;">{$otp}</p>
                            </div>
                            
                            <p style="margin: 24px 0 0; color: #64748b; font-size: 14px; line-height: 1.6;">
                                <strong>⏱️ This code expires in 1 minute.</strong><br><br>
                                If you didn't request this code, please ignore this email or contact support if you have concerns about your account security.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 40px; background-color: #f8fafc; border-radius: 0 0 16px 16px; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0; color: #94a3b8; font-size: 12px; text-align: center; line-height: 1.6;">
                                This is an automated message from Alertara QC.<br>
                                Please do not reply to this email.<br><br>
                                &copy; {$year} Barangay Public Safety Campaign Management System
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    private static function getPasswordResetEmailTemplate(string $otp, string $name): string
    {
        return <<<HTML
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0;">Password Reset</h1>
    </div>
    <div style="padding: 30px; background: #f8fafc;">
        <p>Hello <strong>{$name}</strong>,</p>
        <p>You requested to reset your password. Use the code below to complete the process:</p>
        <div style="background: white; border: 2px solid #0d9488; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0;">
            <span style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #0d9488;">{$otp}</span>
        </div>
        <p style="color: #64748b; font-size: 14px;">This code will expire in 1 minute.</p>
        <p style="color: #64748b; font-size: 14px;">If you didn't request this, please ignore this email.</p>
    </div>
    <div style="padding: 20px; text-align: center; color: #94a3b8; font-size: 12px;">
        <p>Alertara QC - Barangay Public Safety Campaign Management System</p>
    </div>
</div>
HTML;
    }
}
